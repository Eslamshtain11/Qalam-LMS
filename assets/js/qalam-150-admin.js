(function(){'use strict';
 function mediaPicker(button){
   if(!window.wp||!wp.media){return;}
   var target=document.getElementById(button.getAttribute('data-qalam-media-target')||''); if(!target){return;}
   var frame=wp.media({title:'اختار الملف',button:{text:'استخدام الملف'},multiple:false,library:{type:['image','video']}});
   frame.on('select',function(){
     var item=frame.state().get('selection').first().toJSON();
     target.value=item.url||'';
     var idTarget=document.getElementById(button.getAttribute('data-qalam-media-id-target')||'');
     var mimeTarget=document.getElementById(button.getAttribute('data-qalam-media-mime-target')||'');
     if(idTarget){idTarget.value=String(item.id||0);}
     if(mimeTarget){mimeTarget.value=String(item.mime||item.type||'');}
     var typeSelect=button.closest('form')&&button.closest('form').querySelector('select[name="media_type"]');
     if(typeSelect){var mime=String(item.mime||''); if(mime.indexOf('image/')===0){typeSelect.value='image';}else if(mime.indexOf('video/')===0){typeSelect.value='video';}}
     target.dispatchEvent(new Event('change',{bubbles:true}));
   });frame.open();
 }
 document.addEventListener('click',function(e){var btn=e.target.closest('[data-qalam-media-target]');if(btn){e.preventDefault();mediaPicker(btn);}});
 function syncSelectZ(){
   document.querySelectorAll('.qalam-select-open').forEach(function(el){el.classList.remove('qalam-select-open');});
   document.querySelectorAll('.qalam-select-host-open').forEach(function(el){el.classList.remove('qalam-select-host-open');});
   document.querySelectorAll('.tutor-js-form-select.is-active').forEach(function(el){
     el.classList.add('qalam-select-open');
     var node=el.parentElement, depth=0;
     while(node && node!==document.body && depth<6){node.classList.add('qalam-select-host-open');node=node.parentElement;depth++;}
   });
 }
 document.addEventListener('click',function(){setTimeout(syncSelectZ,0);},true);
 document.addEventListener('focusin',function(){setTimeout(syncSelectZ,0);},true);
 document.addEventListener('keydown',function(e){if(e.key==='Escape'){setTimeout(syncSelectZ,0);}},true);
 syncSelectZ();
})();
