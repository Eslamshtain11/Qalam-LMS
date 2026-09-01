<?php
/** Runtime mobile branding, entitlements and surface policy. */
defined( 'ABSPATH' ) || exit;

final class Qalam_Mobile_Experience {
    public static function platform_type(): string {
        if ( function_exists( 'qalam_290_platform_type' ) ) {
            return qalam_290_platform_type();
        }
        $brand = function_exists( 'qalam_230_brand' ) ? qalam_230_brand() : array();
        return 'individual' === (string) ( $brand['platform_type'] ?? '' ) ? 'individual' : 'academy';
    }

    public static function feature_enabled( string $key ): bool {
        $academy_only = array( 'student_analytics', 'instructor_marketplace', 'progress_reset', 'multi_instructor', 'advanced_reports', 'manual_enrollments' );
        if ( 'individual' === self::platform_type() && in_array( $key, $academy_only, true ) ) {
            return false;
        }
        if ( function_exists( 'qalam_290_platform_feature_allowed' ) && ! qalam_290_platform_feature_allowed( $key ) ) {
            return false;
        }
        return function_exists( 'qalam_feature_enabled' ) ? qalam_feature_enabled( $key ) : true;
    }

    public static function features(): array {
        $defs = function_exists( 'qalam_180_feature_definitions' ) ? qalam_180_feature_definitions() : array();
        $out = array();
        foreach ( array_keys( $defs ) as $key ) {
            $out[ sanitize_key( (string) $key ) ] = self::feature_enabled( (string) $key );
        }
        return $out;
    }

    public static function surfaces(): array {
        return array(
            'home'          => true,
            'courses'       => true,
            'quizzes'       => true,
            'assignments'   => self::feature_enabled( 'assignments' ),
            'certificates'  => self::feature_enabled( 'certificates' ),
            'announcements' => true,
            'notifications' => self::feature_enabled( 'notifications' ),
            'profile'       => true,
        );
    }

    private static function clean_url( $value ): string {
        $value = trim( (string) $value );
        return '' === $value ? '' : esc_url_raw( $value );
    }

    public static function branding(): array {
        $brand = function_exists( 'qalam_230_brand' ) ? qalam_230_brand() : array();
        $palette = array();
        if ( function_exists( 'qalam_230_palettes' ) ) {
            $palettes = qalam_230_palettes();
            $palette = isset( $palettes[ $brand['palette'] ?? '' ] ) ? $palettes[ $brand['palette'] ] : array();
        }
        $primary   = sanitize_hex_color( (string) ( $brand['custom_primary'] ?? '' ) ) ?: (string) ( $palette['primary'] ?? '' );
        $primary_2 = sanitize_hex_color( (string) ( $brand['custom_primary_2'] ?? '' ) ) ?: (string) ( $palette['primary_2'] ?? '' );
        $accent    = sanitize_hex_color( (string) ( $brand['custom_accent'] ?? '' ) ) ?: (string) ( $palette['accent'] ?? '' );
        $appearance = sanitize_key( (string) ( $brand['appearance_mode'] ?? 'system' ) );
        if ( ! in_array( $appearance, array( 'light', 'dark', 'system' ), true ) ) { $appearance = 'system'; }

        return array(
            'platform_name'     => sanitize_text_field( (string) ( $brand['platform_name'] ?? get_bloginfo( 'name' ) ) ),
            'teacher_name'      => sanitize_text_field( (string) ( $brand['teacher_name'] ?? '' ) ),
            'teacher_title'     => sanitize_text_field( (string) ( $brand['teacher_title'] ?? '' ) ),
            'tagline'           => sanitize_text_field( (string) ( $brand['tagline'] ?? '' ) ),
            'hero_title'        => sanitize_text_field( (string) ( $brand['hero_title'] ?? '' ) ),
            'hero_text'         => sanitize_textarea_field( (string) ( $brand['hero_text'] ?? '' ) ),
            'teacher_bio'       => sanitize_textarea_field( (string) ( $brand['teacher_bio'] ?? '' ) ),
            'courses_title'     => sanitize_text_field( (string) ( $brand['courses_title'] ?? '' ) ),
            'about_title'       => sanitize_text_field( (string) ( $brand['about_title'] ?? '' ) ),
            'logo_url'          => self::clean_url( $brand['logo_url'] ?? '' ),
            'hero_image_url'    => self::clean_url( $brand['hero_image_url'] ?? '' ),
            'teacher_image_url' => self::clean_url( $brand['teacher_image_url'] ?? '' ),
            'about_image_url'   => self::clean_url( $brand['about_image_url'] ?? '' ),
            'youtube'           => self::clean_url( $brand['youtube'] ?? '' ),
            'facebook'          => self::clean_url( $brand['facebook'] ?? '' ),
            'instagram'         => self::clean_url( $brand['instagram'] ?? '' ),
            'telegram'          => self::clean_url( $brand['telegram'] ?? '' ),
            'phone'             => sanitize_text_field( (string) ( $brand['phone'] ?? '' ) ),
            'whatsapp'          => preg_replace( '/[^0-9+]/', '', (string) ( $brand['whatsapp'] ?? '' ) ),
            'whatsapp_message'  => sanitize_textarea_field( (string) ( $brand['whatsapp_message'] ?? '' ) ),
            'email'             => sanitize_email( (string) ( $brand['email'] ?? get_option( 'admin_email', '' ) ) ),
            'palette'           => sanitize_key( (string) ( $brand['palette'] ?? 'royal-purple' ) ),
            'appearance_mode'   => $appearance,
            'custom_primary'    => $primary,
            'custom_primary_2'  => $primary_2,
            'custom_accent'     => $accent,
            // Stable aliases for non-mobile consumers already using the Cloud branding payload.
            'primary'           => $primary,
            'secondary'         => $primary_2,
            'accent'            => $accent,
        );
    }

    public static function config(): array {
        $manifest = function_exists( 'qalam_290_cached_manifest' ) ? qalam_290_cached_manifest() : array();
        $state = function_exists( 'qalam_290_state' ) ? qalam_290_state() : array();
        $branding = self::branding();
        return array(
            'version'                 => defined( 'QALAM_LMS_PRODUCT_VERSION' ) ? QALAM_LMS_PRODUCT_VERSION : 'unknown',
            'platform_type'           => self::platform_type(),
            'branding'                => $branding,
            'features'                => self::features(),
            'surfaces'                => self::surfaces(),
            'catalog_version'         => sanitize_text_field( (string) ( $manifest['catalog_version'] ?? $manifest['feature_catalog_version'] ?? '' ) ),
            'feature_catalog_version' => sanitize_text_field( (string) ( $manifest['feature_catalog_version'] ?? '' ) ),
            'subscription_status'     => sanitize_key( (string) ( $state['status'] ?? $manifest['subscription']['status'] ?? 'unknown' ) ),
            'support'                 => array(
                'phone'    => sanitize_text_field( (string) ( $branding['phone'] ?? '' ) ),
                'whatsapp' => sanitize_text_field( (string) ( $branding['whatsapp'] ?? '' ) ),
                'email'    => sanitize_email( (string) ( $branding['email'] ?? '' ) ),
            ),
            'generated_at'             => gmdate( 'c' ),
        );
    }
}
