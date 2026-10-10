<?php
/** Delete an attachment (AJAX) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$id = (int)($_POST['id'] ?? 0);
if ($id < 1) json_response(['success'=>false,'message'=>'Invalid attachment']);

try {
    $stmt = db()->prepare("SELECT * FROM attachments WHERE id=?");
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if (!$a) json_response(['success'=>false,'message'=>'Attachment not found']);

    if (!is_manager() && (int)$a['uploaded_by'] !== (int)$_SESSION['user_id']) {
        json_response(['success'=>false,'message'=>'Permission denied'], 403);
    }
    // remove file
    $abs = __DIR__ . '/../' . $a['file_path'];
    if (is_file($abs)) @unlink($abs);

    db()->prepare("DELETE FROM attachments WHERE id=?")->execute([$id]);
    json_response(['success'=>true,'message'=>'Attachment removed','reload'=>true]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Could not delete'], 500);
}
