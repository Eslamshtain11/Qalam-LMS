<?php
/**
 * Qalam LMS 0.6.0 control-center release.
 *
 * Add-on artwork, AI activation/model discovery, certificate workspace,
 * general quizzes and the separated Question Bank live here.
 *
 * @package QalamLMS
 */

defined( 'ABSPATH' ) || exit;

/** Question Bank taxonomy. */
const QALAM_QUESTION_CATEGORY_TAX = 'qalam-question-category';
const QALAM_GENERAL_COURSE_META   = '_qalam_internal_general_quiz_course';
const QALAM_GENERAL_QUIZ_META     = '_qalam_general_quiz';
const QALAM_GENERAL_TOPIC_META    = '_qalam_internal_general_quiz_topic';

/** Extra complete-string translations for this release. */
function qalam_060_dictionary( $map ) {
	$map = is_array( $map ) ? $map : array();
	return array_replace(
		$map,
		array(
			'Certificate Builder' => 'منشئ شهادات التقدير',
			'Question Bank' => 'بنك الأسئلة',
			'Questions' => 'الأسئلة',
			'AI Provider API Key' => 'مفتاح API للمزود',
			'AI Model' => 'نموذج الذكاء الاصطناعي',
			'Custom OpenAI Base URL' => 'رابط Base URL للمزود المخصص',
			'Choose the provider used by Qalam AI for quiz and text generation.' => 'اختار المزود اللي هيستخدمه قلم لإنشاء الأسئلة والنصوص وتشغيل أدوات الذكاء الاصطناعي.',
			'Enter the API key for the selected provider. The key is stored server-side and is never exposed to students.' => 'أدخل مفتاح API الخاص بالمزود المختار. المفتاح بيتخزن على السيرفر ومش بيتبعت للطلاب أو يظهر في واجهة الموقع.',
			'Optional model id. Leave empty to use the Qalam recommended model for the selected provider.' => 'اختار الموديل اللي هيستخدمه قلم بعد تفعيل المزود وجلب الموديلات المتاحة.',
			'Used only when the Custom provider is selected. Example: https://provider.example.com/v1' => 'الخانة دي بتستخدم فقط مع المزود المخصص المتوافق مع OpenAI. اكتب رابط الـ API الأساسي للمزود.',
		)
	);
}
add_filter( 'qalam_lms_dictionary', 'qalam_060_dictionary', 60 );

/** Register hierarchical categories dedicated to questions. */
function qalam_060_register_question_taxonomy() {
	register_taxonomy(
		QALAM_QUESTION_CATEGORY_TAX,
		array( 'cb-question' ),
		array(
			'labels' => array(
				'name'          => 'تصنيفات بنك الأسئلة',
				'singular_name' => 'تصنيف سؤال',
				'add_new_item'  => 'إضافة تصنيف جديد',
				'parent_item'   => 'التصنيف الأب',
			),
			'public'            => false,
			'show_ui'           => false,
			'show_in_rest'      => false,
			'hierarchical'      => true,
			'show_admin_column' => false,
			'rewrite'           => false,
			'query_var'         => false,
		)
	);
}
add_action( 'init', 'qalam_060_register_question_taxonomy', 60 );

/**
 * Add Qalam control-center pages and override the 0.5 quiz/add-ons callbacks.
 *
 * @param array $menu Tutor menu schema.
 * @return array
 */
function qalam_060_admin_menu( $menu ) {
	if ( ! is_array( $menu ) ) {
		return $menu;
	}

	if ( isset( $menu['group_one']['qalam_quiz_builder'] ) ) {
		$menu['group_one']['qalam_quiz_builder']['callback'] = 'qalam_060_render_quiz_builder';
		$menu['group_one']['qalam_quiz_builder']['page_title'] = 'منشئ الاختبارات';
		$menu['group_one']['qalam_quiz_builder']['menu_title'] = 'منشئ الاختبارات';
	}

	$question_bank = array(
		'parent_slug' => 'tutor',
		'page_title'  => 'بنك الأسئلة',
		'menu_title'  => 'بنك الأسئلة',
		'capability'  => 'manage_tutor_instructor',
		'menu_slug'   => 'qalam-question-bank',
		'callback'    => 'qalam_060_render_question_bank',
	);

	$group_one = isset( $menu['group_one'] ) && is_array( $menu['group_one'] ) ? $menu['group_one'] : array();
	$new_group = array();
	foreach ( $group_one as $key => $item ) {
		$new_group[ $key ] = $item;
		if ( 'content_bank' === $key ) {
			$new_group['qalam_question_bank'] = $question_bank;
		}
	}
	if ( ! isset( $new_group['qalam_question_bank'] ) ) {
		$new_group['qalam_question_bank'] = $question_bank;
	}
	$menu['group_one'] = $new_group;

	$certificate_builder = array(
		'parent_slug' => 'tutor',
		'page_title'  => 'منشئ شهادات التقدير',
		'menu_title'  => 'منشئ الشهادات',
		'capability'  => 'manage_tutor',
		'menu_slug'   => 'qalam-certificate-builder',
		'callback'    => 'qalam_060_render_certificate_builder',
	);

	$target_group = isset( $menu['group_three'] ) && is_array( $menu['group_three'] ) ? 'group_three' : 'group_two';
	if ( ! isset( $menu[ $target_group ] ) || ! is_array( $menu[ $target_group ] ) ) {
		$menu[ $target_group ] = array();
	}
	$new_three = array();
	$inserted = false;
	foreach ( $menu[ $target_group ] as $key => $item ) {
		$new_three[ $key ] = $item;
		if ( 'gradebook' === $key || 'addons' === $key ) {
			$new_three['qalam_certificate_builder'] = $certificate_builder;
			$inserted = true;
		}
	}
	if ( ! $inserted ) {
		$new_three['qalam_certificate_builder'] = $certificate_builder;
	}
	$menu[ $target_group ] = $new_three;

	if ( isset( $menu['group_three']['addons'] ) && is_array( $menu['group_three']['addons'] ) ) {
		$menu['group_three']['addons']['callback'] = 'qalam_060_render_addons';
	}

	return $menu;
}
add_filter( 'tutor_admin_menu', 'qalam_060_admin_menu', PHP_INT_MAX );

