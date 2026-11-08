<?php
/**
 * Idempotentny import pełnych tekstów C1–C6 z pliku prywatnego w instalatorze.
 * Zachowuje ręczne poprawki, publikuje wyłącznie rekordy źródłowe etapu 2/3.
 *
 * @package NovaCellManager
 */
namespace NovaCell\Manager;
defined( 'ABSPATH' ) || exit;

final class Full_Import {
    private const IMPORT_VERSION = '5';
    public const CLEANUP_VERSION = 'patient-facing-clean-v1-2026-11-08';

    public static function data(): array {
        $path = NC_MANAGER_PATH . 'data/full-content.php';
        if ( ! is_readable( $path ) ) { return array(); }
        $decoded = require $path;
        return is_array( $decoded ) && 3 === (int) ( $decoded['schema'] ?? 0 ) ? $decoded : array();
    }

    private static function previously_imported( string $type, string $key, array $item ): int {
        $found = get_posts( array(
            'post_type' => $type, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids',
            'meta_key' => '_nc_full_source_key', 'meta_value' => $key,
        ) );
        if ( $found ) { return absint( $found[0] ); }
        // Migracja z 0.2.0: nie duplikujemy wpisów istniejących wcześniej.
        $legacy = array(
            'topic-1' => array( 'nc_pathway', 'menopauza' ),
            'topic-8' => array( 'nc_pathway', 'diagnostyka-i-plan' ),
            'topic-7' => array( 'nc_pathway', 'zmeczenie' ),
            'method-dfpp-tpe-afereza' => array( 'nc_method', 'dfpp-tpe-afereza' ),
            'profile-jakub-rzepka' => array( 'nc_physician', 'jakub-rzepka' ),
            'profile-katarzyna-moson-rzepka' => array( 'nc_physician', 'katarzyna-moson-rzepka' ),
            'profile-przemyslaw-szalanski' => array( 'nc_physician', 'przemyslaw-szalanski' ),
        );
        if ( isset( $legacy[ $key ] ) && $type === $legacy[ $key ][0] ) {
            $find = get_posts( array(
                'post_type' => $type, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids',
                'meta_key' => '_nc_seed_key',
                'meta_value' => 'novacell-2026:' . $type . ':' . $legacy[ $key ][1],
            ) );
            if ( $find ) { return absint( $find[0] ); }
        }
        return 0;
    }

    private static function should_update_body( int $id, array $item ): bool {
        $existing_hash = (string) get_post_meta( $id, '_nc_full_import_hash', true );
        if ( $existing_hash ) {
            $current_hash = hash( 'sha256', (string) get_post_field( 'post_content', $id ) );
            return hash_equals( $existing_hash, $current_hash );
        }
        // Wpisy wersji 0.2.0 nie miały pełnej treści. Pełny opis dopisujemy
        // jedynie, jeśli dotychczas wpis zawierał krótki wstęp z importu lub jest pusty.
        $old = (string) get_post_field( 'post_content', $id );
        $stage2 = Starter_Import::data();
        foreach ( (array) ( $stage2['records'] ?? array() ) as $seed ) {
            if ( ! empty( $seed['pl_title'] ) && (string) $seed['pl_title'] === (string) get_post_field( 'post_title', $id ) ) {
                $reference = wp_kses_post( (string) ( $seed['pl_body'] ?? $seed['pl_lead'] ?? '' ) );
                return '' === trim( $old ) || $old === $reference;
            }
        }
        return '' === trim( $old );
    }

