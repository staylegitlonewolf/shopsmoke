'use strict';
/* v0.0.24.1: turns the timeline into one MP4 file, right in the browser.
   A photo holds still, so a hold only needs about one frame a second; transitions run at 30 frames a second.
   That is why 100 photos take minutes to make, not the 20 minutes the show lasts.
   A browser without the H.264 encoder (VideoEncoder) records the show as it plays instead, which takes as long as the show. */
window.FTVideo=(()=>{
 const W=1920,H=1080,FPS=30,TICKS=90000,CODECS=['avc1.640028','avc1.4d0028','avc1.42e028'];
 const fast=typeof VideoEncoder==='function'&&typeof VideoFrame==='function';
 const canRecord=typeof MediaRecorder==='function'&&!!HTMLCanvasElement.prototype.captureStream;
 const supported=()=>fast||canRecord;
 const canvas=()=>{const c=document.createElement('canvas');c.width=W;c.height=H;return c};
 const wait=ms=>new Promise(r=>setTimeout(r,ms));
 const gap=(p,last)=>last||p.transition==='none'?0:p.transitionDuration;
 const showLength=pages=>pages.reduce((t,p,i)=>t+p.duration+gap(p,i===pages.length-1),0);
 /* The same four transitions as Preview and the TV. */
 function blend(x,a,b,kind,r){x.globalAlpha=1;x.fillStyle='#000';x.fillRect(0,0,W,H);
  if(kind==='fade'){x.drawImage(a,0,0);x.globalAlpha=r;x.drawImage(b,0,0)}
  else if(kind==='slide-left'){x.drawImage(a,-W*r,0);x.drawImage(b,W*(1-r),0)}
  else if(kind==='slide-up'){x.drawImage(a,0,-H*r);x.drawImage(b,0,H*(1-r))}
  else if(kind==='zoom'){x.globalAlpha=1-r;x.drawImage(a,-W*.08*r,-H*.08*r,W*(1+.16*r),H*(1+.16*r));x.globalAlpha=r;x.drawImage(b,W*.06*(1-r),H*.06*(1-r),W*(.88+.12*r),H*(.88+.12*r))}
  else x.drawImage(b,0,0);
  x.globalAlpha=1}
 /* --- the MP4 file itself: header first, so a TV can start playing before the whole file has arrived --- */
 const u32=n=>[n>>>24&255,n>>>16&255,n>>>8&255,n&255],u16=n=>[n>>>8&255,n&255],txt=s=>[...s].map(c=>c.charCodeAt(0));
 function box(type,...parts){const flat=parts.map(p=>p instanceof Uint8Array?p:Uint8Array.from(p)),size=flat.reduce((t,p)=>t+p.length,8),out=new Uint8Array(size);out.set(u32(size));out.set(txt(type),4);let at=8;for(const p of flat){out.set(p,at);at+=p.length}return out}
 const full=(type,flags,...parts)=>box(type,[0,0,0,flags],...parts);
 const MATRIX=[0x00010000,0,0,0,0x00010000,0,0,0,0x40000000].flatMap(u32),zeros=n=>new Array(n).fill(0);
 const pairs=runs=>[...u32(runs.length),...runs.flatMap(([n,v])=>[...u32(n),...u32(v)])];
 function runLength(list){const runs=[];for(const v of list){const r=runs[runs.length-1];if(r&&r[1]===v)r[0]++;else runs.push([1,v])}return runs}
 function moov(samples,avcC,end,offset){const ticks=us=>Math.round(us*TICKS/1e6),order=samples.map(s=>s.pts).sort((a,b)=>a-b),n=samples.length;
  const lengths=order.map((p,i)=>Math.max(1,ticks(i<n-1?order[i+1]:end)-ticks(p))),total=lengths.reduce((a,b)=>a+b,0);
  const shifts=samples.map((s,i)=>ticks(s.pts)-ticks(order[i])),lift=Math.max(0,-Math.min(...shifts)),reordered=shifts.some(v=>v!==0);
  const entry=box('avc1',zeros(6),u16(1),zeros(16),u16(W),u16(H),u32(0x00480000),u32(0x00480000),zeros(4),u16(1),zeros(32),u16(24),[255,255],box('avcC',avcC));
  const keys=samples.flatMap((s,i)=>s.key?[i+1]:[]);
  const stbl=box('stbl',full('stsd',0,u32(1),entry),full('stts',0,pairs(runLength(lengths))),...(reordered?[full('ctts',0,pairs(runLength(shifts.map(v=>v+lift))))]:[]),full('stss',0,u32(keys.length),keys.flatMap(u32)),full('stsc',0,u32(1),u32(1),u32(n),u32(1)),full('stsz',0,u32(0),u32(n),samples.flatMap(s=>u32(s.size))),full('stco',0,u32(1),u32(offset)));
  const minf=box('minf',full('vmhd',1,zeros(8)),box('dinf',full('dref',0,u32(1),full('url ',1))),stbl);
  const mdia=box('mdia',full('mdhd',0,u32(0),u32(0),u32(TICKS),u32(total),u16(0x55c4),u16(0)),full('hdlr',0,u32(0),txt('vide'),zeros(12),txt('ShopSmoke video'),[0]),minf);
  const tkhd=full('tkhd',3,u32(0),u32(0),u32(1),u32(0),u32(total),zeros(8),u16(0),u16(0),u16(0),u16(0),MATRIX,u32(W<<16),u32(H<<16));
  return box('moov',full('mvhd',0,u32(0),u32(0),u32(TICKS),u32(total),u32(0x00010000),u16(0x0100),zeros(10),MATRIX,zeros(24),u32(2)),box('trak',tkhd,mdia))}
 function fileHead(samples,avcC,end){const ftyp=box('ftyp',txt('isom'),u32(512),txt('isommp42avc1')),size=moov(samples,avcC,end,0).length,start=ftyp.length+size+8,body=samples.reduce((t,s)=>t+s.size,0);return [ftyp,moov(samples,avcC,end,start),Uint8Array.from([...u32(body+8),...txt('mdat')])]}
 /* Aim for about 200 MB however long the show is, so it stays well under the 300 MB upload limit. */
 const bitrateFor=pages=>{const frames=pages.reduce((n,p,i)=>n+Math.ceil(p.duration)+Math.round(gap(p,i===pages.length-1)*FPS),0);return Math.round(Math.max(3e6,Math.min(16e6,200*1048576*8*FPS/(frames*.85))))};
 async function pickConfig(bitrate){for(const codec of CODECS){const config={codec,width:W,height:H,bitrate,framerate:FPS,avc:{format:'avc'},latencyMode:'quality'};try{if((await VideoEncoder.isConfigSupported(config)).supported)return config}catch{}}return null}
 /* --- the fast way: the browser's H.264 encoder --- */
 async function encode({pages,paint,onProgress,cancelled}){const config=await pickConfig(bitrateFor(pages));if(!config)throw Error('No H.264 encoder here');
  const samples=[],stored=[];let held=[],heldSize=0,avcC=null,failed=null;
  const store=()=>{if(held.length){stored.push(new Blob(held));held=[];heldSize=0}};
  const enc=new VideoEncoder({output:(chunk,meta)=>{const d=meta?.decoderConfig?.description;if(d&&!avcC)avcC=new Uint8Array(d.buffer||d,d.byteOffset||0,d.byteLength);const data=new Uint8Array(chunk.byteLength);chunk.copyTo(data);samples.push({pts:chunk.timestamp,key:chunk.type==='key',size:data.length});held.push(data);heldSize+=data.length;if(heldSize>16777216)store()},error:e=>{failed=e}});
  enc.configure(config);
  const total=showLength(pages),out=canvas(),x=out.getContext('2d');let cur=canvas(),next=canvas(),t=0;
  const frame=async(source,at,length,keyFrame)=>{if(failed)throw failed;const f=new VideoFrame(source,{timestamp:Math.round(at*1e6),duration:Math.round(length*1e6)});enc.encode(f,{keyFrame});f.close();while(enc.encodeQueueSize>4){if(failed)throw failed;await wait(4)}};
  try{await paint(0,cur);
   for(let i=0;i<pages.length;i++){if(cancelled?.())return null;const p=pages[i],last=i===pages.length-1,td=gap(p,last);onProgress?.(t/total,i);
    for(let s=0;s<p.duration;s++)await frame(cur,t+s,Math.min(1,p.duration-s),s===0);
    t+=p.duration;if(last)break;await paint(i+1,next);
    const steps=Math.round(td*FPS);for(let k=0;k<steps;k++){blend(x,cur,next,p.transition,(k+1)/(steps+1));await frame(out,t+k/FPS,1/FPS,false)}
    t+=td;[cur,next]=[next,cur]}
   await enc.flush();if(failed)throw failed;store();if(!samples.length||!avcC)throw Error('The encoder gave no video');
   onProgress?.(1,pages.length-1);return {blob:new Blob([...fileHead(samples,avcC,Math.round(t*1e6)),...stored],{type:'video/mp4'}),ext:'mp4',seconds:t}}
  finally{if(enc.state!=='closed')enc.close();cur.width=next.width=out.width=0}}
 /* --- the slow way for older browsers: record the show as it plays --- */
 async function record({pages,paint,onProgress,cancelled}){const type=['video/mp4;codecs=avc1.640028','video/mp4','video/webm;codecs=vp9','video/webm'].find(v=>MediaRecorder.isTypeSupported(v));if(!type)throw Error('This browser can’t make videos');
  const total=showLength(pages),out=canvas(),x=out.getContext('2d'),parts=[];let cur=canvas(),next=canvas(),t=0;await paint(0,cur);x.drawImage(cur,0,0);
  const stream=out.captureStream(FPS),rec=new MediaRecorder(stream,{mimeType:type,videoBitsPerSecond:8000000}),stopped=new Promise(r=>{rec.onstop=r});rec.ondataavailable=e=>{if(e.data.size)parts.push(e.data)};rec.start(1000);
  const until=async(ms,draw,step)=>{const start=performance.now();for(;;){const r=(performance.now()-start)/ms;if(r>=1||cancelled?.())return;draw(r);await wait(step)}};
  try{for(let i=0;i<pages.length;i++){const p=pages[i],last=i===pages.length-1,td=gap(p,last);onProgress?.(t/total,i);if(!last)await paint(i+1,next);
    await until(p.duration*1000,()=>x.drawImage(cur,0,0),200);t+=p.duration;if(cancelled?.())break;if(last)break;
    await until(td*1000,r=>blend(x,cur,next,p.transition,r),1000/FPS);t+=td;[cur,next]=[next,cur]}}
  finally{rec.stop();await stopped;stream.getTracks().forEach(k=>k.stop())}
  if(cancelled?.())return null;onProgress?.(1,pages.length-1);return {blob:new Blob(parts,{type:type.split(';')[0]}),ext:type.includes('mp4')?'mp4':'webm',seconds:t}}
 async function make(job){if(fast)try{return await encode(job)}catch(e){if(job.cancelled?.()||!canRecord)throw e;console.warn('Fast video failed, recording instead',e)}return record(job)}
 return {supported,make,fast,showLength};
})();