/** Load control-center assets on Qalam pages and Tutor settings. */
function qalam_060_admin_assets() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$pages = array( 'qalam-course-builder', 'create-course', 'qalam-quiz-builder', 'tutor-addons', 'qalam-question-bank', 'qalam-certificate-builder', 'tutor_settings', 'tutor-content-bank' );
	if ( ! in_array( $page, $pages, true ) ) {
		return;
	}
	$base = plugin_dir_url( TUTOR_FILE );
	wp_enqueue_style( 'qalam-050-admin', $base . 'assets/css/qalam-050-admin.css', array( 'qalam-lms-brand' ), QALAM_LMS_UI_VERSION );
	wp_enqueue_style( 'qalam-060-admin', $base . 'assets/css/qalam-060-admin.css', array( 'qalam-050-admin' ), QALAM_LMS_UI_VERSION );
	wp_enqueue_script( 'qalam-060-admin', $base . 'assets/js/qalam-060-admin.js', array(), QALAM_LMS_UI_VERSION, true );
	wp_localize_script(
		'qalam-060-admin',
		'Qalam060',
		array(
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
			'ai_nonce'       => wp_create_nonce( 'qalam_ai_activate_provider' ),
			'addon_nonce'    => wp_create_nonce( 'qalam_050_addon_toggle' ),
			'loadingModels'  => 'جاري التحقق من المفتاح وجلب الموديلات...',
			'activateLabel'  => 'تفعيل وجلب الموديلات',
			'modelSearch'    => 'ابحث عن موديل...',
			'noModels'       => 'مفيش موديلات مطابقة للبحث.',
		)
	);
}
add_action( 'admin_enqueue_scripts', 'qalam_060_admin_assets', PHP_INT_MAX );

/** Resolve the best local artwork for a packaged add-on. */
function qalam_060_addon_icon_url( $slug ) {
	$slug = sanitize_key( $slug );
	$core_path = trailingslashit( plugin_dir_path( TUTOR_FILE ) );
	$core_url  = trailingslashit( plugin_dir_url( TUTOR_FILE ) );
	$candidates = array(
		array( $core_path . 'assets/images/addons/' . $slug . '/thumbnail.svg', $core_url . 'assets/images/addons/' . $slug . '/thumbnail.svg' ),
		array( $core_path . 'assets/images/addons/' . $slug . '/thumbnail.png', $core_url . 'assets/images/addons/' . $slug . '/thumbnail.png' ),
	);
	if ( defined( 'TUTOR_PRO_FILE' ) ) {
		$pro_path = trailingslashit( plugin_dir_path( TUTOR_PRO_FILE ) );
		$pro_url  = trailingslashit( plugin_dir_url( TUTOR_PRO_FILE ) );
		$candidates[] = array( $pro_path . 'addons/' . $slug . '/assets/images/thumbnail.svg', $pro_url . 'addons/' . $slug . '/assets/images/thumbnail.svg' );
		$candidates[] = array( $pro_path . 'addons/' . $slug . '/assets/images/thumbnail.png', $pro_url . 'addons/' . $slug . '/assets/images/thumbnail.png' );
	}
	foreach ( $candidates as $candidate ) {
		if ( is_file( $candidate[0] ) ) {
			return esc_url_raw( $candidate[1] );
		}
	}
	return $core_url . 'assets/images/qalam-logo.svg';
}

/** Extended registry adds local icon artwork without mutating Tutor's registry. */
function qalam_060_addon_registry() {
	$registry = qalam_050_addon_registry();
	foreach ( $registry as $slug => &$addon ) {
		$addon['icon_url'] = qalam_060_addon_icon_url( $slug );
	}
	unset( $addon );
	return $registry;
}

