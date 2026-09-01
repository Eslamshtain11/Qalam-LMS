<?php
/**
 * Qalam LMS 0.14 — F3 Product Identity, F4 Arabic/RTL, F5 Design System,
 * unified-product cleanup, and a standalone public exam route.
 *
 * The internal Tutor course/topic used by general quizzes remains only as a
 * compatibility/storage layer. Students never navigate that course.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_140_EXAM_TOKEN_META   = '_qalam_exam_public_token';
const QALAM_140_ROUTE_VERSION_OPT = 'qalam_140_route_version';
const QALAM_140_ROUTE_VERSION     = '1';

/* -------------------------------------------------------------------------
 * Unified product: there is no Free/Pro split in Qalam.
 * These filters are registered before Tutor Config/Admin are instantiated.
 * ---------------------------------------------------------------------- */
add_filter( 'tutor_has_pro', '__return_true', PHP_INT_MAX );
add_filter( 'tutor_pro_flag', static fn() => '', PHP_INT_MAX );

function qalam_140_remove_upsell_menu( $menu ) {
    if ( ! is_array( $menu ) ) { return $menu; }
    foreach ( $menu as $group_key => &$group ) {
        if ( ! is_array( $group ) ) { continue; }
        foreach ( $group as $key => $item ) {
            if ( 'upgrade_to_pro' === $key ) { unset( $group[$key] ); continue; }
            if ( ! is_array( $item ) ) { continue; }
            $slug  = (string) ( $item['menu_slug'] ?? '' );
            $title = wp_strip_all_tags( (string) ( $item['menu_title'] ?? '' ) );
            if ( 'tutor-get-pro' === $slug || false !== stripos( $title, 'Upgrade to Pro' ) || false !== stripos( $title, 'Get Pro' ) ) {
                unset( $group[$key] );
            }
        }
    }
    unset( $group );
    return $menu;
}
add_filter( 'tutor_admin_menu', 'qalam_140_remove_upsell_menu', PHP_INT_MAX );

function qalam_140_plugin_action_links( $actions ) {
    if ( is_array( $actions ) ) {
        unset( $actions['tutor_pro_link'] );
        foreach ( $actions as $key => $html ) {
            if ( false !== stripos( (string) $html, 'Upgrade to Pro' ) || false !== stripos( (string) $html, 'tutorlms.com/pricing' ) ) {
                unset( $actions[$key] );
            }
        }
    }
    return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename( TUTOR_FILE ), 'qalam_140_plugin_action_links', PHP_INT_MAX );

add_action( 'admin_init', static function () {
    if ( isset( $_GET['page'] ) && 'tutor-get-pro' === sanitize_key( (string) $_GET['page'] ) ) {
        wp_safe_redirect( admin_url( 'admin.php?page=tutor' ) );
        exit;
    }
}, 1 );

/* -------------------------------------------------------------------------
 * F3 + F4: centralized visible terminology, Arabic-first UI and RTL.
 * ---------------------------------------------------------------------- */
