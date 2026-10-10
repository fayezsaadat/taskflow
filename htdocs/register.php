<?php
/** TaskFlow — Register */
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) redirect('dashboard.php');

$errors = [];
$name = $email = $department = $phone = $role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid session token.';
    } else {
        $name       = clean($_POST['name'] ?? '');
        $email      = trim(strtolower(clean($_POST['email'] ?? '')));
        $password   = $_POST['password'] ?? '';
        $confirm    = $_POST['confirm_password'] ?? '';
        $department = clean($_POST['department'] ?? '');
        $phone      = clean($_POST['phone'] ?? '');
        $role       = clean($_POST['role'] ?? 'employee');

        if (mb_strlen($name) < 2) $errors[] = 'Please enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        if (!in_array($role, ['employee', 'manager', 'admin'], true)) $role = 'employee';

        if (empty($errors)) {
            $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) $errors[] = 'An account with that email already exists.';
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = db()->prepare(
                "INSERT INTO users (name, email, password, role, department, phone, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'active')"
            );
            $stmt->execute([$name, $email, $hash, $role, $department ?: null, $phone ?: null]);
            $newId = (int) db()->lastInsertId();
            log_activity('registered', 'user', $newId, $name . ' created an account');

            $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$newId]);
            login_user($stmt->fetch());

            set_flash('success', 'Account created. Welcome to TaskFlow!');
            redirect('dashboard.php');
        }
    }
}
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#6366f1">
<title>Create account — TaskFlow</title>
<link rel="icon" type="image/png" href="assets/img/logo-48.png">
<link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="auth-card">
    <div class="auth-hero">
      <div>
        <div class="auth-hero__logo"><span class="logo-tile"><img src="assets/img/logo-256.png" alt="TaskFlow"></span><span>TaskFlow</span></div>
        <h2>Start managing your projects smarter today.</h2>
        <p>Create your free account and get your team up and running in minutes with powerful tools.</p>
      </div>
      <ul class="auth-hero__feat">
        <li><span class="ic-wrap"><svg viewBox="0 0 24 24" class="ic"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></span> Free to get started</li>
        <li><span class="ic-wrap"><svg viewBox="0 0 24 24" class="ic"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></span> Secure password hashing</li>
        <li><span class="ic-wrap"><svg viewBox="0 0 24 24" class="ic"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></span> Role-based access control</li>
        <li><span class="ic-wrap"><svg viewBox="0 0 24 24" class="ic"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></span> Real-time insights &amp; reports</li>
      </ul>
    </div>

    <div class="auth-form">
      <div class="form-head">
        <h1>Create account ✨</h1>
        <p class="sub">Join your team and start building together.</p>
      </div>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert--error"><svg viewBox="0 0 24 24" class="ic"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm5 13.6L15.6 17 12 13.4 8.4 17 7 15.6 10.6 12 7 8.4 8.4 7 12 10.6 15.6 7 17 8.4 13.4 12 17 15.6z"/></svg><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" action="register.php">
        <?= csrf_field() ?>
        <div class="field">
          <label for="name">Full name</label>
          <input type="text" id="name" name="name" class="input" value="<?= e($name) ?>" placeholder="Jane Doe" required autofocus>
        </div>
        <div class="field">
          <label for="remail">Email address</label>
          <input type="email" id="remail" name="email" class="input" value="<?= e($email) ?>" placeholder="you@company.com" required>
        </div>
        <div class="form-grid" style="grid-template-columns:1fr 1fr">
          <div class="field">
            <label for="department">Department</label>
            <input type="text" id="department" name="department" class="input" value="<?= e($department) ?>" placeholder="Engineering">
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" class="input" value="<?= e($phone) ?>" placeholder="+1 555 0100">
          </div>
        </div>
        <div class="field">
          <label>Account type</label>
          <div class="role-cards">
            <label class="role-card <?= $role==='employee'?'is-active':'' ?>">
              <input type="radio" name="role" value="employee" <?= $role==='employee'||!$role?'checked':'' ?> style="display:none"> Employee
            </label>
            <label class="role-card <?= $role==='manager'?'is-active':'' ?>">
              <input type="radio" name="role" value="manager" <?= $role==='manager'?'checked':'' ?> style="display:none"> Manager
            </label>
            <label class="role-card <?= $role==='admin'?'is-active':'' ?>">
              <input type="radio" name="role" value="admin" <?= $role==='admin'?'checked':'' ?> style="display:none"> Admin
            </label>
          </div>
        </div>
        <div class="form-grid">
          <div class="field">
            <label for="rpass">Password</label>
            <div class="password-field">
              <input type="password" id="rpass" name="password" class="input" placeholder="Min. 6 characters" required>
              <button type="button" class="toggle-pass" aria-label="Show password"><svg viewBox="0 0 24 24" class="ic ic-sm"><path d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5c-1.7-4.4-6-7.5-11-7.5zM12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg></button>
            </div>
          </div>
          <div class="field">
            <label for="rcpass">Confirm password</label>
            <div class="password-field">
              <input type="password" id="rcpass" name="confirm_password" class="input" placeholder="Re-enter password" required>
              <button type="button" class="toggle-pass" aria-label="Show password"><svg viewBox="0 0 24 24" class="ic ic-sm"><path d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5c-1.7-4.4-6-7.5-11-7.5zM12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg></button>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn--gradient btn--block btn--lg">Create account →</button>
      </form>

      <div class="auth-switch">
        Already have an account? <a href="login.php">Sign in</a>
      </div>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('input[name=role]').forEach(r=>{
  r.addEventListener('change',()=>{document.querySelectorAll('.role-card').forEach(c=>c.classList.remove('is-active'));r.closest('.role-card').classList.add('is-active');});
});
if(localStorage.getItem('tf-theme'))document.documentElement.setAttribute('data-theme',localStorage.getItem('tf-theme'));
</script>
</body>
</html>
