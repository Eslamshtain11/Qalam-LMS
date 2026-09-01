<?php
/**
 * Qalam LMS 0.7.0 real workflows.
 *
 * Connects Question Bank, Content Bank, standalone quizzes, AI/PDF generation,
 * and full-sentence Arabic locale data without replacing Tutor persistence.
 *
 * @package QalamLMS
 */

defined( 'ABSPATH' ) || exit;

const QALAM_QBANK_COLLECTION_META = '_qalam_question_bank_collection';
const QALAM_QBANK_PENDING_CAT     = '_qalam_pending_question_category';
const QALAM_QBANK_SOURCE_META     = '_qalam_question_source';
const QALAM_QBANK_PDF_META        = '_qalam_question_pdf_source';
const QALAM_QBANK_DIFFICULTY_META = '_qalam_question_difficulty';

/** Full-string translations for the remaining admin/React surfaces. */
function qalam_070_dictionary( $map ) {
	$map = is_array( $map ) ? $map : array();
	$extra = array(
		// AI quiz modal.
		'Generate Quiz Component' => 'إنشاء أسئلة بالذكاء الاصطناعي',
		'Question Types' => 'أنواع الأسئلة',
		'Select Topics' => 'اختار المحتوى',
		'Search topics...' => 'ابحث في المحتوى...',
		'Difficulty Level' => 'مستوى الصعوبة',
		'Easy' => 'سهل',
		'Medium' => 'متوسط',
		'Hard' => 'صعب',
		'Number of Questions' => 'عدد الأسئلة',
		'Generate Now' => 'إنشاء الأسئلة الآن',
		'AI will generate questions based on your selected topics and types.' => 'الذكاء الاصطناعي هيعمل الأسئلة حسب المحتوى والأنواع اللي اخترتها.',
		'Upload PDF' => 'ارفع ملف PDF',
		'PDF File' => 'ملف PDF',
		'Generate from PDF' => 'إنشاء من PDF',
		'Extract questions from PDF' => 'استخراج الأسئلة من PDF',
		'Create new questions from PDF' => 'إنشاء أسئلة جديدة من PDF',
		'True/False' => 'صح / خطأ',
		'Multiple Choice' => 'اختيارات متعددة',
		'Open Ended/Essay' => 'مقالي',
		'Fill in the Blanks' => 'أكمل الفراغات',
		'Short Answer' => 'إجابة قصيرة',
		'Matching' => 'توصيل',
		'Image Answering' => 'إجابة بالصورة',
		'Ordering' => 'ترتيب',
		'Image Marking' => 'تحديد على الصورة',
		'Range' => 'مدى',
		'Pin' => 'تحديد نقطة',
		'Graph' => 'رسم بياني',
		'Puzzle' => 'لغز',
		'Question Type' => 'نوع السؤال',
		'Conditions:' => 'إعدادات السؤال:',
		'Multiple Correct Answer' => 'أكثر من إجابة صحيحة',
		'Image Matching' => 'مطابقة بالصور',
		'Add Question' => 'إضافة سؤال',
		'Add Lesson' => 'إضافة درس',
		'Add Assignment' => 'إضافة واجب',
		'Create Collection' => 'إنشاء مجموعة',
		'Collection' => 'المجموعة',
		'Collections' => 'المجموعات',
		'Add Content' => 'إضافة محتوى',

		// Payment settings.
		'Payment Methods' => 'طرق الدفع',
		'Supported payment methods' => 'طرق الدفع المتاحة',
		'Supports Subscriptions' => 'يدعم الاشتراكات',
		'Environment' => 'بيئة التشغيل',
		'Test' => 'تجريبي',
		'Live' => 'فعلي',
		'Merchant email' => 'بريد التاجر',
		'Client id' => 'معرّف العميل',
		'Client ID' => 'معرّف العميل',
		'Secret id' => 'المفتاح السري',
		'Secret ID' => 'المفتاح السري',
		'Webhook id' => 'معرّف Webhook',
		'Webhook url' => 'رابط Webhook',
		'Webhook URL' => 'رابط Webhook',
		'Copy' => 'نسخ',
		'Add Manual Payment' => 'إضافة دفع يدوي',
		'Add New Gateway' => 'إضافة بوابة دفع',
		'Payment gateways' => 'بوابات الدفع',
		'Set up manual payment method' => 'إعداد طريقة دفع يدوية',
		'Taxes' => 'الضرائب',
		'Checkout' => 'إتمام الدفع',
		'Checkout Configuration' => 'إعدادات إتمام الدفع',
		'Tax Settings' => 'إعدادات الضرائب',
		'Reset to Default' => 'استعادة الإعدادات الافتراضية',

		// Reports / analytics.
		'LMS Reports' => 'تقارير قلم',
		'Reports' => 'التقارير',
		'Overview' => 'نظرة عامة',
		'Analytics' => 'التحليلات',
		'Detailed Report of Your Sales & Students' => 'تقرير تفصيلي عن المبيعات والطلاب',
		'Check your course performance through Tutor Report stats.' => 'تابع أداء دوراتك من خلال إحصائيات تقارير قلم.',
		'Total Course' => 'إجمالي الدورات',
		'Total Enrolled' => 'إجمالي المسجلين',
		'Total Enrolled:' => 'إجمالي المسجلين:',
		'Total Student' => 'إجمالي الطلاب',
		'Total Learners' => 'إجمالي المتعلمين',
		'Total Learners:' => 'إجمالي المتعلمين:',
		'Total Lessons' => 'إجمالي الدروس',
		'Total Questions' => 'إجمالي الأسئلة',
		'Total Earning' => 'إجمالي الأرباح',
		'Total Sale' => 'إجمالي المبيعات',
		'Total Refund' => 'إجمالي الاسترداد',
		'Total Discount' => 'إجمالي الخصومات',
		'Total Reviews' => 'إجمالي التقييمات',
		'Total Withdraws' => 'إجمالي السحوبات',
		'Published Courses' => 'الدورات المنشورة',
		'Completed Courses' => 'الدورات المكتملة',
		'Courses in Progress' => 'الدورات قيد الدراسة',
		'In Progress Courses' => 'الدورات قيد الدراسة',
		'Last enrolled courses' => 'أحدث الدورات المسجل بها',
		'Most Popular Courses' => 'أكثر الدورات انتشارًا',
		'New Registered students' => 'الطلاب المسجلون حديثًا',
		'New Registered Teachers' => 'المعلمون المسجلون حديثًا',
		'Recent Reviews' => 'أحدث التقييمات',
		'Course Enrolled Chart %s' => 'مخطط التسجيل في الدورات %s',
		'Earnings Chart %s' => 'مخطط الأرباح %s',
		'Discount Chart %s' => 'مخطط الخصومات %s',
		'Refund Chart %s' => 'مخطط الاسترداد %s',
		'Earnings Graph' => 'رسم الأرباح',
		'Earning graph' => 'رسم الأرباح',
		'Earnings' => 'الأرباح',
		'Earning' => 'الأرباح',
		'My Earnings' => 'أرباحي',
		'Net Earnings' => 'صافي الأرباح',
		'Net Amount:' => 'صافي المبلغ:',
		'Admin Gets' => 'نصيب الإدارة',
		'Admin Share' => 'نسبة الإدارة',
		'Commission' => 'العمولة',
		'Commissions' => 'العمولات',
		'Deducted Commissions' => 'العمولات المخصومة',
		'Deducted Fees' => 'الرسوم المخصومة',
		'Maintenance Fees:' => 'رسوم التشغيل:',
		'Price Breakdown' => 'تفاصيل السعر',
		'Order Amount:' => 'قيمة الطلب:',
		'Tax Amount (%s):' => 'قيمة الضريبة (%s):',
		'Purchaser:' => 'المشتري:',
		'Transaction Details' => 'تفاصيل المعاملة',
		'Statements' => 'كشف الحساب',
		'No Statements Found!' => 'لا توجد كشوف حساب.',
		'No Courses Found!' => 'لا توجد دورات.',
		'No Students Found!' => 'لا يوجد طلاب.',
		'Content Not Found!' => 'المحتوى غير موجود.',
		'Download CSV' => 'تحميل CSV',
		'Export to keep a copy of your analytics data.' => 'صدّر البيانات للاحتفاظ بنسخة من التحليلات.',
		'Export' => 'تصدير',
		'Search courses...' => 'ابحث في الدورات...',
		'Registration Date' => 'تاريخ التسجيل',
		'Registration Date:' => 'تاريخ التسجيل:',
		'Register at' => 'تاريخ التسجيل',
		'Registered at:' => 'تاريخ التسجيل:',
		'Enroll Date' => 'تاريخ التسجيل بالدورة',
		'Published Date' => 'تاريخ النشر',
		'Last Update' => 'آخر تحديث',
		'Course Name' => 'اسم الدورة',
		'Course Taken' => 'الدورات الملتحق بها',
		'Course Taken:' => 'الدورات الملتحق بها:',
		'Quizzes Taken' => 'الاختبارات التي تم حلها',
		'Assignment Submit' => 'الواجبات المسلّمة',
		'Certificate Issued' => 'الشهادات الصادرة',
		'Reviews Placed' => 'التقييمات المضافة',
		'View Profile' => 'عرض الملف الشخصي',
		'View Progress' => 'عرض التقدم',
		'View Course' => 'عرض الدورة',
		'Edit with Builder' => 'تعديل بالمنشئ',
		'Actions' => 'الإجراءات',
		'Breakdown' => 'التفاصيل',
		'Balance' => 'الرصيد',
		'Current Balance' => 'الرصيد الحالي',
		'Sale' => 'مبيعة',
		'Sales' => 'المبيعات',
		'Refund' => 'استرداد',
		'Discount' => 'خصم',
		'Fees' => 'رسوم',
		'Withdraws' => 'السحوبات',
		'Assignment' => 'واجب',
		'Assignments' => 'الواجبات',
		'Quiz' => 'اختبار',
		'Quizzes' => 'الاختبارات',
		'Lesson' => 'درس',
		'Lessons' => 'الدروس',
		'Student' => 'طالب',
		'Students' => 'الطلاب',
		'Student Info' => 'بيانات الطالب',
		'Teacher' => 'معلم',
		'Instructor' => 'معلم',
		'Instructors' => 'المعلمون',
		'Category' => 'التصنيف',
		'Certificate' => 'الشهادة',
		'Rating' => 'التقييم',
		'Rating:' => 'التقييم:',
		'Feedback' => 'الملاحظات',
		'Status' => 'الحالة',
		'Created' => 'تاريخ الإنشاء',
		'Completed' => 'مكتمل',
		'Pending' => 'قيد الانتظار',
		'Publish' => 'نشر',
		'Published' => 'منشور',
		'Unpublished' => 'غير منشور',
		'Draft' => 'مسودة',
		'Preview' => 'معاينة',
		'Today' => 'اليوم',
		'Monthly' => 'شهري',
		'Yearly' => 'سنوي',
		'Clear' => 'مسح',
		'Clear All' => 'مسح الكل',
		'Back' => 'رجوع',
		'Delete' => 'حذف',
		'Search' => 'بحث',
		'Name' => 'الاسم',
		'Email' => 'البريد الإلكتروني',
		'Email:' => 'البريد الإلكتروني:',
		'User Name:' => 'اسم المستخدم:',
		'Price' => 'السعر',
		'Progress' => 'التقدم',
		'Update review status' => 'تحديث حالة التقييم',
		'Invalid course' => 'الدورة غير صالحة',
		'Invalid student' => 'الطالب غير صالح',
		'Invalid student id' => 'معرّف الطالب غير صالح',
		'Date' => 'التاريخ',
		'Details' => 'التفاصيل',
		'Courses' => 'الدورات',
		'Course' => 'الدورة',
		'Enrolled' => 'مسجل',
		'Enrolled Courses' => 'الدورات المسجل بها',
		'Questions' => 'الأسئلة',
		'Reviews' => 'التقييمات',
		'Number of Sales' => 'عدد المبيعات',
		'Order ID' => 'رقم الطلب',
		'Progress Courses' => 'الدورات قيد التقدم',
		'Earnings:' => 'الأرباح:',
		'Course Enrolled' => 'التسجيل في الدورة',
		'Courses Completed' => 'الدورات المكتملة',
		'for %s' => 'لـ %s',
		'Most popular courses' => 'أكثر الدورات انتشارًا',
		'As per %1$d%2$s' => 'حسب %1$d%2$s',
		'%1$s%2$s of %3$s' => '%1$s%2$s من %3$s',
		'Maintenance Fees: ' => 'رسوم التشغيل: ',
		'Net Amount: ' => 'صافي المبلغ: ',
		'Order Amount: ' => 'قيمة الطلب: ',
		'Tax Amount (%s): ' => 'قيمة الضريبة (%s): ',
		'%1$s %2$s' => '%1$s %2$s',
		'Select difficulty level' => 'اختار مستوى الصعوبة',
		'Number of questions' => 'عدد الأسئلة',
		'Generating...' => 'جاري إنشاء الأسئلة...',
		'Generate quiz using AI' => 'إنشاء اختبار بالذكاء الاصطناعي',
		'Close Popover' => 'إغلاق النافذة',
		'Question preview' => 'معاينة السؤال',
		'True' => 'صح',
		'False' => 'خطأ',
		'Question' => 'سؤال',
		'Add Question' => 'إضافة سؤال',
		'Content Bank' => 'بنك المحتوى',
		'Payment Settings' => 'إعدادات الدفع',
		'Customize your checkout process to suit your preferences.' => 'ظبّط خطوات الدفع بما يناسب طريقة البيع على منصتك.',
		'Cart Page' => 'صفحة سلة الشراء',
		'Select the page you wish to set as the cart page.' => 'اختار الصفحة اللي هتستخدم كسلة شراء.',
		'Checkout Page' => 'صفحة إتمام الدفع',
		'Select the page to be used as the checkout page.' => 'اختار الصفحة اللي هتستخدم لإتمام الدفع.',
		'Currency' => 'العملة',
		'Choose the currency for transactions.' => 'اختار العملة المستخدمة في عمليات الدفع.',
		'Currency Position' => 'مكان رمز العملة',
		'Set the position of the currency symbol.' => 'حدد مكان ظهور رمز العملة بالنسبة للمبلغ.',
		'Thousand Separator' => 'فاصل الآلاف',
		'Specify the thousand separator.' => 'حدد العلامة المستخدمة لفصل الآلاف.',
		'Decimal Separator' => 'الفاصل العشري',
		'Specify the decimal separator.' => 'حدد العلامة المستخدمة كفاصل عشري.',
		'Number of Decimals' => 'عدد الخانات العشرية',
		'Set the number of decimal places.' => 'حدد عدد الخانات العشرية في الأسعار.',
		'Enable Coupon Code' => 'تفعيل أكواد الخصم',
		'Allow users to apply the coupon code during checkout.' => 'اسمح للطلاب باستخدام كود الخصم أثناء الدفع.',
		'Enable "Buy Now" Button' => 'تفعيل زر «اشترِ الآن»',
		'Allow users to purchase courses directly without adding them to the cart.' => 'اسمح بشراء الدورة مباشرة من غير إضافتها للسلة الأول.',
		'From Address' => 'عنوان البائع',
		'Specify the "From Address" that will appear in the top-right corner of the order invoice.' => 'حدد عنوان البائع اللي هيظهر في فاتورة الطلب.',
		'Enable Guest Checkout' => 'السماح بالدفع كزائر',
		'Allow users to checkout as a guest user.' => 'اسمح بإتمام الدفع من غير إنشاء حساب أولًا.',
		'Left' => 'يسار',
		'Right' => 'يمين',
		'Operation failed' => 'فشلت العملية',
		'Something Went Wrong!' => 'حصل خطأ غير متوقع.',
		'Review has been deleted ' => 'تم حذف التقييم.',
		'Failed' => 'فشل',
		'Review delete failed ' => 'تعذر حذف التقييم.',
		'North America' => 'أمريكا الشمالية',
		'Asia' => 'آسيا',
		'Europe' => 'أوروبا',
		'Oceania' => 'أوقيانوسيا',
	);
	return array_replace( $map, $extra );
}
add_filter( 'qalam_lms_dictionary', 'qalam_070_dictionary', 70 );