function qalam_140_dictionary( $map ) {
    $extra = array(
        'Tutor LMS' => 'Qalam LMS',
        'Tutor' => 'قلم',
        'Upgrade to Pro' => 'Qalam LMS',
        'Get Pro' => 'Qalam LMS',
        'Pro' => '',
        'Dashboard' => 'لوحة التحكم',
        'My Dashboard' => 'لوحة التحكم',
        'Course Builder' => 'منشئ الدورات',
        'Quiz Builder' => 'منشئ الاختبارات',
        'Question Bank' => 'بنك الأسئلة',
        'Content Bank' => 'بنك المحتوى',
        'All Courses' => 'كل الدورات',
        'My Courses' => 'دوراتي',
        'Create Course' => 'إنشاء دورة',
        'Create a New Course' => 'إنشاء دورة جديدة',
        'Add New Course' => 'إضافة دورة جديدة',
        'All Students' => 'كل الطلاب',
        'All Instructors' => 'كل المعلمين',
        'All Categories' => 'كل التصنيفات',
        'All Tags' => 'كل الوسوم',
        'Announcements' => 'الإعلانات',
        'Quiz Attempts' => 'محاولات الاختبارات',
        'Questions & Answers' => 'الأسئلة والأجوبة',
        'Q&A' => 'الأسئلة والأجوبة',
        'Enrollments' => 'التسجيلات',
        'Orders' => 'الطلبات',
        'Coupons' => 'الكوبونات',
        'Withdraw Requests' => 'طلبات السحب',
        'Withdrawals' => 'طلبات السحب',
        'Themes' => 'القوالب',
        'Tools' => 'الأدوات',
        'Add-ons' => 'الملحقات',
        'Addons' => 'الملحقات',
        'What’s New' => 'ما الجديد',
        "What's New" => 'ما الجديد',
        'Reports' => 'التقارير',
        'Report' => 'التقرير',
        'Gradebook' => 'سجل الدرجات',
        'Certificates' => 'الشهادات',
        'Certificate' => 'الشهادة',
        'Assignments' => 'الواجبات',
        'Assignment' => 'الواجب',
        'Settings' => 'الإعدادات',
        'General Settings' => 'الإعدادات العامة',
        'Advanced Settings' => 'الإعدادات المتقدمة',
        'Payment Methods' => 'طرق الدفع',
        'Supported payment methods' => 'طرق الدفع المدعومة',
        'Add New Gateway' => 'إضافة بوابة دفع',
        'Add Manual Payment' => 'إضافة دفع يدوي',
        'Environment' => 'بيئة التشغيل',
        'Merchant email' => 'البريد الإلكتروني للتاجر',
        'Client id' => 'معرّف العميل',
        'Secret id' => 'المفتاح السري',
        'Webhook id' => 'معرّف Webhook',
        'Webhook url' => 'رابط Webhook',
        'Taxes' => 'الضرائب',
        'Checkout' => 'إتمام الدفع',
        'Subscriptions' => 'الاشتراكات',
        'Design' => 'التصميم',
        'Email' => 'البريد',
        'Notifications' => 'الإشعارات',
        'Authentication' => 'المصادقة',
        'Legal' => 'الموافقات القانونية',
        'Privacy Policy' => 'سياسة الخصوصية',
        'Terms and Conditions' => 'الشروط والأحكام',
        'Select Option' => 'اختر',
        'Reset to Default' => 'استعادة الإعدادات الافتراضية',
        'Save Changes' => 'حفظ التغييرات',
        'Save changes' => 'حفظ التغييرات',
        'Apply' => 'تطبيق',
        'Filter' => 'فلترة',
        'Filters' => 'الفلاتر',
        'Bulk Action' => 'إجراء جماعي',
        'Bulk Actions' => 'إجراءات جماعية',
        'Search' => 'بحث',
        'Search...' => 'بحث...',
        'No Data Found' => 'لا توجد بيانات',
        'No data found' => 'لا توجد بيانات',
        'No results found' => 'لا توجد نتائج',
        'Create Announcement' => 'إنشاء إعلان',
        'Add New Announcement' => 'إضافة إعلان جديد',
        'Notify all students of your course' => 'أرسل تنبيهًا لكل طلاب الدورة',
        'Course Prerequisites' => 'المتطلبات السابقة للدورة',
        'Search courses for prerequisites' => 'ابحث عن دورة لإضافتها كمتطلب سابق',
        'No course selected' => 'لم يتم اختيار دورة',
        'Select a course to add as a prerequisite.' => 'اختر دورة لإضافتها كمتطلب سابق.',
        'Create a Zoom Meeting' => 'إنشاء اجتماع Zoom',
        'Set API' => 'إعداد الربط',
        'Help' => 'المساعدة',
        'Setup your Google Meet Integration' => 'إعداد تكامل Google Meet',
        'Choose a file' => 'اختيار ملف',
        'Drag & Drop your JSON File here, or' => 'اسحب ملف JSON هنا أو',
        'Copy' => 'نسخ',
        'Start Quiz' => 'ابدأ الاختبار',
        'Skip Quiz' => 'تخطي الاختبار',
        'Skip Question' => 'تخطي السؤال',
        'Next' => 'التالي',
        'Previous' => 'السابق',
        'Back' => 'السابق',
        'Finish' => 'إنهاء',
        'Submit Quiz' => 'إنهاء الاختبار',
        'Submit & Next' => 'حفظ والتالي',
        'Questions' => 'الأسئلة',
        'Total Questions' => 'إجمالي الأسئلة',
        'Total Marks' => 'إجمالي الدرجات',
        'Passing Grade' => 'درجة النجاح',
        'Earned Grade' => 'الدرجة المحققة',
        'Correct' => 'صحيح',
        'Incorrect' => 'خطأ',
        'correct' => 'صحيح',
        'incorrect' => 'خطأ',
        'True' => 'صح',
        'False' => 'خطأ',
        'Single Choice' => 'اختيار واحد',
        'Multiple Choice' => 'اختيارات متعددة',
        'Open Ended' => 'مقالي',
        'Short Answer' => 'إجابة قصيرة',
        'Fill In The Blanks' => 'أكمل الفراغات',
        'Matching' => 'توصيل',
        'Image Matching' => 'توصيل بالصور',
        'Image Answering' => 'إجابة بالصورة',
        'Ordering' => 'ترتيب',
        'Image Marking' => 'تحديد على الصورة',
        'Range' => 'مدى',
        'Pin' => 'تحديد نقطة',
        'Graph' => 'رسم بياني',
        'Puzzle' => 'لغز',
        'Easy' => 'سهل',
        'Medium' => 'متوسط',
        'Hard' => 'صعب',
        'All Time' => 'كل الوقت',
        'Total Courses' => 'إجمالي الدورات',
        'Total Students' => 'إجمالي الطلاب',
        'Total Earnings' => 'إجمالي الأرباح',
        'Avg. Rating' => 'متوسط التقييم',
        'Complete Your Profile' => 'أكمل ملفك الشخصي',
        'Set Your Profile Photo' => 'أضف صورتك الشخصية',
        'Profile' => 'الملف الشخصي',
        'Logout' => 'تسجيل الخروج',
        'Continue Learning' => 'متابعة التعلم',
        'Continue Lesson' => 'متابعة الدرس',
        'Start Learning' => 'ابدأ التعلم',
        'Course' => 'الدورة',
        'Lesson' => 'الدرس',
        'Quiz' => 'الاختبار',
        'Student' => 'الطالب',
        'Instructor' => 'المعلم',
        'Students' => 'الطلاب',
        'Instructors' => 'المعلمون',
        'Status' => 'الحالة',
        'Date' => 'التاريخ',
        'Actions' => 'الإجراءات',
        'Action' => 'الإجراء',
        'View' => 'عرض',
        'Edit' => 'تعديل',
        'Delete' => 'حذف',
        'Cancel' => 'إلغاء',
        'Save' => 'حفظ',
        'Update' => 'تحديث',
        'Close' => 'إغلاق',
        'Preview' => 'معاينة',
        'Publish' => 'نشر',
        'Draft' => 'مسودة',
        'Pending' => 'قيد المراجعة',
        'Active' => 'نشط',
        'Inactive' => 'غير نشط',
        'Enabled' => 'مفعّل',
        'Disabled' => 'معطّل',
    );
    return array_replace( (array) $map, $extra );
}
add_filter( 'qalam_lms_dictionary', 'qalam_140_dictionary', PHP_INT_MAX );

