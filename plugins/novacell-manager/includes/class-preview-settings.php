<?php
/** Ustawienia widoczności niezatwierdzonej wersji demonstracyjnej. */
namespace NovaCell\Manager;
defined( 'ABSPATH' ) || exit;
final class Preview_Settings {
    public static function boot(): void {
        add_action( 'admin_menu', static function (): void {
            add_submenu_page( 'novacell-manager', 'Widoczność wersji testowej', 'Widoczność testowa', 'manage_options', 'novacell-preview-settings', array( self::class, 'render' ) );
        } );
        add_action( 'admin_post_nc_preview_settings', array( self::class, 'save' ) );
    }
    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $value = (bool) get_option( 'nc_public_preview', false );
        echo '<div class="wrap"><h1>Widoczność testowej strony NOVACELL</h1>';
        echo '<p>Opublikowane rekordy są widoczne publicznie, jednak nie zostały automatycznie zatwierdzone klinicznie. Do czasu weryfikacji strony pozostają noindex, a na froncie widoczna jest informacja redakcyjna.</p>';
        echo '<p>Włączenie umożliwia odczyt niezatwierdzonych treści bez logowania. Zalecane wyłącznie na testowym adresie chronionym hasłem. Wersja produkcyjna wymaga rzeczywistej recenzji medycznej i prawnej.</p>';
        if ( isset( $_GET['updated'] ) ) { echo '<div class="notice notice-success"><p>Ustawienia zostały zapisane.</p></div>'; }
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'nc_preview_settings' );
        echo '<input type="hidden" name="action" value="nc_preview_settings">';
        echo '<p><label><input type="checkbox" name="nc_public_preview" value="1" ' . checked( $value, true, false ) . '> Udostępnij publiczny podgląd bez logowania (treści nadal niezatwierdzone i noindex)</label></p>';
        echo '<p><button class="button button-primary" type="submit">Zapisz</button></p></form></div>';
    }
    public static function save(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden' ); }
        check_admin_referer( 'nc_preview_settings' );
        update_option( 'nc_public_preview', isset( $_POST['nc_public_preview'] ) ? '1' : '0', false );
        wp_safe_redirect( add_query_arg( array('page'=>'novacell-preview-settings','updated'=>1), admin_url('admin.php') ) );
        exit;
    }
}
