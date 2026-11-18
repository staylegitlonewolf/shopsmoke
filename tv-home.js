'use strict';
// Lightweight status panel within the existing Creator app. No changes to local
// media/draft data and no secrets shared with untrusted/public pages.
(()=>{
const home=document.getElementById('homeView'),store=document.getElementById('storesView');
if(!home||!store)return;
const section=document.createElement('section');section.className='tv-home-card';section.innerHTML='<div class="tv-home-head"><div><h2>📺 TV schedule</h2></div><div class="button-row"><a href="./tv-admin" class="btn outline">Manage TVs ↗</a></div></div><div id="tvHomeStatus">Checking TV publishing status…</div><div id="tvHomeUpcoming"></div>';
home.querySelector('.quick-stats')?.insertAdjacentElement('afterend',section);
const preview=document.createElement('section');preview.className='tv-home-card';preview.innerHTML='<div class="tv-home-head"><div><h2>🖥 Store TVs</h2></div><a href="./tv-admin" class="btn primary">Open TV Manager ↗</a></div><div id="tvStoreInfo">Store website previews remain separate from TV publishing.</div>';
store.querySelector('.page-title')?.insertAdjacentElement('afterend',preview);
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));
async function call(action){const r=await fetch('./api/index.php?action='+encodeURIComponent(action),{cache:'no-store',credentials:'same-origin'}),j=await r.json();if(!r.ok||!j.ok)throw Error(j.error||'Server error');return j}
async function refresh(){const label=document.getElementById('tvHomeStatus'),list=document.getElementById('tvHomeUpcoming');try{const status=await call('status');if(!status.installed){const flag=document.querySelector('.quick-stats .status-text');if(flag)flag.textContent='OFF';label.textContent='TV publishing is locked until secure cPanel setup is completed.';list.innerHTML='';return}if(!status.authenticated){const flag=document.querySelector('.quick-stats .status-text');if(flag)flag.textContent='ONLINE';label.textContent='TV publishing server online — log in to view private schedules.';list.innerHTML='';return}const {data}=await call('dashboard');const flag=document.querySelector('.quick-stats .status-text');if(flag)flag.textContent='ONLINE';const pending=data.schedules.filter(s=>s.status==='pending').sort((a,b)=>Date.parse(a.at)-Date.parse(b.at));label.textContent=`${data.screens.length} registered TV(s) across ${data.stores.length} stores · ${pending.length} upcoming update(s)`;list.innerHTML=pending.slice(0,6).map(s=>{const tv=data.screens.find(t=>t.id===s.screen_id),st=data.stores.find(t=>t.id===tv?.store_id);return `<div class="tv-home-item"><b>${esc(st?.name||'Store')}</b> · ${esc(tv?.name||'Screen')} <span>${esc(new Date(s.at).toLocaleString())}</span></div>`}).join('')||'<p class="tv-home-empty">No scheduled updates at the moment.</p>';
 document.getElementById('tvStoreInfo').textContent=`${data.screens.filter(t=>t.store_id===localStorage.getItem('4tier-selected-store')).length} TV(s) registered for the selected store.`;
 }catch(e){label.textContent='Publishing service unavailable — Creator designs remain local until server configuration is completed.';list.innerHTML='';}}
refresh();setInterval(()=>{if(!document.hidden)refresh()},60000);
})();
