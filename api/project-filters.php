<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

try {
    $pdo = database();
    $clients = $pdo->query(
        "SELECT DISTINCT c.id, c.name
         FROM clients c
         JOIN project_clients pc ON pc.client_id = c.id
         JOIN projects p ON p.id = pc.project_id AND p.status = 'Published'
         ORDER BY c.name ASC"
    )->fetchAll();
    $workTags = $pdo->query(
        "SELECT DISTINCT wt.id, wt.name
         FROM work_tags wt
         JOIN project_work_tags pwt ON pwt.work_tag_id = wt.id
         JOIN projects p ON p.id = pwt.project_id AND p.status = 'Published'
         ORDER BY wt.name ASC"
    )->fetchAll();

    foreach ($clients as &$client) {
        $client['id'] = (int)$client['id'];
    }
    unset($client);
    foreach ($workTags as &$workTag) {
        $workTag['id'] = (int)$workTag['id'];
    }
    unset($workTag);

    echo json_encode(['ok' => true, 'clients' => $clients, 'work_tags' => $workTags], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('Project filter list failed: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Project filters are temporarily unavailable.']);
}
