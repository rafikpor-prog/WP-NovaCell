<?php
/** @package NovaCellVisualEditor */
namespace NovaCell\Visual;

defined( 'ABSPATH' ) || exit;

final class Section_Block {
    public static function register(): void {
        register_block_type(
            NC_VISUAL_PATH . 'blocks/section',
            array( 'render_callback' => array( self::class, 'render' ) )
        );
    }

    /** @param array<string,mixed> $attributes */
    public static function render( array $attributes ): string {
        $id = sanitize_key( (string) ( $attributes['sectionId'] ?? '' ) );
        $language = (string) ( $attributes['language'] ?? 'auto' );
        $language = in_array( $language, array( 'pl', 'en' ), true ) ? $language :
            ( function_exists( 'nc_theme_lang' ) ? \nc_theme_lang() : 'pl' );
        foreach ( (array) get_option( 'nc_home_sections', array() ) as $section ) {
            if ( ! is_array( $section ) || $id !== sanitize_key( (string) ( $section['id'] ?? '' ) ) || empty( $section['enabled'] ) ) { continue; }
            $copy = (array) ( $section[ $language ] ?? array() );
            $title = sanitize_text_field( (string) ( $copy['title'] ?? '' ) );
            $lead = sanitize_textarea_field( (string) ( $copy['lead'] ?? '' ) );
            $body = wp_kses_post( (string) ( $copy['body'] ?? '' ) );
            $html = '<section class="nc-visual-section" lang="' . esc_attr( $language ) . '">';
            if ( '' !== $title ) { $html .= '<h2>' . esc_html( $title ) . '</h2>'; }
            if ( '' !== $lead ) { $html .= '<p class="nc-visual-section__lead">' . esc_html( $lead ) . '</p>'; }
            if ( '' !== $body ) { $html .= '<div class="nc-visual-section__body">' . $body . '</div>'; }
            $label = sanitize_text_field( (string) ( $copy['cta_label'] ?? '' ) );
            $url = esc_url( (string) ( $copy['cta_url'] ?? '' ) );
            if ( $label && $url ) { $html .= '<p><a class="nc-button" href="' . $url . '">' . esc_html( $label ) . '</a></p>'; }
            $html .= '</section>';
            return $html;
        }
        // Editors can see this placeholder, visitors should never see a fake section.
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p class="nc-visual-placeholder">' . esc_html__( 'Wybrana sekcja NOVACELL nie jest dostępna lub jest ukryta.', 'novacell-visual-editor' ) . '</p>';
        }
        return '';
    }
}
