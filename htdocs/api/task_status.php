<?php
/** Quick task status update (AJAX) — used by kanban / dropdowns */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$id     = (int)($_POST['task_id'] ?? $_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
if (!in_array($status, ['todo','in_progress','review','completed'])) {
    json_response(['success'=>false,'message'=>'Invalid status']);
}
if ($id < 1) json_response(['success'=>false,'message'=>'Invalid task']);

try {
    $stmt = db()->prepare("SELECT * FROM tasks WHERE id=?");
    $stmt->execute([$id]);
    $t = $stmt->fetch();
    if (!$t) json_response(['success'=>false,'message'=>'Task not found']);

    // employees can only change their own assigned tasks
    if (is_employee() && (int)$t['assigned_to'] !== (int)$_SESSION['user_id']) {
        json_response(['success'=>false,'message'=>'Permission denied'], 403);
    }

    $progress = $status === 'completed' ? 100 : (int)$t['progress'];
    db()->prepare("UPDATE tasks SET status=?, progress=? WHERE id=?")->execute([$status, $progress, $id]);
    log_activity('updated','task',$id,"Moved task \"{$t['title']}\" to $status");

    // notify task creator of completion
    if ($status === 'completed' && (int)$t['created_by'] !== (int)$_SESSION['user_id']) {
        notify((int)$t['created_by'], 'Task completed', "\"{$t['title']}\" was marked completed.", "task_view.php?id=$id", 'task');
    }
    json_response(['success'=>true,'message'=>'Status updated','reload'=>true]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Could not update'], 500);
}
