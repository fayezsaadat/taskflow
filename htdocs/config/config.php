<?php
/**
 * TaskFlow — Application configuration
 */

// ---- Database ----
// ⚙️ تنظیمات پیش‌فرض برای XAMPP (root بدون پسورد)
// اگر پسورد دیتابیس دارید، در DB_PASS قرار دهید.
define('DB_HOST', 'sql201.infinityfree.com');
define('DB_PORT', '3306');
define('DB_NAME', 'if0_42661653_db');
define('DB_USER', 'if0_42661653');
define('DB_PASS', 'pLhAh9D2cQji');
define('DB_CHARSET', 'utf8mb4');

// ---- Application ----
define('APP_NAME', 'TaskFlow');
define('APP_VERSION', '1.0.0');
define('APP_URL', ''); // leave empty to auto-detect

// ---- File uploads ----
define('UPLOAD_DIR', __DIR__ . '/../uploads/tasks/');
define('UPLOAD_URL', 'uploads/tasks/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB

// ---- Session ----
define('SESSION_LIFETIME', 60 * 60 * 8); // 8 hours

// ---- Allowed upload types ----
define('ALLOWED_EXTENSIONS', [
    'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx',
    'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', 'json'
]);

// ---- Error reporting (set false in production) ----
define('DEBUG', true);
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

date_default_timezone_set('UTC');
