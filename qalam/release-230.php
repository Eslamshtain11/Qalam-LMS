<?php
/**
 * Qalam LMS 0.23.0 — managed education platform foundation.
 *
 * Separates operational LMS settings (Qalam Dashboard) from platform design
 * controls (WordPress maintenance surface for Qalam operators only), and ships
 * the first theme-independent public platform shell.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_230_VERSION       = '0.23.0-platform-studio-foundation';
const QALAM_230_SCHEMA_OPTION = 'qalam_230_schema';
const QALAM_230_SCHEMA_VALUE  = '1';
const QALAM_230_BRAND_OPTION  = 'qalam_platform_brand_v1';
const QALAM_230_DESIGN_CAP    = 'qalam_manage_platform_design';

/**
 * Design access is intentionally independent from LMS ownership.
 * qalam_owner/qalam_manager never receive this capability.
 */
function qalam_230_install_design_capability(): void {
    $administrator = get_role( 'administrator' );
    if ( $administrator && ! $administrator->has_cap( QALAM_230_DESIGN_CAP ) ) {
        $administrator->add_cap( QALAM_230_DESIGN_CAP );
    }

    foreach ( array( 'qalam_owner', 'qalam_manager', 'tutor_instructor', 'subscriber', 'qalam_student' ) as $role_key ) {
        $role = get_role( $role_key );
        if ( $role && $role->has_cap( QALAM_230_DESIGN_CAP ) ) {
            $role->remove_cap( QALAM_230_DESIGN_CAP );
        }
    }

    if ( get_option( QALAM_230_SCHEMA_OPTION ) !== QALAM_230_SCHEMA_VALUE ) {
        update_option( QALAM_230_SCHEMA_OPTION, QALAM_230_SCHEMA_VALUE, false );
    }
}
add_action( 'init', 'qalam_230_install_design_capability', 4 );

/** Platform palettes are curated; clients never edit raw CSS tokens. */
function qalam_230_palettes(): array {
    return array(
        'academy-sky' => array(
            'label'       => 'مرجع الأكاديمية — سماوي',
            'primary'     => '#14B8E6',
            'primary_2'   => '#0B9ED0',
            'accent'      => '#7A61FF',
            'background'  => '#F7FBFD',
            'surface'     => '#FFFFFF',
            'text'        => '#17212B',
            'muted'       => '#687685',
            'border'      => '#DCEEF5',
            'hero_start'  => '#F8FDFF',
            'hero_end'    => '#E8F8FD',
        ),
        'individual-crimson' => array(
            'label'       => 'مرجع المدرس الفردي — قرمزي',
            'primary'     => '#D9284D',
            'primary_2'   => '#A81739',
            'accent'      => '#F1B842',
            'background'  => '#FCFAFB',
            'surface'     => '#FFFFFF',
            'text'        => '#171419',
            'muted'       => '#716A73',
            'border'      => '#EEE5E8',
            'hero_start'  => '#17131A',
            'hero_end'    => '#3A1621',
        ),
        'royal-purple' => array(
            'label'       => 'بنفسجي ملكي',
            'primary'     => '#6D4AFF',
            'primary_2'   => '#8B5CF6',
            'accent'      => '#F2B84B',
            'background'  => '#F7F6FC',
            'surface'     => '#FFFFFF',
            'text'        => '#17151F',
            'muted'       => '#6F6B7A',
            'border'      => '#E7E3F0',
            'hero_start'  => '#171024',
            'hero_end'    => '#3E247A',
        ),
        'deep-navy' => array(
            'label'       => 'كحلي احترافي',
            'primary'     => '#1E5EFF',
            'primary_2'   => '#2F7BFF',
            'accent'      => '#42D6B5',
            'background'  => '#F4F7FB',
            'surface'     => '#FFFFFF',
            'text'        => '#101828',
            'muted'       => '#667085',
            'border'      => '#E4E7EC',
            'hero_start'  => '#071426',
            'hero_end'    => '#123D73',
        ),
        'emerald' => array(
            'label'       => 'زمردي حديث',
            'primary'     => '#0F9D78',
            'primary_2'   => '#10B981',
            'accent'      => '#F59E0B',
            'background'  => '#F4FAF8',
            'surface'     => '#FFFFFF',
            'text'        => '#10231D',
            'muted'       => '#63736D',
            'border'      => '#DCEAE5',
            'hero_start'  => '#08241C',
            'hero_end'    => '#0A5A45',
        ),
        'ruby' => array(
            'label'       => 'قرمزي فاخر',
            'primary'     => '#D92D4B',
            'primary_2'   => '#F0445E',
            'accent'      => '#F4C95D',
            'background'  => '#FCF6F7',
            'surface'     => '#FFFFFF',
            'text'        => '#281317',
            'muted'       => '#7B676B',
            'border'      => '#F0E0E3',
            'hero_start'  => '#2C0C14',
            'hero_end'    => '#761D31',
        ),
        'midnight' => array(
            'label'       => 'داكن مميز',
            'primary'     => '#8B5CF6',
            'primary_2'   => '#A78BFA',
            'accent'      => '#F5C451',
            'background'  => '#0F1117',
            'surface'     => '#171A22',
            'text'        => '#F7F7FA',
            'muted'       => '#A7AAB5',
            'border'      => '#2B2F3A',
            'hero_start'  => '#090A0E',
            'hero_end'    => '#27184E',
        ),
    );
}

