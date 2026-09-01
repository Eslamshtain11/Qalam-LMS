<?php
/**
 * Qalam LMS 0.15.0 — public exams, student role, media tools, reports and UI hardening.
 *
 * This layer deliberately reuses Tutor's course/quiz/attempt/enrollment engines. It
 * only fixes routing/presentation and adds Qalam-owned product modules.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_150_VIDEO_AD_POST_TYPE = 'qalam_video_ad';
const QALAM_150_SUBTITLE_URL_META  = '_qalam_subtitle_url';
const QALAM_150_SUBTITLE_LABEL_META = '_qalam_subtitle_label';

/* -------------------------------------------------------------------------
 * 1. Public standalone exams: process the gate on FRONTEND, never admin-post.
 * ---------------------------------------------------------------------- */
function qalam_150_validate_exam_gate( int $quiz_id ): array {
    $req = qalam_081_public_requirements( $quiz_id );
    $name   = sanitize_text_field( wp_unslash( $_POST['student_name'] ?? '' ) );
    $phone  = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $parent = sanitize_text_field( wp_unslash( $_POST['parent_phone'] ?? '' ) );
    $password = (string) wp_unslash( $_POST['quiz_password'] ?? '' );
    $error = '';
    if ( $req['name'] && mb_strlen( trim( $name ), 'UTF-8' ) < 2 ) { $error = 'اكتب اسم الطالب.'; }
    elseif ( $req['phone'] && ! preg_match( '/^[0-9+()\-\s]{7,20}$/', $phone ) ) { $error = 'اكتب رقم هاتف صحيح.'; }
    elseif ( $req['parent_phone'] && ! preg_match( '/^[0-9+()\-\s]{7,20}$/', $parent ) ) { $error = 'اكتب رقم ولي الأمر بشكل صحيح.'; }
    elseif ( $req['password'] ) {
        $hash = (string) get_post_meta( $quiz_id, QALAM_081_PUBLIC_PASSWORD_META, true );
        if ( ! $hash || ! wp_check_password( $password, $hash ) ) { $error = 'باسورد الاختبار غير صحيح.'; }
    }
    return array( 'name'=>$name, 'phone'=>$phone, 'parent_phone'=>$parent, 'error'=>$error );
}

function qalam_150_front_exam_entry(): void {
    if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || empty( $_POST['qalam_exam_enter'] ) ) { return; }
    $token = sanitize_key( (string) get_query_var( 'qalam_exam_token' ) );
    if ( ! $token ) { return; }
    $quiz_id = qalam_140_quiz_from_token( $token );
    if ( ! $quiz_id || '1' !== (string) get_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, true ) ) {
        status_header( 404 ); wp_die( 'الاختبار غير موجود.' );
    }
    $nonce = sanitize_text_field( wp_unslash( $_POST['qalam_public_enter_nonce'] ?? '' ) );
    if ( ! wp_verify_nonce( $nonce, 'qalam_081_enter_public_' . $quiz_id ) ) {
        qalam_081_render_public_gate( $quiz_id, 'الجلسة انتهت. حدّث الصفحة وحاول تاني.' );
    }
    $data = qalam_150_validate_exam_gate( $quiz_id );
    if ( $data['error'] ) { qalam_081_render_public_gate( $quiz_id, $data['error'] ); }
    try {
        $user_id = qalam_081_guest_user_for_quiz( $quiz_id, $data );
        $target = $quiz_id;
        if ( '1' === (string) get_post_meta( $quiz_id, QALAM_080_DYNAMIC_META, true ) ) {
            $target = qalam_080_create_dynamic_instance( $quiz_id, $user_id );
        }
        qalam_081_enroll_general_quiz_user( $target, $user_id );
        wp_safe_redirect( qalam_140_exam_share_url( $quiz_id, $target ) );
        exit;
    } catch ( \Throwable $e ) {
        qalam_081_render_public_gate( $quiz_id, $e->getMessage() );
    }
}
add_action( 'template_redirect', 'qalam_150_front_exam_entry', -180 );

/* Keep old admin-post endpoint only as compatibility redirect. New forms never hit wp-admin. */
function qalam_150_legacy_exam_admin_post(): void {
    $quiz_id = absint( $_POST['quiz_id'] ?? 0 );
    if ( $quiz_id && '1' === (string) get_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, true ) ) {
        wp_safe_redirect( qalam_140_exam_share_url( $quiz_id ) ); exit;
    }
}
remove_action( 'admin_post_nopriv_qalam_081_enter_public_quiz', 'qalam_140_enter_public_quiz' );
remove_action( 'admin_post_qalam_081_enter_public_quiz', 'qalam_140_enter_public_quiz' );
add_action( 'admin_post_nopriv_qalam_081_enter_public_quiz', 'qalam_150_legacy_exam_admin_post' );
add_action( 'admin_post_qalam_081_enter_public_quiz', 'qalam_150_legacy_exam_admin_post' );

/* -------------------------------------------------------------------------
 * 2. A real visible Qalam Student role while preserving Tutor compatibility.
 * ---------------------------------------------------------------------- */
function qalam_150_register_student_role(): void {
    $subscriber = get_role( 'subscriber' );
    $caps = $subscriber ? (array) $subscriber->capabilities : array( 'read' => true );
    if ( ! get_role( 'qalam_student' ) ) { add_role( 'qalam_student', 'طالب', $caps ); }
}
add_action( 'init', 'qalam_150_register_student_role', 2 );

function qalam_150_sync_student_role( int $user_id, string $role, array $old_roles ): void {
    if ( 'qalam_student' !== $role ) { return; }
    update_user_meta( $user_id, '_is_tutor_student', 1 );
    update_user_meta( $user_id, 'tutor_profile_view_mode', 'student' );
    $user = new WP_User( $user_id );
    // Tutor 4.0.4 still hardcodes subscriber in a few access checks. Secondary role keeps parity.
    if ( ! in_array( 'subscriber', (array) $user->roles, true ) ) { $user->add_role( 'subscriber' ); }
}
add_action( 'set_user_role', 'qalam_150_sync_student_role', 30, 3 );