/** Inject Arabic locale data on frontend as well as admin before dynamic bundles use wp.i18n. */
function qalam_140_locale_data(): void {
    if ( is_admin() && function_exists( 'qalam_is_product_admin_surface' ) && ! qalam_is_product_admin_surface() ) { return; }
    wp_enqueue_script( 'wp-i18n' );
    $locale = array( '' => array( 'domain'=>'tutor','lang'=>'ar','plural-forms'=>'nplurals=6; plural=n==0?0:n==1?1:n==2?2:n%100>=3&&n%100<=10?3:n%100>=11&&n%100<=99?4:5;' ) );
    foreach ( qalam_lms_dictionary() as $source => $translated ) { $locale[(string)$source] = array( (string) $translated ); }
    $json = wp_json_encode( $locale, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    wp_add_inline_script( 'wp-i18n', 'if(window.wp&&wp.i18n&&wp.i18n.setLocaleData){wp.i18n.setLocaleData('.$json.',"tutor");wp.i18n.setLocaleData('.$json.',"tutor-pro");}', 'after' );
}
add_action( 'admin_enqueue_scripts', 'qalam_140_locale_data', -100 );
add_action( 'wp_enqueue_scripts', 'qalam_140_locale_data', -100 );

/* -------------------------------------------------------------------------
 * Standalone exams: /exam/<token>/ never enters Tutor's course learning area.
 * ---------------------------------------------------------------------- */
function qalam_140_exam_token( int $quiz_id ): string {
    $token = (string) get_post_meta( $quiz_id, QALAM_140_EXAM_TOKEN_META, true );
    if ( preg_match( '/^[a-z0-9]{20,40}$/', $token ) ) { return $token; }
    do { $token = strtolower( wp_generate_password( 28, false, false ) ); $exists = get_posts( array( 'post_type'=>tutor()->quiz_post_type,'post_status'=>'any','fields'=>'ids','posts_per_page'=>1,'meta_key'=>QALAM_140_EXAM_TOKEN_META,'meta_value'=>$token ) ); } while ( $exists );
    update_post_meta( $quiz_id, QALAM_140_EXAM_TOKEN_META, $token );
    return $token;
}

function qalam_140_exam_share_url( int $quiz_id, int $instance_id = 0 ): string {
    $token = qalam_140_exam_token( $quiz_id );
    $url = home_url( user_trailingslashit( 'exam/' . rawurlencode( $token ) ) );
    if ( $instance_id ) {
        $url = add_query_arg( array( 'run'=>$instance_id, 'sig'=>qalam_140_instance_sig($quiz_id,$instance_id) ), $url );
    }
    return $url;
}

function qalam_140_instance_sig( int $parent_id, int $instance_id ): string {
    return substr( hash_hmac( 'sha256', $parent_id . '|' . $instance_id, wp_salt( 'auth' ) ), 0, 24 );
}

function qalam_140_register_exam_route(): void {
    add_rewrite_rule( '^exam/([a-zA-Z0-9_-]+)/?$', 'index.php?qalam_exam_token=$matches[1]', 'top' );
}
add_action( 'init', 'qalam_140_register_exam_route', 1 );
add_filter( 'query_vars', static function( $vars ){ $vars[]='qalam_exam_token'; return $vars; } );
add_action( 'admin_init', static function(){
    if ( get_option( QALAM_140_ROUTE_VERSION_OPT ) !== QALAM_140_ROUTE_VERSION ) {
        qalam_140_register_exam_route(); flush_rewrite_rules( false ); update_option( QALAM_140_ROUTE_VERSION_OPT, QALAM_140_ROUTE_VERSION, false );
    }
}, 5 );

function qalam_140_quiz_from_token( string $token ): int {
    $token = sanitize_key( $token );
    if ( ! $token ) { return 0; }
    $ids = get_posts( array( 'post_type'=>tutor()->quiz_post_type,'post_status'=>array('publish','draft','private'),'fields'=>'ids','posts_per_page'=>1,'meta_key'=>QALAM_140_EXAM_TOKEN_META,'meta_value'=>$token,'suppress_filters'=>true ) );
    $id = $ids ? absint( $ids[0] ) : 0;
    return $id && '1' === (string) get_post_meta( $id, QALAM_GENERAL_QUIZ_META, true ) ? $id : 0;
}

function qalam_140_valid_target( int $parent_id, int $target_id ): bool {
    if ( $target_id === $parent_id ) { return true; }
    if ( tutor()->quiz_post_type !== get_post_type( $target_id ) ) { return false; }
    return $parent_id === (int) get_post_meta( $target_id, QALAM_080_DYNAMIC_PARENT_META, true );
}

/** Old share links remain valid, but redirect immediately to the canonical exam URL. */
remove_action( 'template_redirect', 'qalam_081_public_general_quiz_route', -30 );
add_action( 'template_redirect', static function(){
    if ( empty( $_GET['qalam_general_quiz'] ) ) { return; }
    $quiz_id = absint( $_GET['qalam_general_quiz'] );
    if ( ! $quiz_id || '1' !== (string) get_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, true ) ) { return; }
    wp_safe_redirect( qalam_140_exam_share_url( $quiz_id ), 301 ); exit;
}, -120 );