/** Inject WordPress locale data before Tutor React bundles execute. */
function qalam_070_inject_locale_data() {
	if ( ! is_admin() || ( function_exists( 'qalam_is_product_admin_surface' ) && ! qalam_is_product_admin_surface() ) ) { return; }
	wp_enqueue_script( 'wp-i18n' );
	$locale = array(
		'' => array(
			'domain' => 'tutor',
			'lang' => 'ar',
			'plural-forms' => 'nplurals=6; plural=n==0?0:n==1?1:n==2?2:n%100>=3&&n%100<=10?3:n%100>=11&&n%100<=99?4:5;',
		),
	);
	foreach ( qalam_lms_dictionary() as $source => $translated ) {
		$locale[ (string) $source ] = array( (string) $translated );
	}
	$json = wp_json_encode( $locale, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$js = 'if(window.wp&&wp.i18n&&wp.i18n.setLocaleData){wp.i18n.setLocaleData('.$json.',"tutor");wp.i18n.setLocaleData('.$json.',"tutor-pro");}';
	wp_add_inline_script( 'wp-i18n', $js, 'after' );
}
add_action( 'admin_enqueue_scripts', 'qalam_070_inject_locale_data', 0 );

/** Restore/override Qalam menu callbacks with real workflows. */
function qalam_070_admin_menu( $menu ) {
	if ( ! is_array( $menu ) ) { return $menu; }
	if ( isset( $menu['group_one']['qalam_quiz_builder'] ) ) {
		$menu['group_one']['qalam_quiz_builder']['callback'] = 'qalam_070_render_quiz_builder';
		$menu['group_one']['qalam_quiz_builder']['page_title'] = 'منشئ الاختبارات';
		$menu['group_one']['qalam_quiz_builder']['menu_title'] = 'منشئ الاختبارات';
	}
	if ( isset( $menu['group_one']['qalam_question_bank'] ) ) {
		$menu['group_one']['qalam_question_bank']['callback'] = 'qalam_070_render_question_bank';
	}
	return $menu;
}
add_filter( 'tutor_admin_menu', 'qalam_070_admin_menu', PHP_INT_MAX );

/** Dedicated Content Bank collection used by Qalam Question Bank. */
function qalam_070_question_bank_collection_id( $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $user_id || ! post_type_exists( 'cb-collection' ) ) { return 0; }
	$ids = get_posts( array(
		'post_type' => 'cb-collection', 'post_status' => 'any', 'author' => $user_id,
		'posts_per_page' => 1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC',
		'meta_key' => QALAM_QBANK_COLLECTION_META, 'meta_value' => '1',
	) );
	if ( $ids ) { return (int) $ids[0]; }
	$id = wp_insert_post( array(
		'post_type' => 'cb-collection', 'post_status' => 'publish', 'post_author' => $user_id,
		'post_title' => 'بنك الأسئلة — قلم', 'post_content' => '',
	), true );
	if ( is_wp_error( $id ) ) { return 0; }
	update_post_meta( $id, QALAM_QBANK_COLLECTION_META, '1' );
	return (int) $id;
}

