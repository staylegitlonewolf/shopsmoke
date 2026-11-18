'use strict';
// Public browser player: checks for a new published revision every second (v0.0.18; was 15 s),
// reloads safely every 15 minutes, and keeps the previous slides while offline.
// v0.0.10: real slide-left / slide-up / zoom transitions matching the editor's
// Play Preview, a check-in heartbeat (the revision this TV is showing) for the
// owner's TV Manager, and a TV identity line on the waiting screen.
// v0.0.37: written in older JavaScript (var, function, XMLHttpRequest; no arrow functions, fetch,
// async/await or ?.) so older smart-TV browsers can run it too, for example LG TVs from 2016 to 2021.
// Newer syntax made those browsers skip the whole file, which left the TV on "Waiting for your first published menu…".
(function(){
var config=JSON.parse(document.getElementById('ftscreen').textContent);var slug=config.slug;
var script=document.currentScript||(function(){var all=document.getElementsByTagName('script');return all[all.length-1]})();
var src=script.src,cut=src.lastIndexOf('/tv/');var apiURL=(cut>=0?src.slice(0,cut):'')+'/api/index.php';
function $(id){return document.getElementById(id)}
var currentVersion=null,pages=[],index=0,timer=null,fadeTimer=null,loading=false,loadingSince=0,lastGoodFetch=0;
var blank=$('blank'),stage=$('stage'),error=$('error');
var front=$('current'),back=$('next');
/* v0.0.22: a page can carry a video (uploaded file, https link, YouTube or Vimeo). It plays muted on top of the page's
   JPEG, which stays underneath: if this TV can't play the video, the JPEG shows for the page's time. */
var vid=$('video'),embed=$('embed');var playing='',nextImage=null,clipStart=0,clipEnd=0,reloadDue=false;
function embedURL(v){var id=encodeURIComponent(v.id||''),from=Math.floor(v.start||0),to=Math.ceil(v.end||0);if(v.kind==='youtube')return 'https://www.youtube-nocookie.com/embed/'+id+'?autoplay=1&mute='+(v.sound?0:1)+'&controls=0&playsinline=1&rel=0'+(from||to?(from?'&start='+from:'')+(to?'&end='+to:''):'&loop=1&playlist='+id);if(v.kind==='vimeo')return 'https://player.vimeo.com/video/'+id+'?autoplay=1&loop=1&'+(v.sound?'muted=0&controls=0':'muted=1&background=1')+(from?'#t='+from+'s':'');return ''}
/* v0.0.37: older browsers return nothing from play(), newer ones a promise that can be refused. */
function tryPlay(onFail){var p;try{p=vid.play()}catch(e){onFail();return}if(p&&typeof p.then==='function')p.then(null,onFail)}
function stopVideo(){if(!playing)return;playing='';vid.hidden=true;vid.pause();vid.removeAttribute('src');vid.load();embed.hidden=true;embed.removeAttribute('src')}
function startVideo(p){var v=p.video,key=v?JSON.stringify(v):'';if(key===playing)return;stopVideo();if(!v)return;playing=key;var url=embedURL(v);
 if(url){embed.src=url;embed.hidden=false;return}
 clipStart=Number(v.start)||0;clipEnd=Number(v.end)||0;vid.src=v.src||v.url;vid.muted=!v.sound;vid.hidden=false;
 tryPlay(function(){if(vid.muted){vid.hidden=true;return}vid.muted=true;tryPlay(function(){vid.hidden=true})})} /* v0.0.26: a TV that refuses sound still plays the video, silently */
/* v0.0.24: a cut video plays from Start to End. On its own it loops; with other pages it moves on. */
function clipDone(){if(reloadDue){reloadDue=false;freshStart()}if(pages.length>1){clearTimeout(timer);turn()}else{vid.currentTime=clipStart;tryPlay(function(){})}}
vid.addEventListener('loadedmetadata',function(){if(clipStart)vid.currentTime=clipStart});
vid.addEventListener('timeupdate',function(){if(clipEnd&&vid.currentTime>=clipEnd)clipDone()});
vid.addEventListener('ended',clipDone);
vid.addEventListener('error',function(){vid.hidden=true});
/* Outgoing-page transitions, mirroring the editor preview: the incoming page starts
   at `inStart` and settles at full size/opacity; the outgoing page ends at `outEnd`. */
var FX={
 fade:{inStart:{opacity:'0',transform:'none'},outEnd:{}},
 'slide-left':{inStart:{opacity:'1',transform:'translateX(100%)'},outEnd:{transform:'translateX(-100%)'}},
 'slide-up':{inStart:{opacity:'1',transform:'translateY(100%)'},outEnd:{transform:'translateY(-100%)'}},
 zoom:{inStart:{opacity:'0',transform:'scale(.88)'},outEnd:{opacity:'0',transform:'scale(1.16)'}}
};
function setStyles(el,styles){for(var k in styles)if(Object.prototype.hasOwnProperty.call(styles,k))el.style[k]=styles[k]}
function tell(s){error.textContent=s;error.style.display=s?'block':'none'}
function duration(p){return Math.max(1,Math.min(14400,Number(p.duration)||10))*1000}
function preload(src,done,fail){var im=new Image();im.onload=function(){done(im)};im.onerror=fail;im.src=src}
function reset(el){el.style.transition='none';el.style.transform='';el.style.opacity='';el.style.zIndex=''}
function showOne(src){clearTimeout(fadeTimer);reset(front);reset(back);front.src=src;front.className='active';back.className='';back.removeAttribute('src')}
function showBlank(message,identity){clearTimeout(timer);clearTimeout(fadeTimer);stopVideo();pages=[];currentVersion=null;stage.style.display='none';blank.style.display='flex';$('blankMessage').textContent=message;$('blankIdentity').textContent=identity||''}
function scheduleNext(){clearTimeout(timer);if(!pages.length)return;startVideo(pages[index]);if(pages.length>1){nextImage=new Image();nextImage.src=pages[(index+1)%pages.length].src}timer=setTimeout(turn,duration(pages[index]));}
function turn(){if(pages.length<2){scheduleNext();return}
 stopVideo();var prev=pages[index];index=(index+1)%pages.length;var following=pages[index];
 var effect=prev.transition||'fade',ms=Math.max(0,Math.min(10,Number(prev.transitionDuration)||0))*1000;
 clearTimeout(fadeTimer);
 if(effect==='cut'||effect==='none'||ms<100||!Object.prototype.hasOwnProperty.call(FX,effect)){showOne(following.src);scheduleNext();return}
 var fx=FX[effect];
 reset(back);back.src=following.src;setStyles(back,fx.inStart);back.className='active';
 front.style.zIndex='1';back.style.zIndex='2';
 void back.offsetWidth; // commit the start position before animating
 var t='opacity '+ms+'ms ease, transform '+ms+'ms ease';front.style.transition=t;back.style.transition=t;
 setStyles(front,fx.outEnd);back.style.opacity='1';back.style.transform='none';
 fadeTimer=setTimeout(function(){var old=front;front=back;back=old;reset(back);back.className='';back.removeAttribute('src');reset(front);scheduleNext()},ms+60);
}
function finished(){loading=false}
function good(){lastGoodFetch=Date.now();tell('');finished()}
function offline(){tell('Offline / checking for menu updates');finished()}
function handle(status,data){
 if(status===404&&data&&data.ok===false){showBlank('This TV address is not registered.','Check the URL in Store & TV Manager — '+slug);good();return}
 if(status<200||status>=300||!data||!data.ok||!data.screen){offline();return}
 var s=data.screen;
 if(s.revision_id===currentVersion){good();return}
 if(s.pages&&s.pages.length){preload(s.pages[0].src,function(first){ // v0.0.22: the rest load one page ahead (scheduleNext); 100 at once is too much for a TV browser
  stopVideo();pages=s.pages;currentVersion=s.revision_id;index=0;clearTimeout(timer);showOne(first.src);blank.style.display='none';stage.style.display='block';scheduleNext();good()},offline);return}
 showBlank('Waiting for your first published menu…',s.store+' · '+s.name+' · '+location.host+location.pathname);good();
}
function check(){if(loading&&Date.now()-loadingSince<60000)return;loading=true;loadingSince=Date.now();
 var url=apiURL+'?action=screen&slug='+encodeURIComponent(slug)+'&showing='+encodeURIComponent(currentVersion||'')+'&t='+Date.now();
 var x=new XMLHttpRequest(),settled=false;
 function settle(){if(settled)return;settled=true;var data=null;try{data=JSON.parse(x.responseText)}catch(e){}try{handle(x.status,data)}catch(e){offline()}}
 try{x.open('GET',url,true);x.timeout=20000;x.onreadystatechange=function(){if(x.readyState===4)settle()};x.ontimeout=settle;x.onerror=settle;x.send()}catch(e){offline()}
}
check();setInterval(check,1000);
/* v0.0.24.1: a video finishes first. Reloading mid-video would restart it, so a 20-minute slideshow would never reach its end. */
function freshStart(){if(navigator.onLine!==false&&Date.now()-lastGoodFetch<90000)location.reload()}
setInterval(function(){if(!vid.hidden&&!vid.paused)reloadDue=true;else freshStart()},15*60*1000);
window.addEventListener('online',check);document.addEventListener('visibilitychange',function(){if(!document.hidden)check()});
/* v0.0.11: one press of OK on the remote (or one tap/click) puts the TV in full screen — browsers
   only allow full screen after a user action. The screen is kept awake where the browser allows it. */
var fsHint=$('fsHint');var hintTimer=null,wakeLock=null;
function isFull(){return !!(document.fullscreenElement||document.webkitFullscreenElement)||!!(window.matchMedia&&window.matchMedia('(display-mode: fullscreen)').matches)}
function canFull(){return !!(document.fullscreenEnabled||document.webkitFullscreenEnabled)}
function showHint(){clearTimeout(hintTimer);fsHint.hidden=isFull()||!canFull();if(!fsHint.hidden)hintTimer=setTimeout(function(){fsHint.hidden=true},20000)}
function keepAwake(){try{if(navigator.wakeLock&&!wakeLock&&!document.hidden){wakeLock={};navigator.wakeLock.request('screen').then(function(lock){wakeLock=lock;lock.addEventListener('release',function(){wakeLock=null})},function(){wakeLock=null})}}catch(e){wakeLock=null}}
function goFull(){keepAwake();if(isFull())return;var el=document.documentElement,req=el.requestFullscreen||el.webkitRequestFullscreen;if(!req)return;try{var p=req.call(el,{navigationUI:'hide'});if(p&&typeof p.then==='function')p.then(null,function(){})}catch(e){}}
var events=['click','keydown','touchend'];for(var i=0;i<events.length;i++)document.addEventListener(events[i],goFull);
document.addEventListener('fullscreenchange',showHint);document.addEventListener('webkitfullscreenchange',showHint);
document.addEventListener('visibilitychange',function(){if(!document.hidden)keepAwake()});
showHint();keepAwake();
})();
