<?php
require_once __DIR__.'/bootstrap.php';
$admin=basename($_SERVER['SCRIPT_NAME'])==='test-details.php';
if ($admin) { require_admin(); }
$tid=filter_var($_GET['tid'] ?? 0,FILTER_VALIDATE_INT) ?: 0;
$order=input('oid',$_GET);
$record=null; $error=null; $title='Test details';
if ($admin && $tid>0) {
    $record=one('SELECT t.*,p.FullName,p.DateOfBirth,p.FullAddress,p.State,p.GovtIssuedId,p.GovtIssuedIdNo FROM tbltestrecord t JOIN tblpatients p ON p.MobileNumber=t.PatientMobileNumber WHERE t.id=?',[$tid]);
} elseif (!$admin && authorized_order($order)) {
    $record=one('SELECT t.*,p.FullName FROM tbltestrecord t JOIN tblpatients p ON p.MobileNumber=t.PatientMobileNumber WHERE t.OrderNumber=?',[$order]);
} elseif (!$admin) { flash('Verify your patient details to view this request.'); redirect('patient-search-report.php'); }
if (!$record) { http_response_code(404); $error='The requested test record was not found.'; }
if ($record && $admin && $_SERVER['REQUEST_METHOD']==='POST') {
    $newFile=null;
    try {
        check_csrf();
        transaction(function () use ($record,&$newFile): void {
            $locked=one('SELECT * FROM tbltestrecord WHERE id=? FOR UPDATE',[$record['id']]);
            if (!$locked) { throw new InvalidArgumentException('This test record no longer exists.'); }
            $current=$locked['ReportStatus'] ?: 'New';
            $action=input('action'); $remark='';
            if ($action==='assign') {
                if ($current!=='New') { throw new InvalidArgumentException('This request has already been assigned. Refresh to see its current state.'); }
                $employee=one('SELECT * FROM tblphlebotomist WHERE EmpID=? FOR UPDATE',[text_value('assignto','Phlebotomist',100)]);
                if (!$employee) { throw new InvalidArgumentException('Select an existing phlebotomist.'); }
                query("UPDATE tbltestrecord SET AssignedtoEmpId=?,AssigntoName=?,AssignedTime=?,ReportStatus='Assigned' WHERE id=?",[$employee['EmpID'],$employee['FullName'],date('Y-m-d H:i:s'),$locked['id']]);
                $next='Assigned'; $remark='Assigned to '.$employee['FullName'];
            } elseif ($action==='progress') {
                $next=input('status'); $remark=text_value('remark','Progress note',255);
                $position=array_search($current,STATUSES,true);
                if ($position===false || $position===0 || (STATUSES[$position+1] ?? null)!==$next) { throw new InvalidArgumentException('Progress must move to the next stage. Refresh to see the latest status.'); }
                if ($next==='Delivered' && (!$locked['FinalReport'] || !$locked['TestResult'])) { throw new InvalidArgumentException('Save a test result and PDF report before marking delivery.'); }
                query('UPDATE tbltestrecord SET ReportStatus=? WHERE id=?',[$next,$locked['id']]);
            } elseif ($action==='report') {
                if (!in_array($current,['Sent to Lab','Delivered'],true)) { throw new InvalidArgumentException('Reports can be recorded after the sample reaches the laboratory.'); }
                $result=input('testresult');
                if (!in_array($result,['Positive','Negative','Inconclusive'],true)) { throw new InvalidArgumentException('Select a valid test result.'); }
                $remark=text_value('remark','Report note',255);
                $file=$_FILES['report'] ?? null;
                if (!$file || !is_array($file) || !is_string($file['tmp_name'] ?? null) || !is_string($file['name'] ?? null)) { throw new InvalidArgumentException('Select a PDF report.'); }
                $uploadError=$file['error'] ?? UPLOAD_ERR_NO_FILE;
                if ($uploadError!==UPLOAD_ERR_NO_FILE) {
                    if ($uploadError!==UPLOAD_ERR_OK || ($file['size'] ?? 0)<1 || $file['size']>5*1024*1024 || !is_uploaded_file($file['tmp_name'])) { throw new InvalidArgumentException('Upload a PDF of at most 5 MB. Check PHP upload limits if the file is rejected.'); }
                    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    $handle=fopen($file['tmp_name'],'rb'); $signature=$handle ? fread($handle,5) : ''; if($handle) { fclose($handle); }
                    if (strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='pdf' || $mime!=='application/pdf' || $signature!=='%PDF-') { throw new InvalidArgumentException('Only valid PDF files are accepted.'); }
                    $directory=storage_path();
                    if (!is_dir($directory) && !mkdir($directory,0700,true) && !is_dir($directory)) { throw new RuntimeException('Unable to create report storage.'); }
                    $newFile=bin2hex(random_bytes(24)).'.pdf';
                    if (!move_uploaded_file($file['tmp_name'],$directory.'/'.$newFile)) { throw new RuntimeException('Unable to store uploaded report.'); }
                } elseif (!$locked['FinalReport']) { throw new InvalidArgumentException('Attach a PDF for the first report.'); }
                query('UPDATE tbltestrecord SET TestResult=?,FinalReport=?,ReportUploadTime=? WHERE id=?',[$result,$newFile ?? $locked['FinalReport'],date('Y-m-d H:i:s'),$locked['id']]);
                $next=$current;
                $remark='Report saved ('.$result.'): '.$remark;
            } else { throw new InvalidArgumentException('Choose a valid action.'); }
            // Order number is derived from the locked record, never a second URL parameter.
            query('INSERT INTO tblreporttracking (OrderNumber,Status,Remark,RemarkBy) VALUES (?,?,?,?)',[$locked['OrderNumber'],$next,$remark,$_SESSION['aid']]);
        });
        flash('Request updated.'); redirect('test-details.php?tid='.rawurlencode($record['id']));
    } catch (InvalidArgumentException $exception) {
        if ($newFile) { unlink(storage_path().'/'.$newFile); } $error=$exception->getMessage();
    } catch (Throwable $exception) { if ($newFile) { unlink(storage_path().'/'.$newFile); } throw $exception; }
}
$history=$record ? rows('SELECT * FROM tblreporttracking WHERE OrderNumber=? ORDER BY id',[$record['OrderNumber']]) : [];
$employees=$record && $admin && in_array($record['ReportStatus'],[null,'','New'],true) ? rows('SELECT EmpID,FullName FROM tblphlebotomist ORDER BY FullName') : [];
require __DIR__.'/header.php';
?>
<div class="page-heading"><div><div class="eyebrow"><?= $admin ? 'REQUEST MANAGEMENT' : 'YOUR TESTING JOURNEY' ?></div><h1><?= $record ? 'Request #'.e($record['OrderNumber']) : 'Test details' ?></h1><p><?= $record ? e($record['FullName']) : 'Check the requested record and try again.' ?></p></div><div class="no-print"><a class="btn btn-light" href="<?= $admin ? 'all-test.php' : 'patient-search-report.php' ?>">Back to <?= $admin ? 'requests' : 'tracking' ?></a> <button class="btn btn-light" type="button" data-print>Print</button></div></div>
<?php form_error($error); if($record): $current=$record['ReportStatus'] ?: 'New'; ?>
<div class="row"><div class="col-lg-7"><div class="panel"><div class="page-heading"><h2>Request information</h2><?= status_badge($current) ?></div><dl class="details">
<div><dt>Test type</dt><dd><?= e($record['TestType']) ?></dd></div><div><dt>Preferred collection time</dt><dd><?= e(str_replace('T',' ',$record['TestTimeSlot'])) ?></dd></div>
<div><dt>Submitted</dt><dd><?= e($record['RegistrationDate']) ?></dd></div><div><dt>Assigned phlebotomist</dt><dd><?= e($record['AssigntoName'] ?: 'Awaiting assignment') ?></dd></div>
<?php if($admin): ?><div><dt>Patient mobile</dt><dd><?= e($record['PatientMobileNumber']) ?></dd></div><div><dt>Date of birth</dt><dd><?= e($record['DateOfBirth']) ?></dd></div><div><dt>Collection address</dt><dd><?= e($record['FullAddress']) ?></dd></div><div><dt>State / region</dt><dd><?= e($record['State']) ?></dd></div><?php endif; ?>
</dl></div>
<div class="panel"><h2>Test report</h2><?php if($record['FinalReport'] && ($admin || $current==='Delivered')): ?><span class="status status-ready report-readiness">Report ready</span><p><strong>Result:</strong> <?= e($record['TestResult'] ?: 'See PDF report') ?></p><p class="helper">Last saved <?= e($record['ReportUploadTime']) ?></p><a class="btn btn-primary" href="download-report.php?oid=<?= e($record['OrderNumber']) ?>"><?= ui_icon('report') ?>Download PDF report</a><?php else: ?><p class="mb-0">Your report will be available here after it has been delivered.</p><?php endif; ?></div>
<?php if($admin): ?><div class="panel no-print"><h2>Next action</h2>
<?php if($current==='New'): ?><form method="post"><?= csrf() ?><input type="hidden" name="action" value="assign">
<?php select_field('assignto','Assign phlebotomist',array_column($employees,'FullName','EmpID'),input('assignto')); ?>
<?php if(!$employees): ?><p>Add a phlebotomist before assigning this request. <a href="add-phlebotomist.php">Add team member</a></p><?php else: ?><button class="btn btn-primary">Assign request</button><?php endif; ?></form>
<?php elseif($current!=='Delivered'): $position=array_search($current,STATUSES,true); $next=$position!==false ? (STATUSES[$position+1] ?? null) : null; if($next): ?>
<form method="post"><?= csrf() ?><input type="hidden" name="action" value="progress"><input type="hidden" name="status" value="<?= e($next) ?>"><p>Move this request to <strong><?= e($next) ?></strong>.</p><?php field('remark','Progress note','text',input('remark'),true,255); ?><button class="btn btn-primary">Mark <?= e($next) ?></button></form>
<?php else: ?><p>This record has an unrecognized status. Ask your administrator to correct the migrated data.</p><?php endif; else: ?><p class="mb-0">This request is complete. Report corrections can still be recorded below.</p><?php endif; ?></div>
<?php if(in_array($current,['Sent to Lab','Delivered'],true)): ?><div class="panel no-print"><h2><?= $record['FinalReport'] ? 'Update report' : 'Record laboratory result' ?></h2><form method="post" enctype="multipart/form-data"><?= csrf() ?><input type="hidden" name="action" value="report">
<?php select_field('testresult','Result',['Positive'=>'Positive','Negative'=>'Negative','Inconclusive'=>'Inconclusive'],input('testresult') ?: ($record['TestResult'] ?? '')); field('remark','Report note','text',input('remark'),true,255); ?>
<div class="form-group"><label for="report">PDF report <?= $record['FinalReport'] ? '(optional replacement)' : '*' ?></label><input class="form-control-file" id="report" name="report" type="file" accept=".pdf,application/pdf" <?= $record['FinalReport'] ? '' : 'required' ?>><p class="helper mt-2">PDF only, maximum 5 MB. Saving a report preserves the current workflow stage.</p></div><button class="btn btn-primary">Save report</button></form></div><?php endif; endif; ?></div>
<div class="col-lg-5"><div class="panel"><h2>Progress timeline</h2><ol class="timeline"><li><strong>Request submitted</strong><time><?= e($record['RegistrationDate']) ?></time></li>
<?php foreach($history as $event): ?><li><strong><?= e($event['Status']) ?></strong><time><?= e($event['PostingTime']) ?></time><p><?= e($admin ? $event['Remark'] : 'Status updated by the testing team.') ?></p></li><?php endforeach; ?>
</ol></div></div></div><?php endif; require __DIR__.'/footer.php'; ?>
