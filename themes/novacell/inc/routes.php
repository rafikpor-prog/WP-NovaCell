<?php
/**
 * Canonical virtual routes and a query-string fallback that does not require
 * Apache mod_rewrite. All routes are exact-match, never a catch-all for third-party content.
 * @package NovaCellTheme
 */
defined( 'ABSPATH' ) || exit;

function nc_theme_routes(): array {
    $routes = array(
        'home' => array( 'pl' => '/', 'en' => '/en/' ),
        'how' => array( 'pl' => '/jak-pracujemy/', 'en' => '/en/how-we-work/' ),
        'doctors' => array( 'pl' => '/lekarze/', 'en' => '/en/doctors/' ),
        'jakub-rzepka' => array( 'pl' => '/lekarze/jakub-rzepka/', 'en' => '/en/doctors/jakub-rzepka/' ),
        'katarzyna-moson-rzepka' => array( 'pl' => '/lekarze/katarzyna-moson-rzepka/', 'en' => '/en/doctors/katarzyna-moson-rzepka/' ),
        'przemyslaw-szalanski' => array( 'pl' => '/lekarze/przemyslaw-szalanski/', 'en' => '/en/doctors/przemyslaw-szalanski/' ),
        'menopause' => array( 'pl' => '/z-czym-pomagamy/menopauza/', 'en' => '/en/help-with/menopause/' ),
        'diagnostics' => array( 'pl' => '/chce-zadbac-o-zdrowie/diagnostyka-i-plan/', 'en' => '/en/preventive-health/assessment-and-plan/' ),
        'fatigue' => array( 'pl' => '/z-czym-pomagamy/zmeczenie/', 'en' => '/en/help-with/persistent-fatigue/' ),
        'knowledge' => array( 'pl' => '/wiedza/', 'en' => '/en/knowledge/' ),
        'international' => array( 'pl' => '/pacjenci-z-zagranicy/', 'en' => '/en/international-patients/' ),
        'contact' => array( 'pl' => '/kontakt/', 'en' => '/en/contact/' ),
        'help-index' => array( 'pl' => '/z-czym-pomagamy/', 'en' => '/en/help-with/' ),
        'prevention-index' => array( 'pl' => '/chce-zadbac-o-zdrowie/', 'en' => '/en/preventive-health/' ),
        'aesthetics-index' => array( 'pl' => '/wyglad-i-estetyka/', 'en' => '/en/appearance-and-aesthetics/' ),
        'methods-index' => array( 'pl' => '/metody/', 'en' => '/en/methods/' ),
        'prices' => array( 'pl' => '/cennik/', 'en' => '/en/prices/' ),
    );
    foreach ( (array) get_option( 'nc_full_route_index', array() ) as $key => $route ) {
        $name = 'ncfull-' . sanitize_key( (string) $key );
        if ( ! empty( $route['pl'] ) && ! empty( $route['en'] ) ) {
            $routes[ $name ] = array( 'pl' => (string) $route['pl'], 'en' => (string) $route['en'] );
        }
    }
    return $routes;
}


/** A route is available only if its imported record remains published. */
function nc_theme_route_published( string $key ): bool {
    if ( 0 !== strpos( $key, 'ncfull-' ) ) { return true; }
    $index  = (array) get_option( 'nc_full_route_index', array() );
    $source = substr( $key, 7 );
    $id     = absint( $index[ $source ]['post_id'] ?? 0 );
    return $id > 0 && 'publish' === get_post_status( $id );
}

/** For EN pages, the translated body must really exist. */
function nc_theme_translation_available( string $key, string $lang ): bool {
    if ( 'pl' === $lang || 0 !== strpos( $key, 'ncfull-' ) ) { return true; }
    if ( 'ncfull-topic-22' === $key ) { return true; }
    $index  = (array) get_option( 'nc_full_route_index', array() );
    $id     = absint( $index[ substr( $key, 7 ) ]['post_id'] ?? 0 );
    if ( ! $id ) { return false; }
    $body = (string) get_post_meta( $id, 'nc_en_body', true );
    return '' !== trim( wp_strip_all_tags( $body ) );
}

/** Query fallback only for routes known to NOVACELL; never intercept other plugins. */
function nc_theme_verified_route( string $key ): bool {
    return isset( nc_theme_routes()[ $key ] ) && nc_theme_route_published( $key );
}

