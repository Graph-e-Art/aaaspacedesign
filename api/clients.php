<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');
try {
    $statement = database()->query(
        "SELECT c.id, c.name, c.slug, COUNT(p.id) AS project_count
         FROM clients c
         LEFT JOIN projects p ON p.client_id = c.id AND p.status = 'Published'
         GROUP BY c.id, c.name, c.slug
         ORDER BY c.id ASC"
    );
    $clients = $statement->fetchAll();
    foreach ($clients as &$client) {
        $client['id'] = (int)$client['id'];
        $client['project_count'] = (int)$client['project_count'];
    }
    unset($client);
    echo json_encode(['ok' => true, 'clients' => $clients], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('Client feed failed: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Client list is temporarily unavailable.']);
}