/** Remember a category for the next native Content Bank question created from Qalam. */
function qalam_070_prepare_native_question_editor() {
	if ( ! is_admin() || ! current_user_can( 'manage_tutor_instructor' ) ) { return; }
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'tutor-content-bank' !== $page || empty( $_GET['qalam_qbank'] ) ) { return; }
	if ( ! empty( $_GET['qalam_open_question'] ) ) {
		$term_id = isset( $_GET['question_category'] ) ? absint( $_GET['question_category'] ) : 0;
		update_user_meta( get_current_user_id(), QALAM_QBANK_PENDING_CAT, $term_id );
	}
}
add_action( 'admin_init', 'qalam_070_prepare_native_question_editor', 5 );

/** Assign pending category after the native Content Bank saves cb-question. */
function qalam_070_assign_pending_category( $post_id, $post, $update ) {
	if ( ! $post || 'cb-question' !== $post->post_type || ! current_user_can( 'manage_tutor_instructor' ) ) { return; }
	$user_id = get_current_user_id();
	$pending = get_user_meta( $user_id, QALAM_QBANK_PENDING_CAT, true );
	if ( '' === (string) $pending ) { return; }
	$term_id = absint( $pending );
	wp_set_object_terms( $post_id, $term_id ? array( $term_id ) : array(), QALAM_QUESTION_CATEGORY_TAX, false );
	delete_user_meta( $user_id, QALAM_QBANK_PENDING_CAT );
}
add_action( 'save_post_cb-question', 'qalam_070_assign_pending_category', 30, 3 );

/** Build native Content Bank question editor URL. */
function qalam_070_native_question_url( $term_id = 0 ) {
	$collection_id = qalam_070_question_bank_collection_id();
	return add_query_arg( array(
		'page' => 'tutor-content-bank', 'collection_id' => $collection_id,
		'qalam_qbank' => 1, 'qalam_open_question' => 1, 'question_category' => absint( $term_id ),
	), admin_url( 'admin.php' ) );
}

/** Real Question Bank: native editor + AI/PDF + existing native questions. */
function qalam_070_render_question_bank() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) { wp_die( 'غير مسموح.' ); }
	$term_id = isset( $_GET['question_category'] ) ? absint( $_GET['question_category'] ) : 0;
	$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$terms = get_terms( array( 'taxonomy'=>QALAM_QUESTION_CATEGORY_TAX, 'hide_empty'=>false ) );
	$terms = is_wp_error( $terms ) ? array() : $terms;
	$tax_query = $term_id ? array( array( 'taxonomy'=>QALAM_QUESTION_CATEGORY_TAX, 'field'=>'term_id', 'terms'=>$term_id, 'include_children'=>true ) ) : array();
	$questions = get_posts( array( 'post_type'=>'cb-question', 'post_status'=>array('publish','draft','private'), 'posts_per_page'=>100, 's'=>$search, 'tax_query'=>$tax_query, 'orderby'=>'modified', 'order'=>'DESC' ) );
	$labels = qalam_060_question_type_labels();
	$types = array(); global $wpdb;
	if ( $questions ) {
		$ids = array_map( static function($q){ return (int)$q->ID; }, $questions );
		$placeholders = implode( ',', array_fill( 0, count($ids), '%d' ) );
		$sql = $wpdb->prepare( "SELECT content_id,question_type FROM {$wpdb->prefix}tutor_quiz_questions WHERE content_id IN ($placeholders)", $ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $wpdb->get_results( $sql ) as $row ) { $types[(int)$row->content_id]=(string)$row->question_type; }
	}
	$collection_id = qalam_070_question_bank_collection_id();
	$created = isset($_GET['qalam_created']) ? absint($_GET['qalam_created']) : 0;
	$notice = isset($_GET['qalam_notice']) ? sanitize_text_field(wp_unslash($_GET['qalam_notice'])) : '';
	$error = isset($_GET['qalam_error']) ? sanitize_text_field(wp_unslash($_GET['qalam_error'])) : '';
	?>
	<div class="wrap qalam-050-wrap qalam-060-wrap qalam-070-wrap" dir="rtl">
		<div class="qalam-050-hero"><div><span class="qalam-050-eyebrow">Qalam LMS</span><h1>بنك الأسئلة</h1><p>بنك أسئلة فعلي مرتبط بمحرك قلم وبنك المحتوى. أي سؤال تنشئه هنا يُحفظ في بنك المحتوى ويمكن نسخه لأي اختبار.</p></div><div class="qalam-050-hero-actions"><a class="button button-primary qalam-050-primary" href="<?php echo esc_url(qalam_070_native_question_url($term_id)); ?>">+ إنشاء سؤال يدوي</a><a class="button" href="#qalam-ai-question-generator">✨ إنشاء بالذكاء الاصطناعي / PDF</a></div></div>
		<?php if($created):?><div class="notice notice-success inline"><p>تم إنشاء <?php echo esc_html($created); ?> سؤال وإضافتهم لبنك الأسئلة وبنك المحتوى.</p></div><?php endif;?>
		<?php if($notice):?><div class="notice notice-warning inline"><p><?php echo esc_html($notice); ?></p></div><?php endif;?>
		<?php if($error):?><div class="notice notice-error inline"><p><?php echo esc_html($error); ?></p></div><?php endif;?>
		<?php if(!$collection_id):?><div class="notice notice-warning inline"><p>فعّل ملحق «بنك المحتوى» علشان محرر الأسئلة الكامل يشتغل.</p></div><?php endif;?>
		<div class="qalam-question-layout">
			<aside class="qalam-question-sidebar qalam-050-panel"><h2>التصنيفات</h2><a class="qalam-category-link <?php echo $term_id?'':'is-active'; ?>" href="<?php echo esc_url(admin_url('admin.php?page=qalam-question-bank')); ?>">كل الأسئلة</a><?php qalam_060_render_term_tree($terms,0,$term_id); ?>
			<hr><h3>إضافة تصنيف</h3><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="qalam_060_add_question_category"><?php wp_nonce_field('qalam_060_add_question_category','qalam_qcat_nonce'); ?><input type="text" name="name" required placeholder="اسم التصنيف"><select name="parent"><option value="0">بدون تصنيف أب</option><?php foreach($terms as $term):?><option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option><?php endforeach;?></select><button class="button button-primary" type="submit">إضافة التصنيف</button></form></aside>
			<main class="qalam-question-main"><section class="qalam-050-panel"><div class="qalam-050-section-head"><div><h2>الأسئلة</h2><p>الأسئلة دي ظاهرة كمان داخل بنك المحتوى ويمكن استيرادها لأي اختبار.</p></div><form method="get"><input type="hidden" name="page" value="qalam-question-bank"><input type="search" class="qalam-050-search" name="q" value="<?php echo esc_attr($search); ?>" placeholder="ابحث في بنك الأسئلة..."></form></div>
			<div class="qalam-050-table-wrap"><table class="widefat striped qalam-050-table"><thead><tr><th>السؤال</th><th>النوع</th><th>الصعوبة</th><th>التصنيف</th><th>المجموعة</th><th>آخر تعديل</th></tr></thead><tbody><?php if(!$questions):?><tr><td colspan="6">لسه مفيش أسئلة في القسم ده. استخدم زر «إنشاء سؤال يدوي» أو مولد الذكاء الاصطناعي.</td></tr><?php else:foreach($questions as $q):$qterm_ids=wp_get_post_terms($q->ID,QALAM_QUESTION_CATEGORY_TAX,array('fields'=>'ids'));$current_qterm=!empty($qterm_ids)?(int)$qterm_ids[0]:0;$parent=get_post($q->post_parent);?><tr><td><strong><?php echo esc_html($q->post_title ?: wp_trim_words(wp_strip_all_tags($q->post_content),12)); ?></strong></td><td><?php echo esc_html($labels[$types[$q->ID]??'']??($types[$q->ID]??'—')); ?></td><td><?php qalam_071_render_difficulty_badge( (string) get_post_meta( $q->ID, QALAM_QBANK_DIFFICULTY_META, true ) ); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="qalam-inline-category-form"><input type="hidden" name="action" value="qalam_060_assign_question_category"><input type="hidden" name="question_id" value="<?php echo esc_attr($q->ID); ?>"><?php wp_nonce_field('qalam_060_assign_question_category_'.$q->ID,'qalam_assign_nonce'); ?><select name="term_id" onchange="this.form.submit()"><option value="0">غير مصنف</option><?php foreach($terms as $term):?><option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($current_qterm,$term->term_id); ?>><?php echo esc_html($term->name); ?></option><?php endforeach;?></select></form></td><td><?php echo esc_html($parent?$parent->post_title:'—'); ?></td><td><?php echo esc_html(get_the_modified_date('', $q)); ?></td></tr><?php endforeach;endif;?></tbody></table></div></section>
			<?php qalam_070_render_ai_generator( array( 'target'=>'bank', 'term_id'=>$term_id ) ); ?>
			</main>
		</div>
	</div>
	<?php
}

/** Type list shared by AI/PDF form. */
function qalam_070_ai_question_types() {
	return array(
		'true_false'=>'صح / خطأ','single_choice'=>'اختيار واحد','multiple_choice'=>'اختيارات متعددة','open_ended'=>'مقالي','fill_in_the_blank'=>'أكمل الفراغات','short_answer'=>'إجابة قصيرة','matching'=>'توصيل','image_matching'=>'مطابقة بالصور','image_answering'=>'إجابة بالصورة','ordering'=>'ترتيب','draw_image'=>'تحديد على الصورة','scale'=>'مدى','pin_image'=>'تحديد نقطة','coordinates'=>'رسم بياني','puzzle'=>'لغز',
	);
}

/** Human label/class for per-question difficulty. */
function qalam_071_difficulty_data( string $difficulty ): array {
	$map = array(
		'easy'   => array( 'label' => 'سهل', 'class' => 'is-easy' ),
		'medium' => array( 'label' => 'متوسط', 'class' => 'is-medium' ),
		'hard'   => array( 'label' => 'صعب', 'class' => 'is-hard' ),
	);
	return $map[ $difficulty ] ?? array( 'label' => 'غير محدد', 'class' => 'is-unknown' );
}

