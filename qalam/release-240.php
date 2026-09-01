<?php
/**
 * Qalam LMS 0.25.0 — reference calibration and mobile interaction rebuild.
 *
 * Two curated, non-client-editable public structures:
 * - academy: multi-instructor / multi-subject platform surface.
 * - individual: single-teacher personal education platform surface.
 *
 * Branding, content and palette are controlled only from the protected
 * WordPress Design Studio owned by Qalam operators.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_240_VERSION = '0.28.6';
const QALAM_240_SCHEMA_OPTION = 'qalam_240_schema';
const QALAM_240_SCHEMA_VALUE = '1';

// 0.24 owns the public homepage and global public shell.
remove_action( 'template_redirect', 'qalam_230_render_public_home', 1 );
remove_action( 'wp_enqueue_scripts', 'qalam_230_enqueue_platform_assets', 90 );
remove_action( 'wp_enqueue_scripts', 'qalam_230_strip_theme_assets_from_home', PHP_INT_MAX );
remove_action( 'wp_footer', 'qalam_230_legacy_brand_strip', 99 );

function qalam_240_platform_type(): string {
    $brand = qalam_230_brand();
    return 'individual' === ( $brand['platform_type'] ?? 'academy' ) ? 'individual' : 'academy';
}

function qalam_240_appearance_mode(): string {
    $brand = qalam_230_brand();
    $mode = sanitize_key( (string) ( $brand['appearance_mode'] ?? 'system' ) );
    return in_array( $mode, array( 'light', 'dark', 'system' ), true ) ? $mode : 'system';
}

function qalam_240_palette(): array {
    $brand = qalam_230_brand();
    $palette = qalam_230_current_palette();
    if ( ! empty( $brand['custom_primary'] ) ) { $palette['primary'] = $brand['custom_primary']; }
    if ( ! empty( $brand['custom_primary_2'] ) ) { $palette['primary_2'] = $brand['custom_primary_2']; }
    if ( ! empty( $brand['custom_accent'] ) ) { $palette['accent'] = $brand['custom_accent']; }
    return $palette;
}

function qalam_240_css_variables(): string {
    $p = qalam_240_palette();
    return implode( '', array(
        '--q-ref-primary:' . esc_attr( $p['primary'] ) . ';',
        '--q-ref-secondary:' . esc_attr( $p['primary_2'] ) . ';',
        '--q-ref-accent:' . esc_attr( $p['accent'] ) . ';',
        '--q-ref-light-bg:#f8f9fc;',
        '--q-ref-light-surface:#ffffff;',
        '--q-ref-light-text:#16171d;',
        '--q-ref-light-muted:#6f7280;',
        '--q-ref-light-border:#e8e9ef;',
        '--q-ref-dark-bg:#0d0f14;',
        '--q-ref-dark-surface:#151820;',
        '--q-ref-dark-surface-2:#1d212b;',
        '--q-ref-dark-text:#f7f8fa;',
        '--q-ref-dark-muted:#aeb3c2;',
        '--q-ref-dark-border:#2a2f3b;',
    ) );
}

function qalam_240_is_public_surface(): bool {
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) { return false; }
    if ( function_exists( 'qalam_210_is_dashboard_request' ) && qalam_210_is_dashboard_request() ) { return false; }
    return true;
}

function qalam_240_is_login_surface(): bool {
    return 1 === (int) get_query_var( 'qalam_login' );
}

function qalam_240_enqueue_assets(): void {
    if ( ! qalam_240_is_public_surface() ) { return; }
    wp_enqueue_style( 'qalam-reference-system', plugins_url( 'assets/css/qalam-reference-system.css', QALAM_LMS_FILE ), array(), QALAM_240_VERSION );
    wp_add_inline_style( 'qalam-reference-system', ':root{' . qalam_240_css_variables() . '}' );
    wp_enqueue_script( 'qalam-reference-system', plugins_url( 'assets/js/qalam-reference-system.js', QALAM_LMS_FILE ), array(), QALAM_240_VERSION, true );
    wp_localize_script( 'qalam-reference-system', 'QalamReferenceSystem', array(
        'defaultMode'  => qalam_240_appearance_mode(),
        'platformType' => qalam_240_platform_type(),
        'storageKey'   => 'qalam-color-mode',
    ) );
}
add_action( 'wp_enqueue_scripts', 'qalam_240_enqueue_assets', 20 );

function qalam_240_theme_boot_script(): void {
    if ( ! qalam_240_is_public_surface() ) { return; }
    $mode = qalam_240_appearance_mode();
    ?><script id="qalam-theme-boot">(function(){try{var d='<?php echo esc_js( $mode ); ?>',s=localStorage.getItem('qalam-color-mode'),m=s||d;if(m==='system'){m=window.matchMedia&&window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';}document.documentElement.dataset.qalamMode=m;document.documentElement.dataset.qalamPlatform='<?php echo esc_js( qalam_240_platform_type() ); ?>';}catch(e){document.documentElement.dataset.qalamMode='<?php echo 'dark' === $mode ? 'dark' : 'light'; ?>';}})();</script><?php
}
add_action( 'wp_head', 'qalam_240_theme_boot_script', 0 );

function qalam_240_body_classes( array $classes ): array {
    if ( ! qalam_240_is_public_surface() ) { return $classes; }
    $classes[] = 'qalam-reference-ui';
    $classes[] = 'qalam-platform-' . qalam_240_platform_type();
    return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'qalam_240_body_classes' );

function qalam_240_registration_url(): string {
    if ( function_exists( 'tutor_utils' ) ) {
        $page_id = (int) tutor_utils()->get_option( 'student_register_page', 0 );
        if ( $page_id ) {
            $url = get_permalink( $page_id );
            if ( $url ) { return $url; }
        }
    }
    if ( get_option( 'users_can_register' ) ) { return wp_registration_url(); }
    return function_exists( 'qalam_220_login_url' ) ? qalam_220_login_url() : wp_login_url();
}

/** Theme-independent header used by Tutor templates and Qalam public pages. */
function qalam_240_render_shell_header(): void {
    $brand = qalam_230_brand();
    $type = qalam_240_platform_type();
    $login = function_exists( 'qalam_220_login_url' ) ? qalam_220_login_url() : wp_login_url();
    $register = qalam_240_registration_url();
    $dashboard = function_exists( 'qalam_210_dashboard_url' ) && function_exists( 'qalam_210_user_is_managed' ) && qalam_210_user_is_managed() ? qalam_210_dashboard_url() : ( function_exists( 'tutor_utils' ) ? tutor_utils()->tutor_dashboard_url() : home_url( '/' ) );
    $logout = wp_logout_url( home_url( '/' ) );
    ?><!doctype html><html <?php language_attributes(); ?> dir="rtl" data-qalam-platform="<?php echo esc_attr( $type ); ?>"><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark"><?php wp_head(); ?></head><body <?php body_class( 'qalam-reference-body' ); ?>><?php wp_body_open(); ?><div class="q-ref-app"><header class="q-ref-header" data-qalam-header><div class="q-ref-container q-ref-header-inner"><a class="q-ref-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php if ( ! empty( $brand['logo_url'] ) ) : ?><img src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt="<?php echo esc_attr( $brand['platform_name'] ); ?>"><?php else : ?><img class="q-ref-qalam-mark" src="<?php echo esc_url( plugins_url( 'assets/images/qalam-mark.svg', QALAM_LMS_FILE ) ); ?>" alt="Qalam"><?php endif; ?><span><?php echo esc_html( $brand['platform_name'] ); ?></span></a><nav class="q-ref-nav" aria-label="التنقل الرئيسي"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">الرئيسية</a><?php if ( 'academy' === $type ) : ?><a href="<?php echo esc_url( home_url( '/#teachers' ) ); ?>">المدرسون</a><a href="<?php echo esc_url( home_url( '/#subjects' ) ); ?>">المواد</a><?php else : ?><a href="<?php echo esc_url( home_url( '/#about' ) ); ?>">عن المدرس</a><a href="<?php echo esc_url( home_url( '/#honor' ) ); ?>">المتفوقون</a><?php endif; ?><a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ?: home_url( '/courses/' ) ); ?>">الكورسات</a><a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>">تواصل معنا</a></nav><div class="q-ref-actions"><button class="q-ref-mode-toggle" type="button" data-qalam-mode-toggle aria-label="تغيير الوضع"><span class="q-ref-sun">☀</span><span class="q-ref-moon">☾</span></button><?php if ( is_user_logged_in() ) : ?><a class="q-ref-btn q-ref-btn-ghost" href="<?php echo esc_url( $dashboard ); ?>">لوحة التحكم</a><a class="q-ref-btn q-ref-btn-soft q-ref-auth-secondary" href="<?php echo esc_url( $logout ); ?>">تسجيل الخروج</a><?php else : ?><a class="q-ref-btn q-ref-btn-ghost" href="<?php echo esc_url( $login ); ?>">تسجيل الدخول</a><a class="q-ref-btn q-ref-btn-primary q-ref-auth-secondary" href="<?php echo esc_url( $register ); ?>">إنشاء حساب</a><?php endif; ?><button class="q-ref-menu-toggle" type="button" data-qalam-menu-toggle aria-label="فتح القائمة" aria-expanded="false" aria-controls="qalam-mobile-menu">☰</button></div></div><div class="q-ref-menu-backdrop" data-qalam-menu-backdrop aria-hidden="true"></div><div class="q-ref-mobile-menu" id="qalam-mobile-menu" data-qalam-mobile-menu aria-hidden="true"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">الرئيسية</a><a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ?: home_url( '/courses/' ) ); ?>">الكورسات</a><?php if ( 'academy' === $type ) : ?><a href="<?php echo esc_url( home_url( '/#teachers' ) ); ?>">المدرسون</a><a href="<?php echo esc_url( home_url( '/#subjects' ) ); ?>">المواد</a><?php else : ?><a href="<?php echo esc_url( home_url( '/#about' ) ); ?>">عن المدرس</a><?php endif; ?><a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>">تواصل معنا</a><div class="q-ref-mobile-auth"><?php if ( is_user_logged_in() ) : ?><a class="is-primary" href="<?php echo esc_url( $dashboard ); ?>">لوحة التحكم</a><a class="is-danger" href="<?php echo esc_url( $logout ); ?>">تسجيل الخروج</a><?php else : ?><a href="<?php echo esc_url( $login ); ?>">تسجيل الدخول</a><a class="is-primary" href="<?php echo esc_url( $register ); ?>">إنشاء حساب</a><?php endif; ?></div></div></header><main class="q-ref-main"><?php
}

