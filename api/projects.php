<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

try {
    $pdo = database();
    $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
    $month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
    $clientId = filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT);
    $workTagId = filter_input(INPUT_GET, 'work_tag_id', FILTER_VALIDATE_INT);
    $featuredOnly = (($_GET['featured'] ?? '') === '1');
    $limit = min(max((int)($_GET['limit'] ?? 100), 1), 100);
    $offset = min(max((int)($_GET['offset'] ?? 0), 0), 1000000);
    $where = ["p.status = 'Published'"];
    $params = [];
    if ($year) { $where[] = 'p.year = :year'; $params[':year'] = $year; }
    if ($month) { $where[] = 'p.month = :month'; $params[':month'] = $month; }
    if ($clientId) { $where[] = 'EXISTS (SELECT 1 FROM project_clients pc WHERE pc.project_id = p.id AND pc.client_id = :client_id)'; $params[':client_id'] = $clientId; }
    if ($workTagId) { $where[] = 'EXISTS (SELECT 1 FROM project_work_tags pwt_filter WHERE pwt_filter.project_id = p.id AND pwt_filter.work_tag_id = :work_tag_id)'; $params[':work_tag_id'] = $workTagId; }
    if ($featuredOnly) { $where[] = 'p.featured_order BETWEEN 1 AND 5'; }
    $orderBy = $featuredOnly ? 'p.featured_order ASC' : 'p.year DESC, p.month DESC, p.id DESC';
    $countStatement = $pdo->prepare('SELECT COUNT(*) FROM projects p WHERE ' . implode(' AND ', $where));
    $countStatement->execute($params);
    $total = (int)$countStatement->fetchColumn();
    $sql = 'SELECT p.id, p.client_id, c.name AS client_name, p.title, p.year, p.month, p.description, p.image_url, p.embed_url, p.featured_order, p.created_at FROM projects p LEFT JOIN clients c ON c.id = p.client_id WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $orderBy . ' LIMIT ' . $limit . ' OFFSET ' . $offset;
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
        $clientTagStatement = $pdo->prepare("SELECT pc.project_id, c.id, c.name FROM project_clients pc JOIN clients c ON c.id = pc.client_id WHERE pc.project_id IN ($placeholders) ORDER BY c.name ASC");
        $clientTagStatement->execute($ids);
        $clientsByProject = [];
        foreach ($clientTagStatement->fetchAll() as $clientTag) {
            $clientsByProject[(int)$clientTag['project_id']][] = ['id' => (int)$clientTag['id'], 'name' => $clientTag['name']];
        }
        $workTagStatement = $pdo->prepare("SELECT pwt.project_id, wt.id, wt.category_key, wt.category_label, wt.name, wt.slug FROM project_work_tags pwt JOIN work_tags wt ON wt.id = pwt.work_tag_id WHERE pwt.project_id IN ($placeholders) ORDER BY wt.sort_order ASC, wt.id ASC");
        $workTagStatement->execute($ids);
        $workTagsByProject = [];
        foreach ($workTagStatement->fetchAll() as $workTag) {
            $workTagsByProject[(int)$workTag['project_id']][] = ['id' => (int)$workTag['id'], 'category' => $workTag['category_label'], 'category_key' => $workTag['category_key'], 'name' => $workTag['name'], 'slug' => $workTag['slug']];
        }
        foreach ($projects as &$project) {
            $project['id'] = (int)$project['id'];
            $project['client_id'] = $project['client_id'] === null ? null : (int)$project['client_id'];
            $project['year'] = (int)$project['year'];
            $project['month'] = (int)$project['month'];
            $project['featured_order'] = $project['featured_order'] === null ? null : (int)$project['featured_order'];
            $project['clients'] = $clientsByProject[$project['id']] ?? [];
            $project['client_names'] = array_column($project['clients'], 'name');
            if ($project['client_names']) { $project['client_name'] = implode(', ', $project['client_names']); }
            $project['work_tags'] = $workTagsByProject[$project['id']] ?? [];
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
    echo json_encode(['ok' => true, 'projects' => $projects, 'total' => $total, 'years' => array_map('intval', $years), 'months' => array_map('intval', $months)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('Project feed failed: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Project archive is temporarily unavailable.']);
}
