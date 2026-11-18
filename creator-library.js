'use strict';
/* v0.0.12: save an image into the Creator's on-device Media Library ("Recent photos").
   Same IndexedDB database, stores and record shape as app.js, so Quick Publish slides and
   TV Manager history pages show up in the Creator for that store. Device-local only. */
window.FTLibrary=(()=>{
 const DB_NAME='fourTierMarketingPWA',DB_VERSION=1;
 const open=()=>new Promise((res,rej)=>{const r=indexedDB.open(DB_NAME,DB_VERSION);r.onupgradeneeded=()=>{const db=r.result;for(const n of ['assets','folders','drafts'])if(!db.objectStoreNames.contains(n))db.createObjectStore(n,{keyPath:'id'})};r.onsuccess=()=>res(r.result);r.onerror=()=>rej(r.error)});
 const put=(db,table,item)=>new Promise((res,rej)=>{const tx=db.transaction(table,'readwrite');tx.objectStore(table).put(item);tx.oncomplete=res;tx.onerror=()=>rej(tx.error)});
 const get=(db,table,key)=>new Promise((res,rej)=>{const r=db.transaction(table,'readonly').objectStore(table).get(key);r.onsuccess=()=>res(r.result);r.onerror=()=>rej(r.error)});
 const uid=()=>'asset-'+(crypto.randomUUID?.()||Date.now().toString(36)+'-'+Math.random().toString(36).slice(2));
 async function thumb(blob){const u=URL.createObjectURL(blob);try{const img=await new Promise((ok,no)=>{const i=new Image();i.onload=()=>ok(i);i.onerror=()=>no(Error('Image failed to decode'));i.src=u});
  const s=Math.min(1,550/Math.max(img.width,img.height)),c=document.createElement('canvas');c.width=Math.max(1,Math.round(img.width*s));c.height=Math.max(1,Math.round(img.height*s));c.getContext('2d').drawImage(img,0,0,c.width,c.height);
  return await new Promise((ok,no)=>c.toBlob(b=>b?ok(b):no(Error('Thumbnail failed')),'image/jpeg',.78))}finally{URL.revokeObjectURL(u)}}
 /** save(blob,{storeId,name,note,id?,folderName?,thumb?,createdAt?,byName?}) → the asset id. v0.0.17: id/folder/thumb let server sync keep one copy per photo. v0.0.22: byName = who added it (the logged-in person by default). */
 async function save(blob,{storeId,name,note,id,folderName,thumb:givenThumb,createdAt,byName}){if(!blob||!/^image\//.test(blob.type))throw Error('Not an image');if(!storeId)throw Error('No store selected');
  const db=await open();try{let folderId='unsorted-'+storeId;if(!await get(db,'folders',folderId))await put(db,'folders',{id:folderId,storeId,name:'Unsorted',createdAt:Date.now(),locked:true});
  if(folderName){const all=await new Promise((res,rej)=>{const r=db.transaction('folders','readonly').objectStore('folders').getAll();r.onsuccess=()=>res(r.result);r.onerror=()=>rej(r.error)});const match=all.find(f=>f.storeId===storeId&&f.name===folderName);if(match)folderId=match.id;else{folderId='folder-'+uid().slice(6);await put(db,'folders',{id:folderId,storeId,name:folderName,createdAt:Date.now()})}}
  const now=Date.now(),asset={id:id||uid(),storeId,folderId,name:String(name||'TV slide').replace(/[^\w .()-]+/g,'-').slice(0,100),sku:'',note:String(note||'').slice(0,180),byName:String(byName??window.FT?.me?.name??''),type:blob.type,size:blob.size,blob,thumb:givenThumb||await thumb(blob),createdAt:createdAt||now,updatedAt:now};
  await put(db,'assets',asset);return asset.id}finally{db.close()}}
 async function list(storeId){const db=await open();try{const [assets,folders]=await Promise.all(['assets','folders'].map(t=>new Promise((res,rej)=>{const r=db.transaction(t,'readonly').objectStore(t).getAll();r.onsuccess=()=>res(r.result);r.onerror=()=>rej(r.error)})));return {assets:assets.filter(a=>storeId==='all'||a.storeId===storeId),folders}}finally{db.close()}}
 /** v0.0.22: the editor's Folders → Photos tab can start a folder too. Reuses a folder of the same name. */
 async function addFolder(storeId,name){const db=await open();try{const all=await new Promise((res,rej)=>{const r=db.transaction('folders','readonly').objectStore('folders').getAll();r.onsuccess=()=>res(r.result);r.onerror=()=>rej(r.error)});const hit=all.find(f=>f.storeId===storeId&&f.name===name);if(hit)return hit;const f={id:'folder-'+uid().slice(6),storeId,name,createdAt:Date.now()};await put(db,'folders',f);return f}finally{db.close()}}
 return {save,list,addFolder};
})();
