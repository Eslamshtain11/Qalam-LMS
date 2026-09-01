<?php
/**
 * Qalam LMS 0.19.0 — feature management center.
 *
 * Adds a real management page for every catalog capability, complete icon coverage,
 * and strict runtime disappearance for disabled packaged add-ons outside the catalog.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_190_VERSION = '0.19.0-feature-management';

/** Dashicons are used as a local, dependency-free fallback for every catalog entry. */
function qalam_190_feature_icons(): array {
	return array(
		'question_bank'          => 'dashicons-editor-help',
		'standalone_exams'       => 'dashicons-forms',
		'randomized_exams'       => 'dashicons-randomize',
		'dynamic_exams'          => 'dashicons-update-alt',
		'ai_question_generation' => 'dashicons-lightbulb',
		'pdf_question_generation'=> 'dashicons-media-document',
		'ai_background_worker'   => 'dashicons-admin-generic',
		'qalam_video_player'     => 'dashicons-video-alt3',
		'video_subtitles'        => 'dashicons-editor-spellcheck',
		'video_ads'              => 'dashicons-megaphone',
		'student_analytics'      => 'dashicons-chart-area',
		'certificate_builder'    => 'dashicons-awards',
		'instructor_marketplace' => 'dashicons-groups',
		'gift_courses'           => 'dashicons-tickets-alt',
		'lesson_notes'           => 'dashicons-welcome-write-blog',
		'progress_reset'         => 'dashicons-image-rotate',
		'email_update'           => 'dashicons-email-alt2',
		'course_bundles'         => 'dashicons-screenoptions',
		'subscriptions'          => 'dashicons-money-alt',
		'content_bank'           => 'dashicons-database',
		'social_login'           => 'dashicons-share',
		'content_drip'           => 'dashicons-clock',
		'multi_instructor'       => 'dashicons-groups',
		'assignments'            => 'dashicons-clipboard',
		'course_preview'         => 'dashicons-visibility',
		'course_attachments'     => 'dashicons-paperclip',
		'google_meet'            => 'dashicons-video-alt2',
		'advanced_reports'       => 'dashicons-chart-bar',
		'email_notifications'    => 'dashicons-email',
		'calendar'               => 'dashicons-calendar-alt',
		'notifications'          => 'dashicons-bell',
		'google_classroom'       => 'dashicons-welcome-learn-more',
		'zoom'                   => 'dashicons-video-alt',
		'quiz_import_export'     => 'dashicons-migrate',
		'manual_enrollments'     => 'dashicons-id-alt',
		'certificates'           => 'dashicons-awards',
		'gradebook'              => 'dashicons-chart-line',
		'course_prerequisites'   => 'dashicons-lock',
		'buddypress'             => 'dashicons-buddicons-community',
		'wc_subscriptions'       => 'dashicons-cart',
		'pmpro'                  => 'dashicons-money',
		'restrict_content_pro'   => 'dashicons-shield',
		'weglot'                 => 'dashicons-translation',
		'wpml'                   => 'dashicons-translation',
		'h5p'                    => 'dashicons-games',
	);
}

function qalam_190_feature_manager_url( string $key ): string {
	return add_query_arg(
		array( 'page' => 'qalam-feature-settings', 'feature' => sanitize_key( $key ) ),
		admin_url( 'admin.php' )
	);
}

/**
 * Native destinations used by the feature manager.
 * These links point only to existing operational/settings surfaces; no fake settings are created.
 */
