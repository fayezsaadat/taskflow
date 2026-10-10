<?php
/** Add a comment to a task (AJAX) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$taskId  = (int)($_POST['task_id'] ?? 0);
$comment = clean($_POST['comment'] ?? '');
if ($taskId < 1) json_response(['success'=>false,'message'=>'Invalid task']);
if (mb_strlen($comment) < 1) json_response(['success'=>false,'message'=>'Comment is empty']);

try {
    $stmt = db()->prepare("SELECT id, title, created_by FROM tasks WHERE id=?");
    $stmt->execute([$taskId]);
    $t = $stmt->fetch();
    if (!$t) json_response(['success'=>false,'message'=>'Task not found']);

    $stmt = db()->prepare("INSERT INTO comments (task_id,user_id,comment) VALUES (?,?,?)");
    $stmt->execute([$taskId, $_SESSION['user_id'], $comment]);
    $cid = (int)db()->lastInsertId();
    log_activity('commented','task',$taskId,"Commented on \"{$t['title']}\"");

    if ((int)$t['created_by'] !== (int)$_SESSION['user_id']) {
        notify((int)$t['created_by'], 'New comment', "New comment on \"{$t['title']}\".", "task_view.php?id=$taskId#comment-$cid", 'comment');
    }
    json_response(['success'=>true,'message'=>'Comment added','reload'=>true]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Could not add comment'], 500);
}
