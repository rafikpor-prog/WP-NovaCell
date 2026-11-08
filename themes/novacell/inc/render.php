<?php
/** Widoki SSR NOVACELL. Bez automatycznych obietnic terapeutycznych. */
defined( 'ABSPATH' ) || exit;

function nc_theme_can_preview(): bool {
    return (bool) get_option( 'nc_public_preview', true ) || (bool) get_option( 'nc_site_approved', false ) || current_user_can( 'manage_options' );
}

function nc_theme_unpublished(): void {
    $en = 'en' === nc_theme_lang();
    echo '<section class="nc-shell nc-unpublished"><p class="nc-eyebrow">NOVACELL</p><h1>' . esc_html( $en ? 'A new website is being prepared' : 'Przygotowujemy nowy serwis NOVACELL' ) . '</h1>';
    echo '<p>' . esc_html( $en ? 'Information about consultations and care pathways will be available following clinical review. Contact the clinic for current information.' : 'Informacje o konsultacjach i ścieżkach opieki udostępnimy po zatwierdzeniu medycznym. W bieżących sprawach skontaktuj się z kliniką.' ) . '</p>';
    echo '<p><a class="nc-button" href="mailto:contact@novacell.clinic">contact@novacell.clinic</a></p></section>';
}

function nc_theme_icon( string $name ): string {
    $allowed = array( 'arrow-right', 'heart-pulse', 'shield-check', 'users', 'clipboard-list', 'calendar', 'sparkles', 'stethoscope', 'globe', 'map-pin', 'check-circle', 'search', 'clock' );
    if ( ! in_array( $name, $allowed, true ) ) { return ''; }
    $path = NC_THEME_PATH . '/assets/icons/' . $name . '.svg';
    if ( ! is_readable( $path ) ) { return ''; }
    $svg = file_get_contents( $path );
    return is_string( $svg ) ? $svg : '';
}

function nc_theme_html( string $html ): string {
    return wp_kses_post( $html );
}

function nc_theme_get_records( string $type, int $limit = 12 ): array {
    if ( ! post_type_exists( $type ) ) { return array(); }
    return get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => $limit, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
}

function nc_theme_record( string $type, string $route ): ?\WP_Post {
    if ( ! post_type_exists( $type ) ) { return null; }
    $posts = get_posts( array( 'post_type' => $type, 'post_status' => array( 'draft', 'publish', 'pending', 'private' ), 'posts_per_page' => 1, 'meta_key' => '_nc_route_key', 'meta_value' => $route ) );
    return $posts ? $posts[0] : null;
}

function nc_theme_record_text( \WP_Post $post, string $lang, string $field ): string {
    if ( 'title' === $field ) { return 'en' === $lang ? (string) get_post_meta( $post->ID, 'nc_en_title', true ) : (string) $post->post_title; }
    if ( 'lead' === $field ) { return 'en' === $lang ? (string) get_post_meta( $post->ID, 'nc_en_excerpt', true ) : (string) $post->post_excerpt; }
    if ( 'body' === $field ) { return 'en' === $lang ? (string) get_post_meta( $post->ID, 'nc_en_body', true ) : (string) $post->post_content; }
    return '';
}

function nc_theme_section_intro( array $data, string $lang, string $id ): void {
    $title = (string) ( $data['title'] ?? '' );
    $lead = (string) ( $data['lead'] ?? '' );
    echo '<div class="nc-section-heading"><span class="nc-eyebrow">' . esc_html( 'NOVACELL' ) . '</span><h2>' . esc_html( $title ) . '</h2>';
    if ( $lead ) { echo '<p class="nc-section-lead">' . esc_html( $lead ) . '</p>'; }
    echo '</div>';
}

function nc_theme_cta( array $data, string $lang, string $class = 'nc-button nc-button--outline' ): void {
    $label = (string) ( $data['cta_label'] ?? '' );
    $link = (string) ( $data['cta_url'] ?? '' );
    if ( ! $label || ! $link ) { return; }
    // Wewnętrzne linki mają mapowanie językowe i są edytowalne w panelu.
    $url = preg_match( '#^(https?://|mailto:|tel:)#i', $link ) ? $link : home_url( $link );
    if ( ! preg_match( '#^(https?://|mailto:|tel:)#i', $link ) ) {
        $resolved = nc_theme_resolve_path( $link );
        if ( $resolved ) { $url = nc_theme_link( $resolved[0], $resolved[1] ); }
    }
    echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '"><span>' . esc_html( $label ) . '</span>' . nc_theme_icon( 'arrow-right' ) . '</a>';
}

