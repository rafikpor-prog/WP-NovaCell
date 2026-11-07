<?php
/** Exact-match historical redirects extracted from Annex C6, 55 rows incl. homepage. */
defined( 'ABSPATH' ) || exit;

function nc_theme_legacy_redirects(): array {
    return require NC_THEME_PATH . '/inc/redirects-map.php';
}

/** Verify destination is an active route, never redirect to a missing draft. */
function nc_theme_legacy_valid_target( string $destination ): bool {
    $resolved = nc_theme_resolve_path( $destination );
    return $resolved && nc_theme_verified_route( $resolved[0] );
}

add_action( 'template_redirect', static function (): void {
    if ( is_admin() || ! isset( $_SERVER['REQUEST_METHOD'] ) || ! in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'HEAD' ), true ) ) {
        return;
    }
    $path = nc_theme_requested_path();
    $map = nc_theme_legacy_redirects();
    if ( ! isset( $map[ $path ] ) ) { return; }
    $destination = $map[ $path ];
    if ( $destination === $path || ! nc_theme_legacy_valid_target( $destination ) ) { return; }
    // Both URLs use the same configured WordPress origin. No arbitrary redirect targets.
    wp_safe_redirect( home_url( $destination ), 301, 'NOVACELL' );
    exit;
}, -20 );