    private static function write_item( array $item, string $type, string $key ): array {
        $id = self::previously_imported( $type, $key, $item );
        $created = ! $id;
        $content_changed = $created;
        // Explicit owner-requested correction of source-imported descriptions.
        $replace_source_copy = ! $created && (string) get_post_meta( $id, '_nc_clean_content_version', true ) !== self::CLEANUP_VERSION;
        $content = wp_kses_post( (string) ( $item['pl_body'] ?? $item['description'] ?? '' ) );
        $title = sanitize_text_field( (string) ( $item['pl_title'] ?? '' ) );
        if ( ! $title ) { return array( 0, false, false ); }
        if ( $created ) {
            $id = wp_insert_post( array(
                'post_type' => $type,
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => sanitize_title( (string) ( $item['source_id'] ?? $title ) ),
                'post_excerpt' => sanitize_textarea_field( (string) ( $item['pl_lead'] ?? '' ) ),
                'post_content' => $content,
            ), true );
            if ( is_wp_error( $id ) || ! $id ) { return array( 0, false, false ); }
            update_post_meta( $id, '_nc_full_import_hash', hash( 'sha256', $content ) );
        } else {
            $update = array( 'ID' => $id );
            if ( 'publish' !== get_post_status( $id ) ) { $update['post_status'] = 'publish'; }
            if ( $replace_source_copy ) {
                // Reversible backup before overwriting even a manually modified source copy.
                if ( ! metadata_exists( 'post', $id, '_nc_cleanup_backup_20261108' ) ) {
                    update_post_meta( $id, '_nc_cleanup_backup_20261108', array(
                        'title' => get_post_field( 'post_title', $id ),
                        'excerpt' => get_post_field( 'post_excerpt', $id ),
                        'body' => get_post_field( 'post_content', $id ),
                        'en_title' => get_post_meta( $id, 'nc_en_title', true ),
                        'en_excerpt' => get_post_meta( $id, 'nc_en_excerpt', true ),
                        'en_body' => get_post_meta( $id, 'nc_en_body', true ),
                    ) );
                }
                $update['post_title'] = $title;
                $update['post_excerpt'] = sanitize_textarea_field( (string) ( $item['pl_lead'] ?? '' ) );
            }
            if ( $replace_source_copy || self::should_update_body( $id, $item ) ) {
                $previous = (string) get_post_field( 'post_content', $id );
                if ( $previous !== $content ) {
                    $update['post_content'] = $content;
                    $content_changed = true;
                }
                // Stable snapshot hash; manual edits remain protected by the importer's hash check.
                update_post_meta( $id, '_nc_full_import_hash', hash( 'sha256', $content ) );
            }
            if ( count( $update ) > 1 ) {
                $saved = wp_update_post( $update, true );
                if ( is_wp_error( $saved ) ) { return array( 0, false, false ); }
            }
        }
        if ( $created || $replace_source_copy ) {
            // Preserve completed English fields when source has no translation.
            if ( '' !== trim( (string) ( $item['en_title'] ?? '' ) ) ) {
                update_post_meta( $id, 'nc_en_title', sanitize_text_field( (string) $item['en_title'] ) );
            }
            if ( '' !== trim( (string) ( $item['en_lead'] ?? '' ) ) ) {
                update_post_meta( $id, 'nc_en_excerpt', sanitize_textarea_field( (string) $item['en_lead'] ) );
            }
            if ( '' !== trim( (string) ( $item['en_body'] ?? '' ) ) ) {
                update_post_meta( $id, 'nc_en_body', wp_kses_post( (string) $item['en_body'] ) );
            }
            update_post_meta( $id, '_nc_clean_content_version', self::CLEANUP_VERSION );
        }
        update_post_meta( $id, '_nc_full_source_key', $key );
        update_post_meta( $id, '_nc_source_url', esc_url_raw( (string) ( $item['source_url'] ?? '' ) ) );
        // Do not revoke a valid medical approval on an identical re-import.
        if ( $content_changed || ! metadata_exists( 'post', $id, '_nc_editorial_status' ) ) {
            update_post_meta( $id, '_nc_editorial_status', 'requires_review' );
        }
        update_post_meta( $id, '_nc_source_document', 'NOVACELL 2026-11-05 v3' );
        update_post_meta( $id, '_nc_english_complete', ! empty( $item['english_complete'] ) ? '1' : '0' );
        if ( ! metadata_exists( 'post', $id, 'nc_en_title' ) ) {
            update_post_meta( $id, 'nc_en_title', sanitize_text_field( (string) ( $item['en_title'] ?? '' ) ) );
        }
        if ( ! metadata_exists( 'post', $id, 'nc_en_excerpt' ) ) {
            update_post_meta( $id, 'nc_en_excerpt', sanitize_textarea_field( (string) ( $item['en_lead'] ?? '' ) ) );
        }
        if ( ! metadata_exists( 'post', $id, 'nc_en_body' ) ) {
            update_post_meta( $id, 'nc_en_body', wp_kses_post( (string) ( $item['en_body'] ?? '' ) ) );
        }
        if ( 'nc_service' === $type && array_key_exists( 'price_pln_minor', $item ) ) {
            if ( ! metadata_exists( 'post', $id, 'nc_price_pln_minor' ) ) {
                update_post_meta( $id, 'nc_price_pln_minor', absint( $item['price_pln_minor'] ) );
            }
            update_post_meta( $id, 'nc_price_is_from', ! empty( $item['price_from'] ) );
            update_post_meta( $id, '_nc_price_project_only', '1' );
            update_post_meta( $id, '_nc_price_label', sanitize_text_field( (string) ( $item['price_label'] ?? '' ) ) );
        } elseif ( 'nc_package' === $type ) {
            update_post_meta( $id, '_nc_price_project_only', '1' );
            update_post_meta( $id, '_nc_price_label', sanitize_text_field( (string) ( $item['price_label'] ?? '' ) ) );
        }
        return array( $id, $created, true );
    }