function qalam_190_feature_destinations( string $key, array $def ): array {
	$workspace = qalam_180_feature_manage_url( $def );
	$settings  = '';
	$mode      = 'workspace';
	$note      = '';

	$settings_tabs = array(
		'ai_question_generation' => 'advanced',
		'pdf_question_generation'=> 'advanced',
		'ai_background_worker'   => 'advanced',
		'social_login'           => 'authentication',
		'email_notifications'    => 'email_notification',
		'notifications'          => 'tutor_notifications',
		'gradebook'              => 'gradebook',
		'certificates'           => 'tutor_certificate',
		'pmpro'                  => 'pm-pro',
	);
	if ( isset( $settings_tabs[ $key ] ) ) {
		$settings = add_query_arg( array( 'page'=>'tutor_settings', 'tab_page'=>$settings_tabs[ $key ] ), admin_url( 'admin.php' ) );
	}

	$course_settings = array(
		'content_drip', 'course_prerequisites', 'course_preview', 'course_attachments', 'multi_instructor', 'calendar',
	);
	if ( in_array( $key, $course_settings, true ) ) {
		$mode = 'per_course';
		$workspace = admin_url( 'admin.php?page=create-course' );
		$settings = admin_url( 'admin.php?page=tutor_settings&tab_page=course' );
		$note = 'إعدادات الميزة الأساسية بتظهر داخل منشئ الدورة أو إعدادات الدورة بعد تفعيل الملحق.';
	}

	$option_tabs = array(
		'instructor_marketplace' => 'general',
		'gift_courses'           => 'course',
		'lesson_notes'           => 'course',
		'progress_reset'         => 'general',
		'email_update'           => 'advanced',
	);
	if ( isset( $option_tabs[ $key ] ) ) {
		$settings = add_query_arg( array( 'page'=>'tutor_settings', 'tab_page'=>$option_tabs[ $key ] ), admin_url( 'admin.php' ) );
	}

	if ( 'quiz_import_export' === $key ) {
		$workspace = admin_url( 'admin.php?page=tutor-tools&sub_page=import_export' );
	}
	if ( 'student_analytics' === $key ) {
		$workspace = admin_url( 'users.php' );
		$note = 'افتح أي طالب من صفحة المستخدمين ثم اختار «ملف الطالب في قلم».';
	}
	if ( 'qalam_video_player' === $key ) {
		$workspace = admin_url( 'admin.php?page=tutor_settings&tab_page=course#field_supported_video_sources' );
		$settings = $workspace;
		$note = 'المشغل يعمل تلقائيًا على فيديوهات YouTube داخل الدروس. إعداد مصدر الفيديو يتم من إعدادات الدورة.';
	}
	if ( in_array( $key, array( 'video_ads','video_subtitles' ), true ) ) {
		$workspace = admin_url( 'admin.php?page=qalam-video-ads' );
	}
	if ( in_array( $key, array( 'question_bank','ai_question_generation','pdf_question_generation','ai_background_worker' ), true ) ) {
		$workspace = admin_url( 'admin.php?page=qalam-question-bank' );
	}
	if ( in_array( $key, array( 'standalone_exams','randomized_exams','dynamic_exams' ), true ) ) {
		$workspace = admin_url( 'admin.php?page=qalam-quiz-builder' );
	}
	if ( in_array( $key, array( 'certificate_builder','certificates' ), true ) ) {
		$workspace = admin_url( 'admin.php?page=qalam-certificate-builder' );
	}

	$external = array(
		'buddypress'           => admin_url( 'options-general.php?page=bp-components' ),
		'wc_subscriptions'     => admin_url( 'admin.php?page=wc-settings&tab=subscriptions' ),
		'pmpro'                => admin_url( 'admin.php?page=pmpro-dashboard' ),
		'restrict_content_pro' => admin_url( 'admin.php?page=rcp-settings' ),
		'weglot'               => admin_url( 'admin.php?page=weglot-settings' ),
		'wpml'                 => admin_url( 'admin.php?page=sitepress-multilingual-cms/menu/languages.php' ),
	);
	if ( isset( $external[ $key ] ) ) {
		$mode = 'external';
		$settings = $external[ $key ];
		if ( ! $workspace ) { $workspace = $settings; }
		$note = 'التحكم التفصيلي في التكامل يتم من إعدادات الإضافة الخارجية المرتبطة بعد تفعيلها.';
	}

	return array(
		'workspace_url' => $workspace,
		'settings_url'  => $settings,
		'mode'          => $mode,
		'note'          => $note,
	);
}

/** Enrich every catalog card with a real icon and a dedicated management page. */
function qalam_190_enrich_catalog( array $catalog ): array {
	$icons = qalam_190_feature_icons();
	$defs  = qalam_180_feature_definitions();
	foreach ( $catalog as $key => &$feature ) {
		$def = $defs[ $key ] ?? $feature;
		$feature['dashicon'] = $icons[ $key ] ?? 'dashicons-admin-plugins';
		$dest = qalam_190_feature_destinations( (string) $key, (array) $def );
		$feature['workspace_url'] = $dest['workspace_url'];
		$feature['settings_url'] = $dest['settings_url'];
		$feature['management_mode'] = $dest['mode'];
		$feature['management_note'] = $dest['note'];
		// «إدارة» must land on the real operational/settings surface in one click.
		// The Qalam manager remains available as metadata/control infrastructure, but
		// it is not an extra hop between the catalog and the actual service.
		$feature['details_url'] = qalam_190_feature_manager_url( (string) $key );
		$primary = 'per_course' === $dest['mode'] ? $dest['workspace_url'] : ( $dest['settings_url'] ?: $dest['workspace_url'] );
		$feature['manage_url_resolved'] = $primary ?: $feature['details_url'];
	}
	unset( $feature );
	return $catalog;
}
add_filter( 'qalam_feature_catalog', 'qalam_190_enrich_catalog', PHP_INT_MAX );

