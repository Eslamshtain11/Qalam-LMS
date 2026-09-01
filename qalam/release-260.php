<?php
/**
 * Qalam LMS 0.26.0 — internal surfaces + operational settings role matrix.
 *
 * Keeps Tutor/Qalam native engines and templates intact while applying the
 * managed Qalam public design system to student-facing internal surfaces.
 * Global LMS settings remain one source of truth and are exposed through
 * /qalam-admin/settings/ according to platform role.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_260_VERSION = '0.26.0-internal-surfaces-role-matrix';
const QALAM_260_SCHEMA_OPTION = 'qalam_260_schema';
const QALAM_260_SCHEMA_VALUE = '1';

/**
 * Tabs that are never customer-controlled. Design is owned by Qalam operators,
 * while maintenance/license/update tools remain WordPress maintenance concerns.
 */
function qalam_260_maintenance_setting_tab( string $tab ): bool {
    $tab = sanitize_key( $tab );
    if ( in_array( $tab, array( 'design', 'license', 'licenses', 'updates', 'update', 'tools', 'maintenance', 'system' ), true ) ) {
        return true;
    }
    return (bool) preg_match( '/(^|_)(license|update|maintenance|system_tools)(_|$)/', $tab );
}

/** Tabs with secrets, money movement, authentication or external-provider authority. */
function qalam_260_sensitive_setting_tab( string $tab ): bool {
    $tab = sanitize_key( $tab );
    $exact = array(
        'monetization',
        'ecommerce_payment',
        'ecommerce_tax',
        'authentication',
        'advanced',
        'pm-pro',
        'pmpro',
        'social_login',
        'webhooks',
        'api',
    );
    if ( in_array( $tab, $exact, true ) ) { return true; }
    return (bool) preg_match( '/(payment|gateway|monetiz|billing|subscription|tax|auth|security|secret|credential|api|webhook|integration|oauth|social)/', $tab );
}

/**
 * Operational settings role matrix.
 * - Qalam maintenance administrator: all operational tabs.
 * - Platform owner: all customer-operational tabs.
 * - Platform manager: day-to-day tabs, excluding sensitive authority.
 * - Instructor/student: no platform-global settings.
 */
function qalam_260_user_can_manage_settings_tab( string $tab, $user = null ): bool {
    $tab = sanitize_key( $tab );
    if ( '' === $tab || qalam_260_maintenance_setting_tab( $tab ) ) { return false; }

    $user = $user instanceof WP_User ? $user : wp_get_current_user();
    if ( ! $user || ! $user->exists() ) { return false; }

    if ( user_can( $user, 'manage_options' ) || ( defined( 'QALAM_230_DESIGN_CAP' ) && user_can( $user, QALAM_230_DESIGN_CAP ) ) ) {
        return true;
    }

    $roles = (array) $user->roles;
    if ( in_array( 'qalam_owner', $roles, true ) ) {
        return user_can( $user, 'qalam_manage_settings' );
    }
    if ( in_array( 'qalam_manager', $roles, true ) ) {
        if ( function_exists( 'qalam_270_manager_can_manage_settings_tab' ) ) {
            return user_can( $user, 'qalam_manage_settings' ) && qalam_270_manager_can_manage_settings_tab( $tab );
        }
        return user_can( $user, 'qalam_manage_settings' ) && ! qalam_260_sensitive_setting_tab( $tab );
    }
    return false;
}

function qalam_260_settings_access_label( string $tab ): string {
    if ( function_exists( 'qalam_270_manager_can_manage_settings_tab' ) && ! qalam_270_manager_can_manage_settings_tab( $tab ) ) { return 'مالك المنصة فقط'; }
    if ( qalam_260_sensitive_setting_tab( $tab ) ) { return 'مالك المنصة فقط'; }
    return 'المالك والمدير';
}

