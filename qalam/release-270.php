<?php
/**
 * Qalam LMS 0.27.0 — runtime + visual closure.
 *
 * - Tightens operational settings by role (manager default-deny allowlist).
 * - Rebuilds academy/individual public home structures against the chosen references.
 * - Adds precision skin for internal Tutor surfaces.
 * - Keeps the hardened role matrix and reference skin used by the production runtime.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_270_VERSION = '0.28.6';
const QALAM_270_SCHEMA_OPTION = 'qalam_270_schema';
const QALAM_270_SCHEMA_VALUE = '2';

/** Explicit manager allowlist. Unknown future settings default to owner-only. */
function qalam_270_manager_settings_tabs(): array {
    return array(
        'general',
        'course',
        'tutor_gradebook',
        'tutor_notifications',
        'tutor_certificate',
    );
}

function qalam_270_manager_can_manage_settings_tab( string $tab ): bool {
    $tab = sanitize_key( $tab );
    return '' !== $tab && in_array( $tab, qalam_270_manager_settings_tabs(), true );
}

/** Precision layer always loads last on Qalam public surfaces. */
function qalam_270_enqueue_precision_assets(): void {
    if ( ! function_exists( 'qalam_240_is_public_surface' ) || ! qalam_240_is_public_surface() ) { return; }
    wp_enqueue_style(
        'qalam-reference-precision',
        plugins_url( 'assets/css/qalam-reference-precision.css', QALAM_LMS_FILE ),
        array( 'qalam-reference-system' ),
        QALAM_270_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'qalam_270_enqueue_precision_assets', 96 );

function qalam_270_course_archive_url(): string {
    $url = get_post_type_archive_link( 'courses' );
    return $url ? $url : home_url( '/courses/' );
}

function qalam_270_registration_url(): string {
    if ( function_exists( 'tutor_utils' ) ) {
        $id = (int) tutor_utils()->get_option( 'student_register_page', 0 );
        if ( $id ) { return get_permalink( $id ); }
    }
    return function_exists( 'qalam_220_login_url' ) ? qalam_220_login_url() : wp_registration_url();
}

function qalam_270_render_academy_picker( array $instructors, array $categories ): void {
    ?><div class="q270-academy-picker" data-qalam-reveal>
        <div class="q270-picker-head"><span>ابدأ من هنا</span><h2>اختار مدرسك ومسارك</h2><p>وصول سريع للمحتوى حسب المدرس والمادة.</p></div>
        <div class="q270-picker-grid">
            <label><span>المدرس</span><select data-q270-teacher-select><option value="">اختر المدرس</option><?php foreach ( $instructors as $instructor ) : ?><option value="<?php echo esc_attr( function_exists( 'tutor_utils' ) ? tutor_utils()->profile_url( $instructor->ID ) : '' ); ?>"><?php echo esc_html( $instructor->display_name ); ?></option><?php endforeach; ?></select></label>
            <label><span>المادة / المسار</span><select data-q270-subject-select><option value="">اختر المادة</option><?php foreach ( $categories as $term ) : $term_url = get_term_link( $term ); ?><option value="<?php echo esc_attr( is_wp_error( $term_url ) ? '' : $term_url ); ?>"><?php echo esc_html( $term->name ); ?></option><?php endforeach; ?></select></label>
            <button class="q-ref-btn q-ref-btn-primary" type="button" data-q270-picker-go>عرض المحتوى</button>
        </div>
    </div><?php
}

function qalam_270_render_teacher_cards( array $brand, array $instructors ): void {
    if ( $instructors ) {
        echo '<div class="q270-teacher-grid">';
        foreach ( $instructors as $index => $instructor ) {
            $avatar = ( 0 === (int) $index && ! empty( $brand['teacher_image_url'] ) ) ? $brand['teacher_image_url'] : get_avatar_url( $instructor->ID, array( 'size' => 640 ) );
            $bio = (string) get_user_meta( $instructor->ID, '_tutor_profile_bio', true );
            $url = function_exists( 'tutor_utils' ) ? tutor_utils()->profile_url( $instructor->ID ) : '#';
            echo '<a class="q270-teacher-card" data-qalam-reveal href="' . esc_url( $url ) . '"><div class="q270-teacher-photo">';
            if ( $avatar ) { echo '<img src="' . esc_url( $avatar ) . '" alt="' . esc_attr( $instructor->display_name ) . '">'; }
            else { echo '<span>' . esc_html( mb_substr( $instructor->display_name ?: 'ق', 0, 1 ) ) . '</span>'; }
            echo '</div><div><small>مدرس المنصة</small><h3>' . esc_html( $instructor->display_name ) . '</h3><p>' . esc_html( wp_trim_words( $bio, 15 ) ) . '</p><strong>عرض المدرس ←</strong></div></a>';
        }
        echo '</div>';
        return;
    }
    if ( ! empty( $brand['teacher_name'] ) || ! empty( $brand['teacher_image_url'] ) ) {
        echo '<div class="q270-teacher-grid"><article class="q270-teacher-card" data-qalam-reveal><div class="q270-teacher-photo">';
        if ( ! empty( $brand['teacher_image_url'] ) ) { echo '<img src="' . esc_url( $brand['teacher_image_url'] ) . '" alt="' . esc_attr( $brand['teacher_name'] ) . '">'; }
        else { echo '<span>ق</span>'; }
        echo '</div><div><small>مدرس المنصة</small><h3>' . esc_html( $brand['teacher_name'] ?: $brand['platform_name'] ) . '</h3><p>' . esc_html( wp_trim_words( (string) ( $brand['teacher_bio'] ?: $brand['hero_text'] ), 15 ) ) . '</p><strong>الكورسات بالأسفل</strong></div></article></div>';
        return;
    }
    return;
}

/** Academy reference: selection first, then teachers/courses/subjects/features/support CTAs. */
function qalam_270_render_academy_home( array $brand ): void {
    $courses = qalam_240_course_query( (int) $brand['featured_courses'] );
    $instructors = qalam_240_instructors();
    $categories = qalam_240_course_categories();
    $hero_media = $brand['hero_image_url'] ?: ( $brand['teacher_image_url'] ?: $brand['logo_url'] );
    ?><section class="q270-academy-hero"><div class="q-ref-container q270-academy-hero-grid">
        <div class="q270-academy-copy" data-qalam-reveal><span class="q-ref-eyebrow"><?php echo esc_html( $brand['tagline'] ); ?></span><h1><?php echo esc_html( $brand['hero_title'] ?: 'منصة تعليمية متكاملة تساعد الطالب يوصل لهدفه' ); ?></h1><p><?php echo esc_html( $brand['hero_text'] ); ?></p><div class="q-ref-hero-actions"><a class="q-ref-btn q-ref-btn-primary" href="#courses">تصفح الكورسات</a><a class="q-ref-btn q-ref-btn-ghost" href="#teachers">اختار مدرسك</a></div></div>
        <div class="q270-academy-visual" data-qalam-reveal><?php if ( $hero_media ) : ?><img src="<?php echo esc_url( $hero_media ); ?>" alt="<?php echo esc_attr( $brand['platform_name'] ); ?>"><?php else : ?><div class="q270-visual-fallback">ق</div><?php endif; ?><span class="q270-visual-chip one">اختبارات</span><span class="q270-visual-chip two">متابعة</span><span class="q270-visual-chip three">كورسات</span></div>
        <?php qalam_270_render_academy_picker( $instructors, $categories ); ?>
    </div></section>

    <section class="q-ref-section q270-teachers" id="teachers"><div class="q-ref-container"><div class="q-ref-section-head" data-qalam-reveal><div><span>اختار المدرس</span><h2>مدرسو المنصة</h2><p>اختار المدرس المناسب ليك وابدأ معاه.</p></div></div><?php qalam_270_render_teacher_cards( $brand, $instructors ); ?></div></section>

    <section class="q-ref-section q-ref-surface-section q270-courses" id="courses"><div class="q-ref-container"><div class="q-ref-section-head" data-qalam-reveal><div><span>كورسات مقترحة</span><h2><?php echo esc_html( $brand['courses_title'] ); ?></h2><p>مجموعة من أحدث الكورسات المنشورة على المنصة.</p></div><a href="<?php echo esc_url( qalam_270_course_archive_url() ); ?>">عرض كل الكورسات</a></div><?php qalam_240_render_course_cards( $courses, 'academy' ); ?></div></section>

    <section class="q270-search-banner"><div class="q-ref-container"><div data-qalam-reveal><span>وصل للكورس أسرع</span><h2>ابحث وسط كل محتوى المنصة</h2><p>تصفح كل الكورسات والمواد من صفحة واحدة مرتبة.</p></div><a class="q-ref-btn q-ref-btn-light" href="<?php echo esc_url( qalam_270_course_archive_url() ); ?>">تصفح الكورسات</a></div></section>

    <section class="q-ref-section q270-subjects" id="subjects"><div class="q-ref-container"><div class="q-ref-section-head" data-qalam-reveal><div><span>مواد المنصة</span><h2>اختار المادة وابدأ</h2><p>اختار المسار أو المادة المناسبة ليك.</p></div></div><?php if ( $categories ) : ?><div class="q270-subject-grid"><?php foreach ( $categories as $term ) : $u=get_term_link($term); ?><a class="q270-subject-card" data-qalam-reveal href="<?php echo esc_url( is_wp_error($u)?'#':$u ); ?>"><i><?php echo esc_html( mb_substr( $term->name, 0, 1 ) ); ?></i><div><h3><?php echo esc_html( $term->name ); ?></h3><span><?php echo esc_html( number_format_i18n( $term->count ) ); ?> كورس</span></div><b>←</b></a><?php endforeach; ?></div><?php endif; ?></div></section>

    <?php qalam_240_render_features_section( 'academy' ); ?>
    <section class="q270-dual-cta"><div class="q-ref-container q270-dual-cta-grid"><article data-qalam-reveal><span>ذاكر في أي وقت</span><h2>الوصول للمحتوى من أي جهاز</h2><p>الطالب يقدر يكمل مذاكرته واختباراته من الموبايل أو الكمبيوتر بنفس الحساب.</p><a href="<?php echo esc_url( qalam_270_course_archive_url() ); ?>">استكشف المحتوى ←</a></article><article data-qalam-reveal><span>فريق المنصة</span><h2>منصة جاهزة لأكثر من مدرس</h2><p>مدرسين ودورات ومسارات متعددة داخل تجربة تعليمية واحدة.</p><a href="#teachers">شاهد المدرسين ←</a></article></div></section>
    <section class="q-ref-cta q-ref-cta-academy" data-qalam-reveal><div class="q-ref-container"><div><span>ابدأ رحلتك</span><h2>كل أدوات التعلم في منصة واحدة</h2><p>كورسات، اختبارات، متابعة ومدرسون داخل تجربة موحدة.</p></div><a class="q-ref-btn q-ref-btn-light" href="<?php echo esc_url( qalam_270_registration_url() ); ?>">ابدأ الآن</a></div></section><?php
}

/** Individual reference: personal hero, about, courses, years, honor, six benefits, CTA. */
function qalam_270_render_individual_home( array $brand ): void {
    $courses = qalam_240_course_query( (int) $brand['featured_courses'] );
    $portrait = $brand['teacher_image_url'] ?: ( $brand['hero_image_url'] ?: $brand['logo_url'] );
    $teacher = $brand['teacher_name'] ?: $brand['platform_name'];
    ?><section class="q270-individual-hero"><div class="q-ref-container q270-individual-hero-grid"><div class="q270-individual-copy" data-qalam-reveal><span class="q270-teacher-name"><?php echo esc_html( $teacher ); ?></span><h1><?php echo esc_html( $brand['hero_title'] ?: 'رحلتك التعليمية تبدأ بخطوة واضحة' ); ?></h1><p><?php echo esc_html( $brand['hero_text'] ); ?></p><div class="q-ref-hero-actions"><a class="q-ref-btn q-ref-btn-primary" href="<?php echo esc_url( qalam_270_registration_url() ); ?>">سجل وخد أول خطوة</a><a class="q-ref-text-link" href="#about">اعرف أكتر ←</a></div></div><div class="q270-individual-portrait" data-qalam-reveal><?php if ( $portrait ) : ?><img src="<?php echo esc_url( $portrait ); ?>" alt="<?php echo esc_attr( $teacher ); ?>"><?php else : ?><span>ق</span><?php endif; ?><div class="q270-portrait-shape"></div></div></div></section>

    <section class="q-ref-section q270-individual-about" id="about"><div class="q-ref-container q270-about-grid"><div class="q270-about-visual" data-qalam-reveal><?php if ( $brand['teacher_image_url'] ) : ?><img src="<?php echo esc_url( $brand['teacher_image_url'] ); ?>" alt="<?php echo esc_attr( $teacher ); ?>"><?php elseif ( $brand['logo_url'] ) : ?><img class="is-logo" src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt="<?php echo esc_attr( $brand['platform_name'] ); ?>"><?php else : ?><span>ق</span><?php endif; ?></div><div data-qalam-reveal><span class="q-ref-eyebrow">منصة <?php echo esc_html( $teacher ); ?></span><h2><?php echo esc_html( $brand['about_title'] ); ?></h2><p><?php echo nl2br( esc_html( $brand['teacher_bio'] ?: $brand['hero_text'] ) ); ?></p><div class="q270-about-stats"><div><strong><?php echo esc_html( number_format_i18n( qalam_230_course_count() ) ); ?></strong><span>كورس منشور</span></div><div><strong><?php echo esc_html( number_format_i18n( qalam_230_student_count() ) ); ?></strong><span>طالب</span></div><div><strong>24/7</strong><span>وصول</span></div></div></div></div></section>

    <section class="q-ref-section q-ref-surface-section q270-courses" id="courses"><div class="q-ref-container"><div class="q-ref-section-head" data-qalam-reveal><div><span>الكورسات المقترحة</span><h2><?php echo esc_html( $brand['courses_title'] ); ?></h2><p>اختار الكورس المناسب وابدأ المذاكرة خطوة بخطوة.</p></div><a href="<?php echo esc_url( qalam_270_course_archive_url() ); ?>">عرض الكل</a></div><?php qalam_240_render_course_cards( $courses, 'individual' ); ?></div></section>

    <?php qalam_240_render_grade_tracks(); ?>
    <?php qalam_240_render_features_section( 'individual' ); ?>
    <section class="q-ref-cta q-ref-cta-individual" data-qalam-reveal><div class="q-ref-container"><div><span>ابدأ مرحلة جديدة</span><h2>خد أول خطوة في رحلتك التعليمية</h2><p>سجل حسابك، اختار كورسك، وابدأ من أي جهاز.</p></div><a class="q-ref-btn q-ref-btn-light" href="<?php echo esc_url( qalam_270_registration_url() ); ?>">انشئ حسابك الآن</a></div></section><?php
}

remove_action( 'template_redirect', 'qalam_240_render_home', 1 );
function qalam_270_render_home(): void {
    if ( ! function_exists( 'qalam_240_should_render_home' ) || ! qalam_240_should_render_home() ) { return; }
    status_header( 200 );
    nocache_headers();
    $brand = qalam_230_brand();
    qalam_240_render_shell_header();
    echo '<div class="q-ref-home q270-home q270-home-' . esc_attr( qalam_240_platform_type() ) . '">';
    if ( 'academy' === qalam_240_platform_type() ) { qalam_270_render_academy_home( $brand ); }
    else { qalam_270_render_individual_home( $brand ); }
    echo '</div>';
    qalam_240_render_shell_footer();
    exit;
}
add_action( 'template_redirect', 'qalam_270_render_home', 1 );

function qalam_270_frontend_picker_script(): void {
    if ( ! function_exists( 'qalam_240_should_render_home' ) || ! qalam_240_should_render_home() || 'academy' !== qalam_240_platform_type() ) { return; }
    wp_add_inline_script( 'qalam-reference-system', "document.addEventListener('click',function(e){var b=e.target.closest('[data-q270-picker-go]');if(!b)return;var box=b.closest('.q270-academy-picker'),t=box&&box.querySelector('[data-q270-teacher-select]'),s=box&&box.querySelector('[data-q270-subject-select]'),u=(s&&s.value)||(t&&t.value);if(u){window.location.href=u;}});" );
}
add_action( 'wp_enqueue_scripts', 'qalam_270_frontend_picker_script', 97 );

/** Runtime probes are intentionally excluded from production packages. */


function qalam_270_schema_once(): void {
    if ( get_option( QALAM_270_SCHEMA_OPTION ) !== QALAM_270_SCHEMA_VALUE ) {
        update_option( QALAM_270_SCHEMA_OPTION, QALAM_270_SCHEMA_VALUE, false );
    }
}
add_action( 'init', 'qalam_270_schema_once', 8 );