function qalam_240_render_shell_footer(): void {
    $brand = qalam_230_brand();
    $whatsapp = qalam_230_whatsapp_url( $brand );
    ?></main><footer class="q-ref-footer" id="contact"><div class="q-ref-container q-ref-footer-grid"><div class="q-ref-footer-brand"><?php if ( ! empty( $brand['logo_url'] ) ) : ?><img src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt=""><?php endif; ?><h3><?php echo esc_html( $brand['platform_name'] ); ?></h3><p><?php echo esc_html( $brand['tagline'] ); ?></p></div><div><h4>روابط سريعة</h4><a href="<?php echo esc_url( home_url( '/' ) ); ?>">الرئيسية</a><a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ?: home_url( '/courses/' ) ); ?>">الكورسات</a><?php if ( is_user_logged_in() ) : $footer_dashboard = function_exists( 'qalam_210_dashboard_url' ) && function_exists( 'qalam_210_user_is_managed' ) && qalam_210_user_is_managed() ? qalam_210_dashboard_url() : ( function_exists( 'tutor_utils' ) ? tutor_utils()->tutor_dashboard_url() : home_url( '/' ) ); ?><a href="<?php echo esc_url( $footer_dashboard ); ?>">لوحة التحكم</a><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">تسجيل الخروج</a><?php else : ?><a href="<?php echo esc_url( function_exists( 'qalam_220_login_url' ) ? qalam_220_login_url() : wp_login_url() ); ?>">تسجيل الدخول</a><a href="<?php echo esc_url( qalam_240_registration_url() ); ?>">إنشاء حساب</a><?php endif; ?></div><div><h4>تواصل معنا</h4><?php if ( ! empty( $brand['phone'] ) ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $brand['phone'] ) ); ?>"><?php echo esc_html( $brand['phone'] ); ?></a><?php endif; ?><?php if ( ! empty( $brand['email'] ) ) : ?><a href="mailto:<?php echo esc_attr( $brand['email'] ); ?>"><?php echo esc_html( $brand['email'] ); ?></a><?php endif; ?><?php if ( $whatsapp ) : ?><a target="_blank" rel="noopener" href="<?php echo esc_url( $whatsapp ); ?>">واتساب</a><?php endif; ?></div></div><div class="q-ref-footer-bottom"><span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $brand['platform_name'] ); ?></span><strong>مؤسسة قلم للخدمات الإلكترونية</strong><span>بكل فخر ❤️ صنع في مصر</span></div></footer><?php if ( $whatsapp ) : ?><a class="q-ref-whatsapp" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener" aria-label="واتساب">واتساب</a><?php endif; ?></div><?php wp_footer(); ?></body></html><?php
}

