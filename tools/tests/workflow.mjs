import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const app = resolve(fileURLToPath(new URL('../..', import.meta.url)));
const root = app;
const base = process.env.TEST_BASE_URL || 'http://127.0.0.1:8086';
if (!process.env.VMS_TEST_DB_PORT || process.env.VMS_TEST_DB_PORT === '3306') {
  throw new Error('Set VMS_TEST_DB_PORT to an isolated database port; never run against your working database.');
}
const env = { ...process.env, DB_PORT: process.env.VMS_TEST_DB_PORT, DB_NAME: 'covidtmsdb', APP_ENV: 'production' };
const php = process.env.PHP_BIN || 'C:/xampp/php/php.exe';
const mysql = process.env.MYSQL_BIN || 'C:/xampp/mysql/bin/mysql.exe';
const mysqlArgs = ['--no-defaults','--host=127.0.0.1','--port=' + env.DB_PORT,'--user=root'];
execFileSync(mysql, [...mysqlArgs, '-e', 'CREATE DATABASE IF NOT EXISTS covidtmsdb']);
const tables = execFileSync(mysql, [...mysqlArgs, '-N', '-e', 'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA="covidtmsdb"'], {encoding:'utf8'}).trim();
assert.equal(tables, '0', 'Integration tests require a NEW empty database.');
execFileSync(mysql, mysqlArgs, {input:readFileSync(join(root, 'database/covidtmsdb.sql')),env});
const password = 'Integration-pass-' + Date.now();
execFileSync(php, [join(app,'tools/admin.php'),'create'], {input:'tester\n'+password+'\nTest Administrator\ntester@example.invalid\n1234567890\n',env});
const client = () => {
  let cookie='';
  return {
    async request(path, options={}) {
      const response=await fetch(base+'/'+path, {...options,redirect:'manual',headers:{...options.headers,...(cookie ? {Cookie:cookie} : {})}});
      const setCookie=response.headers.get('set-cookie');
      if (setCookie) cookie=setCookie.split(';')[0];
      return {status:response.status,location:response.headers.get('location'),type:response.headers.get('content-type'),body:await response.text(),headers:response.headers};
    },
    async form(path, values, tokenPath=path) {
      const page=await this.request(tokenPath);
      const token=page.body.match(/name="csrf" value="([^"]+)"/)?.[1];
      assert.ok(token,'CSRF token present: '+tokenPath);
      return this.request(path,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({csrf:token,...values})});
    }
  };
};
let checks=0;
function check(condition,label) { assert.ok(condition,label); checks++; console.log('PASS '+label); }
const publicUser=client(), admin=client(), stranger=client();
let result=await publicUser.request('index.php');
check(result.status===200 && result.body.includes('A clearer path'),'landing page');
result=await stranger.request('dashboard.php');
check(result.status===303 && result.location==='login.php','protected admin redirect');
result=await admin.form('login.php',{username:"' OR 1=1 --",inputpwd:'wrong'});
check(result.status===200 && result.body.includes('incorrect'),'SQL injection cannot bypass authentication');
result=await admin.form('login.php',{username:'tester',inputpwd:password});
check(result.status===303 && result.location==='dashboard.php','administrator sign in');
result=await admin.request('dashboard.php');
check(result.body.includes('No requests yet'),'empty dashboard');
result=await admin.request('add-phlebotomist.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'empid=BAD&fullname=Bad&mobilenumber=1234567890'});
check(result.body.includes('form has expired'),'CSRF rejection');
result=await admin.form('add-phlebotomist.php',{empid:'EMP-001',fullname:'Casey Collection',mobilenumber:'1234567891'});
check(result.status===303,'create phlebotomist');
result=await admin.form('add-phlebotomist.php',{empid:'EMP-001',fullname:'Duplicate',mobilenumber:'1234567892'});
check(result.body.includes('already exists'),'employee uniqueness');
result=await admin.form('edit-phlebotomist.php?pid=1',{fullname:'Casey Updated',mobilenumber:'1234567891'});
check(result.status===303,'update phlebotomist');
result=await publicUser.form('new-user-testing.php',{fullname:'Invalid',mobilenumber:'abc',dob:'2000-01-01'});
check(result.body.includes('10–15 digits'),'server-side validation');
const slot=new Date(Date.now()+86400000).toISOString().slice(0,16);
const request={fullname:"Jordan <script>alert(1)</script> O'Neil",mobilenumber:'1234567893',dob:'2000-01-01',govtissuedid:'Passport',govtidnumber:'TEST-ONLY',address:"100 Test Street, O'Neil",state:'Test Region',testtype:'RT-PCR',birthdaytime:slot};
result=await publicUser.form('new-user-testing.php',request);
check(result.status===303 && result.location.startsWith('patient-report-details.php?oid='),'new patient and request');
const order=new URL('http://local/'+result.location).searchParams.get('oid');
result=await publicUser.request(result.location);
check(result.body.includes('&lt;script&gt;') && !result.body.includes('<script>alert(1)</script>'),'patient output escaped');
check(result.body.includes(order),'reference visible after submission');
result=await stranger.request('patient-report-details.php?oid='+order);
check(result.status===303 && result.location==='patient-search-report.php','unauthorized patient record hidden');
result=await stranger.form('patient-search-report.php',{ordernumber:order,mobilenumber:'1234567893',dob:'1999-01-01'});
check(result.body.includes('No matching request'),'incorrect patient identity rejected');
result=await stranger.form('patient-search-report.php',{ordernumber:order,mobilenumber:'1234567893',dob:'2000-01-01'});
check(result.status===303,'authorized patient tracking');
result=await publicUser.form('new-user-testing.php',request);
check(result.body.includes('registered'),'duplicate patient rejected');
result=await publicUser.form('registered-user-testing.php',{mobilenumber:'1234567893',dob:'1999-01-01',testtype:'Antigen',birthdaytime:slot});
check(result.body.includes('did not match'),'repeat request checks patient identity');
result=await publicUser.form('registered-user-testing.php',{mobilenumber:'1234567893',dob:'2000-01-01',testtype:'Antigen',birthdaytime:slot});
check(result.status===303,'repeat patient request');
const detail='test-details.php?tid=1';
result=await admin.form(detail,{action:'assign',assignto:'EMP-001'});
check(result.status===303,'assign request');
result=await admin.form('manage-phlebotomist.php',{pid:'1'});
check(result.body.includes('cannot be deleted'),'assigned staff protected from deletion');
result=await admin.form(detail,{action:'progress',status:'Sent to Lab',remark:'Attempt skipped stage'});
check(result.body.includes('next stage'),'out-of-order transition rejected');
for (const status of ['On the Way for Collection','Sample Collected','Sent to Lab']) {
  result=await admin.form(detail,{action:'progress',status,remark:'Integration progress'});
  check(result.status===303,'progress: '+status);
}
result=await admin.form(detail,{action:'progress',status:'Delivered',remark:'Premature delivery'});
check(result.body.includes('Save a test result'),'delivery requires report');
const pdf = '%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF';
async function upload(content,name) {
  const page=await admin.request(detail);
  const token=page.body.match(/name="csrf" value="([^"]+)"/)[1];
  const form=new FormData();
  for(const [key,value] of Object.entries({csrf:token,action:'report',testresult:'Negative',remark:'Integration report'})) form.set(key,value);
  form.set('report',new Blob([content],{type:'application/pdf'}),name);
  return admin.request(detail,{method:'POST',body:form});
}
result=await upload('<?php echo "unsafe"; ?>','report.pdf');
check(result.body.includes('valid PDF'),'disguised PHP upload rejected');
result=await upload(pdf,'report.pdf');
check(result.status===303,'PDF report and result saved');
result=await publicUser.request('download-report.php?oid='+order);
check(result.status===404,'undelivered report hidden from patient');
result=await admin.form(detail,{action:'progress',status:'Delivered',remark:'Report delivered'});
check(result.status===303,'report delivered');
result=await stranger.request('download-report.php?oid='+order);
check(result.status===200 && result.type==='application/pdf' && result.body.startsWith('%PDF-'),'authorized report download');
result=await upload(pdf,'corrected.pdf');
check(result.status===303,'delivered report correction');
const anonymous=client();
result=await anonymous.request('download-report.php?oid='+order);
check(result.status===404,'anonymous report download rejected');
for (const path of ['storage/test.pdf','reportfiles/test.pdf','includes/config.php','tools/admin.php','.htaccess']) {
  result=await anonymous.request(path); check([403,404].includes(result.status),'private path blocked: '+path);
}
result=await admin.request('all-test.php?q=Jordan&status=Delivered');
check(result.body.includes(order),'search and status filtering');
result=await admin.request('all-test.php?fromdate=2020-01-01&todate=2030-01-01');
check(result.body.includes(order),'date range report');
result=await admin.request('all-test.php?fromdate=bad&todate=bad');
check(result.body.includes('valid date range'),'invalid date range handled');
result=await admin.request('test-details.php?tid=999999');
check(result.status===404 && result.body.includes('not found'),'missing record handled');
result=await admin.form('profile.php',{adminname:'Updated Admin',mobilenumber:'1234567890',email:'updated@example.invalid'});
check(result.status===303,'profile update');
const newPassword=password+'-new';
result=await admin.form('change-password.php',{currentpassword:password,newpassword:newPassword,confirmpassword:newPassword});
check(result.status===303,'password change');
result=await admin.form('logout.php',{});
check(result.status===303,'CSRF protected logout');
result=await admin.form('login.php',{username:'tester',inputpwd:password});
check(result.body.includes('incorrect'),'old password rejected');
result=await admin.form('login.php',{username:'tester',inputpwd:newPassword});
check(result.status===303,'new password works');
result=await admin.form('add-phlebotomist.php',{empid:'DELETE-ME',fullname:'Temporary',mobilenumber:'1234567894'});
check(result.status===303,'create unassigned staff');
const temporaryStaffId=execFileSync(mysql,[...mysqlArgs,'-N','-e',"SELECT id FROM covidtmsdb.tblphlebotomist WHERE EmpID='DELETE-ME'"],{encoding:'utf8'}).trim();
result=await admin.form('manage-phlebotomist.php',{pid:temporaryStaffId});
check(result.status===303,'delete unassigned staff');
const sqlCount=execFileSync(mysql,[...mysqlArgs,'-N','-e','SELECT COUNT(*) FROM covidtmsdb.tblpatients; SELECT COUNT(*) FROM covidtmsdb.tbltestrecord; SELECT COUNT(*) FROM covidtmsdb.tblreporttracking;'],{encoding:'utf8'}).trim();
check(sqlCount==='1\n2\n7' || sqlCount.replace(/\r/g,'')==='1\n2\n7','consistent patient/test/history counts');
console.log('Completed '+checks+' integration checks. Test database contains synthetic fixtures only.');

if (process.env.VMS_TEST_FIXTURE_FILE) writeFileSync(process.env.VMS_TEST_FIXTURE_FILE, JSON.stringify({password:newPassword,order}));
