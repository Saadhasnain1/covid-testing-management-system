<?php
require_once __DIR__ . '/bootstrap.php';
$title = 'Track your test'; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        if ((int) ($_SESSION['track_locked_until'] ?? 0) > time()) { throw new InvalidArgumentException('Too many attempts. Try again in five minutes.'); }
        $order = text_value('ordernumber','Reference number',14);
        $phone = mobile(); $dob = input('dob');
        if (!preg_match('/^[0-9]{9,14}$/D',$order) || !valid_date($dob)) { throw new InvalidArgumentException('Enter a valid reference and date of birth.'); }
        $record = one('SELECT t.id FROM tbltestrecord t JOIN tblpatients p ON p.MobileNumber=t.PatientMobileNumber WHERE t.OrderNumber=? AND p.MobileNumber=? AND p.DateOfBirth=?',[$order,$phone,$dob]);
        if (!$record) {
            $_SESSION['track_failures'] = (int) ($_SESSION['track_failures'] ?? 0)+1;
            if ($_SESSION['track_failures'] >= 5) { $_SESSION['track_locked_until']=time()+300; $_SESSION['track_failures']=0; }
            throw new InvalidArgumentException('No matching request was found. Check all three details.');
        }
        $_SESSION['orders'][$order]=time(); unset($_SESSION['track_failures']);
        redirect('patient-report-details.php?oid='.rawurlencode($order));
    } catch (InvalidArgumentException $exception) { $error=$exception->getMessage(); }
}
require __DIR__ . '/header.php';
?><div class="form-narrow"><div class="page-heading"><div><div class="eyebrow">PATIENT SERVICES</div><h1>Track your test</h1><p>View your progress and access your delivered report.</p></div></div><?php form_error($error); ?>
<div class="panel"><form method="post"><?= csrf() ?><?php field('ordernumber','Order / reference number','text',input('ordernumber'),true,14); field('mobilenumber','Registered mobile number','tel',input('mobilenumber'),true,15); field('dob','Patient date of birth','date',input('dob')); ?><button class="btn btn-primary">View test status</button></form><p class="helper mt-4 mb-0">Your reference was provided when you submitted your request. If you have lost it, contact the testing administrator.</p></div></div>
<?php require __DIR__ . '/footer.php'; ?>
