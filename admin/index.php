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
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login | AA Associate</title>
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
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin configuration error | AA Associate</title>
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
    $clientId = filter_var($_POST['client_id'] ?? null, FILTER_VALIDATE_INT);
    $title = trim((string)($_POST['title'] ?? ''));
    $year = filter_var($_POST['year'] ?? null, FILTER_VALIDATE_INT);
    $month = filter_var($_POST['month'] ?? null, FILTER_VALIDATE_INT);
    $description = trim((string)($_POST['description'] ?? ''));
    $imageUrl = trim((string)($_POST['image_url'] ?? ''));
    $embedUrl = trim((string)($_POST['embed_url'] ?? ''));
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $projectError = 'This form expired. Reload the page and try again.';
    } elseif (!$clientId || $title === '' || !$year || !$month || $month < 1 || $month > 12 || $description === '') {
        $projectError = 'Client, title, valid year, month, and description are required.';
    } elseif (($imageUrl !== '' && !filter_var($imageUrl, FILTER_VALIDATE_URL)) || ($embedUrl !== '' && !filter_var($embedUrl, FILTER_VALIDATE_URL))) {
        $projectError = 'Image and Google Drive links must be valid URLs.';
    } else {
        try {
            $clientCheck = $pdo->prepare('SELECT id FROM clients WHERE id = :client_id');
            $clientCheck->execute([':client_id' => $clientId]);
            if (!$clientCheck->fetch()) { throw new RuntimeException('Invalid client tag.'); }
            $pdo->beginTransaction();
            $statement = $pdo->prepare('INSERT INTO projects (client_id, title, year, month, description, image_url, embed_url) VALUES (:client_id, :title, :year, :month, :description, :image_url, :embed_url)');
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
            $statement->execute([':client_id' => $clientId, ':title' => $title, ':year' => $year, ':month' => $month, ':description' => $description, ':image_url' => $firstImage ?: null, ':embed_url' => $firstDrive ?: null]);
            $projectId = (int)$pdo->lastInsertId();
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
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('Admin project insert failed: ' . $error->getMessage());
            $projectError = 'Project could not be saved. Confirm client tags and project schema are installed.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_project') {
    $projectId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $clientInput = trim((string)($_POST['client_id'] ?? ''));
    $clientId = $clientInput === '' ? null : filter_var($clientInput, FILTER_VALIDATE_INT);
    $title = trim((string)($_POST['title'] ?? ''));
    $year = filter_var($_POST['year'] ?? null, FILTER_VALIDATE_INT);
    $month = filter_var($_POST['month'] ?? null, FILTER_VALIDATE_INT);
    $description = trim((string)($_POST['description'] ?? ''));
    $imageUrl = trim((string)($_POST['image_url'] ?? ''));
    $embedUrl = trim((string)($_POST['embed_url'] ?? ''));
    $status = (string)($_POST['status'] ?? 'Published');
    $mediaTypes = is_array($_POST['media_type'] ?? null) ? $_POST['media_type'] : [];
    $mediaUrls = is_array($_POST['media_url'] ?? null) ? $_POST['media_url'] : [];
    $mediaLabels = is_array($_POST['media_label'] ?? null) ? $_POST['media_label'] : [];

    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $projectError = 'This form expired. Reload the page and try again.';
    } elseif (!$projectId || $clientId === false || ($clientId !== null && $clientId < 1) || $title === '' || strlen($title) > 180 || !$year || $year < 2000 || $year > 2200 || !$month || $month < 1 || $month > 12 || $description === '' || strlen($description) > 2000) {
        $projectError = 'Client, title, valid year, month, and description are required.';
    } elseif (!in_array($status, ['Published', 'Archived', 'Draft'], true)) {
        $projectError = 'Choose a valid project status.';
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
                if ($clientId !== null) {
                    $clientCheck = $pdo->prepare('SELECT id FROM clients WHERE id = :client_id');
                    $clientCheck->execute([':client_id' => $clientId]);
                    if (!$clientCheck->fetch()) { throw new RuntimeException('Invalid client tag.'); }
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
                $update = $pdo->prepare('UPDATE projects SET client_id = :client_id, title = :title, year = :year, month = :month, description = :description, image_url = :image_url, embed_url = :embed_url, status = :status WHERE id = :id');
                $update->execute([':client_id' => $clientId, ':title' => $title, ':year' => $year, ':month' => $month, ':description' => $description, ':image_url' => $firstImage ?: null, ':embed_url' => $firstDrive ?: null, ':status' => $status, ':id' => $projectId]);
                $deleteMedia = $pdo->prepare('DELETE FROM project_media WHERE project_id = :project_id');
                $deleteMedia->execute([':project_id' => $projectId]);
                $insertMedia = $pdo->prepare('INSERT INTO project_media (project_id, media_type, url, label, sort_order) VALUES (:project_id, :media_type, :url, :label, :sort_order)');
                foreach ($mediaItems as $index => $media) {
                    $insertMedia->execute([':project_id' => $projectId, ':media_type' => $media['type'], ':url' => $media['url'], ':label' => $media['label'], ':sort_order' => $index]);
                }
                $pdo->commit();
                header('Location: /admin/?tab=projects&project_saved=updated');
                exit;
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                error_log('Admin project update failed: ' . $error->getMessage());
                $projectError = 'Project could not be updated. Confirm client tags and project schema are installed.';
            }
        }
    }
}
$clientRows = [];
try {
    $clientRows = $pdo->query('SELECT id, name FROM clients ORDER BY name ASC')->fetchAll();
} catch (Throwable $error) {
    error_log('Admin client query failed: ' . $error->getMessage());
    $clientError = 'Client tags are unavailable. Run database/migrations/20261023_client_tags.sql first.';
}
$projectRows = [];
try {
    $projectRows = $pdo->query('SELECT p.*, c.name AS client_name FROM projects p LEFT JOIN clients c ON c.id = p.client_id ORDER BY p.year DESC, p.month DESC, p.id DESC')->fetchAll();
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
            $editMediaStatement = $pdo->prepare('SELECT media_type, url, label FROM project_media WHERE project_id = :project_id ORDER BY sort_order ASC, id ASC');
            $editMediaStatement->execute([':project_id' => $editingProjectId]);
            $editingMediaRows = $editMediaStatement->fetchAll();
            if (!$editingMediaRows) {
                if (!empty($editingProject['image_url'])) { $editingMediaRows[] = ['media_type' => 'image', 'url' => $editingProject['image_url'], 'label' => '']; }
                if (!empty($editingProject['embed_url'])) { $editingMediaRows[] = ['media_type' => 'drive', 'url' => $editingProject['embed_url'], 'label' => '']; }
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
        'client_id' => (int)($_POST['client_id'] ?? 0),
        'title' => (string)($_POST['title'] ?? ''),
        'year' => (int)($_POST['year'] ?? date('Y')),
        'month' => (int)($_POST['month'] ?? date('n')),
        'description' => (string)($_POST['description'] ?? ''),
        'image_url' => (string)($_POST['image_url'] ?? ''),
        'embed_url' => (string)($_POST['embed_url'] ?? ''),
        'status' => (string)($_POST['status'] ?? 'Published'),
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
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin table error | AA Associate</title>
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
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Enquiries | AA Associate</title>
<style>body{margin:0;background:#f5f2eb;color:#3d3a35;font:14px Arial,sans-serif}.wrap{width:min(1180px,calc(100% - 32px));margin:0 auto;padding:28px 0 60px}.top{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:25px}h1{margin:0;font-size:30px}.muted{color:#736c63}.logout{color:#665849;text-decoration:none;font-weight:700}.list{display:grid;gap:18px}.item{background:#fff;border:1px solid #e6dfd3;padding:22px;box-shadow:0 10px 28px #3d3a350b}.meta{display:flex;flex-wrap:wrap;gap:8px 18px;margin:10px 0;color:#736c63;font-size:13px}.meta strong{color:#3d3a35}.message{white-space:pre-wrap;line-height:1.6;margin:16px 0}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.field{display:grid;gap:7px;color:#665849;font-weight:700;font-size:12px}.field input,.field select,.field textarea{width:100%;box-sizing:border-box;padding:10px;border:1px solid #e6dfd3;background:#fff;font:inherit;color:#3d3a35}.field textarea{min-height:80px;resize:vertical}.actions{display:flex;align-items:end;gap:10px;margin-top:14px}.actions button{padding:11px 16px;border:0;background:#3d3a35;color:#fff;font-weight:700;cursor:pointer}.whatsapp{display:inline-block;padding:11px 16px;background:#665849;color:#fff;text-decoration:none;font-weight:700}.empty{background:#fff;padding:32px;border:1px solid #e6dfd3}.dashboard-tabs{display:flex;gap:8px;margin-bottom:18px;border-bottom:1px solid #e6dfd3}.dashboard-tabs a{padding:12px 16px;color:#736c63;text-decoration:none;font-weight:700;border-bottom:3px solid transparent}.dashboard-tabs a.active{color:#3d3a35;border-color:#665849}.media-builder{display:grid;gap:12px;padding:16px;background:#f8f5f0;border:1px solid #e6dfd3}.media-builder-head{display:flex;justify-content:space-between;align-items:center;gap:12px}.add-media,.remove-media{padding:9px 12px;border:1px solid #d8cec5;background:#fff;color:#665849;font-weight:700;cursor:pointer}.media-rows{display:grid;gap:8px}.media-row{display:grid;grid-template-columns:140px 1fr 1fr auto;gap:8px;align-items:center}.media-row input,.media-row select{min-width:0;padding:10px;border:1px solid #e6dfd3;background:#fff;font:inherit;color:#3d3a35}@media(max-width:650px){.media-row{grid-template-columns:1fr}.media-row .remove-media{width:max-content}}.client-tag-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end;margin:18px 0;padding:16px;background:#f8f5f0;border:1px solid #e6dfd3}.client-tag-form button{padding:11px 16px;border:0;background:#665849;color:#fff;font-weight:700;cursor:pointer}.project-manager,.project-list{background:#fff;border:1px solid #e6dfd3;padding:24px;margin-bottom:18px}.project-manager h2,.project-list h2{margin:0 0 8px;font-size:22px}.project-form{display:grid;gap:14px;margin-top:22px}.project-form button{padding:12px 18px;border:0;background:#3d3a35;color:#fff;font-weight:700;cursor:pointer}.success-message{color:#2f6b45;font-weight:700}.project-list-items{display:grid;gap:12px}.project-list-items article{padding:15px 0;border-top:1px solid #e6dfd3}.project-list-items article div{display:flex;justify-content:space-between;gap:18px}.project-list-items article span{color:#736c63;font-size:12px}.project-list-items article p{margin:8px 0 0;color:#736c63;line-height:1.5;font-size:13px}@media(max-width:650px){.top{align-items:flex-start;flex-direction:column}.client-tag-form{grid-template-columns:1fr}.grid{grid-template-columns:1fr}.actions{align-items:stretch;flex-direction:column}.actions button,.whatsapp{width:100%;box-sizing:border-box;text-align:center}}.project-list-items article .project-row-head{align-items:center}.edit-project{display:inline-flex;align-items:center;justify-content:center;padding:9px 14px;border:1px solid #d8cec5;background:#fff;color:#665849;font-weight:700;text-decoration:none;white-space:nowrap}.edit-project:hover,.edit-project:focus-visible{border-color:#665849;background:#f8f5f0}.project-form-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.project-form .edit-cancel{display:inline-flex;align-items:center;min-height:42px;color:#665849;font-weight:700;text-decoration:underline;text-underline-offset:3px}@media(max-width:650px){.project-list-items article .project-row-head{align-items:flex-start;flex-direction:column}.project-form-actions{align-items:stretch}.project-form-actions>*{text-align:center}}</style></head>
<body><main class="wrap"><header class="top"><div><p class="muted">AA Associate</p><h1>Customer enquiries</h1></div><a class="logout" href="?logout=1">Log out</a></header><nav class="dashboard-tabs" aria-label="Dashboard sections"><a class="<?= $activeTab === 'messages' ? 'active' : '' ?>" href="?tab=messages">User Messages</a><a class="<?= $activeTab === 'projects' ? 'active' : '' ?>" href="?tab=projects">Projects Submit</a></nav><?php if ($activeTab === 'projects'): ?>
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
    <label class="field">Client tag
      <select name="client_id" <?= $isEditingProject ? '' : 'required' ?>>
        <option value="" <?= (!$isEditingProject || empty($editingProject['client_id'])) ? 'selected' : '' ?> <?= $isEditingProject ? '' : 'disabled' ?>><?= $isEditingProject ? 'Unassigned' : 'Select a client' ?></option>
        <?php foreach ($clientRows as $client): ?>
          <option value="<?= (int)$client['id'] ?>" <?= $isEditingProject && (int)$editingProject['client_id'] === (int)$client['id'] ? 'selected' : '' ?>><?= h((string)$client['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field">Title<input name="title" required maxlength="180" placeholder="Example: Karachi office fit-out" value="<?= h((string)($editingProject['title'] ?? '')) ?>"></label>
    <div class="grid">
      <label class="field">Year<input name="year" type="number" min="2000" max="2200" value="<?= h((string)($editingProject['year'] ?? date('Y'))) ?>" required></label>
      <label class="field">Month
        <select name="month" required>
          <?php for ($month = 1; $month <= 12; $month++): ?>
            <option value="<?= $month ?>" <?= (int)($editingProject['month'] ?? date('n')) === $month ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $month, 1)) ?></option>
          <?php endfor; ?>
        </select>
      </label>
    </div>
    <label class="field">Description<textarea name="description" required maxlength="2000" placeholder="Short project summary for the public archive"><?= h((string)($editingProject['description'] ?? '')) ?></textarea></label>
    <label class="field">Image URL <span class="muted">Optional</span><input name="image_url" type="url" placeholder="https://..." value="<?= h((string)($editingProject['image_url'] ?? '')) ?>"></label>
    <label class="field">Google Drive embed/media link <span class="muted">Optional legacy link</span><input name="embed_url" type="url" placeholder="https://drive.google.com/..." value="<?= h((string)($editingProject['embed_url'] ?? '')) ?>"></label>
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
      <div class="media-builder-head"><strong>Project media</strong><button type="button" class="add-media" onclick="addMediaRow()">+ Add image or Google Drive link</button></div>
      <div id="media-rows">
        <?php $formMediaRows = $editingMediaRows ?: [['media_type' => 'image', 'url' => '', 'label' => '']]; ?>
        <?php foreach ($formMediaRows as $mediaRow): ?>
          <div class="media-row">
            <select name="media_type[]" aria-label="Media type">
              <option value="image" <?= ($mediaRow['media_type'] ?? 'image') === 'image' ? 'selected' : '' ?>>Image</option>
              <option value="drive" <?= ($mediaRow['media_type'] ?? '') === 'drive' ? 'selected' : '' ?>>Google Drive</option>
            </select>
            <input name="media_url[]" type="url" placeholder="https://..." <?= $isEditingProject ? '' : 'required' ?> aria-label="Media URL" value="<?= h((string)($mediaRow['url'] ?? '')) ?>">
            <input name="media_label[]" placeholder="Label (optional)" aria-label="Media label" value="<?= h((string)($mediaRow['label'] ?? '')) ?>">
            <button type="button" class="remove-media" onclick="removeMediaRow(this)" aria-label="Remove media">Remove</button>
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
            <strong><?= h((string)$project['title']) ?></strong>
            <span><?= h((string)($project['client_name'] ?? 'Unassigned')) ?> · <?= h((string)$project['month']) ?>/<?= h((string)$project['year']) ?> · <?= h((string)$project['status']) ?></span>
            <a class="edit-project" href="?tab=projects&amp;edit_project=<?= (int)$project['id'] ?>" aria-label="Edit <?= h((string)$project['title']) ?>">Edit</a>
          </div>
          <p><?= h((string)$project['description']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php if ($activeTab === 'messages'): ?><section class="list">
<?php if (!$rows): ?><p class="empty">No enquiries yet.</p><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="item"><h2><?= h((string)$row['name']) ?></h2><div class="meta"><span><strong>Email:</strong> <?= h((string)$row['email']) ?></span><span><strong>Phone:</strong> <?= h((string)$row['phone_whatsapp']) ?></span><span><strong>Project:</strong> <?= h((string)$row['project_type']) ?></span><span><strong>City:</strong> <?= h((string)$row['city']) ?></span><span><strong>Submitted:</strong> <?= h((string)$row['created_at']) ?></span></div><div class="meta"><span><strong>Budget:</strong> <?= h((string)($row['budget_range'] ?: 'Not provided')) ?></span><span><strong>Preferred call:</strong> <?= h(trim((string)($row['preferred_call_date'] ?: 'Not provided') . ' ' . (string)($row['preferred_call_time'] ?: ''))) ?></span></div><p class="message"><?= h((string)$row['message']) ?></p><form method="post"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><div class="grid"><label class="field">Status<select name="status"><?php foreach (['New','Contacted','Closed'] as $status): ?><option <?= $row['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label><label class="field">Your notes<textarea name="admin_notes" placeholder="Internal note about this customer"><?= h((string)($row['admin_notes'] ?? '')) ?></textarea></label></div><div class="actions"><button type="submit">Save update</button><a class="whatsapp" href="<?= h(whatsapp_url((string)$row['phone_whatsapp'], (string)$row['name'])) ?>" target="_blank" rel="noopener">Open WhatsApp</a></div></form></article><?php endforeach; ?></section><?php endif; ?></main><script>function addMediaRow(){const row=document.querySelector('.media-row').cloneNode(true);row.querySelectorAll('input').forEach(input=>{input.value=''});document.getElementById('media-rows').appendChild(row)}function removeMediaRow(button){const rows=document.querySelectorAll('.media-row');if(rows.length>1)button.closest('.media-row').remove()}</script></body></html>
