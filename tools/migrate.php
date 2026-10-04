<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/includes/bootstrap.php';
// DDL is not transactional in MySQL. Default mode audits only; back up before --apply.
$apply=in_array('--apply',$argv,true);
$issues=[];
$checks=[
 'Duplicate patient mobile numbers'=>'SELECT MobileNumber FROM tblpatients GROUP BY MobileNumber HAVING COUNT(*)>1',
 'Duplicate employee IDs'=>'SELECT EmpID FROM tblphlebotomist GROUP BY EmpID HAVING COUNT(*)>1',
 'Duplicate test references'=>'SELECT OrderNumber FROM tbltestrecord GROUP BY OrderNumber HAVING COUNT(*)>1',
 'Duplicate administrator usernames'=>'SELECT AdminuserName FROM tbladmin GROUP BY AdminuserName HAVING COUNT(*)>1',
 'Tests without patients'=>'SELECT t.id FROM tbltestrecord t LEFT JOIN tblpatients p ON p.MobileNumber=t.PatientMobileNumber WHERE p.id IS NULL',
 'Tests assigned to missing staff'=>'SELECT t.id FROM tbltestrecord t LEFT JOIN tblphlebotomist p ON p.EmpID=t.AssignedtoEmpId WHERE t.AssignedtoEmpId IS NOT NULL AND t.AssignedtoEmpId<>"" AND p.id IS NULL',
 'History without tests'=>'SELECT r.id FROM tblreporttracking r LEFT JOIN tbltestrecord t ON t.OrderNumber=r.OrderNumber WHERE t.id IS NULL',
 'History without administrator'=>'SELECT r.id FROM tblreporttracking r LEFT JOIN tbladmin a ON a.ID=r.RemarkBy WHERE r.RemarkBy IS NOT NULL AND a.ID IS NULL',
 'Invalid statuses'=>"SELECT id FROM tbltestrecord WHERE ReportStatus IS NOT NULL AND ReportStatus<>'' AND ReportStatus NOT IN ('New','Assigned','On the Way for Collection','Sample Collected','Sent to Lab','Delivered')",
 'Incomplete patients'=>"SELECT id FROM tblpatients WHERE FullName IS NULL OR MobileNumber IS NULL OR DateOfBirth IS NULL OR CAST(DateOfBirth AS CHAR)='0000-00-00' OR GovtIssuedId IS NULL OR GovtIssuedIdNo IS NULL OR FullAddress IS NULL OR State IS NULL",
 'Incomplete staff'=>'SELECT id FROM tblphlebotomist WHERE EmpID IS NULL OR FullName IS NULL OR MobileNumber IS NULL',
 'Incomplete admins'=>'SELECT ID FROM tbladmin WHERE AdminName IS NULL OR AdminuserName IS NULL OR MobileNumber IS NULL OR Email IS NULL OR Password IS NULL',
 'Incomplete tests'=>'SELECT id FROM tbltestrecord WHERE OrderNumber IS NULL OR PatientMobileNumber IS NULL OR TestType IS NULL OR TestTimeSlot IS NULL',
 'Incomplete history'=>'SELECT id FROM tblreporttracking WHERE OrderNumber IS NULL OR Remark IS NULL OR Status IS NULL',
 'Oversized employee references'=>'SELECT id FROM tbltestrecord WHERE CHAR_LENGTH(AssignedtoEmpId)>100',
];
foreach($checks as $label=>$sql) { $count=count(rows($sql)); if($count) { $issues[]=$label.': '.$count; } }
foreach(['tbladmin'=>'MobileNumber','tblpatients'=>'MobileNumber','tblphlebotomist'=>'MobileNumber'] as $table=>$column) {
    $bad=rows("SELECT ".$column." FROM ".$table." WHERE CAST(".$column." AS CHAR) NOT REGEXP '^[0-9]{10,15}$'");
    if($bad) { $issues[]='Invalid phone numbers in '.$table.': '.count($bad); }
}
$legacy=count(rows("SELECT ID FROM tbladmin WHERE Password REGEXP '^[a-fA-F0-9]{32}$'"));
echo "Legacy MD5 accounts: ".$legacy." (upgraded on successful sign in).\n";
if($issues) { echo implode("\n",$issues)."\nResolve these issues manually in a backup copy; no records were changed.\n"; exit(1); }
if(!$apply) { echo "Preflight passed. Back up the database and report files, then run with --apply.\n"; exit; }
$definitions=[
 'tbladmin'=>"MODIFY AdminName VARCHAR(120) NOT NULL, MODIFY AdminuserName VARCHAR(20) NOT NULL, MODIFY MobileNumber VARCHAR(15) NOT NULL, MODIFY Email VARCHAR(120) NOT NULL, MODIFY Password VARCHAR(255) NOT NULL",
 'tblpatients'=>"MODIFY FullName VARCHAR(120) NOT NULL, MODIFY MobileNumber VARCHAR(15) NOT NULL, MODIFY DateOfBirth DATE NOT NULL, MODIFY GovtIssuedId VARCHAR(150) NOT NULL, MODIFY GovtIssuedIdNo VARCHAR(150) NOT NULL, MODIFY FullAddress VARCHAR(255) NOT NULL, MODIFY State VARCHAR(200) NOT NULL",
 'tblphlebotomist'=>"MODIFY EmpID VARCHAR(100) NOT NULL, MODIFY FullName VARCHAR(120) NOT NULL, MODIFY MobileNumber VARCHAR(15) NOT NULL",
 'tbltestrecord'=>"MODIFY OrderNumber BIGINT NOT NULL, MODIFY PatientMobileNumber VARCHAR(15) NOT NULL, MODIFY TestType VARCHAR(100) NOT NULL, MODIFY TestTimeSlot VARCHAR(120) NOT NULL, MODIFY ReportStatus VARCHAR(100) NOT NULL DEFAULT 'New', MODIFY AssignedtoEmpId VARCHAR(100) DEFAULT NULL",
 'tblreporttracking'=>"MODIFY OrderNumber BIGINT NOT NULL, MODIFY Remark VARCHAR(255) NOT NULL, MODIFY Status VARCHAR(120) NOT NULL"
];
// Drop this application's foreign keys before type changes, allowing safe reruns after partial DDL.
$foreignKeys=rows('SELECT TABLE_NAME,CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_TYPE="FOREIGN KEY"');
foreach($foreignKeys as $key) {
    if(!array_key_exists($key['TABLE_NAME'],$definitions)) { continue; }
    $table=str_replace('`','``',$key['TABLE_NAME']); $name=str_replace('`','``',$key['CONSTRAINT_NAME']);
    query('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$name.'`');
}
query("UPDATE tbltestrecord SET ReportStatus='New' WHERE ReportStatus IS NULL OR ReportStatus=''");
query("UPDATE tbltestrecord SET AssignedtoEmpId=NULL WHERE AssignedtoEmpId=''");
foreach($definitions as $table=>$definition) {
    query('ALTER TABLE '.$table.' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    query('ALTER TABLE '.$table.' '.$definition);
}
if(!one('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="tbltestrecord" AND COLUMN_NAME="TestResult"')) { query('ALTER TABLE tbltestrecord ADD TestResult VARCHAR(30) DEFAULT NULL'); }
$indexes=[
 ['tbladmin','uq_admin_username','AdminuserName',true],['tblpatients','uq_patient_mobile','MobileNumber',true],
 ['tblphlebotomist','uq_employee_id','EmpID',true],['tbltestrecord','uq_test_order','OrderNumber',true],
 ['tbltestrecord','idx_test_status','ReportStatus',false],['tbltestrecord','idx_test_registered','RegistrationDate',false]
];
foreach($indexes as [$table,$name,$column,$unique]) {
    if(!one('SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? AND SEQ_IN_INDEX=1 AND NON_UNIQUE=?',[$table,$column,$unique ? 0 : 1])) {
        query('ALTER TABLE '.$table.' ADD '.($unique ? 'UNIQUE ' : '').'INDEX '.$name.' ('.$column.')');
    }
}
foreach([
 'ALTER TABLE tbltestrecord ADD CONSTRAINT fk_test_patient FOREIGN KEY (PatientMobileNumber) REFERENCES tblpatients(MobileNumber)',
 'ALTER TABLE tbltestrecord ADD CONSTRAINT fk_test_employee FOREIGN KEY (AssignedtoEmpId) REFERENCES tblphlebotomist(EmpID)',
 'ALTER TABLE tblreporttracking ADD CONSTRAINT fk_tracking_order FOREIGN KEY (OrderNumber) REFERENCES tbltestrecord(OrderNumber)',
 'ALTER TABLE tblreporttracking ADD CONSTRAINT fk_tracking_admin FOREIGN KEY (RemarkBy) REFERENCES tbladmin(ID)'
] as $sql) { query($sql); }
// Preserve report references and validate each legacy file before copying into protected storage.
if(!is_dir(storage_path()) && !mkdir(storage_path(),0700,true) && !is_dir(storage_path())) { throw new RuntimeException('Unable to create storage.'); }
foreach(rows("SELECT FinalReport FROM tbltestrecord WHERE FinalReport IS NOT NULL AND FinalReport<>''") as $record) {
    $name=$record['FinalReport'];
    if(!preg_match('/^[a-f0-9]{24,64}\\.pdf$/D',$name)) { echo "Skipped unsafe legacy report reference.\n"; continue; }
    $target=storage_path().'/'.$name; $source=dirname(__DIR__).'/reportfiles/'.$name;
    if(is_file($target)) { continue; }
    if(!is_file($source) || (new finfo(FILEINFO_MIME_TYPE))->file($source)!=='application/pdf') { echo "Missing or invalid legacy PDF: ".$name."\n"; continue; }
    if(!copy($source,$target)) { throw new RuntimeException('Unable to copy legacy PDF.'); }
}
echo "Schema migration complete. Existing delivered PDFs remain accessible; record structured results manually where appropriate.\n";
