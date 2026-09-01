<?php
/**
 * Qalam LMS 0.9.0 responsive UI + unified question type compatibility layer.
 * Keeps Tutor persistence/rendering intact and only normalizes Qalam-facing UX.
 */
defined( 'ABSPATH' ) || exit;

/** Canonical question type registry sourced from Tutor itself. */
function qalam_090_question_type_registry(): array {
    $labels = qalam_060_question_type_labels();
    $native = class_exists( '\Tutor\Models\QuizModel' ) ? \Tutor\Models\QuizModel::get_question_types() : array();
    $registry = array();
    foreach ( $native as $slug => $data ) {
        $registry[ $slug ] = array(
            'slug' => $slug,
            'label' => $labels[ $slug ] ?? wp_strip_all_tags( $data['name'] ?? $slug ),
            'is_pro' => ! empty( $data['is_pro'] ),
            'multi_correct' => 'multiple_choice' === $slug,
            'single_correct' => in_array( $slug, array( 'single_choice', 'true_false' ), true ),
            'needs_image' => in_array( $slug, array( 'image_matching','image_answering','draw_image','pin_image','puzzle' ), true ),
        );
    }
    return $registry;
}


/** Enforce the Qalam distinction: Single Choice = radio/one correct, Multiple Choice = checkbox/multi-correct. */
function qalam_090_normalize_question_data( array $data, array $input ): array {
    $type = sanitize_key( (string) ( $data['question_type'] ?? $input['question_type'] ?? '' ) );
    if ( ! in_array( $type, array( 'single_choice', 'multiple_choice' ), true ) ) { return $data; }
    $settings = maybe_unserialize( $data['question_settings'] ?? array() );
    $settings = is_array( $settings ) ? $settings : array();
    $settings['question_type'] = $type;
    $settings['has_multiple_correct_answer'] = 'multiple_choice' === $type ? '1' : '0';
    $data['question_settings'] = maybe_serialize( $settings );
    return $data;
}
add_filter( 'tutor_quiz_question_data', 'qalam_090_normalize_question_data', PHP_INT_MAX, 2 );

/** Front/admin body markers used by the scoped design system. */
function qalam_090_body_class( array $classes ): array {
    if ( is_admin() ) { return $classes; }
    $classes[] = 'qalam-responsive-ui';
    if ( is_singular( 'tutor_quiz' ) || ( isset( $_GET['qalam_general_quiz'] ) || isset( $_GET['qalam_question_preview'] ) ) ) {
        $classes[] = 'qalam-quiz-shell';
    }
    return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'qalam_090_body_class', PHP_INT_MAX );

function qalam_090_admin_body_class( string $classes ): string {
    $page = sanitize_key( (string) ( $_GET['page'] ?? '' ) );
    if ( 0 === strpos( $page, 'qalam-' ) || 0 === strpos( $page, 'tutor' ) || 'create-course' === $page ) {
        $classes .= ' qalam-admin-shell qalam-responsive-ui';
    }
    return $classes;
}
add_filter( 'admin_body_class', 'qalam_090_admin_body_class', PHP_INT_MAX );

/** Load the unified responsive layer last so it resolves legacy-vs-v4 Tutor renderer collisions. */
function qalam_090_assets(): void {
    $base = plugin_dir_url( TUTOR_FILE );
    if ( is_admin() ) {
        $page = sanitize_key( (string) ( $_GET['page'] ?? '' ) );
        if ( 0 === strpos( $page, 'qalam-' ) || 0 === strpos( $page, 'tutor' ) || 'create-course' === $page ) {
            wp_enqueue_style( 'qalam-090-responsive', $base . 'assets/css/qalam-090-responsive.css', array( 'qalam-lms-brand' ), QALAM_LMS_UI_VERSION );
        }
        return;
    }
    wp_enqueue_style( 'qalam-090-responsive', $base . 'assets/css/qalam-090-responsive.css', array( 'qalam-lms-student' ), QALAM_LMS_UI_VERSION );
    wp_enqueue_script( 'qalam-090-responsive', $base . 'assets/js/qalam-090-responsive.js', array(), QALAM_LMS_UI_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'qalam_090_assets', PHP_INT_MAX );
add_action( 'wp_enqueue_scripts', 'qalam_090_assets', PHP_INT_MAX );
