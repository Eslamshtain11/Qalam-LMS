(function(){
  'use strict';

  var menu=document.querySelector('.qalam-mobile-menu');
  var sidebar=document.getElementById('qalam-sidebar');
  if(menu&&sidebar){
    menu.addEventListener('click',function(e){e.stopPropagation();sidebar.classList.toggle('is-open');});
    sidebar.addEventListener('click',function(e){if(e.target.closest('a')&&window.innerWidth<=760){sidebar.classList.remove('is-open');}});
    document.addEventListener('click',function(e){if(window.innerWidth<=760&&sidebar.classList.contains('is-open')&&!sidebar.contains(e.target)&&e.target!==menu){sidebar.classList.remove('is-open');}});
  }

  var creditData=window.QalamCloudCredits;
  var topbar=document.querySelector('.qalam-topbar');
  if(creditData&&topbar&&!document.querySelector('[data-qalam-cloud-credits]')){
    var creditBadge=document.createElement(creditData.ai_url?'a':'div');
    creditBadge.className='qalam-cloud-credit-badge'+(creditData.status==='suspended'?' is-suspended':'')+(!creditData.available?' is-unavailable':'');
    creditBadge.setAttribute('data-qalam-cloud-credits','');
    creditBadge.setAttribute('aria-label',creditData.available?'رصيد الذكاء الاصطناعي المتبقي '+creditData.remaining:'تعذر تحميل رصيد الذكاء الاصطناعي');
    if(creditData.ai_url){creditBadge.href=creditData.ai_url;}
    var creditIcon=document.createElement('span');creditIcon.className='qalam-cloud-credit-icon';creditIcon.setAttribute('aria-hidden','true');
    creditIcon.innerHTML='<svg viewBox="0 0 24 24"><path d="M12 2 14 7l5 2-5 2-2 5-2-5-5-2 5-2 2-5Zm7 12 1 2.5 2.5 1L20 18.5 19 21l-1-2.5-2.5-1 2.5-1L19 14Z"></path></svg>';
    var creditCopy=document.createElement('span');creditCopy.className='qalam-cloud-credit-copy';
    var creditLabel=document.createElement('small');creditLabel.textContent='رصيد الذكاء الاصطناعي';
    var creditValue=document.createElement('strong');
    if(creditData.available){
      var amount=document.createElement('b');amount.textContent=Number(creditData.remaining||0).toLocaleString('ar-EG');
      creditValue.append(amount,document.createTextNode(' سؤال متبقي'));
    }else{creditValue.textContent='غير متصل بالكلاود';}
    creditCopy.append(creditLabel,creditValue);
    var creditState=document.createElement('span');creditState.className='qalam-cloud-credit-state';creditState.title=creditData.status==='active'?'متزامن مع Qalam Cloud':'حالة الاشتراك غير نشطة';
    creditBadge.append(creditIcon,creditCopy,creditState);
    var userArea=topbar.querySelector('.qalam-user');
    topbar.insertBefore(creditBadge,userArea||null);
  }


  var userToggle=document.querySelector('[data-qalam-user-toggle]');
  var userMenu=document.querySelector('[data-qalam-user-menu]');
  function closeUserMenu(){
    if(!userToggle||!userMenu)return;
    userToggle.setAttribute('aria-expanded','false');
    userMenu.setAttribute('aria-hidden','true');
    userMenu.classList.remove('is-open');
  }
  if(userToggle&&userMenu){
    userToggle.addEventListener('click',function(e){
      e.stopPropagation();
      var open=userMenu.classList.toggle('is-open');
      userToggle.setAttribute('aria-expanded',open?'true':'false');
      userMenu.setAttribute('aria-hidden',open?'false':'true');
    });
    document.addEventListener('click',function(e){if(!userMenu.contains(e.target)&&!userToggle.contains(e.target)){closeUserMenu();}});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeUserMenu();}});
  }

  function rewriteLegacyAdminUrl(href){
    if(!href||!window.QalamAdmin||!QalamAdmin.legacyRoutes){return href;}
    var url;
    try{url=new URL(href,window.location.href);}catch(err){return href;}
    if(url.origin!==window.location.origin){return href;}
    var path=url.pathname.replace(/\/+$/,'');
    if(path.indexOf('/wp-admin/')===-1){return href;}
    if(/\/(admin-ajax|admin-post|async-upload)\.php$/.test(path)){return href;}
    var routes=QalamAdmin.legacyRoutes||{};
    var dest='';
    if(/\/users\.php$/.test(path)){dest=routes.users||'';}
    else if(/\/user-edit\.php$/.test(path)){dest=routes.instructors||'';}
    else if(/\/(edit|post-new)\.php$/.test(path)){
      var postType=url.searchParams.get('post_type')||'';
      if(postType==='course-bundle'){dest=routes.bundles||'';}
      else if(postType==='courses'||postType==='course'){dest=routes.courses||'';}
      else if(postType==='shop_order'){dest=(routes.pages&&routes.pages.tutor_orders)||'';}
      if(dest&&/\/post-new\.php$/.test(path)){var d0=new URL(dest,window.location.href);d0.searchParams.set('builder','1');dest=d0.href;}
    }else if(/\/admin\.php$/.test(path)){
      var page=url.searchParams.get('page')||'';
      dest=(routes.pages&&routes.pages[page])||'';
    }
    if(!dest){return href;}
    var out=new URL(dest,window.location.href);
    url.searchParams.forEach(function(value,key){
      if(key==='page'){return;}
      if(key==='tab_page'&&url.searchParams.get('page')==='tutor_settings'){out.searchParams.set('tab',value);return;}
      if(!out.searchParams.has(key)){out.searchParams.set(key,value);}
    });
    if(url.hash){out.hash=url.hash;}
    return out.href;
  }

  document.addEventListener('click',function(e){
    var link=e.target.closest('a[href]');
    if(link&&!e.defaultPrevented&&e.button===0&&!e.metaKey&&!e.ctrlKey&&!e.shiftKey&&!e.altKey){
      var original=link.getAttribute('href')||'';
      var rewritten=rewriteLegacyAdminUrl(original);
      if(rewritten!==original){e.preventDefault();window.location.assign(rewritten);return;}
    }

    var toggler=e.target.closest('[data-qalam-toggle]');
    if(toggler){
      e.preventDefault();
      var selector=toggler.getAttribute('data-qalam-toggle');
      var target=selector?document.querySelector(selector):null;
      if(target){
        target.hidden=!target.hidden;
        if(!target.hidden){var first=target.querySelector('input,select,textarea');if(first){setTimeout(function(){first.focus();},20);}}
      }
      return;
    }

    var btn=e.target.closest('.qalam-addon-toggle');
    if(!btn||!window.QalamAdmin)return;
    var feature=btn.getAttribute('data-feature');
    var enable=btn.getAttribute('data-enable');
    if(!feature)return;
    btn.disabled=true;
    var old=btn.textContent;
    btn.textContent='جارٍ الحفظ...';
    var body=new URLSearchParams();
    body.set('action','qalam_200_toggle_product');
    body.set('nonce',QalamAdmin.toggleNonce);
    body.set('feature',feature);
    body.set('enable',enable);
    fetch(QalamAdmin.ajaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString()})
      .then(function(r){return r.json();})
      .then(function(data){if(!data||!data.success){throw new Error(data&&data.data&&data.data.message?data.data.message:'تعذر تحديث الملحق');}location.reload();})
      .catch(function(err){alert(err.message||'تعذر تحديث الملحق');btn.disabled=false;btn.textContent=old;});
  });

  var addonSearch=document.querySelector('[data-qalam-addon-search]');
  var addonCategory=document.querySelector('[data-qalam-addon-category]');
  var addonCards=[].slice.call(document.querySelectorAll('[data-addon-card]'));
  function normalize(value){return String(value||'').trim().toLocaleLowerCase('ar');}
  function filterAddons(){
    if(!addonCards.length)return;
    var term=normalize(addonSearch&&addonSearch.value);
    var category=addonCategory?addonCategory.value:'';
    addonCards.forEach(function(card){
      var matchTerm=!term||normalize(card.getAttribute('data-search')).indexOf(term)!==-1;
      var matchCategory=!category||card.getAttribute('data-category')===category;
      card.hidden=!(matchTerm&&matchCategory);
    });
  }
  if(addonSearch){addonSearch.addEventListener('input',filterAddons);}
  if(addonCategory){addonCategory.addEventListener('change',filterAddons);}

  var globalSearch=document.querySelector('[data-qalam-global-search]');
  if(globalSearch){
    var palette=document.createElement('div');
    palette.className='qalam-command-palette';
    document.body.appendChild(palette);
    var navItems=[].slice.call(document.querySelectorAll('.qalam-nav a')).map(function(a){return{label:(a.textContent||'').trim(),href:a.href};});
    function renderPalette(){
      var q=normalize(globalSearch.value);
      var items=navItems.filter(function(item){return !q||normalize(item.label).indexOf(q)!==-1;}).slice(0,8);
      palette.innerHTML=items.map(function(item,index){return '<a '+(index===0?'class="is-active" ':'')+'href="'+item.href.replace(/"/g,'&quot;')+'"><span>'+item.label.replace(/</g,'&lt;')+'</span><small>فتح</small></a>';}).join('');
      palette.classList.toggle('is-open',document.activeElement===globalSearch&&items.length>0);
    }
    globalSearch.addEventListener('focus',renderPalette);
    globalSearch.addEventListener('input',renderPalette);
    globalSearch.addEventListener('keydown',function(e){
      if(e.key==='Enter'){
        var first=palette.querySelector('a');
        if(first){e.preventDefault();window.location.assign(first.href);}
      }else if(e.key==='Escape'){
        palette.classList.remove('is-open');globalSearch.blur();
      }
    });
    document.addEventListener('click',function(e){if(e.target!==globalSearch&&!palette.contains(e.target)){palette.classList.remove('is-open');}});
  }
})();
