<?php
/** Global search (AJAX) — projects, tasks, users */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) json_response(['html' => '']);

$like = "%$q%";
$pdo = db();
$html = '';

// Projects
$stmt = $pdo->prepare("SELECT id, name FROM projects WHERE name LIKE ? OR description LIKE ? ORDER BY name LIMIT 5");
$stmt->execute([$like, $like]);
$projects = $stmt->fetchAll();
if ($projects) {
    $html .= '<div class="search-results__head">Projects</div>';
    foreach ($projects as $p) {
        $html .= '<a href="project_view.php?id=' . (int)$p['id'] . '"><svg viewBox="0 0 24 24" class="ic ic-sm"><path d="M20 6h-4V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM10 4h4v2h-4V4z"/></svg><span>' . e($p['name']) . '</span><span class="sr-tag">Project</span></a>';
    }
}

// Tasks
$stmt = $pdo->prepare("SELECT t.id, t.title, p.name project FROM tasks t JOIN projects p ON p.id=t.project_id WHERE t.title LIKE ? ORDER BY t.id DESC LIMIT 5");
$stmt->execute([$like]);
$tasks = $stmt->fetchAll();
if ($tasks) {
    $html .= '<div class="search-results__head">Tasks</div>';
    foreach ($tasks as $t) {
        $html .= '<a href="task_view.php?id=' . (int)$t['id'] . '"><svg viewBox="0 0 24 24" class="ic ic-sm"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-9 14l-4-4 1.4-1.4L10 14.2l6.6-6.6L18 9l-8 8z"/></svg><span>' . e($t['title']) . '</span><span class="sr-tag">' . e($t['project']) . '</span></a>';
    }
}

// Users
$stmt = $pdo->prepare("SELECT id, name, job_title FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY name LIMIT 5");
$stmt->execute([$like, $like]);
$users = $stmt->fetchAll();
if ($users) {
    $html .= '<div class="search-results__head">People</div>';
    foreach ($users as $u) {
        $html .= '<a href="profile.php?id=' . (int)$u['id'] . '">' . avatar_html($u['name'], null, 24) . '<span>' . e($u['name']) . '</span><span class="sr-tag">' . e($u['job_title'] ?: 'Member') . '</span></a>';
    }
}

if (!$html) $html = '<div class="search-results__head">No results found</div>';
json_response(['html' => $html]);
