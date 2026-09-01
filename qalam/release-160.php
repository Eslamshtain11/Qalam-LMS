<?php
/** Qalam LMS 0.16 — close all previously-started implementation work at source level. */
defined( 'ABSPATH' ) || exit;

const QALAM_160_CLOSURE_VERSION = '0.16.0-partials-closure';

/** Final Arabic-first dictionary for started Core/Pro surfaces. */
function qalam_160_dictionary( array $map ): array {
    $extra = array(
        'Download Certificate'=>'تحميل الشهادة','Certificate Image Error'=>'مشكلة في صورة الشهادة','Course not yet completed'=>'الدورة لسه ما اكتملتش','Invalid Course ID'=>'معرّف الدورة غير صحيح',
        'Certificate verified successfully'=>'تم التحقق من الشهادة بنجاح','Certificate not found'=>'الشهادة غير موجودة','Certificate ID is required'=>'معرّف الشهادة مطلوب',
        'Please collect OTP and enter here to complete login process.'=>'هات كود التحقق واكتبه هنا لإكمال تسجيل الدخول.','Resend OTP'=>'إعادة إرسال الكود','Verify OTP'=>'تأكيد الكود',
        'Something went wrong, please try again!'=>'حصلت مشكلة. حاول مرة تانية.','Something went wrong! Please try again'=>'حصلت مشكلة. حاول مرة تانية.','Registration is not enabled, please contact with site owner!'=>'التسجيل متوقف حاليًا. تواصل مع إدارة المنصة.',
        'Please complete the authorization process'=>'كمّل خطوات منح الصلاحيات','Press the button to grant permissions to your Google Classroom. Please allow all required permissions.'=>'اضغط الزر وامنح Google Classroom كل الصلاحيات المطلوبة.',
        'Something went wrong, please check credential and permission!'=>'حصلت مشكلة. راجع بيانات الربط والصلاحيات.','Please note that this course has the following prerequisites which must be completed before it can be accessed'=>'الدورة دي لها متطلبات سابقة لازم تكملها قبل الدخول.',
        'Please select at least one student'=>'اختار طالب واحد على الأقل','Please select a course or subscription plan'=>'اختار دورة أو خطة اشتراك','Enrollment for %s is currently paused. Please remove it from your cart to proceed.'=>'التسجيل في %s متوقف حاليًا. احذفه من السلة علشان تكمل.',
        'Are you sure you want to delete this meeting permanently? Please confirm your choice.'=>'متأكد إنك عايز تحذف الاجتماع نهائيًا؟','Download'=>'تحميل','View Certificate'=>'عرض الشهادة','No certificates found'=>'مفيش شهادات.',
        'Course Progress'=>'تقدم الدورة','Student Details'=>'بيانات الطالب','Student Profile'=>'ملف الطالب','Quiz Results'=>'نتائج الاختبارات','Attempts'=>'المحاولات','Passed'=>'ناجح','Failed'=>'راسب',
        'Subscription Status'=>'حالة الاشتراك','Renewal'=>'التجديد','Next Payment'=>'الدفعة القادمة','Payment'=>'الدفع','Payments'=>'المدفوعات','Refund'=>'استرداد','Refunded'=>'تم الاسترداد',
        'Start Meeting'=>'ابدأ الاجتماع','Join Meeting'=>'ادخل الاجتماع','Meeting'=>'الاجتماع','Meetings'=>'الاجتماعات','Live Classes'=>'الحصص المباشرة','Live Class'=>'حصة مباشرة',
        'No assignments found'=>'مفيش واجبات.','Submit Assignment'=>'تسليم الواجب','Assignment Submitted'=>'تم تسليم الواجب','Grade'=>'الدرجة','Grading'=>'التقييم',
        'Content Drip'=>'الإتاحة التدريجية','Prerequisites'=>'المتطلبات السابقة','Course Bundle'=>'حزمة دورات','Course Bundles'=>'حزم الدورات','Preview Course'=>'معاينة الدورة',
        'Notifications'=>'الإشعارات','Email Notifications'=>'إشعارات البريد','Manual Email'=>'بريد يدوي','Send Email'=>'إرسال البريد','Recipient'=>'المستلم','Recipients'=>'المستلمون',
        'Authenticator App (TOTP)'=>'تطبيق المصادقة (TOTP)','Authenticator app is required but is not configured for this account.'=>'تطبيق المصادقة مطلوب للحساب لكنه لسه مش متجهز.','Enter the 6-digit code from your authenticator app or a recovery code.'=>'اكتب كود الـ6 أرقام من تطبيق المصادقة أو استخدم كود استرداد.',
        'Verification request expired. Please login again.'=>'طلب التحقق انتهت صلاحيته. سجل دخول من جديد.','Invalid or expired social login request'=>'طلب تسجيل الدخول الاجتماعي غير صالح أو انتهت صلاحيته.',
        'This content is locked because its drip date is missing or invalid.'=>'المحتوى مقفول لأن تاريخ الإتاحة ناقص أو غير صحيح.','This content is locked because the drip day configuration is missing or invalid.'=>'المحتوى مقفول لأن إعداد عدد أيام الإتاحة ناقص أو غير صحيح.',
        'A configured prerequisite is missing or unavailable.'=>'في متطلب سابق متحدد لكنه غير موجود أو غير متاح.','This content is locked because its prerequisite configuration is empty.'=>'المحتوى مقفول لأن إعداد المتطلبات السابقة فاضي.','This content is locked because its drip configuration is invalid.'=>'المحتوى مقفول لأن إعداد الإتاحة التدريجية غير صحيح.',
        'No results'=>'مفيش نتائج','No results found'=>'مفيش نتائج','No data available'=>'مفيش بيانات متاحة','Loading'=>'جاري التحميل','Loading...'=>'جاري التحميل...',
    );
    return array_replace( $map, $extra );
}
add_filter( 'qalam_lms_dictionary', 'qalam_160_dictionary', PHP_INT_MAX );

