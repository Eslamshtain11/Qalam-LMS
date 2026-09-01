<?php
/**
 * Qalam LMS 0.22.0 — remaining admin surfaces + standalone Qalam login.
 *
 * This layer closes the remaining customer-facing management links without
 * granting WordPress administrator capabilities. Native Tutor/Qalam engines
 * are reused inside /qalam-admin/ whenever possible.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_220_VERSION = '0.22.1-mobile-admin-freeze-hotfix';
const QALAM_220_SCHEMA_OPTION = 'qalam_220_schema';
const QALAM_220_SCHEMA_VALUE  = '1';

/** Product-level management registry. */
function qalam_220_surface_registry(): array {
    return array(
        'question_bank_suite'    => array( 'type'=>'route', 'section'=>'question-bank' ),
        'advanced_exams'         => array( 'type'=>'route', 'section'=>'exams' ),
        'artificial_intelligence'=> array( 'type'=>'route', 'section'=>'ai' ),
        'video_player'           => array( 'type'=>'settings', 'tab'=>'course' ),
        'certificates_suite'     => array( 'type'=>'certificates', 'page'=>'qalam-certificate-builder' ),
        'instructor_suite'       => array( 'type'=>'instructors', 'page'=>'tutor-instructors' ),
        'reports_suite'          => array( 'type'=>'route', 'section'=>'reports' ),
        'communications_suite'   => array( 'type'=>'settings_multi', 'tabs'=>array('tutor_notifications'=>'الإشعارات','email_notification'=>'البريد الإلكتروني') ),
        'account_access_suite'   => array( 'type'=>'settings_multi', 'tabs'=>array('authentication'=>'تسجيل الدخول','advanced'=>'الحساب والبريد') ),

        'video_ads'              => array( 'type'=>'callback', 'page'=>'qalam-video-ads', 'callback'=>'qalam_150_render_video_ads_admin' ),
        'gift_courses'           => array( 'type'=>'settings', 'tab'=>'course' ),
        'lesson_notes'           => array( 'type'=>'settings', 'tab'=>'course' ),
        'course_bundles'         => array( 'type'=>'course_bundles', 'page'=>'course-bundle' ),
        'subscriptions'          => array( 'type'=>'commerce', 'page'=>'tutor-subscriptions' ),
        'assignments'            => array( 'type'=>'native', 'page'=>'tutor-assignments' ),
        'content_drip'           => array( 'type'=>'course_setting', 'tab'=>'course' ),
        'course_preview'         => array( 'type'=>'course_setting', 'tab'=>'course' ),
        'course_attachments'     => array( 'type'=>'course_setting', 'tab'=>'course' ),
        'google_meet'            => array( 'type'=>'native', 'page'=>'google-meet' ),
        'calendar'               => array( 'type'=>'course_setting', 'tab'=>'course' ),
        'google_classroom'       => array( 'type'=>'native', 'page'=>'tutor-google-classroom' ),
        'zoom'                   => array( 'type'=>'native', 'page'=>'tutor_zoom' ),
        'manual_enrollments'     => array( 'type'=>'native', 'page'=>'enrollments' ),
        'gradebook'              => array( 'type'=>'native', 'page'=>'tutor_gradebook' ),
        'course_prerequisites'   => array( 'type'=>'course_setting', 'tab'=>'course' ),
        'buddypress'             => array( 'type'=>'external', 'label'=>'BuddyPress / BuddyBoss' ),
        'wc_subscriptions'       => array( 'type'=>'external', 'label'=>'اشتراكات المتجر الإلكتروني' ),
        'pmpro'                  => array( 'type'=>'settings', 'tab'=>'pm-pro' ),
        'restrict_content_pro'   => array( 'type'=>'external', 'label'=>'Restrict Content Pro' ),
        'weglot'                 => array( 'type'=>'external', 'label'=>'Weglot' ),
        'wpml'                   => array( 'type'=>'external', 'label'=>'WPML' ),
        'h5p'                    => array( 'type'=>'native', 'page'=>'tutor_h5p' ),
    );
}

function qalam_220_manage_url( string $feature, array $args = array() ): string {
    $args = array_merge( array( 'feature'=>sanitize_key( $feature ) ), $args );
    return qalam_210_dashboard_url( 'manage', $args );
}

function qalam_220_commerce_url( string $view = '', array $args = array() ): string {
    if ( $view && 'default' !== $view ) { $args = array_merge( array( 'view'=>sanitize_key($view) ), $args ); }
    return qalam_210_dashboard_url( 'commerce', $args );
}

/** True only when Tutor/Qalam native e-commerce owns the checkout/order tables. */
function qalam_220_native_ecommerce_enabled(): bool {
    return function_exists( 'tutor_utils' ) && (bool) tutor_utils()->is_monetize_by_tutor();
}

/** Marketplace/withdrawals only make sense when marketplace mode is enabled. */
function qalam_220_marketplace_enabled(): bool {
    return function_exists( 'tutor_utils' ) && (bool) tutor_utils()->get_option( 'enable_course_marketplace' );
}