function qalam_071_render_difficulty_badge( string $difficulty ): void {
	$data = qalam_071_difficulty_data( $difficulty );
	echo '<span class="qalam-difficulty-badge ' . esc_attr( $data['class'] ) . '">' . esc_html( $data['label'] ) . '</span>';
}

/** Shared AI/PDF generator UI. */
function qalam_070_render_ai_generator( $args = array() ) {
	if ( function_exists( 'qalam_feature_enabled' ) && ! qalam_feature_enabled( 'ai_question_generation' ) ) { return; }
	$args = wp_parse_args($args,array('target'=>'bank','term_id'=>0,'quiz_id'=>0));
	$pdf_enabled = ! function_exists( 'qalam_feature_enabled' ) || qalam_feature_enabled( 'pdf_question_generation' );
	$qalam_cloud_credits = function_exists( 'qalam_290_cached_manifest' ) ? qalam_290_cached_manifest() : null;
	$qalam_cloud_credits = is_array( $qalam_cloud_credits ) && is_array( $qalam_cloud_credits['ai_credits'] ?? null ) ? $qalam_cloud_credits['ai_credits'] : null;
	$provider='';$model='';
	if(class_exists('\\TutorPro\\TutorAI\\Helper')){try{$cfg=\TutorPro\TutorAI\Helper::get_ai_provider_config();$provider=$cfg['provider'];$model=$cfg['model'];}catch(\Throwable $e){}}
	?>
	<section id="qalam-ai-question-generator" class="qalam-050-panel qalam-ai-generator">
		<div class="qalam-050-section-head"><div><h2>✨ إنشاء أسئلة بالذكاء الاصطناعي أو من PDF</h2><p>المزود الحالي: <strong><?php echo esc_html($provider?:'غير مفعّل'); ?></strong><?php if($model):?> — <?php echo esc_html($model); ?><?php endif;?>. حدد عدد كل نوع، ومجموع الأعداد هو عدد الأسئلة النهائي.</p><?php if ( $qalam_cloud_credits ) : $qalam_limit = absint( $qalam_cloud_credits['limit'] ?? 0 ); $qalam_used = absint( $qalam_cloud_credits['used'] ?? 0 ); $qalam_remaining = absint( $qalam_cloud_credits['remaining'] ?? 0 ); $qalam_percent = $qalam_limit ? min( 100, round( ( $qalam_remaining / $qalam_limit ) * 100 ) ) : 0; ?><div class="qalam-ai-credit-card" role="status" aria-label="رصيد الذكاء الاصطناعي"><span><small>رصيد AI المتبقي</small><strong><?php echo esc_html( number_format_i18n( $qalam_remaining ) ); ?> سؤال</strong></span><span><small>المستخدم</small><strong><?php echo esc_html( number_format_i18n( $qalam_used ) ); ?> من <?php echo esc_html( number_format_i18n( $qalam_limit ) ); ?></strong></span><div class="qalam-ai-credit-track" aria-hidden="true"><i style="width:<?php echo esc_attr( $qalam_percent ); ?>%"></i></div><?php if ( 0 === $qalam_remaining ) : ?><p>الرصيد انتهى. تواصل مع إدارة المنصة لشحن رصيد جديد.</p><?php endif; ?></div><?php endif; ?></div></div>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="qalam-ai-question-form">
			<input type="hidden" name="action" value="qalam_070_generate_questions"><?php wp_nonce_field('qalam_070_generate_questions','qalam_070_ai_nonce'); ?>
			<input type="hidden" name="target" value="<?php echo esc_attr($args['target']); ?>"><input type="hidden" name="quiz_id" value="<?php echo esc_attr((int)$args['quiz_id']); ?>"><input type="hidden" name="term_id" value="<?php echo esc_attr((int)$args['term_id']); ?>">
			<div class="qalam-ai-source-grid"><label><span>طريقة الإنشاء</span><select name="source_mode" data-qalam-source-mode><option value="prompt">إنشاء من تعليمات/موضوع</option><?php if($pdf_enabled):?><option value="pdf_generate">إنشاء أسئلة جديدة من PDF</option><option value="pdf_extract">استخراج الأسئلة الموجودة في PDF كما هي</option><?php endif;?></select></label><label><span>توزيع مستوى الصعوبة</span><select name="difficulty"><option value="mixed" selected>متنوع تلقائيًا</option><option value="easy">سهل فقط</option><option value="medium">متوسط فقط</option><option value="hard">صعب فقط</option></select><small>في الوضع المتنوع، كل سؤال بياخد مستوى صعوبة مستقل ويظهر كـ Label في بنك الأسئلة.</small></label></div>
			<label class="qalam-ai-prompt"><span>الموضوع أو تعليمات إضافية</span><textarea name="instructions" rows="4" placeholder="مثال: أسئلة في الفيزياء على قانون أوم، بالعربي، مع الحفاظ على الوحدات والرموز..."></textarea></label>
			<?php if($pdf_enabled):?><label class="qalam-ai-pdf-field" data-qalam-pdf-field hidden><span>ملف PDF</span><input type="file" name="pdf_file" accept="application/pdf"><small>الحد الحالي 15MB. Google AI Studio وOpenAI وOpenRouter مدعومين مباشرة للـPDF في هذه النسخة.</small></label><?php endif;?>
			<h3>عدد كل نوع سؤال</h3><div class="qalam-question-count-grid"><?php foreach(qalam_070_ai_question_types() as $slug=>$label):?><label><span><?php echo esc_html($label); ?></span><input type="number" name="type_counts[<?php echo esc_attr($slug); ?>]" min="0" max="50" value="0" data-qalam-question-count></label><?php endforeach;?></div>
			<div class="qalam-ai-total">إجمالي الأسئلة: <strong data-qalam-question-total>0</strong></div>
			<button type="submit" class="button button-primary qalam-050-primary">إنشاء وحفظ الأسئلة</button>
		</form>
	</section>
	<?php
}

/** Build a strict JSON prompt for all Tutor question types. */
function qalam_070_ai_prompt( array $counts, string $mode, string $difficulty, string $instructions ): string {
	$requested=array();foreach($counts as $type=>$count){if($count>0){$requested[$type]=$count;}}
	$types=json_encode($requested,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	$extract = 'pdf_extract' === $mode;
	return "أنت محرك بنك أسئلة في Qalam LMS. أخرج JSON صالح فقط بدون Markdown.\n".
		"المطلوب حسب النوع والعدد بالضبط: {$types}.\n".
		( 'mixed' === $difficulty
			? "وزّع الصعوبة على الأسئلة بشكل منطقي بين easy وmedium وhard حسب عمق كل سؤال، وحاول يكون التوزيع متنوعًا ومتوازنًا. لازم كل سؤال يحتوي difficulty مستقل.\n"
			: "كل الأسئلة المطلوبة مستوى صعوبتها {$difficulty}، ولازم كل سؤال يحتوي difficulty بالقيمة نفسها.\n" ).
		($extract?"استخرج الأسئلة الموجودة في الملف كما هي قدر الإمكان، ولا تعيد صياغتها ولا تخترع سؤالًا غير موجود. قيّم صعوبة كل سؤال بشكل مستقل لو الوضع mixed. إذا العدد المطلوب لنوع أكبر من الموجود، أرجع الموجود فقط.\n":"أنشئ أسئلة جديدة اعتمادًا فقط على المصدر/التعليمات المقدمة.\n").
		"التعليمات الإضافية: {$instructions}\n".
		"الأنواع المسموحة: true_false,single_choice,multiple_choice,open_ended,fill_in_the_blank,short_answer,matching,image_matching,image_answering,ordering,draw_image,scale,pin_image,coordinates,puzzle. اختيار واحد يعني إجابة صحيحة واحدة فقط. اختيارات متعددة يعني إجابتين صحيحتين أو أكثر.\n".
		"صيغة JSON: {\"questions\":[{\"question_type\":\"...\",\"question_title\":\"...\",\"question_description\":\"\",\"question_mark\":1,\"difficulty\":\"easy|medium|hard\",\"answers\":[{\"text\":\"\",\"correct\":true,\"match\":\"\"}],\"blanks\":[\"\"],\"pairs\":[{\"left\":\"\",\"right\":\"\"}],\"scale\":{\"min\":0,\"max\":100,\"step\":1,\"value\":50},\"source_page\":1,\"image_bbox\":[0,0,0,0]}]}.\n".
		"image_bbox إحداثيات الصورة التابعة للسؤال على الصفحة بنظام 0..1000 بالشكل [x1,y1,x2,y2]، واتركها [0,0,0,0] إن لم توجد صورة. سؤال fill_in_the_blank يجب أن يحتوي {dash} مكان كل فراغ وblanks بالترتيب. matching يستخدم pairs. ordering يستخدم answers بالترتيب الصحيح. scale يستخدم scale. للأنواع الرسومية/Pin/Graph/Puzzle اكتب السؤال بدقة وحدد الصورة/الصفحة إن وجدت؛ سيتم فتحه للمراجعة في محرر قلم.";
}

/** Generate text JSON using the currently configured OpenAI-compatible chat provider. */
function qalam_070_generate_text_questions( string $prompt ): array {
	if(!class_exists('\\TutorPro\\TutorAI\\Helper')){throw new RuntimeException('ميزة الذكاء الاصطناعي غير متاحة. فعّل Qalam Pro.');}
	$client=\TutorPro\TutorAI\Helper::get_openai_client();
	$response=$client->chat()->create(\TutorPro\TutorAI\Helper::create_openai_chat_input(array(array('role'=>'user','content'=>$prompt)),array('temperature'=>0.25)));
	$data=\TutorPro\TutorAI\Helper::check_openai_response($response);
	$content='';if(!empty($data->choices[0]->message->content)){$content=(string)$data->choices[0]->message->content;}
	return qalam_070_decode_ai_json($content);
}

/** Call provider-native PDF input APIs. */
function qalam_070_generate_pdf_questions( string $path, string $filename, string $prompt ): array {
	if(!class_exists('\\TutorPro\\TutorAI\\Helper')){throw new RuntimeException('ميزة الذكاء الاصطناعي غير متاحة.');}
	$cfg=\TutorPro\TutorAI\Helper::get_ai_provider_config();$provider=$cfg['provider'];$key=$cfg['api_key'];$model=$cfg['model'];
	if(!$key){throw new RuntimeException('أضف مفتاح API وفعّل المزود الأول.');}
	$bytes=file_get_contents($path);if(false===$bytes){throw new RuntimeException('تعذر قراءة ملف PDF.');}
	$b64=base64_encode($bytes);$headers=array('Content-Type'=>'application/json');$body=array();$endpoint='';
	if('google'===$provider){
		$google_model=(string)preg_replace('#^models/#i','',trim($model));
		$endpoint='https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($google_model).':generateContent?key='.rawurlencode($key);
		$body=array('contents'=>array(array('parts'=>array(array('inline_data'=>array('mime_type'=>'application/pdf','data'=>$b64)),array('text'=>$prompt)))),'generationConfig'=>array('temperature'=>0.2,'responseMimeType'=>'application/json'));
	}elseif('openrouter'===$provider){
		$endpoint='https://openrouter.ai/api/v1/chat/completions';$headers['Authorization']='Bearer '.$key;$headers['HTTP-Referer']=home_url('/');$headers['X-OpenRouter-Title']=wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES)?:'Qalam LMS';
		$body=array('model'=>$model,'temperature'=>0.2,'messages'=>array(array('role'=>'user','content'=>array(array('type'=>'text','text'=>$prompt),array('type'=>'file','file'=>array('filename'=>$filename,'file_data'=>'data:application/pdf;base64,'.$b64))))));
	}elseif('openai'===$provider){
		$endpoint='https://api.openai.com/v1/responses';$headers['Authorization']='Bearer '.$key;
		$body=array('model'=>$model,'input'=>array(array('role'=>'user','content'=>array(array('type'=>'input_file','filename'=>$filename,'file_data'=>'data:application/pdf;base64,'.$b64),array('type'=>'input_text','text'=>$prompt)))));
	}else{
		throw new RuntimeException('المزود الحالي لا يدعم PDF مباشرة في Qalam حاليًا. اختار OpenAI أو OpenRouter أو Google AI Studio، أو استخدم الإنشاء النصي.');
	}
	$response=wp_safe_remote_post($endpoint,array('timeout'=>90,'redirection'=>2,'sslverify'=>true,'headers'=>$headers,'body'=>wp_json_encode($body),'data_format'=>'body'));
	if(is_wp_error($response)){throw new RuntimeException('تعذر الاتصال بمزود الذكاء الاصطناعي: '.$response->get_error_message());}
	$status=(int)wp_remote_retrieve_response_code($response);$raw=(string)wp_remote_retrieve_body($response);$json=json_decode($raw,true);
	if($status<200||$status>=300){$msg=is_array($json)?($json['error']['message']??$json['message']??'HTTP '.$status):'HTTP '.$status;throw new RuntimeException('فشل تحليل PDF: '.sanitize_text_field((string)$msg));}
	$content='';
	if('google'===$provider){$content=(string)($json['candidates'][0]['content']['parts'][0]['text']??'');}
	elseif('openrouter'===$provider){$content=(string)($json['choices'][0]['message']['content']??'');}
	else{
		if(isset($json['output_text'])){$content=(string)$json['output_text'];}
		if(!$content&&!empty($json['output'])&&is_array($json['output'])){foreach($json['output'] as $item){foreach((array)($item['content']??array()) as $part){if(isset($part['text'])){$content.=(string)$part['text'];}}}}
	}
	return qalam_070_decode_ai_json($content);
}

