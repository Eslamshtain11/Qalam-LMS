<?php
/**
 * Qalam LMS 0.21.1 — standalone Qalam Admin dashboard closure.
 *
 * The WordPress dashboard remains a maintenance surface for Qalam operators only.
 * Platform owners/managers/instructors work from /qalam-admin/ and keep using the
 * mature Tutor/Qalam engines behind the scenes.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_210_VERSION       = '0.21.1-dashboard-closure';
const QALAM_210_SCHEMA_OPTION = 'qalam_210_schema';
const QALAM_210_SCHEMA_VALUE  = '2';

function qalam_210_dashboard_url( string $section = '', array $args = array() ): string {
    $base = home_url( '/qalam-admin/' );
    $section = sanitize_key( $section );
    $url = $section ? trailingslashit( $base . $section ) : $base;
    return $args ? add_query_arg( $args, $url ) : $url;
}

/** True when the current request is served by the standalone Qalam admin shell. */
function qalam_210_is_dashboard_request( string $section = '' ): bool {
    $current = sanitize_key( (string) get_query_var( 'qalam_admin' ) );
    if ( ! $current ) { return false; }
    return '' === $section || sanitize_key( $section ) === $current;
}

function qalam_210_managed_roles(): array {
    return array( 'qalam_owner', 'qalam_manager', 'tutor_instructor' );
}

function qalam_210_user_is_managed( $user = null ): bool {
    $user = $user instanceof WP_User ? $user : wp_get_current_user();
    if ( ! $user || ! $user->exists() ) { return false; }
    return (bool) array_intersect( qalam_210_managed_roles(), (array) $user->roles );
}

function qalam_210_user_is_platform_admin( $user = null ): bool {
    $user = $user instanceof WP_User ? $user : wp_get_current_user();
    if ( ! $user || ! $user->exists() ) { return false; }
    return (bool) array_intersect( array( 'qalam_owner', 'qalam_manager' ), (array) $user->roles );
}

function qalam_210_install_roles(): void {
    $base = array( 'read' => true, 'qalam_access_dashboard' => true );
    $owner_caps = array_merge( $base, array(
        'qalam_manage_courses'       => true,
        'qalam_manage_students'      => true,
        'qalam_manage_exams'         => true,
        'qalam_manage_question_bank' => true,
        'qalam_manage_addons'        => true,
        'qalam_manage_ai'            => true,
        'qalam_manage_reports'       => true,
        'qalam_manage_settings'      => true,
        'manage_tutor'               => true,
        'manage_tutor_instructor'    => true,
    ) );
    // Platform managers are full Qalam administrators, but never WordPress administrators.
    $manager_caps = $owner_caps;

    $owner = get_role( 'qalam_owner' );
    if ( ! $owner ) { add_role( 'qalam_owner', 'مالك المنصة', $owner_caps ); }
    else { foreach ( $owner_caps as $cap => $grant ) { $owner->add_cap( $cap, $grant ); } }

    $manager = get_role( 'qalam_manager' );
    if ( ! $manager ) { add_role( 'qalam_manager', 'مدير المنصة', $manager_caps ); }
    else { foreach ( $manager_caps as $cap => $grant ) { $manager->add_cap( $cap, $grant ); } }

    $instructor = get_role( 'tutor_instructor' );
    if ( $instructor ) {
        foreach ( array( 'qalam_access_dashboard','qalam_manage_courses','qalam_manage_exams','qalam_manage_question_bank','qalam_manage_ai' ) as $cap ) { $instructor->add_cap( $cap ); }
    }
    update_option( QALAM_210_SCHEMA_OPTION, QALAM_210_SCHEMA_VALUE, false );
}
add_action( 'init', 'qalam_210_install_roles', 2 );

/**
 * Give Qalam platform administrators the LMS capabilities needed by Tutor's
 * mature builders without ever granting WordPress administrator powers.
 */
function qalam_210_sync_lms_admin_caps(): void {
    $wp_admin = get_role( 'administrator' );
    $safe_tutor_caps = array( 'read' => true, 'upload_files' => true );
    if ( $wp_admin ) {
        foreach ( (array) $wp_admin->capabilities as $cap => $grant ) {
            if ( ! $grant ) { continue; }
            if ( 'manage_tutor' === $cap || 'manage_tutor_instructor' === $cap || false !== strpos( $cap, 'tutor_' ) ) {
                $safe_tutor_caps[ $cap ] = true;
            }
        }
    }
    $dangerous = array(
        'administrator','manage_options','install_plugins','activate_plugins','edit_plugins','delete_plugins','update_plugins',
        'install_themes','switch_themes','edit_themes','delete_themes','update_themes','update_core','edit_files',
        'manage_network','manage_network_options','manage_network_plugins','manage_network_themes','manage_network_users',
        'create_users','edit_users','delete_users','promote_users','list_users','unfiltered_html',
    );
    foreach ( array( 'qalam_owner', 'qalam_manager' ) as $role_name ) {
        $role = get_role( $role_name );
        if ( ! $role ) { continue; }
        foreach ( $safe_tutor_caps as $cap => $grant ) { $role->add_cap( $cap, $grant ); }
        foreach ( $dangerous as $cap ) { $role->remove_cap( $cap ); }
    }
}
add_action( 'init', 'qalam_210_sync_lms_admin_caps', 30 );

function qalam_210_register_routes(): void {
    add_rewrite_rule( '^qalam-admin/?$', 'index.php?qalam_admin=home', 'top' );
    add_rewrite_rule( '^qalam-admin/([^/]+)/?$', 'index.php?qalam_admin=$matches[1]', 'top' );
}
add_action( 'init', 'qalam_210_register_routes', 3 );

function qalam_210_query_vars( array $vars ): array { $vars[] = 'qalam_admin'; return $vars; }
add_filter( 'query_vars', 'qalam_210_query_vars' );

function qalam_210_flush_routes_once(): void {
    if ( get_option( 'qalam_210_routes_flushed' ) === QALAM_210_VERSION ) { return; }
    qalam_210_register_routes(); flush_rewrite_rules( false );
    update_option( 'qalam_210_routes_flushed', QALAM_210_VERSION, false );
}
add_action( 'init', 'qalam_210_flush_routes_once', 99 );

