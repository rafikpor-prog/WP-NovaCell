<?php
/** Automatyczna inicjalizacja wtyczki. @package NovaCellManager */
namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Installer {
    private const SCHEMA_VERSION = '5';

    /** Uprawnienia wyłącznie dla administratora na etapie budowy procesu zatwierdzania. */
    public static function capabilities(): array {
        return array(
            'edit_nc_records', 'edit_others_nc_records', 'edit_published_nc_records',
            'edit_private_nc_records', 'publish_nc_records', 'read_private_nc_records',
            'delete_nc_records', 'delete_others_nc_records', 'delete_published_nc_records',
            'delete_private_nc_records',
        );
    }

    private static function preflight(): void {
        if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
            wp_die( esc_html__( 'NOVACELL Manager wymaga PHP 8.0 lub nowszego.', 'novacell-manager' ) );
        }
        if ( version_compare( get_bloginfo( 'version' ), '6.5', '<' ) ) {
            wp_die( esc_html__( 'NOVACELL Manager wymaga WordPress 6.5 lub nowszego.', 'novacell-manager' ) );
        }
    }

    private static function install(): void {
        $admin = get_role( 'administrator' );
        if ( null === $admin ) {
            wp_die( esc_html__( 'Nie odnaleziono roli administratora WordPress.', 'novacell-manager' ) );
        }
        foreach ( self::capabilities() as $capability ) {
            $admin->add_cap( $capability );
        }
        if ( false === get_option( 'nc_manager_installed_at', false ) ) {
            add_option( 'nc_manager_installed_at', gmdate( 'c' ), '', false );
        }
        update_option( 'nc_manager_schema_version', self::SCHEMA_VERSION, false );
        update_option( 'nc_manager_version', NC_MANAGER_VERSION, false );
        // Etap 5: na żądanie właściciela publicznie pokazujemy importowane rekordy,
        // ale NIE przypisujemy im fikcyjnej akceptacji medycznej.
        if ( ! get_option( 'nc_stage5_public_visibility_migrated', false ) ) {
            update_option( 'nc_public_preview', '1', false );
            update_option( 'nc_stage5_public_visibility_migrated', '1', false );
        }
        if ( false === get_option( 'nc_route_mode', false ) ) {
            update_option( 'nc_route_mode', 'safe', false );
        }
        // Nie nadpisujemy ręcznie zmodyfikowanego menu użytkownika.
        Main_Menu::install();
        if ( false === get_option( 'nc_starter_initialized', false ) ) {
            $result = Starter_Import::seed();
            if ( empty( $result['error'] ) ) {
                update_option( 'nc_starter_initialized', 1, false );
            }
        }
    }

    public static function activate(): void {
        self::preflight();
        // Rejestrujemy rodzaje treści jeszcze przed pierwszym żądaniem wp-admin.
        Content_Types::register();
        self::install();
        // Pierwsze pełne dane importujemy przy następnym wejściu w panel admina,
        // a nie w trakcie aktywacji ZIP (unikamy timeoutu na hostingu współdzielonym).
        flush_rewrite_rules();
    }

    public static function maybe_upgrade(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( get_option( 'nc_manager_version', '' ) !== NC_MANAGER_VERSION ||
             get_option( 'nc_manager_schema_version', '' ) !== self::SCHEMA_VERSION ) {
            self::preflight();
            self::install();
            Full_Import::maybe_upgrade();
        }
    }

    public static function deactivate(): void {
        // Zachowujemy wpisane dane, role i ustawienia; wyłączamy jedynie reguły URL.
        flush_rewrite_rules();
    }
}