    public static function import(): array {
        $data = self::data();
        if ( ! $data ) { return array( 'created' => 0, 'updated' => 0, 'routes' => 0, 'error' => 'Nie znaleziono danych źródłowych w instalatorze.' ); }
        $created = 0; $updated = 0; $route_index = array();
        $batches = array(
            (array) ( $data['records'] ?? array() ),
            (array) ( $data['profiles'] ?? array() ),
            (array) ( $data['services'] ?? array() ),
            (array) ( $data['packages'] ?? array() ),
        );
        foreach ( $batches as $batch ) {
            foreach ( $batch as $item ) {
                if ( ! is_array( $item ) ) { continue; }
                $type = sanitize_key( (string) ( $item['post_type'] ?? '' ) );
                if ( ! isset( Content_Types::types()[ $type ] ) && 'post' !== $type ) { continue; }
                $key = sanitize_key( (string) ( $item['source_id'] ?? '' ) );
                if ( ! $key ) { continue; }
                list( $id, $is_new, $success ) = self::write_item( $item, $type, $key );
                if ( ! $success ) { continue; }
                if ( $is_new ) { $created++; } else { $updated++; }
                if ( ! empty( $item['source_url'] ) ) {
                    $path = '/' . trim( (string) $item['source_url'], '/' ) . '/';
                    // Temat 22 w źródłowym Wordzie istnieje wyłącznie po angielsku.
                    // Nie oznaczamy /en/international-patients/ jako odpowiednika PL.
                    if ( 'topic-22' === $key ) { $path = '/pacjenci-z-zagranicy/'; }
                    $path = '//' === $path ? '/' : $path;
                    $route_index[ $key ] = array( 'pl' => $path, 'en' => self::en_url( $path ), 'post_id' => $id,
                        'en_complete' => ! empty( $item['english_complete'] ) );
                }
            }
        }
        update_option( 'nc_full_route_index', $route_index, false );
        self::publish_previous_records();
        self::link_pathways( $route_index );
        update_option( 'nc_full_import_version', self::IMPORT_VERSION, false );
        update_option( 'nc_full_imported_at', gmdate( 'c' ), false );
        // Po imporcie zapisujemy 301 mapę routingu bez ingerencji w inne reguły.
        if ( did_action( 'init' ) ) {
            if ( function_exists( 'nc_theme_register_rewrites' ) ) { \nc_theme_register_rewrites(); }
            flush_rewrite_rules( false );
        }
        return array( 'created' => $created, 'updated' => $updated, 'routes' => count( $route_index ), 'error' => '' );
    }