/** Routes used by the shell to catch links generated at runtime by Tutor React apps. */
function qalam_220_client_admin_routes(): array {
    return array(
        'pages'=>array(
            // Core/legacy Qalam surfaces. Keep these here too because React may
            // create links after the PHP output-rewrite pass has already run.
            'tutor'=>qalam_210_dashboard_url('courses'),
            'create-course'=>qalam_210_dashboard_url('courses',array('builder'=>1)),
            'qalam-question-bank'=>qalam_210_dashboard_url('question-bank'),
            'tutor-content-bank'=>qalam_210_dashboard_url('question-bank',array('mode'=>'content-bank')),
            'qalam-quiz-builder'=>qalam_210_dashboard_url('exams'),
            'qalam-student-profile'=>qalam_210_dashboard_url('students'),
            'tutor_report'=>qalam_210_dashboard_url('reports'),
            'tutor_settings'=>qalam_210_dashboard_url('settings'),
            'tutor-addons'=>qalam_210_dashboard_url('addons'),
            'tutor_zoom'=>qalam_220_manage_url('zoom'),
            'google-meet'=>qalam_220_manage_url('google_meet'),
            'tutor-google-classroom'=>qalam_220_manage_url('google_classroom'),
            'tutor-assignments'=>qalam_220_manage_url('assignments'),
            'tutor_gradebook'=>qalam_220_manage_url('gradebook'),
            'enrollments'=>qalam_220_manage_url('manual_enrollments'),
            'tutor_h5p'=>qalam_220_manage_url('h5p'),
            'tutor-instructors'=>qalam_220_manage_url('instructor_suite'),
            'tutor_withdraw_requests'=>qalam_220_manage_url('instructor_suite',array('view'=>'withdrawals')),
            'qalam-certificate-builder'=>qalam_220_manage_url('certificates_suite'),
            'qalam-video-ads'=>qalam_220_manage_url('video_ads'),
            'course-bundle'=>qalam_220_manage_url('course_bundles'),
            'tutor-subscriptions'=>qalam_220_commerce_url(),
            'tutor_orders'=>qalam_220_commerce_url('orders'),
            'tutor_coupons'=>qalam_220_commerce_url('coupons'),
        ),
        'users'=>qalam_210_dashboard_url('students'),
        'instructors'=>qalam_220_manage_url('instructor_suite'),
        'courses'=>qalam_210_dashboard_url('courses'),
        'bundles'=>qalam_220_manage_url('course_bundles'),
    );
}

/** Request-level helper usable even while bundled Pro is booting (before WP query parsing). */
function qalam_220_request_path(): string {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    return untrailingslashit( (string) wp_parse_url( $uri, PHP_URL_PATH ) );
}

function qalam_220_is_qalam_surface_context( string $feature = '' ): bool {
    $path = qalam_220_request_path();
    $manage_path = untrailingslashit( (string) wp_parse_url( qalam_210_dashboard_url( 'manage' ), PHP_URL_PATH ) );
    $commerce_path = untrailingslashit( (string) wp_parse_url( qalam_210_dashboard_url( 'commerce' ), PHP_URL_PATH ) );
    if ( ! $path || ! in_array( $path, array( $manage_path, $commerce_path ), true ) ) { return false; }
    if ( '' === $feature ) { return true; }
    if ( $path !== $manage_path ) { return false; }
    $requested = sanitize_key( (string) ( $_GET['feature'] ?? '' ) );
    return sanitize_key( $feature ) === $requested;
}

function qalam_220_is_admin_surface_context( string $feature = '' ): bool {
    return is_admin() || qalam_220_is_qalam_surface_context( $feature );
}

function qalam_220_google_meet_callback_url(): string {
    return untrailingslashit( home_url( '/qalam-google-meet-callback/' ) );
}

function qalam_220_is_google_meet_callback_request(): bool {
    $callback_path = untrailingslashit( (string) wp_parse_url( qalam_220_google_meet_callback_url(), PHP_URL_PATH ) );
    return '' !== $callback_path && qalam_220_request_path() === $callback_path;
}

/** Resolve the correct Qalam destination for every product catalog card. */
function qalam_220_product_manage_url( string $feature ): string {
    $registry = qalam_220_surface_registry();
    if ( empty( $registry[ $feature ] ) ) { return qalam_220_manage_url( $feature ); }
    $surface = $registry[ $feature ];
    $type = (string) ( $surface['type'] ?? '' );
    if ( 'subscriptions' === $feature ) { return qalam_220_commerce_url(); }
    if ( 'route' === $type ) {
        return qalam_210_dashboard_url( (string) $surface['section'] );
    }
    if ( 'settings' === $type ) {
        return qalam_210_dashboard_url( 'settings', array( 'tab'=>(string)$surface['tab'] ) );
    }
    return qalam_220_manage_url( $feature );
}

/** Find an add-on callback from Tutor's mature admin menu registry. */
function qalam_220_find_native_menu_item( string $slug ): array {
    $menu = apply_filters( 'tutor_admin_menu', array() );
    foreach ( (array) $menu as $group ) {
        foreach ( (array) $group as $item ) {
            if ( ! is_array( $item ) ) { continue; }
            if ( $slug === (string) ( $item['menu_slug'] ?? '' ) ) { return $item; }
        }
    }
    // Core instructor/withdrawal management is created directly by TUTOR\Admin
    // rather than through tutor_admin_menu filters, so expose the mature callbacks.
    if ( isset( tutor()->admin ) ) {
        if ( 'tutor-instructors' === $slug && is_callable( array( tutor()->admin, 'tutor_instructors' ) ) ) {
            return array( 'menu_slug'=>'tutor-instructors', 'callback'=>array( tutor()->admin, 'tutor_instructors' ) );
        }
        if ( 'tutor_withdraw_requests' === $slug && is_callable( array( tutor()->admin, 'withdraw_requests' ) ) ) {
            return array( 'menu_slug'=>'tutor_withdraw_requests', 'callback'=>array( tutor()->admin, 'withdraw_requests' ) );
        }
    }
    return array();
}