/** Direct visits to the hidden compatibility course or general quiz are never student navigation. */
add_action( 'template_redirect', static function(){
    if ( get_query_var('qalam_exam_token') ) { return; }
    if ( is_singular( tutor()->course_post_type ) ) {
        $id = get_queried_object_id();
        if ( '1' === (string) get_post_meta( $id, QALAM_GENERAL_COURSE_META, true ) ) { wp_safe_redirect( home_url('/') ); exit; }
    }
    if ( is_singular( tutor()->quiz_post_type ) ) {
        $id = get_queried_object_id();
        $parent = (int) get_post_meta( $id, QALAM_080_DYNAMIC_PARENT_META, true );
        if ( '1' === (string) get_post_meta( $id, QALAM_GENERAL_QUIZ_META, true ) ) { wp_safe_redirect( qalam_140_exam_share_url( $id ) ); exit; }
        if ( $parent && '1' === (string) get_post_meta( $parent, QALAM_GENERAL_QUIZ_META, true ) ) { wp_safe_redirect( qalam_140_exam_share_url( $parent, $id ) ); exit; }
    }
}, -110 );

/** Replace the 0.8.1 public-entry handler but retain its validated guest/session storage. */
remove_action( 'admin_post_nopriv_qalam_081_enter_public_quiz', 'qalam_081_enter_public_quiz' );
remove_action( 'admin_post_qalam_081_enter_public_quiz', 'qalam_081_enter_public_quiz' );
function qalam_140_enter_public_quiz(): void {
    $quiz_id = absint( $_POST['quiz_id'] ?? 0 );
    check_admin_referer( 'qalam_081_enter_public_' . $quiz_id, 'qalam_public_enter_nonce' );
    if ( ! $quiz_id || '1' !== (string) get_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, true ) ) { wp_die( 'الاختبار غير موجود.' ); }
    $req = qalam_081_public_requirements( $quiz_id );
    $name = sanitize_text_field( wp_unslash( $_POST['student_name'] ?? '' ) );
    $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $parent_phone = sanitize_text_field( wp_unslash( $_POST['parent_phone'] ?? '' ) );
    $password = (string) wp_unslash( $_POST['quiz_password'] ?? '' );
    $error='';
    if ( $req['name'] && mb_strlen( trim($name), 'UTF-8' ) < 2 ) { $error='اكتب اسم الطالب.'; }
    elseif ( $req['phone'] && ! preg_match('/^[0-9+()\\-\\s]{7,20}$/',$phone) ) { $error='اكتب رقم هاتف صحيح.'; }
    elseif ( $req['parent_phone'] && ! preg_match('/^[0-9+()\\-\\s]{7,20}$/',$parent_phone) ) { $error='اكتب رقم ولي الأمر بشكل صحيح.'; }
    elseif ( $req['password'] ) { $hash=(string)get_post_meta($quiz_id,QALAM_081_PUBLIC_PASSWORD_META,true); if(!$hash||!wp_check_password($password,$hash)){$error='باسورد الاختبار غير صحيح.';} }
    if ( $error ) { qalam_081_render_public_gate( $quiz_id, $error ); }
    try {
        $user_id = qalam_081_guest_user_for_quiz( $quiz_id, array('name'=>$name,'phone'=>$phone,'parent_phone'=>$parent_phone) );
        $target = $quiz_id;
        if ( '1' === (string) get_post_meta( $quiz_id, QALAM_080_DYNAMIC_META, true ) ) { $target = qalam_080_create_dynamic_instance( $quiz_id, $user_id ); }
        qalam_081_enroll_general_quiz_user( $target, $user_id );
        wp_safe_redirect( qalam_140_exam_share_url( $quiz_id, $target ) ); exit;
    } catch ( Throwable $e ) { qalam_081_render_public_gate( $quiz_id, $e->getMessage() ); }
}
add_action( 'admin_post_nopriv_qalam_081_enter_public_quiz', 'qalam_140_enter_public_quiz' );
add_action( 'admin_post_qalam_081_enter_public_quiz', 'qalam_140_enter_public_quiz' );