/* -------------------------------------------------------------------------
 * 3. Report terminology + currency normalization.
 * ---------------------------------------------------------------------- */
function qalam_150_dictionary( array $map ): array {
    $extra = array(
        'LMS Reports'=>'تقارير قلم','Overview'=>'نظرة عامة','Published Courses'=>'الدورات المنشورة','Course Enrolled'=>'التسجيل في الدورات','Courses Enrolled'=>'التسجيلات في الدورات',
        'Earning graph'=>'رسم الأرباح','Earnings Chart for %s'=>'رسم الأرباح لـ %s','Total Earning'=>'إجمالي الأرباح','Total Earnings'=>'إجمالي الأرباح','Total Refund'=>'إجمالي المرتجعات','Total Discount'=>'إجمالي الخصومات',
        'Last 30 Days'=>'آخر 30 يوم','Sales'=>'المبيعات','Ratings'=>'التقييمات','Membership Revenue'=>'إيرادات العضويات','Subscription Revenue'=>'إيرادات الاشتراكات',
        'Active Memberships'=>'العضويات النشطة','Active Subscriptions'=>'الاشتراكات النشطة','Expired Memberships'=>'العضويات المنتهية','Expired Subscriptions'=>'الاشتراكات المنتهية',
        'Memberships'=>'العضويات','Subscriptions'=>'الاشتراكات','Revenue'=>'الإيرادات','Earnings'=>'الأرباح','Total Students'=>'إجمالي الطلاب','Total Courses'=>'إجمالي الدورات',
        'Passing Marks'=>'درجة النجاح','Passing Mark'=>'درجة النجاح','Incorrect Answer'=>'إجابة غلط','Correct Answer'=>'إجابة صحيحة','Earned Grade'=>'الدرجة المحققة','Fail'=>'راسب','Passed'=>'ناجح',
        'No Notes Have Been Added!'=>'مفيش ملاحظات مضافة لحد دلوقتي','Type your note here to save for later'=>'اكتب ملاحظتك هنا عشان ترجع لها بعدين','Biography'=>'نبذة عن الطالب','Bio data is empty'=>'مفيش نبذة مضافة',
        'Course Name'=>'اسم الدورة','Progress'=>'التقدم','Actions'=>'الإجراءات','Course Completed'=>'دورة مكتملة','Courses Completed'=>'دورات مكتملة',
        'Native'=>'المدفوعات الأصلية','Cart'=>'السلة','All Time'=>'كل الوقت','Total Courses'=>'إجمالي الدورات','Avg. Rating'=>'متوسط التقييم','Total Students'=>'إجمالي الطلاب',
    );
    $extra = array_merge( $extra, array(
        '%1$s%2$s of %3$s' => '%1$s%2$s من %3$s',
        'Access' => 'الوصول',
        'Action' => 'الإجراء',
        'Actions' => 'الإجراءات',
        'Active Memberships' => 'العضويات النشطة',
        'Active Subscriptions' => 'الاشتراكات النشطة',
        'Active Users' => 'المستخدمون النشطون',
        'Admin Gets' => 'نصيب الإدارة',
        'Admin Share' => 'حصة الإدارة',
        'Amount' => 'المبلغ',
        'Amount:' => 'المبلغ:',
        'Analytics' => 'التحليلات',
        'Are you sure?' => 'متأكد؟',
        'As per %1$d%2$s' => 'حسب %1$d%2$s',
        'Assignment' => 'الواجب',
        'Assignment Submit' => 'تسليم الواجب',
        'Assignments' => 'الواجبات',
        'Auto-Renewal' => 'التجديد التلقائي',
        'Back' => 'رجوع',
        'Balance' => 'الرصيد',
        'Billing Address' => 'عنوان الفاتورة',
        'Breakdown' => 'التفاصيل',
        'Bundle-based' => 'حسب الحزمة',
        'Bundle Subscriptions' => 'اشتراكات الحزم',
        'Only Memberships' => 'العضويات فقط',
        'Single Course Subscription' => 'اشتراك دورة واحدة',
        'Cancel' => 'إلغاء',
        'Category' => 'التصنيف',
        'Category-based' => 'حسب التصنيف',
        'Certificate' => 'الشهادة',
        'Certificate Issued' => 'تم إصدار الشهادة',
        'Change plan' => 'تغيير الخطة',
        'Check your course performance through Tutor Report stats.' => 'تابع أداء دوراتك من خلال إحصائيات تقارير قلم.',
        'Clear' => 'مسح',
        'Clear All' => 'مسح الكل',
        'Close' => 'إغلاق',
        'Commission' => 'العمولة',
        'Commissions' => 'العمولات',
        'Completed' => 'مكتمل',
        'Completed Courses' => 'الدورات المكتملة',
        'Confirm' => 'تأكيد',
        'Contact information' => 'بيانات التواصل',
        'Content Not Found!' => 'المحتوى مش موجود!',
        'Course' => 'الدورة',
        'Course Access' => 'الوصول للدورة',
        'Course Enrolled' => 'التسجيل في الدورات',
        'Course Enrolled Chart %s' => 'رسم التسجيل في الدورات %s',
        'Course Name' => 'اسم الدورة',
        'Course Taken' => 'الدورات اللي دخلها الطالب',
        'Course Taken:' => 'الدورات اللي دخلها الطالب:',
        'Course-based' => 'حسب الدورة',
        'Courses' => 'الدورات',
        'Courses Completed' => 'الدورات المكتملة',
        'Courses in Progress' => 'الدورات الجاري دراستها',
        'Created' => 'تم الإنشاء',
        'Current Balance' => 'الرصيد الحالي',
        'Date' => 'التاريخ',
        'Deducted Commissions' => 'العمولات المخصومة',
        'Deducted Fees' => 'الرسوم المخصومة',
        'Delete' => 'حذف',
        'Detailed Report of Your Sales & Students' => 'تقرير تفصيلي عن المبيعات والطلاب',
        'Details' => 'التفاصيل',
        'Disabled' => 'متوقف',
        'Discount' => 'الخصم',
        'Discount Chart %s' => 'رسم الخصومات %s',
        'Download CSV' => 'تحميل CSV',
        'Draft' => 'مسودة',
        'Earning' => 'الربح',
        'Earning graph' => 'رسم الأرباح',
        'Earnings' => 'الأرباح',
        'Earnings Chart %s' => 'رسم الأرباح %s',
        'Earnings Graph' => 'رسم الأرباح',
        'Earnings:' => 'الأرباح:',
        'Edit' => 'تعديل',
        'Edit with Builder' => 'تعديل بالمنشئ',
        'Email' => 'البريد الإلكتروني',
        'Email:' => 'البريد الإلكتروني:',
        'Enabled' => 'مفعّل',
        'Enroll Date' => 'تاريخ التسجيل',
        'Enrolled' => 'مسجل',
        'Enrolled Courses' => 'الدورات المسجل فيها',
        'Expired Memberships' => 'العضويات المنتهية',
        'Expired Subscriptions' => 'الاشتراكات المنتهية',
        'Export' => 'تصدير',
        'Export to keep a copy of your analytics data.' => 'صدّر البيانات للاحتفاظ بنسخة من التحليلات.',
        'Feedback' => 'التقييم',
        'Fees' => 'الرسوم',
        'ID' => 'الرقم',
        'In Progress Courses' => 'الدورات قيد الدراسة',
        'Instructor' => 'المعلم',
        'Instructors' => 'المعلمون',
        'Invalid course' => 'دورة غير صالحة',
        'Invalid student' => 'طالب غير صالح',
        'Invalid student id' => 'رقم الطالب غير صالح',
        'LMS Reports' => 'تقارير قلم',
        'Last Update' => 'آخر تحديث',
        'Last enrolled courses' => 'أحدث الدورات المسجل فيها',
        'Lesson' => 'الدرس',
        'Lessons' => 'الدروس',
        'Maintenance Fees: ' => 'رسوم الصيانة: ',
        'Membership Access:' => 'صلاحية العضوية:',
        'Membership Name' => 'اسم العضوية',
        'Membership Plan Insights' => 'تحليلات خطط العضوية',
        'Membership Revenue' => 'إيرادات العضويات',
        'Monthly' => 'شهري',
        'Most Popular Courses' => 'أكثر الدورات انتشارًا',
        'Most popular courses' => 'أكثر الدورات انتشارًا',
        'My Earnings' => 'أرباحي',
        'N/A' => 'غير متاح',
        'Name' => 'الاسم',
        'Net Amount: ' => 'صافي المبلغ: ',
        'Net Earnings' => 'صافي الأرباح',
        'New Registered Teachers' => 'المعلمون الجدد',
        'New Registered students' => 'الطلاب الجدد',
        'Next Payment Date' => 'موعد الدفعة الجاية',
        'Next Payment Date:' => 'موعد الدفعة الجاية:',
        'No Courses Found!' => 'مفيش دورات!',
        'No Statements Found!' => 'مفيش كشوف حساب!',
        'No Students Found!' => 'مفيش طلاب!',
        'Number of Sales' => 'عدد المبيعات',
        'Order Amount: ' => 'قيمة الطلب: ',
        'Order ID' => 'رقم الطلب',
        'Overview' => 'نظرة عامة',
        'Payment History' => 'سجل المدفوعات',
        'Payment Method' => 'طريقة الدفع',
        'Payment Status' => 'حالة الدفع',
        'Payment:' => 'الدفع:',
        'Pending' => 'قيد الانتظار',
        'Plan' => 'الخطة',
        'Please confirm your action to change the Subscription Status.' => 'أكد إنك عايز تغيّر حالة الاشتراك.',
        'Preview' => 'معاينة',
        'Price' => 'السعر',
        'Price Breakdown' => 'تفاصيل السعر',
        'Progress' => 'التقدم',
        'Progress Courses' => 'الدورات الجاري دراستها',
        'Publish' => 'نشر',
        'Published' => 'منشور',
        'Published Courses' => 'الدورات المنشورة',
        'Published Date' => 'تاريخ النشر',
        'Purchaser:' => 'المشتري:',
        'Questions' => 'الأسئلة',
        'Quiz' => 'الاختبار',
        'Quizzes' => 'الاختبارات',
        'Quizzes Taken' => 'الاختبارات اللي اتعملت',
        'Rating' => 'التقييم',
        'Rating:' => 'التقييم:',
        'Recent Reviews' => 'أحدث التقييمات',
        'Refund' => 'استرداد',
        'Refund Chart %s' => 'رسم المرتجعات %s',
        'Register at' => 'تاريخ التسجيل',
        'Registered at:' => 'تاريخ التسجيل:',
        'Registration Date' => 'تاريخ التسجيل',
        'Registration Date:' => 'تاريخ التسجيل:',
        'Renew:' => 'التجديد:',
        'Reports' => 'التقارير',
        'Reviews' => 'التقييمات',
        'Reviews Placed' => 'التقييمات المضافة',
        'Sale' => 'بيعة',
        'Sales' => 'المبيعات',
        'Search' => 'بحث',
        'Search courses...' => 'ابحث في الدورات...',
        'Site-wide' => 'على مستوى المنصة',
        'Start Date:' => 'تاريخ البداية:',
        'Statements' => 'كشوف الحساب',
        'Status' => 'الحالة',
        'Status:' => 'الحالة:',
        'Student' => 'الطالب',
        'Student Details' => 'تفاصيل الطالب',
        'Student Info' => 'بيانات الطالب',
        'Students' => 'الطلاب',
        'Subscription' => 'الاشتراك',
        'Subscription Details' => 'تفاصيل الاشتراك',
        'Subscription ID:' => 'رقم الاشتراك:',
        'Subscription Revenue' => 'إيرادات الاشتراكات',
        'Subscription Type' => 'نوع الاشتراك',
        'Subscriptions' => 'الاشتراكات',
        'Tax Amount (%s): ' => 'قيمة الضريبة (%s): ',
        'Teacher' => 'المعلم',
        'The trial period for this subscription ends on %1$s. The first payment will be charged on %2$s.' => 'الفترة التجريبية للاشتراك بتنتهي يوم %1$s، وأول دفعة هتتحسب يوم %2$s.',
        'Timezone:' => 'المنطقة الزمنية:',
        'Today' => 'اليوم',
        'Top Course & Bundle Subscription Insights' => 'أهم تحليلات اشتراكات الدورات والحزم',
        'Total Course' => 'إجمالي الدورات',
        'Total Discount' => 'إجمالي الخصومات',
        'Total Earning' => 'إجمالي الأرباح',
        'Total Enrolled' => 'إجمالي التسجيلات',
        'Total Enrolled:' => 'إجمالي التسجيلات:',
        'Total Learners' => 'إجمالي الطلاب',
        'Total Learners:' => 'إجمالي الطلاب:',
        'Total Lessons' => 'إجمالي الدروس',
        'Total Questions' => 'إجمالي الأسئلة',
        'Total Refund' => 'إجمالي المرتجعات',
        'Total Revenue' => 'إجمالي الإيرادات',
        'Total Reviews' => 'إجمالي التقييمات',
        'Total Sale' => 'إجمالي المبيعات',
        'Total Student' => 'إجمالي الطلاب',
        'Total Withdraws' => 'إجمالي السحوبات',
        'Transaction Details' => 'تفاصيل العملية',
        'Trial' => 'تجريبي',
        'Trial End Date:' => 'نهاية الفترة التجريبية:',
        'Type' => 'النوع',
        'Unpublished' => 'غير منشور',
        'Update review status' => 'تحديث حالة التقييم',
        'User Name:' => 'اسم المستخدم:',
        'View' => 'عرض',
        'View Course' => 'عرض الدورة',
        'View Profile' => 'عرض الملف',
        'View Progress' => 'عرض التقدم',
        'Withdraws' => 'السحوبات',
        'Yearly' => 'سنوي',
        'Yes, I’m sure' => 'أيوه، متأكد',
        'for %s' => 'لـ %s',
    ) );
    return array_merge( $map, $extra );
}
add_filter( 'qalam_lms_dictionary', 'qalam_150_dictionary', PHP_INT_MAX );

