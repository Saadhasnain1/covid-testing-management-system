<?php
declare(strict_types=1);
$local = is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : [];
if (!is_array($local)) { throw new RuntimeException('Local configuration must return an array.'); }
function setting(string $key, mixed $default = null): mixed {
    global $local;
    $value = getenv($key);
    return $value !== false ? $value : ($local[$key] ?? $default);
}
error_reporting(E_ALL);
ini_set('display_errors', setting('APP_ENV', 'production') === 'development' ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set((string) setting('APP_TIMEZONE', 'UTC'));
function db(): mysqli {
    static $connection;
    if ($connection instanceof mysqli) { return $connection; }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = new mysqli((string) setting('DB_HOST', '127.0.0.1'), (string) setting('DB_USER', 'root'),
        (string) setting('DB_PASSWORD', ''), (string) setting('DB_NAME', 'covidtmsdb'), (int) setting('DB_PORT', 3306));
    $connection->set_charset('utf8mb4');
    return $connection;
}
