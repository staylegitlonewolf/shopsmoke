/* v0.0.14: app-like phone behaviour for every page.
   - iOS Safari zooms into any field whose text is under 16px: fields are 16px on touch screens.
   - No double-tap zoom on controls, no rubber-band scroll chaining.
   - Dialogs follow the *visible* viewport, so they float above the on-screen keyboard,
     and the focused field is scrolled into view once the keyboard has opened. */
(function(){
 var css='@media (pointer:coarse){input:not([type=range]):not([type=checkbox]):not([type=radio]):not([type=color]):not([type=file]),select,textarea{font-size:16px!important}}'
  +'html{-webkit-text-size-adjust:100%;text-size-adjust:100%}html,body{overscroll-behavior-y:none}'
  +'button,a,input,select,textarea,label{touch-action:manipulation}'
  +'#modalBack,#modalBackdrop,#overlay,.shade{top:var(--vv-top,0px)!important;height:var(--vv-height,100%)!important;bottom:auto!important}';
 /* v0.0.34: a device that hasn't logged in sees nothing until ftapi.js has checked, so the app doesn't flash before the login splash. A device that has logged in opens straight away, and ftapi.js still checks. */
 css+='html.ft-locked,html.ft-locked body{background:#080c1a!important}html.ft-locked body>*:not(.ft-login){visibility:hidden!important}';
 var seen=false;try{seen=localStorage.getItem('ft-logged-in')==='1'}catch(_){}
 if(!seen)document.documentElement.classList.add('ft-locked');
 /* v0.0.36: --ft-top and --ft-bottom are the room every page keeps at the screen edges: the safe areas on a phone, 0 on a computer, and more on an iPad.
    iPadOS 26 and later fog the top of a Home Screen app, about 60px down, and the header buttons there stop working. WebKit only drops the fog when a solid,
    fixed, full-width bar over 10px tall sits at the top edge, so #ft-top-band is that bar. It goes in now, before the body exists, because the iPad can keep the fog
    after a single frame without it. The header starts below the 44px, so it clears the fog even on an iPad that ignores the bar. Change 44px here if an iPad needs more. */
 css+=':root{--ft-top:env(safe-area-inset-top,0px);--ft-bottom:env(safe-area-inset-bottom,0px)}';
 var ipad=navigator.maxTouchPoints>1&&Math.min(screen.width,screen.height)>=600&&/iPad|Macintosh/.test(navigator.userAgent);
 if(ipad){
  var installed=navigator.standalone===true||(window.matchMedia&&matchMedia('(display-mode: standalone)').matches);
  css+='html.ft-ipad{--ft-top:max('+(installed?'44px':'3vh')+',env(safe-area-inset-top,0px));--ft-bottom:max(20px,env(safe-area-inset-bottom,0px))}'
   +'#ft-top-band{position:fixed;top:0;left:0;right:0;height:var(--ft-top);background:#000;z-index:2147483647}'
   +'html.ft-ipad .app{height:100dvh;padding-top:var(--ft-top)}html.ft-ipad .app.timeline-docked-top{padding-bottom:var(--ft-bottom)}'
   +'html.ft-ipad .about-dialog{max-height:calc(100dvh - var(--ft-top) - var(--ft-bottom) - 24px)!important}'
   +'html.ft-ipad .previewCard{top:calc(58px + var(--ft-top))}'
   +'@media(min-width:821px){html.ft-ipad .topbar{box-sizing:content-box;height:60px;padding-top:var(--ft-top)}html.ft-ipad .sidebar{padding-top:calc(20px + var(--ft-top))}html.ft-ipad .select-bar{top:calc(65px + var(--ft-top))}'
   +'html.ft-ipad .modal-backdrop,html.ft-ipad .modal-back,html.ft-ipad .shade,html.ft-ipad .overlay{padding-top:calc(18px + var(--ft-top));padding-bottom:calc(18px + var(--ft-bottom))}}'
   +'@media(min-width:601px) and (max-width:820px){html.ft-ipad .select-bar{top:calc(64px + var(--ft-top))}}';
  document.documentElement.classList.add('ft-ipad');
  var band=document.createElement('div');band.id='ft-top-band';band.setAttribute('aria-hidden','true');document.documentElement.appendChild(band);
 }
 var s=document.createElement('style');s.id='ft-viewport';s.textContent=css;(document.head||document.documentElement).appendChild(s);
 var vv=window.visualViewport;if(!vv)return;
 var root=document.documentElement;
 function sync(){root.style.setProperty('--vv-height',vv.height+'px');root.style.setProperty('--vv-top',vv.offsetTop+'px');root.classList.toggle('ft-keyboard',window.innerHeight-vv.height>120)}
 vv.addEventListener('resize',sync);vv.addEventListener('scroll',sync);sync();
 document.addEventListener('focusin',function(e){var t=e.target;if(!t.matches||!t.matches('input,select,textarea'))return;setTimeout(function(){if(root.classList.contains('ft-keyboard'))try{t.scrollIntoView({block:'center',behavior:'smooth'})}catch(_){}} ,350)});
 /* v0.0.36: on a page with nothing to scroll, the keyboard can leave the screen pushed up and the top buttons out of reach, so it goes back when the keyboard closes. */
 document.addEventListener('focusout',function(){setTimeout(function(){if(!root.classList.contains('ft-keyboard')&&window.scrollY>0&&root.scrollHeight<=window.innerHeight+1)window.scrollTo(0,0)},350)});
})();