/** Managed Qalam accounts never receive wp-admin UI. Plumbing endpoints stay available. */
function qalam_210_block_wp_admin(): void {
    if ( ! qalam_210_user_is_managed() || wp_doing_ajax() || wp_doing_cron() ) { return; }
    $script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( (string) $_SERVER['SCRIPT_NAME'] ) : '';
    if ( in_array( $script, array( 'admin-ajax.php','admin-post.php','async-upload.php' ), true ) ) { return; }

    // Last-resort server-side guard for links generated by native/React code that
    // navigate directly instead of using a normal anchor. Preserve the requested
    // Tutor action inside Qalam when a 0.22 surface mapping exists.
    if ( function_exists( 'qalam_220_legacy_url_to_dashboard' ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
        $requested = home_url( (string) wp_unslash( $_SERVER['REQUEST_URI'] ) );
        $mapped = qalam_220_legacy_url_to_dashboard( $requested );
        if ( $mapped !== $requested ) { wp_safe_redirect( $mapped ); exit; }
    }
    wp_safe_redirect( qalam_210_dashboard_url() ); exit;
}
add_action( 'admin_init', 'qalam_210_block_wp_admin', 0 );

function qalam_210_hide_admin_bar( bool $show ): bool { return qalam_210_user_is_managed() ? false : $show; }
add_filter( 'show_admin_bar', 'qalam_210_hide_admin_bar', 100 );

function qalam_210_login_redirect( string $redirect_to, string $requested, $user ): string {
    return $user instanceof WP_User && qalam_210_user_is_managed( $user ) ? qalam_210_dashboard_url() : $redirect_to;
}
add_filter( 'login_redirect', 'qalam_210_login_redirect', 20, 3 );


/** Keep the standalone dashboard visually isolated from the active WordPress theme. */
function qalam_210_strip_theme_assets(): void {
    if ( ! qalam_210_is_dashboard_request() ) { return; }
    global $wp_styles, $wp_scripts;
    $theme_urls = array_filter( array( get_stylesheet_directory_uri(), get_template_directory_uri() ) );
    if ( $wp_styles instanceof WP_Styles ) {
        foreach ( (array) $wp_styles->queue as $handle ) {
            $src = isset( $wp_styles->registered[ $handle ] ) ? (string) $wp_styles->registered[ $handle ]->src : '';
            foreach ( $theme_urls as $theme_url ) {
                if ( $src && 0 === strpos( $src, $theme_url ) ) { wp_dequeue_style( $handle ); break; }
            }
        }
    }
    if ( $wp_scripts instanceof WP_Scripts ) {
        foreach ( (array) $wp_scripts->queue as $handle ) {
            $src = isset( $wp_scripts->registered[ $handle ] ) ? (string) $wp_scripts->registered[ $handle ]->src : '';
            foreach ( $theme_urls as $theme_url ) {
                if ( $src && 0 === strpos( $src, $theme_url ) ) { wp_dequeue_script( $handle ); break; }
            }
        }
    }
    foreach ( array( 'global-styles','classic-theme-styles','wp-block-library','wp-block-library-theme' ) as $handle ) { wp_dequeue_style( $handle ); }
}
add_action( 'wp_enqueue_scripts', 'qalam_210_strip_theme_assets', PHP_INT_MAX );

function qalam_210_section_capability( string $section ): string {
    $map = array(
        'home'=>'qalam_access_dashboard','courses'=>'qalam_manage_courses','students'=>'qalam_manage_students',
        'exams'=>'qalam_manage_exams','question-bank'=>'qalam_manage_question_bank','addons'=>'qalam_manage_addons',
        'ai'=>'qalam_manage_ai','reports'=>'qalam_manage_reports','commerce'=>'qalam_manage_addons','settings'=>'qalam_manage_settings','manage'=>'qalam_manage_addons',
    );
    return $map[ $section ] ?? 'qalam_access_dashboard';
}

function qalam_210_sections(): array {
    $items = array(
        'home'=>array('label'=>'الرئيسية','icon'=>'home'),
        'courses'=>array('label'=>'الدورات','icon'=>'courses'),
        'students'=>array('label'=>'الطلاب','icon'=>'students'),
        'exams'=>array('label'=>'الاختبارات','icon'=>'exams'),
        'question-bank'=>array('label'=>'بنك الأسئلة','icon'=>'questions','feature'=>'question_bank_suite'),
        'addons'=>array('label'=>'الملحقات','icon'=>'addons'),
        'ai'=>array('label'=>'الذكاء الاصطناعي','icon'=>'ai','feature'=>'artificial_intelligence'),
        'reports'=>array('label'=>'التقارير','icon'=>'reports','feature'=>'reports_suite'),
        'commerce'=>array('label'=>'التجارة','icon'=>'addons'),
        'settings'=>array('label'=>'الإعدادات','icon'=>'settings'),
        'manage'=>array('label'=>'إدارة الملحق','icon'=>'addons','hidden'=>true),
    );
    foreach ( $items as $key => &$item ) { $item['capability'] = qalam_210_section_capability( $key ); }
    unset( $item ); return $items;
}

function qalam_210_feature_group_visible( string $group ): bool {
    if ( $group && function_exists( 'qalam_290_feature_visible' ) && ! qalam_290_feature_visible( $group ) ) { return false; }
    return ! $group || ! function_exists( 'qalam_200_group_enabled' ) || qalam_200_group_enabled( $group );
}

function qalam_210_svg( string $name ): string {
    $paths = array(
        'home'=>'M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z',
        'courses'=>'M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15.5A2.5 2.5 0 0 0 17.5 16H6a2 2 0 0 0-2 2V5.5Zm2 8.5h11.5c.9 0 1.75.24 2.5.66V5H6.5a.5.5 0 0 0-.5.5V14Z',
        'students'=>'M8.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7.5-1a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20a5.5 5.5 0 0 1 11 0H3Zm10.5 0c0-2.02-.8-3.85-2.1-5.2A5 5 0 0 1 21 17v3h-7.5Z',
        'exams'=>'M5 3h11l3 3v15H5V3Zm3 5h8v2H8V8Zm0 4h8v2H8v-2Zm0 4h5v2H8v-2Z',
        'questions'=>'M12 3a7 7 0 0 0-7 7h3a4 4 0 1 1 4 4h-1.5v4H13v-1.3A7 7 0 0 0 12 3Zm-1.5 17h3v3h-3v-3Z',
        'addons'=>'M8 3h3v5h2V3h3v5h2v3h-5v2h5v3h-5v5h-3v-5H8v5H5v-5H3v-3h5v-2H3V8h5V3Z',
        'ai'=>'M12 2 14 7l5 2-5 2-2 5-2-5-5-2 5-2 2-5Zm7 12 1 2.5 2.5 1L20 18.5 19 21l-1-2.5-2.5-1 2.5-1L19 14Z',
        'reports'=>'M4 20V10h3v10H4Zm6 0V4h3v16h-3Zm6 0v-7h3v7h-3Z',
        'settings'=>'M12 8.5A3.5 3.5 0 1 0 12 15a3.5 3.5 0 0 0 0-6.5Zm9 4.5-2.3.8a7.8 7.8 0 0 1-.7 1.7l1 2.2-2.3 2.3-2.2-1a8 8 0 0 1-1.7.7L12 22h-3l-.8-2.3a8 8 0 0 1-1.7-.7l-2.2 1L2 17.7l1-2.2a8 8 0 0 1-.7-1.7L0 13v-3l2.3-.8A8 8 0 0 1 3 7.5l-1-2.2L4.3 3l2.2 1a8 8 0 0 1 1.7-.7L9 1h3l.8 2.3a8 8 0 0 1 1.7.7l2.2-1L19 5.3l-1 2.2a8 8 0 0 1 .7 1.7L21 10v3Z',
    );
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . esc_attr( $paths[ $name ] ?? $paths['home'] ) . '"></path></svg>';
}

/** Translate legacy Qalam/Tutor admin URLs into the standalone Qalam dashboard. */
function qalam_210_legacy_url_to_dashboard( string $url ): string {
    if ( function_exists( 'qalam_220_legacy_url_to_dashboard' ) ) {
        $qalam_220_mapped = qalam_220_legacy_url_to_dashboard( $url );
        if ( $qalam_220_mapped !== $url ) { return $qalam_220_mapped; }
    }
    $decoded = html_entity_decode( $url, ENT_QUOTES, 'UTF-8' );
    $parts   = wp_parse_url( $decoded );
    $path    = (string) ( $parts['path'] ?? '' );
    $script  = basename( $path );
    $query   = array();
    parse_str( (string) ( $parts['query'] ?? '' ), $query );

    // WordPress post editor is never exposed to managed Qalam users. Course edit
    // links go to the Qalam course builder; other page links become safe previews.
    if ( 'post.php' === $script ) {
        $post_id = absint( $query['post'] ?? 0 );
        if ( $post_id && get_post_type( $post_id ) === ( tutor()->course_post_type ?? 'courses' ) ) {
            return qalam_210_course_builder_url( $post_id );
        }
        if ( $post_id ) {
            $permalink = get_permalink( $post_id );
            return $permalink ? $permalink : qalam_210_dashboard_url( 'settings' );
        }
        return qalam_210_dashboard_url( 'settings' );
    }

    if ( 'admin.php' !== $script && false === strpos( $decoded, 'admin.php' ) ) { return $url; }
    $page = sanitize_key( (string) ( $query['page'] ?? '' ) );
    $map = array(
        'qalam-question-bank'=>'question-bank','tutor-content-bank'=>'question-bank','qalam-quiz-builder'=>'exams',
        'tutor_report'=>'reports','tutor_settings'=>'settings','tutor-addons'=>'addons','create-course'=>'courses','tutor'=>'courses',
        'qalam-student-profile'=>'students',
    );
    if ( ! isset( $map[ $page ] ) ) { return $url; }
    unset( $query['page'] );
    if ( 'tutor-content-bank' === $page ) { $query['mode'] = 'content-bank'; }
    if ( 'create-course' === $page && isset( $query['course_id'] ) ) { $query['course_id'] = absint( $query['course_id'] ); }
    if ( 'create-course' === $page ) { $query['builder'] = 1; }
    return qalam_210_dashboard_url( $map[ $page ], $query );
}
function qalam_210_map_managed_redirect( string $location, int $status = 302 ): string {
    return qalam_210_user_is_managed() ? qalam_210_legacy_url_to_dashboard( $location ) : $location;
}
add_filter( 'wp_redirect', 'qalam_210_map_managed_redirect', 1, 2 );

function qalam_210_rewrite_legacy_html( string $html ): string {
    return preg_replace_callback(
        '~((?:https?:)?//[^"\'<> ]+)?/wp-admin/(?:admin|post|users|user-edit|edit|post-new|options-general)\.php\?[^"\'<> ]+|(?:admin|post|users|user-edit|edit|post-new|options-general)\.php\?[^"\'<> ]+~i',
        static function( $m ) { return esc_url( qalam_210_legacy_url_to_dashboard( html_entity_decode( $m[0], ENT_QUOTES, 'UTF-8' ) ) ); },
        $html
    );
}
function qalam_210_capture( callable $callback ): string {
    ob_start(); $callback(); return qalam_210_rewrite_legacy_html( (string) ob_get_clean() );
}

function qalam_210_admin_styles(): void {
    foreach ( array( 'common','forms','buttons','list-tables','dashboard' ) as $handle ) { wp_enqueue_style( $handle ); }
    try {
        if ( class_exists( '\TUTOR\Assets' ) ) { ( new \TUTOR\Assets( false ) )->admin_scripts( 'qalam-admin' ); }
    } catch ( Throwable $e ) {
        do_action( 'qalam_dashboard_optional_asset_error', 'tutor', $e );
    }
    try {
        if ( class_exists( '\TUTOR_PRO\Assets' ) ) { ( new \TUTOR_PRO\Assets() )->admin_scripts(); }
    } catch ( Throwable $e ) {
        do_action( 'qalam_dashboard_optional_asset_error', 'tutor-pro', $e );
    }
}

function qalam_210_enqueue_qalam_tool_assets(): void {
    $base = plugin_dir_url( TUTOR_FILE );
    $prev = array();
    $tool_section = sanitize_key( (string) get_query_var( 'qalam_admin' ) );
    $versions = 'courses' === $tool_section ? array( '050','060','070','080' ) : array( '050','060','070','080','081' );
    foreach ( $versions as $ver ) {
        $handle = 'qalam-' . $ver . '-embedded';
        wp_enqueue_style( $handle, $base . 'assets/css/qalam-' . $ver . '-admin.css', $prev, QALAM_LMS_UI_VERSION );
        $prev = array( $handle );
        wp_enqueue_script( $handle, $base . 'assets/js/qalam-' . $ver . '-admin.js', array(), QALAM_LMS_UI_VERSION, true );
    }

    // The original Qalam admin scripts were localized only inside wp-admin.
    // Recreate the same contracts for the standalone dashboard so generation,
    // dynamic exams, previews and native Content Bank editing remain functional.
    wp_localize_script( 'qalam-050-embedded', 'Qalam050', array(
        'ajaxurl'       => admin_url( 'admin-ajax.php' ),
        'nonce_key'     => tutor()->nonce,
        'tutor_nonce'   => wp_create_nonce( tutor()->nonce_action ),
        'addon_nonce'   => wp_create_nonce( 'qalam_050_addon_toggle' ),
        'creating'      => 'جاري إنشاء المسودة...',
        'create_failed' => 'تعذر إنشاء مسودة الدورة. حاول مرة تانية.',
        'toggle_failed' => 'تعذر تغيير حالة الملحق.',
    ) );
    wp_localize_script( 'qalam-060-embedded', 'Qalam060', array(
        'ajaxurl'       => admin_url( 'admin-ajax.php' ),
        'ai_nonce'      => wp_create_nonce( 'qalam_ai_activate_provider' ),
        'addon_nonce'   => wp_create_nonce( 'qalam_050_addon_toggle' ),
        'loadingModels' => 'جاري التحقق من المفتاح وجلب الموديلات...',
        'activateLabel' => 'تفعيل وجلب الموديلات',
        'modelSearch'   => 'ابحث عن موديل...',
        'noModels'      => 'مفيش موديلات مطابقة للبحث.',
    ) );

    $section = sanitize_key( (string) get_query_var( 'qalam_admin' ) );
    $mode    = sanitize_key( (string) ( $_GET['mode'] ?? '' ) );
    $legacy_page = 'courses' === $section ? 'create-course' : ( 'exams' === $section ? 'qalam-quiz-builder' : ( 'content-bank' === $mode ? 'tutor-content-bank' : 'qalam-question-bank' ) );
    wp_localize_script( 'qalam-070-embedded', 'Qalam070', array(
        'questionBankUrl' => qalam_210_dashboard_url( 'question-bank' ),
        'contentBankUrl'  => qalam_210_dashboard_url( 'question-bank', array( 'mode'=>'content-bank' ) ),
        'page'            => $legacy_page,
    ) );

    $terms = get_terms( array( 'taxonomy'=>defined('QALAM_QUESTION_CATEGORY_TAX') ? QALAM_QUESTION_CATEGORY_TAX : 'qalam_question_category', 'hide_empty'=>false ) );
    if ( is_wp_error( $terms ) ) { $terms = array(); }
    $cats = array();
    foreach ( $terms as $term ) { $cats[] = array( 'id'=>(int)$term->term_id, 'name'=>$term->name, 'parent'=>(int)$term->parent ); }
    $quiz_id = 'exams' === $section ? absint( $_GET['quiz_id'] ?? 0 ) : 0;
    $dynamic = $quiz_id && defined('QALAM_080_DYNAMIC_META') ? '1' === (string) get_post_meta( $quiz_id, QALAM_080_DYNAMIC_META, true ) : false;
    $rules   = $quiz_id && defined('QALAM_080_DYNAMIC_RULES_META') ? get_post_meta( $quiz_id, QALAM_080_DYNAMIC_RULES_META, true ) : array();
    wp_localize_script( 'qalam-080-embedded', 'Qalam080', array(
        'ajaxUrl'                  => admin_url( 'admin-ajax.php' ),
        'processNonce'             => wp_create_nonce( 'qalam_080_process_generation' ),
        'adminPost'                => admin_url( 'admin-post.php' ),
        'previewBase'              => home_url( '/?qalam_question_preview=' ),
        'categories'               => $cats,
        'quizId'                   => $quiz_id,
        'quizToolsNonce'           => $quiz_id ? wp_create_nonce( 'qalam_080_quiz_tools_'.$quiz_id ) : '',
        'dynamicEnabled'           => $dynamic,
        'dynamicRules'             => is_array( $rules ) ? $rules : array(),
        'randomizedFeatureEnabled' => ! function_exists('qalam_feature_enabled') || qalam_feature_enabled('randomized_exams'),
        'dynamicFeatureEnabled'    => ! function_exists('qalam_feature_enabled') || qalam_feature_enabled('dynamic_exams'),
    ) );
    if ( wp_script_is( 'qalam-081-embedded', 'enqueued' ) ) {
        wp_localize_script( 'qalam-081-embedded', 'Qalam081', array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'statusNonce' => wp_create_nonce( 'qalam_080_process_generation' ),
        ) );
    }
}

