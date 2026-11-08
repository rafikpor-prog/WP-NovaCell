<?php
/** Pulpit NOVACELL. @package NovaCellManager */
namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Admin {
    public static function register_menu(): void {
        add_menu_page( 'NOVACELL Manager', 'NOVACELL Manager', 'manage_options',
            'novacell-manager', array( self::class, 'dashboard' ), 'dashicons-heart', 25 );
        add_submenu_page( 'novacell-manager', 'Pulpit NOVACELL', 'Pulpit', 'manage_options',
            'novacell-manager', array( self::class, 'dashboard' ) );
    }

    public static function dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Brak uprawnień.', 'novacell-manager' ) );
        }
        echo '<div class="wrap">';
        echo '<h1>NOVACELL Manager <small style="font-weight:normal">' . esc_html( NC_MANAGER_VERSION ) . '</small></h1>';
        echo '<p>Centralny system obsługi NOVACELL. <strong>Wersja publikacyjna WordPress i zatwierdzenie kliniczne to odrębne statusy.</strong></p>';
        echo '<h2>Otwórz właściwy ekran</h2><p>Nie wpisuj skrótów typu <code>/novacell-pages</code> jako adresu strony. Wewnętrzne ekrany działają w <code>wp-admin/admin.php?page=...</code>.</p>';
        echo '<div class="nc-admin-shortcuts" style="display:flex;flex-wrap:wrap;gap:10px;margin:18px 0 24px">';
        $nav = array(
            'Strona główna: sekcje PL / EN' => 'novacell-home-builder',
            'Wszystkie podstrony PL / EN' => 'novacell-pages',
            'Import materiałów startowych' => 'novacell-starter',
            'Import pełnych treści' => 'novacell-full-import',
            'Widoczność publiczna' => 'novacell-preview-settings',
            'Trasy i podgląd (404)' => 'novacell-diagnostics',
            'Testy uruchomieniowe HTTP' => 'novacell-readiness',
            'Menu główne PL / EN' => 'novacell-main-menu',
            'NOVACELL Visual Editor' => 'novacell-visual-editor',
        );
        foreach ( $nav as $label => $slug ) {
            echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'admin.php?page=' . $slug ) ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</div>';
        // Verify that the actual WordPress admin page hooks exist, rather than
        // assuming that a displayed link guarantees the screen is accessible.
        if ( function_exists( 'get_plugin_page_hookname' ) && function_exists( 'has_action' ) ) {
            $screens = array(
                'novacell-pages', 'novacell-home-builder', 'novacell-starter',
                'novacell-preview-settings', 'novacell-diagnostics',
                'novacell-full-import', 'novacell-main-menu', 'novacell-readiness',
            );
            $unavailable = array();
            foreach ( $screens as $screen_slug ) {
                $page_hook = get_plugin_page_hookname( $screen_slug, 'novacell-manager' );
                if ( ! $page_hook || ! has_action( $page_hook ) ) {
                    $unavailable[] = $screen_slug;
                }
            }
            if ( ! $unavailable ) {
                echo '<div class="notice notice-success inline"><p><strong>Menu NOVACELL: 8/8 ekranów zarejestrowanych poprawnie.</strong> Możesz otwierać ekrany przyciskami powyżej.</p></div>';
            } else {
                echo '<div class="notice notice-warning inline"><p><strong>Uwaga: część ekranów NOVACELL nie została zarejestrowana:</strong> ' . esc_html( implode( ', ', $unavailable ) ) . '. Sprawdź aktywację wtyczki i konflikt z innymi rozszerzeniami.</p></div>';
            }
        }
        echo '<h2>Rejestry danych kliniki</h2>';
        
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;max-width:1200px;margin:20px 0">';
        foreach ( Content_Types::types() as $type => $label ) {
            $count = wp_count_posts( $type );
            $total = $count ? array_sum( (array) $count ) : 0;
            $url   = admin_url( 'edit.php?post_type=' . $type );
            echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px">';
            echo '<h2 style="margin-top:0">' . esc_html( $label ) . '</h2>';
            echo '<p><strong>' . esc_html( (string) $total ) . '</strong> rekordów (w tym wersje robocze)</p>';
            echo '<a class="button button-secondary" href="' . esc_url( $url ) . '">Zarządzaj</a></div>';
        }
        echo '</div>';
        echo '<p><strong>Podgląd:</strong> po włączeniu w ustawieniach widoczności materiały mogą być publicznie przeglądane, lecz nie jest to równoznaczne z ich zatwierdzeniem medycznym. Treści niezatwierdzone pozostają noindex.</p></div>';
    }
}
