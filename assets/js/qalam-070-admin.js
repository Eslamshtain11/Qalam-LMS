(function(){
'use strict';
function params(){return new URLSearchParams(window.location.search);}
function isPage(name){var p=params();return p.get('page')===name||!!(window.Qalam070&&Qalam070.page===name);}
function setupQuestionCounts(){
  document.querySelectorAll('.qalam-ai-question-form').forEach(function(form){
    var mode=form.querySelector('[data-qalam-source-mode]');
    var pdf=form.querySelector('[data-qalam-pdf-field]');
    var total=form.querySelector('[data-qalam-question-total]');
    var inputs=Array.from(form.querySelectorAll('[data-qalam-question-count]'));
    function syncMode(){if(pdf)pdf.hidden=!(mode&&mode.value.indexOf('pdf_')===0);}
    function syncTotal(){if(total)total.textContent=inputs.reduce(function(n,i){return n+(parseInt(i.value||'0',10)||0);},0);}
    if(mode)mode.addEventListener('change',syncMode);
    inputs.forEach(function(i){i.addEventListener('input',syncTotal);});
    syncMode();syncTotal();
  });
}
function setupNativeQuestionEditor(){
  var p=params();
  if(!isPage('tutor-content-bank')||!p.get('qalam_qbank'))return;
  var bar=document.createElement('div');
  bar.className='qalam-native-editor-bar';
  bar.innerHTML='<a class="button" href="'+((window.Qalam070&&Qalam070.questionBankUrl)||'admin.php?page=qalam-question-bank')+'">← رجوع لبنك الأسئلة</a><strong>محرر الأسئلة الكامل — قلم</strong><span>الأسئلة اللي بتحفظها هنا بتظهر تلقائيًا في بنك الأسئلة وبنك المحتوى.</span>';
  var place=document.querySelector('.tutor-admin-wrap')||document.body;
  place.insertBefore(bar,place.firstChild);
  if(!p.get('qalam_open_question'))return;
  var clicked=false,tries=0;
  function open(){
    if(clicked)return true;
    var btn=document.querySelector('[data-cy="question-content-modal"]');
    if(btn&&btn.offsetParent!==null){clicked=true;btn.click();return true;}
    return false;
  }
  if(!open()){
    var timer=setInterval(function(){tries++;if(open()||tries>80)clearInterval(timer);},125);
    var observer=new MutationObserver(function(){if(open())observer.disconnect();});
    observer.observe(document.documentElement,{childList:true,subtree:true});
  }
}
function setupAdvancedCourseAI(){
  var p=params();
  if(!isPage('create-course'))return;
  var href=((window.Qalam070&&Qalam070.questionBankUrl)||'admin.php?page=qalam-question-bank')+'#qalam-ai-question-generator';
  function inject(){
    document.querySelectorAll('form').forEach(function(form){
      var text=(form.textContent||'').replace(/\s+/g,' ').trim();
      if(text.indexOf('إنشاء أسئلة بالذكاء الاصطناعي')===-1&&text.indexOf('Generate Quiz Component')===-1)return;
      if(form.querySelector('[data-qalam-advanced-ai]'))return;
      var box=document.createElement('div');
      box.className='qalam-course-ai-advanced';
      box.setAttribute('data-qalam-advanced-ai','1');
      box.innerHTML='<div><strong>مولد قلم المتقدم</strong><span>PDF + كل أنواع الأسئلة + تحديد عدد كل نوع. الأسئلة تتحفظ في بنك الأسئلة وبنك المحتوى، وبعدها استوردها للاختبار من زر «بنك المحتوى».</span></div><a class="button button-primary" target="_blank" rel="noopener" href="'+href+'">فتح مولد PDF وكل الأنواع</a>';
      var submit=form.querySelector('button[type="submit"]');
      var host=submit&&submit.parentElement?submit.parentElement:form;
      if(host.parentElement){host.parentElement.appendChild(box);}else{form.appendChild(box);}
    });
  }
  inject();
  var observer=new MutationObserver(inject);
  observer.observe(document.documentElement,{childList:true,subtree:true});
}
function markReports(){if(isPage('tutor_report'))document.body.classList.add('qalam-report-ar');}
function paymentGatewayNotice(){/* Intentionally no inline notice: keep payment settings layout clean. */}
document.addEventListener('DOMContentLoaded',function(){setupQuestionCounts();setupNativeQuestionEditor();setupAdvancedCourseAI();markReports();paymentGatewayNotice();});
})();