/** Enqueue add-on assets that are normally attached only to admin_enqueue_scripts. */
function qalam_210_enqueue_pro_admin_context_assets( string $section ): void {
    if ( 'settings' === $section ) {
        if ( class_exists( '\\TutorPro\\Subscription\\Assets' ) ) {
            try {
                $subscription_assets = new \TutorPro\Subscription\Assets();
                $subscription_assets->admin_script();
            } catch ( Throwable $e ) {
                do_action( 'qalam_dashboard_optional_asset_error', 'subscriptions', $e );
            }
        }
        if ( class_exists( '\\TutorPro\\Auth\\Assets' ) ) {
            // Auth settings uses the exact same page contract but normally requires wp-admin.
            wp_enqueue_script( 'tutor-pro-auth-settings-js', tutor_pro()->url . 'addons/auth/assets/js/settings.js', array( 'jquery', 'wp-i18n' ), TUTOR_PRO_VERSION, true );
        }
        if ( function_exists( 'TUTOR_CERT' ) ) {
            wp_enqueue_style( 'tutor-pro-certificate-field-css', TUTOR_CERT()->url . 'assets/css/certificate-field.css', array(), TUTOR_PRO_VERSION );
        }
        if ( function_exists( 'tutor_pro' ) ) {
            wp_enqueue_style( 'tutor-pro-email-styles', tutor_pro()->url . 'addons/tutor-email/assets/css/email-manage.css', array(), TUTOR_PRO_VERSION );
        }
    }
    if ( 'reports' === $section && 'subscriptions' === sanitize_key( (string) ( $_GET['sub_page'] ?? '' ) ) && class_exists( '\\TutorPro\\Subscription\\Assets' ) ) {
        $subscription_assets = new \TutorPro\Subscription\Assets();
        $subscription_assets->admin_script();
    }
}

function qalam_210_is_course_builder_request(): bool {
    return 'courses' === sanitize_key( (string) get_query_var( 'qalam_admin' ) ) && ! empty( $_GET['builder'] );
}

/** Prepare the mature engine assets before wp_head() prints the Qalam shell. */
function qalam_210_prepare_section_assets( string $section ): void {
    try {
    if ( 'courses' === $section && qalam_210_is_course_builder_request() ) {
        $_GET['page'] = 'create-course';
        // Reuse Tutor's mature React course builder, but render it inside the Qalam shell.
        do_action( 'tutor_before_course_builder_load' );
        qalam_210_enqueue_qalam_tool_assets();
    }
    if ( in_array( $section, array( 'question-bank','exams' ), true ) ) {
        $_GET['page'] = 'exams' === $section ? 'qalam-quiz-builder' : 'qalam-question-bank';
        qalam_210_admin_styles();
        qalam_210_enqueue_qalam_tool_assets();
    }
    if ( 'question-bank' === $section && 'content-bank' === sanitize_key( (string) ( $_GET['mode'] ?? '' ) ) ) {
        $_GET['page'] = 'tutor-content-bank';
        qalam_210_admin_styles();
        if ( class_exists( '\\TutorPro\\ContentBank\\Assets' ) ) { ( new \TutorPro\ContentBank\Assets( false ) )->admin_script(); }
        wp_enqueue_editor(); wp_enqueue_media();
    }
    if ( 'reports' === $section ) {
        $_GET['page'] = 'tutor_report'; qalam_210_admin_styles();
        if ( function_exists( 'tutor_report_instance' ) && ! empty( tutor_report_instance()->report ) ) { tutor_report_instance()->report->admin_scripts( 'qalam-admin' ); }
        qalam_210_enqueue_pro_admin_context_assets( 'reports' );
    }
    if ( 'settings' === $section ) {
        $_GET['page'] = 'tutor_settings'; qalam_210_admin_styles();
        qalam_210_enqueue_pro_admin_context_assets( 'settings' );
    }
    if ( 'commerce' === $section && function_exists( 'qalam_220_prepare_commerce_assets' ) ) {
        qalam_220_prepare_commerce_assets();
    }
    if ( 'manage' === $section && function_exists( 'qalam_220_prepare_manage_assets' ) ) {
        qalam_220_prepare_manage_assets();
    }
    } catch ( Throwable $e ) {
        // Optional wp-admin assets must never take down the standalone Qalam dashboard.
        do_action( 'qalam_dashboard_optional_asset_error', sanitize_key( $section ), $e );
    }
}

function qalam_210_dashboard_stats(): array {
    global $wpdb;
    $course_type = isset( tutor()->course_post_type ) ? tutor()->course_post_type : 'courses';
    $args = array( 'post_type'=>$course_type,'post_status'=>array('publish','draft','pending'),'fields'=>'ids','posts_per_page'=>1 );
    if ( ! qalam_210_user_is_platform_admin() && ! current_user_can( 'administrator' ) ) { $args['author'] = get_current_user_id(); }
    $course_count = (int) ( new WP_Query( $args ) )->found_posts;
    $student_count = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = '_is_tutor_student'" );
    $quiz_count = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type='tutor_quiz' AND post_status IN ('publish','draft','private')" );
    $addons = function_exists( 'qalam_200_product_catalog' ) ? qalam_200_product_catalog() : array(); $enabled = 0;
    foreach ( $addons as $addon ) { if ( ! empty( $addon['effective_enabled'] ) ) { $enabled++; } }
    return array(
        array('label'=>'الدورات','value'=>$course_count,'icon'=>'courses'), array('label'=>'الطلاب','value'=>$student_count,'icon'=>'students'),
        array('label'=>'الاختبارات','value'=>$quiz_count,'icon'=>'exams'), array('label'=>'الملحقات المفعلة','value'=>$enabled,'icon'=>'addons'),
    );
}

function qalam_210_render_home(): void {
    $user = wp_get_current_user();
    echo '<section class="qalam-admin-hero"><div><span class="qalam-eyebrow">لوحة إدارة قلم</span><h1>أهلًا ' . esc_html( $user->display_name ?: $user->user_login ) . ' 👋</h1><p>كل ما تحتاجه لإدارة منصتك التعليمية في مكان واحد داخل لوحة قلم.</p></div><a class="qalam-primary-action" href="' . esc_url( qalam_210_dashboard_url('courses') ) . '">إدارة الدورات</a></section><div class="qalam-stat-grid">';
    foreach ( qalam_210_dashboard_stats() as $stat ) { echo '<article class="qalam-stat-card"><div class="qalam-stat-icon">'.qalam_210_svg($stat['icon']).'</div><div><strong>'.esc_html((string)$stat['value']).'</strong><span>'.esc_html($stat['label']).'</span></div></article>'; }
    echo '</div><div class="qalam-dashboard-grid"><section class="qalam-panel"><div class="qalam-panel-head"><div><span>اختصارات</span><h2>ابدأ بسرعة</h2></div></div><div class="qalam-quick-grid">';
    foreach ( array('courses'=>'إدارة الدورات','students'=>'الطلاب','exams'=>'الاختبارات','question-bank'=>'بنك الأسئلة','addons'=>'الملحقات','ai'=>'الذكاء الاصطناعي') as $key=>$label ) {
        $section=qalam_210_sections()[$key]??array(); if(!$section||(!current_user_can($section['capability'])&&!current_user_can('manage_tutor'))||(!empty($section['feature'])&&!qalam_210_feature_group_visible($section['feature'])))continue;
        echo '<a class="qalam-quick-item" href="'.esc_url(qalam_210_dashboard_url($key)).'"><span class="qalam-quick-icon">'.qalam_210_svg($section['icon']).'</span><span>'.esc_html($label).'</span></a>';
    }
    echo '</div></section><section class="qalam-panel qalam-brand-panel"><div class="qalam-orb"></div><span class="qalam-eyebrow">Qalam LMS</span><h2>إدارة تعليمية أبسط.</h2><p>لوحة قلم هي واجهة الإدارة اليومية، والمحرك الخلفي يعمل بعيدًا عن تجربة المستخدم.</p><div class="qalam-status-pill"><span></span>النظام يعمل بصورة طبيعية</div></section></div>';
}

function qalam_210_course_builder_url( int $course_id = 0 ): string {
    $args = array( 'builder' => 1 );
    if ( $course_id ) { $args['course_id'] = $course_id; }
    return qalam_210_dashboard_url( 'courses', $args );
}