/** Hidden page: one actual management center per feature. */
function qalam_190_register_feature_manager(): void {
	add_submenu_page(
		null,
		'إدارة ملحق قلم',
		'إدارة ملحق قلم',
		'manage_tutor',
		'qalam-feature-settings',
		'qalam_190_render_feature_manager'
	);
}
add_action( 'admin_menu', 'qalam_190_register_feature_manager', PHP_INT_MAX );

/** Resolve catalog entry and enforce enabled/access state for the manager page. */
function qalam_190_current_feature(): array {
	$key = sanitize_key( (string) ( $_GET['feature'] ?? '' ) );
	$catalog = qalam_feature_catalog();
	if ( ! $key || ! isset( $catalog[ $key ] ) ) {
		return array( '', array() );
	}
	return array( $key, $catalog[ $key ] );
}

function qalam_190_render_icon( string $key, array $feature, string $class = '' ): void {
	if ( ! empty( $feature['icon_url'] ) && false === strpos( (string) $feature['icon_url'], 'qalam-logo.svg' ) ) {
		echo '<span class="qalam-190-icon qalam-190-icon-art ' . esc_attr( $class ) . '"><img src="' . esc_url( $feature['icon_url'] ) . '" alt=""></span>';
		return;
	}
	$icon = (string) ( $feature['dashicon'] ?? ( qalam_190_feature_icons()[ $key ] ?? 'dashicons-admin-plugins' ) );
	echo '<span class="qalam-190-icon ' . esc_attr( $class ) . '"><span class="dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span></span>';
}

