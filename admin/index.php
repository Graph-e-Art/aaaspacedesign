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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_project') {
    $title = trim((string)($_POST['title'] ?? ''));
    $year = filter_var($_POST['year'] ?? null, FILTER_VALIDATE_INT);
    $month = filter_var($_POST['month'] ?? null, FILTER_VALIDATE_INT);
    $description = trim((string)($_POST['description'] ?? ''));
    $imageUrl = trim((string)($_POST['image_url'] ?? ''));
    $embedUrl = trim((string)($_POST['embed_url'] ?? ''));
    if ($title === '' || !$year || !$month || $month < 1 || $month > 12 || $description === '') {
        $projectError = 'Title, valid year, month, and description are required.';
    } elseif (($imageUrl !== '' && !filter_var($imageUrl, FILTER_VALIDATE_URL)) || ($embedUrl !== '' && !filter_var($embedUrl, FILTER_VALIDATE_URL))) {
        $projectError = 'Image and Google Drive links must be valid URLs.';
    } else {
        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare('INSERT INTO projects (title, year, month, description, image_url, embed_url) VALUES (:title, :year, :month, :description, :image_url, :embed_url)');
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
            $statement->execute([':title' => $title, ':year' => $year, ':month' => $month, ':description' => $description, ':image_url' => $firstImage ?: null, ':embed_url' => $firstDrive ?: null]);
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
            $projectError = 'Project could not be saved. Confirm that the projects table exists.';
        }
    }
}
$projectRows = [];
try {
    $projectRows = $pdo->query('SELECT * FROM projects ORDER BY year DESC, month DESC, id DESC')->fetchAll();
} catch (Throwable $error) {
    error_log('Admin project query failed: ' . $error->getMessage());
}
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
<style>body{margin:0;background:#f5f2eb;color:#3d3a35;font:14px Arial,sans-serif}.wrap{width:min(1180px,calc(100% - 32px));margin:0 auto;padding:28px 0 60px}.top{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:25px}h1{margin:0;font-size:30px}.muted{color:#736c63}.logout{color:#665849;text-decoration:none;font-weight:700}.list{display:grid;gap:18px}.item{background:#fff;border:1px solid #e6dfd3;padding:22px;box-shadow:0 10px 28px #3d3a350b}.meta{display:flex;flex-wrap:wrap;gap:8px 18px;margin:10px 0;color:#736c63;font-size:13px}.meta strong{color:#3d3a35}.message{white-space:pre-wrap;line-height:1.6;margin:16px 0}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.field{display:grid;gap:7px;color:#665849;font-weight:700;font-size:12px}.field input,.field select,.field textarea{width:100%;box-sizing:border-box;padding:10px;border:1px solid #e6dfd3;background:#fff;font:inherit;color:#3d3a35}.field textarea{min-height:80px;resize:vertical}.actions{display:flex;align-items:end;gap:10px;margin-top:14px}.actions button{padding:11px 16px;border:0;background:#3d3a35;color:#fff;font-weight:700;cursor:pointer}.whatsapp{display:inline-block;padding:11px 16px;background:#665849;color:#fff;text-decoration:none;font-weight:700}.empty{background:#fff;padding:32px;border:1px solid #e6dfd3}.dashboard-tabs{display:flex;gap:8px;margin-bottom:18px;border-bottom:1px solid #e6dfd3}.dashboard-tabs a{padding:12px 16px;color:#736c63;text-decoration:none;font-weight:700;border-bottom:3px solid transparent}.dashboard-tabs a.active{color:#3d3a35;border-color:#665849}.media-builder{display:grid;gap:12px;padding:16px;background:#f8f5f0;border:1px solid #e6dfd3}.media-builder-head{display:flex;justify-content:space-between;align-items:center;gap:12px}.add-media,.remove-media{padding:9px 12px;border:1px solid #d8cec5;background:#fff;color:#665849;font-weight:700;cursor:pointer}.media-rows{display:grid;gap:8px}.media-row{display:grid;grid-template-columns:140px 1fr 1fr auto;gap:8px;align-items:center}.media-row input,.media-row select{min-width:0;padding:10px;border:1px solid #e6dfd3;background:#fff;font:inherit;color:#3d3a35}@media(max-width:650px){.media-row{grid-template-columns:1fr}.media-row .remove-media{width:max-content}}.project-manager,.project-list{background:#fff;border:1px solid #e6dfd3;padding:24px;margin-bottom:18px}.project-manager h2,.project-list h2{margin:0 0 8px;font-size:22px}.project-form{display:grid;gap:14px;margin-top:22px}.project-form button{padding:12px 18px;border:0;background:#3d3a35;color:#fff;font-weight:700;cursor:pointer}.success-message{color:#2f6b45;font-weight:700}.project-list-items{display:grid;gap:12px}.project-list-items article{padding:15px 0;border-top:1px solid #e6dfd3}.project-list-items article div{display:flex;justify-content:space-between;gap:18px}.project-list-items article span{color:#736c63;font-size:12px}.project-list-items article p{margin:8px 0 0;color:#736c63;line-height:1.5;font-size:13px}@media(max-width:650px){.top{align-items:flex-start;flex-direction:column}.grid{grid-template-columns:1fr}.actions{align-items:stretch;flex-direction:column}.actions button,.whatsapp{width:100%;box-sizing:border-box;text-align:center}}</style></head>
<body><main class="wrap"><header class="top"><div><p class="muted">AA Associate</p><h1>Customer enquiries</h1></div><a class="logout" href="?logout=1">Log out</a></header><nav class="dashboard-tabs" aria-label="Dashboard sections"><a class="<?= $activeTab === 'messages' ? 'active' : '' ?>" href="?tab=messages">User Messages</a><a class="<?= $activeTab === 'projects' ? 'active' : '' ?>" href="?tab=projects">Projects Submit</a></nav><?php if ($activeTab === 'projects'): ?><section class="project-manager"><div><p class="muted">Project archive</p><h2>Publish a new project</h2><p class="muted">Content saves to MySQL. No Git commit is created for content-only updates.</p></div><?php if ($projectMessage): ?><p class="success-message"><?= h($projectMessage) ?></p><?php endif; ?><?php if ($projectError): ?><p class="error"><?= h($projectError) ?></p><?php endif; ?><form method="post" class="project-form"><input type="hidden" name="action" value="create_project"><label class="field">Title<input name="title" required maxlength="180" placeholder="Example: Karachi office fit-out"></label><div class="grid"><label class="field">Year<input name="year" type="number" min="2000" max="2200" value="<?= date('Y') ?>" required></label><label class="field">Month<select name="month" required><?php for ($month = 1; $month <= 12; $month++): ?><option value="<?= $month ?>" <?= (int)date('n') === $month ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $month, 1)) ?></option><?php endfor; ?></select></label></div><label class="field">Description<textarea name="description" required maxlength="2000" placeholder="Short project summary for the public archive"></textarea></label><label class="field">Image URL <span class="muted">Optional</span><input name="image_url" type="url" placeholder="https://..."></label><label class="field">Google Drive embed/media link <span class="muted">Optional legacy link</span><input name="embed_url" type="url" placeholder="https://drive.google.com/..."></label><div class="media-builder"><div class="media-builder-head"><strong>Project media</strong><button type="button" class="add-media" onclick="addMediaRow()">+ Add image or Google Drive link</button></div><div id="media-rows"><div class="media-row"><select name="media_type[]"><option value="image">Image</option><option value="drive">Google Drive</option></select><input name="media_url[]" type="url" placeholder="https://..." required><input name="media_label[]" placeholder="Label (optional)"><button type="button" class="remove-media" onclick="removeMediaRow(this)" aria-label="Remove media">Remove</button></div></div></div><button type="submit">Publish project</button></form></section><section class="project-list"><h2>Published project entries</h2><?php if (!$projectRows): ?><p class="empty">No projects yet.</p><?php else: ?><div class="project-list-items"><?php foreach ($projectRows as $project): ?><article><div><strong><?= h((string)$project['title']) ?></strong><span><?= h((string)$project['month']) ?>/<?= h((string)$project['year']) ?> · <?= h((string)$project['status']) ?></span></div><p><?= h((string)$project['description']) ?></p></article><?php endforeach; ?></div><?php endif; ?></section><?php endif; ?><?php if ($activeTab === 'messages'): ?><section class="list">
<?php if (!$rows): ?><p class="empty">No enquiries yet.</p><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="item"><h2><?= h((string)$row['name']) ?></h2><div class="meta"><span><strong>Email:</strong> <?= h((string)$row['email']) ?></span><span><strong>Phone:</strong> <?= h((string)$row['phone_whatsapp']) ?></span><span><strong>Project:</strong> <?= h((string)$row['project_type']) ?></span><span><strong>City:</strong> <?= h((string)$row['city']) ?></span><span><strong>Submitted:</strong> <?= h((string)$row['created_at']) ?></span></div><div class="meta"><span><strong>Budget:</strong> <?= h((string)($row['budget_range'] ?: 'Not provided')) ?></span><span><strong>Preferred call:</strong> <?= h(trim((string)($row['preferred_call_date'] ?: 'Not provided') . ' ' . (string)($row['preferred_call_time'] ?: ''))) ?></span></div><p class="message"><?= h((string)$row['message']) ?></p><form method="post"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><div class="grid"><label class="field">Status<select name="status"><?php foreach (['New','Contacted','Closed'] as $status): ?><option <?= $row['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label><label class="field">Your notes<textarea name="admin_notes" placeholder="Internal note about this customer"><?= h((string)($row['admin_notes'] ?? '')) ?></textarea></label></div><div class="actions"><button type="submit">Save update</button><a class="whatsapp" href="<?= h(whatsapp_url((string)$row['phone_whatsapp'], (string)$row['name'])) ?>" target="_blank" rel="noopener">Open WhatsApp</a></div></form></article><?php endforeach; ?></section><?php endif; ?></main><script>function addMediaRow(){const row=document.querySelector('.media-row').cloneNode(true);row.querySelectorAll('input').forEach(input=>{input.value=''});document.getElementById('media-rows').appendChild(row)}function removeMediaRow(button){const rows=document.querySelectorAll('.media-row');if(rows.length>1)button.closest('.media-row').remove()}</script></body></html>
