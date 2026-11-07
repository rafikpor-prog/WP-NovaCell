(function(){
 'use strict';
 document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('[data-nc-lang-field]').forEach(function(group){
   var tabs=Array.prototype.slice.call(group.querySelectorAll('[role="tab"]'));
   var panels=Array.prototype.slice.call(group.querySelectorAll('[role="tabpanel"]'));
   function activate(tab){
    tabs.forEach(function(t){var yes=t===tab;t.setAttribute('aria-selected',yes?'true':'false');t.tabIndex=yes?0:-1;});
    panels.forEach(function(p){p.hidden=p.dataset.langPanel!==tab.dataset.lang;});
    // Inicjalizowane edytory pozostają w stabilnym miejscu w DOM.
    try{if(window.tinymce){window.tinymce.triggerSave();}}catch(_e){}
   }
   tabs.forEach(function(tab,i){
    tab.addEventListener('click',function(){activate(tab);});
    tab.addEventListener('keydown',function(event){
     var next=-1;
     if(event.key==='ArrowRight')next=(i+1)%tabs.length;
     if(event.key==='ArrowLeft')next=(i-1+tabs.length)%tabs.length;
     if(event.key==='Home')next=0;
     if(event.key==='End')next=tabs.length-1;
     if(next>=0){event.preventDefault();activate(tabs[next]);tabs[next].focus();}
    });
   });
  });
 });
})();
