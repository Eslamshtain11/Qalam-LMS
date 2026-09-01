<?php
/**
 * Qalam LMS 0.10.0 — full visual redesign and canonical Single Choice exposure.
 * Functional Tutor persistence remains intact; this release replaces Qalam's visual layer.
 */
defined( 'ABSPATH' ) || exit;

/** Force Single Choice into the canonical Tutor question registry in a stable position. */
function qalam_100_force_single_choice( array $types ): array {
    if ( ! isset( $types['single_choice'] ) ) {
        $types['single_choice'] = array(
            'name'   => __( 'Single Choice', 'tutor' ),
            'icon'   => '<span class="tooltip-btn"><i class="tutor-quiz-type-icon tutor-quiz-type-single-choice tutor-icon-mark"></i></span>',
            'is_pro' => false,
        );
    }
    $ordered = array();
    foreach ( $types as $slug => $data ) {
        if ( 'multiple_choice' === $slug && isset( $types['single_choice'] ) && ! isset( $ordered['single_choice'] ) ) {
            $ordered['single_choice'] = $types['single_choice'];
        }
        if ( 'single_choice' !== $slug ) { $ordered[$slug] = $data; }
    }
    if ( ! isset( $ordered['single_choice'] ) ) { $ordered['single_choice'] = $types['single_choice']; }
    return $ordered;
}
add_filter( 'tutor_get_question_types', 'qalam_100_force_single_choice', PHP_INT_MAX );

/** Ensure the semantics survive every save path. */
function qalam_100_single_choice_semantics( array $data, array $input ): array {
    $type = sanitize_key( (string) ( $data['question_type'] ?? $input['question_type'] ?? '' ) );
    if ( ! in_array( $type, array( 'single_choice', 'multiple_choice' ), true ) ) { return $data; }
    $settings = maybe_unserialize( $data['question_settings'] ?? array() );
    $settings = is_array( $settings ) ? $settings : array();
    $settings['question_type'] = $type;
    $settings['has_multiple_correct_answer'] = 'multiple_choice' === $type ? '1' : '0';
    $data['question_settings'] = maybe_serialize( $settings );
    return $data;
}
add_filter( 'tutor_quiz_question_data', 'qalam_100_single_choice_semantics', PHP_INT_MAX, 2 );

function qalam_100_body_class( array $classes ): array {
    if ( ! is_admin() ) { $classes[] = 'qalam-v1-ui'; }
    return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'qalam_100_body_class', PHP_INT_MAX );

function qalam_100_admin_body_class( string $classes ): string {
    $page = sanitize_key( (string) ( $_GET['page'] ?? '' ) );
    if ( 0 === strpos( $page, 'qalam-' ) || 0 === strpos( $page, 'tutor' ) || 'create-course' === $page ) {
        $classes .= ' qalam-v1-ui qalam-v1-admin';
    }
    return $classes;
}
add_filter( 'admin_body_class', 'qalam_100_admin_body_class', PHP_INT_MAX );

/** Load the full design system after every legacy/Qalam stylesheet. */
function qalam_100_enqueue_design(): void {
    $base = plugin_dir_url( TUTOR_FILE );
    wp_enqueue_style( 'qalam-100-design', $base . 'assets/css/qalam-100-design.css', array(), QALAM_LMS_UI_VERSION );
    wp_enqueue_script( 'qalam-100-ui', $base . 'assets/js/qalam-100-ui.js', array(), QALAM_LMS_UI_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'qalam_100_enqueue_design', PHP_INT_MAX );
add_action( 'admin_enqueue_scripts', function(): void {
    $page = sanitize_key( (string) ( $_GET['page'] ?? '' ) );
    if ( 0 === strpos( $page, 'qalam-' ) || 0 === strpos( $page, 'tutor' ) || 'create-course' === $page ) {
        qalam_100_enqueue_design();
    }
}, PHP_INT_MAX );