function nc_theme_link( string $key, string $lang = 'pl' ): string {
    $routes = nc_theme_routes();
    $lang   = 'en' === $lang ? 'en' : 'pl';
    if ( ! isset( $routes[ $key ] ) ) { $key = 'home'; }
    // Etap 5: w trybie bezpiecznym działamy bez .htaccess / nginx rewrite.
    // Przyjazne URL można uruchomić w diagnostyce po prawdziwym teście serwera.
    if ( 'pretty' !== get_option( 'nc_route_mode', 'safe' ) || '' === (string) get_option( 'permalink_structure', '' ) ) {
        return add_query_arg( array( 'nc_view' => $key, 'nc_lang' => $lang ), home_url( '/' ) );
    }
    return home_url( $routes[ $key ][ $lang ] );
}

function nc_theme_resolve_path( string $path ): array {
    $path = rawurldecode( $path );
    $path = preg_replace( '#/{2,}#', '/', $path );
    // Only absolute site-relative paths, no encoded query strings.
    if ( false !== strpos( $path, '?' ) ) { $path = strstr( $path, '?', true ); }
    $path = '/' . trim( $path, '/' ) . '/';
    if ( '//' === $path ) { $path = '/'; }
    $matches = array();
    foreach ( nc_theme_routes() as $key => $translations ) {
        foreach ( array( 'pl', 'en' ) as $lang ) {
            if ( isset( $translations[ $lang ] ) && untrailingslashit( $path ) === untrailingslashit( $translations[ $lang ] ) ) {
                $matches[] = array( $key, $lang );
            }
        }
    }
    if ( ! $matches ) { return array(); }
    foreach ( $matches as $match ) {
        if ( 0 === strpos( $match[0], 'ncfull-' ) &&
            ! in_array( $match[0], array( 'ncfull-topic-23', 'ncfull-topic-26', 'ncfull-topic-30' ), true ) ) {
            return $match;
        }
    }
    foreach ( $matches as $match ) {
        if ( 0 !== strpos( $match[0], 'ncfull-' ) ) { return $match; }
    }
    return $matches[0];
}

function nc_theme_register_rewrites(): void {
    $seen = array();
    foreach ( nc_theme_routes() as $langs ) {
        foreach ( $langs as $url ) {
            if ( '/' === $url ) { continue; }
            $path = trim( $url, '/' );
            if ( isset( $seen[ $path ] ) ) { continue; }
            $seen[ $path ] = true;
            $resolved = nc_theme_resolve_path( '/' . $path . '/' );
            if ( $resolved ) {
                add_rewrite_rule( '^' . preg_quote( $path, '#' ) . '/?$',
                    'index.php?nc_view=' . $resolved[0] . '&nc_lang=' . $resolved[1], 'top' );
            }
        }
    }
}
add_action( 'init', 'nc_theme_register_rewrites' );

add_filter( 'query_vars', static function ( array $vars ): array {
    $vars[] = 'nc_view';
    $vars[] = 'nc_lang';
    return array_unique( $vars );
} );

/** Read path independently of rewrite rules, including WP installations in subfolders. */
function nc_theme_requested_path(): string {
    $request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '/';
    $path = (string) wp_parse_url( $request, PHP_URL_PATH );
    $home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    $base = '/' . trim( $home_path, '/' );
    if ( '/' !== $base && ( $path === $base || 0 === strpos( $path, $base . '/' ) ) ) {
        $path = substr( $path, strlen( $base ) );
    }
    return '/' . trim( $path, '/' ) . '/';
}

add_action( 'parse_request', static function ( $wp ): void {
    if ( is_admin() ) { return; }
    $routes = nc_theme_routes();
    $key  = sanitize_key( (string) ( $wp->query_vars['nc_view'] ?? '' ) );
    $lang = 'en' === (string) ( $wp->query_vars['nc_lang'] ?? '' ) ? 'en' : 'pl';
    if ( ! isset( $routes[ $key ] ) ) {
        // Parse-request rescue works even when Apache has stale rewrite rules,
        // provided the request reaches index.php.
        $resolved = nc_theme_resolve_path( nc_theme_requested_path() );
        if ( ! $resolved ) { return; }
        list( $key, $lang ) = $resolved;
    }
    $wp->query_vars['nc_view'] = $key;
    $wp->query_vars['nc_lang'] = $lang;
    unset( $wp->query_vars['error'], $wp->query_vars['pagename'], $wp->query_vars['name'], $wp->query_vars['page_id'] );
}, 100 );

