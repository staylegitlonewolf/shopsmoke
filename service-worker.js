/* Kill switch for the PREVIOUS coolwebsitedude.com website's service worker.
   Browsers that visited the old site still run its /service-worker.js, which answers
   requests with 404s. Browsers re-check that script URL on every visit; finding this file,
   they install it, it deletes the old site's caches, unregisters itself and reloads the page,
   so the ShopSmoke app (which uses ./sw.js) loads normally. It never touches ShopSmoke caches and has
   no fetch handler. Safe to delete from the server after a few months. */
self.addEventListener('install',()=>self.skipWaiting());
self.addEventListener('activate',event=>{event.waitUntil((async()=>{
 try{const keys=await caches.keys();await Promise.all(keys.filter(k=>!k.startsWith('four-tier-marketing-')).map(k=>caches.delete(k)))}catch(e){} // best effort: never block the unregister
 await self.registration.unregister();
 await self.clients.claim().catch(()=>{});
 const windows=await self.clients.matchAll({type:'window'});
 await Promise.all(windows.map(c=>c.navigate(c.url).catch(()=>null)));
})())});