function qalam_210_render_course_builder(): void {
    $course_id = absint( $_GET['course_id'] ?? 0 );
    if ( $course_id ) {
        if ( get_post_type( $course_id ) !== tutor()->course_post_type ) {
            echo '<div class="qalam-empty"><strong>الدورة غير موجودة.</strong></div>';
            return;
        }
        $author_id = (int) get_post_field( 'post_author', $course_id );
        if ( ! qalam_210_user_is_platform_admin() && $author_id !== get_current_user_id() ) {
            echo '<div class="qalam-alert is-error">ليس لديك صلاحية تعديل هذه الدورة.</div>';
            return;
        }
        if ( 'trash' === get_post_status( $course_id ) ) {
            echo '<div class="qalam-alert is-error">الدورة موجودة في سلة المهملات ولا يمكن تعديلها.</div>';
            return;
        }
    }
    echo '<div class="qalam-course-builder-head"><a class="qalam-back-link" href="'.esc_url(qalam_210_dashboard_url('courses')).'">← رجوع إلى الدورات</a><div><span class="qalam-eyebrow">منشئ قلم</span><h1>'.esc_html($course_id ? (get_the_title($course_id) ?: 'تعديل الدورة') : 'إنشاء دورة جديدة').'</h1></div></div>';
    echo '<div class="qalam-course-builder-embed"><div id="tutor-course-builder"></div></div>';
    do_action( 'tutor_course_builder_footer' );
    do_action( 'tutor_after_course_builder_load' );
}

function qalam_210_render_courses(): void {
    if ( qalam_210_is_course_builder_request() ) { qalam_210_render_course_builder(); return; }
    $course_type = tutor()->course_post_type ?? 'courses';
    $search = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ); $status=sanitize_key((string)($_GET['status']??'')); $paged=max(1,absint($_GET['paged']??1));
    $statuses=array('publish'=>'منشورة','draft'=>'مسودة','pending'=>'قيد المراجعة','future'=>'مجدولة');
    $args=array('post_type'=>$course_type,'post_status'=>$status&&isset($statuses[$status])?$status:array_keys($statuses),'posts_per_page'=>20,'paged'=>$paged,'s'=>$search,'orderby'=>'modified','order'=>'DESC');
    if(!qalam_210_user_is_platform_admin()&&!current_user_can('administrator'))$args['author']=get_current_user_id();
    $query=new WP_Query($args);
    echo '<div class="qalam-page-head"><div><span class="qalam-eyebrow">المحتوى التعليمي</span><h1>الدورات</h1><p>إنشاء وتعديل ونشر الدورات مباشرة من محرك قلم.</p></div><a class="qalam-primary-action" href="'.esc_url(qalam_210_course_builder_url()).'">+ دورة جديدة</a></div>';
    echo '<form class="qalam-filterbar" method="get"><input type="search" name="q" value="'.esc_attr($search).'" placeholder="ابحث باسم الدورة..."><select name="status"><option value="">كل الحالات</option>'; foreach($statuses as $k=>$v)echo '<option value="'.esc_attr($k).'" '.selected($status,$k,false).'>'.esc_html($v).'</option>'; echo '</select><button>بحث</button></form>';
    echo '<section class="qalam-panel"><div class="qalam-table">';
    if(!$query->have_posts())echo '<div class="qalam-empty"><strong>لا توجد دورات مطابقة</strong><span>أنشئ دورة جديدة أو غيّر البحث.</span></div>';
    while($query->have_posts()){ $query->the_post();$id=get_the_ID();$st=get_post_status($id);$trash=wp_nonce_url(admin_url('admin-post.php?action=qalam_210_course_trash&course_id='.$id),'qalam_210_course_trash_'.$id);echo '<article class="qalam-row"><div class="qalam-row-main"><div class="qalam-row-thumb">'.(get_the_post_thumbnail($id,'thumbnail')?:qalam_210_svg('courses')).'</div><div><h3>'.esc_html(get_the_title()?:'دورة بدون عنوان').'</h3><span>'.esc_html($statuses[$st]??$st).' · آخر تعديل '.esc_html(get_the_modified_date()).'</span></div></div><div class="qalam-inline-actions"><a class="qalam-row-action" href="'.esc_url(qalam_210_course_builder_url($id)).'">فتح المنشئ</a><a class="qalam-row-action is-danger" href="'.esc_url($trash).'" onclick="return confirm(\'نقل الدورة إلى سلة المهملات؟\')">حذف</a></div></article>'; }
    wp_reset_postdata(); echo '</div>';
    if($query->max_num_pages>1){echo '<nav class="qalam-pagination">';for($i=1;$i<=$query->max_num_pages;$i++)echo '<a class="'.($i===$paged?'is-current':'').'" href="'.esc_url(qalam_210_dashboard_url('courses',array_filter(array('q'=>$search,'status'=>$status,'paged'=>$i)))).'">'.$i.'</a>';echo '</nav>';}
    echo '</section>';
}

function qalam_210_course_trash(): void {
    $id=absint($_GET['course_id']??0); check_admin_referer('qalam_210_course_trash_'.$id);
    if(!current_user_can('qalam_manage_courses')&&!current_user_can('manage_tutor'))wp_die('غير مسموح.');
    if($id&&get_post_type($id)===tutor()->course_post_type&&(qalam_210_user_is_platform_admin()||(int)get_post_field('post_author',$id)===get_current_user_id()))wp_trash_post($id);
    wp_safe_redirect(qalam_210_dashboard_url('courses',array('deleted'=>1)));exit;
}
add_action('admin_post_qalam_210_course_trash','qalam_210_course_trash');

function qalam_210_student_ids( string $search = '' ): array {
    global $wpdb;
    $role_ids = get_users( array( 'number'=>-1, 'role'=>'qalam_student', 'fields'=>'ID' ) );
    $meta_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key='_is_tutor_student'" );
    $ids = array_values( array_unique( array_map( 'intval', array_merge( (array) $role_ids, (array) $meta_ids ) ) ) );
    if ( '' !== $search ) {
        $needle = function_exists( 'mb_strtolower' ) ? mb_strtolower( $search, 'UTF-8' ) : strtolower( $search );
        $ids = array_values( array_filter( $ids, static function( $id ) use ( $needle ) {
            $u = get_userdata( $id );
            if ( ! $u ) { return false; }
            $hay = trim( (string) $u->display_name . ' ' . (string) $u->user_login . ' ' . (string) $u->user_email . ' ' . (string) get_user_meta( $id, 'phone_number', true ) );
            $hay = function_exists( 'mb_strtolower' ) ? mb_strtolower( $hay, 'UTF-8' ) : strtolower( $hay );
            return false !== strpos( $hay, $needle );
        } ) );
    }
    usort( $ids, static function( $a, $b ) {
        $ua = get_userdata( $a ); $ub = get_userdata( $b );
        return strcmp( (string) ( $ub->user_registered ?? '' ), (string) ( $ua->user_registered ?? '' ) );
    } );
    return $ids;
}

function qalam_210_enrolled_course_posts( int $student_id ): array {
    if ( ! class_exists( 'Tutor\\Models\\CourseModel' ) ) { return array(); }
    $query = \Tutor\Models\CourseModel::get_enrolled_courses_by_user( $student_id, array( 'publish', 'private' ) );
    return $query && isset( $query->posts ) && is_array( $query->posts ) ? $query->posts : array();
}

