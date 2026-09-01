<?php
/**
 * Qalam LMS 0.28.0 — reference fidelity closure.
 *
 * Two Qalam-owned public design systems:
 * - academy: clean multi-teacher discovery experience.
 * - individual: personal single-teacher storytelling experience.
 *
 * Third-party code/assets are never bundled. Layout language is recreated with
 * Qalam components while customer names, colors, images and content stay dynamic.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_280_VERSION = '0.28.6';
const QALAM_280_SCHEMA_OPTION = 'qalam_280_schema';
const QALAM_280_SCHEMA_VALUE = '1';

function qalam_280_reference_palette_key( string $type ): string {
    return 'individual' === $type ? 'individual-crimson' : 'academy-sky';
}

/** Migrate only the old untouched purple default; explicit operator choices win. */
function qalam_280_migrate_reference_palette_once(): void {
    if ( get_option( QALAM_280_SCHEMA_OPTION ) === QALAM_280_SCHEMA_VALUE ) { return; }
    if ( defined( 'QALAM_230_BRAND_OPTION' ) ) {
        $stored = get_option( QALAM_230_BRAND_OPTION, array() );
        $stored = is_array( $stored ) ? $stored : array();
        $type = 'individual' === ( $stored['platform_type'] ?? 'academy' ) ? 'individual' : 'academy';
        $has_custom = ! empty( $stored['custom_primary'] ) || ! empty( $stored['custom_primary_2'] ) || ! empty( $stored['custom_accent'] );
        $palette = (string) ( $stored['palette'] ?? 'royal-purple' );
        if ( ! $has_custom && in_array( $palette, array( '', 'royal-purple' ), true ) ) {
            $stored['palette'] = qalam_280_reference_palette_key( $type );
            update_option( QALAM_230_BRAND_OPTION, $stored, false );
        }
    }
    update_option( QALAM_280_SCHEMA_OPTION, QALAM_280_SCHEMA_VALUE, false );
}
add_action( 'init', 'qalam_280_migrate_reference_palette_once', 9 );

