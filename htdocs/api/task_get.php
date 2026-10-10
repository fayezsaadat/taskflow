<?php
/** Fetch a single task as JSON (for edit modal) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) json_response(['success'=>false,'message'=>'Invalid task']);
try {
    $stmt = db()->prepare("SELECT * FROM tasks WHERE id=?");
    $stmt->execute([$id]);
    $t = $stmt->fetch();
    if (!$t) json_response(['success'=>false,'message'=>'Not found']);
    json_response(['success'=>true,'task'=>$t]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Error'], 500);
}