/** Identify the LMS surface so CSS can be precise instead of globally restyling WordPress. */
function qalam_260_public_surface_key(): string {
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) { return ''; }
    if ( function_exists( 'qalam_210_is_dashboard_request' ) && qalam_210_is_dashboard_request() ) { return ''; }
    if ( function_exists( 'qalam_240_is_login_surface' ) && qalam_240_is_login_surface() ) { return 'login'; }

    $post_type = get_post_type();
    if ( function_exists( 'tutor' ) ) {
        if ( is_post_type_archive( tutor()->course_post_type ) ) { return 'course-archive'; }
        if ( is_singular( tutor()->course_post_type ) ) { return 'course-single'; }
        if ( is_singular( tutor()->lesson_post_type ) ) { return 'learning'; }
        if ( is_singular( tutor()->quiz_post_type ) ) { return 'quiz'; }
        if ( is_singular( tutor()->assignment_post_type ) || 'tutor_assignments' === $post_type ) { return 'assignment'; }
        if ( in_array( $post_type, array( tutor()->zoom_post_type, tutor()->meet_post_type, 'tutor_zoom_meeting', 'tutor-google-meet' ), true ) ) { return 'live'; }
    }

    if ( function_exists( 'tutor_utils' ) ) {
        try {
            if ( tutor_utils()->is_tutor_dashboard() ) { return 'dashboard'; }
        } catch ( \Throwable $e ) {}
        $checkout_id = (int) tutor_utils()->get_option( 'tutor_checkout_page_id', 0 );
        if ( $checkout_id && is_page( $checkout_id ) ) { return 'checkout'; }
        $cart_id = (int) tutor_utils()->get_option( 'tutor_cart_page_id', 0 );
        if ( $cart_id && is_page( $cart_id ) ) { return 'cart'; }
        $student_register_id = (int) tutor_utils()->get_option( 'student_register_page', 0 );
        if ( $student_register_id && is_page( $student_register_id ) ) { return 'register'; }
        $instructor_register_id = (int) tutor_utils()->get_option( 'instructor_register_page', 0 );
        if ( $instructor_register_id && is_page( $instructor_register_id ) ) { return 'register'; }
    }

    if ( is_front_page() || is_home() ) { return 'home'; }
    return 'public';
}

function qalam_260_body_classes( array $classes ): array {
    $surface = qalam_260_public_surface_key();
    if ( '' !== $surface ) {
        $classes[] = 'qalam-internal-surface';
        $classes[] = 'qalam-surface-' . sanitize_html_class( $surface );
    }
    return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'qalam_260_body_classes', 30 );

/** Keep native Tutor colors aligned with the protected Design Studio palette. */
function qalam_260_native_palette_bridge(): void {
    if ( ! function_exists( 'qalam_240_is_public_surface' ) || ! qalam_240_is_public_surface() ) { return; }
    $palette = function_exists( 'qalam_240_palette' ) ? qalam_240_palette() : array();
    $primary = sanitize_hex_color( (string) ( $palette['primary'] ?? '#6d4aff' ) ) ?: '#6d4aff';
    $secondary = sanitize_hex_color( (string) ( $palette['primary_2'] ?? '#8b5cf6' ) ) ?: '#8b5cf6';
    wp_add_inline_style( 'qalam-reference-system', ':root{--tutor-color-primary:' . esc_attr( $primary ) . ';--tutor-color-primary-hover:' . esc_attr( $secondary ) . ';}' );
}
add_action( 'wp_enqueue_scripts', 'qalam_260_native_palette_bridge', 95 );


/** Pages rendered by WordPress content should still use the Qalam-owned document shell. */
function qalam_260_managed_page_ids(): array {
    if ( ! function_exists( 'tutor_utils' ) ) { return array(); }
    return array_values( array_filter( array_map( 'intval', array(
        tutor_utils()->get_option( 'tutor_checkout_page_id', 0 ),
        tutor_utils()->get_option( 'tutor_cart_page_id', 0 ),
        tutor_utils()->get_option( 'student_register_page', 0 ),
        tutor_utils()->get_option( 'instructor_register_page', 0 ),
    ) ) ) );
}

function qalam_260_managed_page_template( string $template ): string {
    if ( is_admin() || ! is_page() || ! in_array( (int) get_queried_object_id(), qalam_260_managed_page_ids(), true ) ) { return $template; }
    $managed = dirname( __DIR__ ) . '/templates/qalam-managed-page.php';
    return is_file( $managed ) ? $managed : $template;
}
add_filter( 'template_include', 'qalam_260_managed_page_template', 99 );

function qalam_260_schema_once(): void {
    if ( get_option( QALAM_260_SCHEMA_OPTION ) !== QALAM_260_SCHEMA_VALUE ) {
        update_option( QALAM_260_SCHEMA_OPTION, QALAM_260_SCHEMA_VALUE, false );
    }
}
add_action( 'init', 'qalam_260_schema_once', 7 );
