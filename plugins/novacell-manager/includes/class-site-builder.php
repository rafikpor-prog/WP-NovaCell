<?php
/**
 * Edytor sekcji, podstron i importu. Każda sekcja ma pl/en i własny WYSIWYG.
 * @package NovaCellManager
 */
namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Site_Builder {
    private const FIELDS = array( 'title', 'lead', 'body', 'cta_label', 'cta_url' );

    public static function boot(): void {
        add_action( 'admin_menu', array( self::class, 'menus' ) );
        add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
        add_action( 'admin_post_nc_save_home', array( self::class, 'save_home' ) );
        add_action( 'admin_post_nc_add_home_section', array( self::class, 'add_home_section' ) );
        add_action( 'admin_post_nc_delete_home_section', array( self::class, 'delete_home_section' ) );
        add_action( 'admin_post_nc_save_pages', array( self::class, 'save_pages' ) );
        add_action( 'admin_post_nc_import_starter', array( Starter_Import::class, 'import_action' ) );
    }

    public static function menus(): void {
        add_submenu_page( 'novacell-manager', 'Strona główna i sekcje', 'Strona główna i sekcje', 'manage_options', 'novacell-home-builder', array( self::class, 'home_editor' ) );
        add_submenu_page( 'novacell-manager', 'Podstrony PL / EN', 'Podstrony PL / EN', 'manage_options', 'novacell-pages', array( self::class, 'pages_editor' ) );
        add_submenu_page( 'novacell-manager', 'Materiały startowe', 'Materiały startowe', 'manage_options', 'novacell-starter', array( self::class, 'starter_page' ) );
    }

    public static function assets( string $hook ): void {
        if ( false === strpos( $hook, 'novacell' ) ) { return; }
        wp_enqueue_style( 'nc-builder', plugins_url( 'assets/builder.css', NC_MANAGER_FILE ), array(), NC_MANAGER_VERSION );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'nc-builder', plugins_url( 'assets/builder.js', NC_MANAGER_FILE ), array( 'jquery', 'jquery-ui-sortable' ), NC_MANAGER_VERSION, true );
    }

    private static function guard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Brak uprawnień.', 'novacell-manager' ) );
        }
    }

    public static function home_editor(): void {
        self::guard();
        $sections = get_option( 'nc_home_sections', array() );
        if ( ! is_array( $sections ) ) { $sections = array(); }
        usort( $sections, static fn( $a, $b ) => (int) ( $a['order'] ?? 0 ) <=> (int) ( $b['order'] ?? 0 ) );
        echo '<div class="wrap nc-builder nc-builder--studio"><header class="nc-studio-top"><div><span class="nc-studio-eyebrow">NOVACELL / EDYTOR</span><h1>Strona główna</h1><p>Zarządzaj wszystkimi sekcjami, treścią i językami z jednego miejsca.</p></div><span class="nc-studio-version">Manager ' . esc_html( NC_MANAGER_VERSION ) . '</span></header>';
        echo '<div class="nc-studio-layout"><div class="nc-studio-edit">';
        self::feedback();
        echo '<p class="nc-studio-hint">Wybierz sekcję, rozwiń edycję, a następnie wprowadź treści w zakładkach PL / EN. Kolejność można zmieniać myszą i klawiaturą. Przed odświeżeniem podglądu zapisz zmiany.</p>';
        echo '<p><a class="button button-secondary" href="' . esc_url( add_query_arg( array( 'nc_view' => 'home', 'nc_lang' => 'pl' ), home_url( '/' ) ) ) . '" target="_blank" rel="noopener">Otwórz podgląd PL</a> ';
        echo '<a class="button button-secondary" href="' . esc_url( add_query_arg( array( 'nc_view' => 'home', 'nc_lang' => 'en' ), home_url( '/' ) ) ) . '" target="_blank" rel="noopener">Otwórz podgląd EN</a></p>';
        echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" id="nc-home-form">';
        wp_nonce_field( 'nc_save_home' );
        echo '<input type="hidden" name="action" value="nc_save_home">';
        echo '<div id="nc-sortable-sections">';
        foreach ( $sections as $section ) {
            $id = sanitize_key( (string) ( $section['id'] ?? '' ) );
            if ( ! $id ) { continue; }
            $heading = (string) ( $section['pl']['title'] ?? $id );
            echo '<section class="nc-section-card" data-nc-id="' . esc_attr( $id ) . '">';
            echo '<div class="nc-card-head"><span class="nc-card-index" aria-hidden="true">' . esc_html( (string) ( (int) ( $section['order'] ?? 0 ) + 1 ) ) . '</span><span class="nc-drag" aria-hidden="true" title="Przeciągnij sekcję">&#x2637;</span>';
            echo '<div class="nc-card-name"><strong>' . esc_html( $heading ) . '</strong><small>' . esc_html( $id ) . '</small></div>';
            echo '<button type="button" class="button nc-up" aria-label="Przesuń sekcję w górę">W górę</button>';
            echo '<button type="button" class="button nc-down" aria-label="Przesuń sekcję w dół">W dół</button>';
            echo '<button type="button" class="button nc-toggle" aria-expanded="false">Edytuj</button></div>';
            if ( 0 === strpos( $id, 'custom-' ) ) {
                $remove = wp_nonce_url( add_query_arg( array( 'action' => 'nc_delete_home_section', 'section' => $id ), admin_url( 'admin-post.php' ) ), 'nc_delete_home_section_' . $id );
                echo '<p class="nc-remove-row"><a class="button button-link-delete nc-confirm-remove" href="' . esc_url( $remove ) . '">Usuń tę własną sekcję</a></p>';
            }
            echo '<div class="nc-card-controls">';
            echo '<label><input type="checkbox" name="sections[' . esc_attr( $id ) . '][enabled]" value="1" ' . checked( ! empty( $section['enabled'] ), true, false ) . '> Wyświetlaj sekcję</label>';
            echo '<input type="hidden" name="sections[' . esc_attr( $id ) . '][order]" class="nc-order" value="' . esc_attr( (string) (int) ( $section['order'] ?? 0 ) ) . '">';
            echo '</div>';
            echo '<div class="nc-card-details" hidden>';
            self::language_tabs( $id, (array) $section, 'sections', true );
            echo '</div></section>';
        }
        echo '</div><p class="nc-save-row"><button type="submit" class="button button-primary button-hero">Zapisz kolejność i treści strony</button></p></form>';
        echo '<form class="nc-add-section" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'nc_add_home_section' );
        echo '<input type="hidden" name="action" value="nc_add_home_section"><button class="button button-secondary button-large" type="submit">Dodaj nową sekcję PL / EN</button></form></div>';
        $pl_preview = add_query_arg( array( 'nc_view' => 'home', 'nc_lang' => 'pl' ), home_url( '/' ) );
        $en_preview = add_query_arg( array( 'nc_view' => 'home', 'nc_lang' => 'en' ), home_url( '/' ) );
        echo '<aside class="nc-studio-preview" aria-label="Podgląd strony"><div class="nc-preview-toolbar"><div><strong>Podgląd strony</strong><small>Zapisz zmiany, aby zobaczyć je na stronie.</small></div><div class="nc-preview-actions">';
        echo '<button type="button" class="button nc-preview-language" data-nc-preview-url="' . esc_url( $pl_preview ) . '" aria-pressed="true">PL</button>';
        echo '<button type="button" class="button nc-preview-language" data-nc-preview-url="' . esc_url( $en_preview ) . '" aria-pressed="false">EN</button>';
        echo '<button type="button" class="button nc-preview-refresh" aria-label="Odśwież podgląd">Odśwież</button></div></div>';
        echo '<iframe id="nc-studio-frame" title="Podgląd strony głównej NOVACELL" src="' . esc_url( $pl_preview ) . '" loading="lazy"></iframe>';
        echo '<p class="nc-preview-caption">Podgląd PL/EN działa bez logowania po włączeniu publicznej widoczności. Treści z Worda nadal wymagają recenzji medycznej.</p></aside></div></div>';
    }

    private static function language_tabs( string $id, array $record, string $root, bool $cta ): void {
        $group_id = $root . '-' . $id;
        echo '<div class="nc-editor-langs" data-nc-lang-tabs="1">';
        echo '<div role="tablist" class="nc-tab-list" aria-label="Wybierz język">';
        foreach ( array( 'pl' => 'Język Polski', 'en' => 'Język Angielski' ) as $lang => $label ) {
            echo '<button type="button" class="nc-lang-tab" role="tab" id="tab-' . esc_attr( $group_id . '-' . $lang ) . '" aria-selected="' . ( 'pl' === $lang ? 'true' : 'false' ) . '" aria-controls="panel-' . esc_attr( $group_id . '-' . $lang ) . '" data-nc-tab="' . esc_attr( $lang ) . '">' . esc_html( $label ) . '</button>';
        }
        echo '</div>';
        foreach ( array( 'pl', 'en' ) as $lang ) {
            $v = (array) ( $record[ $lang ] ?? array() );
            $name = $root . '[' . $id . '][' . $lang . ']';
            echo '<div class="nc-lang-panel" role="tabpanel" data-nc-panel="' . esc_attr( $lang ) . '" id="panel-' . esc_attr( $group_id . '-' . $lang ) . '" aria-labelledby="tab-' . esc_attr( $group_id . '-' . $lang ) . '"' . ( 'en' === $lang ? ' hidden' : '' ) . '>';
            self::input( $name . '[title]', (string) ( $v['title'] ?? '' ), 'Nagłówek sekcji', 'text' );
            self::input( $name . '[lead]', (string) ( $v['lead'] ?? '' ), 'Krótki opis', 'textarea' );
            echo '<p><label><strong>Treść szczegółowa (WYSIWYG)</strong></label></p>';
            wp_editor( (string) ( $v['body'] ?? '' ), 'nc_body_' . str_replace( '-', '_', $group_id ) . '_' . $lang, array( 'textarea_name' => $name . '[body]', 'textarea_rows' => 5, 'media_buttons' => false, 'teeny' => true ) );
            if ( $cta ) {
                self::input( $name . '[cta_label]', (string) ( $v['cta_label'] ?? '' ), 'Tekst przycisku', 'text' );
                self::input( $name . '[cta_url]', (string) ( $v['cta_url'] ?? '' ), 'Adres przycisku', 'text' );
            }
            echo '</div>';
        }
        echo '</div>';
    }

    private static function input( string $name, string $value, string $label, string $type ): void {
        $id = sanitize_title( $name );
        echo '<p><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label></p>';
        if ( 'textarea' === $type ) {
            echo '<textarea class="large-text" rows="3" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '">' . esc_textarea( $value ) . '</textarea>';
        } else {
            echo '<input class="large-text" type="text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '">';
        }
    }

    private static function sanitized_language( array $raw, bool $cta ): array {
        $result = array(
            'title' => sanitize_text_field( (string) ( $raw['title'] ?? '' ) ),
            'lead' => sanitize_textarea_field( (string) ( $raw['lead'] ?? '' ) ),
            'body' => wp_kses_post( (string) ( $raw['body'] ?? '' ) ),
        );
        if ( $cta ) {
            $result['cta_label'] = sanitize_text_field( (string) ( $raw['cta_label'] ?? '' ) );
            $result['cta_url'] = esc_url_raw( (string) ( $raw['cta_url'] ?? '' ) );
        }
        return $result;
    }

    public static function save_home(): void {
        self::guard();
        check_admin_referer( 'nc_save_home' );
        $raw = isset( $_POST['sections'] ) && is_array( $_POST['sections'] ) ? wp_unslash( $_POST['sections'] ) : array();
        $existing = get_option( 'nc_home_sections', array() );
        $out = array();
        // Whitelist IDs: nie tworzymy dowolnych sekcji przez modyfikację POST.
        foreach ( $existing as $saved ) {
            $id = sanitize_key( (string) $saved['id'] );
            $input = isset( $raw[ $id ] ) && is_array( $raw[ $id ] ) ? $raw[ $id ] : array();
            $out[] = array(
                'id' => $id,
                'type' => sanitize_key( (string) $saved['type'] ),
                'order' => min( 999, max( 0, (int) ( $input['order'] ?? $saved['order'] ) ) ),
                'enabled' => isset( $input['enabled'] ) ? 1 : 0,
                'pl' => self::sanitized_language( (array) ( $input['pl'] ?? array() ), true ),
                'en' => self::sanitized_language( (array) ( $input['en'] ?? array() ), true ),
            );
        }
        usort( $out, static fn( $a, $b ) => $a['order'] <=> $b['order'] );
        update_option( 'nc_home_sections', $out, false );
        wp_safe_redirect( add_query_arg( array( 'page' => 'novacell-home-builder', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /** Add an editable section without modifying existing items. */
    public static function add_home_section(): void {
        self::guard();
        check_admin_referer( 'nc_add_home_section' );
        $sections = (array) get_option( 'nc_home_sections', array() );
        $id = 'custom-' . substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 12 );
        $sections[] = array(
            'id' => $id, 'type' => 'custom', 'order' => count( $sections ), 'enabled' => 0,
            'pl' => array( 'title' => 'Nowa sekcja', 'lead' => '', 'body' => '', 'cta_label' => '', 'cta_url' => '' ),
            'en' => array( 'title' => 'New section', 'lead' => '', 'body' => '', 'cta_label' => '', 'cta_url' => '' ),
        );
        update_option( 'nc_home_sections', $sections, false );
        wp_safe_redirect( add_query_arg( array( 'page' => 'novacell-home-builder', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /** Only custom sections can be deleted; core sections stay available and may be hidden. */
    public static function delete_home_section(): void {
        self::guard();
        $id = sanitize_key( (string) ( $_GET['section'] ?? '' ) );
        check_admin_referer( 'nc_delete_home_section_' . $id );
        if ( 0 !== strpos( $id, 'custom-' ) ) {
            wp_die( esc_html__( 'Nie można usunąć sekcji systemowej.', 'novacell-manager' ) );
        }
        $sections = array_filter( (array) get_option( 'nc_home_sections', array() ),
            static fn( $item ) => sanitize_key( (string) ( $item['id'] ?? '' ) ) !== $id );
        update_option( 'nc_home_sections', array_values( $sections ), false );
        wp_safe_redirect( add_query_arg( array( 'page' => 'novacell-home-builder', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function pages_editor(): void {
        self::guard();
        $pages = get_option( 'nc_route_pages', array() );
        echo '<div class="wrap nc-builder"><h1>Podstrony: Język Polski | Język Angielski</h1>';
        self::feedback();
        echo '<p>Obie wersje danej podstrony edytujesz w jednym miejscu. Zmiany pozostają w trybie roboczego podglądu.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'nc_save_pages' );
        echo '<input type="hidden" name="action" value="nc_save_pages">';
        foreach ( $pages as $page ) {
            $id = sanitize_key( (string) ( $page['id'] ?? '' ) );
            echo '<section class="nc-section-card"><div class="nc-card-head"><strong>' . esc_html( (string) $page['pl']['title'] ) . '</strong><button type="button" class="button nc-toggle" aria-expanded="false">Edytuj</button></div><div class="nc-card-details" hidden>';
            self::language_tabs( $id, (array) $page, 'pages', false );
            echo '</div></section>';
        }
        echo '<p class="nc-save-row"><button class="button button-primary button-hero" type="submit">Zapisz podstrony PL / EN</button></p></form></div>';
    }

    public static function save_pages(): void {
        self::guard();
        check_admin_referer( 'nc_save_pages' );
        $raw = isset( $_POST['pages'] ) && is_array( $_POST['pages'] ) ? wp_unslash( $_POST['pages'] ) : array();
        $out = array();
        foreach ( (array) get_option( 'nc_route_pages', array() ) as $existing ) {
            $id = sanitize_key( (string) $existing['id'] );
            $input = (array) ( $raw[ $id ] ?? array() );
            $out[] = array( 'id' => $id, 'pl' => self::sanitized_language( (array) ( $input['pl'] ?? array() ), false ), 'en' => self::sanitized_language( (array) ( $input['en'] ?? array() ), false ) );
        }
        update_option( 'nc_route_pages', $out, false );
        wp_safe_redirect( add_query_arg( array( 'page' => 'novacell-pages', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function starter_page(): void {
        self::guard();
        $current = get_option( 'nc_last_import', '' );
        echo '<div class="wrap nc-builder"><h1>Materiały startowe z dokumentu NOVACELL</h1>';
        self::feedback();
        echo '<p>Źródło: dokument z 5 listopada 2026 r., wersja 3. Import utworzy szkice lekarzy, problemów pacjentów, ścieżek i metody DFPP/TPE. Ustawia także sekcje strony głównej i podstrony PL/EN tylko wówczas, gdy nie były wcześniej konfigurowane.</p>';
        echo '<p>Ostatnie uruchomienie importu: <strong>' . esc_html( $current ?: 'brak' ) . '</strong></p>';
        echo '<p><strong>Nie tworzy duplikatów i nie nadpisuje ręcznie zmienionych rekordów.</strong> Pełny import z wersji 0.3+ oznacza wpisy jako opublikowane w WordPress, ale treści wymagają zatwierdzenia medycznego. Ceny pozostają projektem.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'nc_import_starter' );
        echo '<input type="hidden" name="action" value="nc_import_starter"><button type="submit" class="button button-primary">Importuj brakujące materiały robocze</button></form>';
        echo '</div>';
    }

    private static function feedback(): void {
        if ( isset( $_GET['updated'] ) && '1' === (string) $_GET['updated'] ) {
            echo '<div class="notice notice-success is-dismissible"><p>Zmiany zostały zapisane.</p></div>';
        }
        if ( isset( $_GET['created'] ) ) {
            echo '<div class="notice notice-info is-dismissible"><p>Utworzono: ' . esc_html( (string) absint( $_GET['created'] ) ) . ', pominięto istniejące: ' . esc_html( (string) absint( $_GET['skipped'] ?? 0 ) ) . '.</p></div>';
        }
        if ( isset( $_GET['seed_error'] ) && '1' === (string) $_GET['seed_error'] ) {
            echo '<div class="notice notice-error"><p>Brak pliku startowych danych w paczce lub błędny format.</p></div>';
        }
    }
}