    private static function publish_previous_records(): void {
        // Wpisy początkowe 0.2.0: nie zmieniamy obcych treści i nie odblokowujemy
        // niezrecenzowanych opisów na produkcji bez odrębnego przełącznika podglądu.
        foreach ( array('nc_problem','nc_pathway','nc_physician','nc_method') as $type ) {
            $ids = get_posts( array('post_type'=>$type,'post_status'=>'any','posts_per_page'=>100,
                'fields'=>'ids','meta_query'=>array(array('key'=>'_nc_seed_key','compare'=>'EXISTS')) ) );
            foreach ( $ids as $id ) {
                if ( 'publish' !== get_post_status( $id ) ) { wp_update_post( array('ID'=>$id,'post_status'=>'publish') ); }
                if ( ! metadata_exists( 'post', $id, '_nc_editorial_status' ) ) {
                    update_post_meta( $id, '_nc_editorial_status', 'requires_review' );
                }
            }
        }
    }

    private static function link_pathways( array $routes ): void {
        $assign = array(
           'topic-1'=>array('profile-katarzyna-moson-rzepka','profile-jakub-rzepka'),
           'topic-2'=>array('profile-katarzyna-moson-rzepka','profile-jakub-rzepka'),
           'topic-3'=>array('profile-jakub-rzepka','profile-katarzyna-moson-rzepka'),
           'topic-4'=>array('profile-jakub-rzepka','profile-katarzyna-moson-rzepka'),
           'topic-33'=>array('profile-katarzyna-moson-rzepka'),
           'topic-35'=>array('profile-katarzyna-moson-rzepka','profile-jakub-rzepka'),
           'topic-36'=>array('profile-katarzyna-moson-rzepka'),
           'topic-38'=>array('profile-katarzyna-moson-rzepka','profile-jakub-rzepka'),
           'topic-8'=>array('profile-jakub-rzepka'),
           'topic-7'=>array('profile-jakub-rzepka','profile-katarzyna-moson-rzepka'),
           'topic-39'=>array('profile-jakub-rzepka'),
           'topic-42'=>array('profile-jakub-rzepka','profile-katarzyna-moson-rzepka'),
           'topic-9'=>array('profile-jakub-rzepka'),
           'topic-13'=>array('profile-jakub-rzepka'),
           'topic-14'=>array('profile-jakub-rzepka','profile-przemyslaw-szalanski'),
           'topic-34'=>array('profile-jakub-rzepka','profile-katarzyna-moson-rzepka'),
           'topic-15'=>array('profile-przemyslaw-szalanski','profile-jakub-rzepka'),
           'topic-17'=>array('profile-przemyslaw-szalanski'),
           'topic-18'=>array('profile-przemyslaw-szalanski'),
           'topic-19'=>array('profile-przemyslaw-szalanski'),
           'topic-21'=>array('profile-jakub-rzepka'),
        );
        foreach ( $assign as $key=>$profile_keys ) {
            if ( empty( $routes[$key]['post_id'] ) ) { continue; }
            $path_id=absint( $routes[$key]['post_id'] );
            if ( metadata_exists( 'post', $path_id, 'nc_physician_ids' ) ) { continue; }
            $ids=array();
            foreach ( $profile_keys as $profile_key ) {
                if ( ! empty( $routes[$profile_key]['post_id'] ) ) { $ids[] = absint( $routes[$profile_key]['post_id'] ); }
            }
            if ( $ids ) { update_post_meta( $path_id, 'nc_physician_ids', $ids ); }
        }

        $methods = array(
            'topic-1' => array('method-terapia-hormonalna-menopauza'),
            'topic-2' => array('method-laser-1470'),
            'topic-7' => array('method-wlewy-dozylne'),
            'topic-8' => array('method-wiek-biologiczny'),
            'topic-9' => array('method-dfpp-tpe-afereza'),
            'topic-13' => array('method-mct-egzosomy-z-wlasnego-osocza'),
            'topic-14' => array('method-laser-1470','method-mct-egzosomy-z-wlasnego-osocza'),
            'topic-34' => array('method-mct-egzosomy-z-wlasnego-osocza'),
            'topic-21' => array('method-mct-egzosomy-z-wlasnego-osocza'),
        );
        foreach ( $methods as $key=>$keys ) {
            if ( empty( $routes[$key]['post_id'] ) ) { continue; }
            $id=absint($routes[$key]['post_id']);
            if ( metadata_exists('post',$id,'nc_method_ids') ) {continue;}
            $values=array();
            foreach ( $keys as $related ) {
                if ( ! empty( $routes[$related]['post_id'] ) ) { $values[]=absint($routes[$related]['post_id']); }
            }
            if($values){update_post_meta($id,'nc_method_ids',$values);}
        }
    }

