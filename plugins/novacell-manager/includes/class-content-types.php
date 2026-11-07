<?php
/**
 * Wewnętrzne, strukturalne typy treści NOVACELL.
 *
 * @package NovaCellManager
 */

namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Content_Types {
    /**
     * Typy wewnętrzne, niewidoczne bezpośrednio na publicznym froncie.
     * Publikowanie stron opierać się będzie na zatwierdzonym szablonie i resolverze URL.
     *
     * @return array<string, string>
     */
    public static function types(): array {
        return array(
            'nc_problem'   => 'Problemy pacjentów',
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
            register_post_type(
                $type,
                array(
                    'labels'               => array(
                        'name'          => $label,
                        'singular_name' => $label,
                        'add_new_item'  => 'Dodaj: ' . $label,
                        'edit_item'     => 'Edytuj: ' . $label,
                        'all_items'     => $label,
                        'menu_name'     => $label,
                    ),
                    'public'               => false,
                    'publicly_queryable'   => false,
                    'exclude_from_search'  => true,
                    'show_ui'              => true,
                    'show_in_menu'         => 'novacell-manager',
                    // Brak automatycznego publicznego API do czasu dodania kontroli dostępu.
                    'show_in_rest'         => false,
                    'has_archive'          => false,
                    'rewrite'              => false,
                    'supports'             => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
                    'map_meta_cap'         => true,
                    'delete_with_user'     => false,
                )
            );
        }

        register_taxonomy(
            'nc_problem_group',
            array( 'nc_problem', 'nc_pathway' ),
            array(
                'label'             => 'Grupy problemów',
                'public'            => false,
                'show_ui'           => true,
                'show_in_rest'      => false,
                'show_admin_column' => true,
                'hierarchical'      => true,
                'rewrite'           => false,
            )
        );
        register_taxonomy(
            'nc_method_group',
            array( 'nc_method' ),
            array(
                'label'             => 'Grupy metod',
                'public'            => false,
                'show_ui'           => true,
                'show_in_rest'      => false,
                'show_admin_column' => true,
                'hierarchical'      => true,
                'rewrite'           => false,
            )
        );
    }
}
