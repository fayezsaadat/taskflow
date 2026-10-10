<?php
/** Delete a project (AJAX) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');

if (!is_manager()) json_response(['success'=>false,'message'=>'Permission denied'], 403);
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$id = (int)($_POST['id'] ?? 0);
if ($id < 1) json_response(['success'=>false,'message'=>'Invalid project']);

try {
    $stmt = db()->prepare("SELECT name FROM projects WHERE id=?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) json_response(['success'=>false,'message'=>'Project not found']);

    db()->prepare("DELETE FROM projects WHERE id=?")->execute([$id]);
    log_activity('deleted','project',$id,"Deleted project \"{$p['name']}\"");
    json_response(['success'=>true,'message'=>'Project deleted','redirect'=>'projects.php']);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Could not delete'], 500);
}