/** Map remaining legacy admin URLs to Qalam surfaces. */
function qalam_220_legacy_url_to_dashboard( string $url ): string {
    $decoded = html_entity_decode( $url, ENT_QUOTES, 'UTF-8' );
    $parts = wp_parse_url( $decoded );
    $script = basename( (string) ( $parts['path'] ?? '' ) );
    $query = array();
    parse_str( (string) ( $parts['query'] ?? '' ), $query );

    if ( 'users.php' === $script ) { return qalam_210_dashboard_url( 'students' ); }
    if ( 'user-edit.php' === $script ) { return qalam_220_manage_url( 'instructor_suite' ); }
    if ( in_array( $script, array( 'edit.php','post-new.php' ), true ) ) {
        $post_type = sanitize_key( (string) ( $query['post_type'] ?? '' ) );
        if ( $post_type === (string) ( tutor()->course_post_type ?? 'courses' ) || 'courses' === $post_type ) {
            return qalam_210_dashboard_url( 'courses', 'post-new.php' === $script ? array( 'builder'=>1 ) : array() );
        }
        if ( 'course-bundle' === $post_type ) { return qalam_220_manage_url( 'course_bundles' ); }
        if ( 'shop_order' === $post_type ) { return qalam_220_commerce_url( 'orders', array( 'source'=>'woocommerce' ) ); }
    }

    if ( 'admin.php' !== $script && false === strpos( $decoded, 'admin.php' ) ) { return $url; }
    $page = sanitize_key( (string) ( $query['page'] ?? '' ) );
    if ( 'tutor_settings' === $page ) {
        $tab = sanitize_key( (string) ( $query['tab_page'] ?? ( $query['tab'] ?? 'general' ) ) );
        return qalam_210_dashboard_url( 'settings', array( 'tab'=>$tab ) );
    }
    if ( 'qalam-feature-settings' === $page && ! empty( $query['feature'] ) ) {
        return qalam_220_product_manage_url( sanitize_key( (string) $query['feature'] ) );
    }
    // Core commerce and marketplace pages are product-adjacent admin surfaces.
    // Preserve their action/query context while moving the visible UI into Qalam.
    $special = array(
        'tutor_orders'           => array( 'section'=>'commerce', 'view'=>'orders' ),
        'tutor_coupons'          => array( 'section'=>'commerce', 'view'=>'coupons' ),
        'tutor_withdraw_requests'=> array( 'feature'=>'instructor_suite', 'view'=>'withdrawals' ),
    );
    if ( isset( $special[ $page ] ) ) {
        $dest = $special[ $page ];
        unset( $query['page'] );
        $query['view'] = $dest['view'];
        if ( ! empty( $dest['section'] ) && 'commerce' === $dest['section'] ) { return qalam_210_dashboard_url( 'commerce', $query ); }
        return qalam_220_manage_url( $dest['feature'], $query );
    }
    if ( 'tutor-subscriptions' === $page ) {
        unset( $query['page'] );
        return qalam_210_dashboard_url( 'commerce', $query );
    }
    $map = array(
        'tutor_zoom'=>'zoom',
        'google-meet'=>'google_meet',
        'tutor-google-classroom'=>'google_classroom',
        'tutor-assignments'=>'assignments',
        'tutor_gradebook'=>'gradebook',
        'enrollments'=>'manual_enrollments',
        'tutor_h5p'=>'h5p',
        'tutor-instructors'=>'instructor_suite',
        'qalam-certificate-builder'=>'certificates_suite',
        'qalam-video-ads'=>'video_ads',
        'course-bundle'=>'course_bundles',
    );
    if ( isset( $map[ $page ] ) ) {
        unset( $query['page'] );
        return qalam_220_manage_url( $map[ $page ], $query );
    }
    if ( 'tutor-tools' === $page && 'import_export' === sanitize_key( (string) ( $query['sub_page'] ?? '' ) ) ) {
        return qalam_210_dashboard_url( 'question-bank', array( 'mode'=>'content-bank' ) );
    }
    return $url;
}

function qalam_220_map_managed_redirect( string $location, int $status = 302 ): string {
    if ( ! qalam_210_user_is_managed() ) { return $location; }
    return qalam_220_legacy_url_to_dashboard( $location );
}
add_filter( 'wp_redirect', 'qalam_220_map_managed_redirect', 2, 2 );

/** True when the Qalam shell is rendering the Course Bundle React editor. */
function qalam_220_is_bundle_editor_request(): bool {
    return 'manage' === sanitize_key( (string) get_query_var( 'qalam_admin' ) )
        && 'course_bundles' === sanitize_key( (string) ( $_GET['feature'] ?? '' ) )
        && 'edit' === sanitize_key( (string) ( $_GET['action'] ?? '' ) )
        && absint( $_GET['id'] ?? 0 ) > 0;
}

/** Keep the Course Bundle React app inside Qalam instead of generating wp-admin/Tutor dashboard exits. */
function qalam_220_bundle_localize_data( array $data ): array {
    if ( ! qalam_220_is_bundle_editor_request() ) { return $data; }
    $bundle_list = qalam_220_manage_url( 'course_bundles' );
    $course_list = qalam_210_dashboard_url( 'courses' );
    $data['backend_bundle_list_url']  = $bundle_list;
    $data['frontend_bundle_list_url'] = $bundle_list;
    $data['course_bundle_list_page_url'] = $bundle_list;
    $data['backend_course_list_url']  = $course_list;
    $data['frontend_course_list_url'] = $course_list;
    $data['course_list_page_url']     = $course_list;
    $data['dashboard_url']            = qalam_210_dashboard_url();
    $data['current_page']             = 'course-bundle';
    return $data;
}

function qalam_220_bundle_inline_data( array $data ): array {
    if ( ! qalam_220_is_bundle_editor_request() ) { return $data; }
    $data['is_course_bundle_editor'] = true;
    $data['course_bundle_list_page_url'] = qalam_220_manage_url( 'course_bundles' );
    return $data;
}

/** Keep React/native engine navigation in Qalam for every embedded admin surface. */
function qalam_220_localize_manage_data( array $data ): array {
    if ( ! qalam_220_is_qalam_surface_context() ) { return $data; }
    $data['dashboard_url'] = qalam_210_dashboard_url();
    $data['course_list_page_url'] = qalam_210_dashboard_url( 'courses' );
    $data['backend_course_list_url'] = qalam_210_dashboard_url( 'courses' );
    $data['backend_bundle_list_url'] = qalam_220_manage_url( 'course_bundles' );
    $data['qalam_admin_surface'] = true;
    return $data;
}
add_filter( 'tutor_localize_data', 'qalam_220_localize_manage_data', 998 );

