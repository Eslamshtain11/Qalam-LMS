(function(){'use strict';
var cfg=window.QalamReferenceSystem||{};
var key=cfg.storageKey||'qalam-color-mode';
function systemMode(){return window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}
function apply(mode,persist){
  if(mode==='system'){mode=systemMode();}
  document.documentElement.dataset.qalamMode=mode;
  document.documentElement.style.colorScheme=mode;
  if(persist){try{localStorage.setItem(key,mode);}catch(e){}}
  document.querySelectorAll('[data-qalam-mode-toggle]').forEach(function(btn){
    btn.setAttribute('aria-label',mode==='dark'?'تفعيل الوضع الفاتح':'تفعيل الوضع الداكن');
  });
}
function setMenu(open){
  var menu=document.querySelector('[data-qalam-mobile-menu]');
  var btn=document.querySelector('[data-qalam-menu-toggle]');
  var backdrop=document.querySelector('[data-qalam-menu-backdrop]');
  if(!menu||!btn){return;}
  menu.classList.toggle('is-open',!!open);
  menu.setAttribute('aria-hidden',open?'false':'true');
  btn.setAttribute('aria-expanded',open?'true':'false');
  btn.setAttribute('aria-label',open?'إغلاق القائمة':'فتح القائمة');
  if(backdrop){backdrop.classList.toggle('is-open',!!open);backdrop.setAttribute('aria-hidden',open?'false':'true');}
  document.documentElement.classList.toggle('qalam-menu-open',!!open);
  document.body&&document.body.classList.toggle('qalam-menu-open',!!open);
}
function boot(){
  var stored=null;try{stored=localStorage.getItem(key);}catch(e){}
  apply(stored||cfg.defaultMode||'system',false);
  document.addEventListener('click',function(e){
    var modeBtn=e.target.closest('[data-qalam-mode-toggle]');
    if(modeBtn){e.preventDefault();apply(document.documentElement.dataset.qalamMode==='dark'?'light':'dark',true);return;}
    var menuBtn=e.target.closest('[data-qalam-menu-toggle]');
    if(menuBtn){e.preventDefault();var menu=document.querySelector('[data-qalam-mobile-menu]');setMenu(!(menu&&menu.classList.contains('is-open')));return;}
    if(e.target.closest('[data-qalam-menu-backdrop]')){e.preventDefault();setMenu(false);return;}
    var menuLink=e.target.closest('[data-qalam-mobile-menu] a');
    if(menuLink){setMenu(false);}
  });
  document.addEventListener('keydown',function(e){if(e.key==='Escape'){setMenu(false);}});
  window.addEventListener('resize',function(){if(window.innerWidth>1080){setMenu(false);}},{passive:true});

  var els=[].slice.call(document.querySelectorAll('[data-qalam-reveal]'));
  if('IntersectionObserver'in window){
    var io=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('is-visible');io.unobserve(entry.target);}});},{threshold:.12,rootMargin:'0px 0px -30px'});
    els.forEach(function(el){io.observe(el);});
  }else{els.forEach(function(el){el.classList.add('is-visible');});}

  var header=document.querySelector('[data-qalam-header]');
  if(header){
    var ticking=false;
    window.addEventListener('scroll',function(){if(!ticking){window.requestAnimationFrame(function(){header.classList.toggle('is-scrolled',window.scrollY>18);ticking=false;});ticking=true;}},{passive:true});
  }
}
if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',boot);}else{boot();}
})();
