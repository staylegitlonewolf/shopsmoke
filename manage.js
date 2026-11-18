'use strict';
/* v0.0.31: forms that used to be popups, each on its own page, because popups were hard to use on a phone.
   This one page serves five addresses: /team, /add-store, /add-tv, /rename-tv and /tv-history.
   Saving goes back to the right panel of the TV Manager, which shows the message left in ft-manager-said. */
(()=>{
 const $=id=>document.getElementById(id),ESC=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const {PERMS,PERM_LABEL,PRESETS}=FT.team,query=new URLSearchParams(location.search),route=location.pathname.split('/').pop().toLowerCase();
 const note=(text,kind='')=>{const n=$('pageNote');n.className='notice '+kind;n.textContent=text;n.hidden=!text};
 let toastTimer=0;const toast=text=>{const el=$('toast');el.textContent=text;el.classList.add('show');clearTimeout(toastTimer);toastTimer=setTimeout(()=>el.classList.remove('show'),3000)};
 const manager=(panel,store)=>'./tv-admin'+(store?'?store='+encodeURIComponent(store):'')+'#'+panel;
 const leave=(url,said)=>{try{sessionStorage.setItem('ft-manager-said',said)}catch{}location.href=url};
 const can=(data,perm)=>data.me.role==='owner'||(data.me.perms||[]).includes(perm);
 const backTo=(url,label)=>{const a=$('pageBack');a.href=url;a.textContent='← '+label};
 /* save() either leaves the page or draws what comes next. If it throws, the message shows here and what was typed stays. */
 function showForm(title,fields,label,cancelTo,save){const form=$('pageForm');$('pageTitle').textContent=title;
  form.innerHTML=`${fields}<p id="formError" class="notice error" role="alert" hidden></p><div class="form-actions"><a class="form-cancel" href="${ESC(cancelTo)}">Cancel</a><button class="primary" id="formSave" type="submit">${ESC(label)}</button></div>`;
  form.onsubmit=async e=>{e.preventDefault();const button=$('formSave'),error=$('formError');button.disabled=true;button.textContent='Saving…';error.hidden=true;
   try{await save()}catch(ex){error.textContent=ex.message;error.hidden=false;error.scrollIntoView({block:'center'});button.disabled=false;button.textContent=label}};
  note('');form.hidden=false}

 function team(data){if(data.me.role!=='owner')return note('Only the owner can add or change team accounts.','error');
  const id=query.get('id')||'',u=id?(data.users||[]).find(x=>x.id===id):null;if(id&&!u)return note('That account no longer exists.','error');
  const isNew=!u,to=manager('teamPanel'),form=$('pageForm');backTo(to,'Team accounts');
  showForm(isNew?'Add a person':'Edit '+u.name,`<label>Name<input id="uName" maxlength="60" autocomplete="off" value="${ESC(u?.name)}" placeholder="e.g. Maria Lopez"></label>
<label>Phone number or username <small>(used to sign in)</small><input id="uUser" maxlength="32" inputmode="text" autocomplete="off" autocapitalize="off" spellcheck="false" value="${ESC(u?.phone||u?.username)}" placeholder="(305) 555-1234"></label>
<label>Role<select id="uRole"><option value="staff">Staff — upload photos, owner approves</option><option value="manager">Manager — everything in their stores</option><option value="custom">Custom — pick below</option></select></label>
<fieldset class="perm-checks"><legend>What they can do</legend>${PERMS.map(p=>`<label class="inline"><input type="checkbox" value="${p}"> ${ESC(PERM_LABEL[p])}</label>`).join('')}<p class="form-note">Only the owner can manage stores, team accounts, the deployment check, delete TVs and clear activity.</p></fieldset>
<fieldset class="store-checks"><legend>Stores</legend>${data.stores.map(s=>`<label class="inline"><input type="checkbox" value="${ESC(s.id)}" ${u?.stores?.includes(s.id)?'checked':''}> ${ESC(s.name)}</label>`).join('')}</fieldset>
<label>${isNew?'Password (4 to 20 characters)':'New password (leave blank to keep)'}<input id="uPass" type="password" autocomplete="new-password"></label>
${isNew?'':`<label class="inline"><input type="checkbox" id="uActive" ${u.active?'checked':''}> Account active (untick to block sign-in)</label>`}
<p class="form-note">Share the username and password with the person privately. They sign in on any page with Username + Password.</p>`,isNew?'Create account':'Save changes',to,async()=>{const name=$('uName').value.trim();
   await FT.api('user_save',{data:{id:u?.id||'',name,username:$('uUser').value.trim(),role:$('uRole').value,perms:boxes.filter(b=>b.checked).map(b=>b.value),stores:[...form.querySelectorAll('.store-checks input:checked')].map(i=>i.value),password:$('uPass').value,active:isNew?true:$('uActive').checked}});
   leave(to,isNew?'✓ Account created for '+name:'✓ Changes saved for '+name)});
  $('uRole').value=u?.role||'staff';const boxes=[...form.querySelectorAll('.perm-checks input')],setPerms=list=>boxes.forEach(b=>b.checked=list.includes(b.value));setPerms(u?.perms||PRESETS[$('uRole').value]||[]);
  $('uRole').onchange=()=>{if(PRESETS[$('uRole').value])setPerms(PRESETS[$('uRole').value])};
  boxes.forEach(b=>b.onchange=()=>{const now=boxes.filter(x=>x.checked).map(x=>x.value).sort().join(),match=Object.entries(PRESETS).find(([,l])=>l.slice().sort().join()===now);$('uRole').value=match?match[0]:'custom'})}

 /* v0.0.24: the owner only types the name. The web path and TV links are made from it and can't be edited, so they can't go wrong. */
 const slugify=s=>s.normalize('NFKD').replace(/[̀-ͯ]/g,'').toLowerCase().replace(/&/g,' and ').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,60).replace(/-+$/,'');
 function addStore(data){if(data.me.role!=='owner')return note('Only the owner can add stores.','error');const to=manager('storesPanel');backTo(to,'Stores');
  const freeSlug=name=>{const base=slugify(name);let slug=base,n=2;while(base&&data.stores.some(st=>st.id===slug))slug=base+'-'+n++;return slug};
  showForm('Add a store',`<label>Display name<input id="storeName" placeholder="e.g. Galaxy Kendall" maxlength="80" autocomplete="off"></label><p class="form-note" id="storeHint">The web path and TV links are made from the name.</p>`,'Create store',to,async()=>{const name=$('storeName').value.trim(),slug=freeSlug(name);if(!slug)throw Error('Enter a store name');const {store}=await FT.api('store_add',{data:{name,slug}});leave(manager('storesPanel',store.id),'✓ Store created')});
  $('storeName').oninput=()=>{const slug=freeSlug($('storeName').value);$('storeHint').textContent=slug?'Web path /'+slug+'/ · TV links /'+slug+'-tv-1/':'The web path and TV links are made from the name.'};$('storeName').focus()}

 function addTV(data){if(!can(data,'tvs'))return note('Your login can’t add TVs. Ask the owner.','error');const st=data.stores.find(s=>s.id===query.get('store'));if(!st)return note('That store no longer exists.','error');
  const to=manager('tvPanel',st.id),form=$('pageForm');backTo(to,'TV screens');
  showForm('Add TV to '+st.name,`<label>TV name<input id="tvName" value="Front Counter TV" maxlength="70"></label><p class="form-note">A permanent URL will be assigned automatically, e.g. /${ESC(st.id)}-tv-1/.</p>`,'Create TV',to,async()=>{const {screen}=await FT.api('screen_add',{data:{store_id:st.id,name:$('tvName').value}});
   $('pageTitle').textContent='TV ready';form.onsubmit=e=>e.preventDefault();form.innerHTML=`<p class="form-note">Your TV browser can open this unique URL. It will display a waiting screen until you publish a menu.</p><code class="tv-url">${ESC(location.origin+screen.url)}</code><p class="form-note">If the clean URL shows a 404, use the fallback: <code class="form-code">${ESC(location.origin+screen.fallback_url)}</code></p><div class="form-actions"><button class="primary" id="formDone" type="button">Done</button></div>`;$('formDone').onclick=()=>leave(to,'✓ TV added')})}

 function renameTV(data){if(!can(data,'tvs'))return note('Your login can’t rename TVs. Ask the owner.','error');const sc=data.screens.find(s=>s.id===query.get('tv'));if(!sc)return note('That TV no longer exists.','error');
  const to=manager('tvPanel',sc.store_id);backTo(to,'TV screens');
  showForm('Rename TV',`<label>TV name<input id="tvName" value="${ESC(sc.name)}" maxlength="70"></label>`,'Save name',to,async()=>{await FT.api('screen_update',{data:{id:sc.id,name:$('tvName').value}});leave(to,'✓ TV renamed')})}

 /* v0.0.10: revision history and rollback for one TV. v0.0.12: delete old menus and save pages to Recent photos.
    v0.0.31: "are you sure?" opens inside the row, so this page has no popups at all. */
 const stateLabel=r=>r.state==='live'?'<span class="small-status live">On air</span>':r.state==='scheduled'?`<span class="small-status pending">Scheduled ${ESC(new Date(r.scheduled_at).toLocaleString())}</span>`:'<span class="small-status">Previous</span>';
 function tvHistory(data){const sc=data.screens.find(s=>s.id===query.get('tv'));if(!sc)return note('That TV no longer exists.','error');
  backTo(manager('tvPanel',sc.store_id),'TV screens');$('pageTitle').textContent='History · '+sc.name;note('');
  const revs=sc.revisions||[],list=$('pageList'),when=r=>new Date(r.created_at).toLocaleString();
  list.innerHTML=revs.length?`<p class="form-note">Most recent ${revs.length} revision(s) for <code>${ESC(sc.slug)}</code>. Putting a previous menu back on air, or deleting one, affects this TV only.</p><div class="history">${revs.map(r=>`<div class="history-row" data-row="${ESC(r.id)}">${r.thumb?`<img src="${ESC(r.thumb)}" alt="">`:'<div class="empty-thumb">Not yet public</div>'}<div><b>${ESC(when(r))}</b><br>${r.pages} page(s) · ${Math.round(r.seconds)} s loop · ${r.mode==='schedule'?'scheduled':'published now'}<br>${stateLabel(r)}</div><div class="history-actions">${r.state==='previous'&&can(data,'publish')?`<button type="button" data-rev="${ESC(r.id)}">Put back on TV</button>`:''}${r.images?.length&&window.FTLibrary?`<button type="button" data-save="${ESC(r.id)}">Save to Recent photos</button>`:''}${r.state==='previous'&&can(data,'history')?`<button type="button" data-del="${ESC(r.id)}" class="danger-outline">🗑 Delete</button>`:''}</div></div>`).join('')}</div>`:'<p class="form-note">No menus have been published to this TV yet.</p>';list.hidden=false;
  const find=b=>revs.find(r=>r.id===(b.dataset.rev||b.dataset.save||b.dataset.del));
  const redraw=async said=>{const d=await FT.api('dashboard');tvHistory(d.data);toast(said)};
  const ask=(b,text,yes,danger,run)=>{const box=b.closest('.history-actions');box.innerHTML=`<p class="history-ask">${text}</p><button type="button" data-no>Cancel</button><button type="button" class="${danger?'danger-outline':'primary'}" data-yes>${ESC(yes)}</button>`;box.querySelector('[data-no]').onclick=()=>tvHistory(data);
   const go=box.querySelector('[data-yes]');go.onclick=async()=>{go.disabled=true;try{await run()}catch(e){go.disabled=false;toast(e.message)}}};
  list.querySelectorAll('[data-rev]').forEach(b=>b.onclick=()=>{const r=find(b);ask(b,`<b>${ESC(sc.name)}</b> will switch to the ${r.pages}-page menu from ${ESC(when(r))}. Online TVs pick it up within about a second. Pending schedules for this TV stay as they are.`,'Put back on TV',false,async()=>{await FT.api('revision_activate',{data:{screen_id:sc.id,revision_id:r.id}});await redraw('✓ Previous menu is back on air')})});
  list.querySelectorAll('[data-del]').forEach(b=>b.onclick=()=>{const r=find(b);ask(b,`Delete the ${r.pages}-page menu from ${ESC(when(r))}? <b class="warn">This cannot be undone.</b> Its images are removed from the server. Want a copy? Choose Cancel, then Save to Recent photos first.`,'Delete menu',true,async()=>{const out=await FT.api('revision_delete',{data:{screen_id:sc.id,revision_id:r.id}});await redraw('✓ Menu deleted from history'+(out.deleted_images?' ('+out.deleted_images+' image(s) removed)':''))})});
  list.querySelectorAll('[data-save]').forEach(b=>b.onclick=async()=>{const r=find(b);b.disabled=true;b.textContent='Saving…';
   try{let n=0;for(const [i,src] of r.images.entries()){const res=await fetch(src,{cache:'no-store'});if(!res.ok)throw Error('Could not download page '+(i+1));await FTLibrary.save(await res.blob(),{storeId:sc.store_id,name:sc.slug+' '+new Date(r.created_at).toLocaleDateString('en-CA')+' page '+(i+1)+'.jpg',note:'Saved from TV Manager history: '+sc.name+', published '+when(r)});n++}
    b.textContent='✓ Saved';toast(n+' page(s) saved to Recent photos on this device')}catch(e){b.disabled=false;b.textContent='Save to Recent photos';toast(e.message)}})}

 const PAGES={team:[team,'Log in to manage team accounts.'],'add-store':[addStore,'Log in to add a store.'],'add-tv':[addTV,'Log in to add a TV.'],'rename-tv':[renameTV,'Log in to rename this TV.'],'tv-history':[tvHistory,'Log in to see this TV’s history.']};
 async function start(){const [draw,why]=PAGES[route]||[];if(!draw)return location.replace('./tv-admin');$('pageLogin').hidden=true;note('Loading…');
  try{await FT.ensureLogin(why)}catch(e){const notSetUp=/set up/i.test(e?.message||'');note(notSetUp?e.message:why,notSetUp?'error':'');$('pageLogin').hidden=notSetUp;return}
  try{const {data}=await FT.api('dashboard');draw(data)}catch(e){note(e.message,'error')}}
 $('pageLogin').onclick=start;start();
})();