/** Enqueue the native add-on assets on the standalone dashboard. */
function qalam_220_prepare_manage_assets(): void {
    $feature = sanitize_key( (string) ( $_GET['feature'] ?? '' ) );
    $registry = qalam_220_surface_registry();
    if ( ! $feature || empty( $registry[ $feature ] ) ) { return; }
    $surface = $registry[ $feature ];
    $type = (string) ( $surface['type'] ?? '' );
    $page = sanitize_key( (string) ( $surface['page'] ?? '' ) );
    $view = sanitize_key( (string) ( $_GET['view'] ?? '' ) );
    if ( 'instructors' === $type ) {
        $page = 'withdrawals' === $view ? 'tutor_withdraw_requests' : 'tutor-instructors';
    } elseif ( 'commerce' === $type ) {
        if ( 'orders' === $view ) { $page = 'tutor_orders'; }
        elseif ( 'coupons' === $view ) { $page = 'tutor_coupons'; }
        elseif ( 'payments' === $view ) { $page = 'tutor_settings'; $_GET['tab_page'] = 'monetization'; }
        else { $page = 'tutor-subscriptions'; }
    }
    if ( $page ) { $_GET['page'] = $page; }

    // Course Bundle is intentionally run as its mature frontend editor inside
    // the Qalam shell. Pretending this request is wp-admin would leak legacy
    // links and conflicts with the platform-manager security model.
    if ( 'course_bundles' === $type && qalam_220_is_bundle_editor_request() && class_exists( '\\TutorPro\\CourseBundle\\Frontend\\BundleBuilder' ) ) {
        $id = absint( $_GET['id'] ?? 0 );
        set_query_var( 'tutor_dashboard_page', \TutorPro\CourseBundle\Frontend\BundleBuilder::QUERY_PARAM );
        $_GET['bundle-id'] = $id;
        add_filter( 'tutor_localize_data', 'qalam_220_bundle_localize_data', 999 );
        add_filter( 'tutor_pro_course_bundle_inline_data', 'qalam_220_bundle_inline_data', 999 );
    }

    qalam_210_admin_styles();

    if ( $page ) {
        // Reuse native hooks first. Some add-ons only key off $_GET['page'].
        do_action( 'admin_enqueue_scripts', 'qalam_page_' . $page );
    }

    // These three add-ons hard-code is_admin()/the exact hook suffix, so their
    // native admin hook cannot fire on /qalam-admin/. Enqueue the same original
    // files explicitly; no duplicated replacement UI is introduced.
    if ( 'google_classroom' === $feature && function_exists( 'TUTOR_GC' ) ) {
        wp_enqueue_style( 'tutor-gc-dashboard-style', TUTOR_GC()->url . 'assets/css/classroom-dashboard.css', array(), TUTOR_PRO_VERSION );
        wp_enqueue_script( 'tutor-gc-dashboard-script', TUTOR_GC()->url . 'assets/js/classroom-dashboard.js', array( 'jquery' ), TUTOR_PRO_VERSION, true );
    }
    if ( 'gradebook' === $feature && function_exists( 'TUTOR_GB' ) ) {
        wp_enqueue_style( 'tutor-gradebook', TUTOR_GB()->url . 'assets/css/gradebook.css', array(), TUTOR_PRO_VERSION );
        wp_enqueue_script( 'tutor-gradebook', TUTOR_GB()->url . 'assets/js/gradebook.js', array( 'jquery' ), TUTOR_GB()->version, true );
    }
    if ( 'manual_enrollments' === $feature && function_exists( 'TUTOR_ENROLLMENTS' ) ) {
        wp_enqueue_style( 'enrollment-admin-style', TUTOR_ENROLLMENTS()->url . 'assets/css/admin.css', array(), TUTOR_PRO_VERSION );
        wp_enqueue_script( 'tutor-enrollment-admin-script', TUTOR_ENROLLMENTS()->url . 'assets/js/admin.js', array(), TUTOR_PRO_VERSION, true );
        if ( 'add_new' === sanitize_key( (string) ( $_GET['action'] ?? '' ) ) ) {
            wp_enqueue_script( 'tutor-create-enrollment', TUTOR_ENROLLMENTS()->url . 'assets/js/create-enrollment/index.js', array( 'wp-i18n', 'wp-element' ), TUTOR_PRO_VERSION, true );
        }
    }

    if ( 'course_bundles' === $type && qalam_220_is_bundle_editor_request() ) {
        // CourseBundle\Init already registered the mature frontend/common asset
        // hooks. With the Qalam bundle query-var set above, wp_head() executes
        // those hooks in the correct frontend-editor mode exactly once.
        wp_enqueue_editor();
        wp_enqueue_media();
    }
    if ( 'commerce' === $type && ! in_array( $view, array( 'orders','coupons','payments' ), true ) && class_exists( '\\TutorPro\\Subscription\\Assets' ) ) {
        $assets = new \TutorPro\Subscription\Assets();
        if ( method_exists( $assets, 'admin_script' ) ) { $assets->admin_script(); }
    }
}

function qalam_220_render_unavailable( string $message ): void {
    echo '<div class="qalam-panel qalam-surface-message"><h2>الخدمة غير متاحة</h2><p>'.esc_html($message).'</p><a class="qalam-secondary-action" href="'.esc_url(qalam_210_dashboard_url('addons')).'">الرجوع للملحقات</a></div>';
}

function qalam_220_render_native_surface( string $page ): void {
    $item = qalam_220_find_native_menu_item( $page );
    $callback = $item['callback'] ?? null;
    if ( ! is_callable( $callback ) ) {
        qalam_220_render_unavailable( 'المحرك موجود لكن واجهة الإدارة الأصلية لم تُسجل في الطلب الحالي.' );
        return;
    }
    echo '<div class="qalam-native-surface">';
    echo qalam_210_capture( static function() use ( $callback ) { call_user_func( $callback ); } );
    echo '</div>';
}

function qalam_220_bundle_post_type(): string {
    return isset( tutor()->bundle_post_type ) && tutor()->bundle_post_type ? (string) tutor()->bundle_post_type : 'course-bundle';
}

