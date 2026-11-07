<?php
/** Diagnostyka tras, podglądów i zgodności danych. @package NovaCellManager */
namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Diagnostics {
    public static function boot(): void {
        add_action( 'admin_menu', array( self::class, 'menu' ) );
        add_action( 'admin_post_nc_repair_routes', array( self::class, 'repair' ) );
        add_action( 'admin_post_nc_route_mode', array( self::class, 'save_route_mode' ) );
    }
    public static function menu(): void {
        add_submenu_page( 'novacell-manager', 'Trasy i podgląd', 'Trasy i podgląd', 'manage_options', 'novacell-diagnostics', array( self::class, 'render' ) );
    }
    public static function repair(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Brak uprawnień.', 'novacell-manager' ) ); }
        check_admin_referer( 'nc_repair_routes' );
        if ( function_exists( 'nc_theme_register_rewrites' ) ) { nc_theme_register_rewrites(); }
        flush_rewrite_rules( false );
        Main_Menu::install();
        wp_safe_redirect( add_query_arg( array( 'page' => 'novacell-diagnostics', 'repaired' => 1 ), admin_url( 'admin.php' ) ) );
        exit;
    }
    public static function save_route_mode(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden', '', array( 'response' => 403 ) ); }
        check_admin_referer( 'nc_route_mode' );
        $mode = isset( $_POST['nc_route_mode'] ) && is_string( $_POST['nc_route_mode'] )
            ? sanitize_key( wp_unslash( $_POST['nc_route_mode'] ) ) : 'safe';
        update_option( 'nc_route_mode', 'pretty' === $mode ? 'pretty' : 'safe', false );
        if ( function_exists( 'nc_theme_register_rewrites' ) ) { nc_theme_register_rewrites(); }
        flush_rewrite_rules( false );
        wp_safe_redirect( add_query_arg( array( 'page' => 'novacell-diagnostics', 'mode_updated' => 1 ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $theme_active = function_exists( 'nc_theme_routes' );
        $routes = (array) get_option( 'nc_full_route_index', array() );
        $home_sections = (array) get_option( 'nc_home_sections', array() );
        $approved = (bool) get_option( 'nc_site_approved', false );
        echo '<div class="wrap nc-diagnostics"><h1>Trasy i podgląd NOVACELL</h1>';
        echo '<p>Ten ekran służy do weryfikacji stron bez konieczności ich publicznego udostępniania. Status „Opublikowano” w WordPress nie zatwierdza automatycznie treści medycznej.</p>';
        if ( isset( $_GET['repaired'] ) ) { echo '<div class="notice notice-success"><p>Reguły odnośników odświeżono; brakujące menu zostały utworzone. Dane stron nie zostały zmienione.</p></div>'; }
        echo '<div class="nc-status-grid">';
        self::stat( 'Motyw NOVACELL', $theme_active ? 'Aktywny' : 'Nieaktywny', $theme_active );
        self::stat( 'Zaimportowane trasy', (string) count( $routes ), (bool) $routes );
        self::stat( 'Sekcje strony głównej', (string) count( $home_sections ), (bool) $home_sections );
        self::stat( 'Status serwisu', $approved ? 'Zatwierdzony' : 'Podgląd redakcyjny', true );
        echo '</div>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'nc_repair_routes' );
        echo '<input type="hidden" name="action" value="nc_repair_routes"><p><button type="submit" class="button button-primary">Napraw adresy i sprawdź menu</button> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=novacell-full-import' ) ) . '">Sprawdź import treści</a></p></form>';
        if ( ! $theme_active ) { echo '<div class="notice notice-warning inline"><p>Włącz NOVACELL Theme w Wygląd → Motywy, aby otwierać podstrony systemowe.</p></div>'; }
        echo '<div class="notice notice-info inline"><p><strong>Ważne: wpisane bez /wp-admin/ slug-i są błędne.</strong> NOVACELL Manager to panel administracyjny, a nie adresy podstron: np. <code>/novacell-pages</code> zwróci 404, ponieważ właściwy URL to <code>/wp-admin/admin.php?page=novacell-pages</code>.</p>';
        echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=novacell-pages' ) ) . '">Podstrony PL/EN</a> | <a href="' . esc_url( admin_url( 'admin.php?page=novacell-home-builder' ) ) . '">Menedżer strony głównej</a> | <a href="' . esc_url( admin_url( 'admin.php?page=novacell-visual-editor' ) ) . '">Visual Editor</a></p></div>';
        echo '<p><strong>Adres diagnostyczny:</strong> jeżeli przyjazna ścieżka zwraca 404, skorzystaj z linku «Otwórz bez reguł URL». Jeśli serwer zwraca własny komunikat «Not Found» przed uruchomieniem WordPress, wtyczka nie może sama naprawić konfiguracji Apache/Nginx. W tym przypadku sprawdź wskazówki serwera w dokumentacji Etapu 6.</p>';
        echo '<h2>Tryb adresów URL</h2>';
        echo '<p>Tryb bezpieczny nie wymaga reguł rewrite i jest zalecany, jeżeli występują błędy 404. Adresy czytelne wymagają prawidłowej konfiguracji Apache/Nginx.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'nc_route_mode' );
        echo '<input type="hidden" name="action" value="nc_route_mode">';
        echo '<p><label for="nc_route_mode_select">Tryb URL: </label><select id="nc_route_mode_select" name="nc_route_mode">';
        echo '<option value="safe"' . selected( get_option( 'nc_route_mode', 'safe' ), 'safe', false ) . '>Bezpieczny (bez 404, ?nc_view=...)</option>';
        echo '<option value="pretty"' . selected( get_option( 'nc_route_mode', 'safe' ), 'pretty', false ) . '>Przyjazne adresy /z-czym-pomagamy/...</option>';
        echo '</select> <button class="button button-secondary" type="submit">Zapisz tryb adresów</button></p></form>';
        if ( isset( $_GET['mode_updated'] ) ) { echo '<div class="notice notice-success"><p>Zapisano tryb linków. Otwórz linki w tabeli poniżej.</p></div>'; }
        echo '<p>Aktualny publiczny link strony głównej: <a href="' . esc_url( add_query_arg( array( 'nc_view' => 'home', 'nc_lang' => 'pl' ), home_url( '/' ) ) ) . '" target="_blank" rel="noopener noreferrer">Otwórz bez przepisywania adresów</a>.</p>';
        echo '<h2>Adresy do sprawdzenia</h2><p>Link awaryjny korzysta z parametrów WordPress i zadziała nawet wtedy, gdy serwer nie obsługuje przyjaznych adresów URL. Otwórz go będąc zalogowanym jako administrator.</p>';
        echo '<table class="widefat striped"><thead><tr><th scope="col">Rekord</th><th scope="col">Status</th><th scope="col">Strona PL</th><th scope="col">Strona EN</th><th scope="col">Link diagnostyczny</th></tr></thead><tbody>';
        foreach ( $routes as $source => $data ) {
            $key = 'ncfull-' . sanitize_key( (string) $source );
            $post = ! empty( $data['post_id'] ) ? get_post( absint( $data['post_id'] ) ) : null;
            if ( ! $post ) { continue; }
            $pl = isset( $data['pl'] ) ? (string) $data['pl'] : '/';
            $en = isset( $data['en'] ) ? (string) $data['en'] : '/en/';
            $debug = add_query_arg( array( 'nc_view' => $key, 'nc_lang' => 'pl' ), home_url( '/' ) );
            echo '<tr><td>' . esc_html( $post->post_title ) . '</td><td>' . esc_html( $post->post_status ) . '</td>';
            echo '<td><a target="_blank" rel="noopener" href="' . esc_url( $theme_active ? nc_theme_link( $key, 'pl' ) : $debug ) . '">PL (aktywny tryb)</a></td>';
            echo '<td><a target="_blank" rel="noopener" href="' . esc_url( $theme_active ? nc_theme_link( $key, 'en' ) : add_query_arg( array('nc_view'=>$key,'nc_lang'=>'en'), home_url('/') ) ) . '">EN (aktywny tryb)</a></td>';
            echo '<td><a target="_blank" rel="noopener" href="' . esc_url( $debug ) . '">Otwórz bez reguł URL</a></td></tr>';
        }
        echo '</tbody></table>';
        echo '<p><strong>Uwaga:</strong> jeżeli w innym oknie nie jesteś zalogowany, zobaczysz ekran informujący o niedostępności niezatwierdzonych tekstów.</p></div>';
    }
    private static function stat( string $title, string $value, bool $ok ): void {
        echo '<div class="nc-status"><span class="nc-status__label">' . esc_html( $title ) . '</span><strong>' . esc_html( $value ) . '</strong><span class="screen-reader-text">' . esc_html( $ok ? 'Poprawny' : 'Wymaga działania' ) . '</span></div>';
    }
}
