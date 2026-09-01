<?php
/**
 * Qalam LMS 0.18.0 — unified feature catalog and SaaS-ready feature gates.
 *
 * Goals:
 * - Put optional product capabilities in one Add-ons catalog.
 * - Hide optional capability shortcuts from the Tutor sidebar while keeping their pages routable.
 * - Give every capability a stable machine key for future SaaS plans.
 * - Enforce local enabled state + future SaaS entitlement server-side.
 *
 * Security fixes are never represented as optional capabilities and cannot be disabled here.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_180_FEATURE_OPTION         = 'qalam_feature_states';
const QALAM_180_FEATURE_SCHEMA_OPTION  = 'qalam_feature_schema_version';
const QALAM_180_FEATURE_SCHEMA_VERSION = '0.18.0';

/** Human labels for catalog sections. */
function qalam_180_feature_categories(): array {
	return array(
		'exams'          => 'الاختبارات والأسئلة',
		'ai'             => 'الذكاء الاصطناعي',
		'video'          => 'الفيديو والمشاهدة',
		'teaching'       => 'التعليم والتفاعل',
		'live'           => 'الحصص المباشرة والتكاملات',
		'commerce'       => 'التجارة والاشتراكات',
		'instructors'    => 'المعلمون والمؤسسات',
		'reports'        => 'التقارير والمتابعة',
		'communication'  => 'التواصل والإشعارات',
		'account'        => 'الحساب والدخول',
		'integrations'   => 'تكاملات خارجية',
		'multilingual'   => 'اللغات والتوافق',
	);
}

/**
 * Qalam-owned/internal capabilities.
 * All are enabled by default on upgrade to preserve existing behavior.
 */
function qalam_180_internal_features(): array {
	return array(
		'question_bank' => array(
			'name' => 'بنك الأسئلة', 'description' => 'إدارة بنك أسئلة مستقل بالتصنيفات والبحث والتعديل والحذف والمعاينة.',
			'category' => 'exams', 'manage_page' => 'qalam-question-bank', 'icon' => '؟', 'depends' => array( 'content_bank' ),
		),
		'standalone_exams' => array(
			'name' => 'الاختبارات المستقلة', 'description' => 'إنشاء اختبارات بروابط مستقلة للطلاب والزوار بعيدًا عن واجهة الدورة.',
			'category' => 'exams', 'manage_page' => 'qalam-quiz-builder', 'icon' => 'اخ',
		),
		'randomized_exams' => array(
			'name' => 'الاختيار العشوائي للأسئلة', 'description' => 'اختيار عدد محدد من الأسئلة عشوائيًا من بنك الأسئلة حسب التصنيف والصعوبة.',
			'category' => 'exams', 'manage_page' => 'qalam-quiz-builder', 'icon' => 'ع', 'depends' => array( 'question_bank', 'standalone_exams' ),
		),
		'dynamic_exams' => array(
			'name' => 'الاختبارات الديناميكية', 'description' => 'إنشاء محاولة مستقلة بأسئلة مختلفة لكل طالب مع تقليل تكرار الأسئلة السابقة.',
			'category' => 'exams', 'manage_page' => 'qalam-quiz-builder', 'icon' => 'د', 'depends' => array( 'question_bank', 'standalone_exams' ),
		),
		'ai_question_generation' => array(
			'name' => 'توليد الأسئلة بالذكاء الاصطناعي', 'description' => 'إنشاء أسئلة آليًا من موضوع أو تعليمات باستخدام مزود الذكاء الاصطناعي المحدد.',
			'category' => 'ai', 'manage_page' => 'qalam-question-bank', 'icon' => 'AI', 'depends' => array( 'question_bank', 'ai_background_worker' ),
		),
		'pdf_question_generation' => array(
			'name' => 'الأسئلة من ملفات PDF', 'description' => 'استخراج أو إنشاء أسئلة من PDF مع دعم صور الأسئلة عند توفرها.',
			'category' => 'ai', 'manage_page' => 'qalam-question-bank', 'icon' => 'PDF', 'depends' => array( 'ai_question_generation' ),
		),
		'ai_background_worker' => array(
			'name' => 'المعالجة الخلفية للذكاء الاصطناعي', 'description' => 'تنفيذ دفعات التوليد في الخلفية مع متابعة التقدم والاستكمال عند الانقطاع.',
			'category' => 'ai', 'manage_page' => 'qalam-question-bank', 'icon' => '⚙',
		),
		'qalam_video_player' => array(
			'name' => 'مشغل فيديو قلم', 'description' => 'مشغل الفيديو المخصص لقلم مع شريط تقدم وصوت وسرعة وإعدادات وملء الشاشة.',
			'category' => 'video', 'manage_page' => 'qalam-video-ads', 'icon' => '▶',
		),
		'video_subtitles' => array(
			'name' => 'ترجمة الفيديو', 'description' => 'إضافة ملفات ترجمة VTT/SRT وتشغيلها وإيقافها من مشغل قلم.',
			'category' => 'video', 'manage_page' => 'qalam-video-ads', 'icon' => 'CC', 'depends' => array( 'qalam_video_player' ),
		),
		'video_ads' => array(
			'name' => 'إعلانات الفيديو والصور', 'description' => 'عرض إعلانات صورة أو فيديو داخل الدروس مع توقيتات وتخطي وتتبع المشاهدة.',
			'category' => 'video', 'manage_page' => 'qalam-video-ads', 'icon' => 'إع', 'depends' => array( 'qalam_video_player' ),
		),
		'student_analytics' => array(
			'name' => 'ملف الطالب المتقدم', 'description' => 'عرض دورات الطالب والتقدم والاختبارات والدرجات والشهادات للمدير.',
			'category' => 'reports', 'manage_url' => 'users.php', 'icon' => 'ط',
		),
		'certificate_builder' => array(
			'name' => 'منشئ الشهادات', 'description' => 'اختيار وإدارة قوالب الشهادات من واجهة قلم.',
			'category' => 'teaching', 'manage_page' => 'qalam-certificate-builder', 'icon' => 'ش', 'depends' => array( 'certificates' ),
		),

		'instructor_marketplace' => array(
			'name' => 'سوق المعلمين', 'description' => 'السماح لأكثر من معلم ببيع دوراته مع إدارة المعلمين والأرباح وطلبات السحب.',
			'category' => 'instructors', 'manage_page' => 'tutor-instructors', 'icon' => 'مـ', 'option_key'=>'enable_course_marketplace', 'option_default'=>false,
		),
		'gift_courses' => array(
			'name' => 'إهداء الدورات', 'description' => 'السماح بشراء دورة كهدية وإرسالها لمستخدم آخر مع ربطها بحالة الطلب.',
			'category' => 'commerce', 'manage_page' => 'tutor_settings', 'icon' => 'هـ', 'option_key'=>'enable_gift_course', 'option_default'=>false,
		),
		'lesson_notes' => array(
			'name' => 'ملاحظات الدروس', 'description' => 'تمكين الطالب من حفظ ملاحظاته الخاصة أثناء مشاهدة الدروس.',
			'category' => 'teaching', 'manage_page' => 'tutor_settings', 'icon' => 'م', 'option_key'=>'enable_lesson_notes', 'option_default'=>true,
		),
		'progress_reset' => array(
			'name' => 'إعادة ضبط تقدم الطالب', 'description' => 'السماح للمخولين بإعادة ضبط تقدم طالب في دورة عند الحاجة.',
			'category' => 'reports', 'manage_page' => 'tutor_settings', 'icon' => '↻', 'option_key'=>'instructor_can_reset_course_progress', 'option_default'=>false,
		),
		'email_update' => array(
			'name' => 'تحديث البريد الإلكتروني', 'description' => 'السماح للمستخدم بتغيير بريده الإلكتروني من حسابه وفق ضوابط قلم.',
			'category' => 'account', 'manage_page' => 'tutor_settings', 'icon' => '@', 'option_key'=>'enable_change_email', 'option_default'=>false,
		),
	);
}

