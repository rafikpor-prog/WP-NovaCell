<?php
/**
 * Plugin Name: NOVACELL Visual Editor
 * Description: Wizualna nakładka na edytor blokowy WordPress. Kafelki, własne ikony, inspektor stylów i dynamiczna sekcja NOVACELL.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Text Domain: novacell-visual-editor
 * License: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;

define( 'NC_VISUAL_VERSION', '0.1.0' );
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

    $deps = array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-blocks', 'wp-block-editor', 'wp-editor', 'wp-i18n' );
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
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
        'before'
    );
} );

add_action( 'admin_menu', static function (): void {
    add_options_page( 'NOVACELL Visual Editor', 'NOVACELL Visual Editor', 'edit_posts', 'novacell-visual-editor', static function (): void {
        echo '<div class="wrap"><h1>NOVACELL Visual Editor 0.1.0</h1>';
        echo '<p>Otwórz dowolną Stronę lub Wpis w edytorze blokowym. W prawym górnym rogu wybierz panel NOVACELL Visual Editor z menu wtyczek edytora.</p>';
        echo '<p>Nakładka działa wyłącznie w edytorze Gutenberg. Kafelki są elementami natywnymi WordPress. Zapis pozostaje w formacie bloków.</p>';
        echo '<p>Stronę główną zarządzaną przez NovaCell Manager edytujesz osobno: <a href="' . esc_url( admin_url( 'admin.php?page=novacell-home-builder' ) ) . '">NOVACELL Manager → Strona główna i sekcje</a>.</p></div>';
    } );
} );
