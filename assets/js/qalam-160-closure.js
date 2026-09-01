(function(){'use strict';
function visible(el){if(!el)return false;var s=getComputedStyle(el);return s.display!=='none'&&s.visibility!=='hidden'&&el.getClientRects().length>0;}
function syncLayers(){document.querySelectorAll('.qalam-select-open').forEach(function(el){el.classList.remove('qalam-select-open');});document.querySelectorAll('.tutor-js-form-select').forEach(function(el){var menu=el.querySelector('.tutor-form-select-dropdown,.tutor-dropdown-menu,[role=listbox]');var open=el.classList.contains('is-active')||el.getAttribute('aria-expanded')==='true'||(menu&&visible(menu));if(open)el.classList.add('qalam-select-open');});}
function hideLegacyUpsells(root){(root||document).querySelectorAll&& (root||document).querySelectorAll('a,button').forEach(function(el){if(/upgrade to pro|get tutor lms pro|get pro/i.test((el.textContent||'').trim()))el.hidden=true;});}
function markStudentRole(){document.querySelectorAll('option').forEach(function(o){if(o.value==='qalam_student')o.textContent='طالب';});}
function isProductSurface(){return !!document.querySelector('.qalam-f345-ui,.qalam-f345-admin,.tutor-wrap,.tutor-admin-wrap,.tutor-dashboard,.tutor-course-builder,[class*=\"tutor-dashboard\"],[class*=\"tutor-course-builder\"]');}
function run(){if(!isProductSurface())return;syncLayers();hideLegacyUpsells(document);markStudentRole();}
if(!isProductSurface() && document.readyState!=='loading'){return;}
document.addEventListener('click',function(){setTimeout(run,0);},true);document.addEventListener('focusin',function(){setTimeout(run,0);},true);document.addEventListener('keydown',function(e){if(e.key==='Escape')setTimeout(run,0)},true);
new MutationObserver(function(ms){var needs=false;ms.forEach(function(m){if(m.addedNodes.length)needs=true;});if(needs)setTimeout(run,0);}).observe(document.documentElement,{subtree:true,childList:true});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run,{once:true});else run();
})();