<?php
/** TaskFlow — Login */
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) redirect('dashboard.php');

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $email    = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $errors[] = 'Please enter your email and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $user = attempt_login($email, $password);
            if (!$user) {
                $errors[] = 'Invalid credentials or inactive account.';
                log_activity('login_failed', 'user', null, 'Failed login for ' . $email);
            } else {
                login_user($user);
                set_flash('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '!');
                redirect('dashboard.php');
            }
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
<title>Sign in — TaskFlow</title>
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
        <h2>Manage projects, tasks &amp; teams — all in one flow.</h2>
        <p>TaskFlow brings your projects, deadlines and team collaboration together with real-time insights and beautiful dashboards.</p>
      </div>

      <!-- floating glassmorphic preview card -->
      <div class="hero-preview float">
        <div class="hp-head">
          <div class="hp-dots"><i></i><i></i><i></i></div>
          <span>TaskFlow · Dashboard</span>
        </div>
        <div class="hp-body">
          <div class="hp-stat"><div class="hp-stat__bar" style="width:80%"></div></div>
          <div class="hp-row">
            <div class="hp-card"><div class="hp-mini"></div><div class="hp-line w70"></div></div>
            <div class="hp-card"><div class="hp-mini hp-mini--green"></div><div class="hp-line w50"></div></div>
          </div>
          <div class="hp-chart">
            <svg viewBox="0 0 120 40" preserveAspectRatio="none"><path d="M0,32 L20,24 L40,28 L60,14 L80,18 L100,6 L120,10" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M0,32 L20,24 L40,28 L60,14 L80,18 L100,6 L120,10 L120,40 L0,40 Z" fill="rgba(255,255,255,.15)"/></svg>
          </div>
        </div>
      </div>

      <ul class="auth-hero__feat">
        <li><span class="ic-wrap"><svg viewBox="0 0 24 24" class="ic"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></span> Visual dashboards &amp; reports</li>
        <li><span class="ic-wrap"><svg viewBox="0 0 24 24" class="ic"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></span> Kanban task boards</li>
        <li><span class="ic-wrap"><svg viewBox="0 0 24 24" class="ic"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></span> Team collaboration &amp; comments</li>
        <li><span class="ic-wrap"><svg viewBox="0 0 24 24" class="ic"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></span> Deadline calendar &amp; alerts</li>
      </ul>
    </div>

    <div class="auth-form">
      <div class="form-head">
        <h1>Welcome</h1>
        <p class="sub">Please sign in to your account to continue.</p>
      </div>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert--error"><svg viewBox="0 0 24 24" class="ic"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm5 13.6L15.6 17 12 13.4 8.4 17 7 15.6 10.6 12 7 8.4 8.4 7 12 10.6 15.6 7 17 8.4 13.4 12 17 15.6z"/></svg><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" action="login.php" autocomplete="on" id="loginForm">
        <?= csrf_field() ?>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" class="input" value="<?= e($email) ?>" placeholder="you@company.com" required autofocus>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="password-field">
            <input type="password" id="password" name="password" class="input" placeholder="••••••••" required>
            <button type="button" class="toggle-pass" aria-label="Show password">
              <svg viewBox="0 0 24 24" class="ic ic-sm"><path d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5c-1.7-4.4-6-7.5-11-7.5zM12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn--gradient btn--block btn--lg">Sign in →</button>
      </form>

      <div class="auth-switch">
        Don't have an account? <a href="register.php">Create one</a>
      </div>

      <div class="demo-creds">
        <strong><svg viewBox="0 0 24 24" class="ic ic-sm"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-2 15-5-5 1.4-1.4L10 14.2l7.6-7.6L19 8l-9 9z"/></svg> Demo accounts</strong>
        Admin: <code>admin@taskflow.com</code> · <code>admin123</code><br>
        Manager: <code>manager@taskflow.com</code> · <code>manager123</code><br>
        Employee: <code>employee@taskflow.com</code> · <code>employee123</code>
      </div>
    </div>
  </div>
</div>
<script>if(localStorage.getItem('tf-theme'))document.documentElement.setAttribute('data-theme',localStorage.getItem('tf-theme'));</script>
</body>
</html>