function qalam_240_course_query( int $limit = 6 ): WP_Query {
    return new WP_Query( array(
        'post_type' => 'courses',
        'post_status' => 'publish',
        'posts_per_page' => max( 1, min( 12, $limit ) ),
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
    ) );
}

function qalam_240_render_course_cards( WP_Query $courses, string $variant = 'default' ): void {
    if ( ! $courses->have_posts() ) {
        echo '<div class="q-ref-empty"><strong>لا توجد كورسات متاحة حاليًا</strong><p>تابع المنصة لمعرفة أحدث الكورسات عند نشرها.</p></div>';
        return;
    }
    echo '<div class="q-ref-course-grid q-ref-course-grid-' . esc_attr( $variant ) . '">';
    while ( $courses->have_posts() ) {
        $courses->the_post();
        $id = get_the_ID();
        $image = get_the_post_thumbnail_url( $id, 'large' );
        $price = function_exists( 'tutor_utils' ) ? tutor_utils()->get_course_price( $id ) : '';
        echo '<article class="q-ref-course-card" data-qalam-reveal><a class="q-ref-course-thumb" href="' . esc_url( get_permalink() ) . '">';
        if ( $image ) { echo '<img src="' . esc_url( $image ) . '" alt="">'; }
        else { echo '<span class="q-ref-course-fallback">ق</span>'; }
        echo '<span class="q-ref-course-badge">كورس</span></a><div class="q-ref-course-content"><h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3><p>' . esc_html( wp_trim_words( get_the_excerpt(), 18 ) ) . '</p><div class="q-ref-course-row"><strong>' . ( $price ? wp_kses_post( $price ) : 'مجاني' ) . '</strong><a href="' . esc_url( get_permalink() ) . '">التفاصيل</a></div></div></article>';
    }
    wp_reset_postdata();
    echo '</div>';
}