function qalam_150_currency_symbol( $symbol ) {
    $monetize = (string) tutor_utils()->get_option( 'monetize_by', '' );
    if ( 'wc' === $monetize && function_exists( 'get_woocommerce_currency_symbol' ) ) { return get_woocommerce_currency_symbol(); }
    if ( tutor_utils()->get_option( 'enable_tutor_edd' ) && function_exists( 'edd_currency_symbol' ) ) { return edd_currency_symbol(); }
    if ( 'pmpro' === $monetize && function_exists( 'pmpro_get_currency' ) ) { $c=pmpro_get_currency(); if(!empty($c['symbol'])){return $c['symbol'];} }
    try {
        if ( class_exists( '\\Tutor\\Ecommerce\\Settings' ) && class_exists( '\\Tutor\\Ecommerce\\OptionKeys' ) ) {
            $code = tutor_utils()->get_option( \Tutor\Ecommerce\OptionKeys::CURRENCY_CODE, 'USD' );
            $native = \Tutor\Ecommerce\Settings::get_currency_symbol_by_code( $code );
            if ( $native ) { return $native; }
        }
    } catch ( \Throwable $e ) { /* keep engine fallback */ }
    return $symbol;
}
add_filter( 'get_tutor_currency_symbol', 'qalam_150_currency_symbol', PHP_INT_MAX );

