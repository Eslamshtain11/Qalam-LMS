<?php
/**
 * Qalam LMS 0.5.0 foundation.
 *
 * Arabic closure, builder launchers and the dynamic add-on registry live here so
 * Tutor's proven persistence/runtime code can remain unchanged.
 *
 * @package QalamLMS
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/release-050-translations.php';

/**
 * Egyptian-Arabic full-string catalogue additions for 0.5.0.
 *
 * IMPORTANT: keys are complete source strings. Do not add token/fragment keys here
 * merely to make DOM replacement easier. Brand names such as Zoom and Google Meet
 * intentionally remain Latin inside Arabic sentences.
 *
 * @param array $map Existing Qalam dictionary.
 * @return array
 */
function qalam_050_dictionary( $map ) {
	$map = is_array( $map ) ? $map : array();

	$phrases = array(
		// Global admin navigation.
		'Create Course' => 'إنشاء دورة',
		'Course Builder' => 'منشئ الدورات',
		'Quiz Builder' => 'منشئ الاختبارات',
		'Content Bank' => 'بنك المحتوى',
		'Categories' => 'التصنيفات',
		'Tags' => 'الوسوم',
		'Orders' => 'الطلبات',
		'Subscriptions' => 'الاشتراكات',
		'Coupons' => 'الكوبونات',
		'Students' => 'الطلاب',
		'Announcements' => 'الإعلانات',
		'Assignments' => 'الواجبات',
		'Quiz Attempts' => 'محاولات الاختبارات',
		'Q&A' => 'الأسئلة والأجوبة',
		'Q&A ' => 'الأسئلة والأجوبة ',
		'Enrollments' => 'التسجيلات',
		'Reports' => 'التقارير',
		'Gradebook' => 'سجل الدرجات',
		'Instructors' => 'المعلمون',
		'Withdraw Requests' => 'طلبات السحب',
		'Addons' => 'الملحقات',
		'Add-ons' => 'الملحقات',
		'Tools' => 'الأدوات',
		'Settings' => 'الإعدادات',
		'What\'s New' => 'ما الجديد',
		'Themes' => 'القوالب',
		'Help' => 'المساعدة',
		'Set API' => 'إعداد الربط',
		'FAQ' => 'الأسئلة الشائعة',
		'Save & Check Connection' => 'حفظ واختبار الاتصال',
		'Check & Save Connection' => 'حفظ واختبار الاتصال',
		'Save Changes' => 'حفظ التغييرات',
		'Bulk Action' => 'إجراء جماعي',
		'Filters' => 'الفلاتر',
		'No Data Found' => 'لا توجد بيانات',
		'No records found' => 'لا توجد بيانات',
		'Select Option' => 'اختر خيارًا',

		// Google Meet — complete semantic strings.
		'Set the default timezone for Google Meet' => 'حدد المنطقة الزمنية الافتراضية لاجتماعات Google Meet.',
		'Set default timezone for Google Meet' => 'حدد المنطقة الزمنية الافتراضية لاجتماعات Google Meet.',
		'Reminder Time' => 'وقت التذكير',
		'Define how long before a meeting email reminders are automatically sent to attendees.' => 'حدد قبل الاجتماع بمدة قد إيه يتم إرسال تذكير بالبريد الإلكتروني للحاضرين تلقائيًا.',
		'5 Minutes Before' => 'قبل الموعد بـ 5 دقايق',
		'15 Minutes Before' => 'قبل الموعد بـ 15 دقيقة',
		'30 Minutes Before' => 'قبل الموعد بـ 30 دقيقة',
		'Set Default Event Status' => 'حدد الحالة الافتراضية للاجتماعات الجديدة',
		'Automatically mark new Google Meet events as Confirmed or Tentative.' => 'حدد هل اجتماعات Google Meet الجديدة تتسجل كمؤكدة ولا مبدئية.',
		'Confirmed' => 'مؤكد',
		'Tentative' => 'مبدئي',
		'Send Notifications' => 'إرسال الإشعارات',
		'Specify who receives email notifications when a new event is created.' => 'حدد مين يستقبل إشعار بالبريد الإلكتروني لما يتم إنشاء اجتماع جديد.',
		'Everyone' => 'الكل',
		'External Only' => 'الخارجيون فقط',
		'No One' => 'لا أحد',
		'Transparency' => 'حالة إتاحة الوقت',
		'Determine if events block calendar availability or leave the time slot free.' => 'حدد هل الاجتماع يحجز الوقت في التقويم ولا يسيبه متاح.',
		'How do I connect Google Meet with my LMS Website?' => 'إزاي أربط Google Meet بمنصة قلم؟',
		'How do I create a Live Lesson on Qalam LMS?' => 'إزاي أنشئ حصة مباشرة على Qalam LMS؟',
		'How do I notify students about live lessons?' => 'إزاي أبعت للطلاب تنبيه عن الحصص المباشرة؟',
		'Do I need a Google account to integrate Google Meet with Qalam LMS?' => 'هل لازم يكون عندي حساب Google علشان أربط Google Meet مع Qalam LMS؟',
		'What Equipment Do I Need To Hold a Live Class?' => 'إيه الأجهزة المطلوبة علشان أعمل حصة مباشرة؟',
		'You can notify students about live lessons using Email Notifications of Qalam LMS and from the Google Meet settings on Qalam LMS frontend and backend.' => 'تقدر تبعت للطلاب تنبيهات عن الحصص المباشرة من إشعارات البريد في قلم ومن إعدادات Google Meet داخل المنصة.',
		'Yes, an active Google Account is required to configure the API credentials and to act as the primary host for the scheduled live meetings.' => 'أيوه، لازم حساب Google شغال علشان تضبط بيانات API ويكون هو المضيف الأساسي للاجتماعات المباشرة المجدولة.',
		'You will need a Microphone, a PC running Windows or Mac OS, and preferably a Webcam to effectively hold a live class.' => 'هتحتاج ميكروفون وكمبيوتر Windows أو Mac، ويفضل كاميرا ويب علشان تقدم الحصة المباشرة بشكل كويس.',
		'Setup your Google Meet Integration' => 'إعداد ربط Google Meet',
		'To integrate with Google Meet, go to this link o create your Credentials. During this process, copy the link below and paste it as your detailed guide, please refer to our documentation' => 'لربط Google Meet، أنشئ بيانات الاعتماد من Google Cloud واتبع خطوات الربط. استخدم رابط إعادة التوجيه الموضح بالأسفل عند إعداد OAuth.',
		'Drag & Drop your JSON File here, or' => 'اسحب ملف JSON هنا، أو',
		'Choose a file' => 'اختيار ملف',
		'Copy' => 'نسخ',
		'Meeting Name' => 'اسم الاجتماع',
		'Meeting Summary' => 'ملخص الاجتماع',
		'Meeting Time' => 'موعد الاجتماع',
		'Timezone' => 'المنطقة الزمنية',
		'Add Enrolled Students as Attendees' => 'إضافة الطلاب المسجلين كحاضرين',
		'Start Time' => 'وقت البداية',
		'Meeting Title' => 'عنوان الاجتماع',
		'Course:' => 'الدورة:',
		'Info' => 'التفاصيل',
		'Meeting Link' => 'رابط الاجتماع',
		'Host Email' => 'بريد المضيف',
		'Credential is not correct, refresh the page & upload again!' => 'بيانات الاعتماد غير صحيحة. حدّث الصفحة وارفع الملف مرة تانية.',
		'Do You Want to Delete This?' => 'متأكد إنك عايز تحذف ده؟',
		'Are you sure you want to delete this permanently from the site? Please confirm your choice.' => 'متأكد إنك عايز تحذف العنصر نهائيًا من الموقع؟',

		// Zoom — complete semantic strings.
		'Setup your Zoom Integration' => 'إعداد ربط Zoom',
		'Visit your Zoom account and fetch the API key to connect Zoom with your eLearning website. Go to Zoom Website.' => 'افتح حساب Zoom وجهّز بيانات الربط المطلوبة علشان توصل Zoom بمنصة قلم.',
		'Account ID' => 'معرّف الحساب',
		'Client ID' => 'معرّف العميل',
		'Client Secret' => 'الرمز السري للعميل',
		'Enter Your Zoom Account ID' => 'أدخل معرّف حساب Zoom',
		'Enter Your Zoom Client ID' => 'أدخل معرّف عميل Zoom',
		'Enter Your Zoom Client Secret' => 'أدخل الرمز السري لعميل Zoom',
		'Zoom Meeting' => 'اجتماع Zoom',
		'Enter Meeting Name' => 'اكتب اسم الاجتماع',
		'Meeting Duration' => 'مدة الاجتماع',
		'Minutes' => 'دقائق',
		'Hours' => 'ساعات',
		'Time Zone' => 'المنطقة الزمنية',
		'Auto Recording' => 'التسجيل التلقائي',
		'No Recordings' => 'بدون تسجيل',
		'Local' => 'على الجهاز',
		'Cloud' => 'على السحابة',
		'Password' => 'كلمة المرور',
		'Create a Password' => 'اكتب كلمة مرور',
		'Meeting Host' => 'مضيف الاجتماع',
		'Create Meeting' => 'إنشاء الاجتماع',
		'Update Meeting' => 'تحديث الاجتماع',
		'Meeting Title is required' => 'عنوان الاجتماع مطلوب',
		'Meeting Summary is required' => 'ملخص الاجتماع مطلوب',
		'Meeting Start Date is required' => 'تاريخ بداية الاجتماع مطلوب',
		'Meeting Start Time is required' => 'وقت بداية الاجتماع مطلوب',
		'Meeting Duration is required' => 'مدة الاجتماع مطلوبة',
		'Meeting Password is required' => 'كلمة مرور الاجتماع مطلوبة',
		'Type your summary...' => 'اكتب ملخص الاجتماع...',
		'Start Date' => 'تاريخ البداية',
		'Duration' => 'المدة',
		'Host' => 'المضيف',
		'Meeting Token' => 'رمز الاجتماع',
		'Host Mail' => 'بريد المضيف',
		'Search meeting' => 'ابحث في الاجتماعات',
		'All' => 'الكل',
		'Expired' => 'منتهي',
		'More options' => 'خيارات إضافية',

		// Add-on registry/state wording.
		'Available' => 'متاح',
		'Enabled' => 'مفعل',
		'Disabled' => 'متوقف',
		'Requires external plugin' => 'يحتاج إضافة خارجية',
		'Requires account/API setup' => 'يحتاج إعداد حساب أو API',
		'Runtime error' => 'خطأ أثناء التشغيل',
		'Unsupported in this environment' => 'غير مدعوم في البيئة الحالية',
		'Enable' => 'تفعيل',
		'Disable' => 'تعطيل',
		'Retry' => 'إعادة المحاولة',
	);

	return array_replace( $map, $phrases );
}
add_filter( 'qalam_lms_dictionary', 'qalam_050_dictionary', 50 );