function qalam_240_instructors(): array {
    $query = new WP_User_Query( array(
        'role__in' => array( 'tutor_instructor' ),
        'number' => 8,
        'orderby' => 'display_name',
        'order' => 'ASC',
    ) );
    return $query->get_results();
}

function qalam_240_course_categories(): array {
    $taxonomies = array( 'course-category', 'course_category' );
    foreach ( $taxonomies as $taxonomy ) {
        if ( taxonomy_exists( $taxonomy ) ) {
            $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true, 'number' => 8 ) );
            return is_wp_error( $terms ) ? array() : $terms;
        }
    }
    return array();
}

function qalam_240_render_academy_home( array $brand ): void {
    $courses = qalam_240_course_query( (int) $brand['featured_courses'] );
    $instructors = qalam_240_instructors();
    $categories = qalam_240_course_categories();
    $hero_image = $brand['hero_image_url'] ?: ( $brand['teacher_image_url'] ?: $brand['logo_url'] );
    ?><section class="q-ref-academy-hero"><div class="q-ref-container q-ref-academy-hero-grid"><div class="q-ref-hero-copy" data-qalam-reveal><span class="q-ref-eyebrow"><?php echo esc_html( $brand['tagline'] ); ?></span><h1><?php echo esc_html( $brand['hero_title'] ?: 'منصة متكاملة بها كل ما يحتاجه الطالب ليتفوق' ); ?></h1><p><?php echo esc_html( $brand['hero_text'] ); ?></p><div class="q-ref-hero-actions"><a class="q-ref-btn q-ref-btn-primary" href="#courses">تصفح الكورسات</a><?php if ( ! is_user_logged_in() && function_exists( 'qalam_220_login_url' ) ) : ?><a class="q-ref-btn q-ref-btn-soft" href="<?php echo esc_url( qalam_220_login_url() ); ?>">ابدأ رحلتك</a><?php endif; ?></div><div class="q-ref-mini-stats"><div><strong><?php echo esc_html( number_format_i18n( qalam_230_course_count() ) ); ?></strong><span>كورس</span></div><div><strong><?php echo esc_html( number_format_i18n( count( $instructors ) ) ); ?></strong><span>مدرس</span></div><div><strong><?php echo esc_html( number_format_i18n( qalam_230_student_count() ) ); ?></strong><span>طالب</span></div></div></div><div class="q-ref-hero-art" data-qalam-reveal><?php if ( $hero_image ) : ?><div class="q-ref-art-orbit q-ref-reference-portrait"><img src="<?php echo esc_url( $hero_image ); ?>" alt="<?php echo esc_attr( $brand['teacher_name'] ?: $brand['platform_name'] ); ?>"></div><?php else : ?><div class="q-ref-academy-symbol">ق</div><?php endif; ?><span class="q-ref-float-card q-ref-float-one">اختبارات تفاعلية</span><span class="q-ref-float-card q-ref-float-two">متابعة مستمرة</span></div></div></section>

    <section class="q-ref-section" id="teachers"><div class="q-ref-container"><div class="q-ref-section-head" data-qalam-reveal><div><span>اختار مدرسك</span><h2>مدرسو المنصة</h2><p>اختار المدرس المناسب ليك وابدأ رحلتك التعليمية.</p></div></div><?php if ( $instructors ) : ?><div class="q-ref-teacher-grid"><?php foreach ( $instructors as $index => $instructor ) : $avatar = ( 0 === (int) $index && ! empty( $brand['teacher_image_url'] ) ) ? $brand['teacher_image_url'] : get_avatar_url( $instructor->ID, array( 'size' => 480 ) ); ?><article class="q-ref-teacher-card" data-qalam-reveal><div class="q-ref-teacher-photo"><?php if ( $avatar ) : ?><img src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( $instructor->display_name ); ?>"><?php else : ?><span class="q-ref-teacher-fallback">ق</span><?php endif; ?></div><h3><?php echo esc_html( $instructor->display_name ); ?></h3><p><?php echo esc_html( wp_trim_words( (string) get_user_meta( $instructor->ID, '_tutor_profile_bio', true ), 18 ) ); ?></p><a href="<?php echo esc_url( function_exists( 'tutor_utils' ) ? tutor_utils()->profile_url( $instructor->ID ) : '#' ); ?>">عرض المدرس</a></article><?php endforeach; ?></div><?php elseif ( ! empty( $brand['teacher_image_url'] ) || ! empty( $brand['teacher_name'] ) ) : ?><div class="q-ref-teacher-grid q-ref-teacher-grid-managed"><article class="q-ref-teacher-card" data-qalam-reveal><div class="q-ref-teacher-photo"><?php if ( ! empty( $brand['teacher_image_url'] ) ) : ?><img src="<?php echo esc_url( $brand['teacher_image_url'] ); ?>" alt="<?php echo esc_attr( $brand['teacher_name'] ?: $brand['platform_name'] ); ?>"><?php else : ?><span class="q-ref-teacher-fallback">ق</span><?php endif; ?></div><h3><?php echo esc_html( $brand['teacher_name'] ?: $brand['platform_name'] ); ?></h3><p><?php echo esc_html( wp_trim_words( (string) ( $brand['teacher_bio'] ?: $brand['hero_text'] ), 18 ) ); ?></p><a href="#courses">عرض الكورسات</a></article></div><?php else : ?><?php endif; ?></div></section>

    <section class="q-ref-section q-ref-surface-section" id="courses"><div class="q-ref-container"><div class="q-ref-section-head" data-qalam-reveal><div><span>مختارة بعناية</span><h2><?php echo esc_html( $brand['courses_title'] ); ?></h2><p>اكتشف مجموعة متنوعة من الكورسات المناسبة لأهداف الطلاب.</p></div><a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ?: home_url( '/courses/' ) ); ?>">عرض كل الكورسات</a></div><?php qalam_240_render_course_cards( $courses, 'academy' ); ?></div></section>

    <section class="q-ref-section" id="subjects"><div class="q-ref-container"><div class="q-ref-section-head" data-qalam-reveal><div><span>مواد المنصة</span><h2>اختار المادة وابدأ</h2></div></div><?php if ( $categories ) : ?><div class="q-ref-subject-grid"><?php foreach ( $categories as $term ) : ?><a class="q-ref-subject-card" data-qalam-reveal href="<?php echo esc_url( get_term_link( $term ) ); ?>"><span><?php echo esc_html( mb_substr( $term->name, 0, 1 ) ); ?></span><strong><?php echo esc_html( $term->name ); ?></strong><small><?php echo esc_html( number_format_i18n( $term->count ) ); ?> كورس</small></a><?php endforeach; ?></div><?php else : ?><?php endif; ?></div></section>

    <?php qalam_240_render_features_section( 'academy' ); ?>
    <section class="q-ref-cta q-ref-cta-academy" data-qalam-reveal><div class="q-ref-container"><div><span>جاهز تبدأ؟</span><h2>كل اللي محتاجه للمذاكرة في مكان واحد</h2><p>كورسات، مدرسين، اختبارات ومتابعة داخل تجربة واحدة.</p></div><a class="q-ref-btn q-ref-btn-light" href="#courses">ابدأ رحلتك الآن</a></div></section><?php
}

