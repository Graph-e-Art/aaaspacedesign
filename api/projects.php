<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

try {
    $pdo = database();
    $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
    $month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
    $limit = min(max((int)($_GET['limit'] ?? 100), 1), 100);
    $where = ["status = 'Published'"];
    $params = [];
    if ($year) { $where[] = 'year = :year'; $params[':year'] = $year; }
    if ($month) { $where[] = 'month = :month'; $params[':month'] = $month; }
    $sql = 'SELECT id, title, year, month, description, image_url, embed_url, created_at FROM projects WHERE ' . implode(' AND ', $where) . ' ORDER BY year DESC, month DESC, id DESC LIMIT ' . $limit;
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $projects = $statement->fetchAll();
    $years = $pdo->query("SELECT DISTINCT year FROM projects WHERE status = 'Published' ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
    $months = $pdo->query("SELECT DISTINCT month FROM projects WHERE status = 'Published' ORDER BY month ASC")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['ok' => true, 'projects' => $projects, 'years' => array_map('intval', $years), 'months' => array_map('intval', $months)], JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('Project feed failed: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Project archive is temporarily unavailable.']);
}
