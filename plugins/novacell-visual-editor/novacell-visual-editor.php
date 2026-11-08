<?php
/**
 * Plugin Name: NOVACELL Visual Editor
 * Description: Wizualna nakładka na edytor blokowy WordPress. Kafelki, własne ikony, inspektor stylów i dynamiczna sekcja NOVACELL.
 * Version: 0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Text Domain: novacell-visual-editor
 * License: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;

define( 'NC_VISUAL_VERSION', '0.2.0' );
define( 'NC_VISUAL_PATH', plugin_dir_path( __FILE__ ) );
define( 'NC_VISUAL_URL', plugin_dir_url( __FILE__ ) );

require_once NC_VISUAL_PATH . 'includes/class-section-block.php';

add_action( 'init', static function (): void {
    if ( function_exists( 'register_block_type' ) ) {
        \NovaCell\Visual\Section_Block::register();
    }
} );

add_action( 'enqueue_block_editor_assets', static function (): void {
    if ( ! current_user_can( 'edit_posts' ) ) { return; }
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || ! $screen->is_block_editor() ) { return; }

    $deps = array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-blocks', 'wp-block-editor', 'wp-editor', 'wp-edit-post', 'wp-i18n' );
    wp_enqueue_script( 'novacell-visual-editor', NC_VISUAL_URL . 'assets/editor.js', $deps, NC_VISUAL_VERSION, true );
    wp_enqueue_style( 'novacell-visual-editor', NC_VISUAL_URL . 'assets/editor.css', array(), NC_VISUAL_VERSION );
    $sections = array();
    foreach ( (array) get_option( 'nc_home_sections', array() ) as $section ) {
        if ( ! is_array( $section ) || empty( $section['id'] ) ) { continue; }
        $sections[] = array(
            'value' => sanitize_key( (string) $section['id'] ),
            'label' => sanitize_text_field( (string) ( $section['pl']['title'] ?? $section['id'] ) ),
        );
    }
    wp_add_inline_script(
        'novacell-visual-editor',
        'window.NovaCellVisualConfig = ' . wp_json_encode( array(
            'icons'    => NC_VISUAL_URL . 'assets/icons/',
            'sections' => $sections,
            'homeUrl'  => home_url( '/' ),
            'autoOpen' => isset( $_GET['novacell_visual'] ) && '1' === (string) $_GET['novacell_visual'],
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
        'before'
    );
} );

/**
 * Bezpośredni punkt startowy. Używany niezależnie od widoczności panelu bocznego Gutenberg.
 * Nie tworzymy publicznej strony demonstracyjnej; canvas jest prywatnym szkicem.
 */
function nc_visual_canvas_id(): int {
    $id = absint( get_option( 'nc_visual_canvas_page_id', 0 ) );
    if ( $id && 'page' === get_post_type( $id ) ) { return $id; }
    return 0;
}

function nc_visual_ensure_canvas(): int {
    $id = nc_visual_canvas_id();
    if ( $id ) { return $id; }
    if ( ! current_user_can( 'edit_pages' ) ) { return 0; }
    $existing = get_posts( array(
        'post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => 1,
        'meta_key' => '_nc_visual_canvas', 'meta_value' => '1', 'fields' => 'ids',
    ) );
    if ( $existing ) { $id = absint( $existing[0] ); }
    else {
        $id = wp_insert_post( array(
            'post_type' => 'page', 'post_status' => 'draft',
            'post_title' => 'NOVACELL — pracownia edytora wizualnego',
            'post_name' => 'novacell-visual-workspace',
            'post_content' => '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">NOVACELL — pracownia układu</h2><!-- /wp:heading -->' . "\n" .
                '<!-- wp:paragraph --><p>To robocza strona do układania bloków. Stronę główną oraz teksty medyczne edytuj w NOVACELL Manager.</p><!-- /wp:paragraph -->',
        ), true );
        if ( is_wp_error( $id ) ) { return 0; }
        update_post_meta( (int) $id, '_nc_visual_canvas', '1' );
    }
    update_option( 'nc_visual_canvas_page_id', (int) $id, false );
    return (int) $id;
}

add_action( 'admin_init', static function (): void {
    if ( current_user_can( 'edit_pages' ) ) { nc_visual_ensure_canvas(); }
} );
register_activation_hook( __FILE__, 'nc_visual_ensure_canvas' );

function nc_visual_edit_url(): string {
    $id = nc_visual_ensure_canvas();
    return $id ? add_query_arg( array( 'novacell_visual' => '1' ), admin_url( 'post.php?post=' . $id . '&action=edit' ) ) : admin_url( 'edit.php?post_type=page' );
}