/* -------------------------------------------------------------------------
 * 4. Video ads and Qalam subtitles.
 * ---------------------------------------------------------------------- */
function qalam_150_register_media_types(): void {
    register_post_type( QALAM_150_VIDEO_AD_POST_TYPE, array(
        'labels'=>array('name'=>'إعلانات الفيديو','singular_name'=>'إعلان فيديو'), 'public'=>false, 'show_ui'=>false,
        'supports'=>array('title','author'), 'capability_type'=>'post', 'map_meta_cap'=>true,
    ) );
}
add_action( 'init', 'qalam_150_register_media_types', 5 );

add_filter( 'upload_mimes', static function( $mimes ) {
    $mimes['vtt'] = 'text/vtt'; $mimes['srt'] = 'application/x-subrip'; return $mimes;
} );

function qalam_150_parse_cues( string $raw ): array {
    $out = array();
    foreach ( preg_split( '/[,\n]+/', $raw ) as $piece ) {
        $piece = trim( $piece ); if ( '' === $piece ) { continue; }
        if ( false !== strpos( $piece, ':' ) ) {
            $parts = array_map( 'intval', explode( ':', $piece ) );
            if ( 2 === count($parts) ) { $sec = $parts[0]*60+$parts[1]; }
            elseif ( 3 === count($parts) ) { $sec = $parts[0]*3600+$parts[1]*60+$parts[2]; }
            else { continue; }
        } else { $sec = absint( $piece ); }
        if ( $sec > 0 ) { $out[] = $sec; }
    }
    $out = array_values( array_unique( $out ) ); sort( $out ); return $out;
}