/** Decode model output safely. */
function qalam_070_decode_ai_json( string $content ): array {
	$content=trim($content);$content=preg_replace('/^```(?:json)?\s*/i','',$content);$content=preg_replace('/\s*```$/','',$content);$data=json_decode($content,true);
	if(!is_array($data)){throw new RuntimeException('المزود رجّع نتيجة غير قابلة للقراءة كـ JSON. جرّب موديل أقوى أو قلل عدد الأسئلة.');}
	$questions=isset($data['questions'])&&is_array($data['questions'])?$data['questions']:$data;
	if(!is_array($questions)){throw new RuntimeException('لم يتم العثور على أسئلة في رد المزود.');}
	return array_values(array_filter($questions,'is_array'));
}

/** Normalize AI item to Tutor native Content Bank question payload. */
function qalam_070_native_question_payload( array $item ): array {
	$allowed=array_keys(qalam_070_ai_question_types());$type=sanitize_key((string)($item['question_type']??''));if(!in_array($type,$allowed,true)){$type='multiple_choice';}
	$title=sanitize_text_field((string)($item['question_title']??$item['title']??''));if(!$title){$title='سؤال بدون عنوان';}
	$description=wp_kses_post((string)($item['question_description']??$item['description']??''));
	$settings=array('answer_required'=>'0','show_question_mark'=>'0','randomize_question'=>'0','question_mark'=>max(1,absint($item['question_mark']??1)),'question_type'=>$type);
	$difficulty=sanitize_key((string)($item['difficulty']??''));if(in_array($difficulty,array('easy','medium','hard'),true)){$settings['qalam_difficulty']=$difficulty;}
	if('single_choice'===$type){$settings['has_multiple_correct_answer']='0';}
	if('multiple_choice'===$type){$settings['has_multiple_correct_answer']='1';}
	if('matching'===$type){$settings['is_image_matching']='0';}
	if('image_matching'===$type){$settings['is_image_matching']='1'; $type='matching'; $settings['question_type']='matching';}
	if('draw_image'===$type){$settings['draw_image_threshold_percent']=70;}
	if('coordinates'===$type){$settings['coordinates_axis_range']=10;}
	if('puzzle'===$type){$settings['puzzle_grid_size']=4;}
	$answers=array();$i=0;
	$add=function($text='',$correct=0,$match='',$format='text',$image_id=null)use(&$answers,&$i,$type){$i++;$answers[]=array('_data_status'=>'new','answer_title'=>sanitize_text_field((string)$text),'is_correct'=>$correct?1:0,'answer_order'=>$i,'answer_two_gap_match'=>is_string($match)?$match:wp_json_encode($match),'answer_view_format'=>$format,'image_id'=>$image_id,'belongs_question_type'=>$type);};
	if('true_false'===$type){$correct=$item['correct']??null;if(null===$correct&&isset($item['answers'])&&is_array($item['answers'])){foreach($item['answers'] as $a){if(!empty($a['correct'])){$correct=strtolower((string)($a['text']??''));}}}$true=in_array($correct,array(true,1,'1','true','صح','True'),true);$add('True',$true);$add('False',!$true);}
	elseif('fill_in_the_blank'===$type){$blanks=array_values(array_filter(array_map('sanitize_text_field',(array)($item['blanks']??array()))));$add('',1,implode('|',$blanks),'text');}
	elseif('matching'===$type){foreach((array)($item['pairs']??array()) as $p){if(is_array($p)){$add($p['left']??'',1,$p['right']??'','text');}}}
	elseif(in_array($type,array('draw_image','pin_image','coordinates','puzzle'),true)){$add('',1,'',$type);}
	elseif('scale'===$type){$s=is_array($item['scale']??null)?$item['scale']:array();$min=(float)($s['min']??0);$max=(float)($s['max']??100);$step=(float)($s['step']??1);$value=(float)($s['value']??(($min+$max)/2));$payload=array('value'=>$value,'config'=>array('min'=>$min,'max'=>$max,'step'=>$step,'defaultValue'=>$value,'pxPerUnit'=>10,'labelEvery'=>10,'minorTickEvery'=>5,'precision'=>0));$add('',1,wp_json_encode($payload),'scale');}
	else{foreach((array)($item['answers']??array()) as $a){if(is_string($a)){$add($a,'ordering'===$type);}elseif(is_array($a)){$add($a['text']??$a['answer_title']??'',!empty($a['correct'])||'ordering'===$type,$a['match']??'',$a['answer_view_format']??'text',$a['image_id']??null);}}}
	return array('question_title'=>$title,'question_description'=>$description,'question_type'=>$type,'question_mark'=>max(1,absint($item['question_mark']??1)),'question_settings'=>$settings,'question_answers'=>$answers,'deleted_answer_ids'=>array());
}

/** Save a native Content Bank question using Tutor QuizBuilder persistence. */
function qalam_070_save_content_bank_question( array $payload, int $collection_id, int $term_id=0, array $source=array() ): int {
	global $wpdb;$builder=new \TUTOR\QuizBuilder(false);$qdata=$builder->prepare_question_data(null,$payload);$author_id=absint($source['author_id']??0);if(!$author_id){$author_id=absint(get_post_field('post_author',$collection_id));}if(!$author_id){$author_id=get_current_user_id();}$content_id=wp_insert_post(array('post_type'=>'cb-question','post_status'=>'publish','post_parent'=>$collection_id,'post_author'=>$author_id,'post_title'=>$qdata['question_title'],'post_content'=>$qdata['question_description']),true);if(is_wp_error($content_id)){throw new RuntimeException($content_id->get_error_message());}
	$qdata['content_id']=$content_id;$wpdb->insert($wpdb->prefix.'tutor_quiz_questions',$qdata);$question_id=(int)$wpdb->insert_id;if(!$question_id){wp_delete_post($content_id,true);throw new RuntimeException('تعذر حفظ السؤال في قاعدة البيانات.');}
	$builder->save_question_answers($question_id,$qdata['question_type'],$payload['question_answers']??array());
	if($term_id){wp_set_object_terms($content_id,array($term_id),QALAM_QUESTION_CATEGORY_TAX,false);}
	if($source){update_post_meta($content_id,QALAM_QBANK_SOURCE_META,$source);}
	$difficulty=sanitize_key((string)($source['difficulty']??''));
	if(in_array($difficulty,array('easy','medium','hard'),true)){update_post_meta($content_id,QALAM_QBANK_DIFFICULTY_META,$difficulty);}
	return (int)$content_id;
}

