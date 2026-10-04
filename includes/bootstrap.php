<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
set_exception_handler(function (Throwable $error): void {
    error_log((string) $error);
    if (PHP_SAPI === 'cli') { fwrite(STDERR, "Operation failed. See the error log for details.\n"); exit(1); }
    http_response_code(503);
    $title = 'Service temporarily unavailable'; $admin = false;
    require __DIR__ . '/header.php';
    echo '<div class="panel"><h1>We could not complete your request</h1><p>The service is temporarily unavailable. Please try again later. For local setup, check MySQL, database import and configuration.</p><a class="btn btn-primary" href="index.php">Return home</a></div>';
    require __DIR__ . '/footer.php';
});
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('covidvms');
    session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax', 'path' => '/']);
    if (!session_start()) { throw new RuntimeException('Unable to start the session. Check session.save_path permissions.'); }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
    header('Cache-Control: no-store');
}
function query(string $sql, array $params = []): mysqli_stmt {
    $statement = db()->prepare($sql);
    if ($params) { $statement->bind_param(str_repeat('s', count($params)), ...$params); }
    $statement->execute(); return $statement;
}
function rows(string $sql, array $params = []): array { return query($sql, $params)->get_result()->fetch_all(MYSQLI_ASSOC); }
function one(string $sql, array $params = []): ?array { return rows($sql, $params)[0] ?? null; }
function e(mixed $value): string { return htmlspecialchars(is_scalar($value) || $value === null ? (string) $value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function input(string $name, ?array $source = null): string {
    $value = ($source ?? $_POST)[$name] ?? '';
    if (!is_string($value)) { return ''; }
    return trim($value);
}
function redirect(string $path): never { header('Location: ' . $path, true, 303); exit; }
function flash(string $message): void { $_SESSION['flash'] = $message; }
function csrf(): string {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
function check_csrf(): void {
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], input('csrf'))) { throw new InvalidArgumentException('This form has expired. Refresh the page and try again.'); }
}
function require_admin(): void {
    if (empty($_SESSION['aid'])) { redirect('login.php'); }
    if (time() - (int) ($_SESSION['last_active'] ?? 0) > 1800) {
        unset($_SESSION['aid']); flash('Your session expired. Please sign in again.'); redirect('login.php');
    }
    if (!one('SELECT ID FROM tbladmin WHERE ID = ?', [$_SESSION['aid']])) { unset($_SESSION['aid']); redirect('login.php'); }
    $_SESSION['last_active'] = time();
}
function text_value(string $name, string $label, int $max = 120): string {
    $value = input($name);
    if ($value === '' || mb_strlen($value) > $max) { throw new InvalidArgumentException($label . ' is required and must be at most ' . $max . ' characters.'); }
    return $value;
}
function mobile(string $name = 'mobilenumber'): string {
    $value = input($name);
    if (!preg_match('/^[0-9]{10,15}$/D', $value)) { throw new InvalidArgumentException('Enter a mobile number containing 10–15 digits.'); }
    return $value;
}
function valid_date(string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}
function password_matches(string $password, string $hash): bool {
    return password_verify($password, $hash) || (filter_var(setting('ALLOW_LEGACY_MD5', true), FILTER_VALIDATE_BOOL)
        && preg_match('/^[a-f0-9]{32}$/iD', $hash) && hash_equals(strtolower($hash), md5($password)));
}
function validate_password(string $password): void {
    if (strlen($password) < 12 || strlen($password) > 72) { throw new InvalidArgumentException('Use a password with 12–72 bytes.'); }
}
function password_input(string $name): string {
    $value = $_POST[$name] ?? '';
    if (!is_string($value) || strlen($value) > 72) { throw new InvalidArgumentException('Enter a valid password of at most 72 bytes.'); }
    return $value;
}
function field(string $name, string $label, string $type = 'text', mixed $value = '', bool $required = true, int $max = 120): void {
    echo '<div class="form-group"><label for="' . e($name) . '">' . e($label) . ($required ? ' <span aria-hidden="true">*</span>' : '') . '</label><input class="form-control" id="' . e($name) . '" name="' . e($name) . '" type="' . e($type) . '" value="' . e($type === 'password' ? '' : $value) . '" maxlength="' . $max . '"' . ($required ? ' required' : '') . '></div>';
}
function select_field(string $name, string $label, array $choices, string $value = ''): void {
    echo '<div class="form-group"><label for="' . e($name) . '">' . e($label) . ' *</label><select class="form-control" id="' . e($name) . '" name="' . e($name) . '" required><option value="">Select an option</option>';
    foreach ($choices as $key => $text) { echo '<option value="' . e($key) . '"' . ($value === (string) $key ? ' selected' : '') . '>' . e($text) . '</option>'; }
    echo '</select></div>';
}
function form_error(?string $error): void { if ($error) { echo '<div class="alert alert-danger" role="alert">' . e($error) . '</div>'; } }
const STATUSES = ['New', 'Assigned', 'On the Way for Collection', 'Sample Collected', 'Sent to Lab', 'Delivered'];
function status_badge(?string $status): string {
    $status = $status ?: 'New';
    $class = ['New' => 'new', 'Assigned' => 'assigned', 'On the Way for Collection' => 'assigned', 'Sample Collected' => 'collected', 'Sent to Lab' => 'lab', 'Delivered' => 'delivered'][$status] ?? 'new';
    return '<span class="status status-' . $class . '">' . e($status) . '</span>';
}
function transaction(callable $work): mixed {
    db()->begin_transaction();
    try { $result = $work(); db()->commit(); return $result; }
    catch (Throwable $error) { db()->rollback(); throw $error; }
}
function storage_path(): string { return (string) setting('REPORT_STORAGE', dirname(__DIR__) . '/storage'); }
function authorized_order(string $order): bool { return (int) ($_SESSION['orders'][$order] ?? 0) > time() - 1800; }
function pagination(int $total, int $page, int $size, array $filters = []): void {
    $pages = max(1, (int) ceil($total / $size));
    echo '<nav class="pagination-bar" aria-label="Pagination"><span class="helper">' . e($total) . ' records · Page ' . e($page) . ' of ' . e($pages) . '</span><div>';
    if ($page > 1) { echo '<a class="btn btn-light btn-sm mr-2" href="?' . e(http_build_query([...$filters, 'page'=>$page-1])) . '">Previous</a>'; }
    if ($page < $pages) { echo '<a class="btn btn-light btn-sm" href="?' . e(http_build_query([...$filters, 'page'=>$page+1])) . '">Next</a>'; }
    echo '</div></nav>';
}
