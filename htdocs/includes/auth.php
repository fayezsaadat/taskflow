<?php
/**
 * TaskFlow — Authentication & authorization
 */

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* Authenticate a user by email + password.
 * Returns the user array on success or null on failure. */
function attempt_login(string $email, string $password): ?array
{
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return null;
    }
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_avatar'] = $user['avatar'];
    $_SESSION['theme'] = $user['theme'] ?? 'light';

    // update last activity handled by logs
    log_activity('login', 'user', $user['id'], $user['name'] . ' signed in');
}

function logout_user(): void
{
    if (!empty($_SESSION['user_id'])) {
        log_activity('logout', 'user', $_SESSION['user_id'], ($_SESSION['user_name'] ?? '') . ' signed out');
    }
    $_SESSION = [];
    session_destroy();
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;

    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    return $u ?: null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/* Force a login — used on every protected page. */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('info', 'Please sign in to continue.');
        redirect('login.php');
    }
}

/* Restrict a page to certain roles. */
function require_role(string ...$roles): void
{
    require_login();
    if (!in_array($_SESSION['user_role'], $roles, true)) {
        http_response_code(403);
        die('<!doctype html><meta charset="utf-8"><title>403</title>
             <div style="font-family:sans-serif;text-align:center;padding:80px">
               <h1>403 — Access Denied</h1>
               <p>You do not have permission to view this page.</p>
               <a href="dashboard.php">Back to Dashboard</a>
             </div>');
    }
}

function is_admin(): bool   { return ($_SESSION['user_role'] ?? '') === 'admin'; }
function is_manager(): bool { return in_array(($_SESSION['user_role'] ?? ''), ['admin','manager']); }
function is_employee(): bool { return ($_SESSION['user_role'] ?? '') === 'employee'; }

/* Quick permission helpers */
function can_manage_projects(): bool { return is_manager(); }
function can_manage_team(): bool    { return is_admin(); }