function qalam_210_render_student_detail( int $student_id ): void {
    $student = get_userdata( $student_id );
    if ( ! $student ) { echo '<div class="qalam-empty"><strong>الطالب غير موجود</strong></div>'; return; }
    $is_student = (bool) get_user_meta( $student_id, '_is_tutor_student', true ) || in_array( 'qalam_student', (array) $student->roles, true ) || in_array( 'subscriber', (array) $student->roles, true );
    if ( ! $is_student ) { echo '<div class="qalam-alert is-error">هذا الحساب ليس حساب طالب.</div>'; return; }

    $review_attempt_id = absint( $_GET['attempt_id'] ?? 0 );
    if ( $review_attempt_id ) {
        $attempt = tutor_utils()->get_attempt( $review_attempt_id );
        if ( ! $attempt || (int) $attempt->user_id !== $student_id ) {
            echo '<div class="qalam-alert is-error">المحاولة غير موجودة أو لا تخص هذا الطالب.</div>';
            return;
        }
        echo '<div class="qalam-page-head"><div><a class="qalam-back-link" href="'.esc_url(qalam_210_dashboard_url('students',array('student_id'=>$student_id))).'">← رجوع إلى ملف الطالب</a><h1>مراجعة إجابات الطالب</h1><p>'.esc_html(get_the_title((int)$attempt->quiz_id) ?: 'اختبار').'</p></div></div><section class="qalam-panel qalam-attempt-review-embed">';
        tutor_load_template_from_custom_path( tutor()->path . '/views/quiz/attempt-details.php', array( 'attempt_id'=>$review_attempt_id,'attempt_data'=>$attempt,'user_id'=>$student_id,'context'=>'backend-dashboard-students-attempts' ) );
        echo '</section>';
        return;
    }

    $courses  = qalam_210_enrolled_course_posts( $student_id );
    $attempts = tutor_utils()->get_all_quiz_attempts_by_user( $student_id );
    $attempts = is_array( $attempts ) ? $attempts : array();
    $phone    = (string) get_user_meta( $student_id, 'phone_number', true );
    $certificates = array();
    if ( class_exists( '\\TUTOR_CERT\\Certificate' ) ) {
        try { $certificates = ( new \TUTOR_CERT\Certificate( true ) )->get_user_certificates( $student_id ); } catch ( Throwable $e ) { $certificates = array(); }
    }

    $notice = sanitize_text_field( wp_unslash( $_GET['notice'] ?? '' ) );
    $error  = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) );
    echo '<div class="qalam-page-head qalam-student-profile-head"><div><a class="qalam-back-link" href="'.esc_url(qalam_210_dashboard_url('students')).'">← كل الطلاب</a><h1>'.esc_html($student->display_name ?: $student->user_login).'</h1><p>'.esc_html($student->user_email).' · مسجل منذ '.esc_html(mysql2date('Y/m/d',$student->user_registered)).'</p></div><div class="qalam-profile-avatar">'.get_avatar($student_id,72).'</div></div>';
    if ( $notice ) { echo '<div class="qalam-alert is-success">'.esc_html($notice).'</div>'; }
    if ( $error ) { echo '<div class="qalam-alert is-error">'.esc_html($error).'</div>'; }

    echo '<div class="qalam-student-overview">';
    echo '<section class="qalam-panel"><div class="qalam-panel-head"><div><span>الحساب</span><h2>بيانات الطالب</h2></div></div><form class="qalam-settings-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="qalam_210_student_update"><input type="hidden" name="student_id" value="'.esc_attr($student_id).'">';
    wp_nonce_field( 'qalam_210_student_update_'.$student_id, 'nonce' );
    echo '<div class="qalam-form-grid"><label><span>الاسم</span><input name="name" value="'.esc_attr($student->display_name).'" required></label><label><span>البريد الإلكتروني</span><input type="email" name="email" value="'.esc_attr($student->user_email).'" required></label><label><span>رقم الهاتف</span><input name="phone" value="'.esc_attr($phone).'" inputmode="tel"></label><label><span>كلمة مرور جديدة</span><input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="اتركها فارغة بدون تغيير"></label></div><div class="qalam-form-actions"><button class="qalam-primary-action">حفظ بيانات الطالب</button></div></form></section>';
    echo '<section class="qalam-panel qalam-student-summary"><div><span>الدورات المسجل بها</span><strong>'.esc_html(count((array)$courses)).'</strong></div><div><span>محاولات الاختبارات</span><strong>'.esc_html(count($attempts)).'</strong></div><div><span>الشهادات</span><strong>'.esc_html(count($certificates)).'</strong></div></section></div>';

    echo '<div class="qalam-dashboard-grid qalam-student-learning-grid"><section class="qalam-panel"><div class="qalam-panel-head"><div><span>التعلم</span><h2>الدورات والتقدم</h2></div></div><div class="qalam-table">';
    if ( ! $courses ) { echo '<div class="qalam-empty"><strong>لا توجد دورات</strong></div>'; }
    foreach ( (array) $courses as $course ) {
        $cid = (int) $course->ID;
        $pct = (int) tutor_utils()->get_course_completed_percent( $cid, $student_id );
        $remove = wp_nonce_url( admin_url('admin-post.php?action=qalam_210_student_unenroll&student_id='.$student_id.'&course_id='.$cid), 'qalam_210_student_unenroll_'.$student_id.'_'.$cid );
        echo '<article class="qalam-row"><div class="qalam-row-grow"><h3>'.esc_html(get_the_title($cid)).'</h3><span>'.esc_html($pct).'% مكتمل</span><div class="qalam-progress"><i style="width:'.esc_attr($pct).'%"></i></div></div><a class="qalam-row-action is-danger" href="'.esc_url($remove).'" onclick="return confirm(\'إلغاء تسجيل الطالب من الدورة؟\')">إلغاء التسجيل</a></article>';
    }
    echo '</div><form class="qalam-inline-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="qalam_210_student_enroll"><input type="hidden" name="student_id" value="'.esc_attr($student_id).'">';
    wp_nonce_field( 'qalam_210_student_enroll_'.$student_id, 'nonce' );
    echo '<select name="course_id" required><option value="">اختر دورة لإضافة الطالب</option>';
    $all = get_posts( array( 'post_type'=>tutor()->course_post_type,'post_status'=>'publish','posts_per_page'=>200,'orderby'=>'title','order'=>'ASC' ) );
    $enrolled_ids = array_map( static fn($c)=>(int)$c->ID, (array)$courses );
    foreach ( $all as $c ) { if ( ! in_array( (int)$c->ID, $enrolled_ids, true ) ) { echo '<option value="'.esc_attr($c->ID).'">'.esc_html($c->post_title).'</option>'; } }
    echo '</select><button class="qalam-primary-action">تسجيل بالدورة</button></form></section>';

    echo '<section class="qalam-panel"><div class="qalam-panel-head"><div><span>التقييم</span><h2>محاولات الاختبارات</h2></div></div><div class="qalam-table">';
    if ( ! $attempts ) { echo '<div class="qalam-empty"><strong>لا توجد محاولات</strong></div>'; }
    foreach ( array_slice( $attempts, 0, 30 ) as $a ) {
        $total=(float)($a->total_marks??0); $earned=(float)($a->earned_marks??0); $pct=$total>0?round($earned/$total*100,1):0;
        $result = (string)($a->result??'');
        $review_url = qalam_210_dashboard_url( 'students', array( 'student_id'=>$student_id, 'attempt_id'=>(int)$a->attempt_id ) );
        echo '<article class="qalam-row"><div><h3>'.esc_html(get_the_title((int)$a->quiz_id)?:'اختبار').'</h3><span>'.esc_html($earned.' / '.$total.' · '.$pct.'%'.($result?' · '.$result:'')).'</span></div><a class="qalam-row-action" href="'.esc_url($review_url).'">مراجعة الإجابات</a></article>';
    }
    echo '</div></section></div>';

    echo '<section class="qalam-panel qalam-certificates-panel"><div class="qalam-panel-head"><div><span>الإنجازات</span><h2>الشهادات</h2></div></div><div class="qalam-certificate-grid">';
    if ( ! $certificates ) { echo '<div class="qalam-empty"><strong>لم يحصل الطالب على شهادات بعد</strong></div>'; }
    foreach ( $certificates as $certificate ) {
        $title = (string)($certificate['title'] ?? $certificate['course_title'] ?? 'شهادة إتمام');
        $url   = (string)($certificate['certificate_url'] ?? '');
        echo '<article class="qalam-certificate-card"><div class="qalam-row-symbol">'.qalam_210_svg('courses').'</div><div><strong>'.esc_html($title).'</strong><span>شهادة مستحقة للطالب</span></div>'.($url?'<a class="qalam-row-action" target="_blank" href="'.esc_url($url).'">عرض الشهادة</a>':'').'</article>';
    }
    echo '</div></section>';
}

function qalam_210_render_students(): void {
    $student_id=absint($_GET['student_id']??0); if($student_id){qalam_210_render_student_detail($student_id);return;}
    $search=sanitize_text_field(wp_unslash($_GET['q']??''));$ids=qalam_210_student_ids($search);$page=max(1,absint($_GET['paged']??1));$per=30;$slice=array_slice($ids,($page-1)*$per,$per);
    echo '<div class="qalam-page-head"><div><span class="qalam-eyebrow">المتعلمون</span><h1>الطلاب</h1><p>الحسابات والتسجيلات والتقدم والاختبارات من مكان واحد.</p></div><button class="qalam-primary-action" type="button" data-qalam-toggle="#qalam-new-student">+ طالب جديد</button></div>';
    $list_error = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) );
    if ( $list_error ) { echo '<div class="qalam-alert is-error">'.esc_html($list_error).'</div>'; }
    echo '<form id="qalam-new-student" class="qalam-panel qalam-create-panel" method="post" action="'.esc_url(admin_url('admin-post.php')).'" hidden><input type="hidden" name="action" value="qalam_210_student_create">';wp_nonce_field('qalam_210_student_create','nonce');echo '<div class="qalam-form-grid"><label><span>الاسم</span><input name="name" required></label><label><span>البريد الإلكتروني</span><input type="email" name="email" required></label><label><span>كلمة المرور</span><input type="password" name="password" minlength="8" required></label><button class="qalam-primary-action">إنشاء الطالب</button></div></form>';
    echo '<form class="qalam-filterbar" method="get"><input type="search" name="q" value="'.esc_attr($search).'" placeholder="ابحث بالاسم أو البريد..."><button>بحث</button></form><section class="qalam-panel"><div class="qalam-table">';
    if(!$slice)echo '<div class="qalam-empty"><strong>لا يوجد طلاب</strong><span>أنشئ طالبًا جديدًا أو غيّر البحث.</span></div>';
    foreach($slice as $id){$s=get_userdata($id);if(!$s)continue;$courses=qalam_210_enrolled_course_posts((int)$id);echo '<article class="qalam-row"><div class="qalam-row-main"><div class="qalam-avatar">'.get_avatar($id,48).'</div><div><h3>'.esc_html($s->display_name?:$s->user_login).'</h3><span>'.esc_html($s->user_email).' · '.esc_html(count((array)$courses)).' دورة</span></div></div><a class="qalam-row-action" href="'.esc_url(qalam_210_dashboard_url('students',array('student_id'=>$id))).'">فتح الملف</a></article>';}
    echo '</div>'; $pages=(int)ceil(count($ids)/$per);if($pages>1){echo '<nav class="qalam-pagination">';for($i=1;$i<=$pages;$i++)echo '<a class="'.($i===$page?'is-current':'').'" href="'.esc_url(qalam_210_dashboard_url('students',array_filter(array('q'=>$search,'paged'=>$i)))).'">'.$i.'</a>';echo '</nav>';}echo '</section>';
}

function qalam_210_student_update(): void {
    $sid = absint( $_POST['student_id'] ?? 0 );
    check_admin_referer( 'qalam_210_student_update_'.$sid, 'nonce' );
    if ( ! current_user_can( 'qalam_manage_students' ) && ! current_user_can( 'manage_tutor' ) ) { wp_die( 'غير مسموح.' ); }
    $student = get_userdata( $sid );
    if ( ! $student ) { wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'error'=>'الطالب غير موجود.' ) ) ); exit; }
    $name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
    $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $pass  = (string) wp_unslash( $_POST['password'] ?? '' );
    if ( ! $name || ! is_email( $email ) ) { wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$sid,'error'=>'الاسم أو البريد غير صالح.' ) ) ); exit; }
    $email_owner = email_exists( $email );
    if ( $email_owner && (int)$email_owner !== $sid ) { wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$sid,'error'=>'البريد الإلكتروني مستخدم في حساب آخر.' ) ) ); exit; }
    $payload = array( 'ID'=>$sid, 'display_name'=>$name, 'user_email'=>$email );
    if ( $pass ) {
        if ( strlen( $pass ) < 8 ) { wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$sid,'error'=>'كلمة المرور يجب ألا تقل عن 8 أحرف.' ) ) ); exit; }
        $payload['user_pass'] = $pass;
    }
    $result = wp_update_user( $payload );
    if ( is_wp_error( $result ) ) { wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$sid,'error'=>$result->get_error_message() ) ) ); exit; }
    update_user_meta( $sid, 'phone_number', $phone );
    update_user_meta( $sid, '_is_tutor_student', get_user_meta($sid,'_is_tutor_student',true) ?: tutor_time() );
    wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$sid,'notice'=>'تم تحديث بيانات الطالب.' ) ) ); exit;
}
add_action( 'admin_post_qalam_210_student_update', 'qalam_210_student_update' );

function qalam_210_student_create(): void {
    check_admin_referer( 'qalam_210_student_create', 'nonce' );
    if ( ! current_user_can( 'qalam_manage_students' ) && ! current_user_can( 'manage_tutor' ) ) { wp_die( 'غير مسموح.' ); }
    $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
    $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $password = (string) wp_unslash( $_POST['password'] ?? '' );
    if ( ! $name || ! is_email( $email ) || strlen( $password ) < 8 ) { wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'error'=>'بيانات الطالب غير مكتملة أو كلمة المرور قصيرة.' ) ) ); exit; }
    if ( email_exists( $email ) ) { wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'error'=>'يوجد حساب بالفعل بهذا البريد الإلكتروني.' ) ) ); exit; }
    $login = sanitize_user( strstr( $email, '@', true ), true ) ?: 'student';
    $base=$login; $n=1; while ( username_exists( $login ) ) { $login=$base.++$n; }
    $id = wp_insert_user( array( 'user_login'=>$login,'user_pass'=>$password,'user_email'=>$email,'display_name'=>$name,'role'=>'qalam_student' ) );
    if ( is_wp_error( $id ) ) { wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'error'=>$id->get_error_message() ) ) ); exit; }
    update_user_meta( $id, '_is_tutor_student', tutor_time() );
    wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$id,'notice'=>'تم إنشاء الطالب بنجاح.' ) ) ); exit;
}
add_action( 'admin_post_qalam_210_student_create', 'qalam_210_student_create' );

