(function(){
'use strict';
var allow=['Zoom','Google Meet','Google Classroom','OpenAI','DeepSeek','OpenRouter','Google AI Studio','WooCommerce','BuddyPress','WPML','Weglot','H5P','API','JSON','PDF','OAuth','Qalam LMS','Qalam','Windows','Mac'];
function visible(el){if(!el||!el.getClientRects||!el.getClientRects().length)return false;var s=getComputedStyle(el);return s.visibility!=='hidden'&&s.display!=='none';}
function clean(text){var x=(text||'').replace(/\s+/g,' ').trim();allow.forEach(function(token){x=x.split(token).join('');});return x;}
function scan(){var found=[];var w=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);var n;while((n=w.nextNode())){var p=n.parentElement;if(!p||/^(SCRIPT|STYLE|CODE|PRE|TEXTAREA|NOSCRIPT)$/.test(p.tagName)||!visible(p))continue;var t=clean(n.nodeValue);if(t.length<4)continue;if(/[\u0600-\u06FF]/.test(t)&&/[A-Za-z]/.test(t))found.push((n.nodeValue||'').trim());}window.QalamMixedLanguageAudit=Array.from(new Set(found));if(window.QalamMixedLanguageAudit.length&&window.console&&console.warn)console.warn('[Qalam Arabic Audit] جمل مختلطة تحتاج ترجمة كاملة:',window.QalamMixedLanguageAudit);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(scan,700);});else setTimeout(scan,700);
})();