function qalam_150_video_ads_for_lesson( int $lesson_id, int $course_id ): array {
    if ( function_exists( 'qalam_feature_enabled' ) && ! qalam_feature_enabled( 'video_ads' ) ) { return array(); }
    $ids = get_posts( array('post_type'=>QALAM_150_VIDEO_AD_POST_TYPE,'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','orderby'=>'menu_order date','order'=>'ASC') );
    $result = array();
    foreach ( $ids as $id ) {
        if ( '1' !== (string) get_post_meta($id,'_qalam_ad_active',true) ) { continue; }
        $courses = array_map('absint',(array)get_post_meta($id,'_qalam_ad_courses',true));
        $lessons = array_map('absint',(array)get_post_meta($id,'_qalam_ad_lessons',true));
        if ( $courses && ! in_array($course_id,$courses,true) ) { continue; }
        if ( $lessons && ! in_array($lesson_id,$lessons,true) ) { continue; }
        $url = esc_url_raw( (string)get_post_meta($id,'_qalam_ad_media_url',true) );
        if ( ! $url ) { continue; }
        $attachment_id = absint( get_post_meta($id,'_qalam_ad_media_id',true) );
        $mime = sanitize_mime_type( (string)get_post_meta($id,'_qalam_ad_media_mime',true) );
        if ( ! $mime && $attachment_id ) { $mime = (string) get_post_mime_type( $attachment_id ); }
        $stored_type = sanitize_key((string)get_post_meta($id,'_qalam_ad_media_type',true));
        if ( 0 === strpos($mime,'image/') ) { $media_type = 'image'; }
        elseif ( 0 === strpos($mime,'video/') ) { $media_type = 'video'; }
        else {
            $path = strtolower((string)wp_parse_url($url,PHP_URL_PATH));
            $media_type = preg_match('/\.(jpe?g|png|gif|webp|avif)$/',$path) ? 'image' : ($stored_type ?: 'video');
        }
        $result[] = array(
            'id'=>(int)$id, 'title'=>get_the_title($id), 'type'=>$media_type, 'mime'=>$mime, 'attachment_id'=>$attachment_id,
            'url'=>$url, 'skip_after'=>max(0,absint(get_post_meta($id,'_qalam_ad_skip_after',true))),
            'image_duration'=>max(3,absint(get_post_meta($id,'_qalam_ad_image_duration',true)) ?: 10),
            'cues'=>qalam_150_parse_cues((string)get_post_meta($id,'_qalam_ad_cues',true)),
            'auto_count'=>max(0,min(12,absint(get_post_meta($id,'_qalam_ad_auto_count',true)))),
        );
    }
    return $result;
}

function qalam_150_video_context(): array {
    $lesson_id = get_the_ID();
    $course_id = $lesson_id ? (int) tutor_utils()->get_course_id_by_content( $lesson_id ) : 0;
    $subtitles_enabled = ! function_exists( 'qalam_feature_enabled' ) || qalam_feature_enabled( 'video_subtitles' );
    return array(
        'lesson_id'=>$lesson_id, 'course_id'=>$course_id,
        'ads'=>qalam_150_video_ads_for_lesson($lesson_id,$course_id),
        'subtitle_url'=>$subtitles_enabled ? esc_url_raw((string)get_post_meta($lesson_id,QALAM_150_SUBTITLE_URL_META,true)) : '',
        'subtitle_label'=>sanitize_text_field((string)get_post_meta($lesson_id,QALAM_150_SUBTITLE_LABEL_META,true)) ?: 'العربية',
    );
}

function qalam_150_media_admin_menu(): void {
    add_submenu_page( 'tutor', 'إعلانات الفيديو', 'إعلانات الفيديو', 'manage_tutor_instructor', 'qalam-video-ads', 'qalam_150_render_video_ads_admin' );
}
add_action( 'admin_menu', 'qalam_150_media_admin_menu', 99 );

function qalam_150_save_video_ad(): void {
    if ( ! current_user_can('manage_tutor_instructor') ) { wp_die('غير مسموح.'); }
    check_admin_referer('qalam_150_save_video_ad');
    $id = absint($_POST['ad_id']??0);
    $postarr = array('post_type'=>QALAM_150_VIDEO_AD_POST_TYPE,'post_status'=>'publish','post_title'=>sanitize_text_field(wp_unslash($_POST['title']??'إعلان فيديو')));
    if($id){$postarr['ID']=$id; $id=wp_update_post($postarr,true);} else {$id=wp_insert_post($postarr,true);}
    if(is_wp_error($id)){wp_die($id->get_error_message());}
    update_post_meta($id,'_qalam_ad_active',!empty($_POST['active'])?'1':'0');
    $media_id = absint($_POST['media_id']??0);
    $media_mime = sanitize_mime_type(wp_unslash($_POST['media_mime']??''));
    if($media_id && !$media_mime){$media_mime=(string)get_post_mime_type($media_id);}
    $requested_type=in_array($_POST['media_type']??'',array('video','image'),true)?sanitize_key($_POST['media_type']):'video';
    if(0===strpos($media_mime,'image/')){$requested_type='image';} elseif(0===strpos($media_mime,'video/')){$requested_type='video';}
    update_post_meta($id,'_qalam_ad_media_type',$requested_type);
    update_post_meta($id,'_qalam_ad_media_url',esc_url_raw(wp_unslash($_POST['media_url']??'')));
    update_post_meta($id,'_qalam_ad_media_id',$media_id);
    update_post_meta($id,'_qalam_ad_media_mime',$media_mime);
    update_post_meta($id,'_qalam_ad_skip_after',absint($_POST['skip_after']??5));
    update_post_meta($id,'_qalam_ad_image_duration',absint($_POST['image_duration']??10));
    update_post_meta($id,'_qalam_ad_cues',sanitize_textarea_field(wp_unslash($_POST['cues']??'')));
    update_post_meta($id,'_qalam_ad_auto_count',min(12,absint($_POST['auto_count']??0)));
    update_post_meta($id,'_qalam_ad_courses',array_values(array_filter(array_map('absint',(array)($_POST['courses']??array())))));
    update_post_meta($id,'_qalam_ad_lessons',array_values(array_filter(array_map('absint',(array)($_POST['lessons']??array())))));
    wp_safe_redirect(admin_url('admin.php?page=qalam-video-ads&saved=1')); exit;
}
add_action('admin_post_qalam_150_save_video_ad','qalam_150_save_video_ad');