/** Stable SaaS keys for packaged Tutor Pro add-ons. */
function qalam_180_packaged_features(): array {
	return array(
		'course_bundles'       => array( 'addon'=>'course-bundle', 'category'=>'commerce', 'manage_page'=>'course-bundle' ),
		'subscriptions'        => array( 'addon'=>'subscription', 'category'=>'commerce', 'manage_page'=>'tutor-subscriptions' ),
		'content_bank'         => array( 'addon'=>'content-bank', 'category'=>'teaching', 'manage_page'=>'tutor-content-bank' ),
		'social_login'         => array( 'addon'=>'social-login', 'category'=>'account', 'manage_page'=>'tutor_settings' ),
		'content_drip'         => array( 'addon'=>'content-drip', 'category'=>'teaching', 'manage_page'=>'tutor_settings' ),
		'multi_instructor'     => array( 'addon'=>'tutor-multi-instructors', 'category'=>'instructors', 'manage_page'=>'tutor_settings' ),
		'assignments'          => array( 'addon'=>'tutor-assignments', 'category'=>'teaching', 'manage_page'=>'tutor-assignments' ),
		'course_preview'       => array( 'addon'=>'tutor-course-preview', 'category'=>'teaching', 'manage_page'=>'tutor_settings' ),
		'course_attachments'   => array( 'addon'=>'tutor-course-attachments', 'category'=>'teaching', 'manage_page'=>'tutor_settings' ),
		'google_meet'          => array( 'addon'=>'google-meet', 'category'=>'live', 'manage_page'=>'google-meet' ),
		'advanced_reports'     => array( 'addon'=>'tutor-report', 'category'=>'reports', 'manage_page'=>'tutor_report' ),
		'email_notifications'  => array( 'addon'=>'tutor-email', 'category'=>'communication', 'manage_page'=>'tutor_settings' ),
		'calendar'             => array( 'addon'=>'calendar', 'category'=>'teaching', 'manage_page'=>'tutor_settings' ),
		'notifications'        => array( 'addon'=>'tutor-notifications', 'category'=>'communication', 'manage_page'=>'tutor_settings' ),
		'google_classroom'     => array( 'addon'=>'google-classroom', 'category'=>'live', 'manage_page'=>'tutor-google-classroom' ),
		'zoom'                 => array( 'addon'=>'tutor-zoom', 'category'=>'live', 'manage_page'=>'tutor_zoom' ),
		'quiz_import_export'   => array( 'addon'=>'quiz-import-export', 'category'=>'exams', 'manage_page'=>'tutor_settings' ),
		'manual_enrollments'   => array( 'addon'=>'enrollments', 'category'=>'teaching', 'manage_page'=>'enrollments' ),
		'certificates'         => array( 'addon'=>'tutor-certificate', 'category'=>'teaching', 'manage_page'=>'qalam-certificate-builder' ),
		'gradebook'            => array( 'addon'=>'gradebook', 'category'=>'reports', 'manage_page'=>'tutor_gradebook' ),
		'course_prerequisites' => array( 'addon'=>'tutor-prerequisites', 'category'=>'teaching', 'manage_page'=>'tutor_settings' ),
		'buddypress'           => array( 'addon'=>'buddypress', 'category'=>'integrations', 'manage_page'=>'tutor_settings', 'external_requires_any'=>array( 'buddypress/bp-loader.php'=>'BuddyPress', 'buddyboss-platform/bp-loader.php'=>'BuddyBoss Platform' ) ),
		'wc_subscriptions'     => array( 'addon'=>'wc-subscriptions', 'category'=>'commerce', 'manage_page'=>'tutor_settings', 'external_requires'=>array( 'woocommerce/woocommerce.php'=>'WooCommerce', 'woocommerce-subscriptions/woocommerce-subscriptions.php'=>'WooCommerce Subscriptions' ) ),
		'pmpro'                => array( 'addon'=>'pmpro', 'category'=>'commerce', 'manage_page'=>'tutor_settings', 'external_requires'=>array( 'paid-memberships-pro/paid-memberships-pro.php'=>'Paid Memberships Pro' ) ),
		'restrict_content_pro' => array( 'addon'=>'restrict-content-pro', 'category'=>'commerce', 'manage_page'=>'tutor_settings', 'external_requires'=>array( 'restrict-content-pro/restrict-content-pro.php'=>'Restrict Content Pro' ) ),
		'weglot'               => array( 'addon'=>'tutor-weglot', 'category'=>'multilingual', 'manage_page'=>'tutor_settings', 'external_requires'=>array( 'weglot/weglot.php'=>'Weglot' ) ),
		'wpml'                 => array( 'addon'=>'tutor-wpml', 'category'=>'multilingual', 'manage_page'=>'tutor_settings', 'external_requires'=>array( 'sitepress-multilingual-cms/sitepress.php'=>'WPML' ) ),
		'h5p'                  => array( 'addon'=>'h5p', 'category'=>'teaching', 'manage_page'=>'tutor_h5p' ),
	);
}

