<?php
declare(strict_types=1);
session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']), 'samesite' => 'Lax']);
session_start();
require_once __DIR__ . '/../config/database.php';

$env = app_env();
$adminUser = env_value($env, 'ADMIN_USERNAME', 'admin');
$adminPassword = env_value($env, 'ADMIN_PASSWORD', '1234');
$adminHash = env_value($env, 'ADMIN_PASSWORD_HASH', '');

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /admin/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $validPassword = $adminHash !== ''
        ? password_verify($password, $adminHash)
        : hash_equals($adminPassword, $password);

    if (hash_equals($adminUser, $username) && $validPassword) {
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        header('Location: /admin/');
        exit;
    }
    $loginError = 'Invalid username or password.';
}

$isAuthenticated = !empty($_SESSION['admin_authenticated']);
if (!$isAuthenticated): ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="AA Dashboard login for AA Space Design project and enquiry management."><meta name="robots" content="noindex,nofollow"><title>AA Dashboard Login | AA Space Design</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f2eb;color:#3d3a35;font:15px Arial,sans-serif}.card{width:min(390px,calc(100% - 40px));background:#fff;padding:32px;border:1px solid #e6dfd3;box-shadow:0 18px 50px #3d3a3512}h1{margin:0 0 8px;font-size:28px}p{color:#736c63;line-height:1.6}.field{display:grid;gap:8px;margin:18px 0;font-weight:700;font-size:13px}input{padding:13px;border:1px solid #d8cec5;font:inherit}button{width:100%;padding:14px;border:0;background:#3d3a35;color:#fff;font-weight:700;cursor:pointer}.error{color:#a21d1d;font-size:13px}</style></head><body><main class="card"><p>AA Associate</p><h1>Admin login</h1><p>View and manage customer enquiries.</p><?php if (!empty($loginError)): ?><p class="error"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?><form method="post"><input type="hidden" name="action" value="login"><label class="field">Username<input name="username" required autocomplete="username"></label><label class="field">Password<input name="password" type="password" required autocomplete="current-password"></label><button type="submit">Sign in</button></form></main></body></html>
<?php exit; endif;

$databaseError = '';
try {
    $pdo = database();
} catch (Throwable $error) {
    error_log('Admin database connection failed: ' . $error->getMessage());
    $databaseError = 'Admin database connection failed. Check cPanel database credentials, privileges, and the private .env path.';
    $pdo = null;
}
if ($databaseError !== ''): ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="AA Dashboard configuration check."><meta name="robots" content="noindex,nofollow"><title>AA Dashboard | AA Space Design</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f2eb;color:#3d3a35;font:15px Arial,sans-serif}.card{width:min(560px,calc(100% - 40px));background:#fff;padding:32px;border:1px solid #e6dfd3;box-shadow:0 18px 50px #3d3a3512}h1{margin:0 0 12px;font-size:26px}p{color:#736c63;line-height:1.6}a{color:#665849;font-weight:700}</style></head><body><main class="card"><h1>Admin setup needs one check</h1><p><?= h($databaseError) ?></p><p>Confirm that <code>/home/aaspaced/cred/.env</code> contains <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, and <code>DB_PASS</code>, then confirm the MySQL user has access to the database.</p><p><a href="/admin/">Return to login</a></p></main></body></html>
<?php exit; endif;

