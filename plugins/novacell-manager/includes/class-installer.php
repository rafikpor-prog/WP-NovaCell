<?php
/** Automatyczna inicjalizacja wtyczki. @package NovaCellManager */
namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Installer {
    private const SCHEMA_VERSION = '1';

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
    }

    public static function activate(): void {
        self::preflight();
        Content_Types::register();
        self::install();
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
        }
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
