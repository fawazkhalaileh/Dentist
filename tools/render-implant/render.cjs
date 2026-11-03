const { chromium } = require('/home/codex/tools/node_modules/playwright');
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const root = __dirname;
const server = http.createServer((req,res)=>{
 const file=path.join(root,req.url==='/'?'scene.html':req.url);
 res.setHeader('Content-Type',file.endsWith('.js')?'text/javascript':'text/html');
 fs.createReadStream(file).pipe(res);
});
(async()=>{
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 const browser=await chromium.launch({args:['--use-gl=angle','--use-angle=swiftshader','--enable-unsafe-swiftshader']});
 try {
 const page=await browser.newPage(); await page.goto(`http://127.0.0.1:${server.address().port}`);await page.waitForFunction(()=>window.ready);
 for(const view of ['upper','lower','icon']){
 const data=await page.evaluate(view=>window.renderImplant(view),view);
 fs.writeFileSync(path.join(root,'../../public/images/teeth',view==='icon'?'implant-icon.webp':`implant-side-${view}.webp`),Buffer.from(data.split(',')[1],'base64'));
 }
 } finally {await browser.close();server.close();}
})().catch(e=>{console.error(e);process.exitCode=1;server.close();});
