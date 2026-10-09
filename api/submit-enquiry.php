<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $payload)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(400, ['ok' => false, 'message' => 'Invalid request.']);
}

// Honeypot field. Real users never see or fill this field.
if (!empty($input['website'])) {
    respond(200, ['ok' => true, 'message' => 'Enquiry received.']);
}

$required = ['name', 'email', 'phone_whatsapp', 'project_type', 'city', 'message'];
foreach ($required as $field) {
    if (trim((string)($input[$field] ?? '')) === '') {
        respond(422, ['ok' => false, 'message' => 'Please complete all required fields.']);
    }
}

$name = trim((string)$input['name']);
$email = trim((string)$input['email']);
$phone = trim((string)$input['phone_whatsapp']);
$type = trim((string)$input['project_type']);
$city = trim((string)$input['city']);
$budget = trim((string)($input['budget_range'] ?? ''));
$date = trim((string)($input['preferred_call_date'] ?? ''));
$time = trim((string)($input['preferred_call_time'] ?? ''));
$message = trim((string)$input['message']);

$allowedTypes = ['Residential', 'Commercial', 'Renovation'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($type, $allowedTypes, true)) {
    respond(422, ['ok' => false, 'message' => 'Please check email and project type.']);
}

if (strlen($name) > 120 || strlen($email) > 190 || strlen($phone) > 40 || strlen($city) > 120 || strlen($budget) > 120 || strlen($message) > 10000) {
    respond(422, ['ok' => false, 'message' => 'One or more fields are too long.']);
}

try {
    $pdo = database();
    $statement = $pdo->prepare(
        'INSERT INTO contact_enquiries
        (name, email, phone_whatsapp, project_type, city, budget_range, message, preferred_call_date, preferred_call_time)
        VALUES (:name, :email, :phone, :project_type, :city, :budget, :message, :call_date, :call_time)'
    );
    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':project_type' => $type,
        ':city' => $city,
        ':budget' => $budget !== '' ? $budget : null,
        ':message' => $message,
        ':call_date' => $date !== '' ? $date : null,
        ':call_time' => $time !== '' ? $time : null,
    ]);

    $env = app_env();
    $recipient = env_value($env, 'MAIL_TO', 'info@aaspacedesign.com');
    $subject = 'New Interior Hub enquiry from ' . $name;
    $body = "Name: {$name}\nEmail: {$email}\nPhone / WhatsApp: {$phone}\nProject type: {$type}\nCity: {$city}\nBudget: " . ($budget ?: 'Not provided') . "\nPreferred call: " . (($date ?: 'Not provided') . ' ' . ($time ?: '')) . "\n\nMessage:\n{$message}\n";
    $headers = "From: {$recipient}\r\nReply-To: {$email}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    @mail($recipient, $subject, $body, $headers);

    respond(200, ['ok' => true, 'message' => 'Thank you. Your enquiry has been received.']);
} catch (Throwable $error) {
    error_log('Enquiry submission failed: ' . $error->getMessage());
    respond(500, ['ok' => false, 'message' => 'We could not submit your enquiry. Please try again or contact us directly.']);
}
