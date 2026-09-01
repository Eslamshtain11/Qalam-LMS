(function(){
  'use strict';
  function isQuizAttempt(){
    return !!document.querySelector('.quiz-attempt-single-question,.tutor-quiz-submission,.tutor-quiz-question-wrapper');
  }
  function markFloatingAI(){
    if(!isQuizAttempt()) return;
    document.querySelectorAll('body *').forEach(function(el){
      if(el.children.length>8) return;
      var txt=(el.textContent||'').trim();
      if(!txt || (txt.indexOf('اسأل الذكاء الاصطناعي')===-1 && txt.indexOf('Ask AI')===-1)) return;
      var cs=window.getComputedStyle(el);
      if(cs.position==='fixed' || cs.position==='sticky') el.classList.add('qalam-hide-during-quiz');
    });
  }
  function normalizeQuizDirection(){
    if(!isQuizAttempt()) return;
    document.documentElement.setAttribute('dir','rtl');
    document.querySelectorAll('.quiz-attempt-single-question,.tutor-quiz-question-wrapper,.tutor-quiz-question').forEach(function(el){el.setAttribute('dir','rtl');});
  }
  function run(){markFloatingAI();normalizeQuizDirection();}
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',run); else run();
  var mo=new MutationObserver(function(){window.clearTimeout(window.__qalam090t);window.__qalam090t=window.setTimeout(run,80);});
  mo.observe(document.documentElement,{childList:true,subtree:true});
})();