function nc_theme_display_home(): void {
    if ( ! nc_theme_can_preview() ) { nc_theme_unpublished(); return; }
    $sections = get_option( 'nc_home_sections', array() );
    if ( empty( $sections ) ) {
        echo '<section class="nc-shell nc-unpublished"><h1>NovaCell Theme</h1><p>' . esc_html( 'en' === nc_theme_lang() ? 'Activate NovaCell Manager and import starter content to preview the website.' : 'Aktywuj NovaCell Manager i zaimportuj materiały startowe, aby wyświetlić stronę.' ) . '</p></section>';
        return;
    }
    usort( $sections, static fn( $a, $b ) => (int) $a['order'] <=> (int) $b['order'] );
    $lang = nc_theme_lang();
    foreach ( $sections as $section ) {
        if ( empty( $section['enabled'] ) ) { continue; }
        $id = sanitize_key( (string) ( $section['id'] ?? '' ) );
        $data = (array) ( $section[ $lang ] ?? array() );
        $classes = 'nc-section nc-section--' . $id;
        echo '<section class="' . esc_attr( $classes ) . '" id="section-' . esc_attr( $id ) . '" aria-label="' . esc_attr( (string) ( $data['title'] ?? $id ) ) . '"><div class="nc-shell">';
        if ( 'hero' === $id ) {
            echo '<div class="nc-hero-layout"><div class="nc-hero-copy"><p class="nc-eyebrow">NOVACELL · WARSZAWA</p><h1>' . esc_html( (string) ( $data['title'] ?? '' ) ) . '</h1>';
            echo '<div class="nc-richtext nc-hero-intro">' . nc_theme_html( (string) ( $data['body'] ?? '' ) ) . '</div>';
            echo '<p class="nc-hero-motto">' . esc_html( (string) ( $data['lead'] ?? '' ) ) . '</p>';
            nc_theme_cta( $data, $lang, 'nc-button' );
            echo '<p class="nc-hero-disclaimer">' . esc_html( 'en' === $lang ? 'Medical consultations are arranged after individual assessment.' : 'Konsultacje i zabiegi wymagają indywidualnej kwalifikacji medycznej.' ) . '</p></div>';
            echo '<div class="nc-hero-art" aria-hidden="true"><div class="nc-hero-orbit nc-hero-orbit--one"></div><div class="nc-hero-orbit nc-hero-orbit--two"></div><div class="nc-hero-art-label">N</div></div></div>';
        } elseif ( 'problems' === $id ) {
            nc_theme_section_intro( $data, $lang, $id );
            echo '<div class="nc-card-grid nc-card-grid--problems">';
            foreach ( nc_theme_get_records( 'nc_problem' ) as $i => $post ) {
                $route = sanitize_key( (string) get_post_meta( $post->ID, '_nc_route_key', true ) );
                if ( ! isset( nc_theme_routes()[ $route ] ) ) { continue; }
                $title = nc_theme_record_text( $post, $lang, 'title' );
                $lead = nc_theme_record_text( $post, $lang, 'lead' );
                echo '<article class="nc-feature-card"><div class="nc-feature-card__top">' . nc_theme_icon( 0 === $i ? 'heart-pulse' : ( 1 === $i ? 'shield-check' : 'clipboard-list' ) ) . '</div>';
                echo '<h3><a href="' . esc_url( nc_theme_link( $route, $lang ) ) . '">' . esc_html( $title ) . '</a></h3><p>' . esc_html( $lead ) . '</p>';
                echo '<span class="nc-card-action">' . esc_html( 'en' === $lang ? 'Explore pathway' : 'Poznaj ścieżkę' ) . nc_theme_icon( 'arrow-right' ) . '</span></article>';
            }
            echo '</div>';
            echo '<div class="nc-problems-note">' . nc_theme_html( (string) ( $data['body'] ?? '' ) ) . '</div>';
        } elseif ( 'steps' === $id ) {
            nc_theme_section_intro( $data, $lang, $id );
            $text = html_entity_decode( wp_strip_all_tags( (string) ( $data['body'] ?? '' ), true ), ENT_QUOTES, 'UTF-8' );
            preg_match_all( '/([1-6])\.\s*(.*?)(?=\s+[1-6]\.\s*|$)/us', $text, $matches, PREG_SET_ORDER );
            echo '<ol class="nc-steps">';
            foreach ( $matches as $match ) {
                echo '<li class="nc-step"><span class="nc-step-number" aria-hidden="true">' . esc_html( $match[1] ) . '</span><p>' . esc_html( trim( $match[2] ) ) . '</p></li>';
            }
            echo '</ol>';
            nc_theme_cta( $data, $lang );
        } elseif ( 'doctors' === $id ) {
            nc_theme_section_intro( $data, $lang, $id );
            nc_theme_doctor_cards( $lang );
            nc_theme_cta( $data, $lang );
        } elseif ( 'why' === $id ) {
            nc_theme_section_intro( $data, $lang, $id );
            echo '<div class="nc-reason-grid">';
            $body = (string) ( $data['body'] ?? '' );
            preg_match_all( '/<p[^>]*>(.*?)<\/p>/is', $body, $texts );
            foreach ( (array) ( $texts[1] ?? array() ) as $i => $text ) {
                echo '<div class="nc-reason-item">' . nc_theme_icon( 0 === $i % 2 ? 'check-circle' : 'shield-check' ) . '<p>' . esc_html( wp_strip_all_tags( $text ) ) . '</p></div>';
            }
            echo '</div>';
            nc_theme_cta( $data, $lang );
        } elseif ( 'knowledge' === $id ) {
            nc_theme_section_intro( $data, $lang, $id );
            echo '<div class="nc-knowledge-grid">';
            preg_match_all( '/<p[^>]*>(.*?)<\/p>/is', (string) ( $data['body'] ?? '' ), $texts );
            foreach ( (array) ( $texts[1] ?? array() ) as $i => $text ) {
                echo '<div class="nc-knowledge-item"><span>' . esc_html( sprintf( '%02d', $i + 1 ) ) . '</span><h3>' . esc_html( wp_strip_all_tags( $text ) ) . '</h3><p>' . esc_html( 'en' === $lang ? 'Editorial draft pending medical review' : 'Materiał roboczy do recenzji medycznej' ) . '</p></div>';
            }
            echo '</div>';
        } elseif ( 'contact' === $id ) {
            nc_theme_section_intro( $data, $lang, $id );
            echo '<div class="nc-contact-panel"><div class="nc-richtext">' . nc_theme_html( (string) ( $data['body'] ?? '' ) ) . '</div>';
            nc_theme_cta( $data, $lang, 'nc-button' );
            echo '</div>';
        } else {
            nc_theme_section_intro( $data, $lang, $id );
            echo '<div class="nc-content-slab nc-richtext">' . nc_theme_html( (string) ( $data['body'] ?? '' ) ) . '</div>';
            nc_theme_cta( $data, $lang );
        }
        echo '</div></section>';
    }
}

