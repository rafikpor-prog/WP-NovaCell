<?php
/**
 * Stałe odnośniki podglądu rekordów NOVACELL, niezależne od .htaccess.
 * @package NovaCellManager
 */
namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Preview_Links {
    public static function boot(): void {
        add_filter( 'post_row_actions', array( self::class, 'row_actions' ), 20, 2 );
        add_filter( 'page_row_actions', array( self::class, 'row_actions' ), 20, 2 );
        add_action( 'post_submitbox_misc_actions', array( self::class, 'editor_links' ) );
        add_action( 'admin_bar_menu', array( self::class, 'admin_bar' ), 95 );
    }

    private static function route_for( \WP_Post $post ): string {
        if ( ! isset( Content_Types::types()[ $post->post_type ] ) ) { return ''; }
        $full = sanitize_key( (string) get_post_meta( $post->ID, '_nc_full_source_key', true ) );
        if ( $full ) { return 'ncfull-' . $full; }
        $seed = sanitize_key( (string) get_post_meta( $post->ID, '_nc_route_key', true ) );
        return $seed;
    }

    public static function url( \WP_Post $post, string $lang = 'pl' ): string {
        $route = self::route_for( $post );
        if ( ! $route ) { return ''; }
        return add_query_arg(
            array( 'nc_view' => $route, 'nc_lang' => 'en' === $lang ? 'en' : 'pl' ),
            home_url( '/' )
        );
    }

    public static function row_actions( array $actions, \WP_Post $post ): array {
        if ( ! current_user_can( 'manage_options' ) ) { return $actions; }
        foreach ( array( 'pl' => 'Podgląd PL bez 404', 'en' => 'Podgląd EN bez 404' ) as $lang => $title ) {
            $url = self::url( $post, $lang );
            if ( $url ) {
                $actions[ 'nc_preview_' . $lang ] = '<a target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';
            }
        }
        return $actions;
    }

    public static function editor_links( \WP_Post $post ): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $pl = self::url( $post, 'pl' );
        $en = self::url( $post, 'en' );
        if ( ! $pl ) { return; }
        echo '<div class="misc-pub-section nc-record-preview">';
        echo '<strong>' . esc_html__( 'Podgląd NOVACELL (bez reguł adresów)', 'novacell-manager' ) . '</strong><br>';
        echo '<a target="_blank" rel="noopener noreferrer" href="' . esc_url( $pl ) . '">Otwórz po polsku</a>';
        echo ' · <a target="_blank" rel="noopener noreferrer" href="' . esc_url( $en ) . '">Open in English</a>';
        echo '</div>';
    }

    public static function admin_bar( \WP_Admin_Bar $bar ): void {
        if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) { return; }
        $bar->add_node(
            array(
                'id'    => 'nc-preview-diagnostics',
                'title' => 'NOVACELL: diagnostyka stron',
                'href'  => admin_url( 'admin.php?page=novacell-diagnostics' ),
            )
        );
        $bar->add_node(
            array( 'id' => 'nc-preview-pl', 'parent' => 'nc-preview-diagnostics', 'title' => 'Zobacz PL bez 404',
                'href' => add_query_arg( array( 'nc_view' => 'home', 'nc_lang' => 'pl' ), home_url( '/' ) ) )
        );
        $bar->add_node(
            array( 'id' => 'nc-preview-en', 'parent' => 'nc-preview-diagnostics', 'title' => 'Zobacz EN bez 404',
                'href' => add_query_arg( array( 'nc_view' => 'home', 'nc_lang' => 'en' ), home_url( '/' ) ) )
        );
    }
}
