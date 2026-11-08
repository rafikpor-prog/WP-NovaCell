<?php
/** NOVACELL 0.6.2: one-time, reversible cleanup of imported patient text. */
namespace NovaCell\Manager;
defined( 'ABSPATH' ) || exit;
final class Cleanup_Update {
    public const VERSION = 'patient-facing-clean-v1-2026-11-08';
    public static function maybe_upgrade(): void {
        if ( current_user_can( 'manage_options' ) && self::VERSION !== get_option( 'nc_cleanup_applied_version', '' ) ) { self::apply(); }
    }
    public static function apply( bool $repeat_import = false ): array {
        if ( ! current_user_can( 'manage_options' ) ) { return array( 'error' => 'Brak uprawnień.' ); }
        $seed = Starter_Import::data();
        if ( ! $seed ) { return array( 'error' => 'Brak danych startowych.' ); }
        $source = Full_Import::data();
        if ( $source && ( $repeat_import || '5' !== (string) get_option( 'nc_full_import_version', '' ) ) ) {
            $result = Full_Import::import();
            if ( ! empty( $result['error'] ) ) { return array( 'error' => $result['error'] ); }
        }
        $report = array( 'sections' => 0, 'pages' => 0, 'seed_posts' => 0 );
        foreach ( array( 'nc_home_sections' => 'sections', 'nc_route_pages' => 'pages' ) as $option => $collection ) {
            $current = get_option( $option, false );
            if ( ! is_array( $current ) ) { continue; }
            $originals = array();
            foreach ( (array) ( $seed[$collection] ?? array() ) as $item ) {
                if ( is_array( $item ) && ! empty( $item['id'] ) ) { $originals[$item['id']] = $item; }
            }
            $backup_key = $option . '_backup_20261108';
            if ( ! get_option( $backup_key, false ) ) { add_option( $backup_key, $current, '', false ); }
            foreach ( $current as &$entry ) {
                $id = (string) ( $entry['id'] ?? '' );
                if ( ! isset( $originals[$id] ) ) { continue; }
                foreach ( array( 'pl', 'en' ) as $lang ) {
                    foreach ( array( 'title', 'lead', 'body' ) as $field ) {
                        $entry[$lang][$field] = (string) ( $originals[$id][$lang][$field] ?? '' );
                    }
                    if ( 'sections' === $collection ) {
                        $entry[$lang]['cta_label'] = (string) ( $originals[$id][$lang]['cta_label'] ?? '' );
                    }
                }
                ++$report[$collection];
            }
            unset( $entry );
            update_option( $option, $current, false );
        }
        // Only records marked by the NOVACELL seed are changed. Imported full
        // descriptions were already replaced by Full_Import::import() version 5.
        foreach ( (array) ( $seed['records'] ?? array() ) as $item ) {
            $type = sanitize_key( (string) ( $item['kind'] ?? '' ) );
            if ( ! isset( Content_Types::types()[$type] ) ) { continue; }
            $slug = sanitize_title( (string) ( $item['slug'] ?? '' ) );
            $ids = get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids',
                'meta_key' => '_nc_seed_key', 'meta_value' => 'novacell-2026:' . $type . ':' . $slug ) );
            if ( ! $ids ) { continue; }
            $id = absint( $ids[0] );
            if ( metadata_exists( 'post', $id, '_nc_full_source_key' ) ) { continue; }
            if ( ! metadata_exists( 'post', $id, '_nc_cleanup_backup_20261108' ) ) {
                update_post_meta( $id, '_nc_cleanup_backup_20261108', array(
                    'title' => get_post_field( 'post_title', $id ), 'excerpt' => get_post_field( 'post_excerpt', $id ),
                    'body' => get_post_field( 'post_content', $id ), 'en_body' => get_post_meta( $id, 'nc_en_body', true ) ) );
            }
            $saved = wp_update_post( array( 'ID' => $id, 'post_status' => 'publish',
                'post_title' => sanitize_text_field( (string) ( $item['pl_title'] ?? '' ) ),
                'post_excerpt' => sanitize_textarea_field( (string) ( $item['pl_lead'] ?? '' ) ),
                'post_content' => wp_kses_post( (string) ( $item['pl_body'] ?? $item['pl_lead'] ?? '' ) ) ), true );
            if ( is_wp_error( $saved ) ) { continue; }
            foreach ( array( 'en_title' => 'nc_en_title', 'en_lead' => 'nc_en_excerpt', 'en_body' => 'nc_en_body' ) as $key => $meta ) {
                if ( ! empty( $item[$key] ) ) {
                    update_post_meta( $id, $meta, 'en_body' === $key ? wp_kses_post( $item[$key] ) : sanitize_text_field( $item[$key] ) );
                }
            }
            foreach ( array( 'pl_sections' => 'nc_', 'en_sections' => 'nc_en_' ) as $field => $prefix ) {
                foreach ( (array) ( $item[$field] ?? array() ) as $key => $value ) {
                    update_post_meta( $id, $prefix . sanitize_key( $key ), wp_kses_post( nl2br( esc_html( $value ) ) ) );
                }
            }
            update_post_meta( $id, '_nc_editorial_status', 'requires_review' );
            update_post_meta( $id, '_nc_clean_content_version', self::VERSION );
            ++$report['seed_posts'];
        }
        update_option( 'nc_cleanup_applied_version', self::VERSION, false );
        update_option( 'nc_cleanup_applied_at', gmdate( 'c' ), false );
        update_option( 'nc_cleanup_report', $report, false );
        return $report;
    }
    public static function action(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Brak uprawnień.' ); }
        check_admin_referer( 'nc_clean_content' );
        self::apply( true );
        wp_safe_redirect( admin_url( 'admin.php?page=novacell-clean-content' ) );
        exit;
    }
    public static function admin(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $report = (array) get_option( 'nc_cleanup_report', array() );
        echo '<div class="wrap"><h1>NOVACELL — czyste treści</h1><p>Wydanie 0.6.2 usuwa notatki techniczne z materiałów dla pacjentów i nadpisuje tylko rekordy importowane z dokumentu NOVACELL.</p>';
        echo '<p>Oczyszczono: sekcje strony głównej ' . esc_html( (string) ( $report['sections'] ?? 0 ) ) . ', strony konfiguracyjne ' . esc_html( (string) ( $report['pages'] ?? 0 ) ) . ', pozostałe rekordy startowe ' . esc_html( (string) ( $report['seed_posts'] ?? 0 ) ) . '.</p>';
        echo '<p>Zachowano kolejność sekcji, widoczność, linki, powiązania i ceny. Kopie treści sprzed aktualizacji zapisano w metadanych i opcjach WordPress. Opisy medyczne i prawne nadal wymagają zatwierdzenia merytorycznego.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'nc_clean_content' );
        echo '<input type="hidden" name="action" value="nc_clean_content"><button type="submit" class="button button-primary">Ponownie wgraj oczyszczone treści</button></form></div>';
    }
}