function nc_theme_doctor_cards( string $lang ): void {
    $doctors = nc_theme_get_records( 'nc_physician', 8 );
    echo '<div class="nc-card-grid nc-card-grid--doctors">';
    foreach ( $doctors as $post ) {
        $title = nc_theme_record_text( $post, $lang, 'title' );
        $desc = nc_theme_record_text( $post, $lang, 'lead' );
        $route = sanitize_key( (string) get_post_meta( $post->ID, '_nc_route_key', true ) );
        if ( ! isset( nc_theme_routes()[ $route ] ) ) { continue; }
        echo '<article class="nc-doctor-card">';
        if ( has_post_thumbnail( $post ) ) {
            echo '<div class="nc-doctor-card__photo">' . get_the_post_thumbnail( $post, 'medium_large', array( 'loading' => 'lazy', 'alt' => $title ) ) . '</div>';
        } else {
            echo '<div class="nc-doctor-card__no-photo" aria-hidden="true">' . nc_theme_icon( 'stethoscope' ) . '</div>';
        }
        echo '<div class="nc-doctor-card__copy"><h3><a href="' . esc_url( nc_theme_link( $route, $lang ) ) . '">' . esc_html( $title ) . '</a></h3><p>' . esc_html( $desc ) . '</p>';
        echo '<a class="nc-text-link" href="' . esc_url( nc_theme_link( $route, $lang ) ) . '">' . esc_html( 'en' === $lang ? 'View profile' : 'Zobacz profil' ) . nc_theme_icon( 'arrow-right' ) . '</a></div></article>';
    }
    echo '</div>';
}

