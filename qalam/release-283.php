<?php
/**
 * Qalam LMS 0.28.6 — media separation + public content hygiene.
 *
 * - Adds a dedicated About-section image independent from Hero/teacher imagery.
 * - Keeps public design data backward-compatible through wp_parse_args defaults.
 * - Bumps the schema marker for this visual/branding correction.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_283_VERSION = '0.28.6';
const QALAM_283_SCHEMA_OPTION = 'qalam_283_schema';
const QALAM_283_SCHEMA_VALUE = '1';

function qalam_283_schema_once(): void {
    if ( get_option( QALAM_283_SCHEMA_OPTION ) !== QALAM_283_SCHEMA_VALUE ) {
        update_option( QALAM_283_SCHEMA_OPTION, QALAM_283_SCHEMA_VALUE, false );
    }
}
add_action( 'init', 'qalam_283_schema_once', 9 );