/** Catch PHP-rendered strings from all preserved Tutor text domains. */
function qalam_160_gettext( string $translation, string $text, string $domain ): string {
    if ( ! in_array( $domain, array( 'tutor','tutor-pro' ), true ) ) { return $translation; }
    $dict = qalam_lms_dictionary();
    return array_key_exists( $text, $dict ) ? (string) $dict[ $text ] : $translation;
}
add_filter( 'gettext', 'qalam_160_gettext', PHP_INT_MAX, 3 );

/** Qalam identity in transactional email unless the owner configured a custom logo. */
add_filter( 'tutor_email_logo_src', static function( $url ) {
    $custom = get_tutor_option( 'tutor_email_template_logo_id' );
    if ( $custom ) { return $url; }
    return plugin_dir_url( TUTOR_FILE ) . 'assets/images/qalam-logo.svg';
}, PHP_INT_MAX );
add_filter( 'admin_footer_text', static fn() => 'Qalam LMS — تطوير مؤسسة قلم للخدمات الإلكترونية', PHP_INT_MAX );

/** Keep every Tutor-marked student visible as the Qalam Student role, safely and incrementally. */
function qalam_160_sync_existing_students_batch(): void {
    if ( ! current_user_can( 'list_users' ) || get_option( 'qalam_160_student_roles_complete' ) ) { return; }
    $offset = max( 0, (int) get_option( 'qalam_160_student_role_offset', 0 ) );
    $users = get_users( array( 'number'=>40,'offset'=>$offset,'meta_key'=>'_is_tutor_student','meta_value'=>'1','fields'=>array('ID') ) );
    if ( ! $users ) { update_option( 'qalam_160_student_roles_complete', 1, false ); delete_option( 'qalam_160_student_role_offset' ); return; }
    foreach ( $users as $row ) { $u=new WP_User((int)$row->ID); if(!in_array('qalam_student',(array)$u->roles,true)){$u->add_role('qalam_student');} }
    update_option( 'qalam_160_student_role_offset', $offset + count($users), false );
}
add_action( 'admin_init', 'qalam_160_sync_existing_students_batch', 90 );
function qalam_160_student_meta_role_sync( $meta_id, $user_id, $meta_key, $_meta_value ): void {
    if ( '_is_tutor_student' !== (string) $meta_key || '1' !== (string) $_meta_value ) { return; }
    $u=new WP_User((int)$user_id); if($u->exists() && !in_array('qalam_student',(array)$u->roles,true)){$u->add_role('qalam_student');}
}
add_action('added_user_meta','qalam_160_student_meta_role_sync',20,4);
add_action('updated_user_meta','qalam_160_student_meta_role_sync',20,4);