function qalam_240_render_individual_home( array $brand ): void {
    $courses = qalam_240_course_query( (int) $brand['featured_courses'] );
    $portrait = $brand['teacher_image_url'] ?: ( $brand['hero_image_url'] ?: $brand['logo_url'] );
    $teacher = $brand['teacher_name'] ?: $brand['platform_name'];
    ?><section class="q-ref-individual-hero"><div class="q-ref-container q-ref-individual-hero-grid"><div class="q-ref-hero-copy" data-qalam-reveal><span class="q-ref-teacher-kicker"><?php echo esc_html( $teacher ); ?></span><h1><?php echo esc_html( $brand['hero_title'] ?: 'رحلتك بتبدأ هنا.. خُد أول خطوة وأنت واثق' ); ?></h1><p><?php echo esc_html( $brand['hero_text'] ); ?></p><div class="q-ref-hero-actions"><a class="q-ref-btn q-ref-btn-primary" href="#courses">سجل وخد أول خطوة</a><a class="q-ref-text-link" href="#about">اعرف أكتر عن المدرس ←</a></div></div><div class="q-ref-personal-portrait" data-qalam-reveal><?php if ( $portrait ) : ?><img src="<?php echo esc_url( $portrait ); ?>" alt="<?php echo esc_attr( $teacher ); ?>"><?php else : ?><span>ق</span><?php endif; ?><div class="q-ref-portrait-label"><strong><?php echo esc_html( $teacher ); ?></strong><small><?php echo esc_html( $brand['teacher_title'] ); ?></small></div></div></div></section>

    <section class="q-ref-section q-ref-individual-about" id="about"><div class="q-ref-container q-ref-about-grid"><div data-qalam-reveal><span class="q-ref-eyebrow">منصة تعليمية متكاملة</span><h2><?php echo esc_html( $brand['about_title'] ); ?></h2><h3><?php echo esc_html( $teacher ); ?></h3><p><?php echo nl2br( esc_html( $brand['teacher_bio'] ?: $brand['hero_text'] ) ); ?></p><a class="q-ref-btn q-ref-btn-soft" href="#courses">شوف الكورسات</a></div><div class="q-ref-about-metrics" data-qalam-reveal><div><strong><?php echo esc_html( number_format_i18n( qalam_230_course_count() ) ); ?></strong><span>كورس منشور</span></div><div><strong><?php echo esc_html( number_format_i18n( qalam_230_student_count() ) ); ?></strong><span>طالب على المنصة</span></div><div><strong>24/7</strong><span>وصول للمحتوى</span></div></div></div></section>

    <section class="q-ref-section q-ref-surface-section" id="courses"><div class="q-ref-container"><div class="q-ref-section-head" data-qalam-reveal><div><span>الكورسات المُقترحة</span><h2><?php echo esc_html( $brand['courses_title'] ); ?></h2><p>اختار الكورس المناسب وابدأ مذاكرتك خطوة بخطوة.</p></div><a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ?: home_url( '/courses/' ) ); ?>">عرض كل الكورسات</a></div><?php qalam_240_render_course_cards( $courses, 'individual' ); ?></div></section>

    <?php qalam_240_render_grade_tracks(); ?>
    <?php qalam_240_render_features_section( 'individual' ); ?>
    <section class="q-ref-cta q-ref-cta-individual" data-qalam-reveal><div class="q-ref-container"><div><span>ابدأ مرحلة جديدة</span><h2>خد أول خطوة في رحلتك التعليمية</h2><p>سجل، اختار كورسك، وابدأ التعلم من أي جهاز.</p></div><?php if ( function_exists( 'qalam_220_login_url' ) ) : ?><a class="q-ref-btn q-ref-btn-light" href="<?php echo esc_url( qalam_220_login_url() ); ?>">ابدأ الآن</a><?php endif; ?></div></section><?php
}

