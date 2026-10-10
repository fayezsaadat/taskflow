<?php
/** Delete a comment (AJAX) — owner or manager only */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$id = (int)($_POST['id'] ?? 0);
if ($id < 1) json_response(['success'=>false,'message'=>'Invalid comment']);

try {
    $stmt = db()->prepare("SELECT user_id FROM comments WHERE id=?");
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) json_response(['success'=>false,'message'=>'Comment not found']);

    if (!is_manager() && (int)$c['user_id'] !== (int)$_SESSION['user_id']) {
        json_response(['success'=>false,'message'=>'Permission denied'], 403);
    }
    db()->prepare("DELETE FROM comments WHERE id=?")->execute([$id]);
    json_response(['success'=>true,'message'=>'Comment deleted','reload'=>true]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Could not delete'], 500);
}
