<?php
require_once __DIR__.'/bootstrap.php'; $title='Live testing updates';
$summary=rows("SELECT COALESCE(NULLIF(ReportStatus,''),'New') status,COUNT(*) n FROM tbltestrecord GROUP BY status");
$byStatus=array_column($summary,'n','status');
require __DIR__.'/header.php';
?><div class="page-heading"><div><div class="eyebrow">SERVICE OVERVIEW</div><h1>Live testing updates</h1><p>Aggregate request counts across the testing workflow.</p></div><a class="btn btn-primary" href="patient-search-report.php">Track your test</a></div>
<div class="stats"><?php foreach(STATUSES as $status): ?><div class="panel stat"><span class="stat-label"><?= e($status) ?></span><span class="stat-number"><?= e($byStatus[$status] ?? 0) ?></span></div><?php endforeach; ?></div>
<div class="panel"><h2>Looking for your own request?</h2><p>Use your reference, mobile number and date of birth to view your status and report.</p><a href="patient-search-report.php">Open test tracking &rarr;</a></div>
<?php require __DIR__.'/footer.php'; ?>
