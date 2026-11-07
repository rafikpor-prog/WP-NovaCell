<?php
/**
 * Natywne edytory WYSIWYG i pierwsze relacje rekordów.
 *
 * @package NovaCellManager
 */

namespace NovaCell\Manager;

use WP_Post;

defined( 'ABSPATH' ) || exit;

final class Editors {
    public static function block_editor_enabled( bool $enabled, string $post_type ): bool {
        return isset( Content_Types::types()[ $post_type ] ) ? false : $enabled;
    }

    public static function register_boxes(): void {
        add_meta_box(
            'nc_pathway_fields',
            'Sekcje ścieżki opieki',
            array( self::class, 'render_pathway' ),
            'nc_pathway',
            'normal',
            'high'
        );
        add_meta_box(
            'nc_pathway_relations',
            'Powiązania z danymi kliniki',
            array( self::class, 'render_relations' ),
            'nc_pathway',
            'side',
            'default'
        );
        add_meta_box(
            'nc_method_fields',
            'Opis i bezpieczeństwo metody',
            array( self::class, 'render_method' ),
            'nc_method',
            'normal',
            'high'
        );
        add_meta_box(
            'nc_service_price',
            'Cena usługi',
            array( self::class, 'render_price' ),
            'nc_service',
            'normal',
            'high'
        );
    }

    public static function render_pathway( WP_Post $post ): void {
        wp_nonce_field( 'nc_manager_save', 'nc_manager_nonce' );
        echo '<p>Każda sekcja jest niezależnym polem WYSIWYG. Właściwa strona zostanie złożona przez szablon z powiązanych danych.</p>';
        foreach ( Metadata::PATHWAY_FIELDS as $key => $label ) {
            self::wysiwyg( $post->ID, $key, $label );
        }
    }

    public static function render_method( WP_Post $post ): void {
        wp_nonce_field( 'nc_manager_save', 'nc_manager_nonce' );
        echo '<p>Status i siła dowodów wymagają odrębnej weryfikacji medycznej przed publikacją.</p>';

        self::select(
            $post->ID,
            'nc_method_status',
            'Status metody',
            array(
                ''                   => 'Wymaga oceny',
                'standard'           => 'Leczenie standardowe',
                'medical_device'     => 'Zabieg z wyrobem medycznym',
                'experimental'       => 'Procedura eksperymentalna',
                'clinical_trial'     => 'Badanie kliniczne',
                'hospital_exemption' => 'Wyjątek szpitalny',
            )
        );

        self::select(
            $post->ID,
            'nc_evidence_level',
            'Siła dowodów (wstępna ocena redakcyjna)',
            array(
                ''            => 'Nieoceniona',
                'high'        => '●●● Mocne badania',
                'limited'     => '●●○ Małe badania / wyniki niejednoznaczne',
                'preliminary' => '●○○ Dane wstępne',
            )
        );

        foreach ( Metadata::METHOD_FIELDS as $key => $label ) {
            self::wysiwyg( $post->ID, $key, $label );
        }
    }

    public static function render_relations( WP_Post $post ): void {
        wp_nonce_field( 'nc_manager_save', 'nc_manager_nonce' );
        self::relation( $post->ID, 'nc_physician_ids', 'Lekarze', 'nc_physician' );
        self::relation( $post->ID, 'nc_method_ids', 'Metody', 'nc_method' );
        self::relation( $post->ID, 'nc_service_ids', 'Usługi i ceny', 'nc_service' );
        echo '<p class="description">Przytrzymaj Ctrl lub Cmd, aby wybrać kilka pozycji.</p>';
    }

    public static function render_price( WP_Post $post ): void {
        wp_nonce_field( 'nc_manager_save', 'nc_manager_nonce' );
        foreach ( array( 'nc_price_pln_minor' => 'Cena PLN', 'nc_price_eur_minor' => 'Cena EUR' ) as $key => $label ) {
            $minor  = get_post_meta( $post->ID, $key, true );
            $value  = '' === $minor ? '' : number_format( (int) $minor / 100, 2, ',', '' );
            echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label></p>';
            echo '<input class="regular-text" id="' . esc_attr( $key ) . '" name="nc_manager_' . esc_attr( $key ) . '" type="text" inputmode="decimal" value="' . esc_attr( $value ) . '" placeholder="np. 950,00" />';
            echo '<p class="description">Kwota w jednostkach waluty. System zapisuje ją jako liczbę całkowitą w groszach / centach.</p>';
        }
        $checked = (bool) get_post_meta( $post->ID, 'nc_price_is_from', true );
        echo '<p><label><input type="checkbox" name="nc_manager_nc_price_is_from" value="1" ' . checked( $checked, true, false ) . '/> Wyświetlaj jako cenę „od”</label></p>';
    }

