<?php
/**
 * Etap 6: raport gotowości, kontrola przekierowań i test połączenia zwrotnego.
 * Nie ustawia ani nie symuluje zatwierdzenia klinicznego.
 *
 * @package NovaCellManager
 */
namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Readiness {
    public static function boot(): void {
        add_action( 'admin_menu', array( self::class, 'menu' ) );
        add_action( 'admin_post_nc_stage6_probe', array( self::class, 'probe_action' ) );
    }

    public static function menu(): void {
        add_submenu_page(
            'novacell-manager', 'Testy uruchomieniowe', 'Testy uruchomieniowe',
            'manage_options', 'novacell-readiness', array( self::class, 'render' )
        );
    }

    public static function summary(): array {
        $routes = (array) get_option( 'nc_full_route_index', array() );
        $published = 0;
        $en = 0;
        $reviewed = 0;
        $missing = array();
        foreach ( $routes as $source => $row ) {
            $id = absint( $row['post_id'] ?? 0 );
            if ( ! $id || 'publish' !== get_post_status( $id ) ) {
                $missing[] = (string) $source;
                continue;
            }
            ++$published;
            if ( (string) get_post_meta( $id, '_nc_editorial_status', true ) === 'approved' ) {
                ++$reviewed;
            }
            if ( 'topic-22' === $source || '' !== trim( wp_strip_all_tags( (string) get_post_meta( $id, 'nc_en_body', true ) ) ) ) {
                ++$en;
            }
        }
        return array(
            'routes'        => count( $routes ),
            'published'     => $published,
            'reviewed'      => $reviewed,
            'english'       => $en,
            'missing'       => $missing,
            'clinical'      => (bool) get_option( 'nc_site_approved', false ),
            'public'        => (bool) get_option( 'nc_public_preview', false ),
            'price_confirmed' => (bool) get_option( 'nc_price_source_approved', false ),
            'theme'         => function_exists( 'nc_theme_routes' ),
            'pretty'        => 'pretty' === get_option( 'nc_route_mode', 'safe' ),
        );
    }

    public static function probe_action(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Brak uprawnień.', 'novacell-manager' ) );
        }
        check_admin_referer( 'nc_stage6_probe' );
        $test_routes = array(
            'PL: strona główna' => home_url( '/' ),
            'PL: adres bez reguł' => add_query_arg( array( 'nc_view' => 'ncfull-topic-1', 'nc_lang' => 'pl' ), home_url( '/' ) ),
            'PL: adres przyjazny' => home_url( '/z-czym-pomagamy/menopauza/' ),
            'EN: adres bez reguł' => add_query_arg( array( 'nc_view' => 'ncfull-topic-22', 'nc_lang' => 'en' ), home_url( '/' ) ),
        );
        $results = array();
        foreach ( $test_routes as $name => $url ) {
            // The URL is constructed exclusively from the configured WordPress home_url.
            $response = wp_safe_remote_get( $url, array(
                'timeout' => 5, 'redirection' => 0, 'sslverify' => true,
                'headers' => array( 'Cache-Control' => 'no-cache', 'Accept' => 'text/html' ),
                'user-agent' => 'NOVACELL-Readiness/0.6 (+WordPress local loopback)',
            ) );
            if ( is_wp_error( $response ) ) {
                $results[] = array( 'name' => $name, 'status' => 'BŁĄD', 'detail' => sanitize_text_field( $response->get_error_message() ) );
            } else {
                $code = (int) wp_remote_retrieve_response_code( $response );
                $results[] = array( 'name' => $name, 'status' => (string) $code,
                    'detail' => 200 === $code ? 'HTTP 200' : ( 301 === $code || 302 === $code ? 'Przekierowanie (sprawdź docelowy adres)' : 'Sprawdź routing, WordPress lub serwer' ) );
            }
        }
        set_transient( 'nc_stage6_probe_' . get_current_user_id(), $results, 15 * MINUTE_IN_SECONDS );
        wp_safe_redirect( add_query_arg( array( 'page' => 'novacell-readiness', 'probe' => 1 ), admin_url( 'admin.php' ) ) );
        exit;
    }

    private static function stat( string $label, string $value ): void {
        echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
    }

    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $s = self::summary();
        echo '<div class="wrap"><h1>NOVACELL · Gotowość do uruchomienia</h1>';
        echo '<p>Raport techniczny. Publiczny dostęp do podglądu nie jest potwierdzeniem medycznym ani prawnym. Zielony test adresów wymaga faktycznego HTTP 200 z serwera.</p>';
        echo '<table class="widefat striped" style="max-width:980px"><caption class="screen-reader-text">Stan danych kliniki</caption><tbody>';
        self::stat( 'Aktywny Theme', $s['theme'] ? 'Tak' : 'Nie' );
        self::stat( 'Wpisy z przypisanymi trasami', (string) $s['routes'] );
        self::stat( 'Opublikowane rekordy z trasami', (string) $s['published'] );
        self::stat( 'Zweryfikowane rekordy medyczne', (string) $s['reviewed'] );
        self::stat( 'Rekordy z tekstem EN', (string) $s['english'] );
        self::stat( 'Publiczny podgląd', $s['public'] ? 'Włączony' : 'Wyłączony' );
        self::stat( 'Formalne zatwierdzenie serwisu', $s['clinical'] ? 'Tak' : 'Nie' );
        self::stat( 'Arkusz cennika zatwierdzony', $s['price_confirmed'] ? 'Tak' : 'Nie, ceny robocze' );
        self::stat( 'Tryb przyjaznych URL', $s['pretty'] ? 'Włączony' : 'Wyłączony; linki parametrowe' );
        echo '</tbody></table>';
        if ( $s['missing'] ) {
            echo '<p role="status">Brakujące lub nieopublikowane trasy: ' . esc_html( implode( ', ', array_slice( $s['missing'], 0, 15 ) ) ) . '</p>';
        }
        echo '<h2>Testy HTTP na serwerze</h2><p>Żądania wysyłane są wyłącznie do własnej witryny WordPress. Niektóre hostingi blokują połączenia zwrotne; taki błąd nie oznacza automatycznie błędu publicznej strony.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'nc_stage6_probe' );
        echo '<input type="hidden" name="action" value="nc_stage6_probe">';
        echo '<p><button type="submit" class="button button-primary">Sprawdź 4 rzeczywiste adresy HTTP</button></p></form>';
        $probes = get_transient( 'nc_stage6_probe_' . get_current_user_id() );
        if ( is_array( $probes ) ) {
            echo '<table class="widefat striped" style="max-width:980px"><thead><tr><th scope="col">Adres / scenariusz</th><th scope="col">HTTP</th><th scope="col">Wynik</th></tr></thead><tbody>';
            foreach ( $probes as $item ) {
                echo '<tr><th scope="row">' . esc_html( $item['name'] ) . '</th><td>' . esc_html( $item['status'] ) . '</td><td>' . esc_html( $item['detail'] ) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '<h2>Warunki uruchomienia produkcyjnego</h2><p>Przed rozpoczęciem indeksowania należy wykonać rzeczywisty audyt WCAG 2.2 AA, zaakceptować pełny cennik, opisy medyczne i dokumenty prawne, sprawdzić przekierowania, a także wykonać backup z próbą odtworzenia.</p>';
        echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=novacell-diagnostics' ) ) . '">Trasy i podgląd</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=novacell-home-builder' ) ) . '">Menedżer strony głównej</a></p></div>';
    }
}
