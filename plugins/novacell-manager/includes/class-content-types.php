<?php
/** Wewnętrzne struktury treści NOVACELL. @package NovaCellManager */
namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Content_Types {
    public static function types(): array {
        return array(
            'nc_problem'   => 'Problemy pacjentów',
            'nc_document'  => 'Podstrony i opisy',
            'nc_case'      => 'Przypadki z praktyki',
            'nc_pathway'   => 'Ścieżki opieki',
            'nc_method'    => 'Metody i terapie',
            'nc_physician' => 'Lekarze i zespół',
            'nc_service'   => 'Pozycje cennika',
            'nc_package'   => 'Pakiety',
            'nc_faq'       => 'Pytania i odpowiedzi',
        );
    }

    public static function register(): void {
        foreach ( self::types() as $type => $label ) {
            register_post_type( $type, array(
                'labels'              => array(
                    'name'          => $label,
                    'singular_name' => $label,
                    'menu_name'     => $label,
                    'all_items'     => $label,
                    'add_new_item'  => 'Dodaj: ' . $label,
                    'edit_item'     => 'Edytuj: ' . $label,
                ),
                'public'              => false,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'show_ui'             => true,
                'show_in_menu'        => 'novacell-manager',
                'show_in_rest'        => false,
                'has_archive'         => false,
                'rewrite'             => false,
                'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
                'capability_type'     => array( 'nc_record', 'nc_records' ),
                'map_meta_cap'        => true,
                'delete_with_user'    => false,
            ) );
        }
        register_taxonomy( 'nc_problem_group', array( 'nc_problem', 'nc_pathway' ), array(
            'label'             => 'Grupy problemów',
            'public'            => false,
            'show_ui'           => true,
            'show_in_rest'      => false,
            'show_admin_column' => true,
            'hierarchical'      => true,
            'rewrite'           => false,
            'capabilities'      => array( 'manage_terms' => 'manage_options', 'edit_terms' => 'manage_options', 'delete_terms' => 'manage_options', 'assign_terms' => 'edit_nc_records' ),
        ) );
        register_taxonomy( 'nc_method_group', array( 'nc_method' ), array(
            'label'             => 'Grupy metod',
            'public'            => false,
            'show_ui'           => true,
            'show_in_rest'      => false,
            'show_admin_column' => true,
            'hierarchical'      => true,
            'rewrite'           => false,
            'capabilities'      => array( 'manage_terms' => 'manage_options', 'edit_terms' => 'manage_options', 'delete_terms' => 'manage_options', 'assign_terms' => 'edit_nc_records' ),
        ) );
    }
}
