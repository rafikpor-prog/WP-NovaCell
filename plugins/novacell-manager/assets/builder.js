/* NovaCell Manager: dostępne klawiaturą sortowanie i zakładki językowe. */
(function($){'use strict';
  function updateOrder(){ $('#nc-sortable-sections .nc-section-card').each(function(i){$(this).find('input.nc-order').val(i);}); }
  $(function(){
    var list=$('#nc-sortable-sections');
    if(list.length){list.sortable({handle:'.nc-drag',placeholder:'nc-sortable-placeholder',update:updateOrder,tolerance:'pointer'});updateOrder();}
    $(document).on('click','.nc-up,.nc-down',function(){
      var card=$(this).closest('.nc-section-card');var upward=$(this).hasClass('nc-up');var other=upward?card.prev('.nc-section-card'):card.next('.nc-section-card');
      if(other.length){upward?other.before(card):other.after(card);updateOrder();$(this).trigger('focus');}
    });
    $(document).on('click','.nc-toggle',function(){
      var button=$(this),panel=button.closest('.nc-section-card').find('>.nc-card-details');var open=button.attr('aria-expanded')==='true';
      button.attr('aria-expanded',String(!open)).text(open?'Edytuj':'Zwiń');panel.prop('hidden',open);
    });
    $(document).on('click','.nc-lang-tab',function(){
      var tab=$(this),wrap=tab.closest('.nc-editor-langs'),locale=tab.attr('data-nc-tab');
      wrap.find('>.nc-tab-list .nc-lang-tab').attr('aria-selected','false').attr('tabindex','-1');tab.attr('aria-selected','true').attr('tabindex','0');
      wrap.find('>.nc-lang-panel').prop('hidden',true).filter('[data-nc-panel="'+locale+'"]').prop('hidden',false);
    });
    $(document).on('keydown','.nc-lang-tab',function(e){
      if(e.key!=='ArrowLeft'&&e.key!=='ArrowRight')return;
      e.preventDefault();var tabs=$(this).closest('.nc-tab-list').find('.nc-lang-tab');var i=tabs.index(this);tabs.eq((i+(e.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length).trigger('click').trigger('focus');
    });
    // Zapobieganie utracie treści z aktywnych instancji TinyMCE w ukrytych panelach.
    $('#nc-home-form,form:has(input[name="action"][value="nc_save_pages"])').on('submit',function(){
      if(window.tinymce){window.tinymce.triggerSave();}
    });
  });
})(jQuery);
/* Stage 4: dostępny przełącznik języka podglądu i odświeżenie po zapisie. */
jQuery(function($){
  'use strict';
  var $frame=$('#nc-studio-frame');
  $(document).on('click','.nc-preview-language',function(){
    var $button=$(this), url=$button.attr('data-nc-preview-url');
    if (!$frame.length || !url) return;
    $('.nc-preview-language').attr('aria-pressed','false');
    $button.attr('aria-pressed','true');
    $frame.attr('src',url);
  });
  $(document).on('click','.nc-preview-refresh',function(){
    if($frame.length){$frame.attr('src',$frame.attr('src'));}
  });
  $(document).on('sortupdate','#nc-sortable-sections',function(){
    $(this).children('.nc-section-card').each(function(i){$(this).find('.nc-card-index').text(i+1);});
  });
});

// Potwierdzenie przed usunięciem sekcji dodanej przez użytkownika.
jQuery(function($){
  $(document).on('click','.nc-confirm-remove',function(e){
    if(!window.confirm('Czy na pewno usunąć tę sekcję? Tej operacji nie da się cofnąć.')){e.preventDefault();}
  });
});
