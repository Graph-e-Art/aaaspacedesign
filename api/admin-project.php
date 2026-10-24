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
$clientId = filter_var($input['client_id'] ?? null, FILTER_VALIDATE_INT);
$featuredOrderRaw = trim((string)($input['featured_order'] ?? ''));
$featuredOrder = $featuredOrderRaw === '' ? null : filter_var($featuredOrderRaw, FILTER_VALIDATE_INT);
if ($title === '' || !$year || $year < 2000 || $year > 2200 || !$month || $month < 1 || $month > 12 || $description === '' || !$clientId) { http_response_code(422); echo json_encode(['ok' => false, 'message' => 'Client, title, valid year, month, and description are required.']); exit; }
if ($featuredOrderRaw !== '' && ($featuredOrder === false || $featuredOrder < 1 || $featuredOrder > 5)) { http_response_code(422); echo json_encode(['ok' => false, 'message' => 'Choose Not featured or Portfolio 1–5.']); exit; }
if ($imageUrl !== '' && !filter_var($imageUrl, FILTER_VALIDATE_URL)) { http_response_code(422); echo json_encode(['ok' => false, 'message' => 'Image URL must be a valid URL.']); exit; }
if ($embedUrl !== '' && !filter_var($embedUrl, FILTER_VALIDATE_URL)) { http_response_code(422); echo json_encode(['ok' => false, 'message' => 'Embed URL must be a valid URL.']); exit; }
try {
    $pdo = database();
    $clientStatement = $pdo->prepare('SELECT id FROM clients WHERE id = :client_id');
    $clientStatement->execute([':client_id' => $clientId]);
    if (!$clientStatement->fetch()) { http_response_code(422); echo json_encode(['ok' => false, 'message' => 'Choose a valid client tag.']); exit; }
    if ($featuredOrder !== null) {
        $slotStatement = $pdo->prepare('SELECT title FROM projects WHERE featured_order = :featured_order LIMIT 1');
        $slotStatement->execute([':featured_order' => $featuredOrder]);
        $occupied = $slotStatement->fetch();
        if ($occupied) { http_response_code(409); echo json_encode(['ok' => false, 'message' => 'Portfolio slot is already assigned to ' . (string)$occupied['title'] . '.']); exit; }
    }
    $statement = $pdo->prepare('INSERT INTO projects (client_id, title, year, month, description, image_url, embed_url, featured_order) VALUES (:client_id, :title, :year, :month, :description, :image_url, :embed_url, :featured_order)');
    $statement->execute([':client_id' => $clientId, ':title' => $title, ':year' => $year, ':month' => $month, ':description' => $description, ':image_url' => $imageUrl ?: null, ':embed_url' => $embedUrl ?: null, ':featured_order' => $featuredOrder]);
    echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $error) { error_log('Project insert failed: ' . $error->getMessage()); http_response_code(503); echo json_encode(['ok' => false, 'message' => 'Project could not be saved.']); }