function qalam_230_brand_defaults(): array {
    return array(
        'platform_name'       => get_bloginfo( 'name' ) ?: 'منصتي التعليمية',
        'teacher_name'        => '',
        'teacher_title'       => 'مدرس محترف',
        'tagline'             => 'تعلم بوضوح، وتقدم بثقة.',
        'hero_title'          => 'تعليم أقرب، أوضح، وأكثر تأثيرًا',
        'hero_text'           => 'دورات منظمة، اختبارات ذكية، ومتابعة مستمرة في منصة تعليمية واحدة.',
        'teacher_bio'         => '',
        'logo_url'            => '',
        'hero_image_url'      => '',
        'teacher_image_url'   => '',
        'about_image_url'     => '',
        'whatsapp'            => '',
        'whatsapp_message'    => 'مرحبًا، أريد الاستفسار عن الدورات المتاحة.',
        'phone'               => '',
        'email'               => get_option( 'admin_email', '' ),
        'youtube'             => '',
        'facebook'            => '',
        'instagram'           => '',
        'telegram'            => '',
        'courses_title'       => 'الدورات المتاحة',
        'about_title'         => 'عن المدرس',
        'palette'             => 'royal-purple',
        'platform_type'       => 'academy',
        'appearance_mode'     => 'system',
        'custom_primary'      => '',
        'custom_primary_2'    => '',
        'custom_accent'       => '',
        'featured_courses'    => 6,
        'show_stats'          => 'on',
        'show_testimonials'   => 'off',
        'show_faq'            => 'off',
    );
}

function qalam_230_brand(): array {
    $stored = get_option( QALAM_230_BRAND_OPTION, array() );
    return wp_parse_args( is_array( $stored ) ? $stored : array(), qalam_230_brand_defaults() );
}

function qalam_230_sanitize_url_or_empty( $value ): string {
    $value = trim( (string) $value );
    return '' === $value ? '' : esc_url_raw( $value );
}

function qalam_230_sanitize_brand( array $input ): array {
    $defaults = qalam_230_brand_defaults();
    $palettes = qalam_230_palettes();
    $out = array();

    foreach ( array( 'platform_name','teacher_name','teacher_title','tagline','hero_title','courses_title','about_title' ) as $key ) {
        $out[ $key ] = sanitize_text_field( (string) ( $input[ $key ] ?? $defaults[ $key ] ) );
    }
    foreach ( array( 'hero_text','teacher_bio','whatsapp_message' ) as $key ) {
        $out[ $key ] = sanitize_textarea_field( (string) ( $input[ $key ] ?? $defaults[ $key ] ) );
    }
    foreach ( array( 'logo_url','hero_image_url','teacher_image_url','about_image_url','youtube','facebook','instagram','telegram' ) as $key ) {
        $out[ $key ] = qalam_230_sanitize_url_or_empty( $input[ $key ] ?? '' );
    }

    $out['whatsapp'] = preg_replace( '/[^0-9+]/', '', (string) ( $input['whatsapp'] ?? '' ) );
    $out['phone']     = preg_replace( '/[^0-9+()\- ]/', '', (string) ( $input['phone'] ?? '' ) );
    $out['email']     = sanitize_email( (string) ( $input['email'] ?? '' ) );
    $palette          = sanitize_key( (string) ( $input['palette'] ?? $defaults['palette'] ) );
    $out['palette']   = isset( $palettes[ $palette ] ) ? $palette : $defaults['palette'];
    $platform_type = sanitize_key( (string) ( $input['platform_type'] ?? $defaults['platform_type'] ) );
    $out['platform_type'] = in_array( $platform_type, array( 'academy', 'individual' ), true ) ? $platform_type : $defaults['platform_type'];
    $appearance_mode = sanitize_key( (string) ( $input['appearance_mode'] ?? $defaults['appearance_mode'] ) );
    $out['appearance_mode'] = in_array( $appearance_mode, array( 'system', 'light', 'dark' ), true ) ? $appearance_mode : $defaults['appearance_mode'];
    foreach ( array( 'custom_primary', 'custom_primary_2', 'custom_accent' ) as $color_key ) {
        $candidate = sanitize_hex_color( (string) ( $input[ $color_key ] ?? '' ) );
        $out[ $color_key ] = $candidate ?: '';
    }
    $out['featured_courses']  = max( 1, min( 12, absint( $input['featured_courses'] ?? $defaults['featured_courses'] ) ) );
    $out['show_stats']        = ! empty( $input['show_stats'] ) ? 'on' : 'off';
    $out['show_testimonials'] = ! empty( $input['show_testimonials'] ) ? 'on' : 'off';
    $out['show_faq']          = ! empty( $input['show_faq'] ) ? 'on' : 'off';

    return $out;
}

