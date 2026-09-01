(function(){
  'use strict';
  function mark(){
    document.documentElement.classList.add('qalam-v1-ui');
    if(document.body){document.body.classList.add('qalam-v1-ui');}
    var quiz=document.querySelector('.quiz-attempt-single-question,.tutor-quiz-question-wrapper,.tutor-quiz-attempt-details');
    if(quiz && document.body){document.body.classList.add('qalam-quiz-shell');}
    // Keep the assistant from covering quiz navigation. On mobile elsewhere, collapse it visually.
    document.querySelectorAll('[class*="qalam-ai"],[id*="qalam-ai"]').forEach(function(el){
      if(quiz){el.style.setProperty('display','none','important');}
      else if(window.matchMedia('(max-width:782px)').matches){el.classList.add('qalam-ai-mobile-compact');}
    });
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mark);else mark();
  new MutationObserver(function(){mark();}).observe(document.documentElement,{subtree:true,childList:true});
})();
