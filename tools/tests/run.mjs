import assert from 'node:assert/strict';
import { spawn, execFileSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, readFileSync, readdirSync, openSync, closeSync, writeFileSync } from 'node:fs';
import { resolve, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import net from 'node:net';

const root = fileURLToPath(new URL('../..', import.meta.url));
const artifacts = join(root, '.test-artifacts');
mkdirSync(artifacts, {recursive:true});
writeFileSync(join(artifacts,'.htaccess'),'Require all denied\n');
const run = mkdtempSync(join(artifacts, 'run-'));
const php = process.env.PHP_BIN || 'C:/xampp/php/php.exe';
const mysql = process.env.MYSQL_BIN || 'C:/xampp/mysql/bin/mysql.exe';
const mysqld = process.env.MYSQLD_BIN || 'C:/xampp/mysql/bin/mysqld.exe';
const installer = process.env.MYSQL_INSTALL_DB_BIN || 'C:/xampp/mysql/bin/mysql_install_db.exe';
const chrome = process.env.CHROME_BIN || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const ports = [33316,8086,9226];
for (const port of ports) await new Promise((done,fail) => {
  const server = net.createServer(); server.on('error', () => fail(Error('Test port '+port+' is occupied; no existing service will be stopped.')));
  server.listen(port,'127.0.0.1', () => server.close(done));
});
const env = {...process.env, DB_HOST:'127.0.0.1', DB_PORT:'33316', DB_NAME:'covidtmsdb', DB_USER:'root', DB_PASSWORD:'', APP_ENV:'production', APP_TIMEZONE:'UTC',
  REPORT_STORAGE:join(run,'reports'), VMS_TEST_DB_PORT:'33316', TEST_BASE_URL:'http://127.0.0.1:8086', PHP_BIN:php, MYSQL_BIN:mysql,
  VMS_TEST_FIXTURE_FILE:join(run,'fixtures.json'), VMS_TEST_OUTPUT:join(run,'screenshots')};
for (const name of ['sessions','uploads','reports']) mkdirSync(join(run,name));
function execute(binary,args,options={}) { return execFileSync(binary,args,{cwd:root,env,encoding:'utf8',windowsHide:true,timeout:120000,...options}); }
function files(dir) { return readdirSync(dir,{withFileTypes:true}).flatMap(entry => entry.isDirectory() ? (['.git','.local-backup','.test-artifacts','storage','reportfiles'].includes(entry.name) ? [] : files(join(dir,entry.name))) : [join(dir,entry.name)]); }
const phpFiles = files(root).filter(path=>path.endsWith('.php') && !path.endsWith('config.local.php'));
for (const path of phpFiles) execute(php,['-l',path]);
console.log('PASS PHP syntax: '+phpFiles.length+' files');
execute(installer,['--datadir='+join(run,'data'),'--port=33316','--skip-networking']);
const children=[];
function start(binary,args) {
  const log=openSync(join(run,'service-'+children.length+'.log'),'a');
  const child=spawn(binary,args,{cwd:root,env,windowsHide:true,stdio:['ignore',log,log]}); closeSync(log); children.push(child);
  child.on('error',error=>console.error(error));
  return child;
}
async function waitFor(check) {
  for (let i=0;i<100;i++) { try { if (await check()) return; } catch {} await new Promise(done=>setTimeout(done,200)); }
  throw Error('Isolated test service did not become ready. Artifacts: '+run);
}
const mysqlArgs=['--no-defaults','--host=127.0.0.1','--port=33316','--user=root'];
try {
  start(mysqld,['--no-defaults','--datadir='+join(run,'data'),'--bind-address=127.0.0.1','--port=33316','--console']);
  await waitFor(()=>execute(mysql,[...mysqlArgs,'-e','SELECT 1']).includes('1'));
  start(php,['-d','session.save_path='+join(run,'sessions'),'-d','upload_tmp_dir='+join(run,'uploads'),'-d','error_log='+join(run,'php.log'),'-S','127.0.0.1:8086','-t',root,join(root,'tools/dev-router.php')]);
  await waitFor(async()=> (await fetch(env.TEST_BASE_URL)).status===200);
  console.log(execute(process.execPath,[join(root,'tools/tests/workflow.mjs')]));
  console.log(execute(process.execPath,[join(root,'tools/tests/regression.mjs')]));
  for (const args of [[],['--apply'],['--apply']]) console.log(execute(php,[join(root,'tools/migrate.php'),...args]));
  const fixtures=JSON.parse(readFileSync(env.VMS_TEST_FIXTURE_FILE,'utf8'));
  env.UI_TEST_PASSWORD=fixtures.password; env.UI_TEST_ORDER=fixtures.order;
  start(chrome,['--headless','--disable-gpu','--no-first-run','--remote-debugging-address=127.0.0.1','--remote-debugging-port=9226','--user-data-dir='+join(run,'browser'),'about:blank']);
  await waitFor(async()=> (await fetch('http://127.0.0.1:9226/json')).ok);
  console.log(execute(process.execPath,[join(root,'tools/tests/ui-browser-check.mjs')]));
  for (const path of ['database/covidtmsdb.sql','docs/AUDIT.md','.local-backup/','tools/tests/run.mjs']) {
    assert.ok([403,404].includes((await fetch(env.TEST_BASE_URL+'/'+path)).status),path);
  }
  console.log('PASS publication directories are inaccessible through the development server.');
  console.log('All retained automated suites passed. Artifacts: '+run);
} finally {
  try { execute(resolve(mysql,'../mysqladmin.exe'),[...mysqlArgs,'shutdown']); } catch {}
  for (const child of children.reverse()) { if (child.exitCode===null) child.kill(); }
}
