<?php
/** Delete / deactivate a user (admin only) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);
if (!is_admin()) json_response(['success'=>false,'message'=>'Permission denied'], 403);

$id = (int)($_POST['id'] ?? 0);
if ($id < 1) json_response(['success'=>false,'message'=>'Invalid user']);
if ($id === (int)$_SESSION['user_id']) json_response(['success'=>false,'message'=>'You cannot remove yourself']);

try {
    $stmt = db()->prepare("SELECT name FROM users WHERE id=?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) json_response(['success'=>false,'message'=>'User not found']);
    // unassign their tasks then remove
    db()->prepare("UPDATE tasks SET assigned_to=NULL WHERE assigned_to=?")->execute([$id]);
    db()->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    log_activity('deleted','user',$id,"Removed member \"{$u['name']}\"");
    json_response(['success'=>true,'message'=>'Member removed','reload'=>true]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Error'], 500);
}