function qalam_220_render_course_bundles(): void {
    $action = sanitize_key( (string) ( $_GET['action'] ?? '' ) );
    $id = absint( $_GET['id'] ?? 0 );
    if ( 'edit' === $action && $id ) {
        if ( qalam_220_bundle_post_type() !== get_post_type( $id ) ) {
            qalam_220_render_unavailable( 'حزمة الدورات المطلوبة غير موجودة.' );
            return;
        }
        echo '<div class="qalam-course-bundle-builder qalam-native-surface"><div id="tutor-course-bundle-builder-root"></div></div>';
        return;
    }
    $posts = get_posts( array(
        'post_type'=>qalam_220_bundle_post_type(),
        'post_status'=>array('publish','draft','pending','private'),
        'posts_per_page'=>100,
        'orderby'=>'modified',
        'order'=>'DESC',
    ) );
    echo '<div class="qalam-page-head"><div><span class="qalam-eyebrow">التجارة</span><h1>حزم الدورات</h1><p>إنشاء وتعديل حزم الدورات من داخل لوحة قلم.</p></div><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="qalam_220_create_bundle">';
    wp_nonce_field( 'qalam_220_create_bundle', 'nonce' );
    echo '<button class="qalam-primary-action" type="submit">إنشاء حزمة جديدة</button></form></div><div class="qalam-panel qalam-table">';
    if ( ! $posts ) { echo '<div class="qalam-empty"><strong>لسه مفيش حزم دورات.</strong><span>أنشئ أول حزمة من الزر بالأعلى.</span></div>'; }
    foreach ( $posts as $post ) {
        $edit = qalam_220_manage_url( 'course_bundles', array( 'action'=>'edit','id'=>(int)$post->ID ) );
        $delete = wp_nonce_url( admin_url( 'admin-post.php?action=qalam_220_trash_bundle&id='.(int)$post->ID ), 'qalam_220_trash_bundle_'.(int)$post->ID );
        echo '<div class="qalam-row"><div class="qalam-row-main"><div class="qalam-row-symbol">'.qalam_210_svg('courses').'</div><div><h3>'.esc_html($post->post_title ?: 'حزمة بدون عنوان').'</h3><span>'.esc_html( ( ($status_object = get_post_status_object($post->post_status)) && isset($status_object->label) ) ? $status_object->label : $post->post_status ).' · '.esc_html(get_the_modified_date('', $post)).'</span></div></div><div class="qalam-inline-actions"><a class="qalam-row-action" href="'.esc_url($edit).'">تعديل</a><a class="qalam-row-action is-danger" href="'.esc_url($delete).'">حذف</a></div></div>';
    }
    echo '</div>';
}

function qalam_220_create_bundle(): void {
    check_admin_referer( 'qalam_220_create_bundle', 'nonce' );
    if ( ! current_user_can( 'manage_tutor_instructor' ) && ! current_user_can( 'qalam_manage_addons' ) ) { wp_die( 'غير مسموح.' ); }
    $id = wp_insert_post( array( 'post_type'=>qalam_220_bundle_post_type(), 'post_status'=>'draft', 'post_title'=>'حزمة جديدة', 'post_author'=>get_current_user_id() ), true );
    if ( is_wp_error( $id ) ) { wp_die( esc_html( $id->get_error_message() ) ); }
    wp_safe_redirect( qalam_220_manage_url( 'course_bundles', array( 'action'=>'edit','id'=>(int)$id ) ) ); exit;
}
add_action( 'admin_post_qalam_220_create_bundle', 'qalam_220_create_bundle' );

function qalam_220_trash_bundle(): void {
    $id = absint( $_GET['id'] ?? 0 );
    check_admin_referer( 'qalam_220_trash_bundle_'.$id );
    if ( ! current_user_can( 'manage_tutor_instructor' ) && ! current_user_can( 'qalam_manage_addons' ) ) { wp_die( 'غير مسموح.' ); }
    if ( $id && qalam_220_bundle_post_type() === get_post_type( $id ) ) { wp_trash_post( $id ); }
    wp_safe_redirect( qalam_220_manage_url( 'course_bundles' ) ); exit;
}
add_action( 'admin_post_qalam_220_trash_bundle', 'qalam_220_trash_bundle' );

function qalam_220_surface_tabs( string $feature, array $tabs, string $active ): void {
    echo '<nav class="qalam-surface-tabs" aria-label="أقسام الإدارة">';
    foreach ( $tabs as $key=>$label ) {
        $url = 'subscriptions' === $feature ? qalam_220_commerce_url( $key ) : qalam_220_manage_url( $feature, 'default' === $key ? array() : array( 'view'=>$key ) );
        echo '<a class="'.esc_attr($active===$key?'is-active':'').'" href="'.esc_url($url).'">'.esc_html($label).'</a>';
    }
    echo '</nav>';
}

function qalam_220_render_certificates(): void {
    $view = sanitize_key( (string) ( $_GET['view'] ?? 'builder' ) );
    if ( ! in_array( $view, array( 'builder','issued','settings' ), true ) ) { $view='builder'; }
    qalam_220_surface_tabs( 'certificates_suite', array( 'builder'=>'منشئ الشهادات','issued'=>'الشهادات الصادرة','settings'=>'إعدادات الشهادات' ), $view );
    if ( 'settings' === $view ) {
        echo '<div class="qalam-surface-grid"><a class="qalam-surface-card" href="'.esc_url(qalam_210_dashboard_url('settings',array('tab'=>'tutor_certificate'))).'"><strong>إعدادات الشهادات</strong><span>صفحة التحقق والتوقيع والبريد وخيارات العرض داخل قلم.</span></a></div>';
        return;
    }
    if ( 'builder' === $view ) {
        if ( function_exists( 'qalam_060_render_certificate_builder' ) ) { echo '<div class="qalam-embedded-tool">'.qalam_210_capture('qalam_060_render_certificate_builder').'</div>'; }
        else { qalam_220_render_unavailable( 'منشئ الشهادات غير متاح.' ); }
        return;
    }
    if ( ! class_exists( '\\TUTOR_CERT\\Certificate' ) ) { qalam_220_render_unavailable( 'ملحق الشهادات غير محمل.' ); return; }
    $rows = get_comments( array( 'type'=>'course_completed', 'status'=>'approve', 'number'=>100, 'orderby'=>'comment_date_gmt', 'order'=>'DESC' ) );
    $cert = new \TUTOR_CERT\Certificate( true );
    echo '<div class="qalam-panel qalam-table">';
    if ( ! $rows ) { echo '<div class="qalam-empty"><strong>لسه مفيش شهادات صادرة.</strong></div>'; }
    foreach ( $rows as $completion ) {
        $course_id = absint( $completion->comment_post_ID ?? 0 );
        $user_id = absint( $completion->user_id ?? 0 );
        if ( ! $user_id && is_numeric( $completion->comment_author ?? '' ) ) { $user_id = absint( $completion->comment_author ); }
        if ( ! $course_id || ! $user_id ) { continue; }
        $user = get_userdata( $user_id );
        $revoked = '1' === (string) get_comment_meta( (int)$completion->comment_ID, '_qalam_certificate_revoked', true );
        $url = '';
        if ( ! $revoked ) { try { $url = (string) $cert->get_certificate( $course_id, false, $user_id ); } catch ( Throwable $e ) { $url=''; } }
        echo '<article class="qalam-row"><div class="qalam-row-main"><div class="qalam-row-symbol">'.qalam_210_svg('courses').'</div><div><h3>'.esc_html(get_the_title($course_id)?:'شهادة إتمام').'</h3><span>'.esc_html($user?$user->display_name:'طالب #'.$user_id).' · '.esc_html(mysql2date('j/n/Y g:i a',(string)$completion->comment_date)).'</span></div></div><div class="qalam-inline-actions">';
        echo '<span class="qalam-dot-status '.($revoked?'off':'on').'">'.($revoked?'ملغاة':'فعالة').'</span>';
        if ( $url ) { echo '<a class="qalam-row-action" target="_blank" rel="noopener" href="'.esc_url($url).'">عرض</a>'; }
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="qalam_certificate_revoke"><input type="hidden" name="completion_id" value="'.esc_attr((int)$completion->comment_ID).'"><input type="hidden" name="revoke" value="'.($revoked?'0':'1').'">';
        wp_nonce_field( 'qalam_certificate_revoke_'.(int)$completion->comment_ID );
        echo '<button class="qalam-row-action '.($revoked?'':'is-danger').'" type="submit">'.($revoked?'إعادة تفعيل':'إلغاء').'</button></form></div></article>';
    }
    echo '</div>';
}