function nc_theme_display_route( string $key, string $lang ): void {
    if ( ! nc_theme_can_preview() ) { nc_theme_unpublished(); return; }
    if ( 'home' === $key ) { nc_theme_display_home(); return; }
    if ( 0 === strpos( $key, 'ncfull-' ) ) { nc_theme_display_full_document( substr( $key, 7 ), $lang ); return; }
    if ( in_array( $key, array( 'help-index', 'prevention-index', 'aesthetics-index', 'methods-index' ), true ) ) { nc_theme_display_archive( $key, $lang ); return; }
    if ( 'doctors' === $key ) {
        nc_theme_route_head( 'en' === $lang ? 'Meet our physicians' : 'Poznaj lekarzy NOVACELL', 'en' === $lang ? 'Meet the clinical team and explore areas of practice.' : 'Poznaj zespół i obszary konsultacji.', $lang );
        echo '<section class="nc-section nc-section--doctors"><div class="nc-shell">'; nc_theme_doctor_cards( $lang ); echo '</div></section>'; return;
    }
    if ( in_array( $key, array( 'jakub-rzepka', 'katarzyna-moson-rzepka', 'przemyslaw-szalanski' ), true ) ) {
        $post = nc_theme_record( 'nc_physician', $key );
        if ( ! $post ) { nc_theme_missing( $lang ); return; }
        nc_theme_route_head( nc_theme_record_text( $post, $lang, 'title' ), nc_theme_record_text( $post, $lang, 'lead' ), $lang );
        echo '<section class="nc-section"><div class="nc-shell nc-route-content nc-richtext"><h2>' . esc_html( 'en' === $lang ? 'Clinical background' : 'Zakres i kwalifikacje' ) . '</h2>';
        echo nc_theme_html( wpautop( nc_theme_record_text( $post, $lang, 'body' ) ) );
        echo '<p><a class="nc-button" href="' . esc_url( nc_theme_link( 'contact', $lang ) ) . '">' . esc_html( 'en' === $lang ? 'Contact the clinic' : 'Skontaktuj się z kliniką' ) . '</a></p></div></section>'; return;
    }
    if ( in_array( $key, array( 'menopause', 'diagnostics', 'fatigue' ), true ) ) {
        $post = nc_theme_record( 'nc_pathway', $key );
        if ( ! $post ) { nc_theme_missing( $lang ); return; }
        nc_theme_route_head( nc_theme_record_text( $post, $lang, 'title' ), nc_theme_record_text( $post, $lang, 'lead' ), $lang );
        $fields = array( 'symptoms' => array('Objawy lub wskazania','Symptoms or reasons for assessment'), 'causes' => array('Możliwe przyczyny','Possible contributing factors'), 'first_visit' => array('Pierwsza wizyta','Your first appointment'), 'diagnostics' => array('Diagnostyka','Assessment and investigations'), 'locations' => array('Miejsce badań','Where tests are performed'), 'risks' => array('Ograniczenia i ryzyka','Limitations and risks'), 'preparation' => array('Przygotowanie','Preparing for your visit') );
        echo '<section class="nc-section"><div class="nc-shell nc-route-content">';
        foreach ( $fields as $field => $labels ) {
            $meta_key = 'nc_' . ( 'en' === $lang ? 'en_' : '' ) . $field;
            $content = (string) get_post_meta( $post->ID, $meta_key, true );
            if ( '' === trim( $content ) ) { continue; }
            echo '<section class="nc-route-panel"><h2>' . esc_html( $labels['en' === $lang ? 1 : 0] ) . '</h2><div class="nc-richtext">' . nc_theme_html( $content ) . '</div></section>';
        }
        $ids = (array) get_post_meta( $post->ID, 'nc_physician_ids', true );
        if ( $ids ) {
            echo '<section class="nc-route-panel"><h2>' . esc_html( 'en' === $lang ? 'Your clinical team' : 'Lekarze prowadzący' ) . '</h2><ul class="nc-linked-doctors">';
            foreach ( array_map( 'absint', $ids ) as $id ) {
                $person = get_post( $id ); if ( ! $person || 'nc_physician' !== $person->post_type ) { continue; }
                $route = sanitize_key( (string) get_post_meta( $person->ID, '_nc_route_key', true ) );
                if ( isset( nc_theme_routes()[ $route ] ) ) { echo '<li><a href="' . esc_url( nc_theme_link( $route, $lang ) ) . '">' . esc_html( nc_theme_record_text( $person, $lang, 'title' ) ) . '</a></li>'; }
            }
            echo '</ul></section>';
        }
        echo '<p class="nc-medical-note">' . esc_html( 'en' === $lang ? 'This information is for educational purposes and does not replace an individual medical consultation.' : 'Opis ma charakter informacyjny i nie zastępuje indywidualnej konsultacji lekarskiej.' ) . '</p></div></section>'; return;
    }
    if ( 'knowledge' === $key ) {
        nc_theme_route_head( 'en' === $lang ? 'Knowledge and patient guides' : 'Wiedza i poradniki', 'en' === $lang ? 'Clinical information is currently under editorial review.' : 'Treści medyczne są aktualnie weryfikowane redakcyjnie.', $lang );
        echo '<section class="nc-section"><div class="nc-shell nc-route-content"><p>' . esc_html( 'en' === $lang ? 'New reviewed articles will appear here as they are approved.' : 'Tutaj pojawią się zatwierdzone artykuły i poradniki NOVACELL.' ) . '</p></div></section>'; return;
    }
    $pages = (array) get_option( 'nc_route_pages', array() );
    foreach ( $pages as $page ) {
        if ( (string) ( $page['id'] ?? '' ) === $key ) {
            $item = (array) ( $page[ $lang ] ?? array() );
            nc_theme_route_head( (string) ( $item['title'] ?? '' ), (string) ( $item['lead'] ?? '' ), $lang );
            echo '<section class="nc-section"><div class="nc-shell nc-route-content nc-richtext">' . nc_theme_html( (string) ( $item['body'] ?? '' ) ) . '</div></section>'; return;
        }
    }
    nc_theme_missing( $lang );
}