/** Render add-on cards with real packaged icons. */
function qalam_060_render_addons() {
	if ( ! current_user_can( 'manage_tutor' ) ) {
		wp_die( 'غير مسموح.' );
	}
	$registry = qalam_060_addon_registry();
	?>
	<div class="wrap qalam-050-wrap qalam-060-wrap" dir="rtl">
		<div class="qalam-050-hero">
			<div><span class="qalam-050-eyebrow">Qalam LMS Pro</span><h1>الملحقات</h1><p>كل ملحق ظاهر بأيقونته الأصلية وحالته الحقيقية. التفعيل بيتم بشكل منفصل وآمن علشان خطأ ملحق واحد ما يوقفش المنصة.</p></div>
			<div class="qalam-050-count"><strong><?php echo esc_html( count( $registry ) ); ?></strong><span>ملحق موجود</span></div>
		</div>
		<?php if ( ! defined( 'TUTOR_PRO_FILE' ) ) : ?>
			<div class="notice notice-warning inline"><p>فعّل Qalam LMS Pro علشان تظهر الملحقات المتقدمة.</p></div>
		<?php else : ?>
		<div class="qalam-addon-toolbar"><input type="search" class="qalam-050-search" placeholder="ابحث في الملحقات..." data-qalam-addon-search><span>التفعيل الآمن: ملحق واحد في كل عملية</span></div>
		<div class="qalam-addon-grid" data-qalam-addon-grid>
			<?php foreach ( $registry as $addon ) : ?>
			<article class="qalam-addon-card" data-qalam-addon-card data-qalam-search-text="<?php echo esc_attr( $addon['name'] . ' ' . $addon['description'] ); ?>">
				<div class="qalam-addon-top">
					<div class="qalam-addon-icon qalam-addon-icon-art"><img src="<?php echo esc_url( $addon['icon_url'] ); ?>" alt="" loading="lazy"></div>
					<span class="qalam-addon-status is-<?php echo esc_attr( $addon['status_key'] ); ?>"><?php echo esc_html( $addon['status'] ); ?></span>
				</div>
				<h3><?php echo esc_html( $addon['name'] ); ?></h3>
				<p><?php echo esc_html( $addon['description'] ); ?></p>
				<?php if ( ! empty( $addon['missing'] ) ) : ?><div class="qalam-addon-note"><strong>مطلوب:</strong> <?php echo esc_html( implode( '، ', array_values( $addon['missing'] ) ) ); ?></div><?php endif; ?>
				<?php if ( ! empty( $addon['error']['message'] ) ) : ?><div class="qalam-addon-note is-error"><strong>آخر خطأ:</strong> <?php echo esc_html( $addon['error']['message'] ); ?></div><?php endif; ?>
				<div class="qalam-addon-actions"><button type="button" class="button <?php echo $addon['enabled'] ? '' : 'button-primary'; ?>" data-qalam-addon-toggle data-addon="<?php echo esc_attr( $addon['slug'] ); ?>" data-enable="<?php echo $addon['enabled'] ? '0' : '1'; ?>" <?php disabled( ! $addon['enabled'] && ! empty( $addon['missing'] ) ); ?>><?php echo esc_html( $addon['enabled'] ? 'تعطيل' : 'تفعيل' ); ?></button></div>
			</article>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

/** Find or create the internal course/topic container used by general quizzes. */
function qalam_060_general_quiz_container() {
	$courses = get_posts(
		array(
			'post_type'      => tutor()->course_post_type,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 1,
			'meta_key'       => QALAM_GENERAL_COURSE_META,
			'meta_value'     => '1',
			'fields'         => 'ids',
			'suppress_filters' => true,
		)
	);
	$course_id = ! empty( $courses ) ? absint( $courses[0] ) : 0;
	if ( ! $course_id ) {
		$course_id = wp_insert_post(
			array(
				'post_type'   => tutor()->course_post_type,
				'post_status' => 'publish',
				'post_title'  => 'مساحة الاختبارات العامة - نظام قلم',
				'post_author' => get_current_user_id() ?: 1,
				'post_content'=> '',
			)
		);
		if ( is_wp_error( $course_id ) || ! $course_id ) {
			throw new RuntimeException( 'تعذر إنشاء مساحة الاختبارات العامة.' );
		}
		update_post_meta( $course_id, QALAM_GENERAL_COURSE_META, '1' );
		update_post_meta( $course_id, '_tutor_course_price_type', 'free' );
	}

	$topics = get_posts(
		array(
			'post_type'      => tutor()->topics_post_type,
			'post_parent'    => $course_id,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 1,
			'meta_key'       => QALAM_GENERAL_TOPIC_META,
			'meta_value'     => '1',
			'fields'         => 'ids',
		)
	);
	$topic_id = ! empty( $topics ) ? absint( $topics[0] ) : 0;
	if ( ! $topic_id ) {
		$topic_id = wp_insert_post(
			array(
				'post_type'   => tutor()->topics_post_type,
				'post_parent' => $course_id,
				'post_status' => 'publish',
				'post_title'  => 'الاختبارات العامة',
				'post_author' => get_current_user_id() ?: 1,
			)
		);
		if ( is_wp_error( $topic_id ) || ! $topic_id ) {
			throw new RuntimeException( 'تعذر إنشاء قسم الاختبارات العامة.' );
		}
		update_post_meta( $topic_id, QALAM_GENERAL_TOPIC_META, '1' );
	}
	return array( 'course_id' => $course_id, 'topic_id' => $topic_id );
}

/** Hide the internal system course from ordinary course listings. */
function qalam_060_hide_general_course( $query ) {
	if ( ! $query instanceof WP_Query || ! $query->is_main_query() ) {
		return;
	}
	$post_type = $query->get( 'post_type' );
	$is_course_query = $post_type === tutor()->course_post_type || ( is_array( $post_type ) && in_array( tutor()->course_post_type, $post_type, true ) );
	if ( ! $is_course_query ) {
		return;
	}
	$meta_query = $query->get( 'meta_query' );
	$meta_query = is_array( $meta_query ) ? $meta_query : array();
	$meta_query[] = array( 'key' => QALAM_GENERAL_COURSE_META, 'compare' => 'NOT EXISTS' );
	$query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'qalam_060_hide_general_course', 20 );

/** Keep the hidden general-quiz container out of student dashboard course queries. */
function qalam_060_exclude_general_course_args( $args ) {
	$args = is_array( $args ) ? $args : array();
	$meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
	$meta_query[] = array( 'key' => QALAM_GENERAL_COURSE_META, 'compare' => 'NOT EXISTS' );
	$args['meta_query'] = $meta_query;
	return $args;
}
add_filter( 'tutor_get_completed_courses_by_user', 'qalam_060_exclude_general_course_args', 99 );
add_filter( 'tutor_get_active_courses_by_user', 'qalam_060_exclude_general_course_args', 99 );
add_filter( 'tutor_get_enrolled_courses_by_user', 'qalam_060_exclude_general_course_args', 99 );

/** Ensure a logged-in user has transparent access to a general quiz's hidden container. */
function qalam_060_prepare_general_quiz_access() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$quiz_id = 0;
	if ( isset( $_POST['tutor_action'], $_POST['quiz_id'] ) && 'tutor_start_quiz' === sanitize_text_field( wp_unslash( $_POST['tutor_action'] ) ) ) {
		$quiz_id = absint( $_POST['quiz_id'] );
	} elseif ( is_singular( tutor()->quiz_post_type ) ) {
		$quiz_id = get_queried_object_id();
	}
	if ( ! $quiz_id || '1' !== (string) get_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, true ) ) {
		return;
	}
	$course_id = (int) wp_get_post_parent_id( (int) wp_get_post_parent_id( $quiz_id ) );
	if ( ! $course_id ) {
		return;
	}
	if ( class_exists( '\Tutor\Models\EnrollmentModel' ) ) {
		\Tutor\Models\EnrollmentModel::do_enroll( $course_id, 0, get_current_user_id(), false );
	}
}
add_action( 'template_redirect', 'qalam_060_prepare_general_quiz_access', 0 );

