(function(){'use strict';
 const toggle=document.querySelector('.nc-menu-toggle');
 const nav=document.querySelector('#nc-primary-nav');
 if(!toggle||!nav)return;
 const small=()=>window.matchMedia('(max-width:1450px)').matches;
 function close(){nav.dataset.open='false';toggle.setAttribute('aria-expanded','false');}
 toggle.addEventListener('click',function(){const open=toggle.getAttribute('aria-expanded')==='true';nav.dataset.open=open?'false':'true';toggle.setAttribute('aria-expanded',String(!open));});
 // Klawiaturowe przyciski rozwijania podmenu na desktop; w mobile listy pozostają widoczne.
 const items=nav.querySelectorAll('.menu-item-has-children');
 items.forEach(function(item,index){
   const submenu=item.querySelector('.sub-menu');if(!submenu)return;
   const link=item.querySelector(':scope > a');if(!link)return;
   submenu.id='nc-submenu-'+index;
   const btn=document.createElement('button');btn.type='button';btn.className='nc-submenu-toggle';
   btn.setAttribute('aria-label','Rozwiń lub zwiń: '+link.textContent.trim());
   btn.setAttribute('aria-controls',submenu.id);btn.setAttribute('aria-expanded','false');
   btn.innerHTML='<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m5 9 7 7 7-7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
   link.insertAdjacentElement('afterend',btn);
   btn.addEventListener('click',function(){const open=item.classList.toggle('nc-submenu-open');btn.setAttribute('aria-expanded',open?'true':'false');});
   item.addEventListener('focusout',function(e){if(!item.contains(e.relatedTarget)){item.classList.remove('nc-submenu-open');btn.setAttribute('aria-expanded','false');}});
 });
 document.addEventListener('keydown',function(e){
  if(e.key==='Escape'){
   let open=nav.querySelector('.nc-submenu-open');
   if(open){open.classList.remove('nc-submenu-open');const b=open.querySelector('.nc-submenu-toggle');if(b){b.setAttribute('aria-expanded','false');b.focus();}return;}
   if(nav.dataset.open==='true'){close();toggle.focus();}
  }
 });
 nav.addEventListener('click',function(e){if(small()&&e.target.closest('a'))close();});
 document.addEventListener('click',function(e){if(nav.dataset.open==='true'&&!nav.contains(e.target)&&!toggle.contains(e.target))close();});
})();