function qalam_150_delete_video_ad(): void {
    if(!current_user_can('manage_tutor_instructor')){wp_die('غير مسموح.');}
    $id=absint($_GET['ad_id']??0); check_admin_referer('qalam_150_delete_ad_'.$id);
    if($id && QALAM_150_VIDEO_AD_POST_TYPE===get_post_type($id)){wp_delete_post($id,true);} wp_safe_redirect(admin_url('admin.php?page=qalam-video-ads')); exit;
}
add_action('admin_post_qalam_150_delete_video_ad','qalam_150_delete_video_ad');

function qalam_150_save_subtitle(): void {
    if(!current_user_can('manage_tutor_instructor')){wp_die('غير مسموح.');}
    check_admin_referer('qalam_150_save_subtitle'); $lesson=absint($_POST['lesson_id']??0);
    if(!$lesson || tutor()->lesson_post_type!==get_post_type($lesson)){wp_die('درس غير صالح.');}
    update_post_meta($lesson,QALAM_150_SUBTITLE_URL_META,esc_url_raw(wp_unslash($_POST['subtitle_url']??'')));
    update_post_meta($lesson,QALAM_150_SUBTITLE_LABEL_META,sanitize_text_field(wp_unslash($_POST['subtitle_label']??'العربية')));
    wp_safe_redirect(admin_url('admin.php?page=qalam-video-ads&subtitle_saved=1')); exit;
}
add_action('admin_post_qalam_150_save_subtitle','qalam_150_save_subtitle');

function qalam_150_render_video_ads_admin(): void {
    if(!current_user_can('manage_tutor_instructor')){return;}
    $ads_enabled = ! function_exists('qalam_feature_enabled') || qalam_feature_enabled('video_ads');
    $subtitles_enabled = ! function_exists('qalam_feature_enabled') || qalam_feature_enabled('video_subtitles');
    if ( ! $ads_enabled && ! $subtitles_enabled ) { return; }
    $ads=$ads_enabled?get_posts(array('post_type'=>QALAM_150_VIDEO_AD_POST_TYPE,'post_status'=>'publish','posts_per_page'=>-1)):array();
    $courses=get_posts(array('post_type'=>tutor()->course_post_type,'post_status'=>array('publish','draft'),'posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC'));
    $lessons=get_posts(array('post_type'=>tutor()->lesson_post_type,'post_status'=>array('publish','draft'),'posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC'));
    $edit_id=absint($_GET['edit_ad']??0); $edit=$edit_id?get_post($edit_id):null;
    ?>
    <div class="wrap qalam-150-admin" dir="rtl"><h1>وسائط الفيديو</h1><p>إدارة إعلانات المشغل وترجمات الدروس من مكان واحد، حسب الملحقات المفعّلة في قلم.</p>
    <?php if($ads_enabled): ?><div class="qalam-150-grid"><section class="qalam-150-card"><h2><?php echo $edit?'تعديل الإعلان':'إضافة إعلان فيديو';?></h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><?php wp_nonce_field('qalam_150_save_video_ad');?><input type="hidden" name="action" value="qalam_150_save_video_ad"><input type="hidden" name="ad_id" value="<?php echo esc_attr($edit_id);?>">
    <label>اسم الإعلان<input name="title" required value="<?php echo esc_attr($edit?$edit->post_title:'');?>"></label>
    <label class="qalam-inline"><input type="checkbox" name="active" value="1" <?php checked(!$edit || '1'===get_post_meta($edit_id,'_qalam_ad_active',true));?>> مفعّل</label>
    <label>نوع الإعلان<select name="media_type"><option value="video" <?php selected($edit&&'video'===get_post_meta($edit_id,'_qalam_ad_media_type',true));?>>فيديو</option><option value="image" <?php selected($edit&&'image'===get_post_meta($edit_id,'_qalam_ad_media_type',true));?>>صورة</option></select></label>
    <label>ملف الإعلان<div class="qalam-media-row"><input id="qalam-ad-media-url" name="media_url" required value="<?php echo esc_attr($edit?get_post_meta($edit_id,'_qalam_ad_media_url',true):'');?>"><input type="hidden" id="qalam-ad-media-id" name="media_id" value="<?php echo esc_attr($edit?absint(get_post_meta($edit_id,'_qalam_ad_media_id',true)):0);?>"><input type="hidden" id="qalam-ad-media-mime" name="media_mime" value="<?php echo esc_attr($edit?get_post_meta($edit_id,'_qalam_ad_media_mime',true):'');?>"><button type="button" class="button" data-qalam-media-target="qalam-ad-media-url" data-qalam-media-id-target="qalam-ad-media-id" data-qalam-media-mime-target="qalam-ad-media-mime">اختيار من الوسائط</button></div></label>
    <div class="qalam-cols"><label>السماح بالتخطي بعد كام ثانية؟<input type="number" min="0" max="120" name="skip_after" value="<?php echo esc_attr($edit?absint(get_post_meta($edit_id,'_qalam_ad_skip_after',true)):5);?>"></label><label>مدة إعلان الصورة<input type="number" min="3" max="120" name="image_duration" value="<?php echo esc_attr($edit?absint(get_post_meta($edit_id,'_qalam_ad_image_duration',true)):10);?>"></label></div>
    <label>توقيتات الظهور اليدوية<textarea name="cues" rows="3" placeholder="05:00, 17:30, 35:00"><?php echo esc_textarea($edit?get_post_meta($edit_id,'_qalam_ad_cues',true):'');?></textarea><small>اكتب دقيقة:ثانية أو ساعة:دقيقة:ثانية، وافصل بينهم بفاصلة.</small></label>
    <label>أو وزّع الإعلان تلقائيًا كام مرة؟<input type="number" min="0" max="12" name="auto_count" value="<?php echo esc_attr($edit?absint(get_post_meta($edit_id,'_qalam_ad_auto_count',true)):0);?>"><small>0 = استخدم التوقيتات اليدوية فقط.</small></label>
    <label>الدورات المستهدفة<select name="courses[]" multiple size="6"><option value="">كل الدورات</option><?php $sel=(array)($edit?get_post_meta($edit_id,'_qalam_ad_courses',true):array()); foreach($courses as $c):?><option value="<?php echo esc_attr($c->ID);?>" <?php selected(in_array($c->ID,array_map('absint',$sel),true));?>><?php echo esc_html($c->post_title);?></option><?php endforeach;?></select></label>
    <label>الدروس المستهدفة<select name="lessons[]" multiple size="8"><option value="">كل دروس الدورات المختارة</option><?php $sel=(array)($edit?get_post_meta($edit_id,'_qalam_ad_lessons',true):array()); foreach($lessons as $l):?><option value="<?php echo esc_attr($l->ID);?>" <?php selected(in_array($l->ID,array_map('absint',$sel),true));?>><?php echo esc_html($l->post_title);?></option><?php endforeach;?></select></label>
    <button class="button button-primary" type="submit">حفظ الإعلان</button></form></section>
    <section class="qalam-150-card"><h2>الإعلانات الحالية</h2><?php if(!$ads):?><p>مفيش إعلانات فيديو لسه.</p><?php else:?><div class="qalam-ad-list"><?php foreach($ads as $ad):?><article><div><strong><?php echo esc_html($ad->post_title);?></strong><small><?php echo '1'===get_post_meta($ad->ID,'_qalam_ad_active',true)?'مفعّل':'متوقف';?></small><small>ظهور: <?php echo esc_html((int)get_post_meta($ad->ID,'_qalam_ad_stat_impression',true));?> · تخطي: <?php echo esc_html((int)get_post_meta($ad->ID,'_qalam_ad_stat_skipped',true));?> · مكتمل: <?php echo esc_html((int)get_post_meta($ad->ID,'_qalam_ad_stat_completed',true));?></small></div><div><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=qalam-video-ads&edit_ad='.$ad->ID));?>">تعديل</a><a class="button button-link-delete" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=qalam_150_delete_video_ad&ad_id='.$ad->ID),'qalam_150_delete_ad_'.$ad->ID));?>">حذف</a></div></article><?php endforeach;?></div><?php endif;?></section></div><?php endif; ?>
    <?php if($subtitles_enabled): ?><section class="qalam-150-card qalam-subtitles"><h2>ترجمة الدروس</h2><p>ارفع ملف VTT أو SRT وقلم تعرضه كترجمة داخل المشغل، من غير الاعتماد على أزرار YouTube.</p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><?php wp_nonce_field('qalam_150_save_subtitle');?><input type="hidden" name="action" value="qalam_150_save_subtitle"><label>الدرس<select name="lesson_id" required><option value="">اختر درس</option><?php foreach($lessons as $l):?><option value="<?php echo esc_attr($l->ID);?>"><?php echo esc_html($l->post_title);?></option><?php endforeach;?></select></label><label>اسم الترجمة<input name="subtitle_label" value="العربية"></label><label>ملف VTT / SRT<div class="qalam-media-row"><input id="qalam-subtitle-url" name="subtitle_url" required><button type="button" class="button" data-qalam-media-target="qalam-subtitle-url">اختيار/رفع الملف</button></div></label><button class="button button-primary">حفظ الترجمة</button></form></section><?php endif; ?></div>
    <?php
}