/**
 * Force stable Arabic-first Qalam navigation after Core/Pro add their entries.
 *
 * @param array $menu Tutor menu definition.
 * @return array
 */
function qalam_050_admin_menu( $menu ) {
	if ( ! is_array( $menu ) ) {
		return $menu;
	}

	$labels = array(
		'courses'           => array( 'الدورات', 'الدورات' ),
		'course_builder'    => array( 'منشئ الدورات', 'منشئ الدورات' ),
		'content_bank'      => array( 'بنك المحتوى', 'بنك المحتوى' ),
		'categories'        => array( 'التصنيفات', 'التصنيفات' ),
		'tags'              => array( 'الوسوم', 'الوسوم' ),
		'orders'            => array( 'الطلبات', 'الطلبات' ),
		'subscriptions'     => array( 'الاشتراكات', 'الاشتراكات' ),
		'coupons'           => array( 'الكوبونات', 'الكوبونات' ),
		'students'          => array( 'الطلاب', 'الطلاب' ),
		'announcements'     => array( 'الإعلانات', 'الإعلانات' ),
		'assignments'       => array( 'الواجبات', 'الواجبات' ),
		'quiz_attempts'     => array( 'محاولات الاختبارات', 'محاولات الاختبارات' ),
		'q_and_a'           => array( 'الأسئلة والأجوبة', 'الأسئلة والأجوبة' ),
		'enrollments'       => array( 'التسجيلات', 'التسجيلات' ),
		'reports'           => array( 'التقارير', 'التقارير' ),
		'gradebook'         => array( 'سجل الدرجات', 'سجل الدرجات' ),
		'instructors'       => array( 'المعلمون', 'المعلمون' ),
		'withdraw_requests' => array( 'طلبات السحب', 'طلبات السحب' ),
		'addons'            => array( 'الملحقات', 'الملحقات' ),
		'tools'             => array( 'الأدوات', 'الأدوات' ),
		'settings'          => array( 'الإعدادات', 'الإعدادات' ),
		'whats_new'         => array( 'ما الجديد', 'ما الجديد' ),
		'themes'            => array( 'القوالب', 'القوالب' ),
	);

	foreach ( $menu as $group => &$items ) {
		if ( ! is_array( $items ) ) {
			continue;
		}
		foreach ( $items as $key => &$item ) {
			if ( is_array( $item ) && isset( $labels[ $key ] ) ) {
				$item['page_title'] = $labels[ $key ][0];
				$item['menu_title'] = $labels[ $key ][1];
			}
		}
		unset( $item );
	}
	unset( $items );

	// Replace the visible Core Course Builder menu with the safe Qalam launcher.
	$course_builder = array(
		'parent_slug' => 'tutor',
		'page_title'  => 'منشئ الدورات',
		'menu_title'  => 'منشئ الدورات',
		'capability'  => 'manage_tutor_instructor',
		'menu_slug'   => 'qalam-course-builder',
		'callback'    => 'qalam_050_render_course_builder',
	);

	$quiz_builder = array(
		'parent_slug' => 'tutor',
		'page_title'  => 'منشئ الاختبارات',
		'menu_title'  => 'منشئ الاختبارات',
		'capability'  => 'manage_tutor_instructor',
		'menu_slug'   => 'qalam-quiz-builder',
		'callback'    => 'qalam_050_render_quiz_builder',
	);

	$group_one = isset( $menu['group_one'] ) && is_array( $menu['group_one'] ) ? $menu['group_one'] : array();
	$new_group = array();
	foreach ( array( 'courses', 'course_builder', 'content_bank', 'categories', 'tags' ) as $key ) {
		if ( 'course_builder' === $key ) {
			$new_group['course_builder'] = $course_builder;
			$new_group['qalam_quiz_builder'] = $quiz_builder;
			continue;
		}
		if ( array_key_exists( $key, $group_one ) ) {
			$new_group[ $key ] = $group_one[ $key ];
		}
	}
	foreach ( $group_one as $key => $item ) {
		if ( ! array_key_exists( $key, $new_group ) && 'course_builder' !== $key ) {
			$new_group[ $key ] = $item;
		}
	}
	$menu['group_one'] = $new_group;

	// Replace the old React add-on marketplace callback with Qalam's full registry.
	if ( isset( $menu['group_three']['addons'] ) && is_array( $menu['group_three']['addons'] ) ) {
		$menu['group_three']['addons']['page_title'] = 'الملحقات';
		$menu['group_three']['addons']['menu_title'] = 'الملحقات';
		$menu['group_three']['addons']['callback']   = 'qalam_050_render_addons';
	}

	return $menu;
}
add_filter( 'tutor_admin_menu', 'qalam_050_admin_menu', PHP_INT_MAX );