    private static function en_url( string $path ): string {
        $known = array(
            '/jak-pracujemy/' => '/en/how-we-work/', '/lekarze/' => '/en/doctors/',
            '/cennik/' => '/en/prices/', '/wiedza/' => '/en/knowledge/',
            '/pacjenci-z-zagranicy/' => '/en/international-patients/', '/kontakt/' => '/en/contact/', '/z-czym-pomagamy/menopauza/' => '/en/help-with/menopause/',
            '/z-czym-pomagamy/zmeczenie/' => '/en/help-with/persistent-fatigue/',
            '/chce-zadbac-o-zdrowie/diagnostyka-i-plan/' => '/en/preventive-health/assessment-and-plan/',
            '/lekarze/jakub-rzepka/' => '/en/doctors/jakub-rzepka/',
            '/lekarze/katarzyna-moson-rzepka/' => '/en/doctors/katarzyna-moson-rzepka/',
            '/lekarze/przemyslaw-szalanski/' => '/en/doctors/przemyslaw-szalanski/',
        );
        if ( isset($known[$path]) ) { return $known[$path]; }
        if ( '/en/' === substr( $path, 0, 4 ) ) { return $path; }
        return '/en/' . trim( $path, '/' ) . '/';
    }

    public static function maybe_upgrade(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        if ( self::IMPORT_VERSION !== get_option( 'nc_full_import_version', '' ) ) {
            self::import();
        }
    }

    public static function action(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Brak uprawnień.', 'novacell-manager' ) ); }
        check_admin_referer( 'nc_full_import_action' );
        $result = self::import();
        wp_safe_redirect( add_query_arg( array('page'=>'novacell-full-import','created'=>$result['created'],'updated'=>$result['updated'],'error'=>! empty( $result['error'] ) ? 1 : 0), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function admin(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $data = self::data();
        echo '<div class="wrap"><h1>Pełna zawartość z dokumentu NOVACELL</h1><p>Załączniki C1–C6, wersja 3 z 5 listopada 2026 r. Importowane wpisy otrzymują status WordPress <strong>Opublikowano</strong>, ale zachowują dodatkową flagę <strong>Wymaga zatwierdzenia</strong>.</p>';
        echo '<p>Rozpoznanych rekordów w paczce: <strong>' . esc_html( (string) ( $data['total_records'] ?? 0 ) ) . '</strong>. Łącznie tras: <strong>' . esc_html( (string) count( (array) get_option( 'nc_full_route_index', array() ) ) ) . '</strong>.</p>';
        echo '<p><strong>Cennik 2027:</strong> pozycje są robocze. Nie wprowadzaj cennika do produkcji bez zaakceptowanego arkusza źródłowego.</p>';
        echo '<p>Angielskie pełne tłumaczenia są dostępne tylko dla treści przygotowanych w źródle; pozostałe rekordy mają zakładkę EN do uzupełnienia, bez automatycznego udawania kompletnego tłumaczenia.</p>';
        echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">';
        wp_nonce_field( 'nc_full_import_action' );
        echo '<input type="hidden" name="action" value="nc_full_import"><p><button class="button button-primary" type="submit">Uzupełnij brakujące treści i opublikuj rekordy źródłowe</button></p></form>';
        if ( isset( $_GET['created'] ) ) { echo '<p role="status">Utworzono: ' . esc_html( (string) absint( $_GET['created'] ) ) . '; zaktualizowano lub pominięto: ' . esc_html( (string) absint( $_GET['updated'] ?? 0 ) ) . '.</p>'; }
        echo '</div>';
    }
}