    private static function wysiwyg( int $post_id, string $key, string $label ): void {
        $value = (string) get_post_meta( $post_id, $key, true );
        echo '<div style="margin:22px 0">';
        echo '<label for="nc_manager_' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
        wp_editor(
            $value,
            'nc_manager_' . $key,
            array(
                'textarea_name' => 'nc_manager_' . $key,
                'textarea_rows' => 5,
                'media_buttons' => false,
            )
        );
        echo '</div>';
    }

    /** @param array<string, string> $options */
    private static function select( int $post_id, string $key, string $label, array $options ): void {
        $value = (string) get_post_meta( $post_id, $key, true );
        echo '<p><label for="nc_manager_' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label></p>';
        echo '<select name="nc_manager_' . esc_attr( $key ) . '" id="nc_manager_' . esc_attr( $key ) . '">';
        foreach ( $options as $option => $caption ) {
            echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $caption ) . '</option>';
        }
        echo '</select>';
    }

    private static function relation( int $post_id, string $key, string $label, string $target ): void {
        $selected = (array) get_post_meta( $post_id, $key, true );
        $selected = array_map( 'absint', $selected );
        $posts    = get_posts(
            array(
                'post_type'      => $target,
                'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
                'posts_per_page' => 200,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        echo '<p><label for="nc_manager_' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label></p>';
        echo '<select multiple size="5" style="width:100%" id="nc_manager_' . esc_attr( $key ) . '" name="nc_manager_' . esc_attr( $key ) . '[]">';
        foreach ( $posts as $candidate ) {
            echo '<option value="' . esc_attr( (string) $candidate->ID ) . '" ' . selected( in_array( $candidate->ID, $selected, true ), true, false ) . '>';
            echo esc_html( $candidate->post_title ) . '</option>';
        }
        echo '</select>';
    }

    public static function save( int $post_id, WP_Post $post ): void {
        if ( ! isset( Content_Types::types()[ $post->post_type ] ) ) {
            return;
        }
        if ( ! isset( $_POST['nc_manager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nc_manager_nonce'] ) ), 'nc_manager_save' ) ) {
            return;
        }
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array();
        if ( 'nc_pathway' === $post->post_type ) {
            $fields = Metadata::PATHWAY_FIELDS;
        } elseif ( 'nc_method' === $post->post_type ) {
            $fields = Metadata::METHOD_FIELDS;
        }

        foreach ( array_keys( $fields ) as $key ) {
            $input = 'nc_manager_' . $key;
            if ( isset( $_POST[ $input ] ) && is_string( $_POST[ $input ] ) ) {
                update_post_meta( $post_id, $key, wp_kses_post( wp_unslash( $_POST[ $input ] ) ) );
            }
        }

        if ( 'nc_pathway' === $post->post_type ) {
            foreach ( array( 'nc_physician_ids' => 'nc_physician', 'nc_method_ids' => 'nc_method', 'nc_service_ids' => 'nc_service' ) as $key => $target ) {
                $input  = 'nc_manager_' . $key;
                $ids    = isset( $_POST[ $input ] ) && is_array( $_POST[ $input ] ) ? Metadata::sanitize_ids( wp_unslash( $_POST[ $input ] ) ) : array();
                $valid  = array();
                foreach ( $ids as $id ) {
                    if ( $target === get_post_type( $id ) ) {
                        $valid[] = $id;
                    }
                }
                update_post_meta( $post_id, $key, $valid );
            }
        }

        if ( 'nc_method' === $post->post_type ) {
            foreach (
                array(
                    'nc_method_status' => array( '', 'standard', 'medical_device', 'experimental', 'clinical_trial', 'hospital_exemption' ),
                    'nc_evidence_level' => array( '', 'high', 'limited', 'preliminary' ),
                ) as $key => $allowed
            ) {
                $input = 'nc_manager_' . $key;
                if ( isset( $_POST[ $input ] ) && is_string( $_POST[ $input ] ) ) {
                    $value = sanitize_key( wp_unslash( $_POST[ $input ] ) );
                    update_post_meta( $post_id, $key, in_array( $value, $allowed, true ) ? $value : '' );
                }
            }
        }

        if ( 'nc_service' === $post->post_type ) {
            foreach ( array( 'nc_price_pln_minor', 'nc_price_eur_minor' ) as $key ) {
                $input = 'nc_manager_' . $key;
                if ( ! isset( $_POST[ $input ] ) || ! is_string( $_POST[ $input ] ) ) {
                    continue;
                }
                $raw = trim( wp_unslash( $_POST[ $input ] ) );
                if ( '' === $raw ) {
                    delete_post_meta( $post_id, $key );
                } elseif ( preg_match( '/^([0-9]+)(?:[,.]([0-9]{1,2}))?$/', $raw, $parts ) ) {
                    // Unikamy niedokładności zmiennoprzecinkowych przy obliczeniach cen.
                    $minor = (int) $parts[1] * 100 + (int) str_pad( $parts[2] ?? '0', 2, '0' );
                    update_post_meta( $post_id, $key, $minor );
                }
            }
            update_post_meta( $post_id, 'nc_price_is_from', isset( $_POST['nc_manager_nc_price_is_from'] ) );
        }
    }
}