/** Render/crop an image area from PDF and append it to the question description. */
function qalam_070_attach_pdf_crop( array &$payload, string $pdf_path, array $item, string $filename ): void {
	$bbox=$item['image_bbox']??array();$page=max(1,absint($item['source_page']??0));if(count((array)$bbox)!==4||!$page||!class_exists('Imagick')){return;}
	$vals=array_map('floatval',$bbox);list($x1,$y1,$x2,$y2)=$vals;if($x2<=$x1||$y2<=$y1||$x2<=0||$y2<=0){return;}
	try{$im=new \Imagick();$im->setResolution(160,160);$im->readImage($pdf_path.'['.($page-1).']');$im->setImageFormat('png');$w=$im->getImageWidth();$h=$im->getImageHeight();$px1=(int)round(max(0,min(1000,$x1))/1000*$w);$py1=(int)round(max(0,min(1000,$y1))/1000*$h);$px2=(int)round(max(0,min(1000,$x2))/1000*$w);$py2=(int)round(max(0,min(1000,$y2))/1000*$h);$cw=max(1,$px2-$px1);$ch=max(1,$py2-$py1);$mx=max(32,(int)round($cw*.08));$my=max(32,(int)round($ch*.08));$cx=max(0,$px1-$mx);$cy=max(0,$py1-$my);$cw=min($w-$cx,$cw+2*$mx);$ch=min($h-$cy,$ch+2*$my);$im->cropImage($cw,$ch,$cx,$cy);$uploads=wp_upload_dir();if(!empty($uploads['error'])){return;}$dir=trailingslashit($uploads['basedir']).'qalam-question-crops';wp_mkdir_p($dir);$safe=sanitize_file_name(pathinfo($filename,PATHINFO_FILENAME).'-p'.$page.'-'.wp_generate_password(8,false,false).'.png');$dest=trailingslashit($dir).$safe;$im->writeImage($dest);$im->clear();$type=wp_check_filetype($safe,null);$att=wp_insert_attachment(array('post_mime_type'=>$type['type']?:'image/png','post_title'=>sanitize_text_field($payload['question_title'].' — صورة السؤال'),'post_status'=>'inherit'),$dest);if($att&&!is_wp_error($att)){require_once ABSPATH.'wp-admin/includes/image.php';$meta=wp_generate_attachment_metadata($att,$dest);wp_update_attachment_metadata($att,$meta);$url=wp_get_attachment_url($att);if($url){$payload['question_description'].='<p class="qalam-question-source-image"><img src="'.esc_url($url).'" alt="صورة السؤال"></p>';}}}catch(\Throwable $e){/* Keep question; crop remains reviewable manually. */}
}

/** Generate/save AI or PDF questions into Question Bank, optionally copy to a standalone quiz. */
function qalam_070_generate_questions_action() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) { wp_die( 'غير مسموح.' ); }
	check_admin_referer( 'qalam_070_generate_questions', 'qalam_070_ai_nonce' );
	$reservation_id = '';
	$reservation_committed = false;
	$created_ids = array();

	$requested = array();
	foreach ( qalam_070_ai_question_types() as $slug => $label ) {
		$requested[ $slug ] = min( 50, max( 0, absint( $_POST['type_counts'][ $slug ] ?? 0 ) ) );
	}
	$total        = array_sum( $requested );
	$mode         = sanitize_key( (string) ( $_POST['source_mode'] ?? 'prompt' ) );
	$difficulty   = sanitize_key( (string) ( $_POST['difficulty'] ?? 'mixed' ) );
	if ( ! in_array( $difficulty, array( 'mixed', 'easy', 'medium', 'hard' ), true ) ) { $difficulty = 'mixed'; }
	$instructions = sanitize_textarea_field( wp_unslash( $_POST['instructions'] ?? '' ) );
	$term_id      = absint( $_POST['term_id'] ?? 0 );
	$target       = sanitize_key( (string) ( $_POST['target'] ?? 'bank' ) );
	$quiz_id      = absint( $_POST['quiz_id'] ?? 0 );
	$redirect     = 'quiz' === $target && $quiz_id
		? admin_url( 'admin.php?page=qalam-quiz-builder&quiz_id=' . $quiz_id )
		: admin_url( 'admin.php?page=qalam-question-bank' . ( $term_id ? '&question_category=' . $term_id : '' ) );

	try {
		if ( $total < 1 ) { throw new RuntimeException( 'حدد عدد سؤال واحد على الأقل.' ); }
		if ( $total > 60 ) { throw new RuntimeException( 'الحد الأقصى في العملية الواحدة 60 سؤال.' ); }

		if ( function_exists( 'qalam_290_ai_reserve' ) && function_exists( 'qalam_290_state' ) && ! empty( qalam_290_state()['activation_id'] ) ) {
			$reservation_key = 'qalam-ai-' . wp_generate_uuid4();
			$reservation = qalam_290_ai_reserve( $total, $reservation_key );
			if ( is_wp_error( $reservation ) ) { throw new RuntimeException( 'تعذر حجز رصيد الذكاء الاصطناعي: ' . $reservation->get_error_message() ); }
			$reservation_id = sanitize_text_field( $reservation['reservation_id'] ?? '' );
			if ( '' === $reservation_id ) { throw new RuntimeException( 'استجابة رصيد الذكاء الاصطناعي غير مكتملة.' ); }
		}

		$collection = qalam_070_question_bank_collection_id();
		if ( ! $collection ) { throw new RuntimeException( 'فعّل ملحق بنك المحتوى الأول.' ); }

		$pdf_path = '';
		$pdf_name = '';
		if ( 0 === strpos( $mode, 'pdf_' ) ) {
			if ( empty( $_FILES['pdf_file']['tmp_name'] ) ) { throw new RuntimeException( 'اختار ملف PDF.' ); }
			$file = $_FILES['pdf_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( (int) $file['size'] > 15 * 1024 * 1024 ) { throw new RuntimeException( 'حجم PDF أكبر من 15MB.' ); }
			$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array( 'pdf' => 'application/pdf' ) );
			if ( 'pdf' !== ( $check['ext'] ?? '' ) ) { throw new RuntimeException( 'الملف لازم يكون PDF صالح.' ); }
			$pdf_path = $file['tmp_name'];
			$pdf_name = sanitize_file_name( $file['name'] );
		}

		/*
		 * Generate into memory first. For creation modes we retry only the missing
		 * question types so the final saved set matches the requested per-type
		 * counts. Extraction mode never invents missing questions.
		 */
		$remaining = $requested;
		$accepted  = array();
		$round     = 0;
		$max_rounds = 'pdf_extract' === $mode ? 1 : 3;

		while ( array_sum( $remaining ) > 0 && $round < $max_rounds ) {
			++$round;
			$round_instructions = $instructions;
			if ( $round > 1 ) {
				$round_instructions .= "\nهذه محاولة استكمال فقط. أنشئ الأنواع والأعداد الناقصة المحددة ولا تكرر أسئلة المحاولات السابقة.";
			}
			$prompt = qalam_070_ai_prompt( $remaining, $mode, $difficulty, $round_instructions );
			$items  = $pdf_path
				? qalam_070_generate_pdf_questions( $pdf_path, $pdf_name, $prompt )
				: qalam_070_generate_text_questions( $prompt );

			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) { continue; }
				$type = sanitize_key( (string) ( $item['question_type'] ?? '' ) );
				if ( empty( $remaining[ $type ] ) ) { continue; }
				$item_difficulty = sanitize_key( (string) ( $item['difficulty'] ?? '' ) );
				if ( 'mixed' !== $difficulty ) {
					$item_difficulty = $difficulty;
				} elseif ( ! in_array( $item_difficulty, array( 'easy', 'medium', 'hard' ), true ) ) {
					$fallback_levels = array( 'easy', 'medium', 'hard' );
					$item_difficulty = $fallback_levels[ count( $accepted ) % 3 ];
				}
				$item['difficulty'] = $item_difficulty;
				$accepted[] = $item;
				--$remaining[ $type ];
			}
		}

		if ( ! $accepted ) {
			throw new RuntimeException( 'لم يتم إنشاء أي سؤال صالح. راجع الموديل والتعليمات.' );
		}
		if ( 'pdf_extract' !== $mode && array_sum( $remaining ) > 0 ) {
			$missing = array();
			$labels = qalam_070_ai_question_types();
			foreach ( $remaining as $type => $count ) {
				if ( $count > 0 ) { $missing[] = ( $labels[ $type ] ?? $type ) . ': ' . $count; }
			}
			throw new RuntimeException( 'الموديل ما التزمش بالأعداد المطلوبة بعد 3 محاولات. الناقص: ' . implode( '، ', $missing ) . '. جرّب موديل أقوى أو عدد أقل.' );
		}

		$provider    = '';
		if ( class_exists( '\\TutorPro\\TutorAI\\Helper' ) ) {
			try {
				$provider_cfg = \TutorPro\TutorAI\Helper::get_ai_provider_config();
				$provider = sanitize_key( (string) ( $provider_cfg['provider'] ?? '' ) );
			} catch ( \Throwable $e ) {
				$provider = '';
			}
		}

		foreach ( $accepted as $item ) {
			$payload = qalam_070_native_question_payload( $item );
			if ( $pdf_path ) { qalam_070_attach_pdf_crop( $payload, $pdf_path, $item, $pdf_name ); }
			$created_ids[] = qalam_070_save_content_bank_question(
				$payload,
				$collection,
				$term_id,
				array(
					'mode'        => $mode,
					'provider'    => $provider,
					'source_page' => absint( $item['source_page'] ?? 0 ),
					'pdf'         => $pdf_name,
					'difficulty'  => sanitize_key( (string) ( $item['difficulty'] ?? '' ) ),
				)
			);
		}

		if ( 'quiz' === $target && $quiz_id ) {
			qalam_070_copy_content_questions_to_quiz( $quiz_id, $created_ids );
		}

		if ( $reservation_id && function_exists( 'qalam_290_ai_commit' ) ) {
			$committed = qalam_290_ai_commit( $reservation_id, count( $created_ids ) );
			if ( is_wp_error( $committed ) ) { throw new RuntimeException( 'تعذر اعتماد خصم رصيد الذكاء الاصطناعي: ' . $committed->get_error_message() ); }
			$reservation_committed = true;
		}

		$args = array( 'qalam_created' => count( $created_ids ) );
		if ( 'pdf_extract' === $mode && array_sum( $remaining ) > 0 ) {
			$args['qalam_notice'] = rawurlencode( 'تم استخراج الموجود فعلًا في الملف فقط؛ بعض الأعداد المطلوبة لم تكن موجودة في الـPDF.' );
		}
		wp_safe_redirect( add_query_arg( $args, $redirect ) );
		exit;
	} catch ( \Throwable $e ) {
		if ( $reservation_id && ! $reservation_committed && function_exists( 'qalam_290_ai_release' ) ) { qalam_290_ai_release( $reservation_id ); }
		if ( $created_ids && ! $reservation_committed ) { foreach ( array_unique( array_map( 'absint', $created_ids ) ) as $created_id ) { if ( $created_id ) { wp_delete_post( $created_id, true ); } } }
		wp_safe_redirect( add_query_arg( 'qalam_error', rawurlencode( $e->getMessage() ), $redirect ) );
		exit;
	}
}
add_action( 'admin_post_qalam_070_generate_questions', 'qalam_070_generate_questions_action' );