/* Inject the Video Ads entry from the normal announcement screen without modifying its data model. */
add_action('admin_enqueue_scripts', static function($hook){
    $page=sanitize_key((string)($_GET['page']??''));
    if(in_array($page,array('qalam-video-ads','tutor_announcements','tutor_settings','tutor'),true)){
        wp_enqueue_media();
        wp_enqueue_style('qalam-150-ui',plugin_dir_url(TUTOR_FILE).'assets/css/qalam-150.css',array(),QALAM_LMS_UI_VERSION);
        wp_enqueue_script('qalam-150-admin',plugin_dir_url(TUTOR_FILE).'assets/js/qalam-150-admin.js',array(),QALAM_LMS_UI_VERSION,true);
    }
},PHP_INT_MAX);

/* -------------------------------------------------------------------------
 * 5. Admin student record: courses, progress, certificates and quiz grades.
 * ---------------------------------------------------------------------- */
function qalam_150_student_profile_menu(): void {
    add_submenu_page( null, 'ملف الطالب', 'ملف الطالب', 'list_users', 'qalam-student-profile', 'qalam_150_render_student_profile' );
}
add_action('admin_menu','qalam_150_student_profile_menu',100);

function qalam_150_admin_student_public_profile_redirect(): void {
    if ( function_exists( 'qalam_feature_enabled' ) && ! qalam_feature_enabled( 'student_analytics' ) ) { return; }
    if ( ! is_user_logged_in() || ! current_user_can('list_users') || ! empty($_GET['qalam_public']) ) { return; }
    $username = sanitize_user( (string) get_query_var('tutor_profile_username') );
    if ( ! $username ) { return; }
    $user = tutor_utils()->get_user_by_login( $username );
    if ( $user && ! empty($user->ID) && class_exists('TUTOR\\User') && \TUTOR\User::is_student((int)$user->ID) ) {
        wp_safe_redirect( admin_url('admin.php?page=qalam-student-profile&student_id='.(int)$user->ID) ); exit;
    }
}
add_action('template_redirect','qalam_150_admin_student_public_profile_redirect',-200);

add_filter('user_row_actions', static function($actions,$user){
    if ( function_exists( 'qalam_feature_enabled' ) && ! qalam_feature_enabled( 'student_analytics' ) ) { return $actions; }
    if(current_user_can('list_users') && class_exists('TUTOR\\User') && \TUTOR\User::is_student($user->ID)){
        $actions['qalam_student_profile']='<a href="'.esc_url(admin_url('admin.php?page=qalam-student-profile&student_id='.$user->ID)).'">ملف الطالب في قلم</a>';
    }
    return $actions;
},20,2);

