<?php
/** Główne funkcje Theme NOVACELL. @package NovaCellTheme */
defined( 'ABSPATH' ) || exit;

define( 'NC_THEME_VERSION', '0.6.0' );
define( 'NC_THEME_PATH', get_template_directory() );

require_once NC_THEME_PATH . '/inc/routes.php';
require_once NC_THEME_PATH . '/inc/legacy-redirects.php';
require_once NC_THEME_PATH . '/inc/render.php';
require_once NC_THEME_PATH . '/inc/faq.php';
require_once NC_THEME_PATH . '/inc/seo.php';
require_once NC_THEME_PATH . '/inc/sitemap.php';

add_action( 'admin_menu', static function (): void {
    add_theme_page( 'NOVACELL: edycja strony głównej', 'NOVACELL: strona główna', 'manage_options', 'nc-theme-home', static function (): void {
        if ( class_exists( '\NovaCell\Manager\Site_Builder' ) ) {
            \NovaCell\Manager\Site_Builder::home_editor();
        } else {
            echo '<div class="wrap"><h1>NOVACELL</h1><p>Aktywuj najpierw NovaCell Manager 0.7.0, aby zarządzać sekcjami i językami.</p></div>';
        }
    } );
} );

add_action( 'after_setup_theme', static function (): void {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/site.css' );
    register_nav_menus( array( 'primary_pl' => 'Menu główne PL', 'primary_en' => 'Main menu EN' ) );
} );

add_action( 'wp_enqueue_scripts', static function (): void {
    wp_enqueue_style( 'nc-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap', array(), null );
    wp_enqueue_style( 'novacell-style', get_template_directory_uri() . '/assets/css/site.css', array( 'nc-google-fonts' ), NC_THEME_VERSION );
    wp_enqueue_script( 'novacell-ui', get_template_directory_uri() . '/assets/js/site.js', array(), NC_THEME_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

add_filter( 'language_attributes', static function ( string $output ): string {
    if ( 'en' === nc_theme_lang() ) {
        $output = preg_replace( '/lang="[^"]*"/', 'lang="en"', $output );
        if ( false === strpos( $output, 'lang=' ) ) { $output .= ' lang="en"'; }
    }
    return $output;
} );

// Staging content is unapproved. Ensure that a misconfigured preview site is never indexed.
add_filter( 'wp_robots', static function ( array $robots ): array {
    $key = nc_theme_route_key();
    $untranslated = $key && 'en' === nc_theme_lang() && ! nc_theme_translation_available( $key, 'en' );
    $record = nc_theme_public_record();
    $unreviewed = $record && 'approved' !== (string) get_post_meta( $record->ID, '_nc_editorial_status', true );
    if ( ! (bool) get_option( 'nc_site_approved', false ) || $untranslated || $unreviewed ) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
        unset( $robots['index'], $robots['follow'] );
    }
    return $robots;
} );

add_action( 'send_headers', static function (): void {
    if ( ( ! (bool) get_option( 'nc_site_approved', false ) || ( nc_theme_route_key() && 'en' === nc_theme_lang() && ! nc_theme_translation_available( nc_theme_route_key(), 'en' ) ) ) && ! headers_sent() ) {
        header( 'X-Robots-Tag: noindex, nofollow', true );
    }
} );

add_filter( 'document_title_parts', static function ( array $parts ): array {
    $key = nc_theme_route_key();
    if ( $key ) {
        $lang = nc_theme_lang();
        $labels = array(
            'home' => array( 'pl' => 'Z czym możemy Ci pomóc?', 'en' => 'How can we help?' ),
            'how' => array( 'pl' => 'Jak pracujemy', 'en' => 'How we work' ),
            'doctors' => array( 'pl' => 'Lekarze', 'en' => 'Physicians' ),
            'menopause' => array( 'pl' => 'Perimenopauza i menopauza', 'en' => 'Perimenopause and menopause' ),
            'diagnostics' => array( 'pl' => 'Diagnostyka i plan zdrowia', 'en' => 'Preventive health assessment' ),
            'fatigue' => array( 'pl' => 'Zmęczenie', 'en' => 'Persistent fatigue' ),
            'international' => array( 'pl' => 'Pacjenci z zagranicy', 'en' => 'International patients' ),
            'contact' => array( 'pl' => 'Kontakt', 'en' => 'Contact' ),
            'knowledge' => array( 'pl' => 'Wiedza', 'en' => 'Knowledge' ),
        );
        if ( 0 === strpos( $key, 'ncfull-' ) ) {
            $source = substr( $key, 7 );
            $index = (array) get_option( 'nc_full_route_index', array() );
            $post = ! empty( $index[ $source ]['post_id'] ) ? get_post( absint( $index[ $source ]['post_id'] ) ) : null;
            $parts['title'] = $post ? ( nc_theme_record_text( $post, $lang, 'title' ) ?: $post->post_title ) : 'NOVACELL';
        } else {
            $parts['title'] = $labels[ $key ][ $lang ] ?? 'NOVACELL';
        }
    }
    return $parts;
} );

// Oddzielne odpowiedniki językowe, tylko dla znanych tras serwisu.
add_action( 'wp_head', static function (): void {
    $key = nc_theme_route_key();
    $routes = nc_theme_routes();
    if ( ! $key || ! isset( $routes[ $key ] ) ) { return; }
    if ( ! in_array( $key, array( 'home', 'international' ), true ) && 0 !== strpos( $key, 'ncfull-' ) ) { return; }
    if ( 0 === strpos( $key, 'ncfull-' ) ) {
        $source = substr( $key, 7 );
        $index = (array) get_option( 'nc_full_route_index', array() );
        if ( ! nc_theme_translation_available( $key, 'en' ) ) { return; }
    }
    foreach ( array( 'pl', 'en' ) as $lang ) {
        $url = nc_theme_link( $key, $lang );
        echo '<link rel="alternate" hreflang="' . esc_attr( $lang ) . '" href="' . esc_url( $url ) . '">';
    }
}, 4 );