/** Fix virtual posts becoming a 404 after WP_Query finds no underlying WP page. */
add_filter( 'pre_handle_404', static function ( $handled, $query ) {
    if ( ! $query->is_main_query() ) { return $handled; }
    $key = sanitize_key( (string) $query->get( 'nc_view', '' ) );
    if ( ! $key ) {
        $match = nc_theme_resolve_path( nc_theme_requested_path() );
        $key = $match[0] ?? '';
        if ( $key && nc_theme_verified_route( $key ) ) {
            $query->set( 'nc_view', $key );
            $query->set( 'nc_lang', $match[1] );
        }
    }
    if ( $key && nc_theme_verified_route( $key ) ) {
        $query->is_404 = false;
        $query->is_home = false;
        return true;
    }
    return $handled;
}, 1, 2 );

add_filter( 'redirect_canonical', static function ( $redirect, $requested ) {
    $key = sanitize_key( (string) get_query_var( 'nc_view', '' ) );
    if ( $key && nc_theme_verified_route( $key ) ) { return false; }
    return $redirect;
}, 10, 2 );

add_filter( 'template_include', static function ( string $template ): string {
    $key = sanitize_key( (string) get_query_var( 'nc_view', '' ) );
    if ( ! $key ) {
        $match = nc_theme_resolve_path( nc_theme_requested_path() );
        if ( $match ) {
            $key = $match[0];
            set_query_var( 'nc_view', $match[0] );
            set_query_var( 'nc_lang', $match[1] );
        }
    }
    if ( $key && nc_theme_verified_route( $key ) ) {
        global $wp_query;
        if ( $wp_query instanceof \WP_Query ) {
            $wp_query->is_404 = false;
            $wp_query->is_home = false;
        }
        status_header( 200 );
        return NC_THEME_PATH . '/route.php';
    }
    return $template;
}, 99 );

add_action( 'template_redirect', static function (): void {
    $key = sanitize_key( (string) get_query_var( 'nc_view', '' ) );
    if ( $key && nc_theme_verified_route( $key ) ) {
        status_header( 200 );
        if ( current_user_can( 'manage_options' ) && ! headers_sent() ) { nocache_headers(); }
    }
}, 0 );

add_action( 'after_switch_theme', static function (): void {
    nc_theme_register_rewrites();
    flush_rewrite_rules( false );
} );

function nc_theme_route_key(): string {
    $key = sanitize_key( (string) get_query_var( 'nc_view', '' ) );
    if ( $key && nc_theme_verified_route( $key ) ) { return $key; }
    $resolved = nc_theme_resolve_path( nc_theme_requested_path() );
    if ( $resolved && nc_theme_verified_route( $resolved[0] ) ) { return $resolved[0]; }
    return is_front_page() || is_home() ? 'home' : '';
}

function nc_theme_lang(): string {
    $lang = (string) get_query_var( 'nc_lang', '' );
    if ( 'en' === $lang || 'pl' === $lang ) { return $lang; }
    $resolved = nc_theme_resolve_path( nc_theme_requested_path() );
    return ( $resolved && 'en' === $resolved[1] ) ? 'en' : 'pl';
}

/** Zastępuje stare ręczne adresy menu działającymi linkami bez rewrite. */
add_filter( 'nav_menu_link_attributes', static function ( array $atts, $item ): array {
    if ( 'pretty' === get_option( 'nc_route_mode', 'safe' ) ) { return $atts; }
    if ( empty( $atts['href'] ) ) { return $atts; }
    $url = (string) $atts['href'];
    if ( ! str_starts_with( $url, home_url( '/' ) ) ) { return $atts; }
    $relative = (string) wp_parse_url( $url, PHP_URL_PATH );
    $prefix = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( $prefix && '/' !== $prefix && str_starts_with( $relative, $prefix ) ) {
        $relative = substr( $relative, strlen( rtrim( $prefix, '/' ) ) );
    }
    $match = nc_theme_resolve_path( $relative );
    if ( $match ) { $atts['href'] = nc_theme_link( $match[0], $match[1] ); }
    return $atts;
}, 10, 2 );