function qalam_140_process_exam_post( int $parent_id, int $target_id, string $return_url ): void {
    if ( 'POST' !== strtoupper( (string) ($_SERVER['REQUEST_METHOD'] ?? '') ) ) { return; }
    $action = sanitize_text_field( wp_unslash( $_POST['tutor_action'] ?? '' ) );
    if ( ! in_array( $action, array('tutor_start_quiz','tutor_answering_quiz_question','tutor_finish_quiz_attempt'), true ) ) { return; }
    if ( ! is_user_logged_in() ) { wp_die( 'انتهت جلسة الاختبار. افتح رابط الاختبار من جديد.' ); }
    $posted_quiz = absint( $_POST['quiz_id'] ?? 0 );
    if ( $posted_quiz !== $target_id || ! qalam_140_valid_target($parent_id,$target_id) ) { wp_die( 'بيانات الاختبار غير صحيحة.' ); }
    if ( 'tutor_start_quiz' === $action ) {
        tutor_utils()->checking_nonce();
        $course = \Tutor\Models\CourseModel::get_course_by_quiz( $target_id );
        if ( ! $course ) { wp_die( 'تعذر بدء الاختبار.' ); }
        \TUTOR\Quiz::quiz_attempt( (int)$course->ID, $target_id, get_current_user_id() );
        wp_safe_redirect( $return_url ); exit;
    }
    if ( 'tutor_answering_quiz_question' === $action ) {
        \TUTOR\Quiz::tutor_quiz_attempt_submit();
        wp_safe_redirect( $return_url ); exit;
    }
    if ( 'tutor_finish_quiz_attempt' === $action ) {
        $_POST['_wp_http_referer'] = $return_url;
        $handler = new \TUTOR\Quiz( false );
        $handler->finishing_quiz_attempt();
        wp_safe_redirect( $return_url ); exit;
    }
}