/** Keep the real Tutor Course Builder route registered but hidden from the sidebar. */
function qalam_050_register_hidden_builder_routes() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) {
		return;
	}
	add_submenu_page(
		null,
		'منشئ الدورات',
		'منشئ الدورات',
		'manage_tutor_instructor',
		'create-course',
		array( new \TUTOR\Course( false ), 'load_course_builder' )
	);
}
add_action( 'admin_menu', 'qalam_050_register_hidden_builder_routes', PHP_INT_MAX );

/** Enqueue Qalam 0.5 admin workspace assets. */
function qalam_050_admin_assets() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( ! in_array( $page, array( 'qalam-course-builder', 'qalam-quiz-builder', 'tutor-addons' ), true ) ) {
		return;
	}

	$base = plugin_dir_url( TUTOR_FILE );
	wp_enqueue_style( 'qalam-050-admin', $base . 'assets/css/qalam-050-admin.css', array( 'qalam-lms-brand' ), QALAM_LMS_UI_VERSION );
	wp_enqueue_script( 'qalam-050-admin', $base . 'assets/js/qalam-050-admin.js', array(), QALAM_LMS_UI_VERSION, true );
	wp_localize_script(
		'qalam-050-admin',
		'Qalam050',
		array(
			'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			'nonce_key'     => tutor()->nonce,
			'tutor_nonce'   => wp_create_nonce( tutor()->nonce_action ),
			'addon_nonce'   => wp_create_nonce( 'qalam_050_addon_toggle' ),
			'creating'      => 'جاري إنشاء المسودة...',
			'create_failed' => 'تعذر إنشاء مسودة الدورة. حاول مرة تانية.',
			'toggle_failed' => 'تعذر تغيير حالة الملحق.',
		)
	);
}
add_action( 'admin_enqueue_scripts', 'qalam_050_admin_assets', PHP_INT_MAX );