/** Courses visible in the Qalam quiz workspace, excluding the hidden system container. */
function qalam_060_quiz_courses() {
	$args = array(
		'post_type'      => tutor()->course_post_type,
		'post_status'    => array( 'draft', 'pending', 'publish', 'private' ),
		'posts_per_page' => 200,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_query'     => array( array( 'key' => QALAM_GENERAL_COURSE_META, 'compare' => 'NOT EXISTS' ) ),
	);
	if ( ! current_user_can( 'manage_tutor' ) ) {
		$args['author'] = get_current_user_id();
	}
	return get_posts( $args );
}

/** General/course quiz workspace. */
function qalam_060_render_quiz_builder() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) {
		wp_die( 'غير مسموح.' );
	}
	$courses = qalam_060_quiz_courses();
	$quizzes = get_posts( array( 'post_type' => tutor()->quiz_post_type, 'post_status' => array( 'draft','pending','publish','private' ), 'posts_per_page' => 120, 'orderby' => 'modified', 'order' => 'DESC' ) );
	$created = isset( $_GET['qalam_quiz_created'] ) ? absint( $_GET['qalam_quiz_created'] ) : 0;
	$error = isset( $_GET['qalam_quiz_error'] ) ? sanitize_text_field( wp_unslash( $_GET['qalam_quiz_error'] ) ) : '';
	?>
	<div class="wrap qalam-050-wrap qalam-060-wrap" dir="rtl">
		<div class="qalam-050-hero"><div><span class="qalam-050-eyebrow">Qalam LMS</span><h1>منشئ الاختبارات</h1><p>أنشئ اختبار مرتبط بدورة أو اختبار عام مستقل من غير ما الطالب يشوف أي ارتباط بدورة داخلية.</p></div></div>
		<?php if ( $created ) : ?><div class="notice notice-success inline"><p>تم إنشاء الاختبار بنجاح.</p></div><?php endif; ?>
		<?php if ( $error ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>
		<section class="qalam-050-panel">
			<div class="qalam-050-section-head"><div><h2>إنشاء اختبار جديد</h2><p>اختار نوع الاختبار الأول، وبعدها اكتب اسمه.</p></div></div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qalam-050-form qalam-quiz-create-form" data-qalam-quiz-create-form>
				<input type="hidden" name="action" value="qalam_060_create_quiz"><?php wp_nonce_field( 'qalam_060_create_quiz', 'qalam_060_nonce' ); ?>
				<label><span>نوع الاختبار</span><select name="quiz_scope" data-qalam-quiz-scope><option value="general">اختبار عام غير مرتبط بدورة</option><option value="course">اختبار داخل دورة</option></select></label>
				<label data-qalam-course-field hidden><span>الدورة</span><select name="course_id" data-qalam-course-select><option value="">اختر الدورة</option><?php foreach ( $courses as $course ) : ?><option value="<?php echo esc_attr( $course->ID ); ?>"><?php echo esc_html( $course->post_title ); ?></option><?php endforeach; ?></select></label>
				<label data-qalam-topic-field hidden><span>القسم / الموضوع</span><select name="topic_id" data-qalam-topic-select><option value="">اختار الدورة الأول</option><?php foreach ( $courses as $course ) : $topics = get_posts( array( 'post_type' => tutor()->topics_post_type, 'post_parent' => $course->ID, 'post_status' => array('publish','draft','private'), 'posts_per_page' => -1, 'orderby'=>'menu_order','order'=>'ASC' ) ); foreach ( $topics as $topic ) : ?><option value="<?php echo esc_attr( $topic->ID ); ?>" data-course="<?php echo esc_attr( $course->ID ); ?>"><?php echo esc_html( $topic->post_title ); ?></option><?php endforeach; endforeach; ?></select></label>
				<label class="qalam-050-grow"><span>اسم الاختبار</span><input type="text" name="quiz_title" required maxlength="180" placeholder="مثال: اختبار شامل على الفصل الأول"></label>
				<button type="submit" class="button button-primary qalam-050-primary">إنشاء الاختبار</button>
			</form>
		</section>
		<section class="qalam-050-panel"><div class="qalam-050-section-head"><div><h2>الاختبارات الحالية</h2><p>الاختبارات العامة مميزة بعلامة واضحة ومخزنة داخليًا بطريقة متوافقة مع محرك قلم.</p></div></div><div class="qalam-050-table-wrap"><table class="widefat striped qalam-050-table"><thead><tr><th>الاختبار</th><th>النوع</th><th>الدورة</th><th>الحالة</th><th></th></tr></thead><tbody>
		<?php if ( empty( $quizzes ) ) : ?><tr><td colspan="5">لا توجد اختبارات حاليًا.</td></tr><?php else : foreach ( $quizzes as $quiz ) :
			$general = '1' === (string) get_post_meta( $quiz->ID, QALAM_GENERAL_QUIZ_META, true );
			$topic_id = (int) $quiz->post_parent; $course_id = (int) wp_get_post_parent_id( $topic_id );
			if ( ! $general && $course_id && ! tutor_utils()->can_user_manage( 'course', $course_id ) ) { continue; }
			$course = $course_id ? get_post( $course_id ) : null;
			$url = $course_id ? add_query_arg( array( 'page'=>'create-course','course_id'=>$course_id ), admin_url('admin.php') ) : '#';
			$status_obj = get_post_status_object( $quiz->post_status ); ?>
			<tr><td><strong><?php echo esc_html( $quiz->post_title ); ?></strong></td><td><?php echo $general ? '<span class="qalam-pill">اختبار عام</span>' : 'داخل دورة'; ?></td><td><?php echo $general ? '—' : esc_html( $course ? $course->post_title : '—' ); ?></td><td><?php echo esc_html( $status_obj ? $status_obj->label : $quiz->post_status ); ?></td><td><a class="button" href="<?php echo esc_url( $url ); ?>">فتح للتعديل</a></td></tr>
		<?php endforeach; endif; ?></tbody></table></div></section>
	</div>
	<?php
}

