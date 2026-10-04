import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
if (!process.env.VMS_TEST_DB_PORT || process.env.VMS_TEST_DB_PORT === '3306') throw Error('An isolated database port is required.');
const mysql=process.env.MYSQL_BIN || 'C:/xampp/mysql/bin/mysql.exe';
const args=['--no-defaults','--host=127.0.0.1','--port='+process.env.VMS_TEST_DB_PORT,'--user=root'];
execFileSync(mysql,[...args,'-e', "INSERT INTO covidtmsdb.tbladmin(AdminName,AdminuserName,MobileNumber,Email,Password) VALUES ('Legacy fixture','legacyfixture','1234567899','legacy@example.invalid',MD5('legacy-fixture-password'));"]);
let cookie='';
async function req(path,options={}) {
 const response=await fetch((process.env.TEST_BASE_URL || 'http://127.0.0.1:8086')+'/'+path,{...options,redirect:'manual',headers:{...options.headers,...(cookie?{Cookie:cookie}:{})}});
 const c=response.headers.get('set-cookie');if(c)cookie=c.split(';')[0];
 return {status:response.status,body:await response.text(),headers:response.headers};
}
let response=await req('login.php');const before=cookie;const token=response.body.match(/name="csrf" value="([^"]+)"/)[1];
response=await req('login.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({csrf:token,username:'legacyfixture',inputpwd:'legacy-fixture-password'})});
assert.equal(response.status,303);assert.notEqual(cookie,before);assert.ok(response.headers.get('set-cookie').includes('HttpOnly'));assert.ok(response.headers.get('set-cookie').includes('SameSite=Lax'));
const length=execFileSync(mysql,[...args,'-N','-e',"SELECT CHAR_LENGTH(Password) FROM covidtmsdb.tbladmin WHERE AdminuserName='legacyfixture'"],{encoding:'utf8'}).trim();
assert.ok(Number(length)>32);console.log('PASS MD5 login upgrades hash and regenerates HttpOnly/SameSite session');
const paths=['dashboard.php','all-test.php','new-test.php','assigned-test.php','ontheway-samplecollection-test.php','sample-collected-test.php','sample-collected-tests.php','samplesent-lab-test.php','reportdelivered-test.php','manage-phlebotomist.php','add-phlebotomist.php','edit-phlebotomist.php?pid=1','test-details.php?tid=1&oid=999999999','profile.php','change-password.php','search-report.php','search-report-result.php','bwdates-report-ds.php','bwdates-report-result.php','new-user-testing.php','registered-user-testing.php','patient-search-report.php','patient-report.php','login.php','password-recovery.php','live-test-updates.php','logout.php','index.php'];
for(const path of paths) {
 response=await req(path);assert.equal(response.status,200,path);assert.ok(!response.body.includes('Warning:') && !response.body.includes('Fatal error:') && !response.body.includes('service is temporarily unavailable'),path);
 if(path.startsWith('test-details'))assert.ok(!response.body.includes('Request #999999999'));
}
console.log('PASS 28 public/admin routes and record-derived reference');
execFileSync(mysql,[...args,'-e',"INSERT INTO covidtmsdb.tbltestrecord(OrderNumber,PatientMobileNumber,TestType,TestTimeSlot,ReportStatus) SELECT 90000000000000 + seq, '1234567893','Antigen','2026-10-04T12:00','New' FROM (SELECT 1 seq UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11) n;"]);
response=await req('all-test.php');
assert.ok(response.body.includes('13 records') && response.body.includes('Next') && (response.body.match(/>View request</g)||[]).length===10);
response=await req('all-test.php?page=2');assert.ok(response.body.includes('Previous') && (response.body.match(/>View request</g)||[]).length===3);
response=await req('all-test.php?page=999');assert.ok(response.body.includes('Page 2 of 2'));
console.log('PASS server-side pagination and page clamping');
execFileSync(mysql,[...args,'-e','DELETE FROM covidtmsdb.tbltestrecord WHERE OrderNumber BETWEEN 90000000000001 AND 90000000000011;']);
response=await req('manage-phlebotomist.php?pid=1&action=delete');assert.equal(response.status,200);
const retained=execFileSync(mysql,[...args,'-N','-e','SELECT COUNT(*) FROM covidtmsdb.tblphlebotomist WHERE id=1'],{encoding:'utf8'}).trim();assert.equal(retained,'1');console.log('PASS old GET deletion cannot mutate staff');
execFileSync(mysql,[...args,'-e',"DELETE FROM covidtmsdb.tbladmin WHERE AdminuserName='legacyfixture'"]);