function qalam_210_student_enroll(): void {
    $sid = absint( $_POST['student_id'] ?? 0 );
    check_admin_referer( 'qalam_210_student_enroll_'.$sid, 'nonce' );
    if ( ! current_user_can( 'qalam_manage_students' ) && ! current_user_can( 'manage_tutor' ) ) { wp_die( 'غير مسموح.' ); }
    $cid = absint( $_POST['course_id'] ?? 0 );
    if ( ! $sid || ! get_userdata( $sid ) || ! $cid || get_post_type( $cid ) !== tutor()->course_post_type || ! class_exists( 'Tutor\\Models\\EnrollmentModel' ) ) {
        wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$sid, 'error'=>'تعذر تسجيل الطالب في الدورة.' ) ) ); exit;
    }
    $eid = \Tutor\Models\EnrollmentModel::do_enroll( $cid, 0, $sid );
    if ( $eid ) {
        \Tutor\Models\EnrollmentModel::update_enrollments( \Tutor\Models\EnrollmentModel::STATUS_COMPLETED, array( (int) $eid ), true );
        wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$sid, 'notice'=>'تم تسجيل الطالب في الدورة.' ) ) ); exit;
    }
    wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$sid, 'error'=>'لم يكتمل تسجيل الطالب في الدورة.' ) ) ); exit;
}
add_action( 'admin_post_qalam_210_student_enroll', 'qalam_210_student_enroll' );

function qalam_210_student_unenroll(): void {
    $sid = absint( $_GET['student_id'] ?? 0 );
    $cid = absint( $_GET['course_id'] ?? 0 );
    check_admin_referer( 'qalam_210_student_unenroll_'.$sid.'_'.$cid );
    if ( ! current_user_can( 'qalam_manage_students' ) && ! current_user_can( 'manage_tutor' ) ) { wp_die( 'غير مسموح.' ); }
    if ( $sid && $cid && class_exists( 'Tutor\\Models\\EnrollmentModel' ) ) {
        \Tutor\Models\EnrollmentModel::delete_enrollment_record( $sid, $cid );
        do_action( 'tutor_enrollment/after/delete', $sid, $cid );
        wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$sid, 'notice'=>'تم إلغاء تسجيل الطالب من الدورة.' ) ) ); exit;
    }
    wp_safe_redirect( qalam_210_dashboard_url( 'students', array( 'student_id'=>$sid, 'error'=>'تعذر إلغاء تسجيل الطالب.' ) ) ); exit;
}
add_action( 'admin_post_qalam_210_student_unenroll', 'qalam_210_student_unenroll' );

function qalam_210_render_exams(): void {
    echo '<div class="qalam-page-head"><div><span class="qalam-eyebrow">التقييم</span><h1>الاختبارات</h1><p>الاختبارات المستقلة والديناميكية والنتائج والمراجعة داخل لوحة قلم.</p></div></div><div class="qalam-embedded-tool">';
    if(function_exists('qalam_081_render_quiz_builder'))echo qalam_210_capture('qalam_081_render_quiz_builder');else echo '<div class="qalam-empty"><strong>محرك الاختبارات غير متاح.</strong></div>';
    echo '</div>';
}

function qalam_210_render_question_bank(): void {
    $mode=sanitize_key((string)($_GET['mode']??''));
    echo '<div class="qalam-page-head"><div><span class="qalam-eyebrow">المحتوى القابل لإعادة الاستخدام</span><h1>بنك الأسئلة</h1><p>التصنيفات، التعديل، الحذف، المعاينة، الذكاء الاصطناعي وPDF من لوحة قلم.</p></div></div>';
    if('content-bank'===$mode){
        $term=absint($_GET['question_category']??0);if(!empty($_GET['qalam_open_question']))update_user_meta(get_current_user_id(),defined('QALAM_QBANK_PENDING_CAT')?QALAM_QBANK_PENDING_CAT:'_qalam_qbank_pending_category',$term);
        echo '<div class="qalam-embedded-tool qalam-content-bank-embed"><div class="qalam-tool-back"><a href="'.esc_url(qalam_210_dashboard_url('question-bank')).'">← رجوع لبنك الأسئلة</a></div><div id="tutor-content-bank-root"></div></div>';return;
    }
    echo '<div class="qalam-embedded-tool">';if(function_exists('qalam_081_render_question_bank'))echo qalam_210_capture('qalam_081_render_question_bank');else echo '<div class="qalam-empty"><strong>بنك الأسئلة غير متاح.</strong></div>';echo '</div>';
}

function qalam_210_addon_manage_url( string $key, array $addon ): string {
    if ( function_exists( 'qalam_220_product_manage_url' ) ) { return qalam_220_product_manage_url( $key ); }
    $map=array('question_bank_suite'=>'question-bank','advanced_exams'=>'exams','artificial_intelligence'=>'ai','reports_suite'=>'reports');
    if(isset($map[$key]))return qalam_210_dashboard_url($map[$key]);
    $legacy=(string)($addon['manage_url_resolved']??'');$mapped=$legacy?qalam_210_legacy_url_to_dashboard($legacy):'';
    return $mapped!==$legacy?$mapped:'';
}

function qalam_210_render_addons(): void {
    $catalog=function_exists('qalam_200_product_catalog')?qalam_200_product_catalog():array();$categories=function_exists('qalam_180_feature_categories')?qalam_180_feature_categories():array();$enabled=0;foreach($catalog as $r)if(!empty($r['effective_enabled']))$enabled++;
    echo '<div class="qalam-page-head"><div><span class="qalam-eyebrow">التوسعات</span><h1>الملحقات</h1><p>تشغيل وإيقاف خدمات المنصة؛ الملحق المعطل يختفي من واجهات قلم ولا يُحمّل في الخلفية.</p></div><div class="qalam-head-stat"><strong>'.esc_html($enabled).'</strong><span>مفعّل من '.esc_html(count($catalog)).'</span></div></div>';
    echo '<div class="qalam-addon-toolbar"><input type="search" placeholder="ابحث في الملحقات..." data-qalam-addon-search><select data-qalam-addon-category><option value="">كل الأقسام</option>';foreach($categories as $k=>$v)echo '<option value="'.esc_attr($k).'">'.esc_html($v).'</option>';echo '</select></div><div class="qalam-addon-grid">';
    foreach($catalog as $key=>$addon){$on=!empty($addon['effective_enabled']);$locked=empty($addon['access']['allowed']);$icon=(string)($addon['icon_url']??'');$category=(string)($addon['category']??'');$manage=qalam_210_addon_manage_url((string)$key,$addon);$search=mb_strtolower(($addon['name']??$key).' '.($addon['description']??''),'UTF-8');echo '<article class="qalam-addon-card '.($on?'is-enabled':'').' '.($locked?'is-locked':'').'" data-addon-card data-category="'.esc_attr($category).'" data-search="'.esc_attr($search).'"><div class="qalam-addon-top"><div class="qalam-addon-image">'.($icon?'<img src="'.esc_url($icon).'" alt="">':qalam_210_svg('addons')).'</div><span class="qalam-dot-status '.($on?'on':'off').'">'.esc_html($addon['status']??($on?'مفعّل':'متوقف')).'</span></div><h3>'.esc_html($addon['name']??$key).'</h3><p>'.esc_html($addon['description']??'').'</p>';
        if($locked)echo '<div class="qalam-addon-note">'.esc_html($addon['access']['reason']??'غير متاح في الباقة الحالية.').'</div>';
        echo '<div class="qalam-addon-actions">';if($on&&$manage)echo '<a href="'.esc_url($manage).'" class="qalam-secondary-action">إدارة</a>';if(!$locked)echo '<button type="button" class="qalam-addon-toggle" data-feature="'.esc_attr($key).'" data-enable="'.($on?'0':'1').'">'.($on?'تعطيل':'تفعيل').'</button>';echo '</div></article>';
    }
    echo '</div>';
}

function qalam_210_ai_provider_options(): array { return array('openai'=>'OpenAI','deepseek'=>'DeepSeek','openrouter'=>'OpenRouter','google'=>'Google AI Studio','custom'=>'مزود مخصص'); }

function qalam_210_render_ai(): void {
    $opts=get_option('tutor_option',array());$opts=is_array($opts)?$opts:array();$provider=sanitize_key((string)($opts['qalam_ai_provider']??'openai'));$model=sanitize_text_field((string)($opts['qalam_ai_model']??''));$base=(string)($opts['qalam_ai_base_url']??'');$manual=sanitize_text_field((string)($opts['qalam_ai_model_manual']??''));$enabled='on'===(string)($opts['chatgpt_enable']??'off');$has_key=!empty($opts['chatgpt_api_key']);$models=array();if(class_exists('TutorPro\\TutorAI\\Helper'))$models=\TutorPro\TutorAI\Helper::get_cached_provider_models($provider,$base);
    $notice=sanitize_text_field(wp_unslash($_GET['notice']??''));$error=sanitize_text_field(wp_unslash($_GET['error']??''));
    echo '<div class="qalam-page-head"><div><span class="qalam-eyebrow">Qalam AI</span><h1>الذكاء الاصطناعي</h1><p>المزود والموديل ومفتاح API وتوليد الأسئلة وPDF في مركز واحد.</p></div><span class="qalam-dot-status '.($enabled&&$has_key?'on':'off').'">'.($enabled&&$has_key?'متصل':'غير متصل').'</span></div>';
    if($notice)echo '<div class="qalam-alert is-success">'.esc_html($notice).'</div>';if($error)echo '<div class="qalam-alert is-error">'.esc_html($error).'</div>';
    echo '<div class="qalam-dashboard-grid"><section class="qalam-panel"><div class="qalam-panel-head"><div><span>الاتصال</span><h2>إعداد مزود الذكاء الاصطناعي</h2></div></div><form class="qalam-settings-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="qalam_210_ai_save">';wp_nonce_field('qalam_210_ai_save','nonce');echo '<div class="qalam-form-grid"><label><span>تشغيل الخدمة</span><select name="chatgpt_enable"><option value="on" '.selected($enabled,true,false).'>مفعّلة</option><option value="off" '.selected($enabled,false,false).'>متوقفة</option></select></label><label><span>المزود</span><select name="provider">';foreach(qalam_210_ai_provider_options() as $k=>$v)echo '<option value="'.esc_attr($k).'" '.selected($provider,$k,false).'>'.esc_html($v).'</option>';echo '</select></label><label><span>مفتاح API</span><input type="password" name="api_key" autocomplete="new-password" placeholder="'.($has_key?'مفتاح محفوظ — اتركه فارغًا للاحتفاظ به':'أدخل مفتاح API').'" ></label><label><span>Base URL للمزود المخصص</span><input type="url" name="base_url" value="'.esc_attr($base).'" placeholder="https://provider.example.com/v1"></label><label><span>الموديل</span><select name="model"><option value="">اختر الموديل</option>';foreach($models as $m){$id=(string)($m['id']??'');if($id)echo '<option value="'.esc_attr($id).'" '.selected($model,$id,false).'>'.esc_html($m['label']??$id).'</option>';}if($model&&!in_array($model,wp_list_pluck($models,'id'),true))echo '<option selected value="'.esc_attr($model).'">'.esc_html($model).'</option>';echo '</select></label><label><span>Model ID يدوي</span><input name="manual_model" value="'.esc_attr($manual).'" placeholder="للمزود المخصص عند الحاجة"></label></div><div class="qalam-form-actions"><button class="qalam-primary-action" name="save" value="1">حفظ الإعدادات</button><button class="qalam-secondary-button" name="fetch_models" value="1">اختبار الاتصال وجلب الموديلات</button></div></form></section>';
    echo '<section class="qalam-panel qalam-brand-panel"><span class="qalam-eyebrow">أدوات الإنتاج</span><h2>توليد الأسئلة من النص وPDF</h2><p>بعد ضبط المزود، استخدم بنك الأسئلة لإنشاء دفعات أسئلة، استخراجها من PDF، تحديد الصعوبة، واستكمال الدفعات المتوقفة.</p><a class="qalam-primary-action" href="'.esc_url(qalam_210_dashboard_url('question-bank')).'#qalam-ai-question-generator">فتح مولد الأسئلة</a><div class="qalam-ai-summary"><div><span>المزود الحالي</span><strong>'.esc_html(qalam_210_ai_provider_options()[$provider]??$provider).'</strong></div><div><span>الموديل</span><strong>'.esc_html($model?:'لم يُحدد').'</strong></div><div><span>PDF</span><strong>'.(function_exists('qalam_feature_enabled')&&qalam_feature_enabled('pdf_question_generation')?'متاح':'غير متاح').'</strong></div></div></section></div>';
}

