<?php
/** User profile / settings save (AJAX + theme toggle).
 *  This endpoint is hit from multiple pages; action determines behavior. */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');
if (!csrf_verify()) json_response(['success'=>false,'message'=>'Invalid token'], 419);

$pdo = db();
$uid = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Admin: create a new team member
if ($action === 'create') {
    if (!is_admin()) json_response(['success'=>false,'message'=>'Permission denied'], 403);
    $name=clean($_POST['name'] ?? ''); $email=strtolower(clean($_POST['email'] ?? ''));
    $role=$_POST['new_role'] ?? 'employee'; $password=$_POST['password'] ?? '';
    $phone=clean($_POST['phone'] ?? ''); $dept=clean($_POST['department'] ?? ''); $job=clean($_POST['job_title'] ?? '');
    if (mb_strlen($name)<2) json_response(['success'=>false,'message'=>'Name required']);
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) json_response(['success'=>false,'message'=>'Invalid email']);
    if (strlen($password)<6) json_response(['success'=>false,'message'=>'Password too short']);
    if (!in_array($role,['admin','manager','employee'])) $role='employee';
    $chk=$pdo->prepare("SELECT id FROM users WHERE email=?"); $chk->execute([$email]);
    if ($chk->fetch()) json_response(['success'=>false,'message'=>'Email already in use']);
    try {
        $hash=password_hash($password,PASSWORD_BCRYPT);
        $stmt=$pdo->prepare("INSERT INTO users (name,email,password,role,phone,department,job_title,status) VALUES (?,?,?,?,?,?,?,'active')");
        $stmt->execute([$name,$email,$hash,$role,$phone?:null,$dept?:null,$job?:null]);
        $nid=(int)$pdo->lastInsertId();
        log_activity('created','user',$nid,"Created member \"$name\"");
        json_response(['success'=>true,'message'=>'Member added','redirect'=>'team.php']);
    } catch (Throwable $e) { json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Error'],500); }
}

// Theme toggle (from navbar)
if ($action === 'theme' || ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['theme']))) {
    $theme = ($_POST['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
    $stmt = $pdo->prepare("UPDATE users SET theme=? WHERE id=?");
    $stmt->execute([$theme, $uid]);
    $_SESSION['theme'] = $theme;
    json_response(['success'=>true]);
}

// Full profile update
$name       = clean($_POST['name'] ?? '');
$email      = trim(strtolower(clean($_POST['email'] ?? '')));
$phone      = clean($_POST['phone'] ?? '');
$department = clean($_POST['department'] ?? '');
$jobTitle   = clean($_POST['job_title'] ?? '');
$bio        = clean($_POST['bio'] ?? '');
$password   = $_POST['password'] ?? '';

if (mb_strlen($name) < 2) json_response(['success'=>false,'message'=>'Name is required']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['success'=>false,'message'=>'Invalid email']);

// email uniqueness
$chk = $pdo->prepare("SELECT id FROM users WHERE email=? AND id<>?");
$chk->execute([$email, $uid]);
if ($chk->fetch()) json_response(['success'=>false,'message'=>'Email already in use']);

// avatar upload
$avatarPath = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif'])) json_response(['success'=>false,'message'=>'Avatar must be JPG/PNG/GIF']);
    if ($_FILES['avatar']['size'] > 2*1024*1024) json_response(['success'=>false,'message'=>'Avatar too large (max 2MB)']);
    $newName = 'avatar_' . $uid . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dir = __DIR__ . '/../uploads/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $newName)) {
        $avatarPath = $newName;
    }
}

try {
    if ($password) {
        if (strlen($password) < 6) json_response(['success'=>false,'message'=>'Password must be 6+ chars']);
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET name=?,email=?,phone=?,department=?,job_title=?,bio=?,password=?";
        $params = [$name,$email,$phone,$department,$jobTitle,$bio,$hash];
        if ($avatarPath) { $sql .= ",avatar=?"; $params[] = $avatarPath; }
        $sql .= " WHERE id=?"; $params[] = $uid;
        $pdo->prepare($sql)->execute($params);
    } else {
        $sql = "UPDATE users SET name=?,email=?,phone=?,department=?,job_title=?,bio=?";
        $params = [$name,$email,$phone,$department,$jobTitle,$bio];
        if ($avatarPath) { $sql .= ",avatar=?"; $params[] = $avatarPath; }
        $sql .= " WHERE id=?"; $params[] = $uid;
        $pdo->prepare($sql)->execute($params);
    }

    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    if ($avatarPath) $_SESSION['user_avatar'] = $avatarPath;
    log_activity('updated_profile','user',$uid,"Updated profile");
    json_response(['success'=>true,'message'=>'Profile updated','reload'=>true]);
} catch (Throwable $e) {
    json_response(['success'=>false,'message'=>DEBUG?$e->getMessage():'Could not save'], 500);
}
