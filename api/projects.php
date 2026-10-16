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
    if ($projects) {
        $ids = array_map(static fn(array $project): int => (int)$project['id'], $projects);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $mediaStatement = $pdo->prepare("SELECT project_id, media_type, url, label FROM project_media WHERE project_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
        $mediaStatement->execute($ids);
        $mediaByProject = [];
        foreach ($mediaStatement->fetchAll() as $media) {
            $mediaByProject[(int)$media['project_id']][] = ['type' => $media['media_type'], 'url' => $media['url'], 'label' => $media['label']];
        }
        foreach ($projects as &$project) {
            $project['id'] = (int)$project['id'];
            $project['year'] = (int)$project['year'];
            $project['month'] = (int)$project['month'];
            $project['media'] = $mediaByProject[$project['id']] ?? [];
            if (!$project['media']) {
                if (!empty($project['image_url'])) { $project['media'][] = ['type' => 'image', 'url' => $project['image_url'], 'label' => null]; }
                if (!empty($project['embed_url'])) { $project['media'][] = ['type' => 'drive', 'url' => $project['embed_url'], 'label' => null]; }
            }
        }
        unset($project);
    }
    $years = $pdo->query("SELECT DISTINCT year FROM projects WHERE status = 'Published' ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
    $months = $pdo->query("SELECT DISTINCT month FROM projects WHERE status = 'Published' ORDER BY month ASC")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['ok' => true, 'projects' => $projects, 'years' => array_map('intval', $years), 'months' => array_map('intval', $months)], JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('Project feed failed: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Project archive is temporarily unavailable.']);
}
