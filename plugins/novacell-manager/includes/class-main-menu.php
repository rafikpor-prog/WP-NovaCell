<?php
/** Dwujęzyczne menu główne WordPress, bez naruszania ręcznych zmian. */
namespace NovaCell\Manager;
defined( 'ABSPATH' ) || exit;
final class Main_Menu {
    public static function config(): array {
        return array(
            'pl' => array(
                array( 'Start', '/', array() ),
                array( 'Z czym możemy Ci pomóc?', '/z-czym-pomagamy/', array(
                    array('Profilaktyka i diagnostyka', '/chce-zadbac-o-zdrowie/'),
                    array('Perimenopauza i menopauza','/z-czym-pomagamy/menopauza/'),
                    array('Ginekologia','/z-czym-pomagamy/ginekologia/'),
                    array('Ból miednicy i endometrioza','/z-czym-pomagamy/bol-miednicy-endometrioza/'),
                    array('Hormony i libido','/z-czym-pomagamy/libido-hormony/'),
                    array('Zmęczenie','/z-czym-pomagamy/zmeczenie/'),
                    array('Ból stawów','/z-czym-pomagamy/bol-stawow/'),
                    array('Wygląd i estetyka','/wyglad-i-estetyka/'),
                    array('Nie wiem, od czego zacząć','/nie-wiem-od-czego-zaczac/'),
                ) ),
                array( 'Jak pracujemy', '/jak-pracujemy/', array() ),
                array( 'Lekarze', '/lekarze/', array() ),
                array( 'Metody leczenia A–Z', '/metody/', array() ),
                array( 'Cennik', '/cennik/', array() ),
                array( 'Wiedza', '/wiedza/', array() ),
                array( 'Pacjenci z zagranicy', '/en/international-patients/', array() ),
                array( 'Kontakt', '/kontakt/', array() ),
            ),
            'en' => array(
                array('Home','/en/',array()),
                array('How we can help','/en/help-with/',array(
                    array('Preventive health assessment','/en/preventive-health/assessment-and-plan/'),
                    array('Perimenopause and menopause','/en/help-with/menopause/'),
                    array('Persistent fatigue','/en/help-with/persistent-fatigue/'),
                )),
                array('How we work','/en/how-we-work/',array()),
                array('Physicians','/en/doctors/',array()),
                array('Treatment methods','/en/methods/',array()),
                array('Prices','/en/prices/',array()),
                array('Knowledge','/en/knowledge/',array()),
                array('International patients','/en/international-patients/',array()),
                array('Contact','/en/contact/',array()),
            ),
        );
    }

    public static function install(): void {
        foreach ( self::config() as $locale => $tree ) {
            $name = 'NOVACELL | ' . ( 'pl' === $locale ? 'Menu główne PL' : 'Main menu EN' );
            $menu = wp_get_nav_menu_object( $name );
            if ( ! $menu ) {
                $id = wp_create_nav_menu( $name );
                if ( is_wp_error( $id ) ) { continue; }
            } else { $id = (int) $menu->term_id; }
            $existing = wp_get_nav_menu_items( $id, array( 'post_status' => 'any' ) );
            // Nie nadpisujemy menu, jeśli użytkownik już je zmodyfikował.
            if ( empty( $existing ) ) {
                foreach ( $tree as $entry ) {
                    $parent = self::add( $id, $entry[0], $entry[1], 0 );
                    if ( ! $parent ) { continue; }
                    foreach ( $entry[2] as $child ) { self::add( $id, $child[0], $child[1], $parent ); }
                }
            }
            $location = 'primary_' . $locale;
            if ( current_theme_supports( 'menus' ) && array_key_exists( $location, get_registered_nav_menus() ) ) {
                $locations = (array) get_theme_mod( 'nav_menu_locations', array() );
                if ( empty( $locations[ $location ] ) ) {
                    $locations[ $location ] = $id;
                    set_theme_mod( 'nav_menu_locations', $locations );
                }
            }
        }
    }

    private static function add( int $menu_id, string $title, string $path, int $parent ): int {
        $id = wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title' => wp_slash( $title ),
            'menu-item-url' => home_url( $path ),
            'menu-item-type' => 'custom',
            'menu-item-status' => 'publish',
            'menu-item-parent-id' => $parent,
        ) );
        return is_wp_error( $id ) ? 0 : absint( $id );
    }

    public static function action(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden' ); }
        check_admin_referer( 'nc_install_main_menu' );
        self::install();
        wp_safe_redirect( add_query_arg( array( 'page' => 'novacell-main-menu', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
        exit;
    }
    public static function admin(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        echo '<div class="wrap"><h1>Menu główne NOVACELL, PL i EN</h1><p>Utworzone menu jest edytowalne w natywnym ekranie <strong>Wygląd → Menu</strong>. Ponowna instalacja nie zastępuje ręcznie zmodyfikowanych pozycji.</p>';
        echo '<p><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '" class="button">Otwórz edytor menu WordPress</a></p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'nc_install_main_menu' );
        echo '<input type="hidden" name="action" value="nc_install_main_menu"><button class="button button-primary">Utwórz brakujące menu PL i EN</button></form></div>';
    }
}
