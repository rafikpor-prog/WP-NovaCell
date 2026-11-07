<?php
/**
 * Rejestracja modułów i hooków.
 *
 * @package NovaCellManager
 */

namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Plugin {
    public static function boot(): void {
        add_action( 'init', array( Content_Types::class, 'register' ) );
        add_action( 'init', array( Metadata::class, 'register' ), 11 );
        add_action( 'admin_menu', array( Admin::class, 'register_menu' ) );
        add_action( 'add_meta_boxes', array( Editors::class, 'register_boxes' ) );
        add_action( 'save_post', array( Editors::class, 'save' ), 10, 2 );

        // Administracyjne opisy kliniki korzystają z natywnego edytora WYSIWYG WordPress.
        // Zwykłe strony i wpisy pozostają dostępne w Gutenbergu.
        add_filter( 'use_block_editor_for_post_type', array( Editors::class, 'block_editor_enabled' ), 10, 2 );
    }
}
