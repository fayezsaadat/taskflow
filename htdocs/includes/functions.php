<?php
/**
 * TaskFlow — Helper functions
 */

require_once __DIR__ . '/../config/database.php';

/* -----------------------------------------------------------
 * Sanitisation & output
 * --------------------------------------------------------- */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function clean(string $value): string
{
    return trim($value);
}

function redirect(string $path): void
{
    header("Location: $path");
    exit;
}

/* -----------------------------------------------------------
 * Activity logging
 * --------------------------------------------------------- */
function log_activity(string $action, ?string $entityType = null, ?int $entityId = null, ?string $description = null): void
{
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    try {
        $stmt = db()->prepare(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $action, $entityType, $entityId, $description, $ip]);
    } catch (Throwable $ex) {
        // logging must never break the request
    }
}

/* -----------------------------------------------------------
 * Notifications
 * --------------------------------------------------------- */
function notify(int $userId, string $title, string $message, ?string $link = null, string $type = 'info'): void
{
    try {
        $stmt = db()->prepare(
            "INSERT INTO notifications (user_id, title, message, link, type) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $title, $message, $link, $type]);
    } catch (Throwable $ex) {
    }
}

function unread_count(int $userId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/* -----------------------------------------------------------
 * Labels / badges
 * --------------------------------------------------------- */
function status_label(string $status): array
{
    $map = [
        'todo'        => ['To Do', 'badge--gray'],
        'in_progress' => ['In Progress', 'badge--blue'],
        'review'      => ['Review', 'badge--yellow'],
        'completed'   => ['Completed', 'badge--green'],
    ];
    return $map[$status] ?? ['Unknown', 'badge--gray'];
}

function priority_label(string $priority): array
{
    $map = [
        'low'    => ['Low', 'badge--gray'],
        'medium' => ['Medium', 'badge--blue'],
        'high'   => ['High', 'badge--yellow'],
        'urgent' => ['Urgent', 'badge--red'],
    ];
    return $map[$priority] ?? ['Normal', 'badge--gray'];
}

function project_status_label(string $status): array
{
    $map = [
        'planning'  => ['Planning', 'badge--gray'],
        'active'    => ['Active', 'badge--green'],
        'on_hold'   => ['On Hold', 'badge--yellow'],
        'completed' => ['Completed', 'badge--blue'],
        'cancelled' => ['Cancelled', 'badge--red'],
    ];
    return $map[$status] ?? ['Unknown', 'badge--gray'];
}

function role_label(string $role): string
{
    $map = ['admin' => 'Admin', 'manager' => 'Manager', 'employee' => 'Employee'];
    return $map[$role] ?? $role;
}

function role_badge(string $role): string
{
    $map = [
        'admin'    => 'badge--red',
        'manager'  => 'badge--purple',
        'employee' => 'badge--blue',
    ];
    return $map[$role] ?? 'badge--gray';
}

/* -----------------------------------------------------------
 * Dates
 * --------------------------------------------------------- */
function fmt_date(?string $date, string $format = 'M j, Y'): string
{
    if (!$date || $date === '0000-00-00') return '—';
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

function fmt_datetime(?string $dt, string $format = 'M j, Y g:i A'): string
{
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date($format, $ts) : '—';
}

function time_ago(?string $dt): string
{
    if (!$dt) return '';
    $ts = strtotime($dt);
    if (!$ts) return '';
    $diff = time() - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}

function days_until(?string $date): ?int
{
    if (!$date || $date === '0000-00-00') return null;
    $now = strtotime(date('Y-m-d'));
    $then = strtotime($date);
    return (int) round(($then - $now) / 86400);
}

function is_overdue(?string $date): bool
{
    $d = days_until($date);
    return $d !== null && $d < 0;
}

/* -----------------------------------------------------------
 * Avatars & initials
 * --------------------------------------------------------- */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function avatar_html(string $name, ?string $img = null, string $size = '36'): string
{
    if ($img && file_exists(__DIR__ . '/../uploads/' . $img)) {
        return '<img src="uploads/' . e($img) . '" class="avatar" width="' . $size . '" height="' . $size . '" alt="">';
    }
    return '<span class="avatar avatar--text" style="width:' . $size . 'px;height:' . $size . 'px">' . e(initials($name)) . '</span>';
}

/* -----------------------------------------------------------
 * Money
 * --------------------------------------------------------- */
function money($amount): string
{
    return '$' . number_format((float) $amount, 2);
}

/* -----------------------------------------------------------
 * Pagination helper
 * --------------------------------------------------------- */
function paginate_sql(string $baseSql, array $params, int $page, int $perPage): array
{
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;

    $countSql = "SELECT COUNT(*) FROM ($baseSql) AS _cnt";
    $stmt = db()->prepare($countSql);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $dataSql = "$baseSql LIMIT $perPage OFFSET $offset";
    $stmt = db()->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return [
        'rows'  => $rows,
        'total' => $total,
        'page'  => $page,
        'perPage' => $perPage,
        'pages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
    ];
}

function pagination_links(int $page, int $pages, string $base): string
{
    if ($pages <= 1) return '';
    $html = '<nav class="pagination">';
    $html .= '<a class="btn btn--sm ' . ($page <= 1 ? 'is-disabled' : '') . '" href="' . $base . 'page=' . max(1, $page - 1) . '">&laquo; Prev</a>';
    for ($i = 1; $i <= $pages; $i++) {
        $active = $i === $page ? 'is-active' : '';
        $html .= '<a class="btn btn--sm ' . $active . '" href="' . $base . 'page=' . $i . '">' . $i . '</a>';
    }
    $html .= '<a class="btn btn--sm ' . ($page >= $pages ? 'is-disabled' : '') . '" href="' . $base . 'page=' . min($pages, $page + 1) . '">Next &raquo;</a>';
    $html .= '</nav>';
    return $html;
}

/* -----------------------------------------------------------
 * CSRF tokens
 * --------------------------------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/* -----------------------------------------------------------
 * Misc
 * --------------------------------------------------------- */
function current_url(): string
{
    return basename($_SERVER['PHP_SELF']);
}

function flash(?string $key = null): ?array
{
    if ($key === null) return $_SESSION['flash'] ?? null;
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function show_flash(): string
{
    $html = '';
    if (!empty($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $f) {
            $html .= '<div class="alert alert--' . e($f['type']) . '">' . e($f['message']) . '</div>';
        }
        unset($_SESSION['flash']);
    }
    return $html;
}

function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function file_size_label(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}
