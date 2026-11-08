<?php
/**
 * Wspólny edytor WYSIWYG: każde pole ma zakładki Język Polski | Język Angielski.
 * Do zagnieżdżonych edytorów używamy stabilnego kontenera edit_form_advanced,
 * zamiast przenosić instancje TinyMCE pomiędzy metaboxami.
 * @package NovaCellManager
 */
namespace NovaCell\Manager;
defined( 'ABSPATH' ) || exit;

final class Language_Editor {
    public static function boot(): void {
        add_action( 'edit_form_advanced', array( self::class, 'render' ), 9 );
        add_action( 'save_post', array( self::class, 'save' ), 30, 2 );
        add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
    }

    public static function enqueue(): void {
        global $post;
        if ( ! $post instanceof \WP_Post || ! isset( Content_Types::types()[ $post->post_type ] ) ) { return; }
        wp_enqueue_style( 'nc-language-editor', plugins_url( 'assets/language-editor.css', NC_MANAGER_FILE ), array(), NC_MANAGER_VERSION );
        wp_enqueue_script( 'nc-language-editor', plugins_url( 'assets/language-editor.js', NC_MANAGER_FILE ), array(), NC_MANAGER_VERSION, true );
    }

    private static function fields( string $type ): array {
        $fields = array( 'body' => 'Opis główny', 'lead' => 'Wprowadzenie / lead' );
        if ( 'nc_pathway' === $type ) {
            foreach ( Metadata::PATHWAY_FIELDS as $key => $title ) {
                if ( 'nc_lead' === $key ) { continue; }
                $fields[ substr( $key, 3 ) ] = $title;
            }
        } elseif ( 'nc_method' === $type ) {
            foreach ( Metadata::METHOD_FIELDS as $key => $title ) { $fields[ substr( $key, 3 ) ] = $title; }
        }
        return $fields;
    }

    public static function render( \WP_Post $post ): void {
        if ( ! isset( Content_Types::types()[ $post->post_type ] ) ) { return; }
        wp_nonce_field( 'nc_language_save', 'nc_language_nonce' );
        echo '<div class="nc-langeditor"><h2>Treści NOVACELL w dwóch językach</h2>';
        echo '<p>Polski tytuł rekordu edytujesz w polu u góry. Każde pole tekstowe ma własny edytor WYSIWYG i przełącznik języka. Treści angielskie wymagają sprawdzenia terminologii medycznej przed publikacją.</p>';
        if ( 'requires_review' === (string) get_post_meta( $post->ID, '_nc_editorial_status', true ) ) {
            echo '<p class="notice notice-warning inline"><strong>Wymaga zatwierdzenia medycznego lub prawnego.</strong> Status WordPress „Opublikowano” nie stanowi zatwierdzenia klinicznego.</p>';
        }
        echo '<div class="nc-langeditor__title"><label for="nc_en_title"><strong>English title</strong></label>';
        echo '<input class="widefat" type="text" name="nc_lang[en_title]" id="nc_en_title" value="' . esc_attr( (string) get_post_meta( $post->ID, 'nc_en_title', true ) ) . '"></div>';
        foreach ( self::fields( $post->post_type ) as $field => $title ) {
            $name = 'nc_lang[' . $field . ']';
            $pl = 'body' === $field ? $post->post_content : ( 'lead' === $field ? $post->post_excerpt : (string) get_post_meta( $post->ID, 'nc_' . $field, true ) );
            $en = (string) get_post_meta( $post->ID, 'nc_en_' . ( 'lead' === $field ? 'excerpt' : $field ), true );
            $group = 'nc_field_' . $post->ID . '_' . $field;
            echo '<section class="nc-langfield" data-nc-lang-field><h3>' . esc_html( $title ) . '</h3>';
            echo '<div class="nc-langfield__tabs" role="tablist" aria-label="' . esc_attr( $title . ': język' ) . '">';
            foreach ( array('pl'=>'Język Polski','en'=>'Język Angielski') as $lang => $label ) {
                echo '<button class="button nc-langfield__tab" type="button" role="tab" id="' . esc_attr( $group . '_tab_' . $lang ) . '" aria-controls="' . esc_attr( $group . '_panel_' . $lang ) . '" data-lang="' . esc_attr( $lang ) . '" aria-selected="' . ('pl' === $lang ? 'true':'false') . '" tabindex="' . ('pl' === $lang ? '0':'-1') . '">' . esc_html( $label ) . '</button>';
            }
            echo '</div>';
            foreach ( array( 'pl' => $pl, 'en' => $en ) as $lang => $value ) {
                echo '<div class="nc-langfield__panel" role="tabpanel" id="' . esc_attr( $group . '_panel_' . $lang ) . '" aria-labelledby="' . esc_attr( $group . '_tab_' . $lang ) . '" data-lang-panel="' . esc_attr( $lang ) . '"' . ('en' === $lang ? ' hidden':'') . '>';
                wp_editor( (string) $value, 'nc_lang_' . $post->ID . '_' . $field . '_' . $lang, array(
                    'textarea_name' => $name . '[' . $lang . ']',
                    'textarea_rows' => 'body' === $field ? 16 : 5,
                    'media_buttons' => false,
                    'quicktags' => true,
                    'teeny' => true,
                ) );
                echo '</div>';
            }
            echo '</section>';
        }
        echo '</div>';
    }