/** Create course-bound or general quiz through Tutor QuizBuilder persistence. */
function qalam_060_create_quiz() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) { wp_die( 'غير مسموح.' ); }
	check_admin_referer( 'qalam_060_create_quiz', 'qalam_060_nonce' );
	$scope = isset( $_POST['quiz_scope'] ) ? sanitize_key( wp_unslash( $_POST['quiz_scope'] ) ) : 'general';
	$title = isset( $_POST['quiz_title'] ) ? sanitize_text_field( wp_unslash( $_POST['quiz_title'] ) ) : '';
	$redirect = admin_url( 'admin.php?page=qalam-quiz-builder' );
	if ( '' === $title ) { wp_safe_redirect( add_query_arg( 'qalam_quiz_error', rawurlencode( 'اكتب اسم الاختبار.' ), $redirect ) ); exit; }
	try {
		if ( 'general' === $scope ) {
			$container = qalam_060_general_quiz_container();
			$course_id = $container['course_id']; $topic_id = $container['topic_id'];
		} else {
			$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
			$topic_id  = isset( $_POST['topic_id'] ) ? absint( $_POST['topic_id'] ) : 0;
			if ( ! $course_id || ! $topic_id || (int) wp_get_post_parent_id( $topic_id ) !== $course_id || ! tutor_utils()->can_user_manage( 'course', $course_id ) ) {
				throw new RuntimeException( 'اختار دورة وقسم صحيحين.' );
			}
		}
		$builder = new \TUTOR\QuizBuilder( false );
		$result = $builder->save_quiz( $topic_id, array( 'post_title'=>$title, 'post_content'=>'', 'quiz_option'=>array(), 'questions'=>array() ) );
		if ( empty( $result->success ) || empty( $result->data ) ) { throw new RuntimeException( 'تعذر إنشاء الاختبار.' ); }
		$quiz_id = absint( $result->data );
		if ( 'general' === $scope ) { update_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, '1' ); }
		wp_safe_redirect( add_query_arg( 'qalam_quiz_created', $quiz_id, $redirect ) ); exit;
	} catch ( \Throwable $e ) {
		wp_safe_redirect( add_query_arg( 'qalam_quiz_error', rawurlencode( $e->getMessage() ), $redirect ) ); exit;
	}
}
add_action( 'admin_post_qalam_060_create_quiz', 'qalam_060_create_quiz' );