function nc_theme_route_head( string $title, string $lead, string $lang ): void {
    echo '<section class="nc-page-hero"><div class="nc-shell">';
    echo '<nav class="nc-breadcrumbs" aria-label="' . esc_attr( 'en' === $lang ? 'Breadcrumb' : 'Ścieżka nawigacji' ) . '"><ol><li><a href="' . esc_url( nc_theme_link( 'home', $lang ) ) . '">' . esc_html( 'en' === $lang ? 'Home' : 'Strona główna' ) . '</a></li>';
    $current_key = nc_theme_route_key();
    $routes = nc_theme_routes();
    $path = (string) ( $routes[ $current_key ][ $lang ] ?? '' );
    $parents = array(
        '/z-czym-pomagamy/' => array('help-index','Z czym możemy Ci pomóc?','How we can help'),
        '/chce-zadbac-o-zdrowie/' => array('prevention-index','Chcę zadbać o zdrowie','Preventive health'),
        '/wyglad-i-estetyka/' => array('aesthetics-index','Wygląd i estetyka','Aesthetics'),
        '/metody/' => array('methods-index','Metody leczenia','Treatment methods'),
    );
    $pl_path = (string) ( $routes[ $current_key ]['pl'] ?? $path );
    foreach ( $parents as $prefix => $parent ) {
        if ( $pl_path !== $prefix && str_starts_with( $pl_path, $prefix ) ) {
            echo '<li><a href="' . esc_url( nc_theme_link( $parent[0], $lang ) ) . '">' . esc_html( $parent['en' === $lang ? 2 : 1] ) . '</a></li>';
            break;
        }
    }
    echo '<li aria-current="page">' . esc_html( $title ) . '</li></ol></nav>';
    echo '<p class="nc-eyebrow">NOVACELL · WARSZAWA</p><h1>' . esc_html( $title ) . '</h1><p class="nc-page-lead">' . esc_html( $lead ) . '</p>';
    echo '<a class="nc-back-link" href="' . esc_url( nc_theme_link( 'home', $lang ) ) . '">' . esc_html( 'en' === $lang ? 'Back to home' : 'Wróć na stronę główną' ) . '</a></div></section>';
}

