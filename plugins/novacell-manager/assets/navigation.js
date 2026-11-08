/* Direct link from a public page selects its editable section or entry. */
(function () {'use strict';
  const p=new URLSearchParams(window.location.search);
  const section=p.get('nc_section'), page=p.get('nc_page'), archive=p.get('nc_archive');
  const find=section?document.querySelector('[data-nc-id="'+CSS.escape(section)+'"]'):page?document.querySelector('[data-nc-page="'+CSS.escape(page)+'"]'):archive?document.getElementById('nc-archive-'+archive):null;
  if (!find) return;
  find.setAttribute('data-nc-highlight','');
  const detail=find.querySelector('.nc-card-details');
  if (detail){detail.hidden=false;const button=find.querySelector('.nc-toggle');if(button)button.setAttribute('aria-expanded','true');}
  const top=find.getBoundingClientRect().top+window.scrollY-110;
  window.scrollTo({top:Math.max(0,top),behavior:'instant'});
  find.setAttribute('tabindex','-1');find.focus({preventScroll:true});
})();