    public static function save( int $id, \WP_Post $post ): void {
        static $saving = false;
        if ( $saving || ! isset( Content_Types::types()[ $post->post_type ] ) ) { return; }
        if ( ! isset( $_POST['nc_language_nonce'] ) || ! is_string( $_POST['nc_language_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nc_language_nonce'] ) ), 'nc_language_save' ) ||
            ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ||
            ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) { return; }
        if ( ! isset( $_POST['nc_lang'] ) || ! is_array( $_POST['nc_lang'] ) ) { return; }
        $submitted = wp_unslash( $_POST['nc_lang'] );
        $material_change = false;
        if ( isset( $submitted['en_title'] ) && is_string( $submitted['en_title'] ) ) {
            $new_title = sanitize_text_field( $submitted['en_title'] );
            if ( $new_title !== (string) get_post_meta( $id, 'nc_en_title', true ) ) {
                $material_change = true;
                update_post_meta( $id, 'nc_en_title', $new_title );
            }
        }
        $updated = array('ID'=>$id);
        foreach ( self::fields( $post->post_type ) as $field => $title ) {
            if ( ! isset( $submitted[ $field ] ) || ! is_array( $submitted[ $field ] ) ) { continue; }
            foreach ( array( 'pl', 'en' ) as $lang ) {
                if ( ! isset( $submitted[ $field ][ $lang ] ) || ! is_string( $submitted[ $field ][ $lang ] ) ) { continue; }
                $value = wp_kses_post( $submitted[ $field ][ $lang ] );
                if ( 'pl' === $lang && 'body' === $field ) {
                    if ( $value !== $post->post_content ) {
                        $updated['post_content'] = $value;
                        $material_change = true;
                    }
                } elseif ( 'pl' === $lang && 'lead' === $field ) {
                    $new_lead = sanitize_textarea_field( wp_strip_all_tags( $value ) );
                    if ( $new_lead !== $post->post_excerpt ) {
                        $updated['post_excerpt'] = $new_lead;
                        $material_change = true;
                    }
                } else {
                    $key = 'nc_' . ( 'en' === $lang ? 'en_' : '' ) . ( 'lead' === $field && 'en' === $lang ? 'excerpt' : $field );
                    $new_value = 'lead' === $field ? sanitize_textarea_field( wp_strip_all_tags( $value ) ) : $value;
                    if ( $new_value !== (string) get_post_meta( $id, $key, true ) ) {
                        $material_change = true;
                        update_post_meta( $id, $key, $new_value );
                    }
                }
            }
        }
        if ( isset( $updated['post_content'] ) || isset( $updated['post_excerpt'] ) ) {
            $saving = true;
            wp_update_post( wp_slash( $updated ) );
            $saving = false;
        }
        if ( $material_change ) {
            // Any substantive change invalidates the prior record-level medical approval.
            update_post_meta( $id, '_nc_editorial_status', 'requires_review' );
            // Manual changes must never be silently overwritten by the next source import.
            delete_post_meta( $id, '_nc_full_import_hash' );
        }
    }
}