/** Build static definitions independent of current state. */
function qalam_180_feature_definitions(): array {
	$defs = array();
	foreach ( qalam_180_internal_features() as $key => $row ) {
		$row['key'] = $key;
		$row['type'] = 'internal';
		$row['default_enabled'] = true;
		$defs[ $key ] = $row;
	}
	$copy = function_exists( 'qalam_050_addon_copy' ) ? qalam_050_addon_copy() : array();
	foreach ( qalam_180_packaged_features() as $key => $row ) {
		$slug = $row['addon'];
		$row['key'] = $key;
		$row['type'] = 'packaged';
		$row['name'] = isset( $copy[ $slug ][0] ) ? $copy[ $slug ][0] : ucwords( str_replace( '-', ' ', $slug ) );
		$row['description'] = isset( $copy[ $slug ][1] ) ? $copy[ $slug ][1] : 'ملحق اختياري ضمن Qalam LMS.';
		$row['icon'] = '＋';
		$row['default_enabled'] = false;
		$defs[ $key ] = $row;
	}
	return apply_filters( 'qalam_feature_definitions', $defs );
}

/** Future SaaS entitlement seam. SaaS may return bool or structured access data. */
function qalam_feature_access( string $feature_key ): array {
	$default = array( 'allowed'=>true, 'reason'=>'', 'plan'=>'', 'source'=>'local' );
	$access = apply_filters( 'qalam_saas_feature_access', $default, $feature_key );
	if ( is_bool( $access ) ) {
		$access = array( 'allowed'=>$access, 'reason'=>$access ? '' : 'الميزة غير متاحة في الباقة الحالية.', 'plan'=>'', 'source'=>'saas' );
	}
	$access = is_array( $access ) ? array_merge( $default, $access ) : $default;
	$access['allowed'] = ! empty( $access['allowed'] );
	return $access;
}

/** Seed only missing internal feature states; never turn an existing feature off during upgrade. */
function qalam_180_seed_feature_states(): void {
	$states = get_option( QALAM_180_FEATURE_OPTION, array() );
	$states = is_array( $states ) ? $states : array();
	$changed = false;
	foreach ( qalam_180_internal_features() as $key => $row ) {
		if ( ! array_key_exists( $key, $states ) ) {
			$states[ $key ] = 1;
			$changed = true;
		}
	}
	if ( $changed ) {
		update_option( QALAM_180_FEATURE_OPTION, $states, false );
	}
	update_option( QALAM_180_FEATURE_SCHEMA_OPTION, QALAM_180_FEATURE_SCHEMA_VERSION, false );
}
add_action( 'admin_init', 'qalam_180_seed_feature_states', 5 );

function qalam_180_internal_state( string $key ): bool {
	$defs = qalam_180_internal_features();
	if ( isset( $defs[ $key ]['option_key'] ) ) {
		// Read the stored local choice directly. Runtime option filters can deny a SaaS-locked
		// feature without erasing the administrator's saved on/off preference.
		$option_key = (string) $defs[ $key ]['option_key'];
		$default = ! empty( $defs[ $key ]['option_default'] );
		$options = get_option( 'tutor_option', array() );
		$options = is_array( $options ) ? $options : array();
		if ( ! array_key_exists( $option_key, $options ) ) { return $default; }
		$value = $options[ $option_key ];
		if ( 'on' === $value ) { return true; }
		if ( 'off' === $value ) { return false; }
		return (bool) $value;
	}
	$states = get_option( QALAM_180_FEATURE_OPTION, array() );
	$states = is_array( $states ) ? $states : array();
	return ! array_key_exists( $key, $states ) || ! empty( $states[ $key ] );
}

/**
 * Enforce SaaS access on Tutor option-backed capabilities at runtime without mutating
 * the site's local preference. Tutor Utils applies a filter named after each option key.
 */
function qalam_180_option_entitlement_filter( $value ) {
	$option_key = (string) current_filter();
	foreach ( qalam_180_internal_features() as $feature_key => $def ) {
		if ( (string) ( $def['option_key'] ?? '' ) !== $option_key ) { continue; }
		$access = qalam_feature_access( (string) $feature_key );
		if ( empty( $access['allowed'] ) ) { return false; }
		// Since 0.20 a product-level add-on may own several old implementation keys.
		// Its single switch becomes the source of truth for option-backed children too.
		if ( function_exists( 'qalam_200_group_for_child' ) && function_exists( 'qalam_200_group_enabled' ) ) {
			$group_key = qalam_200_group_for_child( (string) $feature_key );
			if ( $group_key ) { return qalam_200_group_enabled( $group_key ); }
		}
		return $value;
	}
	return $value;
}
function qalam_180_register_option_entitlement_filters(): void {
	foreach ( qalam_180_internal_features() as $def ) {
		$option_key = sanitize_key( (string) ( $def['option_key'] ?? '' ) );
		if ( $option_key ) { add_filter( $option_key, 'qalam_180_option_entitlement_filter', PHP_INT_MAX ); }
	}
}
qalam_180_register_option_entitlement_filters();