function qalam_150_attempt_percent( $attempt ): float {
    $earned=(float)($attempt->earned_marks??0); $total=(float)($attempt->total_marks??0); return $total>0?round(($earned/$total)*100,2):0;
}
function qalam_150_quiz_passing_percent( int $quiz_id ): float { $opt=(array)tutor_utils()->get_quiz_option($quiz_id); return (float)($opt['passing_grade']??0); }
function qalam_150_render_student_profile(): void {
    if(!current_user_can('list_users')){wp_die('غير مسموح.');}
    $student_id=absint($_GET['student_id']??0); $user=get_userdata($student_id); if(!$user){wp_die('الطالب غير موجود.');}
    $courses=\Tutor\Models\CourseModel::get_enrolled_courses_by_user($student_id); $course_posts=$courses&&is_array($courses->posts)?$courses->posts:array();
    $attempts=(array)tutor_utils()->get_all_quiz_attempts_by_user($student_id); $certs=array();
    if(class_exists('TUTOR_CERT\\Certificate')){try{$certs=(new \TUTOR_CERT\Certificate(true))->get_user_certificates($student_id);}catch(\Throwable $e){$certs=array();}}
    $phone=get_user_meta($student_id,'phone_number',true) ?: get_user_meta($student_id,QALAM_081_GUEST_PHONE_META,true);
    ?><div class="wrap qalam-150-admin qalam-student-record" dir="rtl"><header class="qalam-student-head"><?php echo get_avatar($student_id,96);?><div><h1><?php echo esc_html($user->display_name);?></h1><p><?php echo esc_html($user->user_email);?><?php if($phone):?> · <?php echo esc_html($phone);?><?php endif;?></p><small>تاريخ التسجيل: <?php echo esc_html(mysql2date('j F Y',$user->user_registered));?></small></div></header>
    <div class="qalam-student-summary"><div><strong><?php echo count($course_posts);?></strong><span>دورات</span></div><div><strong><?php echo count($attempts);?></strong><span>محاولات اختبار</span></div><div><strong><?php echo count($certs);?></strong><span>شهادات</span></div></div>
    <section class="qalam-150-card"><h2>الدورات والتقدم</h2><?php if(!$course_posts):?><p>الطالب مش مسجل في دورات.</p><?php else:?><div class="qalam-student-courses"><?php foreach($course_posts as $c):$progress=(float)tutor_utils()->get_course_completed_percent($c->ID,$student_id);?><article><div><strong><?php echo esc_html($c->post_title);?></strong><small><?php echo esc_html(number_format_i18n($progress,0));?>% مكتمل</small></div><div class="qalam-progress"><i style="width:<?php echo esc_attr(max(0,min(100,$progress)));?>%"></i></div><a class="button" href="<?php echo esc_url(get_permalink($c));?>" target="_blank">فتح الدورة</a></article><?php endforeach;?></div><?php endif;?></section>
    <section class="qalam-150-card"><h2>درجات الاختبارات</h2><?php if(!$attempts):?><p>مفيش محاولات اختبارات.</p><?php else:?><div class="qalam-table-scroll"><table class="widefat striped"><thead><tr><th>الاختبار</th><th>الدرجة</th><th>النسبة</th><th>الحالة</th><th>التاريخ</th><th>مراجعة</th></tr></thead><tbody><?php foreach($attempts as $a):$pct=qalam_150_attempt_percent($a);$pass=qalam_150_quiz_passing_percent((int)$a->quiz_id);$quiz=get_post((int)$a->quiz_id);?><tr><td><?php echo esc_html($quiz?$quiz->post_title:'#'.$a->quiz_id);?></td><td><?php echo esc_html((float)($a->earned_marks??0).' / '.(float)($a->total_marks??0));?></td><td><?php echo esc_html($pct.'%');?></td><td><?php echo $pct>=$pass?'<span class="qalam-ok">ناجح</span>':'<span class="qalam-bad">راسب</span>';?></td><td><?php $dt=(string)($a->attempt_ended_at??$a->attempt_started_at??''); echo esc_html($dt?mysql2date('j/n/Y g:i a',$dt):'—');?></td><td><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tutor_quiz_attempts&view_quiz_attempt_id='.(int)$a->attempt_id));?>">الإجابات</a></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>
    <section class="qalam-150-card"><h2>الشهادات</h2><?php if(!$certs):?><p>الطالب لسه ما حصلش على شهادات.</p><?php else:?><div class="qalam-cert-grid"><?php foreach($certs as $cert):$cid=absint($cert['certificate_id']??0);$rev=!empty($cert['is_revoked']);?><article><strong><?php echo esc_html($cert['title']??$cert['course_title']??'شهادة');?></strong><span class="<?php echo $rev?'qalam-bad':'qalam-ok';?>"><?php echo $rev?'ملغاة':'فعالة';?></span><?php if(!$rev&&!empty($cert['certificate_url'])):?><a class="button button-primary" target="_blank" href="<?php echo esc_url($cert['certificate_url']);?>">عرض الشهادة</a><?php endif;?><?php if($cid):?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="qalam_certificate_revoke"><input type="hidden" name="completion_id" value="<?php echo esc_attr($cid);?>"><input type="hidden" name="revoke" value="<?php echo $rev?'0':'1';?>"><?php wp_nonce_field('qalam_certificate_revoke_'.$cid);?><button class="button" type="submit"><?php echo $rev?'إعادة تفعيل':'إلغاء الشهادة';?></button></form><?php endif;?></article><?php endforeach;?></div><?php endif;?></section></div><?php
}

/* -------------------------------------------------------------------------
 * 6. Final frontend/admin design, exam UI and dropdown stacking.
 * ---------------------------------------------------------------------- */
function qalam_150_assets(): void {
    if ( is_admin() && function_exists('qalam_is_product_admin_surface') && ! qalam_is_product_admin_surface() ) { return; }
    $base=plugin_dir_url(TUTOR_FILE);
    wp_enqueue_style('qalam-150-ui',$base.'assets/css/qalam-150.css',array(),QALAM_LMS_UI_VERSION);
}
add_action('wp_enqueue_scripts','qalam_150_assets',PHP_INT_MAX);
add_action('admin_enqueue_scripts','qalam_150_assets',PHP_INT_MAX);