function qalam_280_enqueue_fidelity_assets(): void {
    if ( ! function_exists( 'qalam_240_is_public_surface' ) || ! qalam_240_is_public_surface() ) { return; }
    wp_enqueue_style( 'qalam-reference-fidelity', plugins_url( 'assets/css/qalam-reference-fidelity.css', QALAM_LMS_FILE ), array( 'qalam-reference-precision' ), QALAM_280_VERSION );
    wp_enqueue_script( 'qalam-reference-fidelity', plugins_url( 'assets/js/qalam-reference-fidelity.js', QALAM_LMS_FILE ), array( 'qalam-reference-system' ), QALAM_280_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'qalam_280_enqueue_fidelity_assets', 99 );

function qalam_280_course_archive_url(): string {
    $url = get_post_type_archive_link( 'courses' );
    return $url ? $url : home_url( '/courses/' );
}
function qalam_280_registration_url(): string {
    if ( function_exists( 'tutor_utils' ) ) {
        $id = (int) tutor_utils()->get_option( 'student_register_page', 0 );
        if ( $id ) { return get_permalink( $id ); }
    }
    return function_exists( 'qalam_220_login_url' ) ? qalam_220_login_url() : wp_registration_url();
}
function qalam_280_teacher_url( $teacher ): string {
    return $teacher instanceof WP_User && function_exists( 'tutor_utils' ) ? (string) tutor_utils()->profile_url( $teacher->ID ) : '#courses';
}

function qalam_280_render_academy_picker( array $instructors, array $categories ): void {
    if ( ! $instructors && ! $categories ) { return; }
    ?><section class="q28-academy-picker-section" id="discover"><div class="q-ref-container"><div class="q28-picker-shell" data-qalam-reveal>
        <div class="q28-picker-intro"><span>ابدأ من هنا</span><h2>اختار المحتوى المناسب ليك</h2><p>حدد اختيارك ووصل للكورسات المتاحة بسرعة.</p></div>
        <div class="q28-picker-fields">
            <?php if ( $instructors ) : ?><label><span>المدرس</span><select data-q280-teacher-select><option value="">اختر المدرس</option><?php foreach ( $instructors as $teacher ) : ?><option value="<?php echo esc_attr( qalam_280_teacher_url( $teacher ) ); ?>"><?php echo esc_html( $teacher->display_name ); ?></option><?php endforeach; ?></select></label><?php endif; ?>
            <?php if ( $categories ) : ?><label><span>المسار / المادة</span><select data-q280-subject-select><option value="">اختر المسار</option><?php foreach ( $categories as $term ) : $u = get_term_link( $term ); if ( is_wp_error( $u ) ) { continue; } ?><option value="<?php echo esc_attr( $u ); ?>"><?php echo esc_html( $term->name ); ?></option><?php endforeach; ?></select></label><?php endif; ?>
            <button type="button" class="q28-solid-btn" data-q280-picker-go>عرض المحتوى</button>
        </div>
    </div></div></section><?php
}

function qalam_280_render_academy_teacher_cards( array $brand, array $instructors ): void {
    if ( ! $instructors && ( ! empty( $brand['teacher_name'] ) || ! empty( $brand['teacher_image_url'] ) ) ) {
        $managed = (object) array( 'ID' => 0, 'display_name' => $brand['teacher_name'] ?: $brand['platform_name'] );
        $instructors = array( $managed );
    }
    if ( ! $instructors ) { return; }
    echo '<div class="q28-academy-teacher-grid">';
    foreach ( array_slice( $instructors, 0, 8 ) as $index => $teacher ) {
        $id = (int) ( $teacher->ID ?? 0 );
        $name = (string) ( $teacher->display_name ?? $brand['platform_name'] );
        $photo = ( 0 === (int) $index && ! empty( $brand['teacher_image_url'] ) ) ? $brand['teacher_image_url'] : ( $id ? get_avatar_url( $id, array( 'size' => 700 ) ) : $brand['teacher_image_url'] );
        $bio = $id ? (string) get_user_meta( $id, '_tutor_profile_bio', true ) : (string) $brand['teacher_bio'];
        $url = $id ? qalam_280_teacher_url( get_userdata( $id ) ) : '#courses';
        echo '<a class="q28-academy-teacher-card" data-qalam-reveal href="' . esc_url( $url ) . '"><div class="q28-teacher-media">';
        if ( $photo ) { echo '<img src="' . esc_url( $photo ) . '" alt="' . esc_attr( $name ) . '">'; }
        else { echo '<span>' . esc_html( mb_substr( $name ?: 'ق', 0, 1 ) ) . '</span>'; }
        echo '<i class="q28-teacher-dot"></i></div><div class="q28-teacher-copy"><small>مدرس المنصة</small><h3>' . esc_html( $name ) . '</h3>'; if ( '' !== trim( $bio ) ) { echo '<p>' . esc_html( wp_trim_words( $bio, 13 ) ) . '</p>'; } echo '<strong>عرض المدرس</strong></div></a>';
    }
    echo '</div>';
}

function qalam_280_render_academy_home( array $brand ): void {
    $courses = qalam_240_course_query( (int) $brand['featured_courses'] );
    $instructors = qalam_240_instructors();
    $categories = qalam_240_course_categories();
    $hero = $brand['hero_image_url'] ?: ( $brand['teacher_image_url'] ?: $brand['logo_url'] );
    $whatsapp = function_exists( 'qalam_230_whatsapp_url' ) ? qalam_230_whatsapp_url( $brand ) : '';
    ?><section class="q28-academy-hero"><div class="q-ref-container q28-academy-hero-grid">
        <div class="q28-academy-copy" data-qalam-reveal><span class="q28-brand-kicker"><?php echo esc_html( $brand['platform_name'] ); ?></span><h1><?php echo esc_html( $brand['hero_title'] ?: 'منصة متكاملة بها كل ما يحتاجه الطالب ليتفوق' ); ?></h1><p><?php echo esc_html( $brand['hero_text'] ); ?></p><div class="q28-actions"><a class="q28-solid-btn" href="#discover">اختار مدرسك</a><a class="q28-outline-btn" href="#courses">تصفح الكورسات</a></div><div class="q28-trust-row"><span><i>✓</i> شرح منظم</span><span><i>✓</i> اختبارات</span><span><i>✓</i> متابعة</span></div></div>
        <div class="q28-academy-hero-media" data-qalam-reveal><div class="q28-blob"></div><?php if ( $hero ) : ?><img src="<?php echo esc_url( $hero ); ?>" alt="<?php echo esc_attr( $brand['platform_name'] ); ?>"><?php else : ?><div class="q28-photo-fallback">ق</div><?php endif; ?><span class="q28-mini-card q28-mini-card-one"><b>+<?php echo esc_html( number_format_i18n( qalam_230_course_count() ) ); ?></b><small>كورس</small></span><span class="q28-mini-card q28-mini-card-two"><b>24/7</b><small>وصول</small></span></div>
    </div></section>
    <?php qalam_280_render_academy_picker( $instructors, $categories ); ?>
    <?php if ( $instructors || ! empty( $brand['teacher_name'] ) || ! empty( $brand['teacher_image_url'] ) ) : ?><section class="q28-section q28-white" id="teachers"><div class="q-ref-container"><div class="q28-section-head" data-qalam-reveal><div><span>مدرسو المنصة</span><h2>اختار مدرسك وابدأ</h2><p>اختار المدرس المناسب ليك وابدأ معاه.</p></div></div><?php qalam_280_render_academy_teacher_cards( $brand, $instructors ); ?></div></section><?php endif; ?>
    <section class="q28-section q28-soft" id="courses"><div class="q-ref-container"><div class="q28-section-head" data-qalam-reveal><div><span>كورساتنا المقترحة</span><h2><?php echo esc_html( $brand['courses_title'] ); ?></h2><p>أحدث الكورسات المنشورة على المنصة.</p></div><a href="<?php echo esc_url( qalam_280_course_archive_url() ); ?>">عرض كل الكورسات</a></div><?php qalam_240_render_course_cards( $courses, 'academy' ); ?></div></section>
    <section class="q28-academy-search"><div class="q-ref-container q28-search-shell" data-qalam-reveal><div><span>تدوّر على كورس معين؟</span><h2>كل محتوى المنصة في مكان واحد</h2><p>استخدم صفحة الكورسات والفلاتر للوصول للمادة والمدرس المناسبين بسرعة.</p></div><a class="q28-solid-btn q28-solid-light" href="<?php echo esc_url( qalam_280_course_archive_url() ); ?>">تصفح الكورسات</a></div></section>
    <?php if ( $categories ) : ?><section class="q28-section q28-white" id="subjects"><div class="q-ref-container"><div class="q28-section-head" data-qalam-reveal><div><span>استكشف المحتوى</span><h2>اختار مسارك وابدأ</h2><p>اختار من المسارات المتاحة ووصل للكورسات المناسبة ليك.</p></div></div><div class="q28-subject-grid"><?php foreach ( array_slice( $categories, 0, 9 ) as $term ) : $u = get_term_link( $term ); if ( is_wp_error( $u ) ) { continue; } ?><a class="q28-subject-card" data-qalam-reveal href="<?php echo esc_url( $u ); ?>"><i><?php echo esc_html( mb_substr( $term->name, 0, 1 ) ); ?></i><div><h3><?php echo esc_html( $term->name ); ?></h3><small><?php echo esc_html( number_format_i18n( $term->count ) ); ?> كورس</small></div><b>←</b></a><?php endforeach; ?></div></div></section><?php endif; ?>
    <?php qalam_240_render_features_section( 'academy' ); ?>
    <section class="q28-anywhere"><div class="q-ref-container q28-anywhere-grid"><div class="q28-anywhere-art" data-qalam-reveal><?php if ( $brand['teacher_image_url'] ) : ?><img src="<?php echo esc_url( $brand['teacher_image_url'] ); ?>" alt=""><?php elseif ( $brand['logo_url'] ) : ?><img class="is-logo" src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt=""><?php else : ?><span>ق</span><?php endif; ?></div><div data-qalam-reveal><span>ذاكر في أي وقت</span><h2>في أي مكان ومن أي جهاز</h2><p>الدروس والاختبارات والمتابعة متاحة من الموبايل والتابلت والكمبيوتر.</p><?php if ( $whatsapp ) : ?><a class="q28-outline-btn" target="_blank" rel="noopener" href="<?php echo esc_url( $whatsapp ); ?>">تواصل واتساب</a><?php endif; ?></div></div></section>
    <section class="q28-team-cta"><div class="q-ref-container" data-qalam-reveal><div><span>فريق المنصة</span><h2>منصة جماعية جاهزة لأكثر من مدرس</h2><p>إدارة المدرسين والدورات والطلاب والاختبارات من مكان واحد.</p></div><a class="q28-solid-btn q28-solid-light" href="#teachers">شاهد المدرسين</a></div></section><?php
}

function qalam_280_render_individual_home( array $brand ): void {
    $courses = qalam_240_course_query( (int) $brand['featured_courses'] );
    $portrait = $brand['hero_image_url'] ?: ( $brand['teacher_image_url'] ?: $brand['logo_url'] );
    $teacher = $brand['teacher_name'] ?: $brand['platform_name'];
    ?><section class="q28-individual-hero"><div class="q-ref-container q28-individual-hero-grid"><div class="q28-individual-copy" data-qalam-reveal><span class="q28-person-name">د / <?php echo esc_html( $teacher ); ?></span><h1><?php echo esc_html( $brand['hero_title'] ?: 'رحلتك بتبدأ بخطوة واضحة.. وأنت واثق' ); ?></h1><p><?php echo esc_html( $brand['hero_text'] ); ?></p><a class="q28-individual-btn" href="<?php echo esc_url( qalam_280_registration_url() ); ?>">سجل وخد أول خطوة</a></div><div class="q28-individual-portrait" data-qalam-reveal><span class="q28-atom a">✦</span><span class="q28-atom b">✧</span><div class="q28-person-shape"></div><?php if ( $portrait ) : ?><img src="<?php echo esc_url( $portrait ); ?>" alt="<?php echo esc_attr( $teacher ); ?>"><?php else : ?><span class="q28-person-fallback">ق</span><?php endif; ?></div></div></section>
    <section class="q28-section q28-individual-about" id="about"><div class="q-ref-container q28-about-layout<?php echo empty( $brand['about_image_url'] ) ? ' q28-about-layout-no-media' : ''; ?>"><?php if ( ! empty( $brand['about_image_url'] ) ) : ?><div class="q28-about-media" data-qalam-reveal><div class="q28-about-image"><img src="<?php echo esc_url( $brand['about_image_url'] ); ?>" alt="<?php echo esc_attr( $teacher ); ?>"></div><div class="q28-about-caption"><small>تعرف على المدرس</small><strong><?php echo esc_html( $teacher ); ?></strong></div></div><?php endif; ?><div class="q28-about-copy" data-qalam-reveal><span>منصة <?php echo esc_html( $teacher ); ?></span><h2><?php echo esc_html( $brand['about_title'] ?: 'مين المدرس؟' ); ?></h2><h3>شرح بسيط.. ومضمون يوصل للهدف</h3><?php if ( ! empty( $brand['teacher_bio'] ) ) : ?><p><?php echo nl2br( esc_html( $brand['teacher_bio'] ) ); ?></p><?php endif; ?><div class="q28-about-metrics"><div><b><?php echo esc_html( number_format_i18n( qalam_230_course_count() ) ); ?></b><small>كورس منشور</small></div><div><b><?php echo esc_html( number_format_i18n( qalam_230_student_count() ) ); ?></b><small>طالب</small></div><div><b>24/7</b><small>وصول</small></div></div></div></div></section>
    <section class="q28-section q28-individual-courses" id="courses"><div class="q-ref-container"><div class="q28-section-head q28-individual-head" data-qalam-reveal><div><span>الكورسات المقترحة</span><h2><?php echo esc_html( $brand['courses_title'] ); ?></h2><p>اختار الكورس المناسب وابدأ خطوة بخطوة.</p></div><a href="<?php echo esc_url( qalam_280_course_archive_url() ); ?>">عرض الكل</a></div><?php qalam_240_render_course_cards( $courses, 'individual' ); ?></div></section>
    <?php qalam_240_render_grade_tracks(); ?>
    <?php qalam_240_render_features_section( 'individual' ); ?>
    <section class="q28-individual-cta"><div class="q-ref-container" data-qalam-reveal><span>ابدأ مرحلة جديدة</span><h2>خد أول خطوة في رحلتك التعليمية</h2><p>سجل حسابك، اختار كورسك، وابدأ من أي جهاز.</p><a class="q28-individual-btn q28-individual-btn-light" href="<?php echo esc_url( qalam_280_registration_url() ); ?>">انشئ حسابك الآن</a></div></section><?php
}

remove_action( 'template_redirect', 'qalam_270_render_home', 1 );
function qalam_280_render_home(): void {
    if ( ! function_exists( 'qalam_240_should_render_home' ) || ! qalam_240_should_render_home() ) { return; }
    status_header( 200 ); nocache_headers(); $brand = qalam_230_brand();
    qalam_240_render_shell_header();
    echo '<div class="q28-home q28-home-' . esc_attr( qalam_240_platform_type() ) . '">';
    if ( 'academy' === qalam_240_platform_type() ) { qalam_280_render_academy_home( $brand ); } else { qalam_280_render_individual_home( $brand ); }
    echo '</div>'; qalam_240_render_shell_footer(); exit;
}
add_action( 'template_redirect', 'qalam_280_render_home', 1 );

function qalam_280_course_archive_intro(): void {
    if ( wp_doing_ajax() || ( function_exists( 'qalam_260_public_surface_key' ) && 'course-archive' !== qalam_260_public_surface_key() ) ) { return; }
    static $done = false; if ( $done ) { return; } $done = true;
    $type = function_exists( 'qalam_240_platform_type' ) ? qalam_240_platform_type() : 'academy';
    ?><div class="q28-course-archive-intro q28-course-archive-intro-<?php echo esc_attr( $type ); ?>" data-qalam-reveal><span><?php echo 'academy' === $type ? 'كل المدرسين والكورسات' : 'كورسات المدرس'; ?></span><h1>اختار الكورس المناسب ليك</h1><p>استخدم البحث والفلاتر علشان توصل للمحتوى المطلوب بسرعة.</p></div><?php
}
add_action( 'tutor_course/archive/before_loop', 'qalam_280_course_archive_intro', 1 );