/** Render the Course Builder launcher. */
function qalam_050_render_course_builder() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'tutor' ) );
	}

	$args = array(
		'post_type'      => tutor()->course_post_type,
		'post_status'    => array( 'draft', 'pending', 'publish', 'private' ),
		'posts_per_page' => 24,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'meta_query'     => array( array( 'key' => '_qalam_internal_general_quiz_course', 'compare' => 'NOT EXISTS' ) ),
	);
	if ( ! current_user_can( 'manage_tutor' ) ) {
		$args['author'] = get_current_user_id();
	}
	$courses = get_posts( $args );
	?>
	<div class="wrap qalam-050-wrap" dir="rtl">
		<div class="qalam-050-hero">
			<div>
				<span class="qalam-050-eyebrow">Qalam LMS</span>
				<h1>منشئ الدورات</h1>
				<p>ابدأ دورة جديدة أو افتح دورة موجودة في منشئ قلم، مع الحفاظ على المحرك الداخلي والتوافق الكامل مع الدروس والاختبارات.</p>
			</div>
			<button type="button" class="button button-primary qalam-050-primary" data-qalam-create-course>+ إنشاء دورة جديدة</button>
		</div>

		<div class="qalam-050-section-head">
			<div><h2>دوراتك</h2><p>آخر الدورات تعديلًا.</p></div>
			<input type="search" class="qalam-050-search" placeholder="ابحث باسم الدورة..." data-qalam-course-search>
		</div>
		<div class="qalam-050-grid" data-qalam-course-grid>
			<?php if ( empty( $courses ) ) : ?>
				<div class="qalam-050-empty"><strong>لسه مفيش دورات</strong><span>ابدأ أول دورة من الزر اللي فوق.</span></div>
			<?php else : ?>
				<?php foreach ( $courses as $course ) :
					$status_obj = get_post_status_object( $course->post_status );
					$builder_url = add_query_arg( array( 'page' => 'create-course', 'course_id' => $course->ID ), admin_url( 'admin.php' ) );
					?>
					<article class="qalam-050-card qalam-course-card" data-qalam-search-text="<?php echo esc_attr( wp_strip_all_tags( $course->post_title ) ); ?>">
						<div class="qalam-050-card-icon">د</div>
						<div class="qalam-050-card-body">
							<span class="qalam-050-status"><?php echo esc_html( $status_obj ? $status_obj->label : $course->post_status ); ?></span>
							<h3><?php echo esc_html( $course->post_title ? $course->post_title : 'دورة بدون اسم' ); ?></h3>
							<p>آخر تعديل: <?php echo esc_html( get_the_modified_date( '', $course ) ); ?></p>
						</div>
						<a class="button qalam-050-secondary" href="<?php echo esc_url( $builder_url ); ?>">فتح في المنشئ</a>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Return courses visible in the Quiz Builder workspace.
 *
 * @return WP_Post[]
 */
function qalam_050_quiz_courses() {
	$args = array(
		'post_type'      => tutor()->course_post_type,
		'post_status'    => array( 'draft', 'pending', 'publish', 'private' ),
		'posts_per_page' => 200,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);
	if ( ! current_user_can( 'manage_tutor' ) ) {
		$args['author'] = get_current_user_id();
	}
	return get_posts( $args );
}

