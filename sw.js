/* ShopSmoke Studio v0.0.40 static-shell cache only. Pages load scripts and styles with ?v=<version>, so a new page can't run with an old cached file; keep the versions here and in the HTML the same. User media lives in IndexedDB, never in SW cache. */
const CACHE='four-tier-marketing-v0039-shell';
const SHELL=['./','./style.css?v=0.0.40','./app.js?v=0.0.40','./tv-home.js?v=0.0.40','./publish','./publish.css?v=0.0.40','./publish.js?v=0.0.40','./creator-library.js?v=0.0.40','./ftbar.css?v=0.0.40','./viewport.js?v=0.0.40','./ftapi.js?v=0.0.40','./ftvideo.js?v=0.0.40','./creator-cloud.js?v=0.0.40','./start','./editor','./manifest.webmanifest','./icons/icon-192.png','./icons/favicon-48.png','./icons/logo-160.webp','./assets/demo-menu.jpg']; // v0.0.21: home is ./ (index.html redirects there). v0.0.22: the editor is back
self.addEventListener('install',e=>{e.waitUntil(caches.open(CACHE).then(c=>c.addAll(SHELL)).then(()=>self.skipWaiting()))});
self.addEventListener('activate',e=>{e.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k.startsWith('four-tier-marketing-')&&k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim()))});
self.addEventListener('fetch',e=>{
  const r=e.request;if(r.method!=='GET')return;
  const u=new URL(r.url);if(u.origin!==self.location.origin||!u.pathname.startsWith(new URL('./',self.registration.scope).pathname))return;
  if(u.pathname.includes('/api/') || u.pathname.includes('/tv-content/') || u.pathname.includes('/tv/') || u.pathname.includes('/tv-admin') || /\/(team|add-store|add-tv|rename-tv|tv-history)$/.test(u.pathname) || u.pathname.includes('/publish') || u.pathname.includes('/setup/') || /-tv-[0-9]+\/?$/.test(u.pathname))return;
  if(r.mode==='navigate'){
    e.respondWith(fetch(r,{cache:'no-cache'}).then(resp=>{if(resp.ok){const copy=resp.clone();caches.open(CACHE).then(c=>c.put(r,copy))}return resp}).catch(()=>caches.match(r).then(hit=>hit||caches.match('./'))));
  }else e.respondWith(caches.match(r).then(cached=>cached||fetch(r)));
});
