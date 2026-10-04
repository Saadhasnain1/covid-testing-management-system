<?php
require_once __DIR__.'/bootstrap.php'; require_admin(); $admin=true;
$dates=basename($_SERVER['SCRIPT_NAME'])==='bwdates-report-ds.php';
$title=$dates ? 'Date reports' : 'Search records'; require __DIR__.'/header.php';
?><div class="page-heading"><div><div class="eyebrow">RECORDS</div><h1><?= e($title) ?></h1><p><?= $dates ? 'Review requests registered within a date range.' : 'Find a patient or request by name, mobile number or reference.' ?></p></div></div>
<div class="panel form-narrow"><form method="get" action="<?= $dates ? 'bwdates-report-result.php' : 'search-report-result.php' ?>">
<?php if($dates) { field('fromdate','From date','date'); field('todate','Through date','date'); } else { field('q','Name, mobile or reference','search'); } ?><button class="btn btn-primary">View results</button></form></div>
<?php require __DIR__.'/footer.php'; ?>
