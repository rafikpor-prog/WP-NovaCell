<?php
/**
 * Dostępne pytania i odpowiedzi z dokumentacji NOVACELL.
 * Element details/summary jest w pełni obsługiwalny bez JavaScript.
 * @package NovaCellTheme
 */
defined( 'ABSPATH' ) || exit;

function nc_theme_faq_extract( string $body ): array {
    $questions = array();
    if ( ! preg_match( '~<h[2-4][^>]*>\s*(?:Pytania\s+i\s+odpowiedzi|Frequently\s+asked\s+questions|FAQ|Questions\s+and\s+answers)\s*</h[2-4]>(.*?)(?=<h[2-4][^>]*>|\z)~isu', $body, $match ) ) {
        return array();
    }
    preg_match_all( '~<p[^>]*>(.*?)</p>~isu', $match[1], $items );
    foreach ( (array) ( $items[1] ?? array() ) as $entry ) {
        $plain = trim( html_entity_decode( wp_strip_all_tags( $entry ), ENT_QUOTES, 'UTF-8' ) );
        $mark = strpos( $plain, '?' );
        if ( false === $mark || $mark < 5 || $mark > 220 ) { continue; }
        $question = trim( substr( $plain, 0, $mark + 1 ) );
        $answer = trim( substr( $plain, $mark + 1 ) );
        if ( '' !== $answer ) {
            $questions[] = array( 'question' => $question, 'answer' => $answer );
        }
    }
    return array_slice( $questions, 0, 12 );
}

function nc_theme_faq_replace( string $body, string $lang ): string {
    $items = nc_theme_faq_extract( $body );
    if ( ! $items ) { return nc_theme_wrap_tables( nc_theme_html( $body ), $lang ); }
    $title = 'en' === $lang ? 'Frequently asked questions' : 'Pytania i odpowiedzi';
    $replacement = '<section class="nc-faq" aria-labelledby="nc-faq-heading">';
    $replacement .= '<div class="nc-faq-heading"><span class="nc-eyebrow">NOVACELL</span><h2 id="nc-faq-heading">' . esc_html( $title ) . '</h2></div>';
    foreach ( $items as $item ) {
        $replacement .= '<details class="nc-faq-item"><summary><span>' . esc_html( $item['question'] ) . '</span><span class="nc-faq-indicator" aria-hidden="true"></span></summary>';
        $replacement .= '<div class="nc-faq-answer"><p>' . esc_html( $item['answer'] ) . '</p></div></details>';
    }
    $replacement .= '</section>';
    return nc_theme_wrap_tables( (string) preg_replace_callback( '~<h[2-4][^>]*>\s*(?:Pytania\s+i\s+odpowiedzi|Frequently\s+asked\s+questions|FAQ|Questions\s+and\s+answers)\s*</h[2-4]>(.*?)(?=<h[2-4][^>]*>|\z)~isu', static function () use ( $replacement ): string { return $replacement; }, nc_theme_html( $body ), 1 ), $lang );
}

/** Horizontal scrolling of wide clinical tables with a keyboard-focusable region. */
function nc_theme_wrap_tables( string $html, string $lang ): string {
    return (string) preg_replace_callback( '~<table\b[^>]*>.*?</table>~isu', static function ( array $match ) use ( $lang ): string {
        $label = 'en' === $lang ? 'Table of clinical information, scroll horizontally if needed' : 'Tabela informacji, w razie potrzeby przewijana w poziomie';
        return '<div class="nc-table-scroll" role="region" tabindex="0" aria-label="' . esc_attr( $label ) . '">' . $match[0] . '</div>';
    }, $html );
}