/** Arabic names for built-in certificate templates. */
function qalam_060_certificate_template_names() {
	return array(
		'default'=>'الافتراضي','template_1'=>'تجريدي أفقي','template_2'=>'تجريدي رأسي','template_3'=>'مزخرف أفقي','template_4'=>'مزخرف رأسي','template_5'=>'هندسي أفقي','template_6'=>'هندسي رأسي','template_7'=>'بسيط أفقي','template_8'=>'بسيط رأسي','template_9'=>'عائم أفقي','template_10'=>'عائم رأسي','template_11'=>'مخطط أفقي','template_12'=>'مخطط رأسي',
	);
}

/** Certificate management page backed by Tutor Certificate's native engine. */
function qalam_060_render_certificate_builder() {
	if ( ! current_user_can( 'manage_tutor' ) ) { wp_die( 'غير مسموح.' ); }
	?>
	<div class="wrap qalam-050-wrap qalam-060-wrap" dir="rtl"><div class="qalam-050-hero"><div><span class="qalam-050-eyebrow">Qalam LMS</span><h1>منشئ شهادات التقدير</h1><p>اختار تصميم الشهادة الافتراضي أو خصص قالب لدورة معينة باستخدام محرك الشهادات الموجود بالفعل داخل Qalam Pro.</p></div></div>
	<?php if ( ! class_exists( '\TUTOR_CERT\Certificate' ) || ! function_exists( 'TUTOR_CERT' ) ) : ?>
		<div class="qalam-050-panel"><h2>ملحق شهادات قلم غير مفعل</h2><p>فعّل «شهادات قلم» من صفحة الملحقات الأول، وبعدها ارجع هنا علشان تظهر القوالب.</p><a class="button button-primary" href="<?php echo esc_url( admin_url('admin.php?page=tutor-addons') ); ?>">فتح الملحقات</a></div>
	<?php else :
		$cert = new \TUTOR_CERT\Certificate( true );
		$templates = $cert->get_templates( false, true );
		$names = qalam_060_certificate_template_names();
		$options = get_option( 'tutor_option', array() ); $options = is_array($options)?$options:array();
		$current = sanitize_key( (string) ( $options['certificate_template'] ?? 'default' ) );
		$courses = get_posts( array( 'post_type'=>tutor()->course_post_type,'post_status'=>array('publish','draft','private'),'posts_per_page'=>100,'orderby'=>'title','order'=>'ASC','meta_query'=>array(array('key'=>QALAM_GENERAL_COURSE_META,'compare'=>'NOT EXISTS')) ) );
		?>
		<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>"><input type="hidden" name="action" value="qalam_060_save_certificate_builder"><?php wp_nonce_field('qalam_060_save_certificate_builder','qalam_cert_nonce'); ?>
		<section class="qalam-050-panel"><div class="qalam-050-section-head"><div><h2>القالب الافتراضي</h2><p>القالب ده بيتستخدم لأي دورة مفيش لها قالب مخصص.</p></div></div><div class="qalam-certificate-grid">
		<?php foreach ( $templates as $key=>$template ) : $local = trailingslashit( TUTOR_CERT()->url . 'templates/' . $key ) . 'background.png'; ?>
			<label class="qalam-certificate-card"><input type="radio" name="certificate_template" value="<?php echo esc_attr($key); ?>" <?php checked($current,$key); ?>><span class="qalam-cert-preview"><img src="<?php echo esc_url($local); ?>" alt=""></span><strong><?php echo esc_html($names[$key] ?? $template['name']); ?></strong><small><?php echo 'portrait' === ($template['orientation']??'') ? 'رأسي' : 'أفقي'; ?></small></label>
		<?php endforeach; ?></div></section>
		<section class="qalam-050-panel"><div class="qalam-050-section-head"><div><h2>قالب مخصص لدورة</h2><p>اختياري: اختار دورة وقالب علشان يتطبق عليها بس.</p></div></div><div class="qalam-050-form"><label><span>الدورة</span><select name="course_id"><option value="">بدون تخصيص</option><?php foreach($courses as $course): ?><option value="<?php echo esc_attr($course->ID); ?>"><?php echo esc_html($course->post_title); ?></option><?php endforeach;?></select></label><label><span>القالب</span><select name="course_template"><option value="">استخدم الافتراضي</option><?php foreach($templates as $key=>$template): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($names[$key]??$template['name']); ?></option><?php endforeach;?></select></label><button class="button button-primary qalam-050-primary" type="submit">حفظ إعدادات الشهادات</button></div></section>
		</form>
	<?php endif; ?></div>
	<?php
}