/** Student/guest accounts never need WordPress admin screens. */
add_action( 'admin_init', static function(){
    if ( wp_doing_ajax() || ! is_user_logged_in() || current_user_can('edit_posts') ) { return; }
    $u=wp_get_current_user(); if(in_array('qalam_student',(array)$u->roles,true) || '1'===(string)get_user_meta($u->ID,QALAM_081_GUEST_META,true)) { wp_safe_redirect( tutor_utils()->tutor_dashboard_url() ); exit; }
}, 80 );

/** Public exams must never leak the internal compatibility course into normal navigation/search. */
add_action( 'pre_get_posts', static function( $query ) {
    if ( is_admin() || ! $query instanceof WP_Query ) { return; }
    if ( tutor()->course_post_type !== $query->get('post_type') ) { return; }
    $mq=(array)$query->get('meta_query'); $mq[]=array('key'=>QALAM_GENERAL_COURSE_META,'compare'=>'NOT EXISTS'); $query->set('meta_query',$mq);
}, PHP_INT_MAX );

/** Video-ad events: minimal, bounded analytics without storing personal data. */
function qalam_160_video_ad_event(): void {
    check_ajax_referer( 'qalam_160_video_ad_event', 'nonce' );
    $ad=absint($_POST['ad_id']??0); $event=sanitize_key((string)($_POST['event_name']??''));
    if(!$ad || QALAM_150_VIDEO_AD_POST_TYPE!==get_post_type($ad) || !in_array($event,array('impression','skipped','completed'),true)){wp_send_json_error(array('message'=>'invalid'),400);}
    $key='_qalam_ad_stat_'.$event; update_post_meta($ad,$key,max(0,(int)get_post_meta($ad,$key,true))+1); wp_send_json_success();
}
add_action('wp_ajax_qalam_160_video_ad_event','qalam_160_video_ad_event');
add_action('wp_ajax_nopriv_qalam_160_video_ad_event','qalam_160_video_ad_event');

/** Surface ad stats on the existing admin card without creating another module. */
add_filter( 'qalam_150_video_ad_runtime_row', static function( array $row, int $ad_id ): array {
    $row['stats']=array('impressions'=>(int)get_post_meta($ad_id,'_qalam_ad_stat_impression',true),'skipped'=>(int)get_post_meta($ad_id,'_qalam_ad_stat_skipped',true),'completed'=>(int)get_post_meta($ad_id,'_qalam_ad_stat_completed',true)); return $row;
},10,2);

/** Final source-level add-on labels; external brands stay untouched. */
add_filter( 'tutor_addons_data', static function( $addons ) {
    if(!is_array($addons)) return $addons; foreach($addons as &$a){ if(!is_array($a))continue; $name=(string)($a['name']??''); $dict=qalam_lms_dictionary(); if(isset($dict[$name]))$a['name']=$dict[$name]; } unset($a); return $addons;
}, PHP_INT_MAX );

/** Player runtime data for ad analytics. */
add_action( 'wp_enqueue_scripts', static function(){
    if(wp_script_is('qalam-lms-video-player','enqueued')){wp_localize_script('qalam-lms-video-player','QalamVideoRuntime',array('ajaxurl'=>admin_url('admin-ajax.php'),'adNonce'=>wp_create_nonce('qalam_160_video_ad_event')));}
}, PHP_INT_MAX );

/** Final UI layer for all started responsive surfaces. */
function qalam_160_assets(): void {
    if ( is_admin() && function_exists('qalam_is_product_admin_surface') && ! qalam_is_product_admin_surface() ) { return; }
    $base=plugin_dir_url(TUTOR_FILE); wp_enqueue_style('qalam-160-closure',$base.'assets/css/qalam-160-closure.css',array(),QALAM_LMS_UI_VERSION); wp_enqueue_script('qalam-160-closure',$base.'assets/js/qalam-160-closure.js',array(),QALAM_LMS_UI_VERSION,true);
}
add_action('wp_enqueue_scripts','qalam_160_assets',PHP_INT_MAX);
add_action('admin_enqueue_scripts','qalam_160_assets',PHP_INT_MAX);