function qalam_220_render_instructor_suite(): void {
    $view = sanitize_key( (string) ( $_GET['view'] ?? 'default' ) );
    if ( ! in_array( $view, array( 'default','withdrawals','settings' ), true ) ) { $view='default'; }
    qalam_220_surface_tabs( 'instructor_suite', array( 'default'=>'المعلمون','withdrawals'=>'طلبات السحب','settings'=>'السوق والعمولات' ), $view );
    if ( 'settings' === $view ) {
        echo '<div class="qalam-surface-grid"><a class="qalam-surface-card" href="'.esc_url(qalam_210_dashboard_url('settings',array('tab'=>'monetization'))).'"><strong>السوق والعمولات</strong><span>العمولات وطرق السحب وإعدادات تعدد المعلمين.</span></a><a class="qalam-surface-card" href="'.esc_url(qalam_210_dashboard_url('courses')).'"><strong>توزيع المعلمين</strong><span>إضافة المدرسين المشاركين وتغيير المدرس الأساسي من منشئ الدورة.</span></a></div>';
        return;
    }
    if ( ! qalam_220_marketplace_enabled() ) {
        echo '<div class="qalam-panel qalam-surface-message"><h2>سوق المعلمين غير مفعّل</h2><p>فعّل وضع سوق الدورات أولًا لعرض المعلمين وطلبات السحب والعمولات.</p><a class="qalam-secondary-action" href="'.esc_url(qalam_210_dashboard_url('settings',array('tab'=>'monetization'))).'">فتح إعدادات السوق</a></div>';
        return;
    }
    $page = 'withdrawals' === $view ? 'tutor_withdraw_requests' : 'tutor-instructors';
    qalam_220_render_native_surface( $page );
}

function qalam_220_prepare_commerce_assets(): void {
    $view = sanitize_key( (string) ( $_GET['view'] ?? 'default' ) );
    if ( ! in_array( $view, array( 'default','orders','coupons','payments' ), true ) ) { $view='default'; }
    if ( ! qalam_220_native_ecommerce_enabled() && in_array( $view, array( 'default','orders','coupons' ), true ) ) {
        qalam_210_admin_styles();
        return;
    }
    if ( 'orders' === $view ) { $_GET['page']='tutor_orders'; }
    elseif ( 'coupons' === $view ) { $_GET['page']='tutor_coupons'; }
    elseif ( 'payments' === $view ) { $_GET['page']='tutor_settings'; $_GET['tab_page']='ecommerce_payment'; }
    else { $_GET['page']='tutor-subscriptions'; }
    qalam_210_admin_styles();
    do_action( 'admin_enqueue_scripts', 'qalam_page_' . sanitize_key((string)$_GET['page']) );
    if ( 'default' === $view && class_exists( '\TutorPro\Subscription\Assets' ) ) {
        $assets = new \TutorPro\Subscription\Assets();
        if ( method_exists( $assets, 'admin_script' ) ) { $assets->admin_script(); }
    }
}

function qalam_220_render_commerce(): void {
    $view = sanitize_key( (string) ( $_GET['view'] ?? 'default' ) );
    if ( ! in_array( $view, array( 'default','orders','coupons','payments' ), true ) ) { $view='default'; }
    qalam_220_surface_tabs( 'subscriptions', array( 'default'=>'الاشتراكات','orders'=>'الطلبات','coupons'=>'الكوبونات','payments'=>'الدفع والضرائب' ), $view );
    if ( 'payments' === $view ) {
        echo '<div class="qalam-surface-grid"><a class="qalam-surface-card" href="'.esc_url(qalam_210_dashboard_url('settings',array('tab'=>'ecommerce_payment'))).'"><strong>إعدادات الدفع</strong><span>طريقة تحقيق الدخل وبوابات الدفع وإعدادات السوق.</span></a><a class="qalam-surface-card" href="'.esc_url(qalam_210_dashboard_url('settings',array('tab'=>'ecommerce_tax'))).'"><strong>الضرائب</strong><span>إعدادات الضرائب في محرك التجارة.</span></a></div>';
        return;
    }
    if ( ! qalam_220_native_ecommerce_enabled() ) {
        echo '<div class="qalam-panel qalam-surface-message"><h2>محرك التجارة الحالي ليس محرك قلم</h2><p>الطلبات والكوبونات والاشتراكات الأصلية تُدار من قلم عندما يكون محرك قلم الأصلي هو المسؤول عن التجارة. وعند استخدام إضافة تجارة خارجية تظل إعداداتها العامة مسؤولية مشرف الصيانة، من دون توسيع صلاحيات مدير المنصة.</p><a class="qalam-secondary-action" href="'.esc_url(qalam_210_dashboard_url('settings',array('tab'=>'monetization'))).'">اختيار محرك تحقيق الدخل</a></div>';
        return;
    }
    if ( 'orders' === $view ) { qalam_220_render_native_surface( 'tutor_orders' ); return; }
    if ( 'coupons' === $view ) { qalam_220_render_native_surface( 'tutor_coupons' ); return; }
    if ( ! class_exists( '\TutorPro\Subscription\Menu' ) || ! class_exists( '\TutorPro\Subscription\Subscription' ) || ! \TutorPro\Subscription\Subscription::is_enabled() ) {
        echo '<div class="qalam-panel qalam-surface-message"><h2>الاشتراكات غير مفعّلة</h2><p>فعّل ملحق الاشتراكات من صفحة ملحقات قلم لاستخدام الخطط والعضويات.</p><a class="qalam-secondary-action" href="'.esc_url(qalam_210_dashboard_url('addons')).'">فتح الملحقات</a></div>';
        return;
    }
    $_GET['page'] = 'tutor-subscriptions';
    $menu = new \TutorPro\Subscription\Menu( false );
    echo '<div class="qalam-native-surface">'.qalam_210_capture( static function() use ( $menu ) { $menu->admin_subscriptions_view(); } ).'</div>';
}

