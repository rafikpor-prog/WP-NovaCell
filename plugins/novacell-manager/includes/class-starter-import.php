<?php
/**
 * Import dokumentacji roboczej: bez nadpisywania i bez publicznej publikacji.
 * Materiały źródłowe są w osobnym pliku w instalacyjnym ZIP, nie w publicznym repozytorium.
 * @package NovaCellManager
 */
namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Starter_Import {
    public static function data(): array {
        $file = NC_MANAGER_PATH . 'data/starter-content.private.json';
        if ( ! is_readable( $file ) ) {
            return array();
        }
        $raw = file_get_contents( $file );
        $data = is_string( $raw ) ? json_decode( $raw, true ) : null;
        return is_array( $data ) && 2 === (int) ( $data['schema'] ?? 0 ) ? $data : array();
    }

    /** Inicjalizuje raz i nigdy nie nadpisuje danych zmienionych ręcznie. */
    public static function seed(): array {
        $data = self::data();
        if ( empty( $data ) ) {
            return array( 'created' => 0, 'skipped' => 0, 'error' => 'Brak pakietu materiałów startowych.' );
        }
        if ( false === get_option( 'nc_home_sections', false ) ) {
            add_option( 'nc_home_sections', $data['sections'], '', false );
        }
        if ( false === get_option( 'nc_route_pages', false ) ) {
            add_option( 'nc_route_pages', $data['pages'], '', false );
        }
        $created = 0;
        $skipped = 0;
        $ids = array();
        foreach ( $data['records'] as $item ) {
            $type = sanitize_key( (string) ( $item['kind'] ?? '' ) );
            $slug = sanitize_title( (string) ( $item['slug'] ?? '' ) );
            if ( ! isset( Content_Types::types()[ $type ] ) || '' === $slug ) {
                continue;
            }
            $seed_key = 'novacell-2026:' . $type . ':' . $slug;
            $existing = get_posts( array(
                'post_type' => $type,
                'post_status' => 'any',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'meta_key' => '_nc_seed_key',
                'meta_value' => $seed_key,
            ) );
            if ( ! empty( $existing ) ) {
                $id = (int) $existing[0];
                $skipped++;
            } else {
                $id = wp_insert_post( array(
                    'post_type' => $type,
                    'post_status' => 'draft',
                    'post_title' => sanitize_text_field( (string) ( $item['pl_title'] ?? '' ) ),
                    'post_name' => $slug,
                    'post_excerpt' => sanitize_textarea_field( (string) ( $item['pl_lead'] ?? '' ) ),
                    'post_content' => wp_kses_post( (string) ( $item['pl_body'] ?? $item['pl_lead'] ?? '' ) ),
                ), true );
                if ( is_wp_error( $id ) || ! $id ) {
                    continue;
                }
                update_post_meta( $id, '_nc_seed_key', $seed_key );
                update_post_meta( $id, '_nc_editorial_status', 'unapproved' );
                update_post_meta( $id, '_nc_seed_origin', (string) $data['source'] );
                update_post_meta( $id, '_nc_route_key', sanitize_key( (string) ( $item['route'] ?? '' ) ) );
                update_post_meta( $id, 'nc_en_title', sanitize_text_field( (string) ( $item['en_title'] ?? '' ) ) );
                update_post_meta( $id, 'nc_en_excerpt', sanitize_textarea_field( (string) ( $item['en_lead'] ?? '' ) ) );
                update_post_meta( $id, 'nc_en_body', wp_kses_post( (string) ( $item['en_body'] ?? $item['en_lead'] ?? '' ) ) );
                foreach ( array( 'pl' => '', 'en' => 'nc_en_' ) as $locale => $prefix ) {
                    foreach ( (array) ( $item[ $locale . '_sections' ] ?? array() ) as $field => $value ) {
                        $key = '' === $prefix ? 'nc_' . $field : $prefix . $field;
                        update_post_meta( $id, $key, wp_kses_post( nl2br( esc_html( (string) $value ) ) ) );
                    }
                }
                $created++;
            }
            $ids[ $type . ':' . $slug ] = $id;
        }
        // Powiązania dla importowanych ścieżek; nie zastępuj istniejących relacji edytora.
        foreach ( $data['records'] as $item ) {
            if ( 'nc_pathway' !== ( $item['kind'] ?? '' ) ) {
                continue;
            }
            $id = (int) ( $ids['nc_pathway:' . ( $item['slug'] ?? '' ) ] ?? 0 );
            if ( $id && ! metadata_exists( 'post', $id, 'nc_physician_ids' ) ) {
                $links = array();
                foreach ( (array) ( $item['physicians'] ?? array() ) as $slug ) {
                    if ( isset( $ids['nc_physician:' . $slug ] ) ) {
                        $links[] = $ids['nc_physician:' . $slug ];
                    }
                }
                update_post_meta( $id, 'nc_physician_ids', array_map( 'absint', $links ) );
            }
        }
        update_option( 'nc_last_import', gmdate( 'c' ), false );
        return array( 'created' => $created, 'skipped' => $skipped, 'error' => '' );
    }

    public static function import_action(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Brak uprawnień.', 'novacell-manager' ) );
        }
        check_admin_referer( 'nc_import_starter' );
        $result = self::seed();
        $url = add_query_arg( array(
            'page' => 'novacell-starter',
            'created' => (int) $result['created'],
            'skipped' => (int) $result['skipped'],
            'seed_error' => empty( $result['error'] ) ? 0 : 1,
        ), admin_url( 'admin.php' ) );
        wp_safe_redirect( $url );
        exit;
    }
}