function qalam_240_render_grade_tracks(): void {
    $categories = qalam_240_course_categories();
    if ( ! $categories ) { return; }
    ?><section class="q-ref-section q-ref-grade-section"><div class="q-ref-container"><div class="q-ref-section-head" data-qalam-reveal><div><span>المراحل والمسارات</span><h2>اختار مسارك</h2><p>اختار المسار المناسب ليك واستعرض الكورسات المتاحة.</p></div></div><div class="q-ref-grade-grid"><?php foreach ( array_slice( $categories, 0, 6 ) as $term ) : $term_url = get_term_link( $term ); if ( is_wp_error( $term_url ) ) { continue; } ?><a class="q-ref-grade-card" href="<?php echo esc_url( $term_url ); ?>" data-qalam-reveal><small>المسار الدراسي</small><h3><?php echo esc_html( $term->name ); ?></h3><span>عرض الكورسات ←</span></a><?php endforeach; ?></div></div></section><?php
}

function qalam_240_render_features_section( string $type ): void {
    $features = 'academy' === $type ? array(
        array( '↗', 'هتشارك', 'تفاعل مع المدرسين وزمايلك داخل رحلة تعليمية منظمة.' ),
        array( '★', 'هتنافس', 'اختبارات ونتائج تساعدك تعرف مستواك وتتقدم.' ),
        array( '✓', 'هنجهزك', 'خطة واضحة ومحتوى منظم بدل التشتت بين المصادر.' ),
        array( '∞', 'هتتدرب', 'تدريبات واختبارات إلكترونية تعيدها لحد ما تتقنها.' ),
    ) : array(
        array( '◎', 'شرح مبسط ومركز', 'محتوى واضح بعيد عن التعقيد ومبني على الفهم.' ),
        array( '✓', 'اختبارات بنفس النظام', 'اختبارات تفاعلية تقيس الفهم وتجهزك للامتحان.' ),
        array( '↗', 'متابعة وتقييم مستمر', 'متابعة تقدمك ونتائجك أولًا بأول.' ),
        array( '▦', 'خطة مذاكرة منظمة', 'مسار واضح يساعدك تذاكر بتركيز وراحة.' ),
        array( '✦', 'تفاعل مباشر', 'مكان واحد للأسئلة والدروس والمتابعة.' ),
        array( '▶', 'مراجعات مركزة', 'محتوى سريع ومركز قبل الاختبارات والمراجعات.' ),
    );
    ?><section class="q-ref-section q-ref-features"><div class="q-ref-container"><div class="q-ref-section-head" data-qalam-reveal><div><span>تجربة تعليمية متكاملة</span><h2>إيه اللي هتلاقيه على المنصة؟</h2></div></div><div class="q-ref-feature-grid q-ref-feature-grid-<?php echo esc_attr( $type ); ?>"><?php foreach ( $features as $feature ) : ?><article class="q-ref-feature-card" data-qalam-reveal><i><?php echo esc_html( $feature[0] ); ?></i><h3><?php echo esc_html( $feature[1] ); ?></h3><p><?php echo esc_html( $feature[2] ); ?></p></article><?php endforeach; ?></div></div></section><?php
}