function nc_theme_missing( string $lang ): void {
    echo '<section class="nc-shell nc-unpublished"><h1>' . esc_html( 'en' === $lang ? 'Content is being prepared' : 'Treść jest w przygotowaniu' ) . '</h1></section>';
}


/** Pełna treść źródłowa z Worda: zapis i aktualizacja w panelu NOVACELL Manager. */
function nc_theme_display_full_document( string $source, string $lang ): void {
    $routes = (array) get_option( 'nc_full_route_index', array() );
    if ( empty( $routes[ $source ]['post_id'] ) ) { nc_theme_missing( $lang ); return; }
    $post = get_post( absint( $routes[ $source ]['post_id'] ) );
    if ( ! $post || 'publish' !== $post->post_status ) { nc_theme_missing( $lang ); return; }
    if ( 'topic-22' === $source && 'pl' === $lang ) {
        nc_theme_route_head( 'Pacjenci z zagranicy', 'Szczegółowe informacje dla pacjentów międzynarodowych dostępne są po angielsku.', $lang );
        echo '<section class="nc-section"><div class="nc-shell nc-route-content"><p><a class="nc-button" href="' . esc_url( nc_theme_link( 'ncfull-topic-22', 'en' ) ) . '">International patients</a></p></div></section>';
        return;
    }
    $title = nc_theme_record_text( $post, $lang, 'title' );
    $lead = nc_theme_record_text( $post, $lang, 'lead' );
    $body = nc_theme_record_text( $post, $lang, 'body' );
    if ( 'en' === $lang && '' === trim( wp_strip_all_tags( $body ) ) ) {
        // Nie prezentujemy polskiego tekstu z atrybutem lang=en ani fikcyjnych tłumaczeń.
        $title = $title ?: 'English translation in preparation';
        nc_theme_route_head( $title, 'This medical information is undergoing translation and clinical review.', $lang );
        echo '<section class="nc-section"><div class="nc-shell nc-route-content">';
        echo '<p>The full English version is not yet available. Please contact NOVACELL for information about current services and appointments.</p>';
        echo '<p><a class="nc-button" href="' . esc_url( nc_theme_link( 'contact', 'en' ) ) . '">Contact NOVACELL</a></p></div></section>';
        return;
    }
    nc_theme_route_head( $title ?: $post->post_title, $lead, $lang );
    echo '<article class="nc-section nc-full-document"><div class="nc-shell nc-route-content nc-richtext">';
    if ( ! (bool) get_option( 'nc_site_approved', false ) ) {
        echo '<p class="nc-medical-review" role="note">' . esc_html( 'en' === $lang ? 'Editorial preview. Medical content requires clinical verification before use by patients.' : 'Podgląd redakcyjny. Opisy wymagają zatwierdzenia medycznego przed wykorzystaniem przez pacjentów.' ) . '</p>';
    }
    if ( 'topic-28' === $source ) {
        echo '<p class="nc-medical-review">' . esc_html( 'en' === $lang ? 'Draft 2027 prices; final costs must be confirmed with the clinic.' : 'Projekt cennika 2027. Ceny wymagają potwierdzenia w klinice.' ) . '</p>';
    }
    echo nc_theme_faq_replace( $body, $lang );
    if ( 'nc_pathway' === $post->post_type ) {
        $ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, 'nc_physician_ids', true ) ) );
        if ( $ids ) {
            echo '<section class="nc-linked-doctors"><h2>' . esc_html( 'en' === $lang ? 'Physicians involved in this pathway' : 'Lekarze prowadzący' ) . '</h2><ul class="nc-related-links">';
            foreach ( $ids as $id ) {
                $doctor = get_post( $id );
                if ( ! $doctor || 'publish' !== $doctor->post_status ) { continue; }
                $source_key = (string) get_post_meta( $id, '_nc_full_source_key', true );
                $url = isset( $routes[ $source_key ] ) ? nc_theme_link( 'ncfull-' . $source_key, $lang ) : nc_theme_link( 'doctors', $lang );
                echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( nc_theme_record_text( $doctor, $lang, 'title' ) ) . '</a></li>';
            }
            echo '</ul></section>';
        }
    }
    if ( 'nc_pathway' === $post->post_type ) {
        $method_ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, 'nc_method_ids', true ) ) );
        if ( $method_ids ) {
            echo '<section class="nc-linked-methods"><h2>' . esc_html( 'en' === $lang ? 'Related methods' : 'Powiązane metody' ) . '</h2><ul class="nc-related-links">';
            foreach ( $method_ids as $id ) {
                $method = get_post( $id );
                if ( ! $method || 'publish' !== $method->post_status ) {continue;}
                $source_key = (string) get_post_meta( $id, '_nc_full_source_key', true );
                $url = isset( $routes[ $source_key ] ) ? nc_theme_link( 'ncfull-' . $source_key, $lang ) : nc_theme_link( 'methods-index', $lang );
                echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( nc_theme_record_text( $method, $lang, 'title' ) ?: $method->post_title ) . '</a></li>';
            }
            echo '</ul></section>';
        }
    }
    echo '<div class="nc-route-end"><a class="nc-button" href="' . esc_url( nc_theme_link( 'contact', $lang ) ) . '">' . esc_html( 'en' === $lang ? 'Contact the clinic' : 'Skontaktuj się z kliniką' ) . '</a></div>';
    echo '</div></article>';
}

