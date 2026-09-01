(function(){
'use strict';
var cfg=window.Qalam060||{};
function ajax(body){return fetch(cfg.ajaxurl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:new URLSearchParams(body).toString()}).then(function(r){return r.json();});}
function toast(msg,error){if(typeof window.tutor_toast==='function'){window.tutor_toast('',msg,error?'error':'success');}else{window.alert(msg);}}

function setupAI(){
  var provider=document.querySelector('select[name="tutor_option[qalam_ai_provider]"]');
  var key=document.querySelector('input[name="tutor_option[chatgpt_api_key]"]');
  var base=document.querySelector('input[name="tutor_option[qalam_ai_base_url]"]');
  var model=document.querySelector('select[name="tutor_option[qalam_ai_model]"]');
  var manual=document.querySelector('input[name="tutor_option[qalam_ai_model_manual]"]');
  if(!provider||!key)return;
  var keyRow=key.closest('.tutor-option-field-row');
  if(keyRow&&!document.querySelector('[data-qalam-ai-activate]')){
    var row=document.createElement('div'); row.className='qalam-ai-activate-row';
    row.innerHTML='<div><strong>تفعيل المزود</strong><p>بعد إدخال المفتاح اضغط الزر علشان قلم يتحقق من الاتصال ويجيب كل الموديلات المتاحة.</p></div><button type="button" class="button button-primary" data-qalam-ai-activate>'+ (cfg.activateLabel||'تفعيل وجلب الموديلات') +'</button>';
    keyRow.insertAdjacentElement('afterend',row);
  }
  function syncCustom(){var custom=provider.value==='custom';var baseRow=base&&base.closest('.tutor-option-field-row');var manualRow=manual&&manual.closest('.tutor-option-field-row');if(baseRow)baseRow.hidden=!custom;if(manualRow)manualRow.hidden=!custom;}
  provider.addEventListener('change',syncCustom); syncCustom();
  var btn=document.querySelector('[data-qalam-ai-activate]');
  if(btn)btn.addEventListener('click',function(){
    if(btn.disabled)return;
    if(!key.value.trim()){toast('أدخل مفتاح API الأول.',true);key.focus();return;}
    var old=btn.textContent;btn.disabled=true;btn.textContent=cfg.loadingModels||'جاري جلب الموديلات...';
    ajax({action:'qalam_ai_activate_provider',nonce:cfg.ai_nonce,provider:provider.value,api_key:key.value,base_url:base?base.value:''}).then(function(json){
      if(!json||!json.success){var d=json&&json.data||{};throw new Error(d.message||'تعذر تفعيل المزود.');}
      toast((json.data&&json.data.message)||'تم تفعيل المزود.',false);
      // The native Tutor searchable select is initialized on page load. Reloading here
      // makes it rebuild from the server-side cached model catalogue reliably.
      window.setTimeout(function(){window.location.reload();},450);
    }).catch(function(err){toast(err.message||'تعذر تفعيل المزود.',true);btn.disabled=false;btn.textContent=old;});
  });
  if(model){model.setAttribute('aria-label','ابحث واختار موديل الذكاء الاصطناعي');}
}

function setupQuizScope(){
  var form=document.querySelector('[data-qalam-quiz-create-form]'); if(!form)return;
  var scope=form.querySelector('[data-qalam-quiz-scope]'),courseField=form.querySelector('[data-qalam-course-field]'),topicField=form.querySelector('[data-qalam-topic-field]'),course=form.querySelector('[data-qalam-course-select]'),topic=form.querySelector('[data-qalam-topic-select]');
  function sync(){var inside=scope&&scope.value==='course'; if(courseField)courseField.hidden=!inside;if(topicField)topicField.hidden=!inside;if(course)course.required=inside;if(topic)topic.required=inside;}
  if(scope)scope.addEventListener('change',sync);sync();
}


function separateContentBank(){
  var params=new URLSearchParams(window.location.search);if(params.get('page')!=='tutor-content-bank')return;
  function clean(){document.querySelectorAll('[data-cy="question-content-modal"]').forEach(function(el){el.style.display='none';el.setAttribute('aria-hidden','true');});document.querySelectorAll('button,[role="button"]').forEach(function(el){var text=(el.textContent||'').replace(/\s+/g,' ').trim();if(text==='Add Question'||text==='إضافة سؤال'){el.style.display='none';el.setAttribute('aria-hidden','true');}});}
  clean();var observer=new MutationObserver(clean);observer.observe(document.body,{childList:true,subtree:true});
}

document.addEventListener('DOMContentLoaded',function(){setupAI();setupQuizScope();});
})();
