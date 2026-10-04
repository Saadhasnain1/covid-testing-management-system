<?php
require_once __DIR__ . '/bootstrap.php';
$page = basename($_SERVER['SCRIPT_NAME']);
$admin = in_array($page,['profile.php','change-password.php'],true);
if ($admin) { require_admin(); }
$title = ['login.php'=>'Admin sign in','profile.php'=>'Your profile','change-password.php'=>'Change password','password-recovery.php'=>'Account recovery','logout.php'=>'Sign out'][$page] ?? 'Account';
$error = null;
$profile = $admin ? one('SELECT * FROM tbladmin WHERE ID = ?', [$_SESSION['aid']]) : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        if ($page === 'logout.php') {
            $_SESSION = [];
            setcookie(session_name(), '', ['expires'=>time()-3600,'path'=>'/','httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite'=>'Lax']);
            session_destroy(); redirect('login.php');
        }
        if ($page === 'login.php') {
            if ((int) ($_SESSION['login_locked_until'] ?? 0) > time()) { throw new InvalidArgumentException('Too many attempts. Please try again in five minutes.'); }
            $username = text_value('username','Username',20);
            $password = password_input('inputpwd');
            $user = one('SELECT * FROM tbladmin WHERE AdminuserName = ?', [$username]);
            // A dummy hash keeps unknown usernames on the password verification path.
            $hash = $user['Password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
            if (!$user || !password_matches($password,$hash)) {
                password_verify($password,$hash);
                $_SESSION['login_failures'] = (int) ($_SESSION['login_failures'] ?? 0) + 1;
                if ($_SESSION['login_failures'] >= 5) { $_SESSION['login_locked_until'] = time()+300; $_SESSION['login_failures']=0; }
                throw new InvalidArgumentException('The username or password is incorrect.');
            }
            if (!password_verify($password,$hash) || password_needs_rehash($hash,PASSWORD_DEFAULT)) {
                query('UPDATE tbladmin SET Password = ? WHERE ID = ?', [password_hash($password,PASSWORD_DEFAULT),$user['ID']]);
            }
            session_regenerate_id(true);
            $_SESSION['aid'] = $user['ID']; $_SESSION['last_active'] = time();
            unset($_SESSION['csrf'],$_SESSION['login_failures'],$_SESSION['login_locked_until']);
            redirect('dashboard.php');
        } elseif ($page === 'profile.php') {
            $name = text_value('adminname','Name');
            $phone = mobile(); $email = text_value('email','Email');
            if (!filter_var($email,FILTER_VALIDATE_EMAIL)) { throw new InvalidArgumentException('Enter a valid email address.'); }
            query('UPDATE tbladmin SET AdminName = ?, MobileNumber = ?, Email = ? WHERE ID = ?', [$name,$phone,$email,$_SESSION['aid']]);
            flash('Profile updated.'); redirect('profile.php');
        } elseif ($page === 'change-password.php') {
            $password = password_input('newpassword'); validate_password($password);
            if ($password !== password_input('confirmpassword')) { throw new InvalidArgumentException('The new passwords do not match.'); }
            if (!password_matches(password_input('currentpassword'),$profile['Password'])) { throw new InvalidArgumentException('Your current password is incorrect.'); }
            query('UPDATE tbladmin SET Password = ? WHERE ID = ?', [password_hash($password,PASSWORD_DEFAULT),$_SESSION['aid']]);
            session_regenerate_id(true); unset($_SESSION['csrf']);
            flash('Password changed.'); redirect('profile.php');
        }
    } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
}
require __DIR__ . '/header.php';
?>
<div class="<?= $admin ? '' : 'form-narrow' ?>"><div class="page-heading"><div><div class="eyebrow"><?= $admin ? 'ACCOUNT SETTINGS' : 'OPERATIONS WORKSPACE' ?></div><h1><?= e($title) ?></h1><p><?= $page === 'login.php' ? 'Sign in to coordinate requests, collection and reports.' : 'Manage access to your workspace.' ?></p></div></div>
<?php form_error($error); ?><div class="panel">
<?php if ($page === 'password-recovery.php'): ?>
<h2>Contact your system administrator</h2><p>An administrator with server access can reset your account using the documented command-line tool. Password recovery requires server access to protect your account.</p><a class="btn btn-primary" href="login.php">Return to sign in</a>
<?php else: ?><form method="post"><?= csrf() ?>
<?php if ($page === 'login.php') { field('username','Username','text',input('username'),true,20); field('inputpwd','Password','password','',true,72); }
elseif ($page === 'profile.php') { field('adminname','Name','text',$_POST['adminname'] ?? $profile['AdminName']); field('mobilenumber','Mobile number','tel',$_POST['mobilenumber'] ?? $profile['MobileNumber'],true,15); field('email','Email','email',$_POST['email'] ?? $profile['Email']); }
elseif ($page === 'change-password.php') { field('currentpassword','Current password','password','',true,72); field('newpassword','New password (12–72 bytes)','password','',true,72); field('confirmpassword','Confirm new password','password','',true,72); }
elseif ($page === 'logout.php') { echo '<p>End your current session?</p>'; }
?><button class="btn btn-primary"><?= $page === 'login.php' ? 'Sign in' : ($page === 'logout.php' ? 'Sign out' : 'Save changes') ?></button></form>
<?php if ($page === 'login.php'): ?><p class="helper mt-4 mb-0"><a href="password-recovery.php">Need help accessing your account?</a></p><?php endif; endif; ?>
</div></div><?php require __DIR__ . '/footer.php'; ?>
