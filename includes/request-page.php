<?php
require_once __DIR__ . '/bootstrap.php';
$repeat = basename($_SERVER['SCRIPT_NAME']) === 'registered-user-testing.php';
$title = $repeat ? 'Request another test' : 'Request a test';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        $phone = mobile(); $dob = input('dob');
        if (!valid_date($dob) || $dob > date('Y-m-d') || $dob < '1900-01-01') { throw new InvalidArgumentException('Enter a valid date of birth.'); }
        $type = input('testtype');
        if (!in_array($type, ['Antigen','RT-PCR','CB-NAAT'], true)) { throw new InvalidArgumentException('Select a valid test type.'); }
        $slot = input('birthdaytime');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $slot);
        if (!$date || $date->format('Y-m-d\\TH:i') !== $slot || $date <= new DateTimeImmutable() || $date > new DateTimeImmutable('+90 days')) {
            throw new InvalidArgumentException('Choose a collection time in the next 90 days.');
        }
        $values = [];
        if (!$repeat) {
            if (!in_array(input('govtissuedid'), ['National ID','Passport','Driving License','Other'], true)) { throw new InvalidArgumentException('Select a valid government ID type.'); }
            $values = [text_value('fullname','Full name'), $phone, $dob, text_value('govtissuedid','ID type',150),
                text_value('govtidnumber','ID number',150), text_value('address','Address',255), text_value('state','State / region',200)];
        }
        $order = transaction(function () use ($repeat,$phone,$dob,$type,$slot,$values): string {
            $patient = one('SELECT id, DateOfBirth FROM tblpatients WHERE MobileNumber = ? FOR UPDATE', [$phone]);
            if ($repeat) {
                if (!$patient || $patient['DateOfBirth'] !== $dob) { throw new InvalidArgumentException('Patient details did not match. Check your mobile number and date of birth.'); }
            } else {
                if ($patient) { throw new InvalidArgumentException('This mobile number is registered. Use the returning-patient form.'); }
                query('INSERT INTO tblpatients (FullName,MobileNumber,DateOfBirth,GovtIssuedId,GovtIssuedIdNo,FullAddress,State) VALUES (?,?,?,?,?,?,?)', $values);
            }
            $order = (string) random_int(10000000000000,99999999999999);
            query("INSERT INTO tbltestrecord (OrderNumber,PatientMobileNumber,TestType,TestTimeSlot,ReportStatus) VALUES (?,?,?,?,'New')", [$order,$phone,$type,$slot]);
            return $order;
        });
        $_SESSION['orders'][$order] = time();
        flash('Request submitted. Your reference is ' . $order . '. Save it to track your test.');
        redirect('patient-report-details.php?oid=' . rawurlencode($order));
    } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
      catch (mysqli_sql_exception $exception) {
        if ($exception->getCode() !== 1062) { throw $exception; }
        $error = 'A record with these details already exists. Check your details and try again.';
    }
}
require __DIR__ . '/header.php';
?>
<div class="page-heading"><div><div class="eyebrow">PATIENT SERVICES</div><h1><?= e($title) ?></h1><p>Choose a test and preferred collection time. Fields marked * are required.</p></div><a class="btn btn-light" href="<?= $repeat ? 'new-user-testing.php' : 'registered-user-testing.php' ?>"><?= $repeat ? 'First time here?' : 'Already registered?' ?></a></div>
<?php form_error($error); ?>
<div class="row"><div class="col-lg-8"><div class="panel"><form method="post"><?= csrf() ?><h2><?= $repeat ? 'Verify your patient details' : 'Patient details' ?></h2><div class="form-grid">
<?php
if (!$repeat) { field('fullname','Full name','text',input('fullname')); }
field('mobilenumber','Mobile number (10–15 digits)','tel',input('mobilenumber'),true,15);
field('dob','Date of birth','date',input('dob'));
if (!$repeat) {
    select_field('govtissuedid','Government ID type',['National ID'=>'National ID','Passport'=>'Passport','Driving License'=>'Driving License','Other'=>'Other'],input('govtissuedid'));
    field('govtidnumber','Government ID number','text',input('govtidnumber'),true,150);
    field('state','State / region','text',input('state'),true,200);
    field('address','Collection address','text',input('address'),true,255);
}
?></div><h2 class="mt-4">Testing preferences</h2><div class="form-grid"><?php select_field('testtype','Test type',['Antigen'=>'Antigen','RT-PCR'=>'RT-PCR','CB-NAAT'=>'CB-NAAT'],input('testtype')); field('birthdaytime','Preferred collection time','datetime-local',input('birthdaytime')); ?></div><p class="helper">Times use <?= e(date_default_timezone_get()) ?>. Your preferred slot will be reviewed by the collection team.</p><button class="btn btn-primary">Submit test request</button></form></div></div>
<div class="col-lg-4"><div class="panel"><div class="eyebrow">WHAT HAPPENS NEXT</div><h2>Keep your reference.</h2><p>After submission, your unique reference connects you to status updates and your final report.</p><p>To return later, enter your reference, mobile number and date of birth on the tracking page.</p><a href="patient-search-report.php">Track an existing request &rarr;</a></div></div></div>
<?php require __DIR__ . '/footer.php'; ?>