/** Save certificate builder selection without duplicating certificate generation logic. */
function qalam_060_save_certificate_builder() {
	if ( ! current_user_can('manage_tutor') ) { wp_die('غير مسموح.'); }
	check_admin_referer('qalam_060_save_certificate_builder','qalam_cert_nonce');
	$template = isset($_POST['certificate_template']) ? sanitize_key(wp_unslash($_POST['certificate_template'])) : 'default';
	$allowed = array_keys(qalam_060_certificate_template_names());
	if (!in_array($template,$allowed,true)) { $template='default'; }
	$options=get_option('tutor_option',array()); $options=is_array($options)?$options:array(); $options['certificate_template']=$template; update_option('tutor_option',$options,false);
	$course_id=isset($_POST['course_id'])?absint($_POST['course_id']):0; $course_template=isset($_POST['course_template'])?sanitize_key(wp_unslash($_POST['course_template'])):'';
	if($course_id && tutor_utils()->can_user_manage('course',$course_id)) { if($course_template && in_array($course_template,$allowed,true)){ update_post_meta($course_id,'tutor_course_certificate_template',$course_template); } else { delete_post_meta($course_id,'tutor_course_certificate_template'); } }
	wp_safe_redirect( function_exists('qalam_220_manage_url') ? qalam_220_manage_url('certificates_suite',array('view'=>'builder','saved'=>1)) : add_query_arg('saved','1',admin_url('admin.php?page=qalam-certificate-builder')) ); exit;
}
add_action('admin_post_qalam_060_save_certificate_builder','qalam_060_save_certificate_builder');

/** Question type labels from Tutor's actual internal slugs. */
function qalam_060_question_type_labels() {
	return array(
		'true_false'=>'صح / خطأ','single_choice'=>'اختيار واحد','multiple_choice'=>'اختيارات متعددة','open_ended'=>'مقالي','fill_in_the_blank'=>'أكمل الفراغات','short_answer'=>'إجابة قصيرة','matching'=>'توصيل','image_matching'=>'مطابقة بالصور','image_answering'=>'إجابة بالصورة','ordering'=>'ترتيب','draw_image'=>'تحديد على الصورة','scale'=>'مدى / Range','pin_image'=>'تحديد نقطة','coordinates'=>'رسم بياني','puzzle'=>'لغز / Puzzle',
	);
}