/** Return the real state for a packaged add-on using Qalam's bundled Pro registry. */
function qalam_180_packaged_state( string $addon_slug ): array {
	$registry = function_exists( 'qalam_060_addon_registry' ) ? qalam_060_addon_registry() : ( function_exists( 'qalam_050_addon_registry' ) ? qalam_050_addon_registry() : array() );
	return isset( $registry[ $addon_slug ] ) && is_array( $registry[ $addon_slug ] ) ? $registry[ $addon_slug ] : array();
}

/** Canonical feature gate used by current code and future SaaS enforcement. */
function qalam_feature_enabled( string $feature_key, array $seen = array() ): bool {
	$defs = qalam_180_feature_definitions();
	if ( ! isset( $defs[ $feature_key ] ) ) {
		return false;
	}
	if ( isset( $seen[ $feature_key ] ) ) {
		return false;
	}
	$seen[ $feature_key ] = true;
	$access = qalam_feature_access( $feature_key );
	if ( ! $access['allowed'] ) {
		return false;
	}
	$def = $defs[ $feature_key ];
	$group_key = function_exists( 'qalam_200_group_for_child' ) ? qalam_200_group_for_child( $feature_key ) : '';
	$cloud_managed = function_exists( 'qalam_290_cloud_managed' ) && qalam_290_cloud_managed();
	if ( $group_key && function_exists( 'qalam_200_group_enabled' ) && ! $cloud_managed ) {
		// A grouped service is deliberately all-or-nothing. Old per-child states are
		// retained only as migration/compatibility data and no longer fragment runtime.
		$enabled = qalam_200_group_enabled( $group_key );
	} elseif ( 'packaged' === $def['type'] ) {
		$state = qalam_180_packaged_state( (string) $def['addon'] );
		$enabled = ! empty( $state['enabled'] );
	} else {
		$enabled = qalam_180_internal_state( $feature_key );
	}
	if ( ! $enabled ) {
		return false;
	}
	foreach ( (array) ( $def['depends'] ?? array() ) as $dependency ) {
		if ( ! qalam_feature_enabled( (string) $dependency, $seen ) ) {
			return false;
		}
	}
	return (bool) apply_filters( 'qalam_feature_enabled', true, $feature_key, $def );
}

function qalam_180_feature_manage_url( array $def ): string {
	if ( ! empty( $def['manage_url'] ) ) {
		return admin_url( ltrim( (string) $def['manage_url'], '/' ) );
	}
	if ( ! empty( $def['manage_page'] ) ) {
		return admin_url( 'admin.php?page=' . rawurlencode( (string) $def['manage_page'] ) );
	}
	return '';
}

/** Resolve external plugin requirements without bootstrapping a disabled add-on. */
function qalam_180_external_missing( array $def ): array {
	if ( ! function_exists( 'is_plugin_active' ) ) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
	$missing = array();
	foreach ( (array) ( $def['external_requires'] ?? array() ) as $plugin_file => $label ) {
		if ( ! is_plugin_active( (string) $plugin_file ) ) { $missing[ (string) $plugin_file ] = (string) $label; }
	}
	$any = (array) ( $def['external_requires_any'] ?? array() );
	if ( $any ) {
		$found = false;
		foreach ( $any as $plugin_file => $label ) { if ( is_plugin_active( (string) $plugin_file ) ) { $found = true; break; } }
		if ( ! $found ) { $missing['__any__'] = implode( ' أو ', array_values( $any ) ); }
	}
	return $missing;
}

/** Complete catalog with live state/dependency/SaaS information. */
function qalam_feature_catalog(): array {
	$defs = qalam_180_feature_definitions();
	$catalog = array();
	foreach ( $defs as $key => $def ) {
		$access = qalam_feature_access( $key );
		$missing = array();
		$error = array();
		if ( 'packaged' === $def['type'] ) {
			$state = qalam_180_packaged_state( (string) $def['addon'] );
			$locally_enabled = ! empty( $state['enabled'] );
			$missing = array_replace( qalam_180_external_missing( $def ), (array) ( $state['missing'] ?? array() ) );
			$error = (array) ( $state['error'] ?? array() );
			if ( ! empty( $state['name'] ) ) { $def['name'] = $state['name']; }
			if ( ! empty( $state['description'] ) ) { $def['description'] = $state['description']; }
			if ( ! empty( $state['icon_url'] ) ) { $def['icon_url'] = $state['icon_url']; }
			elseif ( function_exists( 'qalam_060_addon_icon_url' ) ) { $def['icon_url'] = qalam_060_addon_icon_url( (string) $def['addon'] ); }
		} else {
			$locally_enabled = qalam_180_internal_state( $key );
		}
		$dependency_labels = array();
		foreach ( (array) ( $def['depends'] ?? array() ) as $dependency ) {
			if ( ! qalam_feature_enabled( (string) $dependency ) ) {
				$dependency_labels[] = isset( $defs[ $dependency ]['name'] ) ? $defs[ $dependency ]['name'] : $dependency;
			}
		}
		$effective = qalam_feature_enabled( $key );
		$status = 'متوقف'; $status_key = 'disabled';
		if ( ! $access['allowed'] ) { $status = 'غير متاح في الباقة'; $status_key = 'locked'; }
		elseif ( ! empty( $error ) ) { $status = 'خطأ أثناء التشغيل'; $status_key = 'error'; }
		elseif ( ! empty( $missing ) || ! empty( $dependency_labels ) ) { $status = 'ينقصه متطلب'; $status_key = 'dependency'; }
		elseif ( $effective ) { $status = 'مفعل'; $status_key = 'enabled'; }
		$def['enabled'] = $locally_enabled;
		$def['effective_enabled'] = $effective;
		$def['access'] = $access;
		$def['missing'] = $missing;
		$def['dependency_labels'] = $dependency_labels;
		$def['error'] = $error;
		$def['status'] = $status;
		$def['status_key'] = $status_key;
		$def['manage_url_resolved'] = qalam_180_feature_manage_url( $def );
		$catalog[ $key ] = $def;
	}
	return apply_filters( 'qalam_feature_catalog', $catalog );
}

