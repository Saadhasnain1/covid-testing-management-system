<?php
require __DIR__.'/includes/bootstrap.php';
$order=input('oid',$_GET);
$isAdmin=!empty($_SESSION['aid']);
if($isAdmin) { require_admin(); }
$record=($isAdmin || authorized_order($order)) ? one('SELECT FinalReport,ReportStatus FROM tbltestrecord WHERE OrderNumber=?',[$order]) : null;
$name=$record['FinalReport'] ?? '';
$path=storage_path().'/'.$name;
if (!$record || (!$isAdmin && $record['ReportStatus']!=='Delivered') || !preg_match('/^[a-f0-9]{24,64}\\.pdf$/D',$name) || !is_file($path)) {
    http_response_code(404); $title='Report unavailable'; $admin=false;
    require __DIR__.'/includes/header.php';
    echo '<div class="panel"><h1>Report unavailable</h1><p>Verify your patient details and check that your report has been delivered. Contact the administrator if a delivered file is missing.</p><a class="btn btn-primary" href="patient-search-report.php">Track your test</a></div>';
    require __DIR__.'/includes/footer.php'; exit;
}
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="test-report.pdf"');
header('Content-Length: '.filesize($path));
readfile($path);
