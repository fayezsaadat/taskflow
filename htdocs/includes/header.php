<?php
/** TaskFlow — shared page header (DOCTYPE, head, navbar) */
require_once __DIR__ . '/auth.php';
$cu = current_user();
$pageTitle = $pageTitle ?? APP_NAME . ' — Dashboard';
$activeNav = $activeNav ?? '';
$bodyClass = $bodyClass ?? '';
$theme = $_SESSION['theme'] ?? 'light';
$unread = $cu ? unread_count((int)$cu['id']) : 0;
?>
<!doctype html>
<html lang="en" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#6366f1">
    <meta name="description" content="TaskFlow — modern project & task management for teams">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="assets/img/logo-48.png">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="apple-touch-icon" href="assets/img/logo-256.png">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= e($bodyClass) ?> grain">

<!-- Animated aurora background -->
<div class="aurora"><span class="a1"></span><span class="a2"></span><span class="a3"></span></div>

<div class="app">
    <!-- ============ SIDEBAR ============ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar__brand">
            <span class="brand__logo"><img src="assets/img/logo-256.png" alt="TaskFlow"></span>
            <span class="brand__name">Task<b>Flow</b></span>
            <button class="sidebar__close" id="sidebarClose" aria-label="Close menu">&times;</button>
        </div>

        <nav class="sidebar__nav">
            <a href="dashboard.php"     class="nav-link <?= $activeNav==='dashboard'?'is-active':'' ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg><span>Dashboard</span></a>
            <a href="projects.php"      class="nav-link <?= $activeNav==='projects'?'is-active':'' ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M20 6h-4V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM10 4h4v2h-4V4z"/></svg><span>Projects</span></a>
            <a href="tasks.php"         class="nav-link <?= $activeNav==='tasks'?'is-active':'' ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-9 14l-4-4 1.4-1.4L10 14.2l6.6-6.6L18 9l-8 8z"/></svg><span>My Tasks</span></a>
            <a href="calendar.php"      class="nav-link <?= $activeNav==='calendar'?'is-active':'' ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14v10z"/></svg><span>Calendar</span></a>
            <a href="reports.php"       class="nav-link <?= $activeNav==='reports'?'is-active':'' ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M5 9.2h3V19H5V9.2zM10.6 5h2.8v14h-2.8V5zm5.6 8H19v6h-2.8v-6z"/></svg><span>Reports</span></a>

            <div class="nav-group-label">Team</div>
            <a href="team.php"          class="nav-link <?= $activeNav==='team'?'is-active':'' ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg><span>Members</span></a>
            <?php if (is_admin()): ?>
            <a href="activity_logs.php" class="nav-link <?= $activeNav==='logs'?'is-active':'' ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M13 3a9 9 0 0 0-9 9H1l3.9 3.9.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.95 8.95 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg><span>Activity Logs</span></a>
            <?php endif; ?>

            <div class="side-promo">
                <h4>⚡ Pro Analytics</h4>
                <p>Unlock advanced reporting & forecasting for your team.</p>
                <a href="reports.php" class="btn btn--sm">Explore Reports</a>
            </div>
        </nav>

        <div class="sidebar__footer">
            <a href="settings.php" class="nav-link <?= $activeNav==='settings'?'is-active':'' ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M19.14 12.94a7.5 7.5 0 0 0 .06-.94 7.5 7.5 0 0 0-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.61-.22l-2.39.96a7 7 0 0 0-1.62-.94l-.36-2.54A.5.5 0 0 0 13.93 2h-3.84a.5.5 0 0 0-.49.42l-.36 2.54c-.59.24-1.13.55-1.62.94l-2.39-.96a.5.5 0 0 0-.61.22L2.7 8.48a.5.5 0 0 0 .12.64l2.03 1.58c-.04.31-.06.62-.06.94s.02.63.06.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32c.13.23.4.32.61.22l2.39-.96c.49.39 1.03.7 1.62.94l.36 2.54c.04.24.25.42.49.42h3.84c.24 0 .45-.18.49-.42l.36-2.54c.59-.24 1.13-.55 1.62-.94l2.39.96c.21.1.48.01.61-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58zM12 15.5A3.5 3.5 0 1 1 12 8.5a3.5 3.5 0 0 1 0 7z"/></svg><span>Settings</span></a>
        </div>
    </aside>

    <!-- backdrop for mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- ============ MAIN ============ -->
    <div class="main">
        <header class="navbar">
            <div class="navbar__left">
                <button class="icon-btn" id="menuToggle" aria-label="Toggle menu">
                    <svg viewBox="0 0 24 24" class="ic"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                </button>
                <div class="navbar__search">
                    <svg viewBox="0 0 24 24" class="ic"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 5L20.49 19l-5-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/></svg>
                    <input type="text" id="globalSearch" placeholder="Search projects, tasks, people…" autocomplete="off">
                    <kbd>/</kbd>
                    <div class="search-results" id="searchResults"></div>
                </div>
            </div>

            <div class="navbar__right">
                <button class="icon-btn" id="themeToggle" aria-label="Toggle theme" title="Toggle theme">
                    <svg viewBox="0 0 24 24" class="ic ic-sun"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
                    <svg viewBox="0 0 24 24" class="ic ic-moon"><path d="M12 3a9 9 0 1 0 9 9c0-.46-.04-.92-.1-1.36a5.4 5.4 0 0 1-7.54-7.54A9 9 0 0 0 12 3z"/></svg>
                </button>

                <a href="notifications.php" class="icon-btn has-badge" aria-label="Notifications" title="Notifications">
                    <svg viewBox="0 0 24 24" class="ic"><path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                    <?php if ($unread): ?><span class="badge-count"><?= $unread ?></span><?php endif; ?>
                </a>

                <div class="user-menu" id="userMenu">
                    <button class="user-menu__btn" id="userMenuBtn">
                        <?= avatar_html($cu['name'] ?? 'User', $cu['avatar'] ?? null, '34') ?>
                        <span class="user-menu__name"><?= e($cu['name'] ?? '') ?></span>
                        <svg viewBox="0 0 24 24" class="ic ic-sm"><path d="M7 10l5 5 5-5z"/></svg>
                    </button>
                    <div class="dropdown" id="userDropdown">
                        <div class="dropdown__header">
                            <?= avatar_html($cu['name'] ?? 'User', $cu['avatar'] ?? null, '46') ?>
                            <div>
                                <div class="dropdown__name"><?= e($cu['name'] ?? '') ?></div>
                                <div class="dropdown__email"><?= e($cu['email'] ?? '') ?></div>
                            </div>
                        </div>
                        <a href="profile.php" class="dropdown__item"><svg viewBox="0 0 24 24" class="ic ic-sm"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0 2c-3.3 0-8 1.7-8 5v3h16v-3c0-3.3-4.7-5-8-5z"/></svg> My Profile</a>
                        <a href="settings.php" class="dropdown__item"><svg viewBox="0 0 24 24" class="ic ic-sm"><path d="M19.14 12.94a7.5 7.5 0 0 0 .06-.94 7.5 7.5 0 0 0-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.61-.22l-2.39.96a7 7 0 0 0-1.62-.94l-.36-2.54A.5.5 0 0 0 13.93 2h-3.84a.5.5 0 0 0-.49.42l-.36 2.54c-.59.24-1.13.55-1.62.94l-2.39-.96a.5.5 0 0 0-.61.22L2.7 8.48a.5.5 0 0 0 .12.64l2.03 1.58c-.04.31-.06.62-.06.94s.02.63.06.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32c.13.23.4.32.61.22l2.39-.96c.49.39 1.03.7 1.62.94l.36 2.54c.04.24.25.42.49.42h3.84c.24 0 .45-.18.49-.42l.36-2.54c.59-.24 1.13-.55 1.62-.94l2.39.96c.21.1.48.01.61-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58zM12 15.5A3.5 3.5 0 1 1 12 8.5a3.5 3.5 0 0 1 0 7z"/></svg> Settings</a>
                        <div class="dropdown__divider"></div>
                        <a href="logout.php" class="dropdown__item dropdown__item--danger"><svg viewBox="0 0 24 24" class="ic ic-sm"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8v-2H4V5z"/></svg> Sign out</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="content" id="content">
            <?= show_flash() ?>
