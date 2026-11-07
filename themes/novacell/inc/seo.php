<?php
/**
 * Wydajna, serwerowa warstwa SEO / GEO / AI — bez zbędnej generacji treści.
 * @package NovaCellTheme
 */
defined( 'ABSPATH' ) || exit;

function nc_theme_seo_plugin_active(): bool {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

function nc_theme_public_record(): ?\WP_Post {
    $key = nc_theme_route_key();
    if ( 0 !== strpos( $key, 'ncfull-' ) ) { return null; }
    $index = (array) get_option( 'nc_full_route_index', array() );
    $source = substr( $key, 7 );
    $id = absint( $index[ $source ]['post_id'] ?? 0 );
    $post = $id ? get_post( $id ) : null;
    return $post && 'publish' === $post->post_status ? $post : null;
}

add_filter( 'wp_resource_hints', static function ( array $urls, string $relation_type ): array {
    if ( 'preconnect' === $relation_type ) {
        $urls[] = array( 'href' => 'https://fonts.googleapis.com' );
        $urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' );
    }
    return $urls;
}, 10, 2 );

add_action( 'wp_head', static function (): void {
    $key = nc_theme_route_key();
    if ( ! $key ) { return; }
    $lang = nc_theme_lang();
    $post = nc_theme_public_record();
    $lead = $post ? nc_theme_record_text( $post, $lang, 'lead' ) : '';
    if ( 'home' === $key ) {
        $sections = (array) get_option( 'nc_home_sections', array() );
        foreach ( $sections as $section ) {
            if ( 'hero' === ( $section['id'] ?? '' ) ) {
                $lead = (string) ( $section[ $lang ]['body'] ?? $section[ $lang ]['lead'] ?? '' );
                break;
            }
        }
    }
    $description = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $lead ) ) );
    $description = function_exists( 'mb_substr' ) ? mb_substr( $description, 0, 160 ) : substr( $description, 0, 155 );
    if ( ! nc_theme_seo_plugin_active() ) {
        if ( $description ) { echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n"; }
        echo '<link rel="canonical" href="' . esc_url( nc_theme_link( $key, $lang ) ) . '">' . "\n";
    }

    // Dane strukturalne kliniki są emitowane wyłącznie po formalnej akceptacji strony.
    if ( ! (bool) get_option( 'nc_site_approved', false ) ) { return; }
    // Search engines must not index medical records that still require clinical review.
    if ( $post && 'approved' !== (string) get_post_meta( $post->ID, '_nc_editorial_status', true ) ) { return; }
    $clinic = array(
        '@context' => 'https://schema.org',
        '@type' => 'MedicalClinic',
        'name' => 'NOVACELL',
        'url' => home_url( '/' ),
        'address' => array(
            '@type' => 'PostalAddress',
            'streetAddress' => 'Ogrodowa 48',
            'addressLocality' => 'Warszawa',
            'postalCode' => '00-876',
            'addressCountry' => 'PL',
        ),
    );
    if ( 'home' === $key ) {
        echo '<script type="application/ld+json">' . wp_json_encode( $clinic, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n";
    }
    if ( $post ) {
        $page = array(
            '@context' => 'https://schema.org',
            '@type' => 'MedicalWebPage',
            'name' => nc_theme_record_text( $post, $lang, 'title' ) ?: $post->post_title,
            'url' => nc_theme_link( $key, $lang ),
            'dateModified' => get_post_modified_time( 'c', true, $post ),
            'inLanguage' => $lang,
            'description' => $description,
            'publisher' => array( '@type' => 'Organization', 'name' => 'NOVACELL' ),
        );
        echo '<script type="application/ld+json">' . wp_json_encode( $page, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n";
        $faq = nc_theme_faq_extract( nc_theme_record_text( $post, $lang, 'body' ) );
        if ( $faq ) {
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map( static function ( array $item ): array {
                    return array( '@type' => 'Question', 'name' => $item['question'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $item['answer'] ) );
                }, $faq ),
            );
            echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n";
        }
    }
}, 7 );
