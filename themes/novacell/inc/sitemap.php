<?php
/**
 * Virtual sitemap for approved WordPress-free NOVACELL routes.
 * Ordinary WordPress pages/posts continue to use WordPress' built-in sitemap.
 */
defined( 'ABSPATH' ) || exit;

function nc_theme_sitemap_url(): string {
    return 'pretty' === get_option( 'nc_route_mode', 'safe' )
        ? home_url( '/novacell-sitemap.xml' )
        : add_query_arg( array( 'nc_sitemap' => 1 ), home_url( '/' ) );
}

add_action( 'init', static function (): void {
    add_rewrite_rule( '^novacell-sitemap\\.xml/?$', 'index.php?nc_sitemap=1', 'top' );
} );
add_filter( 'query_vars', static function ( array $vars ): array {
    $vars[] = 'nc_sitemap';
    return $vars;
} );

add_filter( 'robots_txt', static function ( string $robots, bool $public ): string {
    if ( $public && (bool) get_option( 'nc_site_approved', false ) ) {
        $robots .= "\nSitemap: " . nc_theme_sitemap_url() . "\n";
    }
    return $robots;
}, 15, 2 );

function nc_theme_sitemap_requested(): bool {
    if ( '1' === (string) get_query_var( 'nc_sitemap', '' ) ) { return true; }
    return isset( $_GET['nc_sitemap'] ) && '1' === (string) wp_unslash( $_GET['nc_sitemap'] )
        || '/novacell-sitemap.xml/' === nc_theme_requested_path();
}

/** Build rows without retrieving content bodies or calling external services. */
function nc_theme_sitemap_entries(): array {
    if ( ! (bool) get_option( 'nc_site_approved', false ) ) { return array(); }
    $entries = array();
    $registered = nc_theme_routes();
    foreach ( $registered as $key => $translations ) {
        if ( ! nc_theme_verified_route( $key ) ) { continue; }
        $record = null;
        if ( 0 === strpos( $key, 'ncfull-' ) ) {
            $index = (array) get_option( 'nc_full_route_index', array() );
            $id = absint( $index[ substr( $key, 7 ) ]['post_id'] ?? 0 );
            $record = $id ? get_post( $id ) : null;
            if ( ! $record || 'approved' !== get_post_meta( $id, '_nc_editorial_status', true ) ) { continue; }
        } elseif ( ! in_array( $key, array( 'home', 'how', 'doctors', 'contact', 'knowledge', 'help-index', 'prevention-index', 'aesthetics-index', 'methods-index', 'prices', 'international' ), true ) ) {
            // Static specialist pages may duplicate full-document routes; avoid duplicate indexing.
            continue;
        }
        foreach ( array( 'pl', 'en' ) as $lang ) {
            if ( 'en' === $lang && ! nc_theme_translation_available( $key, 'en' ) ) { continue; }
            $url = nc_theme_link( $key, $lang );
            if ( isset( $entries[ $url ] ) ) { continue; }
            $entries[ $url ] = array(
                'loc' => $url,
                'lastmod' => $record ? get_post_modified_time( 'c', true, $record ) : '',
            );
        }
    }
    return array_values( $entries );
}

add_action( 'template_redirect', static function (): void {
    if ( ! nc_theme_sitemap_requested() ) { return; }
    if ( ! (bool) get_option( 'nc_site_approved', false ) ) {
        status_header( 404 );
        header( 'Content-Type: text/plain; charset=UTF-8' );
        echo 'Sitemap is disabled until the clinic reviews and approves the content.';
        exit;
    }
    status_header( 200 );
    header( 'Content-Type: application/xml; charset=UTF-8' );
    $entries = nc_theme_sitemap_entries();
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ( $entries as $row ) {
        echo '<url><loc>' . htmlspecialchars( $row['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8' ) . '</loc>';
        if ( ! empty( $row['lastmod'] ) ) {
            echo '<lastmod>' . htmlspecialchars( $row['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8' ) . '</lastmod>';
        }
        echo '</url>' . "\n";
    }
    echo '</urlset>';
    exit;
}, -40 );
