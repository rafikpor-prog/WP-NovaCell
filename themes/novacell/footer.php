<?php $lang=nc_theme_lang();$en='en'===$lang; ?>
<footer class="nc-footer" aria-label="<?php echo esc_attr($en?'Footer':'Stopka serwisu'); ?>">
  <div class="nc-shell nc-footer__grid">
    <div><a class="nc-footer-logo" href="<?php echo esc_url(nc_theme_link('home',$lang)); ?>"><img src="<?php echo esc_url(get_template_directory_uri().'/assets/images/novacell-logo-gradient.png'); ?>" width="225" height="32" alt="NOVACELL"></a><p><?php echo esc_html($en?'We listen first. Then we plan your care.':'Najpierw rozumiemy. Potem leczymy.'); ?></p></div>
    <div><h2><?php echo esc_html($en?'Address':'Adres'); ?></h2><p>ul. Ogrodowa 48<br>00-876 Warszawa, Polska</p></div>
    <div><h2><?php echo esc_html($en?'Contact':'Kontakt'); ?></h2><p><a href="mailto:contact@novacell.clinic">contact@novacell.clinic</a></p><p><a href="<?php echo esc_url(nc_theme_link('contact',$lang)); ?>"><?php echo esc_html($en?'Contact details':'Dane kontaktowe'); ?></a></p></div>
  </div>
  <div class="nc-shell nc-footer-bottom"><span>© <?php echo esc_html(gmdate('Y')); ?> NOVACELL Sp. z o.o.</span><span>KRS 0001192597 · NIP 9512629183</span></div>
</footer>
<?php wp_footer(); ?>
</body></html>