/** Safe packaged add-on state transition, preserving legacy hook compatibility. */
function qalam_180_set_packaged_addon( string $addon_slug, bool $enable ) {
	$registry = function_exists( 'qalam_050_addon_registry' ) ? qalam_050_addon_registry() : array();
	if ( ! isset( $registry[ $addon_slug ] ) ) {
		return new WP_Error( 'missing_addon', 'الملحق غير موجود في حزمة قلم.' );
	}
	$addon = $registry[ $addon_slug ];
	if ( $enable && ! empty( $addon['missing'] ) ) {
		return new WP_Error( 'missing_dependency', 'ثبت المتطلب الخارجي أولًا: ' . implode( '، ', array_values( $addon['missing'] ) ) );
	}
	$config = get_option( 'tutor_addons_config', array() );
	$config = is_array( $config ) ? $config : array();
	$previous = $config;
	$key = (string) $addon['basename'];
	$legacy_key = preg_replace( '#^[^/]+/addons/#', 'tutor-pro/addons/', $key );
	$state = tutor_utils()->get_addon_config( $key );
	$state = is_array( $state ) ? $state : array();
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
	} catch ( Throwable $e ) {
		update_option( 'tutor_addons_config', $previous, false );
		return new WP_Error( 'toggle_failed', 'حصل خطأ أثناء تغيير حالة الملحق واتعمل تراجع تلقائي: ' . $e->getMessage() );
	}
	return true;
}

/** One endpoint for both bundled add-ons and Qalam internal modules. */
function qalam_180_toggle_feature(): void {
	check_ajax_referer( 'qalam_180_toggle_feature', 'nonce' );
	if ( ! current_user_can( 'manage_tutor' ) ) {
		wp_send_json_error( array( 'message'=>'معندكش صلاحية لتغيير حالة الملحقات.' ), 403 );
	}
	$key = sanitize_key( (string) ( $_POST['feature'] ?? '' ) );
	$enable = isset( $_POST['enable'] ) && '1' === (string) wp_unslash( $_POST['enable'] );
	$defs = qalam_180_feature_definitions();
	if ( ! isset( $defs[ $key ] ) ) {
		wp_send_json_error( array( 'message'=>'الميزة غير موجودة في كتالوج قلم.' ), 404 );
	}
	$def = $defs[ $key ];
	$access = qalam_feature_access( $key );
	if ( $enable && ! $access['allowed'] ) {
		wp_send_json_error( array( 'message'=>$access['reason'] ?: 'الميزة غير متاحة في الباقة الحالية.' ), 403 );
	}
	if ( $enable ) {
		foreach ( (array) ( $def['depends'] ?? array() ) as $dependency ) {
			if ( ! qalam_feature_enabled( (string) $dependency ) ) {
				wp_send_json_error( array( 'message'=>'فعّل المتطلب الأول: ' . ( $defs[ $dependency ]['name'] ?? $dependency ) ), 422 );
			}
		}
	}
	if ( 'packaged' === $def['type'] ) {
		$result = qalam_180_set_packaged_addon( (string) $def['addon'], $enable );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message'=>$result->get_error_message() ), 422 );
		}
	} else {
		if ( ! empty( $def['option_key'] ) && function_exists( 'tutor_utils' ) ) {
			tutor_utils()->update_option( (string) $def['option_key'], $enable ? 1 : 0 );
		} else {
			$states = get_option( QALAM_180_FEATURE_OPTION, array() );
			$states = is_array( $states ) ? $states : array();
			$states[ $key ] = $enable ? 1 : 0;
			update_option( QALAM_180_FEATURE_OPTION, $states, false );
		}
	}
	wp_send_json_success( array( 'message'=>$enable ? 'تم تفعيل الميزة.' : 'تم تعطيل الميزة.' ) );
}
add_action( 'wp_ajax_qalam_180_toggle_feature', 'qalam_180_toggle_feature' );

// Retire the pre-catalog Qalam toggle endpoint so SaaS locks cannot be bypassed
// by calling the old AJAX action directly.
remove_action( 'wp_ajax_qalam_050_toggle_addon', 'qalam_050_toggle_addon' );

/** Map a bundled add-on basename/slug back to its stable SaaS feature key. */
function qalam_180_feature_key_for_addon_reference( string $reference ): string {
	$reference = str_replace( '\\', '/', $reference );
	foreach ( qalam_180_packaged_features() as $feature_key => $def ) {
		$slug = (string) ( $def['addon'] ?? '' );
		if ( $slug && ( $reference === $slug || false !== strpos( $reference, '/addons/' . $slug . '/' ) ) ) {
			return (string) $feature_key;
		}
	}
	return '';
}

/**
 * Protect Tutor's legacy/native add-on toggle AJAX action too. The Qalam page does not
 * use it, but keeping this guard prevents an administrator-side request from enabling
 * a capability that the SaaS plan explicitly denies.
 */
function qalam_180_guard_native_addon_toggle(): void {
	$raw = isset( $_POST['addonFieldNames'] ) ? (string) wp_unslash( $_POST['addonFieldNames'] ) : '';
	if ( '' === $raw ) { return; }
	$form = json_decode( $raw, true );
	if ( ! is_array( $form ) ) { return; }
	foreach ( $form as $addon_reference => $enable ) {
		if ( ! $enable ) { continue; }
		$feature_key = qalam_180_feature_key_for_addon_reference( (string) $addon_reference );
		if ( ! $feature_key ) { continue; }
		$access = qalam_feature_access( $feature_key );
		if ( empty( $access['allowed'] ) ) {
			wp_send_json_error( array( 'message'=>$access['reason'] ?: 'الملحق غير متاح في الباقة الحالية.' ), 403 );
		}
	}
}
add_action( 'wp_ajax_addon_enable_disable', 'qalam_180_guard_native_addon_toggle', 0 );