function qalam_220_render_settings_multi( array $tabs ): void {
    echo '<div class="qalam-surface-grid">';
    foreach ( $tabs as $tab=>$label ) {
        echo '<a class="qalam-surface-card" href="'.esc_url(qalam_210_dashboard_url('settings',array('tab'=>$tab))).'"><strong>'.esc_html($label).'</strong><span>فتح الإعدادات داخل لوحة قلم</span></a>';
    }
    echo '</div>';
}

function qalam_220_render_course_setting( array $surface ): void {
    $tab = sanitize_key( (string) ( $surface['tab'] ?? 'course' ) );
    echo '<div class="qalam-surface-grid"><a class="qalam-surface-card" href="'.esc_url(qalam_210_dashboard_url('settings',array('tab'=>$tab))).'"><strong>الإعدادات العامة</strong><span>ضبط إعدادات الميزة داخل قلم</span></a><a class="qalam-surface-card" href="'.esc_url(qalam_210_dashboard_url('courses')).'"><strong>الدورات</strong><span>تطبيق الميزة على دورة من منشئ قلم</span></a></div>';
}

function qalam_220_render_external_surface( string $feature, array $product, array $surface ): void {
    $missing = (array) ( $product['missing'] ?? array() );
    $label = (string) ( $surface['label'] ?? ( $product['name'] ?? $feature ) );
    echo '<div class="qalam-panel qalam-surface-message"><h2>'.esc_html($label).'</h2>';
    if ( $missing ) {
        echo '<p>التكامل يحتاج الإضافة الخارجية التالية قبل أن يعمل:</p><ul>';
        foreach ( $missing as $name ) { echo '<li>'.esc_html((string)$name).'</li>'; }
        echo '</ul>';
    } else {
        echo '<p>التكامل محمّل. الإعدادات التي تخص قلم تُدار من هنا، أما الإعدادات العامة للإضافة الخارجية فتظل مسؤولية مشرف الصيانة وليست ضمن صلاحيات مدير المنصة.</p>';
    }
    echo '<a class="qalam-secondary-action" href="'.esc_url(qalam_210_dashboard_url('settings')).'">إعدادات قلم</a></div>';
}

function qalam_220_render_manage(): void {
    if ( ! current_user_can( 'qalam_manage_addons' ) && ! current_user_can( 'manage_tutor' ) ) { wp_die( 'غير مسموح.' ); }
    $feature = sanitize_key( (string) ( $_GET['feature'] ?? '' ) );
    $catalog = function_exists( 'qalam_200_product_catalog' ) ? qalam_200_product_catalog() : array();
    if ( ! $feature || empty( $catalog[ $feature ] ) ) { qalam_220_render_unavailable( 'الملحق المطلوب غير موجود.' ); return; }
    $product = $catalog[ $feature ];
    if ( empty( $product['access']['allowed'] ) ) { qalam_220_render_unavailable( (string) ( $product['access']['reason'] ?? 'الخدمة غير متاحة في الباقة الحالية.' ) ); return; }
    if ( empty( $product['effective_enabled'] ) ) { qalam_220_render_unavailable( 'فعّل الملحق أولًا من صفحة الملحقات.' ); return; }
    $registry = qalam_220_surface_registry();
    $surface = $registry[ $feature ] ?? array( 'type'=>'external' );
    $type = (string) ( $surface['type'] ?? '' );

    echo '<a class="qalam-back-link" href="'.esc_url(qalam_210_dashboard_url('addons')).'">← رجوع للملحقات</a>';
    echo '<div class="qalam-page-head"><div><span class="qalam-eyebrow">إدارة الملحق</span><h1>'.esc_html((string)($product['name'] ?? $feature)).'</h1><p>'.esc_html((string)($product['description'] ?? '')).'</p></div></div>';

    if ( 'native' === $type ) { qalam_220_render_native_surface( (string) $surface['page'] ); return; }
    if ( 'certificates' === $type ) { qalam_220_render_certificates(); return; }
    if ( 'instructors' === $type ) { qalam_220_render_instructor_suite(); return; }
    if ( 'commerce' === $type ) { qalam_220_render_commerce(); return; }
    if ( 'callback' === $type ) {
        $callback = $surface['callback'] ?? null;
        if ( is_callable( $callback ) ) { echo '<div class="qalam-embedded-tool">'.qalam_210_capture( $callback ).'</div>'; }
        else { qalam_220_render_unavailable( 'واجهة الإدارة غير متاحة.' ); }
        return;
    }
    if ( 'course_bundles' === $type ) { qalam_220_render_course_bundles(); return; }
    if ( 'settings_multi' === $type ) { qalam_220_render_settings_multi( (array) ( $surface['tabs'] ?? array() ) ); return; }
    if ( 'course_setting' === $type ) { qalam_220_render_course_setting( $surface ); return; }
    if ( 'external' === $type ) { qalam_220_render_external_surface( $feature, $product, $surface ); return; }
    qalam_220_render_unavailable( 'لم يتم تحديد واجهة إدارة مناسبة لهذه الخدمة.' );
}

/** Standalone Qalam login. */
function qalam_220_login_url( string $redirect_to = '' ): string {
    $url = home_url( '/qalam-login/' );
    return $redirect_to ? add_query_arg( 'redirect_to', $redirect_to, $url ) : $url;
}