/** The page intentionally exposes only real destinations/settings and never fabricated controls. */
function qalam_190_render_feature_manager(): void {
	if ( ! current_user_can( 'manage_tutor' ) ) { wp_die( 'غير مسموح.' ); }
	list( $key, $feature ) = qalam_190_current_feature();
	if ( ! $key ) {
		wp_safe_redirect( admin_url( 'admin.php?page=tutor-addons' ) );
		exit;
	}
	$enabled = ! empty( $feature['effective_enabled'] );
	$locked  = empty( $feature['access']['allowed'] );
	$categories = qalam_180_feature_categories();
	$category = $categories[ $feature['category'] ?? '' ] ?? 'ملحقات قلم';
	?>
	<div class="wrap qalam-190-manager" dir="rtl">
		<div class="qalam-190-manager-nav"><a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor-addons' ) ); ?>">← الرجوع إلى الملحقات</a></div>
		<header class="qalam-190-manager-hero">
			<div class="qalam-190-manager-title"><?php qalam_190_render_icon( $key, $feature, 'is-large' ); ?><div><span><?php echo esc_html( $category ); ?></span><h1><?php echo esc_html( $feature['name'] ); ?></h1><p><?php echo esc_html( $feature['description'] ); ?></p></div></div>
			<div class="qalam-190-manager-state"><span class="qalam-addon-status is-<?php echo esc_attr( $feature['status_key'] ); ?>"><?php echo esc_html( $feature['status'] ); ?></span><code><?php echo esc_html( $key ); ?></code></div>
		</header>

		<?php if ( $locked ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html( $feature['access']['reason'] ?: 'الميزة غير متاحة في الباقة الحالية.' ); ?></p></div><?php endif; ?>
		<?php if ( ! empty( $feature['dependency_labels'] ) ) : ?><div class="notice notice-warning inline"><p>فعّل أولًا: <?php echo esc_html( implode( '، ', $feature['dependency_labels'] ) ); ?></p></div><?php endif; ?>
		<?php if ( ! empty( $feature['missing'] ) ) : ?><div class="notice notice-error inline"><p>متطلب خارجي ناقص: <?php echo esc_html( implode( '، ', array_values( $feature['missing'] ) ) ); ?></p></div><?php endif; ?>

		<div class="qalam-190-manager-grid">
			<section class="qalam-190-panel">
				<h2>حالة الخدمة</h2>
				<p>التعطيل يخفي الخدمة من واجهات قلم ويمنع الوصول إلى صفحاتها ومساراتها. بطاقة الخدمة نفسها تفضل موجودة في صفحة الملحقات علشان تقدر تفعلها من جديد.</p>
				<div class="qalam-190-toggle-row"><div><strong><?php echo $enabled ? 'الخدمة مفعلة' : 'الخدمة متوقفة'; ?></strong><small><?php echo $enabled ? 'تعمل حاليًا حسب صلاحية الباقة والمتطلبات.' : 'لن تظهر أو تعمل خارج صفحة الملحقات.'; ?></small></div><button type="button" class="button <?php echo $enabled ? '' : 'button-primary'; ?>" data-qalam-feature-toggle data-feature="<?php echo esc_attr( $key ); ?>" data-enable="<?php echo $enabled ? '0' : '1'; ?>" <?php disabled( ! $enabled && ( $locked || ! empty( $feature['dependency_labels'] ) || ! empty( $feature['missing'] ) ) ); ?>><?php echo $enabled ? 'تعطيل الخدمة' : 'تفعيل الخدمة'; ?></button></div>
			</section>

			<section class="qalam-190-panel">
				<h2>إعدادات الخدمة</h2>
				<?php if ( ! $enabled ) : ?>
					<div class="qalam-190-empty"><span class="dashicons dashicons-hidden"></span><p>فعّل الخدمة الأول علشان تظهر إعداداتها ولوحة إدارتها.</p></div>
				<?php else : ?>
					<p>المسارات اللي تحت هي الإعدادات ولوحة التشغيل الحقيقية الخاصة بالخدمة، مش صفحات تجريبية.</p>
					<?php if ( ! empty( $feature['management_note'] ) ) : ?><div class="qalam-190-note"><?php echo esc_html( $feature['management_note'] ); ?></div><?php endif; ?>
					<div class="qalam-190-destinations">
						<?php if ( ! empty( $feature['workspace_url'] ) ) : ?><a class="qalam-190-destination" href="<?php echo esc_url( $feature['workspace_url'] ); ?>"><span class="dashicons dashicons-admin-page"></span><div><strong>فتح لوحة إدارة الخدمة</strong><small>إدارة المحتوى أو العمليات المرتبطة بهذه الميزة.</small></div><span class="dashicons dashicons-arrow-left-alt2"></span></a><?php endif; ?>
						<?php if ( ! empty( $feature['settings_url'] ) && $feature['settings_url'] !== $feature['workspace_url'] ) : ?><a class="qalam-190-destination" href="<?php echo esc_url( $feature['settings_url'] ); ?>"><span class="dashicons dashicons-admin-settings"></span><div><strong>فتح الإعدادات الفعلية</strong><small>تعديل إعدادات الخدمة المسجلة داخل قلم أو الإضافة المرتبطة.</small></div><span class="dashicons dashicons-arrow-left-alt2"></span></a><?php endif; ?>
					</div>
					<?php if ( empty( $feature['workspace_url'] ) && empty( $feature['settings_url'] ) ) : ?><div class="qalam-190-empty"><span class="dashicons dashicons-info-outline"></span><p>الميزة تعمل تلقائيًا بعد التفعيل ولا تحتوي على إعدادات عامة مستقلة. التحكم التفصيلي فيها بيظهر في المكان اللي تُستخدم فيه داخل الدورة أو حساب المستخدم.</p></div><?php endif; ?>
				<?php endif; ?>
			</section>

			<section class="qalam-190-panel qalam-190-panel-full">
				<h2>حالة الربط والباقات</h2>
				<div class="qalam-190-facts"><div><span>النوع</span><strong><?php echo 'packaged' === $feature['type'] ? 'ملحق مدمج' : 'ميزة قلم'; ?></strong></div><div><span>صلاحية الباقة</span><strong><?php echo $locked ? 'غير مسموح' : 'مسموح'; ?></strong></div><div><span>الحالة المحلية</span><strong><?php echo ! empty( $feature['enabled'] ) ? 'مفعلة' : 'متوقفة'; ?></strong></div><div><span>الحالة الفعلية</span><strong><?php echo $enabled ? 'تعمل' : 'لا تعمل'; ?></strong></div></div>
			</section>
		</div>
	</div>
	<?php
}

/** Assets for both catalog and individual feature manager. */
function qalam_190_admin_assets(): void {
	$page = sanitize_key( (string) ( $_GET['page'] ?? '' ) );
	if ( ! in_array( $page, array( 'tutor-addons','qalam-feature-settings' ), true ) ) { return; }
	$base = plugin_dir_url( TUTOR_FILE );
	wp_enqueue_style( 'dashicons' );
	if ( 'qalam-feature-settings' === $page ) {
		wp_enqueue_style( 'qalam-050-admin', $base . 'assets/css/qalam-050-admin.css', array( 'qalam-lms-brand' ), QALAM_LMS_UI_VERSION );
		wp_enqueue_style( 'qalam-060-admin', $base . 'assets/css/qalam-060-admin.css', array( 'qalam-050-admin' ), QALAM_LMS_UI_VERSION );
		wp_enqueue_style( 'qalam-190-feature-management', $base . 'assets/css/qalam-190-feature-management.css', array( 'qalam-060-admin' ), QALAM_LMS_UI_VERSION );
		wp_enqueue_script( 'qalam-180-addons', $base . 'assets/js/qalam-180-addons.js', array(), QALAM_LMS_UI_VERSION, true );
		wp_localize_script( 'qalam-180-addons', 'Qalam180', array(
			'ajaxUrl'=>admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('qalam_180_toggle_feature'), 'toggleFailed'=>'تعذر تغيير حالة الميزة.',
		) );
	} else {
		wp_enqueue_style( 'qalam-190-feature-management', $base . 'assets/css/qalam-190-feature-management.css', array( 'qalam-180-addons' ), QALAM_LMS_UI_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'qalam_190_admin_assets', PHP_INT_MAX );

/** Direct links to a disabled manager must return to the only place where it can be re-enabled. */
function qalam_190_guard_feature_manager(): void {
	$page = sanitize_key( (string) ( $_GET['page'] ?? '' ) );
	if ( 'qalam-feature-settings' !== $page ) { return; }
	list( $key, $feature ) = qalam_190_current_feature();
	if ( ! $key ) { return; }
	if ( empty( $feature['effective_enabled'] ) ) {
		wp_safe_redirect( add_query_arg( array( 'page'=>'tutor-addons', 'qalam_feature_unavailable'=>$key ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
add_action( 'admin_init', 'qalam_190_guard_feature_manager', 1200 );

/**
 * Decide whether a packaged add-on should bootstrap on this request.
 * Disabled services are loaded only on the catalog/AJAX control path so their manifest
 * and enable hooks remain available; everywhere else they disappear at runtime.
 */
function qalam_190_packaged_runtime_should_boot( string $addon_slug ): bool {
	$feature_key = qalam_180_feature_key_for_addon_reference( $addon_slug );
	if ( ! $feature_key ) { return true; }
	$access = qalam_feature_access( $feature_key );
	if ( empty( $access['allowed'] ) ) { return false; }

	// Product-level services introduced in 0.20 are intentionally all-or-nothing.
	// Their single group switch overrides old per-add-on config fragments.
	$group_key = function_exists( 'qalam_200_group_for_child' ) ? qalam_200_group_for_child( $feature_key ) : '';
	if ( $group_key && function_exists( 'qalam_200_group_enabled' ) ) {
		if ( qalam_200_group_enabled( $group_key ) ) { return true; }
	} else {
		$defs = qalam_180_packaged_features();
		$def = $defs[ $feature_key ] ?? array();
		if ( empty( $def['addon'] ) ) { return true; }
		$pro_root = defined( 'TUTOR_PRO_FILE' ) ? dirname( plugin_basename( TUTOR_PRO_FILE ) ) : 'qalam-lms/pro';
		$basename = $pro_root . '/addons/' . $addon_slug . '/' . $addon_slug . '.php';
		$state = tutor_utils()->get_addon_config( $basename );
		$enabled = is_array( $state ) && ! empty( $state['is_enable'] );
		if ( $enabled ) { return true; }
	}

	if ( wp_doing_ajax() ) {
		// A disabled add-on may bootstrap only while its parent product is being toggled
		// or while the exact legacy add-on transition is running.
		$action = sanitize_key( (string) ( $_REQUEST['action'] ?? '' ) );
		if ( 'qalam_200_toggle_product' === $action && $group_key ) {
			$target = sanitize_key( (string) ( $_REQUEST['feature'] ?? '' ) );
			return $target && hash_equals( $group_key, $target );
		}
		if ( 'qalam_180_toggle_feature' === $action ) {
			$target = sanitize_key( (string) ( $_REQUEST['feature'] ?? '' ) );
			return $target && hash_equals( $feature_key, $target );
		}
		if ( 'addon_enable_disable' === $action ) {
			$raw = isset( $_REQUEST['addonFieldNames'] ) ? (string) wp_unslash( $_REQUEST['addonFieldNames'] ) : '';
			$form = $raw ? json_decode( $raw, true ) : array();
			if ( is_array( $form ) ) {
				foreach ( $form as $reference => $state ) {
					if ( hash_equals( $feature_key, qalam_180_feature_key_for_addon_reference( (string) $reference ) ) ) { return true; }
				}
			}
		}
	}
	return false;
}

/**
 * Remove the original Tutor setting switches for disabled Qalam-owned features.
 * The Add-ons catalog becomes the only place where a disabled service can be turned
 * back on, while enabled services may still expose their native fine-grained settings.
 */
function qalam_190_hidden_internal_option_keys(): array {
	$hidden = array();
	foreach ( qalam_180_internal_features() as $feature_key => $def ) {
		$option_key = (string) ( $def['option_key'] ?? '' );
		if ( ! $option_key ) { continue; }
		// Grouped implementation switches are never exposed separately; the product
		// add-on card is the only source of truth. Independent options remain visible
		// only while their add-on is enabled.
		$grouped = function_exists( 'qalam_200_group_for_child' ) && qalam_200_group_for_child( (string) $feature_key );
		if ( $grouped || ! qalam_feature_enabled( (string) $feature_key ) ) { $hidden[] = $option_key; }
	}
	return array_values( array_unique( $hidden ) );
}

function qalam_190_strip_option_fields_recursive( $node, array $hidden ) {
	if ( ! is_array( $node ) ) { return $node; }
	if ( isset( $node['key'] ) && in_array( (string) $node['key'], $hidden, true ) ) { return null; }
	foreach ( array_keys( $node ) as $key ) {
		if ( ! is_array( $node[ $key ] ) ) { continue; }
		$next = qalam_190_strip_option_fields_recursive( $node[ $key ], $hidden );
		if ( null === $next ) { unset( $node[ $key ] ); } else { $node[ $key ] = $next; }
	}
	// Re-index only true numeric lists so option blocks keep their expected shape.
	$keys = array_keys( $node );
	$numeric = ! empty( $keys );
	foreach ( $keys as $key ) { if ( ! is_int( $key ) ) { $numeric = false; break; } }
	if ( $numeric ) { $node = array_values( $node ); }
	return $node;
}

function qalam_190_hide_disabled_internal_settings( $attr ) {
	$hidden = qalam_190_hidden_internal_option_keys();
	if ( ! $hidden || ! is_array( $attr ) ) { return $attr; }
	$filtered = qalam_190_strip_option_fields_recursive( $attr, $hidden );
	return is_array( $filtered ) ? $filtered : array();
}
add_filter( 'tutor/options/attr', 'qalam_190_hide_disabled_internal_settings', PHP_INT_MAX );
add_filter( 'tutor/options/extend/attr', 'qalam_190_hide_disabled_internal_settings', PHP_INT_MAX );

/** Keep internal optional menu nodes absent whenever a feature is disabled. */
function qalam_190_strip_disabled_internal_menu_nodes( array $menu ): array {
	$node_feature = array(
		'qalam_question_bank'=>'question_bank', 'qalam_quiz_builder'=>'standalone_exams', 'qalam_certificate_builder'=>'certificate_builder',
		'content_bank'=>'content_bank', 'assignments'=>'assignments', 'gradebook'=>'gradebook', 'reports'=>'advanced_reports',
		'enrollments'=>'manual_enrollments', 'instructors'=>'instructor_marketplace', 'withdraw_requests'=>'instructor_marketplace',
	);
	foreach ( $menu as &$group ) {
		if ( ! is_array( $group ) ) { continue; }
		foreach ( array_keys( $group ) as $node ) {
			if ( isset( $node_feature[ $node ] ) && ! qalam_feature_enabled( $node_feature[ $node ] ) ) { unset( $group[ $node ] ); }
		}
	}
	unset( $group );
	return $menu;
}
add_filter( 'tutor_admin_menu', 'qalam_190_strip_disabled_internal_menu_nodes', PHP_INT_MAX );