add_action( 'admin_menu', static function (): void {
    add_menu_page(
        'NOVACELL Visual Editor', 'NOVACELL Visual Editor', 'edit_pages',
        'novacell-visual-editor', 'nc_visual_welcome', 'dashicons-layout', 26
    );
    if ( defined( 'NC_MANAGER_VERSION' ) ) {
        add_submenu_page( 'novacell-manager', 'Visual Editor', 'Visual Editor',
            'edit_pages', 'novacell-visual-editor', 'nc_visual_welcome' );
    }
    $open_hook = add_submenu_page(
        'novacell-visual-editor', 'Uruchom Visual Editor', 'Otwórz edytor',
        'edit_pages', 'novacell-visual-open', 'nc_visual_welcome'
    );
    if ( $open_hook ) {
        add_action( 'load-' . $open_hook, static function (): void {
            if ( ! current_user_can( 'edit_pages' ) ) { wp_die( 'Forbidden', '', array( 'response' => 403 ) ); }
            wp_safe_redirect( nc_visual_edit_url() );
            exit;
        } );
    }
} );

function nc_visual_welcome(): void {
    if ( ! current_user_can( 'edit_pages' ) ) { return; }
    $enabled = function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( 'page' );
    echo '<div class="wrap" style="max-width:1100px">';
    echo '<h1>NOVACELL Visual Editor ' . esc_html( NC_VISUAL_VERSION ) . '</h1>';
    echo '<p>Otwórz bezpośrednio edytor bloków WordPress. Po prawej stronie edytora wybierz panel <strong>NOVACELL Visual Editor</strong> (ikona układu w górnym pasku, lub menu Więcej → Wtyczki).</p>';
    if ( ! $enabled ) {
        echo '<div class="notice notice-error inline"><p>Edytor blokowy jest wyłączony dla stron. Sprawdź ustawienia Gutenberga oraz wtyczki typu Classic Editor.</p></div>';
    }
    echo '<p><a class="button button-primary button-hero" href="' . esc_url( nc_visual_edit_url() ) . '">Otwórz NOVACELL Visual Editor</a> ';
    echo '<a class="button button-hero" href="' . esc_url( admin_url( 'admin.php?page=novacell-home-builder' ) ) . '">Edytuj stronę główną PL/EN</a> ';
    echo '<a class="button button-hero" href="' . esc_url( admin_url( 'admin.php?page=novacell-diagnostics' ) ) . '">Sprawdź trasy i 404</a></p>';
    echo '<h2>Jaki ekran służy do czego?</h2><p><strong>Visual Editor</strong> edytuje natywne strony i wpisy Gutenberg. <strong>Menedżer strony głównej</strong> edytuje dane dziewięciu dynamicznych sekcji i obie wersje językowe. <strong>Podstrony PL/EN</strong> służą do edycji materiałów kliniki z importu Word.</p>';
    echo '<p>Po kliknięciu przycisku otwierającego edytor zakładka NOVACELL pojawi się automatycznie, jeśli używany Gutenberg udostępnia API paska bocznego. W przeciwnym przypadku wybierz ją w menu paneli edytora.</p>';
    echo '<p>Ten ekran jest dostępny z lewego menu WordPress. Nie otwieraj adresu <code>/novacell-visual-editor</code> w witrynie; to identyfikator panelu administracyjnego.</p>';
    echo '</div>';
}

add_action( 'admin_bar_menu', static function ( $bar ): void {
    if ( ! current_user_can( 'edit_pages' ) ) { return; }
    $bar->add_node( array(
        'id' => 'nc-visual-open',
        'title' => 'NOVACELL Visual Editor',
        'href' => admin_url( 'admin.php?page=novacell-visual-editor' ),
    ) );
}, 82 );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), static function ( array $links ): array {
    array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=novacell-visual-editor' ) ) . '">Otwórz edytor</a>' );
    return $links;
} );

/** Prosty, wyraźny link otwierający panel wizualny z listy stron/wpisów. */
add_filter( 'page_row_actions', 'nc_visual_row_action', 50, 2 );
add_filter( 'post_row_actions', 'nc_visual_row_action', 50, 2 );
function nc_visual_row_action( array $actions, \WP_Post $post ): array {
    if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
        return $actions;
    }
    $url = get_edit_post_link( $post->ID, 'raw' );
    if ( $url ) {
        $actions['novacell-visual'] = '<a href="' . esc_url( add_query_arg( array( 'novacell_visual' => '1' ), $url ) ) . '">Włącz Visual Editor</a>';
    }
    return $actions;
}
