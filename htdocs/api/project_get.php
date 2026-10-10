<?php
/** Fetch a single project + members as JSON (for edit modal) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) json_response(['success'=>false,'message'=>'Invalid project']);

try {
    $stmt = db()->prepare("SELECT * FROM projects WHERE id=?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) json_response(['success'=>false,'message'=>'Not found']);

    $ms = db()->prepare("SELECT user_id FROM project_members WHERE project_id=?");
    $ms->execute([$id]);
    $p['members'] = array_map('intval', array_column($ms->fetchAll(), 'user_id'));
    json_response(['success'=>true,'project'=>$p]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Error'], 500);
}
