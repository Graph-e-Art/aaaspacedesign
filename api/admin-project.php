<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['admin_authenticated'])) { http_response_code(401); echo json_encode(['ok' => false, 'message' => 'Authentication required.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false, 'message' => 'POST required.']); exit; }
$input = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;
$title = trim((string)($input['title'] ?? ''));
$year = filter_var($input['year'] ?? null, FILTER_VALIDATE_INT);
$month = filter_var($input['month'] ?? null, FILTER_VALIDATE_INT);
$description = trim((string)($input['description'] ?? ''));
$imageUrl = trim((string)($input['image_url'] ?? ''));
$embedUrl = trim((string)($input['embed_url'] ?? ''));
if ($title === '' || !$year || $year < 2000 || $year > 2200 || !$month || $month < 1 || $month > 12 || $description === '') { http_response_code(422); echo json_encode(['ok' => false, 'message' => 'Title, valid year, month, and description are required.']); exit; }
if ($imageUrl !== '' && !filter_var($imageUrl, FILTER_VALIDATE_URL)) { http_response_code(422); echo json_encode(['ok' => false, 'message' => 'Image URL must be a valid URL.']); exit; }
if ($embedUrl !== '' && !filter_var($embedUrl, FILTER_VALIDATE_URL)) { http_response_code(422); echo json_encode(['ok' => false, 'message' => 'Embed URL must be a valid URL.']); exit; }
try {
    $statement = database()->prepare('INSERT INTO projects (title, year, month, description, image_url, embed_url) VALUES (:title, :year, :month, :description, :image_url, :embed_url)');
    $statement->execute([':title' => $title, ':year' => $year, ':month' => $month, ':description' => $description, ':image_url' => $imageUrl ?: null, ':embed_url' => $embedUrl ?: null]);
    echo json_encode(['ok' => true, 'id' => (int)database()->lastInsertId()]);
} catch (Throwable $error) { error_log('Project insert failed: ' . $error->getMessage()); http_response_code(503); echo json_encode(['ok' => false, 'message' => 'Project could not be saved.']); }
