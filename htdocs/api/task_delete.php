<?php
/** Delete a task (AJAX) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$id = (int)($_POST['id'] ?? 0);
if ($id < 1) json_response(['success'=>false,'message'=>'Invalid task']);

try {
    $stmt = db()->prepare("SELECT title, assigned_to, project_id FROM tasks WHERE id=?");
    $stmt->execute([$id]);
    $t = $stmt->fetch();
    if (!$t) json_response(['success'=>false,'message'=>'Task not found']);

    if (is_employee() && (int)$t['assigned_to'] !== (int)$_SESSION['user_id']) {
        json_response(['success'=>false,'message'=>'Permission denied'], 403);
    }

    db()->prepare("DELETE FROM tasks WHERE id=?")->execute([$id]);
    log_activity('deleted','task',$id,"Deleted task \"{$t['title']}\"");
    json_response(['success'=>true,'message'=>'Task deleted','redirect'=>'tasks.php']);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Could not delete'], 500);
}