function qalam_240_should_render_home(): bool {
    if ( ! qalam_240_is_public_surface() || qalam_240_is_login_surface() ) { return false; }
    if ( isset( $_GET['qalam_preview'] ) && current_user_can( QALAM_230_DESIGN_CAP ) ) { return true; }
    return is_front_page() || is_home();
}

function qalam_240_render_home(): void {
    if ( ! qalam_240_should_render_home() ) { return; }
    status_header( 200 );
    nocache_headers();
    $brand = qalam_230_brand();
    qalam_240_render_shell_header();
    echo '<div class="q-ref-home q-ref-home-' . esc_attr( qalam_240_platform_type() ) . '">';
    if ( 'academy' === qalam_240_platform_type() ) { qalam_240_render_academy_home( $brand ); }
    else { qalam_240_render_individual_home( $brand ); }
    echo '</div>';
    qalam_240_render_shell_footer();
    exit;
}
add_action( 'template_redirect', 'qalam_240_render_home', 1 );

/** Remove active theme assets on Qalam-owned public LMS surfaces. */
function qalam_240_strip_theme_assets(): void {
    if ( ! qalam_240_is_public_surface() ) { return; }
    global $wp_styles, $wp_scripts;
    $theme_urls = array_filter( array( get_stylesheet_directory_uri(), get_template_directory_uri() ) );
    if ( $wp_styles instanceof WP_Styles ) {
        foreach ( (array) $wp_styles->queue as $handle ) {
            $src = isset( $wp_styles->registered[ $handle ] ) ? (string) $wp_styles->registered[ $handle ]->src : '';
            foreach ( $theme_urls as $theme_url ) { if ( $src && 0 === strpos( $src, $theme_url ) ) { wp_dequeue_style( $handle ); break; } }
        }
    }
    if ( $wp_scripts instanceof WP_Scripts ) {
        foreach ( (array) $wp_scripts->queue as $handle ) {
            $src = isset( $wp_scripts->registered[ $handle ] ) ? (string) $wp_scripts->registered[ $handle ]->src : '';
            foreach ( $theme_urls as $theme_url ) { if ( $src && 0 === strpos( $src, $theme_url ) ) { wp_dequeue_script( $handle ); break; } }
        }
    }
    foreach ( array( 'global-styles', 'classic-theme-styles', 'wp-block-library', 'wp-block-library-theme' ) as $handle ) { wp_dequeue_style( $handle ); }
}
add_action( 'wp_enqueue_scripts', 'qalam_240_strip_theme_assets', PHP_INT_MAX );


