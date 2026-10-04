<?php
require_once __DIR__ . '/bootstrap.php'; require_admin(); $admin=true;
$page=basename($_SERVER['SCRIPT_NAME']);
$editing=$page==='edit-phlebotomist.php'; $listing=$page==='manage-phlebotomist.php';
$id=filter_var($_GET['pid'] ?? 0,FILTER_VALIDATE_INT) ?: 0;
$staff=$editing ? one('SELECT * FROM tblphlebotomist WHERE id=?',[$id]) : null;
$title=$listing ? 'Phlebotomists' : ($editing ? 'Edit phlebotomist' : 'Add phlebotomist');
$error=null;
if ($editing && !$staff) { http_response_code(404); $error='The phlebotomist record was not found.'; }
if ($_SERVER['REQUEST_METHOD']==='POST' && (!$editing || $staff)) {
    try {
        check_csrf();
        if ($listing) {
            $deleteId=filter_var(input('pid'),FILTER_VALIDATE_INT);
            if (!$deleteId) { throw new InvalidArgumentException('Select a valid staff record.'); }
            transaction(function () use ($deleteId): void {
                $employee=one('SELECT * FROM tblphlebotomist WHERE id=? FOR UPDATE',[$deleteId]);
                if (!$employee) { throw new InvalidArgumentException('The staff record no longer exists.'); }
                if (one('SELECT id FROM tbltestrecord WHERE AssignedtoEmpId=? LIMIT 1',[$employee['EmpID']])) { throw new InvalidArgumentException('This phlebotomist has assigned test records and cannot be deleted.'); }
                query('DELETE FROM tblphlebotomist WHERE id=?',[$deleteId]);
            });
            flash('Unassigned phlebotomist deleted.'); redirect('manage-phlebotomist.php');
        }
        $name=text_value('fullname','Full name'); $phone=mobile();
        if ($editing) {
            query('UPDATE tblphlebotomist SET FullName=?, MobileNumber=? WHERE id=?',[$name,$phone,$id]);
            // Keep assigned names as historical snapshots; employee IDs are immutable.
            flash('Phlebotomist updated.');
        } else {
            $emp=text_value('empid','Employee ID',100);
            if (!preg_match('/^[A-Za-z0-9_-]+$/D',$emp)) { throw new InvalidArgumentException('Employee ID can contain letters, numbers, hyphens and underscores.'); }
            query('INSERT INTO tblphlebotomist (EmpID,FullName,MobileNumber) VALUES (?,?,?)',[$emp,$name,$phone]);
            flash('Phlebotomist added.');
        }
        redirect('manage-phlebotomist.php');
    } catch (InvalidArgumentException $exception) { $error=$exception->getMessage(); }
      catch (mysqli_sql_exception $exception) {
        if ($exception->getCode()!==1062 && $exception->getCode()!==1451) { throw $exception; }
        $error=$exception->getCode()===1062 ? 'That employee ID already exists.' : 'Assigned staff cannot be deleted.';
    }
}
$search=input('q',$_GET);
$pageNumber=max(1,(int)($_GET['page'] ?? 1)); $perPage=10;
$count=$listing ? (int)one('SELECT COUNT(*) n FROM tblphlebotomist WHERE FullName LIKE ? OR EmpID LIKE ?', ['%'.$search.'%','%'.$search.'%'])['n'] : 0;
$pageNumber=min($pageNumber,max(1,(int)ceil($count/$perPage)));
$records=$listing ? rows('SELECT p.*, (SELECT COUNT(*) FROM tbltestrecord t WHERE t.AssignedtoEmpId=p.EmpID) AS assignments FROM tblphlebotomist p WHERE FullName LIKE ? OR EmpID LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?', ['%'.$search.'%','%'.$search.'%',$perPage,($pageNumber-1)*$perPage]) : [];
require __DIR__.'/header.php';
?><div class="page-heading"><div><div class="eyebrow">COLLECTION TEAM</div><h1><?= e($title) ?></h1><p>Manage the people coordinating sample collection.</p></div><a class="btn btn-primary" href="<?= $listing ? 'add-phlebotomist.php' : 'manage-phlebotomist.php' ?>"><?= $listing ? 'Add phlebotomist' : 'Back to team' ?></a></div><?php form_error($error); ?>
<?php if ($listing): ?>
<form method="get" class="toolbar"><?php field('q','Search name or employee ID','search',$search,false); ?><button class="btn btn-primary">Search</button></form>
<div class="panel table-panel"><p class="table-scroll-hint">Scroll horizontally to view team details and actions.</p><div class="table-responsive"><table class="table" aria-label="Collection team"><thead><tr><th>Employee ID</th><th>Name</th><th>Mobile</th><th>Test assignments</th><th>Actions</th></tr></thead><tbody>
<?php foreach($records as $record): ?><tr><td><?= e($record['EmpID']) ?></td><td><strong><?= e($record['FullName']) ?></strong></td><td><?= e($record['MobileNumber']) ?></td><td><?= e($record['assignments']) ?></td><td><a class="btn btn-light btn-sm" href="edit-phlebotomist.php?pid=<?= e($record['id']) ?>">Edit</a>
<?php if (!$record['assignments']): ?><form class="inline-form" method="post" data-confirm="Delete this unassigned phlebotomist?"><?= csrf() ?><input type="hidden" name="pid" value="<?= e($record['id']) ?>"><button class="btn btn-outline-danger btn-sm">Delete</button></form><?php else: ?><span class="helper">Retained for test history</span><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$records): ?><tr><td colspan="5" class="empty"><strong>No phlebotomists found.</strong><span class="empty-description">Add a team member to start assigning tests.</span></td></tr><?php endif; ?>
</tbody></table></div></div>
<?php pagination($count,$pageNumber,$perPage,['q'=>$search]); ?>
<?php elseif (!$editing || $staff): ?><div class="panel form-narrow"><form method="post"><?= csrf() ?>
<?php if ($editing): ?><p><strong>Employee ID:</strong> <?= e($staff['EmpID']) ?> <span class="helper">(fixed to preserve assignment history)</span></p><?php else: field('empid','Employee ID','text',input('empid'),true,100); endif;
field('fullname','Full name','text',$_POST['fullname'] ?? $staff['FullName'] ?? '');
field('mobilenumber','Mobile number','tel',$_POST['mobilenumber'] ?? $staff['MobileNumber'] ?? '',true,15); ?>
<button class="btn btn-primary">Save phlebotomist</button></form></div><?php endif; ?>
<?php require __DIR__.'/footer.php'; ?>