/** Render standalone-looking workspace backed by Tutor QuizBuilder. */
function qalam_050_render_quiz_builder() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'tutor' ) );
	}

	$courses = qalam_050_quiz_courses();
	$quizzes = get_posts(
		array(
			'post_type'      => tutor()->quiz_post_type,
			'post_status'    => array( 'draft', 'pending', 'publish', 'private' ),
			'posts_per_page' => 100,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);
	$created = isset( $_GET['qalam_quiz_created'] ) ? absint( $_GET['qalam_quiz_created'] ) : 0;
	$error   = isset( $_GET['qalam_quiz_error'] ) ? sanitize_text_field( wp_unslash( $_GET['qalam_quiz_error'] ) ) : '';
	?>
	<div class="wrap qalam-050-wrap" dir="rtl">
		<div class="qalam-050-hero">
			<div>
				<span class="qalam-050-eyebrow">Qalam LMS</span>
				<h1>منشئ الاختبارات</h1>
				<p>مساحة مستقلة للوصول للاختبارات، لكنها تستخدم نفس محرك الاختبارات والجداول الداخلية علشان المحاولات والدرجات والتقارير تفضل متوافقة.</p>
			</div>
		</div>
		<?php if ( $created ) : ?><div class="notice notice-success inline"><p>تم إنشاء الاختبار بنجاح. تقدر تفتحه داخل منشئ الدورة وتضيف الأسئلة والإعدادات.</p></div><?php endif; ?>
		<?php if ( $error ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>

		<section class="qalam-050-panel">
			<div class="qalam-050-section-head"><div><h2>إنشاء اختبار جديد</h2><p>اختار الدورة والقسم واكتب اسم الاختبار.</p></div></div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qalam-050-form">
				<input type="hidden" name="action" value="qalam_050_create_quiz">
				<?php wp_nonce_field( 'qalam_050_create_quiz', 'qalam_050_nonce' ); ?>
				<label><span>الدورة</span><select name="course_id" required data-qalam-course-select><option value="">اختر الدورة</option>
					<?php foreach ( $courses as $course ) : ?><option value="<?php echo esc_attr( $course->ID ); ?>"><?php echo esc_html( $course->post_title ); ?></option><?php endforeach; ?>
				</select></label>
				<label><span>القسم / الموضوع</span><select name="topic_id" required data-qalam-topic-select><option value="">اختار الدورة الأول</option>
					<?php foreach ( $courses as $course ) :
						$topics = get_posts( array( 'post_type' => tutor()->topics_post_type, 'post_parent' => $course->ID, 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
						foreach ( $topics as $topic ) : ?>
							<option value="<?php echo esc_attr( $topic->ID ); ?>" data-course="<?php echo esc_attr( $course->ID ); ?>"><?php echo esc_html( $topic->post_title ); ?></option>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</select></label>
				<label class="qalam-050-grow"><span>اسم الاختبار</span><input type="text" name="quiz_title" required maxlength="180" placeholder="مثال: اختبار الفصل الأول"></label>
				<button type="submit" class="button button-primary qalam-050-primary">إنشاء الاختبار</button>
			</form>
		</section>

		<section class="qalam-050-panel">
			<div class="qalam-050-section-head"><div><h2>الاختبارات الحالية</h2><p>افتح الدورة الأم لتعديل الاختبار بنفس محرك الاختبارات الأصلي.</p></div></div>
			<div class="qalam-050-table-wrap"><table class="widefat striped qalam-050-table"><thead><tr><th>الاختبار</th><th>الدورة</th><th>الحالة</th><th></th></tr></thead><tbody>
			<?php if ( empty( $quizzes ) ) : ?><tr><td colspan="4">لا توجد اختبارات حاليًا.</td></tr><?php else : foreach ( $quizzes as $quiz ) :
				$topic_id  = (int) $quiz->post_parent;
				$course_id = (int) wp_get_post_parent_id( $topic_id );
				if ( $course_id && ! tutor_utils()->can_user_manage( 'course', $course_id ) ) { continue; }
				$course = $course_id ? get_post( $course_id ) : null;
				$url = $course_id ? add_query_arg( array( 'page' => 'create-course', 'course_id' => $course_id ), admin_url( 'admin.php' ) ) : '#';
				$status_obj = get_post_status_object( $quiz->post_status );
				?><tr><td><strong><?php echo esc_html( $quiz->post_title ); ?></strong></td><td><?php echo esc_html( $course ? $course->post_title : '—' ); ?></td><td><?php echo esc_html( $status_obj ? $status_obj->label : $quiz->post_status ); ?></td><td><a class="button" href="<?php echo esc_url( $url ); ?>">فتح داخل منشئ الدورة</a></td></tr>
			<?php endforeach; endif; ?>
			</tbody></table></div>
		</section>
	</div>
	<?php
}

/** Create an empty quiz through Tutor QuizBuilder's own persistence contract. */
function qalam_050_create_quiz() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) {
		wp_die( 'غير مسموح.' );
	}
	check_admin_referer( 'qalam_050_create_quiz', 'qalam_050_nonce' );

	$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
	$topic_id  = isset( $_POST['topic_id'] ) ? absint( $_POST['topic_id'] ) : 0;
	$title     = isset( $_POST['quiz_title'] ) ? sanitize_text_field( wp_unslash( $_POST['quiz_title'] ) ) : '';
	$redirect  = admin_url( 'admin.php?page=qalam-quiz-builder' );

	if ( ! $course_id || ! $topic_id || '' === $title || (int) wp_get_post_parent_id( $topic_id ) !== $course_id || ! tutor_utils()->can_user_manage( 'course', $course_id ) ) {
		wp_safe_redirect( add_query_arg( 'qalam_quiz_error', rawurlencode( 'بيانات الاختبار غير صحيحة أو معندكش صلاحية على الدورة.' ), $redirect ) );
		exit;
	}

	$builder = new \TUTOR\QuizBuilder( false );
	$result  = $builder->save_quiz(
		$topic_id,
		array(
			'post_title'   => $title,
			'post_content' => '',
			'quiz_option'  => array(),
			'questions'    => array(),
		)
	);

	if ( ! empty( $result->success ) && ! empty( $result->data ) ) {
		wp_safe_redirect( add_query_arg( 'qalam_quiz_created', (int) $result->data, $redirect ) );
		exit;
	}

	$message = 'تعذر إنشاء الاختبار.';
	if ( ! empty( $result->errors ) ) {
		$message .= ' راجع الدورة والقسم وحاول مرة تانية.';
	}
	wp_safe_redirect( add_query_arg( 'qalam_quiz_error', rawurlencode( $message ), $redirect ) );
	exit;
}
add_action( 'admin_post_qalam_050_create_quiz', 'qalam_050_create_quiz' );

/** Arabic metadata for all packaged Pro add-ons. */
function qalam_050_addon_copy() {
	return array(
		'auth' => array( 'أمان الحساب', 'خيارات إضافية لحماية تسجيل الدخول وحسابات الطلاب والمعلمين.' ),
		'buddypress' => array( 'تكامل BuddyPress', 'ربط قلم بميزات المجتمع والتفاعل في BuddyPress.' ),
		'calendar' => array( 'تقويم قلم', 'عرض مواعيد الدروس والاختبارات والواجبات والحصص في مكان واحد.' ),
		'content-bank' => array( 'بنك المحتوى', 'إعادة استخدام الدروس والواجبات والمحتوى بين الدورات.' ),
		'content-drip' => array( 'التدرج في المحتوى', 'فتح المحتوى للطلاب حسب الوقت أو الشروط المحددة.' ),
		'course-bundle' => array( 'حزم الدورات', 'تجميع أكتر من دورة وبيعها أو تقديمها كحزمة واحدة.' ),
		'enrollments' => array( 'التسجيلات', 'تسجيل الطلاب يدويًا وإدارة وصولهم للدورات.' ),
		'google-classroom' => array( 'Google Classroom', 'ربط Qalam LMS بخدمة Google Classroom.' ),
		'google-meet' => array( 'Google Meet', 'إنشاء وإدارة الحصص المباشرة من خلال Google Meet.' ),
		'gradebook' => array( 'سجل الدرجات', 'إدارة درجات الاختبارات والواجبات ومتابعة مستوى الطلاب.' ),
		'h5p' => array( 'تكامل H5P', 'إضافة محتوى H5P التفاعلي داخل الدورات.' ),
		'pmpro' => array( 'تكامل Paid Memberships Pro', 'ربط الوصول للدورات بعضويات Paid Memberships Pro.' ),
		'quiz-import-export' => array( 'استيراد وتصدير الاختبارات', 'نقل الاختبارات والأسئلة بين المواقع والنسخ.' ),
		'restrict-content-pro' => array( 'تكامل Restrict Content Pro', 'التحكم في الوصول للمحتوى عن طريق Restrict Content Pro.' ),
		'social-login' => array( 'تسجيل الدخول الاجتماعي', 'تسجيل الدخول بحسابات Google وFacebook والخدمات المدعومة.' ),
		'subscription' => array( 'الاشتراكات', 'إنشاء خطط اشتراك وإدارة الوصول المتجدد للدورات.' ),
		'tutor-assignments' => array( 'واجبات قلم', 'إنشاء الواجبات واستلام إجابات الطلاب وتقييمها.' ),
		'tutor-certificate' => array( 'شهادات قلم', 'إصدار شهادات للطلاب بعد إكمال الدورات.' ),
		'tutor-course-attachments' => array( 'مرفقات الدورة', 'إضافة ملفات ومصادر قابلة للتنزيل داخل الدورات.' ),
		'tutor-course-preview' => array( 'معاينة الدورة', 'إتاحة أجزاء من الدورة للمعاينة قبل التسجيل.' ),
		'tutor-email' => array( 'بريد قلم', 'إرسال رسائل بريد تلقائية مرتبطة بأحداث المنصة.' ),
		'tutor-multi-instructors' => array( 'تعدد المعلمين', 'إضافة أكتر من معلم لنفس الدورة وإدارة صلاحياتهم.' ),
		'tutor-notifications' => array( 'إشعارات قلم', 'إشعارات داخلية للطلاب والمعلمين عن أحداث المنصة.' ),
		'tutor-prerequisites' => array( 'المتطلبات السابقة', 'اشتراط إكمال دورات محددة قبل بدء دورة أخرى.' ),
		'tutor-report' => array( 'تقارير قلم', 'تقارير وتحليلات الدورات والطلاب والأداء.' ),
		'tutor-weglot' => array( 'تكامل Weglot', 'ربط Qalam LMS بخدمة Weglot للمواقع متعددة اللغات.' ),
		'tutor-wpml' => array( 'تكامل WPML', 'ربط Qalam LMS بإضافة WPML للمحتوى متعدد اللغات.' ),
		'tutor-zoom' => array( 'تكامل Zoom', 'إنشاء وإدارة الحصص المباشرة من خلال Zoom.' ),
		'wc-subscriptions' => array( 'اشتراكات المتجر الإلكتروني', 'ربط الوصول المتكرر بخدمة الاشتراكات في المتجر الإلكتروني.' ),
	);
}

/**
 * Discover every packaged add-on from the actual installed Pro filesystem.
 *
 * @return array<string,array>
 */
function qalam_050_addon_registry() {
	if ( ! defined( 'TUTOR_PRO_FILE' ) ) {
		return array();
	}
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$pro_path   = trailingslashit( plugin_dir_path( TUTOR_PRO_FILE ) );
	$pro_root   = dirname( plugin_basename( TUTOR_PRO_FILE ) );
	$copy       = qalam_050_addon_copy();
	$registered = apply_filters( 'tutor_addons_lists_config', array() );
	$config     = get_option( 'tutor_addons_config', array() );
	$config     = is_array( $config ) ? $config : array();
	$boot_errs  = get_option( 'qalam_lms_pro_addon_boot_errors', array() );
	$boot_errs  = is_array( $boot_errs ) ? $boot_errs : array();
	$activation_errs = get_option( 'qalam_lms_pro_addon_activation_errors', array() );
	$activation_errs = is_array( $activation_errs ) ? $activation_errs : array();
	$registry   = array();

	$dirs = glob( $pro_path . 'addons/*', GLOB_ONLYDIR );
	$dirs = is_array( $dirs ) ? $dirs : array();
	sort( $dirs, SORT_NATURAL | SORT_FLAG_CASE );

	foreach ( $dirs as $dir ) {
		$slug = sanitize_key( basename( $dir ) );
		$file = trailingslashit( $dir ) . $slug . '.php';
		if ( ! is_file( $file ) ) {
			continue;
		}
		$basename = $pro_root . '/addons/' . $slug . '/' . $slug . '.php';
		$meta = array();
		foreach ( $registered as $key => $candidate ) {
			$normalized = str_replace( '\\', '/', (string) $key );
			if ( $normalized === $basename || substr( $normalized, -strlen( '/addons/' . $slug . '/' . $slug . '.php' ) ) === '/addons/' . $slug . '/' . $slug . '.php' ) {
				$meta = is_array( $candidate ) ? $candidate : array();
				break;
			}
		}

		$depends = isset( $meta['depend_plugins'] ) && is_array( $meta['depend_plugins'] ) ? $meta['depend_plugins'] : array();
		$missing = array();
		foreach ( $depends as $plugin_file => $plugin_name ) {
			if ( ! is_plugin_active( $plugin_file ) ) {
				$missing[ $plugin_file ] = $plugin_name;
			}
		}

		$state = tutor_utils()->get_addon_config( $basename );
		$enabled = is_array( $state ) && ! empty( $state['is_enable'] );
		$error = isset( $boot_errs[ $slug ] ) && is_array( $boot_errs[ $slug ] ) ? $boot_errs[ $slug ] : array();
		if ( empty( $error ) && isset( $activation_errs[ $slug ] ) && is_array( $activation_errs[ $slug ] ) ) {
			$error = $activation_errs[ $slug ];
		}

		$name = isset( $copy[ $slug ][0] ) ? $copy[ $slug ][0] : ucwords( str_replace( '-', ' ', $slug ) );
		$description = isset( $copy[ $slug ][1] ) ? $copy[ $slug ][1] : ( isset( $meta['description'] ) ? wp_strip_all_tags( $meta['description'] ) : 'ملحق إضافي ضمن Qalam LMS Pro.' );

		$status = 'متوقف';
		$status_key = 'disabled';
		if ( ! empty( $error ) ) {
			$status = 'خطأ أثناء التشغيل';
			$status_key = 'error';
		} elseif ( ! empty( $missing ) ) {
			$status = 'يحتاج إضافة خارجية';
			$status_key = 'dependency';
		} elseif ( $enabled ) {
			$status = 'مفعل';
			$status_key = 'enabled';
		}

		$registry[ $slug ] = array(
			'slug'        => $slug,
			'file'        => $file,
			'basename'    => $basename,
			'name'        => $name,
			'description' => $description,
			'enabled'     => $enabled,
			'missing'     => $missing,
			'error'       => $error,
			'status'      => $status,
			'status_key'  => $status_key,
		);
	}

	return $registry;
}

/** Render the complete Qalam add-on registry instead of an incomplete React list. */
function qalam_050_render_addons() {
	if ( ! current_user_can( 'manage_tutor' ) ) {
		wp_die( 'غير مسموح.' );
	}
	$registry = qalam_050_addon_registry();
	?>
	<div class="wrap qalam-050-wrap" dir="rtl">
		<div class="qalam-050-hero">
			<div><span class="qalam-050-eyebrow">Qalam LMS Pro</span><h1>الملحقات</h1><p>كل الملحقات الموجودة فعليًا في الحزمة بتظهر هنا بحالتها الحقيقية. التفعيل بيتم ملحق واحد في كل مرة علشان أي مشكلة ما توقعش الموقع كله.</p></div>
			<div class="qalam-050-count"><strong><?php echo esc_html( count( $registry ) ); ?></strong><span>ملحق موجود</span></div>
		</div>
		<?php if ( ! defined( 'TUTOR_PRO_FILE' ) ) : ?>
			<div class="notice notice-warning inline"><p>فعّل Qalam LMS Pro علشان تظهر الملحقات المتقدمة.</p></div>
		<?php else : ?>
		<div class="qalam-addon-toolbar"><input type="search" class="qalam-050-search" placeholder="ابحث في الملحقات..." data-qalam-addon-search><span>التفعيل الآمن: ملحق واحد في كل عملية</span></div>
		<div class="qalam-addon-grid" data-qalam-addon-grid>
			<?php foreach ( $registry as $addon ) : ?>
				<article class="qalam-addon-card" data-qalam-addon-card data-qalam-search-text="<?php echo esc_attr( $addon['name'] . ' ' . $addon['description'] ); ?>">
					<div class="qalam-addon-top"><div class="qalam-addon-icon"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $addon['name'], 0, 1 ) : substr( $addon['name'], 0, 1 ) ); ?></div><span class="qalam-addon-status is-<?php echo esc_attr( $addon['status_key'] ); ?>"><?php echo esc_html( $addon['status'] ); ?></span></div>
					<h3><?php echo esc_html( $addon['name'] ); ?></h3>
					<p><?php echo esc_html( $addon['description'] ); ?></p>
					<?php if ( ! empty( $addon['missing'] ) ) : ?><div class="qalam-addon-note"><strong>مطلوب:</strong> <?php echo esc_html( implode( '، ', array_values( $addon['missing'] ) ) ); ?></div><?php endif; ?>
					<?php if ( ! empty( $addon['error']['message'] ) ) : ?><div class="qalam-addon-note is-error"><strong>آخر خطأ:</strong> <?php echo esc_html( $addon['error']['message'] ); ?></div><?php endif; ?>
					<div class="qalam-addon-actions">
						<button type="button" class="button <?php echo $addon['enabled'] ? '' : 'button-primary'; ?>" data-qalam-addon-toggle data-addon="<?php echo esc_attr( $addon['slug'] ); ?>" data-enable="<?php echo $addon['enabled'] ? '0' : '1'; ?>" <?php disabled( ! $addon['enabled'] && ! empty( $addon['missing'] ) ); ?>><?php echo esc_html( $addon['enabled'] ? 'تعطيل' : 'تفعيل' ); ?></button>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<section class="qalam-050-panel qalam-advanced-modules"><div class="qalam-050-section-head"><div><h2>ميزات متقدمة داخل Qalam Pro</h2><p>دي وحدات داخلية مش مجلدات Add-on تقليدية، لذلك بتظهر بشكل منفصل.</p></div></div><div class="qalam-050-grid qalam-module-grid">
			<div class="qalam-050-card"><div class="qalam-050-card-icon">AI</div><div class="qalam-050-card-body"><h3>ذكاء قلم الاصطناعي</h3><p>OpenAI وDeepSeek وOpenRouter وGoogle AI Studio والمزودات المتوافقة.</p></div></div>
			<div class="qalam-050-card"><div class="qalam-050-card-icon">هـ</div><div class="qalam-050-card-body"><h3>إهداء الدورات</h3><p>وحدة Gift Course المدمجة داخل Qalam Pro.</p></div></div>
			<div class="qalam-050-card"><div class="qalam-050-card-icon">ق</div><div class="qalam-050-card-body"><h3>استيراد القوالب</h3><p>وحدة استيراد القوالب المعزولة عن خدمات الترخيص الخارجية القديمة.</p></div></div>
		</div></section>
		<?php endif; ?>
	</div>
	<?php
}

