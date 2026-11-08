<?php
/** Bootstrapping NovaCell Manager. @package NovaCellManager */
namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Plugin {
    public static function boot(): void {
        add_action( 'init', array( Content_Types::class, 'register' ) );
        Readiness::boot();
        add_action( 'init', array( Metadata::class, 'register' ), 11 );
        // Przeniesiono języki do wspólnego edytora każdej treści WYSIWYG.
        Language_Editor::boot();
        Site_Builder::boot();
        Preview_Settings::boot();
        Diagnostics::boot();
        Preview_Links::boot();
        Navigation_Hub::boot();
        URL_Repair::boot();
        English_Editorial::boot();
        add_action( 'admin_init', array( Installer::class, 'maybe_upgrade' ) );
        add_action( 'admin_init', array( Full_Import::class, 'maybe_upgrade' ), 20 );
        add_action( 'admin_init', array( Cleanup_Update::class, 'maybe_upgrade' ), 30 );
        add_action( 'admin_post_nc_clean_content', array( Cleanup_Update::class, 'action' ) );
        add_action( 'admin_post_nc_full_import', array( Full_Import::class, 'action' ) );
        add_action( 'admin_post_nc_install_main_menu', array( Main_Menu::class, 'action' ) );
        add_action( 'after_switch_theme', array( Main_Menu::class, 'install' ), 20 );
        // Najpierw rejestrujemy menu nadrzędne. Wcześniejsze wersje dodawały
        // podmenu przed rodzicem, przez co WordPress przypisywał im błędny
        // hook (admin_page_* zamiast novacell-manager_page_*) i zwracał 403.
        add_action( 'admin_menu', array( Admin::class, 'register_menu' ), 1 );
        add_action( 'admin_menu', static function (): void {
            add_submenu_page( 'novacell-manager', 'Czyste treści', 'Czyste treści', 'manage_options', 'novacell-clean-content', array( Cleanup_Update::class, 'admin' ) );
            add_submenu_page( 'novacell-manager', 'Pełne treści', 'Pełne treści', 'manage_options', 'novacell-full-import', array( Full_Import::class, 'admin' ) );
            add_submenu_page( 'novacell-manager', 'Menu główne', 'Menu główne PL i EN', 'manage_options', 'novacell-main-menu', array( Main_Menu::class, 'admin' ) );
        } );
        add_action( 'add_meta_boxes', array( Editors::class, 'register_boxes' ) );
        add_action( 'save_post', array( Editors::class, 'save' ), 10, 2 );
        add_filter( 'use_block_editor_for_post_type', array( Editors::class, 'block_editor_enabled' ), 10, 2 );
    }
}
