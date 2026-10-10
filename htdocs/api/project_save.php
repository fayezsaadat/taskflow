<?php
/** Create or update a project (AJAX) */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');

if (!is_manager()) json_response(['success'=>false,'message'=>'Permission denied'], 403);
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$pdo = db();
$action = $_POST['action'] ?? 'save';

// Theme quick toggle
if ($action === 'theme') {
    $theme = ($_POST['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
    $stmt = $pdo->prepare("UPDATE users SET theme=? WHERE id=?");
    $stmt->execute([$theme, $_SESSION['user_id']]);
    $_SESSION['theme'] = $theme;
    json_response(['success'=>true]);
}

$id         = (int)($_POST['id'] ?? 0);
$name       = clean($_POST['name'] ?? '');
$desc       = clean($_POST['description'] ?? '');
$status     = in_array($_POST['status'] ?? '', ['planning','active','on_hold','completed','cancelled']) ? $_POST['status'] : 'planning';
$priority   = in_array($_POST['priority'] ?? '', ['low','medium','high','urgent']) ? $_POST['priority'] : 'medium';
$start      = ($_POST['start_date'] ?? '') ?: null;
$deadline   = ($_POST['deadline'] ?? '') ?: null;
$budget     = (float)($_POST['budget'] ?? 0);
$spent      = (float)($_POST['spent'] ?? 0);
$members    = $_POST['members'] ?? [];

if (mb_strlen($name) < 2) json_response(['success'=>false,'message'=>'Project name is required']);

try {
    if ($id > 0) {
        // ownership/scope check
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id=?");
        $stmt->execute([$id]);
        $proj = $stmt->fetch();
        if (!$proj) json_response(['success'=>false,'message'=>'Project not found']);

        $stmt = $pdo->prepare("UPDATE projects SET name=?,description=?,status=?,priority=?,start_date=?,deadline=?,budget=?,spent=? WHERE id=?");
        $stmt->execute([$name,$desc,$status,$priority,$start,$deadline,$budget,$spent,$id]);
        log_activity('updated','project',$id,"Updated project \"$name\"");
    } else {
        $stmt = $pdo->prepare("INSERT INTO projects (name,description,status,priority,start_date,deadline,budget,spent,created_by) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name,$desc,$status,$priority,$start,$deadline,$budget,$spent,$_SESSION['user_id']]);
        $id = (int)$pdo->lastInsertId();
        log_activity('created','project',$id,"Created project \"$name\"");
    }

    // sync members
    $pdo->prepare("DELETE FROM project_members WHERE project_id=?")->execute([$id]);
    if (is_array($members)) {
        $ins = $pdo->prepare("INSERT IGNORE INTO project_members (project_id,user_id,role) VALUES (?,?,?)");
        foreach ($members as $mid) {
            $mid = (int)$mid;
            if ($mid > 0) $ins->execute([$id, $mid, 'member']);
        }
        // notify members
        foreach ($members as $mid) {
            $mid = (int)$mid;
            if ($mid > 0 && $mid != $_SESSION['user_id']) {
                notify($mid, 'Added to project', "You were added to \"$name\".", "project_view.php?id=$id", 'project');
            }
        }
    }

    json_response(['success'=>true,'message'=>'Project saved','redirect'=>'project_view.php?id='.$id]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Could not save project'], 500);
}
