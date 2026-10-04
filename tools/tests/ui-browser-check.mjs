import assert from 'node:assert/strict';
import { writeFileSync, mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
if (!process.env.UI_TEST_PASSWORD || !process.env.UI_TEST_ORDER) throw Error('Run the isolated test harness to create synthetic fixtures first.');
const output=resolve(process.env.VMS_TEST_OUTPUT || '.test-artifacts/screenshots');
const base=process.env.TEST_BASE_URL || 'http://127.0.0.1:8086';
mkdirSync(output,{recursive:true});
const targets=await (await fetch('http://127.0.0.1:'+(process.env.VMS_BROWSER_PORT || '9226')+'/json')).json();
const ws=new WebSocket(targets.find(t=>t.type==='page').webSocketDebuggerUrl);
await new Promise((done,fail)=>{ws.onopen=done;ws.onerror=fail});
let seq=0; const pending=new Map(), browserErrors=[];
ws.onmessage=event=>{const data=JSON.parse(event.data);if(data.id){const item=pending.get(data.id);if(item){pending.delete(data.id);data.error?item.reject(data.error):item.resolve(data.result);}}if(data.method==='Runtime.exceptionThrown')browserErrors.push(data.params.exceptionDetails);};
function call(method,params={}){return new Promise((resolve,reject)=>{const id=++seq;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});}
await call('Page.enable'); await call('Runtime.enable'); await call('Log.enable');
async function evaluate(expression){const result=await call('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(result.exceptionDetails)throw Error(JSON.stringify(result.exceptionDetails));return result.result.value;}
async function navigate(path,width=1366,height=1000){
 await call('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:width<768});
 await call('Page.navigate',{url:base+'/'+path});
 await new Promise(r=>setTimeout(r,180));
 await evaluate('document.fonts.ready');
 const info=await evaluate('({title:document.title,width:innerWidth,scrollWidth:document.documentElement.scrollWidth,errors:/Warning:|Fatal error:/.test(document.body.innerText)})');
 assert.ok(info.scrollWidth<=info.width,'Horizontal document overflow: '+path+' '+width+' '+JSON.stringify(info));
 assert.equal(info.errors,false,path);
 assert.ok(info.title.includes('COVID19 TMS'));
 return info;
}
async function screenshot(filename){
 const result=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
 writeFileSync(resolve(output,filename),Buffer.from(result.data,'base64'));
}
const widths=[375,768,1366,1920];
const publicPaths=['index.php','new-user-testing.php','registered-user-testing.php','patient-search-report.php','login.php','live-test-updates.php'];
for(const width of widths){
 for(const path of publicPaths)await navigate(path,width,1000);
 console.log('PASS public layouts at '+width+'px');
}
await navigate('index.php',1366,1000);await screenshot('landing-desktop.png');
await navigate('index.php',1920,1080);await screenshot('landing-wide.png');
await navigate('index.php',375,844);await screenshot('landing-mobile.png');
assert.equal(await evaluate('document.querySelector(".navbar-toggler").getAttribute("aria-expanded")'),'false');
await evaluate('document.querySelector(".navbar-toggler").click()');await new Promise(r=>setTimeout(r,350));
assert.equal(await evaluate('document.querySelector(".navbar-toggler").getAttribute("aria-expanded")'),'true');
assert.equal(await evaluate('document.querySelector("#navigation").classList.contains("show")'),true);
await screenshot('navigation-mobile.png');console.log('PASS accessible mobile navigation expansion');
await call('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-motion',value:'reduce'}]});
assert.ok(await evaluate('parseFloat(getComputedStyle(document.querySelector(".feature-card")).transitionDuration)<0.001'));
await call('Emulation.setEmulatedMedia',{features:[]});console.log('PASS reduced-motion styling');
await navigate('new-user-testing.php',1366,1000);await screenshot('request-desktop.png');
await navigate('new-user-testing.php',375,844);await screenshot('request-mobile.png');
await navigate('login.php',1366,1000);await screenshot('login-desktop.png');
await evaluate('document.querySelector("[name=username]").value="tester";document.querySelector("[name=inputpwd]").value='+JSON.stringify(process.env.UI_TEST_PASSWORD)+';document.querySelector("form").requestSubmit();true');
await new Promise(r=>setTimeout(r,750));
assert.ok((await evaluate('location.pathname')).endsWith('dashboard.php'));
const adminPaths=['dashboard.php','all-test.php','test-details.php?tid=1','manage-phlebotomist.php','add-phlebotomist.php','profile.php','change-password.php'];
for(const width of widths){
 for(const path of adminPaths)await navigate(path,width,1000);
 console.log('PASS admin layouts at '+width+'px');
}
await navigate('dashboard.php',1366,1000);await screenshot('dashboard-desktop.png');
await navigate('dashboard.php',375,844);await screenshot('dashboard-mobile.png');
await navigate('all-test.php',375,844);await screenshot('requests-mobile.png');
assert.equal(await evaluate('document.querySelector(".table-responsive").tabIndex'),0);
assert.ok(await evaluate('Array.from(document.querySelectorAll(".form-control")).every(el=>document.querySelector("label[for="+el.id+"]"))'));
await navigate('test-details.php?tid=1',1366,1000);await screenshot('request-details-desktop.png');
assert.ok(await evaluate('document.querySelector(".status-ready").textContent.includes("Report ready")'));
// Tracking submits existing fields unchanged and exposes the delivered report.
await navigate('patient-search-report.php',375,844);
await evaluate('document.querySelector("[name=ordernumber]").value='+JSON.stringify(process.env.UI_TEST_ORDER)+';document.querySelector("[name=mobilenumber]").value="1234567893";document.querySelector("[name=dob]").value="2000-01-01";document.querySelector("main form").requestSubmit();true');
await new Promise(r=>setTimeout(r,300));
assert.ok((await evaluate('location.pathname')).endsWith('patient-report-details.php'));
await screenshot('tracking-mobile.png');
const download=await evaluate('fetch(document.querySelector("a[href^=\\\"download-report.php\\\"]").href).then(async r=>({status:r.status,type:r.headers.get("content-type"),signature:(await r.text()).slice(0,5)}))');
assert.deepEqual(download,{status:200,type:'application/pdf',signature:'%PDF-'});
console.log('PASS browser tracking form and authorized report download');
// Native validity and modal confirmation use the same POST form.
await navigate('add-phlebotomist.php');
assert.equal(await evaluate('document.querySelector("main form").checkValidity()'),false);
await evaluate('document.querySelector("[name=empid]").value="EMP-UI";document.querySelector("[name=fullname]").value="Morgan Lee";document.querySelector("[name=mobilenumber]").value="1234567895";document.querySelector("main form").requestSubmit();true');
await new Promise(r=>setTimeout(r,300));assert.ok((await evaluate('location.pathname')).endsWith('manage-phlebotomist.php'));
await evaluate('Array.from(document.querySelectorAll("tr")).find(row=>row.textContent.includes("EMP-UI")).querySelector("form button").click()');
await new Promise(r=>setTimeout(r,750));
assert.equal(await evaluate('document.querySelector("#ui-confirm-modal").classList.contains("show")'),true);
assert.equal(await evaluate('document.activeElement.id'),'ui-confirm-cancel');
await screenshot('confirmation-desktop.png');
await call('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});
await call('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});
await new Promise(r=>setTimeout(r,750));
assert.equal(await evaluate('document.querySelector("#ui-confirm-modal").classList.contains("show")'),false);
assert.ok(await evaluate('document.body.textContent.includes("EMP-UI")'));
await evaluate('Array.from(document.querySelectorAll("tr")).find(row=>row.textContent.includes("EMP-UI")).querySelector("form button").click()');
await new Promise(r=>setTimeout(r,750));await evaluate('document.querySelector("#ui-confirm-accept").click()');
await new Promise(r=>setTimeout(r,400));
assert.equal(await evaluate('document.body.textContent.includes("EMP-UI")'),false);
console.log('PASS confirmation modal: focus, Escape cancellation, unchanged POST deletion');
const routes=['index.php','new-user-testing.php','registered-user-testing.php','patient-search-report.php','patient-report.php','patient-report-details.php?oid='+process.env.UI_TEST_ORDER,'login.php','logout.php','password-recovery.php','dashboard.php','all-test.php','new-test.php','assigned-test.php','ontheway-samplecollection-test.php','sample-collected-test.php','sample-collected-tests.php','samplesent-lab-test.php','reportdelivered-test.php','test-details.php?tid=1','manage-phlebotomist.php','add-phlebotomist.php','edit-phlebotomist.php?pid=1','search-report.php','search-report-result.php','bwdates-report-ds.php','bwdates-report-result.php','profile.php','change-password.php','live-test-updates.php'];
const responses=await evaluate('Promise.all('+JSON.stringify(routes)+'.map(async path=>{const r=await fetch(path);const body=await r.text();return {path,status:r.status,invalid:/Warning:|Fatal error:|service is temporarily unavailable/.test(body)}}))');
for(const response of responses){assert.equal(response.status,200,JSON.stringify(response));assert.equal(response.invalid,false,response.path);}
await navigate('dashboard.php',1366);
const assets=await evaluate('Promise.all(Array.from(document.querySelectorAll("link[rel=stylesheet],script[src]")).map(async el=>({url:el.href||el.src,status:(await fetch(el.href||el.src)).status})))');
assert.ok(assets.every(asset=>asset.status===200));
const links=await evaluate('Array.from(document.querySelectorAll("a[href]")).map(a=>a.href)');
const linkResults=await evaluate('Promise.all('+JSON.stringify([...new Set(links)])+'.map(async url=>({url,status:(await fetch(url)).status})))');
assert.ok(linkResults.every(link=>link.status<400),JSON.stringify(linkResults));
assert.equal(browserErrors.length,0,JSON.stringify(browserErrors));
console.log('PASS 29 routes, rendered navigation, labels, table keyboard access, local assets and browser JS');
ws.close();