function qalam_140_render_exam_intro( int $quiz_id ): void {
    $options = (array) tutor_utils()->get_quiz_option( $quiz_id );
    $count = (int) tutor_utils()->total_questions_for_student_by_quiz( $quiz_id );
    $time = absint( $options['time_limit']['time_value'] ?? 0 );
    $time_type = sanitize_key( (string)($options['time_limit']['time_type'] ?? 'minutes') );
    $passing = absint( $options['passing_grade'] ?? 0 );
    ?>
    <section class="qalam-exam-intro">
      <div class="qalam-exam-intro-copy"><span class="qalam-exam-chip">اختبار قلم</span><h1><?php echo esc_html(get_the_title($quiz_id));?></h1><?php if(get_post_field('post_content',$quiz_id)):?><div class="qalam-exam-summary"><?php echo wp_kses_post(wpautop(get_post_field('post_content',$quiz_id)));?></div><?php endif;?></div>
      <div class="qalam-exam-stats">
        <div><strong><?php echo esc_html($count);?></strong><span>الأسئلة</span></div>
        <div><strong><?php echo esc_html($passing . '%');?></strong><span>درجة النجاح</span></div>
        <?php if($time):?><div><strong><?php echo esc_html($time);?></strong><span><?php echo esc_html('minutes'===$time_type?'دقيقة':'المدة');?></span></div><?php endif;?>
      </div>
      <?php if($count>0):?>
      <form id="tutor-start-quiz" method="post" class="qalam-exam-start-form"><?php wp_nonce_field(tutor()->nonce_action,tutor()->nonce);?><input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id);?>"><input type="hidden" name="tutor_action" value="tutor_start_quiz"><button class="tutor-btn tutor-btn-primary" type="submit">ابدأ الاختبار</button></form>
      <?php else:?><div class="qalam-exam-empty">الاختبار لا يحتوي أسئلة حتى الآن.</div><?php endif;?>
    </section><?php
}