$activeTab = (($_GET['tab'] ?? $_POST['tab'] ?? 'messages') === 'projects') ? 'projects' : 'messages';
$projectMessage = '';
$projectError = '';
$clientMessage = '';
$clientError = '';
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['admin_csrf_token'];
function find_featured_slot_conflict(PDO $pdo, int $featuredOrder, ?int $exceptProjectId = null): ?array {
    $sql = 'SELECT id, title FROM projects WHERE featured_order = :featured_order';
    $params = [':featured_order' => $featuredOrder];
    if ($exceptProjectId !== null) { $sql .= ' AND id <> :except_id'; $params[':except_id'] = $exceptProjectId; }
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetch() ?: null;
}
function featured_slot_conflict_message(int $slot, array $project): string {
    return 'Portfolio ' . $slot . ' is already assigned to “' . (string)$project['title'] . '”. Edit that project and set it to Not featured first.';
}
function posted_tag_ids($value): ?array {
    if (!is_array($value)) { return null; }
    $ids = [];
    foreach ($value as $rawId) {
        $id = filter_var($rawId, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) { return null; }
        $ids[$id] = $id;
    }
    return array_values($ids);
}
function tag_ids_exist(PDO $pdo, string $table, array $ids): bool {
    if (!$ids) { return true; }
    if (!in_array($table, ['clients', 'work_tags'], true)) { return false; }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE id IN (' . $placeholders . ')');
    $statement->execute($ids);
    return count($statement->fetchAll(PDO::FETCH_COLUMN)) === count($ids);
}
function sync_project_tags(PDO $pdo, int $projectId, array $clientIds, array $workTagIds): void {
    $deleteClients = $pdo->prepare('DELETE FROM project_clients WHERE project_id = :project_id');
    $deleteClients->execute([':project_id' => $projectId]);
    $insertClient = $pdo->prepare('INSERT INTO project_clients (project_id, client_id) VALUES (:project_id, :client_id)');
    foreach ($clientIds as $clientId) { $insertClient->execute([':project_id' => $projectId, ':client_id' => $clientId]); }
    $deleteWorkTags = $pdo->prepare('DELETE FROM project_work_tags WHERE project_id = :project_id');
    $deleteWorkTags->execute([':project_id' => $projectId]);
    $insertWorkTag = $pdo->prepare('INSERT INTO project_work_tags (project_id, work_tag_id) VALUES (:project_id, :work_tag_id)');
    foreach ($workTagIds as $workTagId) { $insertWorkTag->execute([':project_id' => $projectId, ':work_tag_id' => $workTagId]); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_client') {
    $clientName = trim((string)($_POST['client_name'] ?? ''));
    $clientSlug = trim((string)preg_replace('/[^a-z0-9]+/i', '-', strtolower($clientName)), '-');
    if ($clientName === '' || strlen($clientName) > 180 || $clientSlug === '') {
        $clientError = 'Enter a client name up to 180 characters.';
    } else {
        try {
            $clientInsert = $pdo->prepare('INSERT INTO clients (name, slug) VALUES (:name, :slug)');
            $clientInsert->execute([':name' => $clientName, ':slug' => substr($clientSlug, 0, 200)]);
            $clientMessage = 'Client tag added.';
        } catch (Throwable $error) {
            $clientError = 'That client tag already exists, or could not be saved.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_project') {
    $clientIds = posted_tag_ids($_POST['client_ids'] ?? []);
    $workTagIds = posted_tag_ids($_POST['work_tag_ids'] ?? []);
    $clientId = $clientIds[0] ?? null;
    $title = trim((string)($_POST['title'] ?? ''));
    $year = filter_var($_POST['year'] ?? null, FILTER_VALIDATE_INT);
    $month = filter_var($_POST['month'] ?? null, FILTER_VALIDATE_INT);
    $description = trim((string)($_POST['description'] ?? ''));
    $imageUrl = trim((string)($_POST['image_url'] ?? ''));
    $embedUrl = trim((string)($_POST['embed_url'] ?? ''));
    $featuredOrderRaw = trim((string)($_POST['featured_order'] ?? ''));
    $featuredOrder = $featuredOrderRaw === '' ? null : filter_var($featuredOrderRaw, FILTER_VALIDATE_INT);
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $projectError = 'This form expired. Reload the page and try again.';
    } elseif ($clientIds === null || !$clientIds || $workTagIds === null || $title === '' || !$year || !$month || $month < 1 || $month > 12 || $description === '') {
        $projectError = 'Choose at least one valid client, then enter a title, year, month, and description.';
    } elseif ($featuredOrderRaw !== '' && ($featuredOrder === false || $featuredOrder < 1 || $featuredOrder > 5)) {
        $projectError = 'Choose Not featured or a Portfolio 1–5 slot.';
    } elseif (($imageUrl !== '' && !filter_var($imageUrl, FILTER_VALIDATE_URL)) || ($embedUrl !== '' && !filter_var($embedUrl, FILTER_VALIDATE_URL))) {
        $projectError = 'Image and Google Drive links must be valid URLs.';
    } else {
        try {
            if (!tag_ids_exist($pdo, 'clients', $clientIds) || !tag_ids_exist($pdo, 'work_tags', $workTagIds)) {
                throw new RuntimeException('Invalid client or work tag.');
            }
            $pdo->beginTransaction();
            $portfolioConflict = $featuredOrder !== null ? find_featured_slot_conflict($pdo, (int)$featuredOrder) : null;
            if ($portfolioConflict) {
                $pdo->rollBack();
                $projectError = featured_slot_conflict_message((int)$featuredOrder, $portfolioConflict);
            } else {
            $statement = $pdo->prepare('INSERT INTO projects (client_id, title, year, month, description, image_url, embed_url, featured_order) VALUES (:client_id, :title, :year, :month, :description, :image_url, :embed_url, :featured_order)');
            $mediaTypes = $_POST['media_type'] ?? [];
            $mediaUrls = $_POST['media_url'] ?? [];
            $mediaLabels = $_POST['media_label'] ?? [];
            $firstImage = $imageUrl;
            $firstDrive = $embedUrl;
            foreach ($mediaUrls as $index => $mediaUrl) {
                $mediaUrl = trim((string)$mediaUrl);
                $mediaType = (($mediaTypes[$index] ?? 'image') === 'drive') ? 'drive' : 'image';
                $mediaLabel = trim((string)($mediaLabels[$index] ?? ''));
                if ($mediaUrl === '' || !filter_var($mediaUrl, FILTER_VALIDATE_URL)) { continue; }
                if ($mediaType === 'image' && $firstImage === '') { $firstImage = $mediaUrl; }
                if ($mediaType === 'drive' && $firstDrive === '') { $firstDrive = $mediaUrl; }
            }
            $statement->execute([':client_id' => $clientId, ':title' => $title, ':year' => $year, ':month' => $month, ':description' => $description, ':image_url' => $firstImage ?: null, ':embed_url' => $firstDrive ?: null, ':featured_order' => $featuredOrder]);
            $projectId = (int)$pdo->lastInsertId();
            sync_project_tags($pdo, $projectId, $clientIds, $workTagIds);
            $mediaStatement = $pdo->prepare('INSERT INTO project_media (project_id, media_type, url, label, sort_order) VALUES (:project_id, :media_type, :url, :label, :sort_order)');
            foreach ($mediaUrls as $index => $mediaUrl) {
                $mediaUrl = trim((string)$mediaUrl);
                $mediaType = (($mediaTypes[$index] ?? 'image') === 'drive') ? 'drive' : 'image';
                if ($mediaUrl !== '' && filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
                    $mediaStatement->execute([':project_id' => $projectId, ':media_type' => $mediaType, ':url' => $mediaUrl, ':label' => trim((string)($mediaLabels[$index] ?? '')) ?: null, ':sort_order' => $index]);
                }
            }
            $pdo->commit();
            $projectMessage = 'Project published successfully.';
            }
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('Admin project insert failed: ' . $error->getMessage());
            $projectError = 'Project could not be saved. Confirm client tags and project schema are installed.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_project') {
    $projectId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $clientIds = posted_tag_ids($_POST['client_ids'] ?? []);
    $workTagIds = posted_tag_ids($_POST['work_tag_ids'] ?? []);
    $clientId = $clientIds ? ($clientIds[0] ?? null) : null;
    $title = trim((string)($_POST['title'] ?? ''));
    $year = filter_var($_POST['year'] ?? null, FILTER_VALIDATE_INT);
    $month = filter_var($_POST['month'] ?? null, FILTER_VALIDATE_INT);
    $description = trim((string)($_POST['description'] ?? ''));
    $imageUrl = trim((string)($_POST['image_url'] ?? ''));
    $embedUrl = trim((string)($_POST['embed_url'] ?? ''));
    $featuredOrderRaw = trim((string)($_POST['featured_order'] ?? ''));
    $featuredOrder = $featuredOrderRaw === '' ? null : filter_var($featuredOrderRaw, FILTER_VALIDATE_INT);
    $status = (string)($_POST['status'] ?? 'Published');
    $mediaTypes = is_array($_POST['media_type'] ?? null) ? $_POST['media_type'] : [];
    $mediaUrls = is_array($_POST['media_url'] ?? null) ? $_POST['media_url'] : [];
    $mediaLabels = is_array($_POST['media_label'] ?? null) ? $_POST['media_label'] : [];

    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $projectError = 'This form expired. Reload the page and try again.';
    } elseif (!$projectId || $clientIds === null || $workTagIds === null || $title === '' || strlen($title) > 180 || !$year || $year < 2000 || $year > 2200 || !$month || $month < 1 || $month > 12 || $description === '' || strlen($description) > 2000) {
        $projectError = 'Choose valid tags, then enter a title, year, month, and description.';
    } elseif (!in_array($status, ['Published', 'Archived', 'Draft'], true)) {
        $projectError = 'Choose a valid project status.';
    } elseif ($featuredOrderRaw !== '' && ($featuredOrder === false || $featuredOrder < 1 || $featuredOrder > 5)) {
        $projectError = 'Choose Not featured or a Portfolio 1–5 slot.';
    } elseif ($featuredOrder !== null && $status !== 'Published') {
        $projectError = 'Only published projects can use a Portfolio slot.';
    } elseif (($imageUrl !== '' && (!filter_var($imageUrl, FILTER_VALIDATE_URL) || strlen($imageUrl) > 1000)) || ($embedUrl !== '' && (!filter_var($embedUrl, FILTER_VALIDATE_URL) || strlen($embedUrl) > 1000))) {
        $projectError = 'Image and Google Drive links must be valid URLs under 1,000 characters.';
    } else {
        $mediaItems = [];
        foreach ($mediaUrls as $index => $mediaUrl) {
            $mediaUrl = trim((string)$mediaUrl);
            if ($mediaUrl === '') { continue; }
            if (!filter_var($mediaUrl, FILTER_VALIDATE_URL) || strlen($mediaUrl) > 1000) {
                $projectError = 'Each project media URL must be valid and under 1,000 characters.';
                break;
            }
            $mediaLabel = trim((string)($mediaLabels[$index] ?? ''));
            if (strlen($mediaLabel) > 180) {
                $projectError = 'Media labels must be 180 characters or fewer.';
                break;
            }
            $mediaType = (($mediaTypes[$index] ?? 'image') === 'drive') ? 'drive' : 'image';
            $mediaItems[] = ['type' => $mediaType, 'url' => $mediaUrl, 'label' => $mediaLabel ?: null];
        }

        if ($projectError === '') {
            try {
                if (!tag_ids_exist($pdo, 'clients', $clientIds) || !tag_ids_exist($pdo, 'work_tags', $workTagIds)) {
                    throw new RuntimeException('Invalid client or work tag.');
                }

                $projectCheck = $pdo->prepare('SELECT id FROM projects WHERE id = :id');
                $projectCheck->execute([':id' => $projectId]);
                if (!$projectCheck->fetch()) { throw new RuntimeException('Project not found.'); }

                $firstImage = $imageUrl;
                $firstDrive = $embedUrl;
                foreach ($mediaItems as $media) {
                    if ($media['type'] === 'image' && $firstImage === '') { $firstImage = $media['url']; }
                    if ($media['type'] === 'drive' && $firstDrive === '') { $firstDrive = $media['url']; }
                }

                $pdo->beginTransaction();
                $portfolioConflict = $featuredOrder !== null ? find_featured_slot_conflict($pdo, (int)$featuredOrder, (int)$projectId) : null;
                if ($portfolioConflict) {
                    $pdo->rollBack();
                    $projectError = featured_slot_conflict_message((int)$featuredOrder, $portfolioConflict);
                } else {
                    $update = $pdo->prepare('UPDATE projects SET client_id = :client_id, title = :title, year = :year, month = :month, description = :description, image_url = :image_url, embed_url = :embed_url, status = :status, featured_order = :featured_order WHERE id = :id');
                    $update->execute([':client_id' => $clientId, ':title' => $title, ':year' => $year, ':month' => $month, ':description' => $description, ':image_url' => $firstImage ?: null, ':embed_url' => $firstDrive ?: null, ':status' => $status, ':featured_order' => $featuredOrder, ':id' => $projectId]);
                    sync_project_tags($pdo, (int)$projectId, $clientIds, $workTagIds);
                    $deleteMedia = $pdo->prepare('DELETE FROM project_media WHERE project_id = :project_id');
                    $deleteMedia->execute([':project_id' => $projectId]);
                    $insertMedia = $pdo->prepare('INSERT INTO project_media (project_id, media_type, url, label, sort_order) VALUES (:project_id, :media_type, :url, :label, :sort_order)');
                    foreach ($mediaItems as $index => $media) {
                        $insertMedia->execute([':project_id' => $projectId, ':media_type' => $media['type'], ':url' => $media['url'], ':label' => $media['label'], ':sort_order' => $index]);
                    }
                    $pdo->commit();
                    header('Location: /admin/?tab=projects&project_saved=updated');
                    exit;
                }
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                error_log('Admin project update failed: ' . $error->getMessage());
                $projectError = 'Project could not be updated. Confirm client tags and project schema are installed.';
            }
        }
    }
}
$clientRows = [];
$workTagGroups = [];
$selectedClientIds = ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['create_project', 'update_project'], true)) ? (posted_tag_ids($_POST['client_ids'] ?? []) ?? []) : [];
$selectedWorkTagIds = ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['create_project', 'update_project'], true)) ? (posted_tag_ids($_POST['work_tag_ids'] ?? []) ?? []) : [];
try {
    $clientRows = $pdo->query('SELECT id, name FROM clients ORDER BY name ASC')->fetchAll();
} catch (Throwable $error) {
    error_log('Admin client query failed: ' . $error->getMessage());
    $clientError = 'Client tags are unavailable. Run the project-tag migration first.';
}
try {
    $workTagRows = $pdo->query('SELECT id, category_key, category_label, name FROM work_tags ORDER BY sort_order ASC, id ASC')->fetchAll();
    foreach ($workTagRows as $workTag) { $workTagGroups[$workTag['category_key']]['label'] = $workTag['category_label']; $workTagGroups[$workTag['category_key']]['tags'][] = $workTag; }
} catch (Throwable $error) {
    error_log('Admin work-tag query failed: ' . $error->getMessage());
    $projectError = 'Work tags are unavailable. Run database/migrations/20261026_project_tags.sql first.';
}
$projectRows = [];
try {
    $projectRows = $pdo->query("SELECT p.*, c.name AS client_name, (SELECT GROUP_CONCAT(c2.name ORDER BY c2.name SEPARATOR ', ') FROM project_clients pc2 JOIN clients c2 ON c2.id = pc2.client_id WHERE pc2.project_id = p.id) AS client_names, (SELECT GROUP_CONCAT(wt.name ORDER BY wt.sort_order SEPARATOR ' · ') FROM project_work_tags pwt JOIN work_tags wt ON wt.id = pwt.work_tag_id WHERE pwt.project_id = p.id) AS work_tag_names, COALESCE(NULLIF(p.image_url, ''), (SELECT pm.url FROM project_media pm WHERE pm.project_id = p.id AND pm.media_type = 'image' ORDER BY pm.sort_order ASC, pm.id ASC LIMIT 1)) AS thumbnail_url FROM projects p LEFT JOIN clients c ON c.id = p.client_id ORDER BY p.year DESC, p.month DESC, p.id DESC")->fetchAll();
} catch (Throwable $error) {
    error_log('Admin project query failed: ' . $error->getMessage());
}
$editProjectInput = $_GET['edit_project'] ?? ((($_POST['action'] ?? '') === 'update_project') ? ($_POST['id'] ?? null) : null);
$editingProjectId = filter_var($editProjectInput, FILTER_VALIDATE_INT);
$editingProject = null;
$editingMediaRows = [];
if ($editingProjectId) {
    try {
        $editStatement = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
        $editStatement->execute([':id' => $editingProjectId]);
        $editingProject = $editStatement->fetch() ?: null;
        if ($editingProject) {
            if (!($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_project' && $projectError !== '')) {
                $clientTagStatement = $pdo->prepare('SELECT client_id FROM project_clients WHERE project_id = :project_id ORDER BY client_id ASC');
                $clientTagStatement->execute([':project_id' => $editingProjectId]);
                $selectedClientIds = array_map('intval', $clientTagStatement->fetchAll(PDO::FETCH_COLUMN));
                $workTagStatement = $pdo->prepare('SELECT work_tag_id FROM project_work_tags WHERE project_id = :project_id ORDER BY work_tag_id ASC');
                $workTagStatement->execute([':project_id' => $editingProjectId]);
                $selectedWorkTagIds = array_map('intval', $workTagStatement->fetchAll(PDO::FETCH_COLUMN));
            }
            $editMediaStatement = $pdo->prepare('SELECT media_type, url, label FROM project_media WHERE project_id = :project_id ORDER BY sort_order ASC, id ASC');
            $editMediaStatement->execute([':project_id' => $editingProjectId]);
            $editingMediaRows = $editMediaStatement->fetchAll();
            $hasLegacyImage = false;
            $hasLegacyVideo = false;
            foreach ($editingMediaRows as $existingMediaRow) {
                if (($existingMediaRow['media_type'] ?? '') === 'image' && ($existingMediaRow['url'] ?? '') === (string)($editingProject['image_url'] ?? '')) { $hasLegacyImage = true; }
                if (($existingMediaRow['media_type'] ?? '') === 'drive' && ($existingMediaRow['url'] ?? '') === (string)($editingProject['embed_url'] ?? '')) { $hasLegacyVideo = true; }
            }
            if (!empty($editingProject['image_url']) && !$hasLegacyImage) {
                array_unshift($editingMediaRows, ['media_type' => 'image', 'url' => $editingProject['image_url'], 'label' => 'Cover image']);
            }
            if (!empty($editingProject['embed_url']) && !$hasLegacyVideo) {
                $editingMediaRows[] = ['media_type' => 'drive', 'url' => $editingProject['embed_url'], 'label' => 'Legacy video'];
            }
        } elseif (isset($_GET['edit_project'])) {
            $projectError = 'Project entry not found.';
        }
    } catch (Throwable $error) {
        error_log('Admin project edit load failed: ' . $error->getMessage());
        $projectError = 'Project entry could not be loaded.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_project' && $projectError !== '') {
    $editingProject = array_merge($editingProject ?? ['id' => (int)$editingProjectId], [
        'client_id' => (int)(($selectedClientIds[0] ?? 0)),
        'title' => (string)($_POST['title'] ?? ''),
        'year' => (int)($_POST['year'] ?? date('Y')),
        'month' => (int)($_POST['month'] ?? date('n')),
        'description' => (string)($_POST['description'] ?? ''),
        'image_url' => (string)($_POST['image_url'] ?? ''),
        'embed_url' => (string)($_POST['embed_url'] ?? ''),
        'status' => (string)($_POST['status'] ?? 'Published'),
        'featured_order' => (string)($_POST['featured_order'] ?? ''),
    ]);
    $editingMediaRows = [];
    foreach ((is_array($_POST['media_url'] ?? null) ? $_POST['media_url'] : []) as $index => $mediaUrl) {
        $editingMediaRows[] = [
            'media_type' => (($mediaTypes[$index] ?? 'image') === 'drive') ? 'drive' : 'image',
            'url' => (string)$mediaUrl,
            'label' => (string)($mediaLabels[$index] ?? ''),
        ];
    }
}
if (($_GET['project_saved'] ?? '') === 'updated') { $projectMessage = 'Project updated successfully.'; }
$rows = [];
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $status = (string)($_POST['status'] ?? 'New');
        $notes = trim((string)($_POST['admin_notes'] ?? ''));
        if ($id && in_array($status, ['New', 'Contacted', 'Closed'], true)) {
            $statement = $pdo->prepare('UPDATE contact_enquiries SET status = :status, admin_notes = :notes WHERE id = :id');
            $statement->execute([':status' => $status, ':notes' => $notes, ':id' => $id]);
        }
    }

    $rows = $pdo->query('SELECT * FROM contact_enquiries ORDER BY created_at DESC')->fetchAll();
} catch (Throwable $error) {
    error_log('Admin enquiry query failed: ' . $error->getMessage());
    $databaseError = 'The admin database connection works, but the contact_enquiries table or its columns do not match the required schema.';
}
if ($databaseError !== ''): ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="AA Dashboard database setup check."><meta name="robots" content="noindex,nofollow"><title>AA Dashboard | AA Space Design</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f2eb;color:#3d3a35;font:15px Arial,sans-serif}.card{width:min(560px,calc(100% - 40px));background:#fff;padding:32px;border:1px solid #e6dfd3;box-shadow:0 18px 50px #3d3a3512}h1{margin:0 0 12px;font-size:26px}p{color:#736c63;line-height:1.6}a{color:#665849;font-weight:700}</style></head><body><main class="card"><h1>Admin table needs one check</h1><p><?= htmlspecialchars($databaseError, ENT_QUOTES, 'UTF-8') ?></p><p>In phpMyAdmin, confirm that table <code>contact_enquiries</code> includes <code>email</code>, <code>preferred_call_date</code>, <code>preferred_call_time</code>, <code>admin_notes</code>, <code>status</code>, and <code>created_at</code>.</p><p><a href="/admin/?logout=1">Return to login</a></p></main></body></html>
<?php exit; endif;
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function whatsapp_url(string $phone, string $name): string {
    $digits = preg_replace('/[^0-9]/', '', $phone);
    $message = rawurlencode("Hello {$name}, thank you for contacting AA Associate. We received your enquiry and will contact you shortly.");
    return 'https://wa.me/' . $digits . '?text=' . $message;
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="AA Dashboard for managing AA Space Design enquiries and project portfolio entries."><meta name="robots" content="noindex,nofollow"><title>AA Dashboard | AA Space Design</title>
<style>body{margin:0;background:#f5f2eb;color:#3d3a35;font:14px Arial,sans-serif}.wrap{width:min(1180px,calc(100% - 32px));margin:0 auto;padding:28px 0 60px}.top{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:25px}h1{margin:0;font-size:30px}.muted{color:#736c63}.field small.muted{font-size:11px;line-height:1.4;font-weight:400}.logout{color:#665849;text-decoration:none;font-weight:700}.list{display:grid;gap:18px}.item{background:#fff;border:1px solid #e6dfd3;padding:22px;box-shadow:0 10px 28px #3d3a350b}.meta{display:flex;flex-wrap:wrap;gap:8px 18px;margin:10px 0;color:#736c63;font-size:13px}.meta strong{color:#3d3a35}.message{white-space:pre-wrap;line-height:1.6;margin:16px 0}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.field{display:grid;gap:7px;color:#665849;font-weight:700;font-size:12px}.field input,.field select,.field textarea{width:100%;box-sizing:border-box;padding:10px;border:1px solid #e6dfd3;background:#fff;font:inherit;color:#3d3a35}.field textarea{min-height:80px;resize:vertical}.actions{display:flex;align-items:end;gap:10px;margin-top:14px}.actions button{padding:11px 16px;border:0;background:#3d3a35;color:#fff;font-weight:700;cursor:pointer}.whatsapp{display:inline-block;padding:11px 16px;background:#665849;color:#fff;text-decoration:none;font-weight:700}.empty{background:#fff;padding:32px;border:1px solid #e6dfd3}.dashboard-tabs{display:flex;gap:8px;margin-bottom:18px;border-bottom:1px solid #e6dfd3}.dashboard-tabs a{padding:12px 16px;color:#736c63;text-decoration:none;font-weight:700;border-bottom:3px solid transparent}.dashboard-tabs a.active{color:#3d3a35;border-color:#665849}.media-builder{display:grid;gap:12px;padding:16px;background:#f8f5f0;border:1px solid #e6dfd3}.media-builder-head{display:flex;justify-content:space-between;align-items:center;gap:12px}.add-media,.remove-media{padding:9px 12px;border:1px solid #d8cec5;background:#fff;color:#665849;font-weight:700;cursor:pointer}.media-rows{display:grid;gap:8px}.media-row{display:grid;grid-template-columns:140px 1fr 1fr auto;gap:8px;align-items:center}.media-row input,.media-row select{min-width:0;padding:10px;border:1px solid #e6dfd3;background:#fff;font:inherit;color:#3d3a35}@media(max-width:650px){.media-row{grid-template-columns:1fr}.media-row .remove-media{width:max-content}}.client-tag-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end;margin:18px 0;padding:16px;background:#f8f5f0;border:1px solid #e6dfd3}.client-tag-form button{padding:11px 16px;border:0;background:#665849;color:#fff;font-weight:700;cursor:pointer}.project-manager,.project-list{background:#fff;border:1px solid #e6dfd3;padding:24px;margin-bottom:18px}.project-manager h2,.project-list h2{margin:0 0 8px;font-size:22px}.project-form{display:grid;gap:14px;margin-top:22px}.project-form button{padding:12px 18px;border:0;background:#3d3a35;color:#fff;font-weight:700;cursor:pointer}.success-message{color:#2f6b45;font-weight:700}.project-list-items{display:grid;gap:12px}.project-list-items article{padding:15px 0;border-top:1px solid #e6dfd3}.project-list-items article div{display:flex;justify-content:space-between;gap:18px}.project-list-items article span{color:#736c63;font-size:12px}.project-list-items article p{margin:8px 0 0;color:#736c63;line-height:1.5;font-size:13px}@media(max-width:650px){.top{align-items:flex-start;flex-direction:column}.client-tag-form{grid-template-columns:1fr}.grid{grid-template-columns:1fr}.actions{align-items:stretch;flex-direction:column}.actions button,.whatsapp{width:100%;box-sizing:border-box;text-align:center}}.project-list-items article .project-row-head{align-items:center}.edit-project{display:inline-flex;align-items:center;justify-content:center;padding:9px 14px;border:1px solid #d8cec5;background:#fff;color:#665849;font-weight:700;text-decoration:none;white-space:nowrap}.edit-project:hover,.edit-project:focus-visible{border-color:#665849;background:#f8f5f0}.project-form-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.project-form .edit-cancel{display:inline-flex;align-items:center;min-height:42px;color:#665849;font-weight:700;text-decoration:underline;text-underline-offset:3px}@media(max-width:650px){.project-list-items article .project-row-head{align-items:flex-start;flex-direction:column}.project-form-actions{align-items:stretch}.project-list-items article .project-row-head{display:grid;grid-template-columns:72px minmax(0,1fr) auto;align-items:center;gap:14px}.project-list-thumb,.project-thumb-empty{width:72px;height:60px;object-fit:cover;border:1px solid #e6dfd3;background:#f1eee8}.project-list-items article .project-thumb-empty{display:grid;place-items:center;color:#736c63;font-size:10px}.project-list-items article .project-row-copy{display:grid;gap:6px;min-width:0}.project-list-items article .project-row-copy>span{color:#736c63;font-size:12px}.project-list-items article .project-row-title{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}.project-list-items article .portfolio-slot-tag{padding:5px 8px;border:1px solid #e6dfd3;background:#f8f5f0;color:#665849;font-size:11px;font-weight:700;white-space:nowrap}.featured-position-field{padding:14px;background:#f8f5f0;border:1px solid #e6dfd3}.featured-position-field .muted{line-height:1.5}@media(max-width:650px){.project-list-items article .project-row-head{grid-template-columns:58px minmax(0,1fr);gap:10px}.project-list-thumb,.project-thumb-empty{width:58px;height:54px}.project-list-items article .project-row-head .edit-project{grid-column:2;justify-self:start}.project-list-items article .project-row-title{align-items:flex-start;flex-direction:column;gap:6px}}}
.project-date-featured-grid{display:grid;grid-template-columns:120px 170px 240px;gap:12px;align-items:end}.featured-position-field{min-width:0;padding:0;border:0;background:transparent}.featured-position-field select{min-width:0}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}.media-builder-head>div{display:grid;gap:4px}.media-builder-hint{font-size:11px;font-weight:400}.media-row{grid-template-columns:140px minmax(0,2fr) minmax(0,1fr) auto;align-items:start}.media-url-control{display:grid;grid-template-columns:minmax(0,1fr) 128px;gap:8px;align-items:start;min-width:0}.media-url-control input{width:100%;min-width:0;box-sizing:border-box}.media-preview{position:relative;display:grid;place-items:center;width:128px;aspect-ratio:16/10;overflow:hidden;border:1px solid #e6dfd3;background:#f1eee8;color:#736c63;font-size:10px;font-weight:400;text-align:center}.media-preview img,.media-preview iframe{display:block;width:100%;height:100%;border:0;object-fit:cover}.media-preview iframe{position:absolute;inset:0}.media-preview .media-preview-poster{position:absolute;inset:0;z-index:0}.media-preview .media-preview-player{position:absolute;inset:0;z-index:1}.media-preview-placeholder{padding:6px}.media-preview--poster .media-preview-player{display:none}@media(max-width:900px){.media-row{grid-template-columns:120px minmax(0,1fr) auto}.media-url-control{grid-column:2;grid-row:1 / span 2;grid-template-columns:minmax(0,1fr) 120px}.media-url-control .media-preview{width:120px}.media-row input[name="media_label[]"]{grid-column:2;grid-row:3}.media-row .remove-media{grid-column:3;grid-row:1;align-self:start}}@media(max-width:650px){.project-manager,.project-list{padding:16px}.project-date-featured-grid{grid-template-columns:minmax(72px,.72fr) minmax(92px,.92fr) minmax(112px,1fr);gap:8px}.project-date-featured-grid .field{font-size:11px}.project-date-featured-grid .field input,.project-date-featured-grid .field select{padding:8px;font-size:12px}.media-builder-head{align-items:flex-start;flex-direction:column}.media-row{grid-template-columns:minmax(0,1fr)}.media-row .media-url-control{grid-column:auto;grid-row:auto;grid-template-columns:minmax(0,1fr) 112px}.media-url-control .media-preview{width:112px}.media-row input[name="media_label[]"]{grid-column:auto;grid-row:auto}.media-row .remove-media{grid-column:auto;grid-row:auto;width:max-content}}@media(max-width:360px){.project-date-featured-grid{grid-template-columns:56px minmax(0,.9fr) minmax(0,1.3fr);gap:6px}.project-date-featured-grid .field{font-size:10px;white-space:nowrap}.project-date-featured-grid .field input,.project-date-featured-grid .field select{padding:7px 5px;font-size:11px}}
.tag-picker{min-width:0;margin:0;padding:16px;border:1px solid #e6dfd3;background:#fff}.tag-picker legend{padding:0 7px;color:#3d3a35;font-size:14px;font-weight:700}.tag-picker-help{margin:0 0 12px;font-size:12px;line-height:1.5}.tag-choice-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,230px),1fr));gap:8px}.tag-choice{display:flex;align-items:flex-start;gap:10px;min-height:42px;padding:10px 12px;border:1px solid #e6dfd3;background:#fff;color:#3d3a35;font-size:13px;font-weight:400;line-height:1.4;cursor:pointer}.tag-choice:hover{border-color:#b8aa99;background:#fcfaf7}.tag-choice:has(input:checked){border-color:#8b7762;background:#f8f5f0}.tag-choice input{flex:0 0 17px;width:17px;height:17px;margin:1px 0 0;accent-color:#665849}.tag-choice input:focus-visible{outline:2px solid #8b7762;outline-offset:3px}.work-tag-picker{display:grid;gap:12px}.work-tag-group{border:1px solid #e6dfd3;background:#f8f5f0}.work-tag-group summary{padding:12px 14px;color:#665849;font-size:12px;font-weight:700;cursor:pointer}.work-tag-group[open] summary{border-bottom:1px solid #e6dfd3}.work-tag-group .tag-choice-grid{padding:12px}.project-tag-list{padding:8px 10px;border-left:2px solid #b8aa99;background:#f8f5f0}</style></head><body><main class="wrap"><header class="top"><div><p class="muted">AA Space Design</p><h1>AA Dashboard</h1></div><a class="logout" href="?logout=1">Log out</a></header><nav class="dashboard-tabs" aria-label="Dashboard sections"><a class="<?= $activeTab === 'messages' ? 'active' : '' ?>" href="?tab=messages">User Messages</a><a class="<?= $activeTab === 'projects' ? 'active' : '' ?>" href="?tab=projects">Projects Submit</a></nav><?php if ($activeTab === 'projects'): ?>
<?php $isEditingProject = !empty($editingProject); ?>
<section class="project-manager">
  <div>
    <p class="muted">Project archive</p>
    <h2><?= $isEditingProject ? 'Edit project entry' : 'Publish a new project' ?></h2>
    <p class="muted">Content saves to MySQL. No Git commit is created for content-only updates.</p>
  </div>
  <?php if ($clientMessage): ?><p class="success-message"><?= h($clientMessage) ?></p><?php endif; ?>
  <?php if ($clientError): ?><p class="error"><?= h($clientError) ?></p><?php endif; ?>
  <form method="post" class="client-tag-form">
    <input type="hidden" name="tab" value="projects">
    <input type="hidden" name="action" value="create_client">
    <label class="field">Add client tag<input name="client_name" required maxlength="180" placeholder="Enter a new client name"></label>
    <button type="submit">Add client</button>
  </form>
  <?php if ($projectMessage): ?><p class="success-message" role="status"><?= h($projectMessage) ?></p><?php endif; ?>
  <?php if ($projectError): ?><p class="error" role="alert"><?= h($projectError) ?></p><?php endif; ?>
  <form method="post" class="project-form">
    <input type="hidden" name="action" value="<?= $isEditingProject ? 'update_project' : 'create_project' ?>">
    <input type="hidden" name="tab" value="projects">
    <?php if ($isEditingProject): ?>
      <input type="hidden" name="id" value="<?= (int)$editingProject['id'] ?>">
    <?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
    <fieldset class="tag-picker" aria-describedby="client-tags-help">
      <legend>Client tags</legend>
      <p id="client-tags-help" class="muted tag-picker-help">Choose one or more clients. Add new names above.</p>
      <?php if ($clientRows): ?><div class="tag-choice-grid">
        <?php foreach ($clientRows as $client): ?>
          <label class="tag-choice"><input type="checkbox" name="client_ids[]" value="<?= (int)$client['id'] ?>" <?= in_array((int)$client['id'], $selectedClientIds, true) ? 'checked' : '' ?>><span><?= h((string)$client['name']) ?></span></label>
        <?php endforeach; ?>
      </div><?php else: ?><p class="muted">Add a client tag before publishing a project.</p><?php endif; ?>
    </fieldset>
    <fieldset class="tag-picker work-tag-picker" aria-describedby="work-tags-help">
      <legend>Work-type tags</legend>
      <p id="work-tags-help" class="muted tag-picker-help">Choose every service that applies. Leave all unchecked if not confirmed.</p>
      <?php foreach ($workTagGroups as $group): ?>
        <details class="work-tag-group" open>
          <summary><?= h((string)$group['label']) ?></summary>
          <div class="tag-choice-grid">
            <?php foreach ($group['tags'] as $workTag): ?>
              <label class="tag-choice"><input type="checkbox" name="work_tag_ids[]" value="<?= (int)$workTag['id'] ?>" <?= in_array((int)$workTag['id'], $selectedWorkTagIds, true) ? 'checked' : '' ?>><span><?= h((string)$workTag['name']) ?></span></label>
            <?php endforeach; ?>
          </div>
        </details>
      <?php endforeach; ?>
    </fieldset>
    <label class="field">Title<input name="title" required maxlength="180" placeholder="Example: Karachi office fit-out" value="<?= h((string)($editingProject['title'] ?? '')) ?>"></label>
    <?php $featuredOrderValue = $isEditingProject ? (string)($editingProject['featured_order'] ?? '') : (string)($_POST['featured_order'] ?? ''); ?>
    <div class="project-date-featured-grid">
      <label class="field">Year<input name="year" type="number" min="2000" max="2200" value="<?= h((string)($editingProject['year'] ?? date('Y'))) ?>" required></label>
      <label class="field">Month
        <select name="month" required>
          <?php for ($month = 1; $month <= 12; $month++): ?>
            <option value="<?= $month ?>" <?= (int)($editingProject['month'] ?? date('n')) === $month ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $month, 1)) ?></option>
          <?php endfor; ?>
        </select>
      </label>
      <label class="field featured-position-field">Portfolio position
        <select name="featured_order" aria-describedby="featured-position-help">
          <option value="" <?= $featuredOrderValue === '' ? 'selected' : '' ?>>Not featured</option>
          <?php for ($featuredSlot = 1; $featuredSlot <= 5; $featuredSlot++): ?>
            <option value="<?= $featuredSlot ?>" <?= $featuredOrderValue === (string)$featuredSlot ? 'selected' : '' ?>>Portfolio <?= $featuredSlot ?></option>
          <?php endfor; ?>
        </select>
        <span id="featured-position-help" class="sr-only">Choose one of five homepage slots. Each slot can hold one published project.</span>
      </label>
    </div>
    <label class="field">Description<textarea name="description" required maxlength="2000" placeholder="Short project summary for the public archive"><?= h((string)($editingProject['description'] ?? '')) ?></textarea></label>
    <?php if ($isEditingProject): ?>
      <label class="field">Status
        <select name="status" required>
          <?php foreach (['Published', 'Archived', 'Draft'] as $projectStatus): ?>
            <option value="<?= h($projectStatus) ?>" <?= ($editingProject['status'] ?? 'Published') === $projectStatus ? 'selected' : '' ?>><?= h($projectStatus) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
    <div class="media-builder">
      <div class="media-builder-head"><div><strong>Project media</strong><span class="muted media-builder-hint">Preview appears beside each link.</span></div><button type="button" class="add-media" data-action="add-media">+ Add image or Google Drive video</button></div>
      <div id="media-rows">
        <?php $formMediaRows = $editingMediaRows ?: [['media_type' => 'image', 'url' => '', 'label' => '']]; ?>
        <?php foreach ($formMediaRows as $mediaRow): ?>
          <div class="media-row">
            <select name="media_type[]" aria-label="Media type">
              <option value="image" <?= ($mediaRow['media_type'] ?? 'image') === 'image' ? 'selected' : '' ?>>Image</option>
              <option value="drive" <?= ($mediaRow['media_type'] ?? '') === 'drive' ? 'selected' : '' ?>>Google Drive Video</option>
            </select>
            <div class="media-url-control">
              <input name="media_url[]" type="url" placeholder="https://..." <?= $isEditingProject ? '' : 'required' ?> aria-label="Media URL" value="<?= h((string)($mediaRow['url'] ?? '')) ?>">
              <div class="media-preview" data-media-preview role="status" aria-live="polite" aria-label="Media preview">Paste a link to preview</div>
            </div>
            <input name="media_label[]" placeholder="Label (optional)" aria-label="Media label" value="<?= h((string)($mediaRow['label'] ?? '')) ?>">
            <button type="button" class="remove-media" data-action="remove-media" aria-label="Remove media">Remove</button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="project-form-actions">
      <button type="submit"><?= $isEditingProject ? 'Save changes' : 'Publish project' ?></button>
      <?php if ($isEditingProject): ?><a href="?tab=projects" class="edit-cancel">Cancel</a><?php endif; ?>
    </div>
  </form>
</section>
<section class="project-list">
  <h2>Published project entries</h2>
  <?php if (!$projectRows): ?>
    <p class="empty">No projects yet.</p>
  <?php else: ?>
    <div class="project-list-items">
      <?php foreach ($projectRows as $project): ?>
        <article>
          <div class="project-row-head">
            <?php if (!empty($project['thumbnail_url'])): ?>
              <img class="project-list-thumb" src="<?= h((string)$project['thumbnail_url']) ?>" alt="Cover image for <?= h((string)$project['title']) ?>" loading="lazy">
            <?php else: ?>
              <div class="project-thumb-empty" aria-hidden="true">No cover</div>
            <?php endif; ?>
            <div class="project-row-copy">
              <div class="project-row-title"><strong><?= h((string)$project['title']) ?></strong><span class="portfolio-slot-tag"><?= !empty($project['featured_order']) ? 'Portfolio ' . (int)$project['featured_order'] : 'Not featured' ?></span></div>
              <span><?= h((string)($project['client_names'] ?: ($project['client_name'] ?? 'Unassigned'))) ?> · <?= h((string)$project['month']) ?>/<?= h((string)$project['year']) ?> · <?= h((string)$project['status']) ?></span>
            </div>
            <a class="edit-project" href="?tab=projects&amp;edit_project=<?= (int)$project['id'] ?>" aria-label="Edit <?= h((string)$project['title']) ?>">Edit</a>
          </div>
          <?php if (!empty($project['work_tag_names'])): ?><p class="project-tag-list"><strong>Work tags:</strong> <?= h((string)$project['work_tag_names']) ?></p><?php endif; ?>
          <p><?= h((string)$project['description']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php if ($activeTab === 'messages'): ?><section class="list">
<?php if (!$rows): ?><p class="empty">No enquiries yet.</p><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="item"><h2><?= h((string)$row['name']) ?></h2><div class="meta"><span><strong>Email:</strong> <?= h((string)$row['email']) ?></span><span><strong>Phone:</strong> <?= h((string)$row['phone_whatsapp']) ?></span><span><strong>Project:</strong> <?= h((string)$row['project_type']) ?></span><span><strong>City:</strong> <?= h((string)$row['city']) ?></span><span><strong>Submitted:</strong> <?= h((string)$row['created_at']) ?></span></div><div class="meta"><span><strong>Budget:</strong> <?= h((string)($row['budget_range'] ?: 'Not provided')) ?></span><span><strong>Preferred call:</strong> <?= h(trim((string)($row['preferred_call_date'] ?: 'Not provided') . ' ' . (string)($row['preferred_call_time'] ?: ''))) ?></span></div><p class="message"><?= h((string)$row['message']) ?></p><form method="post"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><div class="grid"><label class="field">Status<select name="status"><?php foreach (['New','Contacted','Closed'] as $status): ?><option <?= $row['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label><label class="field">Your notes<textarea name="admin_notes" placeholder="Internal note about this customer"><?= h((string)($row['admin_notes'] ?? '')) ?></textarea></label></div><div class="actions"><button type="submit">Save update</button><a class="whatsapp" href="<?= h(whatsapp_url((string)$row['phone_whatsapp'], (string)$row['name'])) ?>" target="_blank" rel="noopener">Open WhatsApp</a></div></form></article><?php endforeach; ?></section><?php endif; ?></main><script type="module" src="/admin/project-media-preview.js"></script></body></html>