/** Only Qalam maintenance administrators can see this WordPress menu. */
function qalam_230_register_design_studio(): void {
    add_menu_page(
        'استوديو تصميم قلم',
        'تصميم قلم',
        QALAM_230_DESIGN_CAP,
        'qalam-design-studio',
        'qalam_230_render_design_studio',
        'dashicons-art',
        3
    );
}
add_action( 'admin_menu', 'qalam_230_register_design_studio', 50 );

function qalam_230_design_studio_assets( string $hook ): void {
    if ( 'toplevel_page_qalam-design-studio' !== $hook || ! current_user_can( QALAM_230_DESIGN_CAP ) ) { return; }
    wp_enqueue_media();
    wp_enqueue_style( 'qalam-design-studio', plugins_url( 'assets/css/qalam-design-studio.css', QALAM_LMS_FILE ), array(), QALAM_230_VERSION );
    wp_enqueue_script( 'qalam-design-studio', plugins_url( 'assets/js/qalam-design-studio.js', QALAM_LMS_FILE ), array( 'jquery' ), QALAM_230_VERSION, true );
    wp_localize_script( 'qalam-design-studio', 'QalamDesignStudio', array(
        'chooseImage' => 'اختيار الصورة',
        'useImage'    => 'استخدام الصورة',
        'previewUrl'  => home_url( '/?qalam_preview=1' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'qalam_230_design_studio_assets' );

function qalam_230_render_design_studio(): void {
    if ( ! current_user_can( QALAM_230_DESIGN_CAP ) ) { wp_die( 'غير مسموح.' ); }
    $brand = qalam_230_brand();
    $palettes = qalam_230_palettes();
    $saved = ! empty( $_GET['qalam_saved'] );
    ?>
    <div class="wrap qalam-design-studio-wrap" dir="rtl">
        <div class="qalam-design-studio-head">
            <div><span>أدوات مؤسسة قلم</span><h1>استوديو تصميم قلم</h1><p>جهّز هوية أي منصة من مكان واحد. هذه الصفحة لا تظهر لمالك أو مدير المنصة.</p></div>
            <a class="button button-secondary" target="_blank" rel="noopener" href="<?php echo esc_url( home_url( '/?qalam_preview=1' ) ); ?>">معاينة الموقع</a>
        </div>
        <?php if ( $saved ) : ?><div class="notice notice-success is-dismissible"><p>تم حفظ هوية وتصميم المنصة.</p></div><?php endif; ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qalam-design-form">
            <input type="hidden" name="action" value="qalam_230_save_design">
            <?php wp_nonce_field( 'qalam_230_save_design', 'qalam_design_nonce' ); ?>
            <div class="qalam-design-grid">
                <main>
                    <section class="qalam-design-card"><div class="qalam-card-title"><span>01</span><div><h2>هوية المنصة</h2><p>الاسم والشعار والمحتوى الأساسي الذي يراه العميل والطلاب.</p></div></div><div class="qalam-fields two">
                        <label><span>اسم المنصة</span><input name="brand[platform_name]" value="<?php echo esc_attr( $brand['platform_name'] ); ?>" required></label>
                        <label><span>اسم المدرس / الأكاديمية</span><input name="brand[teacher_name]" value="<?php echo esc_attr( $brand['teacher_name'] ); ?>"></label>
                        <label><span>الصفة</span><input name="brand[teacher_title]" value="<?php echo esc_attr( $brand['teacher_title'] ); ?>"></label>
                        <label><span>الجملة التعريفية</span><input name="brand[tagline]" value="<?php echo esc_attr( $brand['tagline'] ); ?>"></label>
                    </div><?php qalam_230_media_field( 'logo_url', 'الشعار', $brand['logo_url'] ); ?></section>

                    <section class="qalam-design-card"><div class="qalam-card-title"><span>02</span><div><h2>واجهة الصفحة الرئيسية</h2><p>محتوى ثابت التصميم ومتغير البيانات لكل عميل.</p></div></div><div class="qalam-fields">
                        <label><span>عنوان المقدمة</span><input name="brand[hero_title]" value="<?php echo esc_attr( $brand['hero_title'] ); ?>"></label>
                        <label><span>النص التعريفي</span><textarea name="brand[hero_text]" rows="3"><?php echo esc_textarea( $brand['hero_text'] ); ?></textarea></label>
                        <label><span>عنوان قسم الدورات</span><input name="brand[courses_title]" value="<?php echo esc_attr( $brand['courses_title'] ); ?>"></label>
                        <label><span>عدد الدورات الظاهرة</span><input type="number" min="1" max="12" name="brand[featured_courses]" value="<?php echo esc_attr( $brand['featured_courses'] ); ?>"></label>
                    </div><?php qalam_230_media_field( 'hero_image_url', 'صورة المقدمة', $brand['hero_image_url'] ); ?></section>

                    <section class="qalam-design-card"><div class="qalam-card-title"><span>03</span><div><h2>عن المدرس</h2><p>قسم مستقل بصريًا عن الـHero. اختر له صورة مختلفة حتى لا تتكرر صورة المقدمة.</p></div></div><div class="qalam-fields">
                        <label><span>عنوان القسم</span><input name="brand[about_title]" value="<?php echo esc_attr( $brand['about_title'] ); ?>"></label>
                        <label><span>النبذة</span><textarea name="brand[teacher_bio]" rows="7"><?php echo esc_textarea( $brand['teacher_bio'] ); ?></textarea></label>
                    </div><?php qalam_230_media_field( 'about_image_url', 'صورة قسم النبذة (مستقلة عن صورة الـHero)', $brand['about_image_url'] ); ?>
                    <?php qalam_230_media_field( 'teacher_image_url', 'صورة المدرس العامة (بطاقات المدرسين وأماكن أخرى)', $brand['teacher_image_url'] ); ?></section>

                    <section class="qalam-design-card"><div class="qalam-card-title"><span>04</span><div><h2>التواصل</h2><p>الأزرار والروابط التي تظهر للزائر مباشرة.</p></div></div><div class="qalam-fields two">
                        <label><span>واتساب</span><input name="brand[whatsapp]" value="<?php echo esc_attr( $brand['whatsapp'] ); ?>" placeholder="201xxxxxxxxx"></label>
                        <label><span>الهاتف</span><input name="brand[phone]" value="<?php echo esc_attr( $brand['phone'] ); ?>"></label>
                        <label><span>البريد الإلكتروني</span><input type="email" name="brand[email]" value="<?php echo esc_attr( $brand['email'] ); ?>"></label>
                        <label><span>رسالة واتساب الافتراضية</span><input name="brand[whatsapp_message]" value="<?php echo esc_attr( $brand['whatsapp_message'] ); ?>"></label>
                        <label><span>YouTube</span><input type="url" name="brand[youtube]" value="<?php echo esc_attr( $brand['youtube'] ); ?>"></label>
                        <label><span>Facebook</span><input type="url" name="brand[facebook]" value="<?php echo esc_attr( $brand['facebook'] ); ?>"></label>
                        <label><span>Instagram</span><input type="url" name="brand[instagram]" value="<?php echo esc_attr( $brand['instagram'] ); ?>"></label>
                        <label><span>Telegram</span><input type="url" name="brand[telegram]" value="<?php echo esc_attr( $brand['telegram'] ); ?>"></label>
                    </div></section>
                </main>
                <aside>
                    <section class="qalam-design-card qalam-sticky-card"><div class="qalam-card-title"><span>05</span><div><h2>نوع المنصة والتصميم</h2><p>اختيار واحد يبدّل هيكل الموقع كله، ثم Palette تغيّر ألوان النظام مركزيًا.</p></div></div>
                    <div class="qalam-platform-type-list">
                        <label class="qalam-type-option"><input type="radio" name="brand[platform_type]" value="academy" <?php checked( $brand['platform_type'], 'academy' ); ?>><span><strong>أكاديمية / منصة جماعية</strong><small>تصميم جماعي متعدد المدرسين والمواد والدورات.</small></span></label>
                        <label class="qalam-type-option"><input type="radio" name="brand[platform_type]" value="individual" <?php checked( $brand['platform_type'], 'individual' ); ?>><span><strong>مدرس فردي</strong><small>هوية شخصية للمدرس مع سنوات دراسية وكورسات ولوحة تميز.</small></span></label>
                    </div>
                    <label class="qalam-mode-select"><span>الوضع الافتراضي</span><select name="brand[appearance_mode]"><option value="system" <?php selected( $brand['appearance_mode'], 'system' ); ?>>حسب جهاز الزائر</option><option value="light" <?php selected( $brand['appearance_mode'], 'light' ); ?>>فاتح</option><option value="dark" <?php selected( $brand['appearance_mode'], 'dark' ); ?>>داكن</option></select></label>
                    <div class="qalam-palette-list">
                    <?php foreach ( $palettes as $key => $palette ) : ?>
                        <label class="qalam-palette-option"><input type="radio" name="brand[palette]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $brand['palette'], $key ); ?>><span class="qalam-palette-preview"><?php foreach ( array( 'primary','primary_2','accent','hero_start' ) as $token ) : ?><i style="background:<?php echo esc_attr( $palette[ $token ] ); ?>"></i><?php endforeach; ?></span><strong><?php echo esc_html( $palette['label'] ); ?></strong></label>
                    <?php endforeach; ?>
                    </div>
                    <div class="qalam-custom-colors"><strong>تعديل سريع اختياري</strong><small>اترك اللون فارغًا لاستخدام الـPalette المختارة.</small><div><label>أساسي <input type="text" name="brand[custom_primary]" value="<?php echo esc_attr( $brand['custom_primary'] ); ?>" placeholder="#6D4AFF" data-qalam-optional-color></label><label>ثانوي <input type="text" name="brand[custom_primary_2]" value="<?php echo esc_attr( $brand['custom_primary_2'] ); ?>" placeholder="#8B5CF6" data-qalam-optional-color></label><label>Accent <input type="text" name="brand[custom_accent]" value="<?php echo esc_attr( $brand['custom_accent'] ); ?>" placeholder="#F2B84B" data-qalam-optional-color></label></div></div>
                    <div class="qalam-switches"><label><input type="checkbox" name="brand[show_stats]" value="1" <?php checked( $brand['show_stats'], 'on' ); ?>><span>إظهار أرقام المنصة</span></label><label><input type="checkbox" name="brand[show_testimonials]" value="1" <?php checked( $brand['show_testimonials'], 'on' ); ?>><span>إظهار آراء الطلاب</span></label><label><input type="checkbox" name="brand[show_faq]" value="1" <?php checked( $brand['show_faq'], 'on' ); ?>><span>إظهار الأسئلة الشائعة</span></label></div>
                    <div class="qalam-required-brand"><strong>هوية قلم الإلزامية</strong><p>مؤسسة قلم للخدمات الإلكترونية</p><p>بكل فخر ❤️ صنع في مصر</p><small>لا تُعرض هذه النصوص كخيار للعميل ولا تتأثر بالباقة.</small></div>
                    <button class="button button-primary button-hero" type="submit">حفظ وتطبيق التصميم</button></section>
                </aside>
            </div>
        </form>
    </div>
    <?php
}

function qalam_230_media_field( string $key, string $label, string $value ): void {
    ?>
    <div class="qalam-media-field" data-qalam-media-field>
        <label><span><?php echo esc_html( $label ); ?></span><input type="url" name="brand[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" data-qalam-media-input></label>
        <button type="button" class="button" data-qalam-media-pick>اختيار صورة</button>
        <div class="qalam-media-preview" data-qalam-media-preview><?php if ( $value ) : ?><img src="<?php echo esc_url( $value ); ?>" alt=""><?php endif; ?></div>
    </div>
    <?php
}

function qalam_230_save_design(): void {
    check_admin_referer( 'qalam_230_save_design', 'qalam_design_nonce' );
    if ( ! current_user_can( QALAM_230_DESIGN_CAP ) ) { wp_die( 'غير مسموح.' ); }
    $input = isset( $_POST['brand'] ) && is_array( $_POST['brand'] ) ? (array) wp_unslash( $_POST['brand'] ) : array();
    $brand = qalam_230_sanitize_brand( $input );
    update_option( QALAM_230_BRAND_OPTION, $brand, false );

    // The public WordPress identity follows the managed platform identity.
    if ( '' !== $brand['platform_name'] ) {
        update_option( 'blogname', $brand['platform_name'] );
    }
    if ( '' !== $brand['tagline'] ) {
        update_option( 'blogdescription', $brand['tagline'] );
    }

    // Keep Tutor/Qalam native components on the same curated palette without
    // exposing the original Tutor Design tab to platform customers.
    $tutor_option = get_option( 'tutor_option', array() );
    $tutor_option = is_array( $tutor_option ) ? $tutor_option : array();
    $palette = qalam_230_palettes()[ $brand['palette'] ] ?? qalam_230_palettes()['royal-purple'];
    $tutor_option['brand_color'] = ! empty( $brand['custom_primary'] ) ? $brand['custom_primary'] : $palette['primary'];
    $tutor_option['learning_mode'] = $tutor_option['learning_mode'] ?? 'modern';
    update_option( 'tutor_option', $tutor_option, false );

    wp_safe_redirect( add_query_arg( 'qalam_saved', '1', admin_url( 'admin.php?page=qalam-design-studio' ) ) );
    exit;
}
add_action( 'admin_post_qalam_230_save_design', 'qalam_230_save_design' );

/**
 * Operational settings permissions.
 * Owners and platform managers operate the LMS; instructors/students do not
 * change platform-global settings. WordPress maintenance administrators retain
 * all operational settings for support and diagnostics.
 */
function qalam_230_user_can_manage_settings_tab( string $tab, $user = null ): bool {
    // 0.26 centralizes the operational role matrix while keeping this public
    // compatibility function for older dashboard/settings callers.
    if ( function_exists( 'qalam_260_user_can_manage_settings_tab' ) ) {
        return qalam_260_user_can_manage_settings_tab( $tab, $user );
    }
    if ( 'design' === sanitize_key( $tab ) ) { return false; }
    $user = $user instanceof WP_User ? $user : wp_get_current_user();
    if ( ! $user || ! $user->exists() ) { return false; }
    if ( user_can( $user, QALAM_230_DESIGN_CAP ) || user_can( $user, 'manage_options' ) ) { return true; }
    if ( array_intersect( array( 'qalam_owner', 'qalam_manager' ), (array) $user->roles ) ) {
        return user_can( $user, 'qalam_manage_settings' );
    }
    return false;
}

function qalam_230_filter_settings_for_user( array $all ): array {
    // The native Design tab is deliberately removed from Qalam Dashboard.
    // Platform appearance is controlled only from the protected Design Studio.
    unset( $all['design'] );
    foreach ( array_keys( $all ) as $tab ) {
        if ( ! qalam_230_user_can_manage_settings_tab( (string) $tab ) ) { unset( $all[ $tab ] ); }
    }
    return $all;
}

function qalam_230_current_palette(): array {
    $brand = qalam_230_brand();
    $palettes = qalam_230_palettes();
    return $palettes[ $brand['palette'] ] ?? reset( $palettes );
}

function qalam_230_css_variables(): string {
    $p = qalam_230_current_palette();
    return sprintf(
        '--qalam-primary:%1$s;--qalam-primary-2:%2$s;--qalam-accent:%3$s;--qalam-bg:%4$s;--qalam-surface:%5$s;--qalam-text:%6$s;--qalam-muted:%7$s;--qalam-border:%8$s;--qalam-hero-start:%9$s;--qalam-hero-end:%10$s;',
        esc_attr( $p['primary'] ), esc_attr( $p['primary_2'] ), esc_attr( $p['accent'] ), esc_attr( $p['background'] ), esc_attr( $p['surface'] ), esc_attr( $p['text'] ), esc_attr( $p['muted'] ), esc_attr( $p['border'] ), esc_attr( $p['hero_start'] ), esc_attr( $p['hero_end'] )
    );
}

function qalam_230_is_public_platform_request(): bool {
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) { return false; }
    if ( function_exists( 'qalam_210_is_dashboard_request' ) && qalam_210_is_dashboard_request() ) { return false; }
    if ( (int) get_query_var( 'qalam_login' ) === 1 ) { return false; }
    return true;
}

function qalam_230_enqueue_platform_assets(): void {
    if ( ! qalam_230_is_public_platform_request() ) { return; }
    wp_enqueue_style( 'qalam-platform', plugins_url( 'assets/css/qalam-platform.css', QALAM_LMS_FILE ), array(), QALAM_230_VERSION );
    wp_add_inline_style( 'qalam-platform', ':root{' . qalam_230_css_variables() . '}' );
}
add_action( 'wp_enqueue_scripts', 'qalam_230_enqueue_platform_assets', 90 );

/** Remove active-theme chrome from the managed homepage; Qalam owns this surface. */
function qalam_230_strip_theme_assets_from_home(): void {
    if ( ! qalam_230_should_render_home() ) { return; }
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
add_action( 'wp_enqueue_scripts', 'qalam_230_strip_theme_assets_from_home', PHP_INT_MAX );

/** First complete, theme-independent Qalam public homepage. */
function qalam_230_should_render_home(): bool {
    if ( ! qalam_230_is_public_platform_request() ) { return false; }
    if ( isset( $_GET['qalam_preview'] ) && current_user_can( QALAM_230_DESIGN_CAP ) ) { return true; }
    return is_front_page() || is_home();
}

function qalam_230_course_count(): int {
    $counts = wp_count_posts( 'courses' );
    return $counts && isset( $counts->publish ) ? (int) $counts->publish : 0;
}

function qalam_230_student_count(): int {
    $query = new WP_User_Query( array( 'role__in' => array( 'subscriber', 'qalam_student' ), 'fields' => 'ID', 'number' => 1, 'count_total' => true ) );
    return (int) $query->get_total();
}

function qalam_230_whatsapp_url( array $brand ): string {
    $number = preg_replace( '/\D+/', '', (string) $brand['whatsapp'] );
    if ( '' === $number ) { return ''; }
    return 'https://wa.me/' . $number . '?text=' . rawurlencode( (string) $brand['whatsapp_message'] );
}

function qalam_230_render_public_home(): void {
    if ( ! qalam_230_should_render_home() ) { return; }
    $brand = qalam_230_brand();
    $courses = new WP_Query( array(
        'post_type' => 'courses', 'post_status' => 'publish', 'posts_per_page' => (int) $brand['featured_courses'],
        'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true,
    ) );
    $whatsapp = qalam_230_whatsapp_url( $brand );
    status_header( 200 );
    nocache_headers();
    ?><!doctype html><html <?php language_attributes(); ?> dir="rtl"><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title><?php echo esc_html( $brand['platform_name'] ); ?></title><?php wp_head(); ?></head><body class="qalam-platform-home"><div class="qalam-platform-shell">
        <header class="qalam-public-header"><a class="qalam-public-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php if ( $brand['logo_url'] ) : ?><img src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt="<?php echo esc_attr( $brand['platform_name'] ); ?>"><?php else : ?><span class="qalam-public-brand-mark">ق</span><?php endif; ?><strong><?php echo esc_html( $brand['platform_name'] ); ?></strong></a><nav><a href="#courses">الدورات</a><?php if ( $brand['teacher_bio'] ) : ?><a href="#about">عن المدرس</a><?php endif; ?><a href="#contact">تواصل معنا</a></nav><div class="qalam-header-actions"><?php if ( is_user_logged_in() ) : ?><a class="qalam-btn ghost" href="<?php echo esc_url( function_exists( 'qalam_210_dashboard_url' ) && qalam_210_user_is_managed() ? qalam_210_dashboard_url() : tutor_utils()->tutor_dashboard_url() ); ?>">لوحتي</a><?php else : ?><a class="qalam-btn ghost" href="<?php echo esc_url( function_exists( 'qalam_220_login_url' ) ? qalam_220_login_url() : wp_login_url() ); ?>">تسجيل الدخول</a><?php endif; ?><a class="qalam-btn" href="#courses">ابدأ التعلم</a></div></header>
        <main><section class="qalam-hero"><div class="qalam-hero-copy"><span class="qalam-kicker"><?php echo esc_html( $brand['tagline'] ); ?></span><h1><?php echo esc_html( $brand['hero_title'] ); ?></h1><p><?php echo esc_html( $brand['hero_text'] ); ?></p><div class="qalam-hero-actions"><a class="qalam-btn large" href="#courses">استعرض الدورات</a><?php if ( $whatsapp ) : ?><a class="qalam-btn ghost large" target="_blank" rel="noopener" href="<?php echo esc_url( $whatsapp ); ?>">تواصل واتساب</a><?php endif; ?></div><?php if ( 'on' === $brand['show_stats'] ) : ?><div class="qalam-stats"><div><strong><?php echo esc_html( number_format_i18n( qalam_230_course_count() ) ); ?></strong><span>دورة منشورة</span></div><div><strong><?php echo esc_html( number_format_i18n( qalam_230_student_count() ) ); ?></strong><span>طالب</span></div><div><strong>24/7</strong><span>وصول للمحتوى</span></div></div><?php endif; ?></div><div class="qalam-hero-visual"><?php if ( $brand['hero_image_url'] ) : ?><img src="<?php echo esc_url( $brand['hero_image_url'] ); ?>" alt=""><?php else : ?><div class="qalam-hero-placeholder"><span>ق</span><strong><?php echo esc_html( $brand['platform_name'] ); ?></strong><small>تعليم منظم. تجربة واضحة.</small></div><?php endif; ?></div></section>

        <section class="qalam-section" id="courses"><div class="qalam-section-head"><div><span>ابدأ من هنا</span><h2><?php echo esc_html( $brand['courses_title'] ); ?></h2></div><a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ?: home_url( '/courses/' ) ); ?>">عرض كل الدورات</a></div><div class="qalam-course-grid">
        <?php if ( $courses->have_posts() ) : while ( $courses->have_posts() ) : $courses->the_post(); $course_id = get_the_ID(); $image = get_the_post_thumbnail_url( $course_id, 'large' ); $price = function_exists( 'tutor_utils' ) ? tutor_utils()->get_course_price( $course_id ) : ''; ?>
            <article class="qalam-course-card"><a class="qalam-course-image" href="<?php the_permalink(); ?>"><?php if ( $image ) : ?><img src="<?php echo esc_url( $image ); ?>" alt=""><?php else : ?><span>ق</span><?php endif; ?></a><div class="qalam-course-body"><span class="qalam-course-type">دورة تعليمية</span><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p><div class="qalam-course-meta"><strong><?php echo $price ? wp_kses_post( $price ) : 'مجاني'; ?></strong><a href="<?php the_permalink(); ?>">التفاصيل ←</a></div></div></article>
        <?php endwhile; wp_reset_postdata(); else : ?><div class="qalam-empty-public"><strong>لا توجد دورات متاحة حاليًا</strong><p>تابع المنصة لمعرفة أحدث الدورات عند نشرها.</p></div><?php endif; ?></div></section>

        <?php if ( $brand['teacher_name'] || $brand['teacher_bio'] ) : ?><section class="qalam-section qalam-about" id="about"><div class="qalam-about-image"><?php if ( $brand['teacher_image_url'] ) : ?><img src="<?php echo esc_url( $brand['teacher_image_url'] ); ?>" alt="<?php echo esc_attr( $brand['teacher_name'] ); ?>"><?php else : ?><span>صورة المدرس</span><?php endif; ?></div><div><span class="qalam-kicker">خبرة تعليمية موثوقة</span><h2><?php echo esc_html( $brand['about_title'] ); ?></h2><h3><?php echo esc_html( $brand['teacher_name'] ); ?></h3><small><?php echo esc_html( $brand['teacher_title'] ); ?></small><p><?php echo nl2br( esc_html( $brand['teacher_bio'] ) ); ?></p></div></section><?php endif; ?>

        <section class="qalam-contact-section" id="contact"><div><span>جاهز تبدأ؟</span><h2>ابدأ رحلتك التعليمية الآن</h2><p>اختر دورتك أو تواصل مباشرة للاستفسار عن الأنسب لك.</p></div><div class="qalam-contact-actions"><a class="qalam-btn light" href="#courses">تصفح الدورات</a><?php if ( $whatsapp ) : ?><a class="qalam-btn outline-light" target="_blank" rel="noopener" href="<?php echo esc_url( $whatsapp ); ?>">واتساب</a><?php endif; ?></div></section></main>
        <?php qalam_230_render_public_footer( $brand ); ?>
        <?php if ( $whatsapp ) : ?><a class="qalam-floating-whatsapp" target="_blank" rel="noopener" href="<?php echo esc_url( $whatsapp ); ?>" aria-label="التواصل عبر واتساب">واتساب</a><?php endif; ?>
    </div><?php wp_footer(); ?></body></html><?php
    exit;
}
add_action( 'template_redirect', 'qalam_230_render_public_home', 1 );

function qalam_230_render_public_footer( ?array $brand = null ): void {
    $brand = $brand ?: qalam_230_brand();
    ?><footer class="qalam-public-footer"><div><strong><?php echo esc_html( $brand['platform_name'] ); ?></strong><span><?php echo esc_html( $brand['tagline'] ); ?></span></div><div class="qalam-footer-qalam"><strong>مؤسسة قلم للخدمات الإلكترونية</strong><span>بكل فخر ❤️ صنع في مصر</span></div></footer><?php
}

/** Mandatory Qalam brand on legacy public templates until every surface is replaced. */
function qalam_230_legacy_brand_strip(): void {
    if ( ! qalam_230_is_public_platform_request() || qalam_230_should_render_home() ) { return; }
    echo '<div class="qalam-mandatory-brand-strip" dir="rtl"><strong>مؤسسة قلم للخدمات الإلكترونية</strong><span>بكل فخر ❤️ صنع في مصر</span></div>';
}
add_action( 'wp_footer', 'qalam_230_legacy_brand_strip', 99 );