function qalam_140_exam_route(): void {
    $token = sanitize_key( (string) get_query_var('qalam_exam_token') );
    if ( ! $token ) { return; }
    $parent_id = qalam_140_quiz_from_token( $token );
    if ( ! $parent_id ) { status_header(404); wp_die('الاختبار غير موجود.'); }
    $run = absint( $_GET['run'] ?? 0 );
    if ( $run ) {
        $sig = sanitize_text_field( (string)($_GET['sig'] ?? '') );
        if ( ! hash_equals(qalam_140_instance_sig($parent_id,$run),$sig) || ! qalam_140_valid_target($parent_id,$run) ) { $run=0; }
    }
    if ( ! $run ) { qalam_081_render_public_gate( $parent_id ); }
    if ( ! is_user_logged_in() ) { qalam_081_render_public_gate( $parent_id, 'ابدأ الاختبار من الرابط من جديد.' ); }
    $guest_parent = (int) get_user_meta( get_current_user_id(), QALAM_081_GUEST_QUIZ_META, true );
    if ( '1' === (string)get_user_meta(get_current_user_id(),QALAM_081_GUEST_META,true) && $guest_parent !== $parent_id ) { qalam_081_render_public_gate($parent_id,'ابدأ جلسة جديدة للاختبار.'); }
    $return_url = qalam_140_exam_share_url( $parent_id, $run );
    qalam_081_enroll_general_quiz_user( $run, get_current_user_id() );
    qalam_140_process_exam_post( $parent_id, $run, $return_url );
    $quiz = get_post( $run );
    if ( ! $quiz ) { status_header(404); wp_die('الاختبار غير موجود.'); }
    global $post; $old_post=$post; $post=$quiz; setup_postdata($post);
    status_header(200); nocache_headers();
    ?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title><?php echo esc_html($quiz->post_title);?> — Qalam LMS</title><?php wp_head();?></head><body <?php body_class('qalam-f345-ui qalam-standalone-exam');?>><div class="qalam-exam-shell"><header class="qalam-exam-header"><a class="qalam-exam-brand" href="<?php echo esc_url(home_url('/'));?>"><span class="qalam-exam-mark">ق</span><strong>Qalam LMS</strong></a><div class="qalam-exam-title"><?php echo esc_html($quiz->post_title);?></div></header><main class="qalam-exam-main"><?php
    $started = tutor_utils()->is_started_quiz( $run );
    $attempts = tutor_utils()->quiz_attempts();
    if ( ! $started && empty($attempts) ) { qalam_140_render_exam_intro($run); }
    else { echo '<div class="qalam-exam-native">'; tutor_single_quiz_body(); echo '</div>'; }
    ?></main></div><?php wp_footer();?></body></html><?php
    wp_reset_postdata(); $post=$old_post; exit;
}
add_action( 'template_redirect', 'qalam_140_exam_route', -100 );

/* -------------------------------------------------------------------------
 * F5: scope marker + final design layer loaded after all legacy styles.
 * ---------------------------------------------------------------------- */
function qalam_140_is_front_context(): bool {
    if ( get_query_var('qalam_exam_token') ) { return true; }
    if ( function_exists('tutor_utils') && tutor_utils()->is_dashboard_page() ) { return true; }
    if ( is_singular( array( tutor()->course_post_type, tutor()->lesson_post_type, tutor()->quiz_post_type, tutor()->assignment_post_type ) ) ) { return true; }
    return false;
}
function qalam_140_body_class( $classes ) { if ( qalam_140_is_front_context() ) { $classes[]='qalam-f345-ui'; } return array_values(array_unique($classes)); }
add_filter( 'body_class', 'qalam_140_body_class', PHP_INT_MAX );
function qalam_140_admin_body_class( $classes ) {
    $page=sanitize_key((string)($_GET['page']??''));
    if ( 0===strpos($page,'tutor') || 0===strpos($page,'qalam') || in_array($page,array('create-course','google-meet','tutor-zoom'),true) ) { $classes.=' qalam-f345-ui qalam-f345-admin'; }
    return $classes;
}
add_filter( 'admin_body_class', 'qalam_140_admin_body_class', PHP_INT_MAX );

function qalam_140_assets(): void {
    $base=plugin_dir_url(TUTOR_FILE);
    wp_enqueue_style('qalam-140-design',$base.'assets/css/qalam-140-design.css',array(),QALAM_LMS_UI_VERSION);
    wp_enqueue_script('qalam-140-ui',$base.'assets/js/qalam-140-ui.js',array('wp-i18n'),QALAM_LMS_UI_VERSION,true);
    wp_localize_script('qalam-140-ui','Qalam140',array('dictionary'=>qalam_lms_dictionary(),'brand'=>'Qalam LMS'));
}
add_action('wp_enqueue_scripts','qalam_140_assets',PHP_INT_MAX);
add_action('admin_enqueue_scripts',function(){
    $page=sanitize_key((string)($_GET['page']??''));
    if(0===strpos($page,'tutor')||0===strpos($page,'qalam')||in_array($page,array('create-course','google-meet','tutor-zoom'),true)){qalam_140_assets();}
},PHP_INT_MAX);
