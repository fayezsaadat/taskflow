<?php
/** Upload an attachment to a task (AJAX) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$taskId = (int)($_POST['task_id'] ?? 0);
if ($taskId < 1) json_response(['success'=>false,'message'=>'Invalid task']);

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    json_response(['success'=>false,'message'=>'No file uploaded or upload error']);
}

$file = $_FILES['file'];
if ($file['size'] > MAX_UPLOAD_SIZE) {
    json_response(['success'=>false,'message'=>'File too large (max ' . file_size_label(MAX_UPLOAD_SIZE) . ')']);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXTENSIONS)) {
    json_response(['success'=>false,'message'=>'File type not allowed']);
}

// verify task exists
$stmt = db()->prepare("SELECT id, title FROM tasks WHERE id=?");
$stmt->execute([$taskId]);
$t = $stmt->fetch();
if (!$t) json_response(['success'=>false,'message'=>'Task not found']);

// secure filename + store under uploads/tasks/<taskId>_<random>.<ext>
$safe = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
$newName = $taskId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
$dest = UPLOAD_DIR . $newName;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_response(['success'=>false,'message'=>'Could not save file']);
}

try {
    $stmt = db()->prepare("INSERT INTO attachments (task_id,file_name,file_path,file_size,file_type,uploaded_by) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$taskId, $file['name'], UPLOAD_URL . $newName, $file['size'], $file['type'], $_SESSION['user_id']]);
    log_activity('uploaded','task',$taskId,"Uploaded file \"{$file['name']}\" to \"{$t['title']}\"");
    json_response(['success'=>true,'message'=>'File uploaded','reload'=>true]);
} catch (Throwable $e) {
    @unlink($dest);
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Could not save record'], 500);
}
