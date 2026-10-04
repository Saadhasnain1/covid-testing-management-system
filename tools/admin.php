<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/includes/bootstrap.php';
$mode=$argv[1] ?? '';
if (!in_array($mode,['create','reset'],true)) { fwrite(STDERR,"Usage: php tools/admin.php create|reset\n"); exit(1); }
function prompt(string $label): string { echo $label.': '; return trim((string) fgets(STDIN)); }
$username=prompt('Username');
if (!preg_match('/^[A-Za-z0-9_.-]{1,20}$/D',$username)) { throw new InvalidArgumentException('Username must contain 1–20 letters, numbers, dots, hyphens or underscores.'); }
// Read from stdin, never command arguments. Terminal input is visible; use a private terminal.
echo 'New password (12–72 bytes; input is visible): ';
$password=rtrim((string) fgets(STDIN), "\r\n");
validate_password($password);
$existing=one('SELECT ID FROM tbladmin WHERE AdminuserName=?',[$username]);
if ($mode==='reset') {
    if (!$existing) { throw new InvalidArgumentException('Administrator not found.'); }
    query('UPDATE tbladmin SET Password=? WHERE ID=?',[password_hash($password,PASSWORD_DEFAULT),$existing['ID']]);
} else {
    if ($existing) { throw new InvalidArgumentException('Username already exists.'); }
    $name=prompt('Display name'); $email=prompt('Email'); $phone=prompt('Mobile (10–15 digits)');
    if ($name==='' || mb_strlen($name)>120 || strlen($email)>120 || !filter_var($email,FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9]{10,15}$/D',$phone)) { throw new InvalidArgumentException('Enter a valid name, email and mobile number.'); }
    query('INSERT INTO tbladmin (AdminName,AdminuserName,MobileNumber,Email,Password) VALUES (?,?,?,?,?)',[$name,$username,$phone,$email,password_hash($password,PASSWORD_DEFAULT)]);
}
echo "Administrator ".$mode." completed.\n";
