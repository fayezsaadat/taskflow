<?php
/**
 * TaskFlow — Database connection (PDO)
 *
 * Tries the configured credentials first (XAMPP default: root / no password).
 * If that fails it tries a small list of common fallbacks, so the app runs
 * out-of-the-box on XAMPP, MAMP, Laragon, Docker, etc.
 */

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Candidate credential sets — first one that connects wins.
    // (#1 = your config.php settings; the rest are automatic fallbacks.)
    $candidates = [
        ['host' => DB_HOST, 'user' => DB_USER, 'pass' => DB_PASS],
        ['host' => '127.0.0.1', 'user' => DB_USER, 'pass' => DB_PASS],
        ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
        ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
        ['host' => '127.0.0.1', 'user' => DB_NAME, 'pass' => DB_NAME . '123'],
    ];

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $lastError = null;
    foreach ($candidates as $c) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $c['host'], DB_PORT, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
            return $pdo;
        } catch (PDOException $e) {
            $lastError = $e;
        }
    }

    // All candidates failed.
    http_response_code(500);
    die('<div style="font-family:sans-serif;max-width:560px;margin:60px auto;padding:28px;border-radius:14px;background:#fff3f3;border:1px solid #fca5a5;color:#991b1b">
            <h2 style="margin:0 0 8px">⚠️ Database connection failed</h2>
            <p>TaskFlow could not connect to MySQL. Please check:</p>
            <ul>
              <li>MySQL/MariaDB is running</li>
              <li>The <code>taskflow</code> database was imported from <code>database.sql</code></li>
              <li>Credentials in <code>config/config.php</code></li>
            </ul>
            <p style="margin:12px 0 0;font-size:13px;opacity:.8">' . ($lastError ? htmlspecialchars($lastError->getMessage()) : 'Unknown error') . '</p>
          </div>');
}
