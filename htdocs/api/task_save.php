<?php
/** Create or update a task (AJAX) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$pdo = db();
$uid = (int)$_SESSION['user_id'];
$id         = (int)($_POST['id'] ?? 0);
$projectId  = (int)($_POST['project_id'] ?? 0);
$title      = clean($_POST['title'] ?? '');
$desc       = clean($_POST['description'] ?? '');
$status     = in_array($_POST['status'] ?? '', ['todo','in_progress','review','completed']) ? $_POST['status'] : 'todo';
$priority   = in_array($_POST['priority'] ?? '', ['low','medium','high','urgent']) ? $_POST['priority'] : 'medium';
$assignee   = (int)($_POST['assigned_to'] ?? 0) ?: null;
$deadline   = ($_POST['deadline'] ?? '') ?: null;
$progress   = max(0, min(100, (int)($_POST['progress'] ?? 0)));
$estHours   = (float)($_POST['estimated_hours'] ?? 0);

if (mb_strlen($title) < 2) json_response(['success'=>false,'message'=>'Task title is required']);
if ($projectId < 1) json_response(['success'=>false,'message'=>'Project is required']);

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id=?");
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if (!$task) json_response(['success'=>false,'message'=>'Task not found']);

        // employees may only edit tasks assigned to them (status/progress)
        if (is_employee() && (int)$task['assigned_to'] !== $uid) {
            json_response(['success'=>false,'message'=>'Permission denied'], 403);
        }

        // if marked completed set progress 100
        if ($status === 'completed') $progress = 100;

        $stmt = $pdo->prepare("UPDATE tasks SET project_id=?,title=?,description=?,status=?,priority=?,assigned_to=?,deadline=?,progress=?,estimated_hours=? WHERE id=?");
        $stmt->execute([$projectId,$title,$desc,$status,$priority,$assignee,$deadline,$progress,$estHours,$id]);
        log_activity('updated','task',$id,"Updated task \"$title\"");

        if ($assignee && (int)$assignee !== $uid && (int)$assignee !== (int)$task['assigned_to']) {
            notify($assignee, 'Task assigned', "You were assigned \"$title\".", "task_view.php?id=$id", 'task');
        }
        $redirect = "task_view.php?id=$id";
    } else {
        if (!is_manager()) json_response(['success'=>false,'message'=>'Only managers/admins create tasks'], 403);
        if ($status === 'completed') $progress = 100;
        $stmt = $pdo->prepare("INSERT INTO tasks (project_id,title,description,status,priority,assigned_to,created_by,deadline,progress,estimated_hours) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$projectId,$title,$desc,$status,$priority,$assignee,$uid,$deadline,$progress,$estHours]);
        $id = (int)$pdo->lastInsertId();
        log_activity('created','task',$id,"Created task \"$title\"");
        if ($assignee && $assignee !== $uid) {
            notify($assignee, 'New task assigned', "You were assigned \"$title\".", "task_view.php?id=$id", 'task');
        }
        $redirect = "task_view.php?id=$id";
    }
    json_response(['success'=>true,'message'=>'Task saved','redirect'=>$redirect]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Could not save task'], 500);
}