function qalam_210_ai_save(): void {
    check_admin_referer('qalam_210_ai_save','nonce');if(!current_user_can('qalam_manage_ai')&&!current_user_can('manage_tutor'))wp_die('غير مسموح.');
    if(!class_exists('TutorPro\\TutorAI\\Helper')){wp_safe_redirect(qalam_210_dashboard_url('ai',array('error'=>'محرك الذكاء الاصطناعي غير متاح.')));exit;}
    $provider=sanitize_key((string)wp_unslash($_POST['provider']??'openai'));$allowed=qalam_210_ai_provider_options();if(!isset($allowed[$provider]))$provider='openai';$new_key=trim((string)wp_unslash($_POST['api_key']??''));$model=sanitize_text_field(wp_unslash($_POST['model']??''));$manual=sanitize_text_field(wp_unslash($_POST['manual_model']??''));$base=trim((string)wp_unslash($_POST['base_url']??''));$enable='on'===sanitize_key((string)($_POST['chatgpt_enable']??'off'));
    $opts=get_option('tutor_option',array());$opts=is_array($opts)?$opts:array();$key=$new_key?:((string)($opts['chatgpt_api_key']??''));
    try{if('custom'===$provider)$base=\TutorPro\TutorAI\Helper::sanitize_custom_base_url($base);else $base='';if('custom'===$provider&&$manual)$model=$manual;if($model&&!preg_match('/^[A-Za-z0-9._:~\/\-]+$/',$model))throw new RuntimeException('معرّف الموديل غير صالح.');if($enable&&!$key)throw new RuntimeException('أدخل مفتاح API لتشغيل الخدمة.');
        if(!empty($_POST['fetch_models'])){$models=\TutorPro\TutorAI\Helper::fetch_provider_models($provider,$key,$base);if(!$models)throw new RuntimeException('تم الاتصال لكن لم يتم العثور على موديلات.');\TutorPro\TutorAI\Helper::cache_provider_models($provider,$base,$models);$ids=wp_list_pluck($models,'id');if(!$model||!in_array($model,$ids,true))$model=(string)$models[0]['id'];}
        $opts['chatgpt_enable']=$enable?'on':'off';$opts['chatgpt_api_key']=$key;$opts['qalam_ai_provider']=$provider;$opts['qalam_ai_model']=$model;$opts['qalam_ai_model_manual']=$manual;$opts['qalam_ai_base_url']=$base;update_option('tutor_option',$opts,false);
        wp_safe_redirect(qalam_210_dashboard_url('ai',array('notice'=>!empty($_POST['fetch_models'])?'تم الاتصال وجلب الموديلات بنجاح.':'تم حفظ إعدادات الذكاء الاصطناعي.')));exit;
    }catch(Throwable $e){wp_safe_redirect(qalam_210_dashboard_url('ai',array('error'=>$e->getMessage())));exit;}
}
add_action('admin_post_qalam_210_ai_save','qalam_210_ai_save');

function qalam_210_render_reports(): void {
    echo '<div class="qalam-page-head"><div><span class="qalam-eyebrow">التحليلات</span><h1>التقارير</h1><p>تقارير الدورات والطلاب والمبيعات والأداء من محرك التقارير الكامل.</p></div></div><div class="qalam-embedded-tool qalam-report-embed">';
    if(function_exists('tutor_report_instance')&&!empty(tutor_report_instance()->report))echo qalam_210_capture(array(tutor_report_instance()->report,'tutor_report'));else echo '<div class="qalam-empty"><strong>ملحق التقارير غير مفعّل.</strong></div>';echo '</div>';
}

/**
 * Return the native Tutor settings registry even when tutor()->options has not
 * been hydrated yet (for example during QA probes on init). This keeps the
 * Qalam settings surface and runtime verification on the same source of truth.
 */
/** Return a fully usable settings engine on both wp-admin and the standalone Qalam dashboard. */
function qalam_210_options_engine() {
    try {
        if ( function_exists( 'tutor' ) && isset( tutor()->options ) && is_object( tutor()->options )
            && method_exists( tutor()->options, 'get_setting_fields' ) && method_exists( tutor()->options, 'template' ) ) {
            return tutor()->options;
        }
        if ( class_exists( 'Tutor\Options_V2' ) ) {
            return new \Tutor\Options_V2( false );
        }
    } catch ( Throwable $e ) {
        do_action( 'qalam_dashboard_settings_engine_error', $e );
    }
    return null;
}

function qalam_210_setting_fields_data(): array {
    $options = qalam_210_options_engine();
    if ( ! $options || ! method_exists( $options, 'get_setting_fields' ) ) { return array(); }
    try {
        $data = $options->get_setting_fields();
        return is_array( $data ) ? $data : array();
    } catch ( Throwable $e ) {
        return array();
    }
}

/** Render the real native settings controls, with a blocks fallback for non-standard sections. */
function qalam_210_render_native_settings_section( array $section ): string {
    $options = qalam_210_options_engine();
    if ( ! $options ) { return ''; }
    $html = '';
    try {
        if ( method_exists( $options, 'template' ) && ! empty( $section['template'] ) ) {
            $html = (string) $options->template( $section );
        }
        // A custom/extended section may expose blocks without a normal template.
        if ( '' === trim( wp_strip_all_tags( $html ) ) && ! empty( $section['blocks'] ) && method_exists( $options, 'blocks' ) ) {
            foreach ( (array) $section['blocks'] as $block ) {
                if ( is_array( $block ) ) { $html .= (string) $options->blocks( $block ); }
            }
        }
    } catch ( Throwable $e ) {
        $html = '';
    }
    return qalam_210_rewrite_legacy_html( $html );
}

function qalam_210_flat_settings(): array {
    $data=qalam_210_setting_fields_data();$out=array();foreach((array)($data['option_fields']??array()) as $key=>$section){$out[$key]=$section;if(!empty($section['submenu'])&&is_array($section['submenu']))foreach($section['submenu'] as $subkey=>$sub)$out[$subkey]=$sub;}if(function_exists('qalam_230_filter_settings_for_user'))$out=qalam_230_filter_settings_for_user($out);return $out;
}