function qalam_220_register_login_route(): void {
    add_rewrite_rule( '^qalam-login/?$', 'index.php?qalam_login=1', 'top' );
    add_rewrite_rule( '^qalam-google-meet-callback/?$', 'index.php?qalam_gmeet_callback=1', 'top' );
}
add_action( 'init', 'qalam_220_register_login_route', 3 );

function qalam_220_login_query_var( array $vars ): array { $vars[]='qalam_login'; $vars[]='qalam_gmeet_callback'; return $vars; }
add_filter( 'query_vars', 'qalam_220_login_query_var' );

function qalam_220_flush_login_route_once(): void {
    if ( get_option( 'qalam_220_routes_flushed' ) === QALAM_220_VERSION ) { return; }
    qalam_220_register_login_route(); flush_rewrite_rules( false );
    update_option( 'qalam_220_routes_flushed', QALAM_220_VERSION, false );
}
add_action( 'init', 'qalam_220_flush_login_route_once', 100 );

function qalam_220_render_login(): void {
    if ( ! get_query_var( 'qalam_login' ) ) { return; }
    if ( is_user_logged_in() && qalam_210_user_is_managed() ) { wp_safe_redirect( qalam_210_dashboard_url() ); exit; }
    $error = '';
    $redirect = isset($_REQUEST['redirect_to']) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ) : qalam_210_dashboard_url();
    $redirect = wp_validate_redirect( $redirect, qalam_210_dashboard_url() );
    if ( 'POST' === strtoupper( (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') ) ) {
        check_admin_referer( 'qalam_220_login', 'qalam_login_nonce' );
        $_POST['tutor_action'] = 'tutor_login'; // Preserve Tutor Auth/2FA security semantics.
        $creds = array(
            'user_login'=>isset($_POST['log']) ? sanitize_text_field(wp_unslash($_POST['log'])) : '',
            'user_password'=>isset($_POST['pwd']) ? (string) wp_unslash($_POST['pwd']) : '',
            'remember'=>!empty($_POST['rememberme']),
        );
        $user = wp_signon( $creds, is_ssl() );
        if ( is_wp_error( $user ) ) { $error = 'تعذر تسجيل الدخول. راجع بياناتك وحاول مرة أخرى.'; }
        else {
            $target = qalam_210_user_is_managed( $user ) ? $redirect : home_url('/');
            wp_safe_redirect( $target ); exit;
        }
    }
    nocache_headers(); status_header(200);
    $asset = plugin_dir_url(TUTOR_FILE).'assets/';
    $qalam_brand = function_exists('qalam_230_brand') ? qalam_230_brand() : array('platform_name'=>'قلم','tagline'=>'إدارة منصتك التعليمية من مكان واحد.','logo_url'=>'');
    $qalam_name = (string)($qalam_brand['platform_name']??'قلم');
    $qalam_tagline = (string)($qalam_brand['tagline']??'إدارة منصتك التعليمية من مكان واحد.');
    $qalam_logo = (string)($qalam_brand['logo_url']??'');
    ?><!doctype html><html <?php language_attributes();?> dir="rtl"><head><meta charset="<?php bloginfo('charset');?>"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title><?php echo esc_html('تسجيل الدخول — '.$qalam_name);?></title><?php wp_head();?><link rel="stylesheet" href="<?php echo esc_url($asset.'css/qalam-login.css?v='.rawurlencode(defined('QALAM_LMS_PRODUCT_VERSION')?QALAM_LMS_PRODUCT_VERSION:QALAM_220_VERSION));?>"></head><body class="qalam-login-page qalam-reference-ui qalam-platform-<?php echo esc_attr( function_exists( 'qalam_240_platform_type' ) ? qalam_240_platform_type() : 'academy' ); ?>"><button class="q-ref-mode-toggle qalam-login-mode-toggle" type="button" data-qalam-mode-toggle aria-label="تغيير الوضع"><span class="q-ref-sun">☀</span><span class="q-ref-moon">☾</span></button><main class="qalam-login-shell"><section class="qalam-login-brand"><?php if($qalam_logo):?><img class="qalam-login-logo" src="<?php echo esc_url($qalam_logo);?>" alt="<?php echo esc_attr($qalam_name);?>"><?php else:?><img class="qalam-login-logo qalam-login-logo-mark" src="<?php echo esc_url($asset.'images/qalam-mark.svg');?>" alt="Qalam"><?php endif;?><h1><?php echo esc_html('أهلًا بك في '.$qalam_name);?></h1><p><?php echo esc_html($qalam_tagline);?></p><span>بكل فخر ❤️ صنع في مصر</span></section><section class="qalam-login-card"><div><span class="qalam-login-eyebrow"><?php echo esc_html($qalam_name);?></span><h2>تسجيل الدخول</h2><p>اكتب بيانات حسابك للمتابعة إلى منصتك التعليمية.</p></div><?php if($error):?><div class="qalam-login-error"><?php echo esc_html($error);?></div><?php endif;?><form method="post" action="<?php echo esc_url(qalam_220_login_url($redirect));?>"><input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect);?>"><input type="hidden" name="tutor_action" value="tutor_login"><?php wp_nonce_field('qalam_220_login','qalam_login_nonce');?><label><span>اسم المستخدم أو البريد الإلكتروني</span><input type="text" name="log" autocomplete="username" required></label><label><span>كلمة المرور</span><input type="password" name="pwd" autocomplete="current-password" required></label><label class="qalam-remember"><input type="checkbox" name="rememberme" value="forever"><span>تذكرني</span></label><button type="submit">تسجيل الدخول</button></form><small>مؤسسة قلم للخدمات الإلكترونية</small></section></main><?php wp_footer();?></body></html><?php exit;
}
add_action( 'template_redirect', 'qalam_220_render_login', -1 );

function qalam_220_install_marker(): void {
    if ( get_option( QALAM_220_SCHEMA_OPTION ) !== QALAM_220_SCHEMA_VALUE ) {
        update_option( QALAM_220_SCHEMA_OPTION, QALAM_220_SCHEMA_VALUE, false );
    }
}
add_action( 'init', 'qalam_220_install_marker', 40 );