/** Fetch native Content Bank question + answers and copy it into a quiz. */
function qalam_070_copy_content_questions_to_quiz( int $quiz_id, array $content_ids ): int {
	if(!$quiz_id||!$content_ids){return 0;}global $wpdb;$builder=new \TUTOR\QuizBuilder(false);$questions=array();foreach(array_unique(array_map('absint',$content_ids)) as $content_id){$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutor_quiz_questions WHERE content_id=%d LIMIT 1",$content_id),ARRAY_A);if(!$row)continue;$answers=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutor_quiz_question_answers WHERE belongs_question_id=%d ORDER BY answer_order ASC",$row['question_id']),ARRAY_A);foreach($answers as &$a){$a['_data_status']='new';unset($a['answer_id']);}unset($a);$settings=maybe_unserialize($row['question_settings']);$questions[]=array('_data_status'=>'new','is_cb_question'=>1,'question_id'=>(int)$row['question_id'],'question_title'=>$row['question_title'],'question_description'=>$row['question_description'],'question_type'=>$row['question_type'],'question_mark'=>$row['question_mark'],'question_settings'=>is_array($settings)?$settings:array(),'question_answers'=>$answers);}
	if($questions){$builder->save_questions($quiz_id,$questions);}return count($questions);
}

/** Standalone general quiz builder list/editor, never opens Course Builder for general quizzes. */
function qalam_070_render_quiz_builder() {
	if(!current_user_can('manage_tutor_instructor')){wp_die('غير مسموح.');}$quiz_id=isset($_GET['quiz_id'])?absint($_GET['quiz_id']):0;if($quiz_id){qalam_070_render_general_quiz_editor($quiz_id);return;}
	$quizzes=get_posts(array('post_type'=>tutor()->quiz_post_type,'post_status'=>array('publish','draft','private'),'posts_per_page'=>100,'meta_key'=>QALAM_GENERAL_QUIZ_META,'meta_value'=>'1','orderby'=>'date','order'=>'DESC'));$error=isset($_GET['qalam_error'])?sanitize_text_field(wp_unslash($_GET['qalam_error'])):'';
	?><div class="wrap qalam-050-wrap qalam-060-wrap qalam-070-wrap" dir="rtl"><div class="qalam-050-hero"><div><span class="qalam-050-eyebrow">Qalam LMS</span><h1>منشئ الاختبارات العامة</h1><p>اختبارات مستقلة عن الدورات في الواجهة، مع استخدام محرك قلم للمحاولات والدرجات في الخلفية.</p></div></div><?php if($error):?><div class="notice notice-error inline"><p><?php echo esc_html($error);?></p></div><?php endif;?><section class="qalam-050-panel"><h2>إنشاء اختبار عام جديد</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="qalam-050-form"><input type="hidden" name="action" value="qalam_070_create_general_quiz"><?php wp_nonce_field('qalam_070_create_general_quiz','qalam_quiz_nonce');?><label class="qalam-050-grow"><span>اسم الاختبار</span><input type="text" name="quiz_title" required placeholder="مثال: اختبار تجريبي شامل"></label><button class="button button-primary qalam-050-primary">إنشاء وفتح الاختبار</button></form></section><section class="qalam-050-panel"><h2>الاختبارات العامة</h2><div class="qalam-050-table-wrap"><table class="widefat striped"><thead><tr><th>الاختبار</th><th>الأسئلة</th><th>الرابط</th><th></th></tr></thead><tbody><?php if(!$quizzes):?><tr><td colspan="4">لا توجد اختبارات عامة.</td></tr><?php else:global $wpdb;foreach($quizzes as $q):$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tutor_quiz_questions WHERE quiz_id=%d",$q->ID));?><tr><td><strong><?php echo esc_html($q->post_title);?></strong></td><td><?php echo esc_html($count);?></td><td><a href="<?php echo esc_url(qalam_070_general_quiz_share_url($q->ID));?>" target="_blank">فتح رابط الطلاب</a></td><td><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$q->ID));?>">فتح المنشئ</a></td></tr><?php endforeach;endif;?></tbody></table></div></section></div><?php
}

function qalam_070_create_general_quiz(){if(!current_user_can('manage_tutor_instructor'))wp_die('غير مسموح.');check_admin_referer('qalam_070_create_general_quiz','qalam_quiz_nonce');$title=sanitize_text_field(wp_unslash($_POST['quiz_title']??''));try{if(!$title)throw new RuntimeException('اكتب اسم الاختبار.');$container=qalam_060_general_quiz_container();$builder=new \TUTOR\QuizBuilder(false);$result=$builder->save_quiz($container['topic_id'],array('post_title'=>$title,'post_content'=>'','quiz_option'=>array('passing_grade'=>50,'limit_attempts_allowed'=>'0','attempts_allowed'=>0,'time_limit'=>array('time_value'=>0,'time_type'=>'minutes')),'questions'=>array()));if(empty($result->success)||empty($result->data))throw new RuntimeException('تعذر إنشاء الاختبار.');$id=absint($result->data);update_post_meta($id,QALAM_GENERAL_QUIZ_META,'1');wp_safe_redirect(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$id));exit;}catch(\Throwable $e){wp_safe_redirect(add_query_arg('qalam_error',rawurlencode($e->getMessage()),admin_url('admin.php?page=qalam-quiz-builder')));exit;}}
add_action('admin_post_qalam_070_create_general_quiz','qalam_070_create_general_quiz');

/** Student share URL for general quiz. */
function qalam_070_general_quiz_share_url( int $quiz_id ): string {if(function_exists('qalam_140_exam_share_url')){return qalam_140_exam_share_url($quiz_id);}return add_query_arg('qalam_general_quiz',$quiz_id,home_url('/'));}

/** Route share URL through login/enrollment, then to Tutor quiz permalink. */
function qalam_070_general_quiz_share_route(){if(empty($_GET['qalam_general_quiz']))return;$quiz_id=absint($_GET['qalam_general_quiz']);if(!$quiz_id||'1'!==(string)get_post_meta($quiz_id,QALAM_GENERAL_QUIZ_META,true)){wp_die('الاختبار غير موجود.');}if(!is_user_logged_in()){auth_redirect();exit;}qalam_060_prepare_general_quiz_access();wp_safe_redirect(get_permalink($quiz_id));exit;}
add_action('template_redirect','qalam_070_general_quiz_share_route',-5);