/** Make the Add-ons item use the SaaS-ready Qalam catalog. */
function qalam_180_admin_menu_callback( $menu ) {
	if ( isset( $menu['group_three']['addons'] ) && is_array( $menu['group_three']['addons'] ) ) {
		$menu['group_three']['addons']['page_title'] = 'ملحقات قلم';
		$menu['group_three']['addons']['menu_title'] = 'الملحقات';
		$menu['group_three']['addons']['callback'] = 'qalam_180_render_addons';
	}
	return $menu;
}
add_filter( 'tutor_admin_menu', 'qalam_180_admin_menu_callback', PHP_INT_MAX );

/** Hide optional feature shortcuts from sidebar; pages remain registered and accessible through catalog Manage buttons. */
function qalam_180_hide_optional_sidebar_items(): void {
	$slugs = array(
		'qalam-question-bank', 'qalam-quiz-builder', 'qalam-certificate-builder', 'qalam-video-ads',
		'tutor-content-bank', 'tutor-assignments', 'tutor_gradebook', 'tutor_report', 'enrollments',
		'tutor_zoom', 'google-meet', 'tutor-google-classroom', 'tutor_h5p', 'tutor-subscriptions', 'tutor-instructors', 'tutor_withdraw_requests',
	);
	foreach ( $slugs as $slug ) {
		remove_submenu_page( 'tutor', $slug );
	}
	remove_submenu_page( 'tutor-pro', 'course-bundle' );
}
add_action( 'admin_menu', 'qalam_180_hide_optional_sidebar_items', PHP_INT_MAX );