/** Safe single add-on toggle. */
function qalam_050_toggle_addon() {
	check_ajax_referer( 'qalam_050_addon_toggle', 'nonce' );
	if ( ! current_user_can( 'manage_tutor' ) ) {
		wp_send_json_error( array( 'message' => 'معندكش صلاحية لتغيير حالة الملحقات.' ), 403 );
	}

	$slug   = isset( $_POST['addon'] ) ? sanitize_key( wp_unslash( $_POST['addon'] ) ) : '';
	$enable = isset( $_POST['enable'] ) && '1' === (string) wp_unslash( $_POST['enable'] );
	$all    = qalam_050_addon_registry();
	if ( ! isset( $all[ $slug ] ) ) {
		wp_send_json_error( array( 'message' => 'الملحق غير موجود في حزمة Qalam Pro.' ), 404 );
	}
	$addon = $all[ $slug ];
	if ( $enable && ! empty( $addon['missing'] ) ) {
		wp_send_json_error( array( 'message' => 'ثبت الإضافة المطلوبة الأول: ' . implode( '، ', array_values( $addon['missing'] ) ) ), 422 );
	}

	$config = get_option( 'tutor_addons_config', array() );
	$config = is_array( $config ) ? $config : array();
	$previous_config = $config;
	$key    = $addon['basename'];
	$legacy_key = preg_replace( '#^[^/]+/addons/#', 'tutor-pro/addons/', $key );
	$state  = tutor_utils()->get_addon_config( $key );
	$state  = is_array( $state ) ? $state : array();
	$state['is_enable'] = $enable ? 1 : 0;

	try {
		do_action( 'tutor_addon_before_enable_disable' );
		if ( $enable ) {
			do_action( "tutor_addon_before_enable_{$key}" );
			if ( $legacy_key !== $key ) { do_action( "tutor_addon_before_enable_{$legacy_key}" ); }
			do_action( 'tutor_addon_before_enable', $key );
		} else {
			do_action( "tutor_addon_before_disable_{$key}" );
			if ( $legacy_key !== $key ) { do_action( "tutor_addon_before_disable_{$legacy_key}" ); }
			do_action( 'tutor_addon_before_disable', $key );
		}

		$config[ $key ] = $state;
		update_option( 'tutor_addons_config', $config, false );

		if ( $enable ) {
			do_action( 'tutor_addon_after_enable', $key );
			do_action( "tutor_addon_after_enable_{$key}" );
			if ( $legacy_key !== $key ) { do_action( "tutor_addon_after_enable_{$legacy_key}" ); }
		} else {
			do_action( 'tutor_addon_after_disable', $key );
			do_action( "tutor_addon_after_disable_{$key}" );
			if ( $legacy_key !== $key ) { do_action( "tutor_addon_after_disable_{$legacy_key}" ); }
		}
		do_action( 'tutor_addon_after_enable_disable' );

		$errors = get_option( 'qalam_lms_pro_addon_activation_errors', array() );
		$errors = is_array( $errors ) ? $errors : array();
		if ( isset( $errors[ $slug ] ) ) {
			unset( $errors[ $slug ] );
			update_option( 'qalam_lms_pro_addon_activation_errors', $errors, false );
		}
	} catch ( \Throwable $e ) {
		update_option( 'tutor_addons_config', $previous_config, false );
		$errors = get_option( 'qalam_lms_pro_addon_activation_errors', array() );
		$errors = is_array( $errors ) ? $errors : array();
		$errors[ $slug ] = array( 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'time' => gmdate( 'c' ) );
		update_option( 'qalam_lms_pro_addon_activation_errors', $errors, false );
		wp_send_json_error( array( 'message' => 'حصل خطأ أثناء تغيير حالة الملحق واتعمل تراجع تلقائي: ' . $e->getMessage() ), 500 );
	}

	wp_send_json_success( array( 'message' => $enable ? 'تم تفعيل الملحق. هيتم تحميله بأمان بعد تحديث الصفحة.' : 'تم تعطيل الملحق.' ) );
}
add_action( 'wp_ajax_qalam_050_toggle_addon', 'qalam_050_toggle_addon' );

/** Runtime mixed-language audit: report only, never mutate UI. */
function qalam_050_mixed_language_audit_config() {
	if ( ! is_admin() || ! current_user_can( 'manage_tutor' ) ) {
		return;
	}
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( '' === $page && ! isset( $_GET['post_type'] ) ) {
		return;
	}
	$base = plugin_dir_url( TUTOR_FILE );
	wp_enqueue_script( 'qalam-mixed-audit', $base . 'assets/js/qalam-mixed-audit.js', array(), QALAM_LMS_UI_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'qalam_050_mixed_language_audit_config', PHP_INT_MAX );