/** Katalogi zawierają wyłącznie opublikowane rekordy w CMS. */
function nc_theme_display_archive( string $key, string $lang ): void {
    $defaults = array(
        'help-index' => array( 'Z czym możemy Ci pomóc?', 'How can we help?' ),
        'prevention-index' => array( 'Chcę zadbać o zdrowie', 'Preventive health' ),
        'aesthetics-index' => array( 'Wygląd i estetyka', 'Aesthetics and appearance' ),
        'methods-index' => array( 'Metody leczenia A–Z', 'Treatment methods A–Z' ),
    );
    $is_en = 'en' === $lang;
    $archives = class_exists( '\NovaCell\Manager\Navigation_Hub' )
        ? \NovaCell\Manager\Navigation_Hub::archives()
        : (array)get_option('nc_archive_pages',array());
    $info = (array)( $archives[ $key ][ $lang ] ?? array() );
    $headline = (string)( $info['title'] ?? $defaults[ $key ][ $is_en ? 1 : 0 ] );
    $lead = (string)( $info['lead'] ?? ( $is_en ? 'Explore clinical information and care pathways.' : 'Poznaj ścieżki opieki i metody.' ) );
    nc_theme_route_head( $headline, $lead, $lang );
    echo '<section class="nc-section"><div class="nc-shell">';
    if ( !empty($info['body']) ) {
        echo '<div class="nc-richtext nc-archive-intro">' . nc_theme_html( (string)$info['body'] ) . '</div>';
    }
    echo '</div></section>';
    $list = (array) get_option( 'nc_full_route_index', array() );
    echo '<section class="nc-section"><div class="nc-shell nc-card-grid nc-card-grid--problems">';
    foreach ( $list as $source => $route ) {
        if ( empty( $route['post_id'] ) ) { continue; }
        $post = get_post( absint( $route['post_id'] ) );
        if ( ! $post || 'publish' !== $post->post_status ) { continue; }
        $path = (string) ( $route['pl'] ?? '' );
        $match = false;
        if ( 'methods-index' === $key ) { $match = 'nc_method' === $post->post_type; }
        elseif ( 'aesthetics-index' === $key ) { $match = 0 === strpos( $path, '/wyglad-i-estetyka/' ); }
        elseif ( 'prevention-index' === $key ) { $match = 0 === strpos( $path, '/chce-zadbac-o-zdrowie/' ); }
        elseif ( 'help-index' === $key ) { $match = 'nc_pathway' === $post->post_type && 0 !== strpos( $path, '/wyglad-i-estetyka/' ) && 0 !== strpos( $path, '/chce-zadbac-o-zdrowie/' ); }
        if ( ! $match ) { continue; }
        echo '<article class="nc-feature-card"><h2><a href="' . esc_url( nc_theme_link( 'ncfull-' . $source, $lang ) ) . '">' . esc_html( nc_theme_record_text( $post, $lang, 'title' ) ?: $post->post_title ) . '</a></h2>';
        echo '<p>' . esc_html( wp_trim_words( nc_theme_record_text( $post, $lang, 'lead' ), 26 ) ) . '</p></article>';
    }
    echo '</div></section>';
}
