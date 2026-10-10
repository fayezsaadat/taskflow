<?php
/** Mark notifications read (AJAX) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$id = (int)($_POST['id'] ?? 0);
$uid = (int)$_SESSION['user_id'];
try {
    if ($id > 0) {
        db()->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$id, $uid]);
    } else {
        db()->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
    }
    json_response(['success'=>true,'reload'=> ($id===0)]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Error'], 500);
}
