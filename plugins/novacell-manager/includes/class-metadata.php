<?php
/**
 * Rejestr metadanych i schematy wartości.
 *
 * @package NovaCellManager
 */

namespace NovaCell\Manager;

defined( 'ABSPATH' ) || exit;

final class Metadata {
    public const PATHWAY_FIELDS = array(
        'nc_lead'         => 'Wprowadzenie',
        'nc_symptoms'     => 'Objawy lub wskazania',
        'nc_causes'       => 'Możliwe przyczyny',
        'nc_first_visit'  => 'Pierwsza wizyta',
        'nc_diagnostics'  => 'Diagnostyka',
        'nc_locations'    => 'Miejsce badań i zabiegów',
        'nc_risks'        => 'Ograniczenia i ryzyka',
        'nc_preparation'  => 'Przygotowanie do wizyty',
    );

    public const METHOD_FIELDS = array(
        'nc_method_indications'     => 'Wskazania',
        'nc_method_contraindications' => 'Przeciwwskazania',
        'nc_method_process'         => 'Przebieg',
        'nc_method_risks'           => 'Ryzyka i ograniczenia',
        'nc_method_sources'         => 'Źródła i wytyczne',
    );

    public static function register(): void {
        foreach ( self::PATHWAY_FIELDS as $key => $label ) {
            self::register_html( 'nc_pathway', $key );
        }
        foreach ( self::METHOD_FIELDS as $key => $label ) {
            self::register_html( 'nc_method', $key );
        }

        foreach ( array( 'nc_physician_ids' => 'nc_physician', 'nc_method_ids' => 'nc_method', 'nc_service_ids' => 'nc_service' ) as $key => $target ) {
            register_post_meta(
                'nc_pathway',
                $key,
                array(
                    'type'              => 'array',
                    'single'            => true,
                    'show_in_rest'      => false,
                    'default'           => array(),
                    'sanitize_callback' => array( self::class, 'sanitize_ids' ),
                )
            );
        }

        foreach ( array( 'nc_method_status', 'nc_evidence_level' ) as $key ) {
            register_post_meta( 'nc_method', $key, array( 'type' => 'string', 'single' => true, 'show_in_rest' => false ) );
        }

        foreach ( array( 'nc_price_pln_minor', 'nc_price_eur_minor' ) as $key ) {
            register_post_meta( 'nc_service', $key, array( 'type' => 'integer', 'single' => true, 'show_in_rest' => false ) );
        }
        register_post_meta( 'nc_service', 'nc_price_is_from', array( 'type' => 'boolean', 'single' => true, 'show_in_rest' => false ) );
    }

    private static function register_html( string $type, string $key ): void {
        register_post_meta(
            $type,
            $key,
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => false,
                'sanitize_callback' => 'wp_kses_post',
            )
        );
    }

    /** @param mixed $value */
    public static function sanitize_ids( $value ): array {
        if ( ! is_array( $value ) ) {
            return array();
        }
        $values = array_map( 'absint', $value );
        return array_values( array_unique( array_filter( $values ) ) );
    }
}
