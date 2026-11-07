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
        echo '<p>System zarządzania treściami NOVACELL. Obecna wersja nie publikuje danych na stronie internetowej.</p>';
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
        echo '<p><strong>Uwaga:</strong> ceny, statusy metod i treści medyczne wymagają zatwierdzenia przed publikacją. ';
        echo 'Wersja 0.1.1 służy do przygotowania bazy informacji i testów na środowisku staging.</p></div>';
    }
}