/** Question Bank page: categories and existing cb-question records only. */
function qalam_060_render_question_bank() {
	if ( ! current_user_can('manage_tutor_instructor') ) { wp_die('غير مسموح.'); }
	$search = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
	$term_id = isset($_GET['question_category']) ? absint($_GET['question_category']) : 0;
	$args = array('post_type'=>'cb-question','post_status'=>array('publish','draft','private'),'posts_per_page'=>100,'orderby'=>'modified','order'=>'DESC','s'=>$search);
	if($term_id){$args['tax_query']=array(array('taxonomy'=>QALAM_QUESTION_CATEGORY_TAX,'field'=>'term_id','terms'=>$term_id));}
	if(!current_user_can('manage_tutor')){$args['author']=get_current_user_id();}
	$questions=get_posts($args);
	$terms=get_terms(array('taxonomy'=>QALAM_QUESTION_CATEGORY_TAX,'hide_empty'=>false)); if(is_wp_error($terms)){$terms=array();}
	global $wpdb; $types=array(); if($questions){$ids=wp_list_pluck($questions,'ID');$ph=implode(',',array_fill(0,count($ids),'%d'));$sql=$wpdb->prepare("SELECT content_id,question_type FROM {$wpdb->prefix}tutor_quiz_questions WHERE content_id IN ($ph)",$ids);foreach($wpdb->get_results($sql) as $row){$types[(int)$row->content_id]=(string)$row->question_type;}}
	$labels=qalam_060_question_type_labels();
	?>
	<div class="wrap qalam-050-wrap qalam-060-wrap" dir="rtl"><div class="qalam-050-hero"><div><span class="qalam-050-eyebrow">Qalam LMS</span><h1>بنك الأسئلة</h1><p>بنك مستقل للأسئلة فقط، بتصنيفات أب وفرعية بأي عمق. بنك المحتوى يفضل مخصص للدروس والواجبات والمحتوى القابل لإعادة الاستخدام.</p></div><div class="qalam-050-count"><strong><?php echo esc_html(count($questions)); ?></strong><span>سؤال ظاهر</span></div></div>
	<div class="qalam-question-layout">
		<aside class="qalam-question-sidebar qalam-050-panel"><h2>التصنيفات</h2><a class="qalam-category-link <?php echo $term_id?'':'is-active'; ?>" href="<?php echo esc_url(admin_url('admin.php?page=qalam-question-bank')); ?>">كل الأسئلة</a><?php qalam_060_render_term_tree($terms,0,$term_id); ?>
		<hr><h3>إضافة تصنيف</h3><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="qalam_060_add_question_category"><?php wp_nonce_field('qalam_060_add_question_category','qalam_qcat_nonce'); ?><input type="text" name="name" required placeholder="اسم التصنيف"><select name="parent"><option value="0">بدون تصنيف أب</option><?php foreach($terms as $term): ?><option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option><?php endforeach; ?></select><button class="button button-primary" type="submit">إضافة التصنيف</button></form></aside>
		<main class="qalam-question-main"><section class="qalam-050-panel"><div class="qalam-050-section-head"><div><h2>الأسئلة</h2><p>الأسئلة الحالية مخزنة بمحرك قلم نفسه، لكن إدارتها هنا منفصلة عن بنك المحتوى.</p></div><form method="get"><input type="hidden" name="page" value="qalam-question-bank"><input type="search" class="qalam-050-search" name="q" value="<?php echo esc_attr($search); ?>" placeholder="ابحث في بنك الأسئلة..."></form></div>
		<div class="qalam-050-table-wrap"><table class="widefat striped qalam-050-table"><thead><tr><th>السؤال</th><th>النوع</th><th>التصنيف</th><th>آخر تعديل</th></tr></thead><tbody><?php if(!$questions):?><tr><td colspan="4">لسه مفيش أسئلة في القسم ده.</td></tr><?php else:foreach($questions as $q):$qterm_ids=wp_get_post_terms($q->ID,QALAM_QUESTION_CATEGORY_TAX,array('fields'=>'ids'));$current_qterm=!empty($qterm_ids)?(int)$qterm_ids[0]:0;?><tr><td><strong><?php echo esc_html($q->post_title ?: wp_trim_words(wp_strip_all_tags($q->post_content),12)); ?></strong></td><td><?php echo esc_html($labels[$types[$q->ID]??'']??($types[$q->ID]??'—')); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="qalam-inline-category-form"><input type="hidden" name="action" value="qalam_060_assign_question_category"><input type="hidden" name="question_id" value="<?php echo esc_attr($q->ID); ?>"><?php wp_nonce_field('qalam_060_assign_question_category_'.$q->ID,'qalam_assign_nonce'); ?><select name="term_id" onchange="this.form.submit()"><option value="0">غير مصنف</option><?php foreach($terms as $term):?><option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($current_qterm,$term->term_id); ?>><?php echo esc_html($term->name); ?></option><?php endforeach;?></select></form></td><td><?php echo esc_html(get_the_modified_date('', $q)); ?></td></tr><?php endforeach;endif;?></tbody></table></div></section></main>
	</div></div>
	<?php
}

/** Render a simple hierarchical category tree. */
function qalam_060_render_term_tree($terms,$parent=0,$active=0){$children=array_filter($terms,static fn($t)=>(int)$t->parent===(int)$parent);if(!$children)return;echo '<ul class="qalam-category-tree">';foreach($children as $term){$url=add_query_arg(array('page'=>'qalam-question-bank','question_category'=>$term->term_id),admin_url('admin.php'));echo '<li><a class="qalam-category-link '.((int)$active===(int)$term->term_id?'is-active':'').'" href="'.esc_url($url).'">'.esc_html($term->name).'<span>'.esc_html($term->count).'</span></a>';qalam_060_render_term_tree($terms,$term->term_id,$active);echo '</li>';}echo '</ul>';}

/** Assign one Question Bank category to an existing question. */
function qalam_060_assign_question_category(){
	$question_id=isset($_POST['question_id'])?absint($_POST['question_id']):0;
	if(!$question_id||!current_user_can('manage_tutor_instructor')){wp_die('غير مسموح.');}
	check_admin_referer('qalam_060_assign_question_category_'.$question_id,'qalam_assign_nonce');
	$term_id=isset($_POST['term_id'])?absint($_POST['term_id']):0;
	if('cb-question'!==get_post_type($question_id)){wp_die('السؤال غير صالح.');}
	wp_set_object_terms($question_id,$term_id?array($term_id):array(),QALAM_QUESTION_CATEGORY_TAX,false);
	wp_safe_redirect(wp_get_referer()?:admin_url('admin.php?page=qalam-question-bank'));exit;
}
add_action('admin_post_qalam_060_assign_question_category','qalam_060_assign_question_category');

/** Add a hierarchical Question Bank category. */
function qalam_060_add_question_category(){if(!current_user_can('manage_tutor_instructor')){wp_die('غير مسموح.');}check_admin_referer('qalam_060_add_question_category','qalam_qcat_nonce');$name=isset($_POST['name'])?sanitize_text_field(wp_unslash($_POST['name'])):'';$parent=isset($_POST['parent'])?absint($_POST['parent']):0;if($name){wp_insert_term($name,QALAM_QUESTION_CATEGORY_TAX,array('parent'=>$parent));}wp_safe_redirect(admin_url('admin.php?page=qalam-question-bank'));exit;}
add_action('admin_post_qalam_060_add_question_category','qalam_060_add_question_category');
