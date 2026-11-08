<?php
/** Nagłówek z niezależnymi menu PL/EN i dostępną nawigacją mobilną. */
defined( 'ABSPATH' ) || exit;
$lang = nc_theme_lang();
$en = 'en' === $lang;
$current = nc_theme_route_key() ?: 'home';
$english_target = nc_theme_translation_available( $current, 'en' ) ? $current : 'home';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'nc-brand' ); ?>>
<?php wp_body_open(); ?>
<a class="nc-skip" href="#main-content"><?php echo esc_html( $en ? 'Skip to content' : 'Przejdź do treści' ); ?></a>
<header class="nc-header">
  <div class="nc-shell nc-header__inner">
    <a class="nc-logo" href="<?php echo esc_url( nc_theme_link( 'home', $lang ) ); ?>" aria-label="<?php echo esc_attr( $en ? 'NOVACELL home' : 'NOVACELL, strona główna' ); ?>">
      <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/novacell-logo-gradient.png' ); ?>" width="250" height="36" alt="NOVACELL" decoding="async" loading="eager">
    </a>
    <button class="nc-menu-toggle" type="button" aria-controls="nc-primary-nav" aria-expanded="false"><span class="nc-menu-toggle__lines" aria-hidden="true"></span><span><?php echo esc_html( $en ? 'Menu' : 'Menu' ); ?></span></button>
    <nav id="nc-primary-nav" class="nc-nav" aria-label="<?php echo esc_attr( $en ? 'Main navigation' : 'Menu główne' ); ?>">
    <?php if ( has_nav_menu( 'primary_' . $lang ) ) :
        wp_nav_menu( array( 'theme_location' => 'primary_' . $lang, 'container' => false, 'menu_class' => 'nc-nav-list', 'depth' => 2 ) );
    else : ?>
      <ul class="nc-nav-list">
        <?php foreach ( array( 'home' => array( 'Start', 'Home' ), 'how' => array( 'Jak pracujemy', 'How we work' ), 'doctors' => array( 'Lekarze', 'Physicians' ), 'menopause' => array( 'Z czym pomagamy', 'How we help' ), 'contact' => array( 'Kontakt', 'Contact' ) ) as $route => $labels ) : ?>
          <li><a href="<?php echo esc_url( nc_theme_link( $route, $lang ) ); ?>"<?php echo $route === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $labels[ $en ? 1 : 0 ] ); ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    </nav>
    <div class="nc-header-actions">
      <div class="nc-language-switch" role="group" aria-label="<?php echo esc_attr( $en ? 'Language' : 'Wybór języka' ); ?>">
        <a lang="pl" hreflang="pl" href="<?php echo esc_url( nc_theme_link( $current, 'pl' ) ); ?>"<?php echo ! $en ? ' aria-current="page"' : ''; ?>>PL</a>
        <span aria-hidden="true">|</span>
        <a lang="en" hreflang="en" href="<?php echo esc_url( nc_theme_link( $english_target, 'en' ) ); ?>" aria-label="<?php echo esc_attr( $english_target === $current ? 'English version' : 'English homepage: this article has no English translation' ); ?>"<?php echo $en ? ' aria-current="page"' : ''; ?>>EN</a>
      </div>
      <a class="nc-header-cta" href="<?php echo esc_url( nc_theme_link( 'contact', $lang ) ); ?>"><?php echo esc_html( $en ? 'Contact us' : 'Umów rozmowę' ); ?></a>
    </div>
  </div>
</header>
<?php if ( ! (bool) get_option( 'nc_site_approved', false ) && (bool) get_option( 'nc_public_preview', true ) ) : ?>
<div class="nc-preview-notice" role="status"><div class="nc-shell"><?php echo esc_html( $en ? 'Information under clinical review. Descriptions are educational, not individual medical advice or a confirmed treatment offer.' : 'Treści w trakcie weryfikacji medycznej. Mają charakter informacyjny i nie stanowią indywidualnej porady ani potwierdzonej oferty leczenia.' ); ?></div></div>
<?php endif; ?>
