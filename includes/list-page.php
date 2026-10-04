<?php
require_once __DIR__.'/bootstrap.php'; require_admin(); $admin=true;
$route=basename($_SERVER['SCRIPT_NAME']);
$filters=['new-test.php'=>'New','assigned-test.php'=>'Assigned','ontheway-samplecollection-test.php'=>'On the Way for Collection','sample-collected-test.php'=>'Sample Collected','sample-collected-tests.php'=>'Sample Collected','samplesent-lab-test.php'=>'Sent to Lab','reportdelivered-test.php'=>'Delivered'];
$status=$filters[$route] ?? input('status',$_GET);
$search=input('q',$_GET); $from=input('fromdate',$_GET); $to=input('todate',$_GET);
$title=$status ? $status.' requests' : 'Test requests'; $error=null;
$conditions=[]; $params=[];
if ($status) {
    if (!in_array($status,STATUSES,true)) { $error='Select a valid status.'; $conditions[]='1=0'; }
    else { $conditions[]="COALESCE(NULLIF(t.ReportStatus,''),'New')=?"; $params[]=$status; }
}
if ($search!=='') { $conditions[]='(p.FullName LIKE ? OR p.MobileNumber LIKE ? OR t.OrderNumber LIKE ?)'; array_push($params,'%'.$search.'%','%'.$search.'%','%'.$search.'%'); }
if ($from!=='' || $to!=='') {
    if (!valid_date($from) || !valid_date($to) || $from>$to) { $error='Enter a valid date range.'; $conditions[]='1=0'; }
    else { $conditions[]='t.RegistrationDate >= ? AND t.RegistrationDate < DATE_ADD(?, INTERVAL 1 DAY)'; array_push($params,$from,$to); }
}
$where=$conditions ? ' WHERE '.implode(' AND ',$conditions) : '';
$base=' FROM tbltestrecord t JOIN tblpatients p ON p.MobileNumber=t.PatientMobileNumber';
$count=(int)one('SELECT COUNT(*) n'.$base.$where,$params)['n'];
$pageNumber=max(1,min((int)($_GET['page'] ?? 1),max(1,(int)ceil($count/10))));
$records=rows('SELECT t.*,p.FullName,p.MobileNumber'.$base.$where.' ORDER BY t.id DESC LIMIT ? OFFSET ?', [...$params,10,($pageNumber-1)*10]);
require __DIR__.'/header.php';
?><div class="page-heading"><div><div class="eyebrow">TESTING OPERATIONS</div><h1><?= e($title) ?></h1><p>Find requests and coordinate the next step in each testing journey.</p></div><a class="btn btn-light" href="dashboard.php">Overview</a></div><?php form_error($error); ?>
<div class="panel"><form method="get" class="toolbar"><?php field('q','Search name, mobile or reference','search',$search,false); ?>
<?php if (!isset($filters[$route])): ?><div class="form-group"><label for="status">Status</label><select id="status" name="status" class="form-control"><option value="">All statuses</option><?php foreach(STATUSES as $choice): ?><option <?= $status===$choice ? 'selected' : '' ?> value="<?= e($choice) ?>"><?= e($choice) ?></option><?php endforeach; ?></select></div><?php endif; ?>
<?php field('fromdate','Registered from','date',$from,false); field('todate','Registered through','date',$to,false); ?><button class="btn btn-primary">Filter</button><a class="btn btn-light" href="<?= e($route) ?>">Reset</a></form></div>
<div class="panel table-panel"><p class="table-scroll-hint">Scroll horizontally to view all request details and actions.</p><div class="table-responsive"><table class="table" aria-label="Test requests"><thead><tr><th>Reference</th><th>Patient</th><th>Test / preferred time</th><th>Status</th><th>Assigned to</th><th>Action</th></tr></thead><tbody>
<?php foreach($records as $record): ?><tr><td><strong><?= e($record['OrderNumber']) ?></strong><div class="helper"><?= e($record['RegistrationDate']) ?></div></td><td><?= e($record['FullName']) ?><div class="helper"><?= e($record['MobileNumber']) ?></div></td><td><?= e($record['TestType']) ?><div class="helper"><?= e(str_replace('T',' ',$record['TestTimeSlot'])) ?></div></td><td><?= status_badge($record['ReportStatus']) ?></td><td><?= e($record['AssigntoName'] ?: 'Awaiting assignment') ?></td><td><a class="btn btn-light btn-sm" href="test-details.php?tid=<?= e($record['id']) ?>">View request</a></td></tr><?php endforeach; ?>
<?php if (!$records): ?><tr><td colspan="6" class="empty"><strong>No requests match these filters.</strong><span class="empty-description">Try another search or adjust your date range.</span></td></tr><?php endif; ?></tbody></table></div></div>
<?php pagination($count,$pageNumber,10,['q'=>$search,'status'=>$status,'fromdate'=>$from,'todate'=>$to]); require __DIR__.'/footer.php'; ?>