/** Guard hidden admin pages and feature-specific actions against local/SaaS disabled access. */
function qalam_180_admin_page_feature_map(): array {
	return array(
		'qalam-question-bank'=>'question_bank', 'qalam-quiz-builder'=>'standalone_exams', 'qalam-certificate-builder'=>'certificate_builder',
		'qalam-student-profile'=>'student_analytics', 'tutor-content-bank'=>'content_bank', 'tutor-assignments'=>'assignments',
		'tutor_gradebook'=>'gradebook', 'tutor_report'=>'advanced_reports', 'enrollments'=>'manual_enrollments', 'tutor_zoom'=>'zoom',
		'google-meet'=>'google_meet', 'tutor-google-classroom'=>'google_classroom', 'tutor_h5p'=>'h5p',
		'tutor-subscriptions'=>'subscriptions', 'tutor-instructors'=>'instructor_marketplace', 'tutor_withdraw_requests'=>'instructor_marketplace', 'course-bundle'=>'course_bundles',
	);
}
function qalam_180_request_action_feature_map(): array {
	return array(
		'qalam_060_add_question_category'=>'question_bank', 'qalam_060_assign_question_category'=>'question_bank',
		'qalam_081_bank_bulk_delete'=>'question_bank', 'qalam_081_save_bank_question_basic'=>'question_bank',
		'qalam_060_create_quiz'=>'standalone_exams', 'qalam_070_create_general_quiz'=>'standalone_exams',
		'qalam_070_save_general_quiz'=>'standalone_exams', 'qalam_070_import_questions_to_quiz'=>'standalone_exams',
		'qalam_070_remove_quiz_question'=>'standalone_exams', 'qalam_081_delete_general_quiz'=>'standalone_exams',
		'qalam_081_save_public_requirements'=>'standalone_exams', 'qalam_080_random_fill_quiz'=>'randomized_exams',
		'qalam_080_save_dynamic_rules'=>'dynamic_exams', 'qalam_070_generate_questions'=>'ai_question_generation',
		'qalam_080_start_generation'=>'ai_question_generation', 'qalam_080_process_generation'=>'ai_background_worker',
		'qalam_080_resume_generation'=>'ai_background_worker', 'qalam_081_generation_worker_ping'=>'ai_background_worker',
		'qalam_060_save_certificate_builder'=>'certificate_builder', 'qalam_150_save_video_ad'=>'video_ads',
		'qalam_150_delete_video_ad'=>'video_ads', 'qalam_150_save_subtitle'=>'video_subtitles', 'qalam_160_video_ad_event'=>'video_ads',
		'tutor_change_email'=>'email_update', 'tutor_reset_student_course_progress'=>'progress_reset',
		'tutor_pro_save_lesson_note'=>'lesson_notes', 'tutor_pro_update_lesson_note'=>'lesson_notes',
		'tutor_pro_delete_lesson_note'=>'lesson_notes', 'tutor_pro_get_lesson_notes_html'=>'lesson_notes',
		'tutor_pro_get_single_lesson_note_html'=>'lesson_notes', 'tutor_pro_lesson_notes_load_more'=>'lesson_notes',
		'tutor_pro_gift_proceed_to_checkout'=>'gift_courses', 'tutor_pro_gift_enrollment'=>'gift_courses',
	);
}
function qalam_180_guard_feature_requests(): void {
	if ( isset( $_REQUEST['action'] ) ) {
		$action = sanitize_key( (string) wp_unslash( $_REQUEST['action'] ) );
		$map = qalam_180_request_action_feature_map();
		if ( isset( $map[ $action ] ) && ! qalam_feature_enabled( $map[ $action ] ) ) {
			$message = 'الميزة متوقفة أو غير متاحة في الباقة الحالية.';
			if ( wp_doing_ajax() ) { wp_send_json_error( array( 'message'=>$message ), 403 ); }
			wp_die( esc_html( $message ), 'Qalam LMS', array( 'response'=>403 ) );
		}
		if ( 'qalam_070_import_questions_to_quiz' === $action && ! qalam_feature_enabled( 'question_bank' ) ) {
			$message = 'بنك الأسئلة متوقف أو غير متاح في الباقة الحالية.';
			if ( wp_doing_ajax() ) { wp_send_json_error( array( 'message'=>$message ), 403 ); }
			wp_die( esc_html( $message ), 'Qalam LMS', array( 'response'=>403 ) );
		}
		if ( 'qalam_080_start_generation' === $action ) {
			$mode = sanitize_key( (string) ( $_POST['source_mode'] ?? '' ) );
			if ( 0 === strpos( $mode, 'pdf_' ) && ! qalam_feature_enabled( 'pdf_question_generation' ) ) {
				wp_send_json_error( array( 'message'=>'ميزة إنشاء الأسئلة من PDF متوقفة أو غير متاحة في الباقة.' ), 403 );
			}
		}
	}
	$page = isset( $_GET['page'] ) ? sanitize_key( (string) wp_unslash( $_GET['page'] ) ) : '';
	// Media administration is shared by two independently licensable features.
	// Keep the page reachable while either video ads or Qalam subtitles is available.
	if ( 'qalam-video-ads' === $page && ! qalam_feature_enabled( 'video_ads' ) && ! qalam_feature_enabled( 'video_subtitles' ) ) {
		wp_safe_redirect( add_query_arg( array( 'page'=>'tutor-addons', 'qalam_feature_unavailable'=>'video_media' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	$pages = qalam_180_admin_page_feature_map();
	if ( $page && isset( $pages[ $page ] ) && ! qalam_feature_enabled( $pages[ $page ] ) ) {
		wp_safe_redirect( add_query_arg( array( 'page'=>'tutor-addons', 'qalam_feature_unavailable'=>$pages[ $page ] ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
add_action( 'admin_init', 'qalam_180_guard_feature_requests', 1000 );

/** Block standalone/public feature routes when entitlement is off. */
function qalam_180_front_feature_guard(): void {
	$token = (string) get_query_var( 'qalam_exam_token' );
	if ( ( $token || ! empty( $_GET['qalam_general_quiz'] ) ) && ! qalam_feature_enabled( 'standalone_exams' ) ) {
		status_header( 404 );
		wp_die( 'الاختبارات المستقلة غير متاحة حاليًا.', 'Qalam LMS', array( 'response'=>404 ) );
	}
	if ( ! empty( $_GET['qalam_question_preview'] ) && ! qalam_feature_enabled( 'question_bank' ) ) {
		status_header( 404 );
		wp_die( 'بنك الأسئلة غير متاح حاليًا.', 'Qalam LMS', array( 'response'=>404 ) );
	}
	if ( qalam_feature_enabled( 'standalone_exams' ) && ! qalam_feature_enabled( 'dynamic_exams' ) ) {
		$quiz_id = 0;
		if ( $token && function_exists( 'qalam_140_quiz_from_token' ) ) {
			$quiz_id = qalam_140_quiz_from_token( sanitize_key( $token ) );
		} elseif ( ! empty( $_GET['qalam_general_quiz'] ) ) {
			$quiz_id = absint( $_GET['qalam_general_quiz'] );
		}
		if ( $quiz_id && defined( 'QALAM_080_DYNAMIC_META' ) && '1' === (string) get_post_meta( $quiz_id, QALAM_080_DYNAMIC_META, true ) ) {
			status_header( 403 );
			wp_die( 'ميزة الاختبارات الديناميكية غير متاحة في الباقة الحالية.', 'Qalam LMS', array( 'response'=>403 ) );
		}
	}
}
add_action( 'template_redirect', 'qalam_180_front_feature_guard', -1000 );

/** Render a single catalog for all optional Qalam capabilities. */
function qalam_180_render_addons(): void {
	if ( ! current_user_can( 'manage_tutor' ) ) { wp_die( 'غير مسموح.' ); }
	$catalog = qalam_feature_catalog();
	$categories = qalam_180_feature_categories();
	$enabled_count = 0;
	foreach ( $catalog as $row ) { if ( ! empty( $row['effective_enabled'] ) ) { $enabled_count++; } }
	$notice_key = sanitize_key( (string) ( $_GET['qalam_feature_unavailable'] ?? '' ) );
	?>
	<div class="wrap qalam-050-wrap qalam-180-wrap" dir="rtl">
		<div class="qalam-050-hero qalam-180-hero">
			<div><span class="qalam-050-eyebrow">Qalam LMS</span><h1>ملحقات قلم</h1><p>كل المزايا الاختيارية في مكان واحد. كل ميزة لها مفتاح ثابت يقدر نظام قلم السحابي يحدد من خلاله إذا كانت متاحة للباقة، وبعدها المدير يقدر يفعلها أو يعطلها محليًا.</p></div>
			<div class="qalam-180-stats"><div><strong><?php echo esc_html( count( $catalog ) ); ?></strong><span>ميزة قابلة للتحكم</span></div><div><strong><?php echo esc_html( $enabled_count ); ?></strong><span>مفعلة حاليًا</span></div></div>
		</div>
		<?php if ( $notice_key && isset( $catalog[ $notice_key ] ) ) : ?><div class="notice notice-warning inline"><p>الميزة «<?php echo esc_html( $catalog[ $notice_key ]['name'] ); ?>» متوقفة أو غير متاحة في الباقة الحالية. فعّلها من هنا لو كانت الباقة تسمح بها.</p></div><?php endif; ?>
		<div class="qalam-180-toolbar"><input type="search" class="qalam-050-search" placeholder="ابحث في الملحقات والمزايا..." data-qalam-feature-search><select data-qalam-feature-category><option value="">كل الأقسام</option><?php foreach ( $categories as $cat_key=>$cat_label ) : ?><option value="<?php echo esc_attr($cat_key); ?>"><?php echo esc_html($cat_label); ?></option><?php endforeach; ?></select><span>طبقة التحكم جاهزة للربط بخطط الـSaaS لاحقًا.</span></div>
		<?php foreach ( $categories as $cat_key => $cat_label ) :
			$rows = array_filter( $catalog, static fn($r) => ( $r['category'] ?? '' ) === $cat_key );
			if ( ! $rows ) { continue; }
		?>
		<section class="qalam-180-section" data-qalam-feature-section data-category="<?php echo esc_attr($cat_key); ?>">
			<div class="qalam-050-section-head"><div><h2><?php echo esc_html($cat_label); ?></h2><p><?php echo esc_html( count($rows) ); ?> ميزة في هذا القسم</p></div></div>
			<div class="qalam-addon-grid qalam-180-grid" data-qalam-feature-grid>
			<?php foreach ( $rows as $key => $feature ) :
				$locked = empty( $feature['access']['allowed'] );
				$can_enable = ! $locked && empty( $feature['missing'] ) && empty( $feature['dependency_labels'] );
				$search_text = $feature['name'].' '.$feature['description'].' '.$cat_label.' '.$key;
			?>
			<article class="qalam-addon-card qalam-180-card <?php echo $locked?'is-locked':''; ?>" data-qalam-feature-card data-category="<?php echo esc_attr($cat_key); ?>" data-qalam-search-text="<?php echo esc_attr($search_text); ?>">
				<div class="qalam-addon-top"><?php if(function_exists('qalam_190_render_icon')){ qalam_190_render_icon((string)$key,$feature,'qalam-addon-icon'); } else { ?><div class="qalam-addon-icon <?php echo !empty($feature['icon_url'])?'qalam-addon-icon-art':''; ?>"><?php if(!empty($feature['icon_url'])):?><img src="<?php echo esc_url($feature['icon_url']); ?>" alt="" loading="lazy"><?php else:?><span><?php echo esc_html($feature['icon']??'＋'); ?></span><?php endif;?></div><?php } ?><span class="qalam-addon-status is-<?php echo esc_attr($feature['status_key']); ?>"><?php echo esc_html($feature['status']); ?></span></div>
				<h3><?php echo esc_html($feature['name']); ?></h3>
				<p><?php echo esc_html($feature['description']); ?></p>
				<div class="qalam-180-key"><code><?php echo esc_html($key); ?></code><span><?php echo 'packaged'===$feature['type']?'ملحق مدمج':'ميزة قلم'; ?></span></div>
				<?php if($locked):?><div class="qalam-addon-note is-locked"><strong>الباقة:</strong> <?php echo esc_html($feature['access']['reason']?:'غير متاحة في الباقة الحالية.'); ?><?php if(!empty($feature['access']['plan'])):?><br>متاحة في: <?php echo esc_html($feature['access']['plan']); ?><?php endif;?></div><?php endif;?>
				<?php if(!empty($feature['dependency_labels'])):?><div class="qalam-addon-note"><strong>فعّل أولًا:</strong> <?php echo esc_html(implode('، ',$feature['dependency_labels'])); ?></div><?php endif;?>
				<?php if(!empty($feature['missing'])):?><div class="qalam-addon-note"><strong>متطلب خارجي:</strong> <?php echo esc_html(implode('، ',array_values($feature['missing']))); ?></div><?php endif;?>
				<?php if(!empty($feature['error']['message'])):?><div class="qalam-addon-note is-error"><strong>آخر خطأ:</strong> <?php echo esc_html($feature['error']['message']); ?></div><?php endif;?>
				<div class="qalam-addon-actions qalam-180-actions">
					<button type="button" class="button <?php echo $feature['enabled']?'':'button-primary'; ?>" data-qalam-feature-toggle data-feature="<?php echo esc_attr($key); ?>" data-enable="<?php echo $feature['enabled']?'0':'1'; ?>" <?php disabled(!$feature['enabled'] && !$can_enable); ?>><?php echo esc_html($feature['enabled']?'تعطيل':'تفعيل'); ?></button>
					<?php if(!empty($feature['manage_url_resolved']) && $feature['effective_enabled']):?><a class="button" href="<?php echo esc_url($feature['manage_url_resolved']); ?>">إدارة</a><?php endif;?>
				</div>
			</article>
			<?php endforeach; ?>
			</div>
		</section>
		<?php endforeach; ?>
	</div>
	<?php
}

/** Catalog assets. */
function qalam_180_admin_assets(): void {
	$page = isset( $_GET['page'] ) ? sanitize_key( (string) wp_unslash( $_GET['page'] ) ) : '';
	if ( 'tutor-addons' !== $page ) { return; }
	$base = plugin_dir_url( TUTOR_FILE );
	wp_enqueue_style( 'qalam-180-addons', $base . 'assets/css/qalam-180-addons.css', array( 'qalam-060-admin' ), QALAM_LMS_UI_VERSION );
	wp_enqueue_script( 'qalam-180-addons', $base . 'assets/js/qalam-180-addons.js', array(), QALAM_LMS_UI_VERSION, true );
	wp_localize_script( 'qalam-180-addons', 'Qalam180', array(
		'ajaxUrl'=>admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('qalam_180_toggle_feature'),
		'toggleFailed'=>'تعذر تغيير حالة الميزة.',
	) );
}
add_action( 'admin_enqueue_scripts', 'qalam_180_admin_assets', PHP_INT_MAX );

/** Export catalog metadata to future SaaS/control-plane code without coupling to UI. */
function qalam_180_catalog_for_saas(): array {
	// 0.20 exposes product-level add-ons to plans while retaining the 45 old keys
	// internally for compatibility and fine-grained runtime guards.
	if ( function_exists( 'qalam_200_catalog_for_saas' ) ) { return qalam_200_catalog_for_saas(); }
	$out = array();
	foreach ( qalam_180_feature_definitions() as $key=>$def ) {
		$out[$key] = array(
			'key'=>$key, 'name'=>$def['name'], 'category'=>$def['category'], 'type'=>$def['type'],
			'depends'=>array_values((array)($def['depends']??array())), 'addon'=>$def['addon']??'',
		);
	}
	return $out;
}