/** Standalone quiz editor backed by Tutor tables. */
function qalam_070_render_general_quiz_editor( int $quiz_id ) {
	if('1'!==(string)get_post_meta($quiz_id,QALAM_GENERAL_QUIZ_META,true)){echo '<div class="notice notice-error"><p>ده مش اختبار عام تابع لقلم.</p></div>';return;}$quiz=get_post($quiz_id);if(!$quiz)return;global $wpdb;$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutor_quiz_questions WHERE quiz_id=%d ORDER BY question_order ASC",$quiz_id));$options=get_post_meta($quiz_id,\TUTOR\Quiz::META_QUIZ_OPTION,true);$options=is_array($options)?$options:array();$question_bank_enabled=!function_exists('qalam_feature_enabled')||qalam_feature_enabled('question_bank');$content_bank_enabled=!function_exists('qalam_feature_enabled')||qalam_feature_enabled('content_bank');$ai_enabled=!function_exists('qalam_feature_enabled')||qalam_feature_enabled('ai_question_generation');$pdf_enabled=!function_exists('qalam_feature_enabled')||qalam_feature_enabled('pdf_question_generation');$bank=$question_bank_enabled?get_posts(array('post_type'=>'cb-question','post_status'=>array('publish','draft','private'),'posts_per_page'=>200,'orderby'=>'modified','order'=>'DESC')):array();$created=isset($_GET['qalam_created'])?absint($_GET['qalam_created']):0;$notice=isset($_GET['qalam_notice'])?sanitize_text_field(wp_unslash($_GET['qalam_notice'])):'';$error=isset($_GET['qalam_error'])?sanitize_text_field(wp_unslash($_GET['qalam_error'])):'';
	?><div class="wrap qalam-050-wrap qalam-070-wrap" dir="rtl"><div class="qalam-050-hero"><div><a href="<?php echo esc_url(admin_url('admin.php?page=qalam-quiz-builder'));?>">← كل الاختبارات</a><h1><?php echo esc_html($quiz->post_title);?></h1><p>منشئ اختبار مستقل. الحاوية الداخلية المخفية مستخدمة فقط لتوافق محرك قلم ولا تظهر للطلاب كدورة.</p></div><div><a class="button button-primary" target="_blank" href="<?php echo esc_url(qalam_070_general_quiz_share_url($quiz_id));?>">مشاركة / معاينة للطلاب</a></div></div><?php if($created):?><div class="notice notice-success inline"><p>تمت إضافة <?php echo esc_html($created);?> سؤال.</p></div><?php endif;?><?php if($notice):?><div class="notice notice-warning inline"><p><?php echo esc_html($notice);?></p></div><?php endif;?><?php if($error):?><div class="notice notice-error inline"><p><?php echo esc_html($error);?></p></div><?php endif;?>
	<section class="qalam-050-panel"><h2>تفاصيل وإعدادات الاختبار</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="qalam-quiz-settings-grid"><input type="hidden" name="action" value="qalam_070_save_general_quiz"><input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id);?>"><?php wp_nonce_field('qalam_070_save_general_quiz_'.$quiz_id,'qalam_quiz_save_nonce');?><label><span>اسم الاختبار</span><input type="text" name="post_title" value="<?php echo esc_attr($quiz->post_title);?>" required></label><label class="qalam-wide"><span>الوصف</span><textarea name="post_content" rows="3"><?php echo esc_textarea($quiz->post_content);?></textarea></label><label><span>درجة النجاح %</span><input type="number" min="0" max="100" name="passing_grade" value="<?php echo esc_attr((int)($options['passing_grade']??50));?>"></label><label><span>مدة الاختبار بالدقائق (0 = بدون حد)</span><input type="number" min="0" name="time_value" value="<?php echo esc_attr((int)($options['time_limit']['time_value']??0));?>"></label><label><span>عدد المحاولات (0 = غير محدود)</span><input type="number" min="0" name="attempts_allowed" value="<?php echo esc_attr((int)($options['attempts_allowed']??0));?>"></label><button class="button button-primary qalam-050-primary">حفظ الاختبار</button></form></section>
	<section class="qalam-050-panel"><div class="qalam-050-section-head"><div><h2>أسئلة الاختبار</h2><p><?php echo esc_html( $question_bank_enabled ? ( $ai_enabled ? 'أضف أسئلة من بنك الأسئلة أو أنشئها بالذكاء الاصطناعي.' : 'أضف أسئلة من بنك الأسئلة.' ) : 'إدارة أسئلة الاختبار الحالية.' ); ?></p></div><div class="qalam-050-section-actions"><?php if($question_bank_enabled):?><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url(qalam_070_native_question_url(0));?>">+ إنشاء سؤال يدوي في البنك</a><?php endif;?><?php if($ai_enabled):?><a class="button" href="#qalam-ai-question-generator">✨ إنشاء بالذكاء الاصطناعي<?php echo $pdf_enabled?' / PDF':'';?></a><?php endif;?></div></div><table class="widefat striped"><thead><tr><th>#</th><th>السؤال</th><th>النوع</th><th>الصعوبة</th><th>الدرجة</th><th></th></tr></thead><tbody><?php if(!$rows):?><tr><td colspan="6">لا توجد أسئلة.</td></tr><?php else:$labels=qalam_060_question_type_labels();foreach($rows as $i=>$row):?><tr><td><?php echo esc_html($i+1);?></td><td><?php echo esc_html($row->question_title);?></td><td><?php echo esc_html($labels[$row->question_type]??$row->question_type);?></td><td><?php $qs=maybe_unserialize($row->question_settings); qalam_071_render_difficulty_badge( is_array($qs)?(string)($qs['qalam_difficulty']??''):'' ); ?></td><td><?php echo esc_html($row->question_mark);?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" onsubmit="return confirm('حذف السؤال من الاختبار؟');"><input type="hidden" name="action" value="qalam_070_remove_quiz_question"><input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id);?>"><input type="hidden" name="question_id" value="<?php echo esc_attr($row->question_id);?>"><?php wp_nonce_field('qalam_070_remove_quiz_question_'.$quiz_id,'qalam_remove_nonce');?><button class="button">حذف</button></form></td></tr><?php endforeach;endif;?></tbody></table></section>
	<?php if($question_bank_enabled):?><section class="qalam-050-panel"><h2>إضافة من بنك الأسئلة</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="qalam_070_import_questions_to_quiz"><input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id);?>"><?php wp_nonce_field('qalam_070_import_questions_to_quiz_'.$quiz_id,'qalam_import_nonce');?><div class="qalam-bank-picker"><?php if(!$bank):?><p>بنك الأسئلة فاضي.</p><?php else:global $wpdb;foreach($bank as $content):$r=$wpdb->get_row($wpdb->prepare("SELECT question_type FROM {$wpdb->prefix}tutor_quiz_questions WHERE content_id=%d LIMIT 1",$content->ID));?><label><input type="checkbox" name="content_ids[]" value="<?php echo esc_attr($content->ID);?>"><span><?php echo esc_html($content->post_title);?></span><small><?php echo esc_html($r?($labels[$r->question_type]??$r->question_type):'');?> · <?php $d=qalam_071_difficulty_data((string)get_post_meta($content->ID,QALAM_QBANK_DIFFICULTY_META,true)); echo esc_html($d['label']); ?></small></label><?php endforeach;endif;?></div><button class="button button-primary">إضافة المحدد للاختبار</button></form></section><?php endif;?>
	<?php if($ai_enabled){qalam_070_render_ai_generator(array('target'=>'quiz','quiz_id'=>$quiz_id,'term_id'=>0));}?></div><?php
}

function qalam_070_save_general_quiz(){if(!current_user_can('manage_tutor_instructor'))wp_die('غير مسموح.');$id=absint($_POST['quiz_id']??0);check_admin_referer('qalam_070_save_general_quiz_'.$id,'qalam_quiz_save_nonce');if('1'!==(string)get_post_meta($id,QALAM_GENERAL_QUIZ_META,true))wp_die('اختبار غير صالح.');wp_update_post(array('ID'=>$id,'post_title'=>sanitize_text_field(wp_unslash($_POST['post_title']??'')),'post_content'=>wp_kses_post(wp_unslash($_POST['post_content']??''))));$options=get_post_meta($id,\TUTOR\Quiz::META_QUIZ_OPTION,true);$options=is_array($options)?$options:array();$attempts=max(0,absint($_POST['attempts_allowed']??0));$options['passing_grade']=min(100,max(0,absint($_POST['passing_grade']??50)));$options['time_limit']=array('time_value'=>max(0,absint($_POST['time_value']??0)),'time_type'=>'minutes');$options['limit_attempts_allowed']=$attempts>0?'1':'0';$options['attempts_allowed']=$attempts;update_post_meta($id,\TUTOR\Quiz::META_QUIZ_OPTION,$options);wp_safe_redirect(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$id.'&saved=1'));exit;}
add_action('admin_post_qalam_070_save_general_quiz','qalam_070_save_general_quiz');

function qalam_070_import_questions_to_quiz(){if(!current_user_can('manage_tutor_instructor'))wp_die('غير مسموح.');$id=absint($_POST['quiz_id']??0);check_admin_referer('qalam_070_import_questions_to_quiz_'.$id,'qalam_import_nonce');$ids=array_values(array_filter(array_map('absint',(array)($_POST['content_ids']??array()))));$count=qalam_070_copy_content_questions_to_quiz($id,$ids);wp_safe_redirect(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$id.'&qalam_created='.$count));exit;}
add_action('admin_post_qalam_070_import_questions_to_quiz','qalam_070_import_questions_to_quiz');

function qalam_070_remove_quiz_question(){if(!current_user_can('manage_tutor_instructor'))wp_die('غير مسموح.');$quiz=absint($_POST['quiz_id']??0);$qid=absint($_POST['question_id']??0);check_admin_referer('qalam_070_remove_quiz_question_'.$quiz,'qalam_remove_nonce');global $wpdb;$belongs=(int)$wpdb->get_var($wpdb->prepare("SELECT quiz_id FROM {$wpdb->prefix}tutor_quiz_questions WHERE question_id=%d",$qid));if($belongs===$quiz){(new \TUTOR\QuizBuilder(false))->handle_delete(array($qid),array(),array());}wp_safe_redirect(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$quiz));exit;}
add_action('admin_post_qalam_070_remove_quiz_question','qalam_070_remove_quiz_question');

/** Assets + native Content Bank modal auto-open. */
function qalam_070_admin_assets(){if(!is_admin())return;$page=isset($_GET['page'])?sanitize_key(wp_unslash($_GET['page'])):'';$pages=array('create-course','qalam-question-bank','qalam-quiz-builder','tutor-content-bank','tutor_settings','tutor_report');if(!in_array($page,$pages,true))return;$base=plugin_dir_url(TUTOR_FILE);wp_enqueue_style('qalam-070-admin',$base.'assets/css/qalam-070-admin.css',array('qalam-060-admin'),QALAM_LMS_UI_VERSION);wp_enqueue_script('qalam-070-admin',$base.'assets/js/qalam-070-admin.js',array(),QALAM_LMS_UI_VERSION,true);wp_localize_script('qalam-070-admin','Qalam070',array('questionBankUrl'=>admin_url('admin.php?page=qalam-question-bank'),'contentBankUrl'=>admin_url('admin.php?page=tutor-content-bank')));}
add_action('admin_enqueue_scripts','qalam_070_admin_assets',PHP_INT_MAX);
