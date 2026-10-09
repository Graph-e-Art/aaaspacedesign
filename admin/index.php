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
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login | Interior Hub</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f2eb;color:#3d3a35;font:15px Arial,sans-serif}.card{width:min(390px,calc(100% - 40px));background:#fff;padding:32px;border:1px solid #e6dfd3;box-shadow:0 18px 50px #3d3a3512}h1{margin:0 0 8px;font-size:28px}p{color:#736c63;line-height:1.6}.field{display:grid;gap:8px;margin:18px 0;font-weight:700;font-size:13px}input{padding:13px;border:1px solid #d8cec5;font:inherit}button{width:100%;padding:14px;border:0;background:#3d3a35;color:#fff;font-weight:700;cursor:pointer}.error{color:#a21d1d;font-size:13px}</style></head><body><main class="card"><p>AA Associate / Interior Hub</p><h1>Admin login</h1><p>View and manage customer enquiries.</p><?php if (!empty($loginError)): ?><p class="error"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?><form method="post"><input type="hidden" name="action" value="login"><label class="field">Username<input name="username" required autocomplete="username"></label><label class="field">Password<input name="password" type="password" required autocomplete="current-password"></label><button type="submit">Sign in</button></form></main></body></html>
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
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin configuration error | Interior Hub</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f2eb;color:#3d3a35;font:15px Arial,sans-serif}.card{width:min(560px,calc(100% - 40px));background:#fff;padding:32px;border:1px solid #e6dfd3;box-shadow:0 18px 50px #3d3a3512}h1{margin:0 0 12px;font-size:26px}p{color:#736c63;line-height:1.6}a{color:#665849;font-weight:700}</style></head><body><main class="card"><h1>Admin setup needs one check</h1><p><?= h($databaseError) ?></p><p>Confirm that <code>/home/aaspaced/cred/.env</code> contains <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, and <code>DB_PASS</code>, then confirm the MySQL user has access to the database.</p><p><a href="/admin/">Return to login</a></p></main></body></html>
<?php exit; endif;

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
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function whatsapp_url(string $phone, string $name): string {
    $digits = preg_replace('/[^0-9]/', '', $phone);
    $message = rawurlencode("Hello {$name}, thank you for contacting AA Associate / Interior Hub. We received your enquiry and will contact you shortly.");
    return 'https://wa.me/' . $digits . '?text=' . $message;
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Enquiries | Interior Hub</title>
<style>body{margin:0;background:#f5f2eb;color:#3d3a35;font:14px Arial,sans-serif}.wrap{width:min(1180px,calc(100% - 32px));margin:0 auto;padding:28px 0 60px}.top{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:25px}h1{margin:0;font-size:30px}.muted{color:#736c63}.logout{color:#665849;text-decoration:none;font-weight:700}.list{display:grid;gap:18px}.item{background:#fff;border:1px solid #e6dfd3;padding:22px;box-shadow:0 10px 28px #3d3a350b}.meta{display:flex;flex-wrap:wrap;gap:8px 18px;margin:10px 0;color:#736c63;font-size:13px}.meta strong{color:#3d3a35}.message{white-space:pre-wrap;line-height:1.6;margin:16px 0}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.field{display:grid;gap:7px;color:#665849;font-weight:700;font-size:12px}.field input,.field select,.field textarea{width:100%;box-sizing:border-box;padding:10px;border:1px solid #e6dfd3;background:#fff;font:inherit;color:#3d3a35}.field textarea{min-height:80px;resize:vertical}.actions{display:flex;align-items:end;gap:10px;margin-top:14px}.actions button{padding:11px 16px;border:0;background:#3d3a35;color:#fff;font-weight:700;cursor:pointer}.whatsapp{display:inline-block;padding:11px 16px;background:#665849;color:#fff;text-decoration:none;font-weight:700}.empty{background:#fff;padding:32px;border:1px solid #e6dfd3}@media(max-width:650px){.top{align-items:flex-start;flex-direction:column}.grid{grid-template-columns:1fr}.actions{align-items:stretch;flex-direction:column}.actions button,.whatsapp{width:100%;box-sizing:border-box;text-align:center}}</style></head>
<body><main class="wrap"><header class="top"><div><p class="muted">AA Associate / Interior Hub</p><h1>Customer enquiries</h1></div><a class="logout" href="?logout=1">Log out</a></header><section class="list">
<?php if (!$rows): ?><p class="empty">No enquiries yet.</p><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="item"><h2><?= h((string)$row['name']) ?></h2><div class="meta"><span><strong>Email:</strong> <?= h((string)$row['email']) ?></span><span><strong>Phone:</strong> <?= h((string)$row['phone_whatsapp']) ?></span><span><strong>Project:</strong> <?= h((string)$row['project_type']) ?></span><span><strong>City:</strong> <?= h((string)$row['city']) ?></span><span><strong>Submitted:</strong> <?= h((string)$row['created_at']) ?></span></div><div class="meta"><span><strong>Budget:</strong> <?= h((string)($row['budget_range'] ?: 'Not provided')) ?></span><span><strong>Preferred call:</strong> <?= h(trim((string)($row['preferred_call_date'] ?: 'Not provided') . ' ' . (string)($row['preferred_call_time'] ?: ''))) ?></span></div><p class="message"><?= h((string)$row['message']) ?></p><form method="post"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><div class="grid"><label class="field">Status<select name="status"><?php foreach (['New','Contacted','Closed'] as $status): ?><option <?= $row['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label><label class="field">Your notes<textarea name="admin_notes" placeholder="Internal note about this customer"><?= h((string)($row['admin_notes'] ?? '')) ?></textarea></label></div><div class="actions"><button type="submit">Save update</button><a class="whatsapp" href="<?= h(whatsapp_url((string)$row['phone_whatsapp'], (string)$row['name'])) ?>" target="_blank" rel="noopener">Open WhatsApp</a></div></form></article><?php endforeach; ?></section></main></body></html>