/** Student-facing Tutor dashboard keeps the same public identity without exposing WordPress chrome. */
function qalam_240_is_student_dashboard_surface(): bool {
    if ( is_admin() || ! function_exists( 'tutor_utils' ) ) { return false; }
    if ( function_exists( 'qalam_210_is_dashboard_request' ) && qalam_210_is_dashboard_request() ) { return false; }
    try { return (bool) tutor_utils()->is_tutor_dashboard(); } catch ( \Throwable $e ) { return false; }
}

function qalam_240_render_embedded_dashboard_header(): void {
    if ( defined( 'QALAM_260_DASHBOARD_SHELL' ) && QALAM_260_DASHBOARD_SHELL ) { return; }
    if ( ! qalam_240_is_student_dashboard_surface() ) { return; }
    $brand = qalam_230_brand();
    ?><header class="q-ref-header q-ref-dashboard-header" data-qalam-header><div class="q-ref-container q-ref-header-inner"><a class="q-ref-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php if ( ! empty( $brand['logo_url'] ) ) : ?><img src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt=""><?php else : ?><img class="q-ref-qalam-mark" src="<?php echo esc_url( plugins_url( 'assets/images/qalam-mark.svg', QALAM_LMS_FILE ) ); ?>" alt="Qalam"><?php endif; ?><span><?php echo esc_html( $brand['platform_name'] ); ?></span></a><div class="q-ref-actions"><button class="q-ref-mode-toggle" type="button" data-qalam-mode-toggle aria-label="تغيير الوضع"><span class="q-ref-sun">☀</span><span class="q-ref-moon">☾</span></button><a class="q-ref-btn q-ref-btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">الرئيسية</a></div></div></header><?php
}
add_action( 'wp_body_open', 'qalam_240_render_embedded_dashboard_header', 3 );

function qalam_240_render_embedded_dashboard_branding(): void {
    if ( defined( 'QALAM_260_DASHBOARD_SHELL' ) && QALAM_260_DASHBOARD_SHELL ) { return; }
    if ( ! qalam_240_is_student_dashboard_surface() ) { return; }
    ?><div class="q-ref-dashboard-branding" dir="rtl"><strong>مؤسسة قلم للخدمات الإلكترونية</strong><span>بكل فخر ❤️ صنع في مصر</span></div><?php
}
add_action( 'wp_footer', 'qalam_240_render_embedded_dashboard_branding', 80 );

function qalam_240_schema_once(): void {
    if ( get_option( QALAM_240_SCHEMA_OPTION ) !== QALAM_240_SCHEMA_VALUE ) {
        update_option( QALAM_240_SCHEMA_OPTION, QALAM_240_SCHEMA_VALUE, false );
    }
}
add_action( 'init', 'qalam_240_schema_once', 6 );