function qalam_210_render_settings(): void {
    $all=qalam_210_flat_settings();if(!$all){echo '<div class="qalam-empty"><strong>تعذر تحميل الإعدادات.</strong></div>';return;}$tab=sanitize_key((string)($_GET['tab']??'general'));if(!isset($all[$tab]))$tab=array_key_first($all);$section=$all[$tab];$saved=!empty($_GET['saved']);
    echo '<div class="qalam-page-head"><div><span class="qalam-eyebrow">ضبط المنصة</span><h1>الإعدادات</h1><p>إعدادات قلم ومحرك التعلم من نفس اللوحة، بدون الخروج من لوحة قلم.</p></div></div>';if($saved)echo '<div class="qalam-alert is-success">تم حفظ الإعدادات.</div>';
    echo '<div class="qalam-settings-layout"><aside class="qalam-settings-nav">';foreach($all as $key=>$row)echo '<a class="'.($key===$tab?'is-active':'').'" href="'.esc_url(qalam_210_dashboard_url('settings',array('tab'=>$key))).'">'.esc_html($row['label']??$key).'</a>';echo '</aside><section class="qalam-panel qalam-settings-panel">';
    $native_html=qalam_210_render_native_settings_section($section);
    echo '<div class="qalam-settings-section-title"><div><h2>'.esc_html($section['label']??$tab).'</h2><p>'.esc_html($section['desc']??'').'</p></div>'.(function_exists('qalam_260_settings_access_label')?'<span class="qalam-settings-access-badge">'.esc_html(qalam_260_settings_access_label($tab)).'</span>':'').'</div>';
    // Never present a fake-success empty settings card when the native renderer failed.
    $has_declared_fields = ! empty( $section['blocks'] );
    if ( $has_declared_fields && '' === trim( wp_strip_all_tags( $native_html ) ) ) {
        echo '<div class="qalam-alert is-error qalam-settings-render-error"><strong>تعذر عرض حقول هذا القسم.</strong><span>أعد تحميل الصفحة، وإذا استمرت المشكلة راجع حالة محرك الإعدادات.</span></div>';
        echo '</section></div>';
        return;
    }
    // Native operational settings (e.g. Tutor Pages regeneration) own their form.
    // Do not nest them in the Qalam save form; nested forms break submissions.
    $owns_form=false!==stripos($native_html,'<form');
    if($owns_form){echo '<div class="qalam-settings-fields qalam-settings-native-action">'.$native_html.'</div>';}
    else{echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="qalam-native-settings"><input type="hidden" name="action" value="qalam_210_settings_save"><input type="hidden" name="tab" value="'.esc_attr($tab).'">';wp_nonce_field('qalam_210_settings_save','nonce');echo '<div class="qalam-settings-fields">'.$native_html.'</div><div class="qalam-form-actions"><button class="qalam-primary-action">حفظ التغييرات</button></div></form>';}
    echo '</section></div>';
}
function qalam_210_setting_is_secret_key( string $key ): bool {
    return (bool) preg_match( '/(?:api[_-]?key|secret|password|token|private[_-]?key|client[_-]?secret)/i', $key );
}

/** Merge one settings section without erasing existing secrets when a masked field is submitted blank. */
function qalam_210_merge_settings_preserve_secrets( $old, $new, string $path = '' ) {
    if ( ! is_array( $new ) ) {
        $leaf = '' !== $path ? basename( str_replace( array('[',']'), '/', $path ) ) : '';
        if ( is_string( $new ) && '' === $new && qalam_210_setting_is_secret_key( $leaf ) && ! empty( $old ) ) { return $old; }
        return $new;
    }
    $out = is_array( $old ) ? $old : array();
    foreach ( $new as $key => $value ) {
        $key = (string) $key;
        $child_path = '' === $path ? $key : $path.'['.$key.']';
        $out[ $key ] = qalam_210_merge_settings_preserve_secrets( is_array($old) && array_key_exists($key,$old) ? $old[$key] : null, $value, $child_path );
    }
    return $out;
}

function qalam_210_settings_save(): void {
    check_admin_referer( 'qalam_210_settings_save', 'nonce' );
    if ( ! current_user_can( 'qalam_manage_settings' ) && ! current_user_can( 'administrator' ) ) { wp_die( 'غير مسموح.' ); }
    $tab    = sanitize_key( (string) ( $_POST['tab'] ?? 'general' ) );
    if ( function_exists( 'qalam_230_user_can_manage_settings_tab' ) && ! qalam_230_user_can_manage_settings_tab( $tab ) ) { wp_die( 'غير مسموح بتعديل هذا القسم.' ); }
    $posted = isset( $_POST['tutor_option'] ) ? (array) wp_unslash( $_POST['tutor_option'] ) : array();
    $old    = get_option( 'tutor_option', array() );
    $old    = is_array( $old ) ? $old : array();

    // Qalam saves only the active settings section, therefore merge rather than replace
    // the entire Tutor options array. Blank secret fields retain their previous value.
    $option = qalam_210_merge_settings_preserve_secrets( $old, $posted );
    if ( ! isset( $option['tutor_login_page'] ) && isset( $old['tutor_login_page'] ) ) { $option['tutor_login_page'] = $old['tutor_login_page']; }

    do_action( 'tutor_option_save_before', $option );
    if ( class_exists( 'TUTOR\\Input' ) ) {
        $option = \TUTOR\Input::sanitize_array( $option, array(
            'payment_settings'                         => 'wp_kses_post',
            'tutor_bank_transfer_withdraw_instruction' => 'sanitize_textarea_field',
            'certificate_showcase_desc'                => 'sanitize_textarea_field',
            'invoice_from_address'                     => 'sanitize_textarea_field',
            'fees_name'                                => 'sanitize_textarea_field',
        ) );
    }
    $option = apply_filters( 'tutor_option_input', $option );

    // Keep Tutor's settings history/rollback contract intact even though the UI is Qalam.
    $time = strtotime( 'now' ) + ( 6 * 60 * 60 );
    $snapshot = array(
        'datetime'     => $time,
        'history_date' => gmdate( 'j M, Y, g:i a', $time ),
        'datatype'     => 'saved',
        'dataset'      => $option,
    );
    $history = get_option( 'tutor_settings_log', array() );
    $history = is_array( $history ) ? $history : array();
    $history = array_slice( array_merge( array( 'tutor-saved-'.$time => $snapshot ), $history ), 0, 10, true );

    update_option( 'tutor_settings_log', $history, false );
    update_option( 'tutor_option', $option, false );
    update_option( 'tutor_option_update_time', gmdate( 'j M, Y, g:i a', $time ) );

    if ( $old !== $option ) {
        foreach ( $option as $key => $value ) {
            $from = $old[ $key ] ?? null;
            if ( $from !== $value ) { do_action( "tutor_option_{$key}_changed", $from, $value ); }
        }
    }
    do_action( 'tutor_option_save_after' );
    wp_safe_redirect( qalam_210_dashboard_url( 'settings', array( 'tab'=>$tab, 'saved'=>1 ) ) ); exit;
}
add_action( 'admin_post_qalam_210_settings_save', 'qalam_210_settings_save' );

function qalam_210_render_section( string $section ): void {
    switch($section){case'courses':qalam_210_render_courses();break;case'students':qalam_210_render_students();break;case'exams':qalam_210_render_exams();break;case'addons':qalam_210_render_addons();break;case'ai':qalam_210_render_ai();break;case'question-bank':qalam_210_render_question_bank();break;case'reports':qalam_210_render_reports();break;case'commerce':if(function_exists('qalam_220_render_commerce'))qalam_220_render_commerce();else qalam_210_render_home();break;case'settings':qalam_210_render_settings();break;case'manage':if(function_exists('qalam_220_render_manage'))qalam_220_render_manage();else qalam_210_render_home();break;default:qalam_210_render_home();}
}

function qalam_210_render_dashboard(): void {
    $section=sanitize_key((string)get_query_var('qalam_admin'));if(!$section)return;if(!is_user_logged_in()){if(function_exists('qalam_220_login_url')){wp_safe_redirect(qalam_220_login_url(qalam_210_dashboard_url($section)));exit;}auth_redirect();exit;}if(!current_user_can('qalam_access_dashboard')&&!current_user_can('manage_tutor')&&!current_user_can('manage_tutor_instructor'))wp_die('غير مسموح.');
    $sections=qalam_210_sections();if(!isset($sections[$section]))$section='home';$config=$sections[$section];if(!current_user_can($config['capability'])&&!current_user_can('manage_tutor'))$section='home';if(!empty($config['feature'])&&!qalam_210_feature_group_visible($config['feature']))$section='home';
    qalam_210_prepare_section_assets($section);$asset=plugin_dir_url(TUTOR_FILE).'assets/';nocache_headers();status_header(200);
    ?><!doctype html><html <?php language_attributes();?> dir="rtl"><head><meta charset="<?php bloginfo('charset');?>"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title><?php $qalam_platform_name=function_exists('qalam_230_brand')?(string)(qalam_230_brand()['platform_name']??'قلم'):'قلم';echo esc_html(($sections[$section]['label']??'الرئيسية').' — '.$qalam_platform_name);?></title><?php wp_print_styles(array('dashicons'));?><?php wp_head();?><link rel="stylesheet" href="<?php echo esc_url($asset.'css/qalam-admin-shell.css?v='.rawurlencode(defined('QALAM_LMS_PRODUCT_VERSION')?QALAM_LMS_PRODUCT_VERSION:QALAM_210_VERSION));?>"></head><body class="qalam-admin-app qalam-admin-section-<?php echo esc_attr($section);?> <?php echo 'reports'===$section?'qalam-report-ar':'';?>"><div class="qalam-shell"><aside class="qalam-sidebar" id="qalam-sidebar"><div class="qalam-logo"><div class="qalam-logo-mark"><img src="<?php echo esc_url($asset.'images/qalam-mark.svg');?>" alt="Qalam LMS"></div><div><strong><?php echo esc_html(function_exists('qalam_230_brand')?(qalam_230_brand()['platform_name']??'قلم'):'قلم');?></strong><span>لوحة الإدارة</span></div></div><nav class="qalam-nav"><?php foreach($sections as $key=>$item){if(!empty($item['hidden']))continue;if(!current_user_can($item['capability'])&&!current_user_can('manage_tutor'))continue;if(!empty($item['feature'])&&!qalam_210_feature_group_visible($item['feature']))continue;?><a href="<?php echo esc_url(qalam_210_dashboard_url($key));?>" class="<?php echo $section===$key?'is-active':'';?>"><span><?php echo qalam_210_svg($item['icon']);?></span><b><?php echo esc_html($item['label']);?></b></a><?php }?></nav><div class="qalam-sidebar-foot"><a href="<?php echo esc_url(home_url('/'));?>" target="_blank">عرض الموقع</a><a href="<?php echo esc_url(wp_logout_url(function_exists('qalam_220_login_url')?qalam_220_login_url():home_url('/')));?>">تسجيل الخروج</a></div></aside><div class="qalam-main"><header class="qalam-topbar"><button class="qalam-mobile-menu" type="button" aria-label="القائمة">☰</button><div class="qalam-search"><span>⌕</span><input type="search" placeholder="ابحث في قلم..." data-qalam-global-search></div><?php $qalam_current_user=wp_get_current_user();$qalam_logout_url=wp_logout_url(function_exists('qalam_220_login_url')?qalam_220_login_url():home_url('/'));?><div class="qalam-user qalam-user-menu-wrap"><button class="qalam-user-toggle" type="button" data-qalam-user-toggle aria-haspopup="menu" aria-expanded="false"><span class="qalam-user-copy"><strong><?php echo esc_html($qalam_current_user->display_name);?></strong><span><?php echo qalam_210_user_is_platform_admin()?'إدارة المنصة':'معلم';?></span></span><?php echo get_avatar(get_current_user_id(),42);?><span class="qalam-user-chevron" aria-hidden="true">⌄</span></button><div class="qalam-user-menu" data-qalam-user-menu role="menu" aria-hidden="true"><div class="qalam-user-menu-head"><strong><?php echo esc_html($qalam_current_user->display_name);?></strong><span><?php echo esc_html($qalam_current_user->user_email);?></span></div><a role="menuitem" href="<?php echo esc_url(qalam_210_dashboard_url());?>">الرئيسية</a><a role="menuitem" href="<?php echo esc_url(home_url('/'));?>" target="_blank" rel="noopener">عرض الموقع</a><a role="menuitem" class="is-logout" href="<?php echo esc_url($qalam_logout_url);?>">تسجيل الخروج</a></div></div></header><main class="qalam-content"><?php qalam_210_render_section($section);?></main></div></div><script>window.QalamAdmin=<?php echo wp_json_encode(array('ajaxUrl'=>admin_url('admin-ajax.php'),'toggleNonce'=>wp_create_nonce('qalam_200_toggle_product'),'dashboard'=>qalam_210_dashboard_url(),'section'=>$section,'legacyRoutes'=>function_exists('qalam_220_client_admin_routes')?qalam_220_client_admin_routes():array()));?>;</script><script>window.ajaxurl=window.QalamAdmin.ajaxUrl;</script><script src="<?php echo esc_url($asset.'js/qalam-admin-shell.js?v='.rawurlencode(defined('QALAM_LMS_PRODUCT_VERSION')?QALAM_LMS_PRODUCT_VERSION:QALAM_210_VERSION));?>"></script><?php wp_footer();?></body></html><?php exit;
}
add_action('template_redirect','qalam_210_render_dashboard',0);
