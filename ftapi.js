'use strict';
/* v0.0.17: shared client for the server features used by the Creator and the editor —
   sign-in (owner or team account) with a small built-in login box, chunked uploads that stay
   under the host's upload limit, editable drafts on the server, and the shared photo library. */
window.FT=(()=>{
 const API=new URL('./api/index.php',location.href);
 let csrf='',me=null,maxUpload=1048576,statusPromise=null,gated=true;
 const SEEN='ft-logged-in',BRAND='<div class="ft-gate-brand"><img src="./icons/logo-160.webp" alt="" width="72" height="72"><strong>ShopSmoke Studio</strong></div>';
 const remember=on=>{try{if(on)localStorage.setItem(SEEN,'1');else localStorage.removeItem(SEEN)}catch{}},remembered=()=>{try{return localStorage.getItem(SEEN)==='1'}catch{return false}};
 /* What a team login can do. v0.0.21: publish now / publish later / send for approval / approve are separate. v0.0.30: here so the TV Manager and the Team page share one list; the server keeps its own. */
 const PERMS=['publish','schedule','submit','approve','tvs','history','activity','drafts','photos','delete_any'],team={PERMS,PERM_LABEL:{publish:'Publish to TVs now (and put an old menu back)',schedule:'Schedule TV updates for later',submit:'Send photos to the owner for approval',approve:'Approve or decline photos the team sent',tvs:'Add and rename TVs',history:'Delete old menus, cancel schedules',activity:'See activity',drafts:'Save drafts',photos:'Upload and sync photos',delete_any:'Delete other people’s drafts and photos'},PRESETS:{manager:PERMS.slice(),staff:['drafts','photos','submit']}};
 async function api(action,{data,form,query}={}){
  const u=new URL(API.href);u.searchParams.set('action',action);for(const [k,v] of Object.entries(query||{}))u.searchParams.set(k,String(v));
  const init={credentials:'same-origin',cache:'no-store',headers:{}};
  if(data!==undefined||form){init.method='POST';init.headers['X-FT-CSRF']=csrf;if(form)init.body=form;else{init.headers['Content-Type']='application/json';init.body=JSON.stringify(data)}}
  let res;try{res=await fetch(u,init)}catch{throw Error('No connection to the server — check the internet and try again')}
  let out=null;try{out=await res.json()}catch{}
  if(!res.ok||!out||!out.ok){if(res.status===401){me=null;csrf=''}throw Error(out?.error||('Server error '+res.status))}
  return out;
 }
 async function status(force=false){
  if(!statusPromise||force)statusPromise=api('status').then(s=>{csrf=s.csrf||'';me=s.authenticated?s.user:null;if(s.max_upload)maxUpload=s.max_upload;return s}).catch(e=>{statusPromise=null;throw e});
  return statusPromise;
 }
 /* --- login box --- */
 function loginBox(reason,{recover=false,gate=false}={}){return new Promise((resolve,reject)=>{
  const wrap=document.createElement('div');wrap.className='ft-login'+(gate?' ft-gate':'');
  wrap.innerHTML='<div class="ft-login-card"><form data-login autocomplete="on"><h3>Log in</h3><p class="ft-login-why"></p><label>Email or username<input name="username" autocomplete="username" autocapitalize="off" spellcheck="false" placeholder="you@example.com"></label><label>Password<input name="password" type="password" autocomplete="current-password" required></label><p class="ft-login-err" role="alert"></p><div class="ft-login-actions"><button type="button" class="ft-forgot" data-forgot aria-expanded="false">Forgot password?</button><button type="button" data-cancel>Cancel</button><button type="submit" class="ft-primary">Log in</button></div></form>'
   +'<form data-send class="ft-recover" hidden><label>Owner email<input name="email" type="email" required autocomplete="username" autocapitalize="off" spellcheck="false"></label><p class="ft-login-why">We’ll email you a code. Team members: ask the owner to reset your password.</p><p class="ft-login-err" role="alert"></p><div class="ft-login-actions"><button type="submit" class="ft-primary">Email me a code</button></div></form>'
   +'<form data-reset class="ft-recover" hidden><p class="ft-login-why">If that’s the owner’s email, a code is on its way. It works for 15 minutes. Check your spam folder too.</p><label>Code<input name="code" inputmode="numeric" autocomplete="one-time-code" required></label><label>New password <small>(4 to 20 characters)</small><input name="password" type="password" minlength="4" maxlength="20" autocomplete="new-password" required></label><label>Repeat new password<input name="password2" type="password" minlength="4" maxlength="20" autocomplete="new-password" required></label><p class="ft-login-err" role="alert"></p><div class="ft-login-actions"><button type="button" data-resend>Send a new code</button><button type="submit" class="ft-primary">Set new password</button></div></form></div>';
  const [login,send,reset]=['[data-login]','[data-send]','[data-reset]'].map(q=>wrap.querySelector(q)),forgot=wrap.querySelector('[data-forgot]'),why=login.querySelector('.ft-login-why'),errOf=f=>f.querySelector('.ft-login-err');
  why.textContent=reason||'Log in to use the server.';if(gate)wrap.querySelector('.ft-login-card').insertAdjacentHTML('afterbegin',BRAND);
  const close=()=>wrap.remove();
  wrap.querySelector('[data-cancel]').onclick=()=>{close();reject(Error('Sign-in cancelled'))};
  login.onsubmit=async e=>{e.preventDefault();const f=new FormData(login),btn=login.querySelector('.ft-primary');btn.disabled=true;
   try{await api('login',{data:{username:f.get('username')||'',password:f.get('password')||''}});await status(true);if(!me)throw Error('Log in failed');close();document.dispatchEvent(new Event('ft-auth'));resolve(me)}
   catch(err){errOf(login).textContent=err.message;btn.disabled=false}};
  /* Forgot password: email → code → new password, in a drop-down under the login form. */
  const showRecover=open=>{send.hidden=!open;reset.hidden=true;forgot.setAttribute('aria-expanded',String(open));if(open){const typed=login.elements.username.value.trim();if(typed.includes('@'))send.elements.email.value=typed;send.elements.email.focus()}};
  forgot.onclick=()=>showRecover(send.hidden&&reset.hidden);
  send.onsubmit=async e=>{e.preventDefault();const btn=send.querySelector('.ft-primary');btn.disabled=true;errOf(send).textContent='';
   try{await api('recover_request',{data:{email:send.elements.email.value.trim()}});send.hidden=true;reset.hidden=false;reset.elements.code.focus()}catch(err){errOf(send).textContent=err.message}btn.disabled=false};
  wrap.querySelector('[data-resend]').onclick=()=>{reset.hidden=true;send.hidden=false};
  reset.onsubmit=async e=>{e.preventDefault();const btn=reset.querySelector('.ft-primary'),email=send.elements.email.value.trim();errOf(reset).textContent='';if(reset.elements.password.value!==reset.elements.password2.value){errOf(reset).textContent='The two passwords do not match.';return}btn.disabled=true;
   try{await api('recover_reset',{data:{email,code:reset.elements.code.value,password:reset.elements.password.value}});reset.reset();showRecover(false);login.elements.username.value=email;login.elements.password.value='';why.textContent='Password changed. Log in with your new password.';errOf(login).textContent='';login.elements.password.focus()}
   catch(err){errOf(reset).textContent=err.message}btn.disabled=false};
  document.body.append(wrap);setTimeout(()=>recover?showRecover(true):login.elements.username.focus(),50);
 })}
 /* The Publish and TV Manager pages have their own login forms: their "Forgot password?" opens this box. */
 document.addEventListener('click',e=>{if(e.target.closest?.('[data-ft-recover]')){e.preventDefault();loginBox('',{recover:true}).then(()=>location.reload()).catch(()=>{})}});
 async function ensureLogin(reason){const s=await status();if(!s.installed)throw Error('The server is not set up yet (open /setup/install.php)');if(me)return me;if(gated)return new Promise(()=>{});return loginBox(reason)}
 /* --- chunked upload --- */
 /* v0.0.24.1: a dropped connection pauses an upload for up to 10 minutes instead of failing it. */
 const backOnline=()=>new Promise(done=>{const t=setTimeout(()=>done(false),600000);addEventListener('online',()=>{clearTimeout(t);done(true)},{once:true})});
 /* What the server stored must be the same size as what was sent. */
 const sameSize=(row,key,blob)=>{if(Number.isFinite(row?.[key])&&row[key]!==blob.size)throw Error('The server saved '+fmtSize(row[key])+' of '+fmtSize(blob.size)+'. Please upload it again.');return row};
 async function uploadBlob(blob,onProgress){
  const size=Math.max(262144,Math.min(1048576,Math.floor(maxUpload*0.8)));const parts=Math.max(1,Math.ceil(blob.size/size));let uploadId='';
  for(let i=0;i<parts;i++){const fd=new FormData();if(uploadId)fd.append('upload_id',uploadId);fd.append('index',String(i));fd.append('chunk',blob.slice(i*size,(i+1)*size),'part'+i);
   let out;for(let attempt=0;;attempt++){try{out=await api('upload_chunk',{form:fd});break}catch(e){if(!navigator.onLine&&await backOnline()){attempt=-1;continue}if(attempt>=2)throw e;await new Promise(r=>setTimeout(r,1200*(attempt+1)))}}
   uploadId=out.upload_id;onProgress?.((i+1)/parts)}
  return {uploadId,parts};
 }
 /* --- v0.0.24.1: one card for every long job. It shows progress and time, and says "done" only once the work is really saved. --- */
 const fmtSize=b=>b>=1073741824?(b/1073741824).toFixed(2)+' GB':b>=1048576?(b/1048576).toFixed(1)+' MB':Math.max(1,Math.round(b/1024))+' KB';
 const fmtTook=s=>{s=Math.max(0,Math.round(s));const h=Math.floor(s/3600),m=Math.floor(s%3600/60);return h?h+' hr '+m+' min':m?m+' min '+(s%60)+' sec':s+' sec'};
 const fmtStamp=v=>{const d=new Date(v);return isNaN(d)?'':d.toLocaleString(undefined,{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'})};
 let jobsRunning=0,wake=null;
 async function stayAwake(){try{if(jobsRunning&&!wake&&!document.hidden&&navigator.wakeLock){wake=await navigator.wakeLock.request('screen');wake.addEventListener('release',()=>{wake=null})}}catch{wake=null}}
 document.addEventListener('visibilitychange',stayAwake);
 addEventListener('beforeunload',e=>{if(jobsRunning){e.preventDefault();e.returnValue=''}});
 function job(title,{cancel=false,note='Keep this page open until you see the green tick.'}={}){
  const el=document.createElement('div');el.className='ft-job';el.innerHTML='<section class="ft-job-card" role="status" aria-live="polite"><h3></h3><p class="ft-job-label"></p><div class="ft-job-bar"><i></i></div><p class="ft-job-time"></p><p class="ft-job-note"></p><ul hidden></ul><footer></footer></section>';
  const q=s=>el.querySelector(s),started=Date.now();let frac=0,running=true;q('h3').textContent=title;q('.ft-job-note').textContent=note;
  const seconds=()=>(Date.now()-started)/1000;
  const showTime=()=>{const s=seconds(),left=frac>.02&&frac<1&&s>=3?s*(1-frac)/frac:null;q('.ft-job-time').textContent=Math.round(frac*100)+'% · '+fmtTook(s)+(left===null?'':' · about '+fmtTook(left)+' left')};
  const offline=()=>{q('.ft-job-note').textContent='Connection lost. This will carry on by itself when it comes back.'},online=()=>{q('.ft-job-note').textContent=note};
  const timer=setInterval(showTime,1000);addEventListener('offline',offline);addEventListener('online',online);
  const finish=()=>{if(!running)return;running=false;clearInterval(timer);removeEventListener('offline',offline);removeEventListener('online',online);jobsRunning--;if(!jobsRunning&&wake){wake.release().catch(()=>{});wake=null}};
  const close=()=>{finish();el.remove()};
  const button=(label,main,run)=>{const b=document.createElement('button');b.type='button';b.textContent=label;if(main)b.className='is-main';b.onclick=run;q('footer').append(b);return b};
  const result=(cls,heading,lines,actions)=>{finish();el.classList.add(cls);q('h3').textContent=heading;['.ft-job-label','.ft-job-bar','.ft-job-time','.ft-job-note'].forEach(s=>{q(s).hidden=true});const ul=q('ul'),rows=lines.filter(Boolean);ul.hidden=!rows.length;ul.replaceChildren(...rows.map(l=>{const li=document.createElement('li');li.textContent=l;return li}));q('footer').replaceChildren();
   for(const a of actions)button(a.label,a.main,()=>{if(!a.stay)close();a.run()});button(actions.length?'Close':'OK',!actions.some(a=>a.main),close).focus()};
  const self={cancelled:false,started,took:()=>fmtTook(seconds()),close,
   update(f,{label}={}){frac=Math.max(0,Math.min(1,f));q('.ft-job-bar i').style.width=(frac*100).toFixed(1)+'%';if(label!==undefined)q('.ft-job-label').textContent=label;showTime()},
   done({title:heading='✓ Done',lines=[],actions=[],warn=false}={}){result(warn?'is-failed':'is-done',heading,lines,actions)},
   fail(message,{title:heading='⚠ That didn’t finish',lines=[]}={}){result('is-failed',heading,[message,...lines],[])}};
  if(cancel){const b=button('Cancel',false,()=>{self.cancelled=true;b.disabled=true;q('.ft-job-note').textContent='Stopping…'})}
  jobsRunning++;stayAwake();showTime();(document.fullscreenElement||document.body).append(el);return self}
 /* --- v0.0.24.1: a ZIP built from Blobs one at a time, so a big export never sits in memory twice --- */
 const CRC=new Int32Array(256).map((_,n)=>{for(let k=0;k<8;k++)n=n&1?0xedb88320^(n>>>1):n>>>1;return n});
 const crc32=b=>{let c=-1;for(let i=0;i<b.length;i++)c=CRC[(c^b[i])&255]^(c>>>8);return (c^-1)>>>0};
 async function zip(entries,onProgress){const out=[],central=[],text=new TextEncoder(),used=new Set(),now=new Date(),time=now.getHours()<<11|now.getMinutes()<<5|now.getSeconds()>>1,date=now.getFullYear()-1980<<9|now.getMonth()+1<<5|now.getDate();let offset=0;
  for(const [i,e] of entries.entries()){let name=e.name;for(let n=2;used.has(name.toLowerCase());n++)name=e.name.replace(/(\.\w+)?$/,'-'+n+'$1');used.add(name.toLowerCase());
   const bytes=new Uint8Array(await e.blob.arrayBuffer()),crc=crc32(bytes),file=text.encode(name),size=bytes.length;if(offset+size>4000000000)throw Error('That is too much for one ZIP. Choose fewer files.');
   const h=new DataView(new ArrayBuffer(30));h.setUint32(0,0x04034b50,true);h.setUint16(4,20,true);h.setUint16(6,0x0800,true);h.setUint16(10,time,true);h.setUint16(12,date,true);h.setUint32(14,crc,true);h.setUint32(18,size,true);h.setUint32(22,size,true);h.setUint16(26,file.length,true);
   const c=new DataView(new ArrayBuffer(46));c.setUint32(0,0x02014b50,true);c.setUint16(4,20,true);c.setUint16(6,20,true);c.setUint16(8,0x0800,true);c.setUint16(12,time,true);c.setUint16(14,date,true);c.setUint32(16,crc,true);c.setUint32(20,size,true);c.setUint32(24,size,true);c.setUint16(28,file.length,true);c.setUint32(42,offset,true);
   out.push(h.buffer,file,e.blob);central.push(c.buffer,file);offset+=30+file.length+size;onProgress?.((i+1)/entries.length,name)}
  const list=new Blob(central),end=new DataView(new ArrayBuffer(22));end.setUint32(0,0x06054b50,true);end.setUint16(8,entries.length,true);end.setUint16(10,entries.length,true);end.setUint32(12,list.size,true);end.setUint32(16,offset,true);
  return new Blob([...out,list,end.buffer],{type:'application/zip'})}
 /* --- drafts --- */
 async function saveDraft({json,name,storeId,pages,draftId,kind='draft',thumb},onProgress){
  const file=new Blob([json],{type:'application/json'}),{uploadId,parts}=await uploadBlob(file,onProgress);
  const fd=new FormData();fd.append('upload_id',uploadId);fd.append('parts',String(parts));fd.append('name',name);fd.append('store_id',storeId);fd.append('pages',String(pages||1));fd.append('kind',kind);if(draftId)fd.append('draft_id',draftId);if(thumb)fd.append('thumb',thumb,'thumb.jpg');
  return sameSize((await api('draft_commit',{form:fd})).draft,'bytes',file);
 }
 const listDrafts=store=>api('draft_list',{query:store?{store}:{}}).then(r=>r.items);
 async function getDraft(id){const u=new URL(API.href);u.searchParams.set('action','draft_get');u.searchParams.set('id',id);const r=await fetch(u,{credentials:'same-origin',cache:'no-store'});if(!r.ok){let m='Could not load draft';try{m=(await r.json()).error||m}catch{}throw Error(m)}return r.text()}
 const draftThumb=id=>{const u=new URL(API.href);u.searchParams.set('action','draft_thumb');u.searchParams.set('id',id);return u.href};
 const deleteDraft=id=>api('draft_delete',{data:{id}});
 /* --- photo library --- */
 const listMedia=store=>api('media_list',{query:{store}}).then(r=>r.items);
 async function uploadMedia({blob,id,storeId,name,folder,thumb},onProgress){const {uploadId,parts}=await uploadBlob(blob,onProgress);const fd=new FormData();fd.append('upload_id',uploadId);fd.append('parts',String(parts));fd.append('id',id||'');fd.append('store_id',storeId);fd.append('name',name||'Photo');fd.append('folder',folder||'');if(thumb)fd.append('thumb',thumb,'thumb.jpg');const out=await api('media_commit',{form:fd});return out.duplicate?out.media:sameSize(out.media,'size',blob)}
 async function mediaBlob(id,thumb=false){const u=new URL(API.href);u.searchParams.set('action','media_file');u.searchParams.set('id',id);if(thumb)u.searchParams.set('thumb','1');const r=await fetch(u,{credentials:'same-origin',cache:'no-store'});if(!r.ok)throw Error('Could not download photo');return r.blob()}
 const deleteMedia=id=>api('media_delete',{data:{id}});
 /* --- v0.0.22: videos for TV pages --- */
 const listVideos=store=>api('video_list',{query:{store}}).then(r=>r.items);
 async function uploadVideo({blob,storeId,name},onProgress){const {uploadId,parts}=await uploadBlob(blob,onProgress);const fd=new FormData();fd.append('upload_id',uploadId);fd.append('parts',String(parts));fd.append('store_id',storeId);fd.append('name',name||'Video');return sameSize((await api('video_commit',{form:fd})).video,'bytes',blob)}
 const deleteVideo=id=>api('video_delete',{data:{id}});
 async function logout(){try{await api("logout",{data:{}})}catch{}me=null;csrf="";statusPromise=null;remember(false);document.dispatchEvent(new Event('ft-auth'));location.reload()}
 /* --- v0.0.22: Log in / Log out button, pinned top right on every page (any element with id ftAuth) --- */
 const AUTH_ICON='<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4.2 4.3-6.3 8-6.3s6.5 2.1 8 6.3"/></svg>';
 let authBtn=null;
 async function refreshAuth(){if(!authBtn)return;let s=null;try{s=await status(true)}catch{}
  const u=s?.authenticated?s.user:null;authBtn.hidden=false;authBtn.disabled=!s;authBtn.classList.toggle('is-in',!!u);authBtn.title=u?'Logged in as '+u.name+'. Click to log out':s?'Log in':'Not connected to the server';authBtn.setAttribute('aria-label',authBtn.title);authBtn.innerHTML=AUTH_ICON+'<i></i><span>'+(u?'Log out':'Log in')+'</span>'}
 function mountAuth(){const host=document.getElementById('ftAuth');if(!host)return;authBtn=document.createElement('button');authBtn.type='button';authBtn.className='ft-auth';authBtn.innerHTML=AUTH_ICON+'<i></i><span>Log in</span>';host.replaceChildren(authBtn);
  /* Pages build themselves around the session, so reload after a change made from this button. */
  authBtn.onclick=async()=>{authBtn.disabled=true;try{const s=await status();if(!s.installed){location.href='./setup/install.php';return}if(me)await logout();else await loginBox('Use your email or team login.');location.reload()}catch{authBtn.disabled=false}};
  document.addEventListener('ft-auth',refreshAuth);refreshAuth()}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mountAuth);else mountAuth();
 async function stores(){await ensureLogin();const d=await api('dashboard');return d.data.stores}
 /* --- styles for the login box --- */
 const st=document.createElement('style');st.textContent='.ft-login{position:fixed;inset:0;top:var(--vv-top,0px);height:var(--vv-height,100%);z-index:1500;background:#0c1228cc;display:flex;align-items:center;justify-content:center;padding:16px;font:15px system-ui,-apple-system,Segoe UI,sans-serif}.ft-login-card{background:#fff;color:#14203a;border-radius:14px;padding:20px;width:min(380px,100%);box-shadow:0 16px 60px #0006;max-height:100%;overflow:auto}.ft-recover{margin-top:14px;padding-top:12px;border-top:1px solid #e3e9f2}.ft-recover[hidden]{display:none}.ft-login-actions .ft-forgot{margin-right:auto;border:0;background:none;color:#3058b2;padding:10px 0}.ft-login-card h3{margin:0 0 6px;font-size:20px}.ft-login-why{margin:0 0 10px;color:#5a6b86;font-size:13px}.ft-login-card label{display:block;font-weight:700;font-size:13px;color:#34455e;margin:10px 0 4px}.ft-login-card small{font-weight:400;color:#7a8aa3}.ft-login-card input{display:block;width:100%;box-sizing:border-box;margin-top:5px;border:1px solid #c5d1e0;border-radius:9px;padding:11px;font:16px system-ui;background:#fbfcff;color:#192746}.ft-login-err{color:#a31d34;font-size:13px;min-height:1em;margin:8px 0 0}.ft-login-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:12px}.ft-login-actions button{border:1px solid #c8d6e8;background:#fff;color:#223d60;border-radius:9px;padding:10px 14px;font-weight:750;font-size:14px;cursor:pointer}.ft-login-actions .ft-primary{background:#e02b47;border-color:#e02b47;color:#fff}#ftAuth{flex:0 0 auto}.ft-auth{position:relative;display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 13px 0 10px;border-radius:19px;border:1px solid #e02b47;background:#e02b47;color:#fff;font:700 13px system-ui,-apple-system,Segoe UI,sans-serif;white-space:nowrap;cursor:pointer}.ft-auth:hover{background:#c11f3a;border-color:#c11f3a}.ft-auth[hidden]{display:none}.ft-auth:disabled{opacity:.6;cursor:default}.ft-auth i{display:none}.ft-auth.is-in{background:#1a1a1a;border-color:#4a4a4a}.ft-auth.is-in:hover{background:#2c2c2c;border-color:#4a4a4a}.ft-auth.is-in i{display:block;position:absolute;left:23px;top:6px;width:9px;height:9px;border-radius:50%;background:#22c55e;border:2px solid #1a1a1a}@media(max-width:820px){.ft-auth{flex-direction:column;justify-content:center;gap:1px;height:44px;min-width:46px;padding:2px 6px;border-radius:10px;font-size:9px}.ft-auth.is-in i{left:calc(50% + 2px);top:3px}}@media(max-width:820px),(max-height:520px){.ft-login{padding:0;align-items:stretch}.ft-login-card{width:100%;max-height:none;border-radius:0;box-shadow:none;padding:calc(24px + var(--ft-top,0px)) 20px calc(20px + var(--ft-bottom,0px))}}.ft-login.ft-gate{z-index:1600;padding:16px;align-items:center;overflow:auto;background:#080c1a radial-gradient(90% 60% at 50% 0,#27314f 0,#080c1a 70%)}.ft-gate .ft-login-card{width:min(400px,100%);max-height:none;margin:auto;border-radius:16px;box-shadow:0 16px 60px #0008;padding:24px 22px}.ft-gate [data-cancel]{display:none}.ft-gate-brand{display:grid;justify-items:center;gap:8px;margin-bottom:14px;font-size:17px;font-weight:800;color:#14203a}.ft-gate-brand img{width:72px;height:72px;border-radius:16px;object-fit:contain}.ft-gate [data-login] h3{display:none}.ft-gate .ft-login-why{text-align:center}.ft-gate .ft-login-actions{justify-content:flex-end}';
 st.textContent+='.ft-job{position:fixed;inset:0;z-index:1450;display:flex;align-items:center;justify-content:center;padding:16px;background:#0c1228b3;font:15px system-ui,-apple-system,Segoe UI,sans-serif;color:#18233c}.ft-job [hidden]{display:none!important}.ft-job-card{width:min(440px,100%);background:#fff;border-radius:14px;padding:20px;box-shadow:0 20px 60px #0007}.ft-job h3{margin:0 0 8px;font-size:19px}.ft-job p{margin:6px 0}.ft-job-label{font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-height:1.3em}.ft-job-bar{height:12px;border-radius:7px;background:#e3e9f2;overflow:hidden;margin:10px 0}.ft-job-bar i{display:block;height:100%;width:0;background:#e02b47;transition:width .25s}.ft-job-time{font-variant-numeric:tabular-nums;color:#38465f}.ft-job-note{font-size:13px;color:#5b6b80}.ft-job ul{list-style:none;margin:8px 0 0;padding:0;display:grid;gap:5px;overflow-wrap:anywhere}.ft-job footer{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;margin-top:16px}.ft-job button{min-height:44px;padding:0 16px;border-radius:10px;border:1px solid #c6d1e0;background:#fff;color:#18233c;font:inherit;font-weight:700;cursor:pointer}.ft-job button.is-main{background:#e02b47;border-color:#e02b47;color:#fff}.ft-job button:disabled{opacity:.5}.ft-job.is-done h3{color:#138a47}.ft-job.is-failed h3{color:#c2182f}';
 (document.head||document.documentElement).append(st);
 /* --- v0.0.34: login first. Every app page shows this splash until someone is logged in, so nobody can just walk in.
    TV screens and setup don't load this file, so they keep working. This hides the app from visitors; the server already protects the data itself. --- */
 const lock=on=>document.documentElement.classList.toggle('ft-locked',on);
 function gateNote(text,label,go){lock(true);const wrap=document.createElement('div');wrap.className='ft-login ft-gate';wrap.innerHTML='<div class="ft-login-card">'+BRAND+'<p class="ft-login-why"></p><div class="ft-login-actions"><button type="button" class="ft-primary"></button></div></div>';wrap.querySelector('p').textContent=text;const button=wrap.querySelector('button');button.textContent=label;button.onclick=go;document.body.append(wrap)}
 async function gate(){let s=null;try{s=await status()}catch{}
  /* The editor loads this file in its head, so wait for the page before drawing on it. */
  if(document.readyState==='loading')await new Promise(r=>document.addEventListener('DOMContentLoaded',r,{once:true}));
  const open=()=>{gated=false;lock(false)};
  /* No server: a device that has logged in keeps working with what's saved on it. */
  if(!s)return remembered()?open():gateNote('Can’t reach the server. Check the internet and try again.','Try again',()=>location.reload());
  if(s.authenticated){remember(true);return open()}
  remember(false);
  /* Not set up yet: the TV Manager has its own first-time screens. */
  if(!s.installed){if(['tv-admin','tv-admin.html'].includes(location.pathname.split('/').pop()))return open();return gateNote('This site isn’t set up yet.','Open setup',()=>{location.href=s.backend==='unconfigured'?'./setup/install.php':'./tv-admin'})}
  lock(true);await loginBox('Log in to continue.',{gate:true});location.reload()}
 gate();
 return {team,api,status,ensureLogin,logout,refreshAuth,uploadBlob,saveDraft,listDrafts,getDraft,draftThumb,deleteDraft,listMedia,uploadMedia,mediaBlob,deleteMedia,listVideos,uploadVideo,deleteVideo,stores,job,zip,fmtSize,fmtStamp,fmtTook,get me(){return me},get maxUpload(){return maxUpload}};
})();
