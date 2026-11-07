<?php
/** Zakładki PL/EN dla wszystkich rejestrów strukturalnych. @package NovaCellManager */
namespace NovaCell\Manager;
defined( 'ABSPATH' ) || exit;

final class Bilingual {
    public static function boot(): void {
        add_action( 'add_meta_boxes', array( self::class, 'metaboxes' ) );
        add_action( 'save_post', array( self::class, 'save' ), 20, 2 );
    }
    public static function metaboxes(): void {
        foreach ( array_keys( Content_Types::types() ) as $type ) {
            add_meta_box( 'nc_bilingual', 'Języki: Polski | English', array( self::class, 'render' ), $type, 'normal', 'default' );
        }
    }
    public static function render( \WP_Post $post ): void {
        wp_nonce_field( 'nc_bilingual_save', 'nc_bilingual_nonce' );
        $en_title = (string) get_post_meta( $post->ID, 'nc_en_title', true );
        $en_excerpt = (string) get_post_meta( $post->ID, 'nc_en_excerpt', true );
        $en_body = (string) get_post_meta( $post->ID, 'nc_en_body', true );
        $pl_fields = array( 'title' => $post->post_title, 'excerpt' => $post->post_excerpt );
        echo '<div class="nc-lang-tabs" data-nc-lang-tabs="1">';
        echo '<div role="tablist" aria-label="Język treści" style="display:flex;gap:8px;padding:12px 0">';
        echo '<button type="button" class="button button-primary" data-nc-tab="pl" role="tab" aria-selected="true">Język Polski</button>';
        echo '<button type="button" class="button" data-nc-tab="en" role="tab" aria-selected="false">Język Angielski</button></div>';
        echo '<div data-nc-panel="pl" role="tabpanel"><p>Polski tytuł i główną treść edytujesz w standardowych polach wpisu powyżej. Edytor ścieżki oferuje również osobne sekcje WYSIWYG.</p>';
        echo '<p><strong>' . esc_html( $pl_fields['title'] ) . '</strong></p><p>' . esc_html( $pl_fields['excerpt'] ) . '</p></div>';
        echo '<div data-nc-panel="en" role="tabpanel" hidden>';
        echo '<p><label for="nc_en_title"><strong>English title</strong></label></p>';
        echo '<input class="widefat" type="text" id="nc_en_title" name="nc_en_title" value="' . esc_attr( $en_title ) . '">';
        echo '<p><label for="nc_en_excerpt"><strong>English lead</strong></label></p>';
        echo '<textarea class="widefat" rows="4" id="nc_en_excerpt" name="nc_en_excerpt">' . esc_textarea( $en_excerpt ) . '</textarea>';
        echo '<p><strong>English content (WYSIWYG)</strong></p>';
        wp_editor( $en_body, 'nc_en_body_editor_' . $post->ID, array( 'textarea_name' => 'nc_en_body', 'textarea_rows' => 5, 'media_buttons' => false ) );
        if ( 'nc_pathway' === $post->post_type || 'nc_method' === $post->post_type ) {
            $fields = 'nc_pathway' === $post->post_type ? Metadata::PATHWAY_FIELDS : Metadata::METHOD_FIELDS;
            foreach ( $fields as $key => $label ) {
                $trans_key = 'nc_en_' . substr( $key, 3 );
                echo '<p><label for="' . esc_attr( $trans_key ) . '"><strong>' . esc_html( $label ) . ' (EN)</strong></label></p>';
                wp_editor( (string) get_post_meta( $post->ID, $trans_key, true ), $trans_key . '_' . $post->ID, array( 'textarea_name' => $trans_key, 'textarea_rows' => 4, 'media_buttons' => false ) );
            }
        }
        echo '</div></div>';
        self::scripts();
    }
    private static function scripts(): void {
        static $done = false;
        if ( $done ) { return; }
        $done = true;
        echo '<script>document.addEventListener("click",function(e){let b=e.target.closest("[data-nc-tab]");if(!b)return;let root=b.closest("[data-nc-lang-tabs]");if(!root)return;root.querySelectorAll("[data-nc-tab]").forEach(function(t){let on=t===b;t.setAttribute("aria-selected",on?"true":"false");t.classList.toggle("button-primary",on);});root.querySelectorAll("[data-nc-panel]").forEach(function(p){p.hidden=p.dataset.ncPanel!==b.dataset.ncTab;});});</script>';
    }
    public static function save( int $id, \WP_Post $post ): void {
        if ( ! isset( Content_Types::types()[ $post->post_type ] ) || ! isset( $_POST['nc_bilingual_nonce'] ) || ! is_string( $_POST['nc_bilingual_nonce'] ) ) { return; }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nc_bilingual_nonce'] ) ), 'nc_bilingual_save' ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) { return; }
        foreach ( array( 'nc_en_title', 'nc_en_excerpt', 'nc_en_body' ) as $key ) {
            if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) { continue; }
            $value = wp_unslash( $_POST[ $key ] );
            update_post_meta( $id, $key, 'nc_en_body' === $key ? wp_kses_post( $value ) : sanitize_text_field( $value ) );
        }
        $fields = 'nc_pathway' === $post->post_type ? Metadata::PATHWAY_FIELDS : ( 'nc_method' === $post->post_type ? Metadata::METHOD_FIELDS : array() );
        foreach ( $fields as $key => $caption ) {
            $trans_key = 'nc_en_' . substr( $key, 3 );
            if ( isset( $_POST[ $trans_key ] ) && is_string( $_POST[ $trans_key ] ) ) {
                update_post_meta( $id, $trans_key, wp_kses_post( wp_unslash( $_POST[ $trans_key ] ) ) );
            }
        }
    }
}
