<?php
/**
 * Qalam LMS 0.20.0 — product-level add-on catalog.
 *
 * The 0.18/0.19 catalog intentionally exposed low-level capabilities. That was useful
 * for wiring SaaS gates, but it is too granular for a real product catalog. This layer
 * groups implementation details into one customer-facing service with one on/off state.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_200_VERSION            = '0.20.1-addons-ui';
const QALAM_200_GROUP_OPTION       = 'qalam_feature_group_states';
const QALAM_200_GROUP_SCHEMA       = 'qalam_feature_group_schema';
const QALAM_200_GROUP_SCHEMA_VALUE = '0.20.0';

/** Product-level groups. Children remain internal compatibility keys. */
function qalam_200_feature_groups(): array {
	return array(
		'question_bank_suite' => array(
			'name' => 'بنك الأسئلة',
			'description' => 'بنك الأسئلة الكامل مع التصنيفات والاستيراد والتصدير وإعادة الاستخدام داخل الاختبارات.',
			'category' => 'exams',
			'children' => array( 'question_bank', 'content_bank', 'quiz_import_export' ),
			'manage_url' => admin_url( 'admin.php?page=qalam-question-bank' ),
			'icon_url' => plugin_dir_url( TUTOR_FILE ) . 'assets/images/qalam-addons/question-bank.svg',
		),
		'advanced_exams' => array(
			'name' => 'الاختبارات المتقدمة',
			'description' => 'الاختبارات المستقلة والعشوائية والديناميكية في خدمة واحدة.',
			'category' => 'exams',
			'children' => array( 'standalone_exams', 'randomized_exams', 'dynamic_exams' ),
			'depends_groups' => array( 'question_bank_suite' ),
			'manage_url' => admin_url( 'admin.php?page=qalam-quiz-builder' ),
			'icon_url' => plugin_dir_url( TUTOR_FILE ) . 'assets/images/qalam-addons/exams.svg',
		),
		'artificial_intelligence' => array(
			'name' => 'الذكاء الاصطناعي',
			'description' => 'توليد الأسئلة من النص وPDF والمعالجة الخلفية؛ تشغيل أو تعطيل كامل من مفتاح واحد.',
			'category' => 'ai',
			'children' => array( 'ai_question_generation', 'pdf_question_generation', 'ai_background_worker' ),
			'depends_groups' => array( 'question_bank_suite' ),
			'manage_url' => admin_url( 'admin.php?page=tutor_settings&tab_page=advanced' ),
			'icon_url' => plugin_dir_url( TUTOR_FILE ) . 'assets/images/qalam-addons/ai.svg',
		),
		'video_player' => array(
			'name' => 'مشغل الفيديو',
			'description' => 'مشغل قلم وترجمة VTT/SRT كخدمة فيديو واحدة.',
			'category' => 'video',
			'children' => array( 'qalam_video_player', 'video_subtitles' ),
			'manage_url' => admin_url( 'admin.php?page=tutor_settings&tab_page=course#field_supported_video_sources' ),
			'icon_url' => plugin_dir_url( TUTOR_FILE ) . 'assets/images/qalam-addons/video-player.svg',
		),
		'certificates_suite' => array(
			'name' => 'الشهادات',
			'description' => 'إصدار الشهادات ومنشئ القوالب والتحقق منها ضمن خدمة واحدة.',
			'category' => 'teaching',
			'children' => array( 'certificates', 'certificate_builder' ),
			'manage_url' => admin_url( 'admin.php?page=qalam-certificate-builder' ),
			'icon_url' => plugin_dir_url( TUTOR_FILE ) . 'pro/addons/tutor-certificate/assets/images/thumbnail.svg',
		),
		'instructor_suite' => array(
			'name' => 'نظام المعلمين',
			'description' => 'تعدد المعلمين وسوق المعلمين وإدارة العمل المؤسسي من مفتاح واحد.',
			'category' => 'instructors',
			'children' => array( 'multi_instructor', 'instructor_marketplace' ),
			'manage_url' => admin_url( 'admin.php?page=tutor-instructors' ),
			'icon_url' => plugin_dir_url( TUTOR_FILE ) . 'pro/addons/tutor-multi-instructors/assets/images/thumbnail.svg',
		),
		'reports_suite' => array(
			'name' => 'التقارير والمتابعة',
			'description' => 'التقارير المتقدمة وملف الطالب وتحليل تقدمه وإعادة ضبط التقدم.',
			'category' => 'reports',
			'children' => array( 'advanced_reports', 'student_analytics', 'progress_reset' ),
			'manage_url' => admin_url( 'admin.php?page=tutor_report' ),
			'icon_url' => plugin_dir_url( TUTOR_FILE ) . 'pro/addons/tutor-report/assets/images/thumbnail.svg',
		),
		'communications_suite' => array(
			'name' => 'الإشعارات والبريد',
			'description' => 'إشعارات المنصة ورسائل البريد الإلكتروني في خدمة تواصل واحدة.',
			'category' => 'communication',
			'children' => array( 'notifications', 'email_notifications' ),
			'manage_url' => admin_url( 'admin.php?page=tutor_settings&tab_page=tutor_notifications' ),
			'icon_url' => plugin_dir_url( TUTOR_FILE ) . 'pro/addons/tutor-notifications/assets/images/thumbnail.png',
		),
		'account_access_suite' => array(
			'name' => 'الحساب وتسجيل الدخول',
			'description' => 'تسجيل الدخول الاجتماعي وإدارة تحديث البريد الإلكتروني من خدمة حساب واحدة.',
			'category' => 'account',
			'children' => array( 'social_login', 'email_update' ),
			'manage_url' => admin_url( 'admin.php?page=tutor_settings&tab_page=authentication' ),
			'icon_url' => plugin_dir_url( TUTOR_FILE ) . 'pro/addons/social-login/assets/images/thumbnail.svg',
		),
	);
}

/** Custom art for Qalam-owned single add-ons that do not have a packaged thumbnail. */
function qalam_200_single_icon_overrides(): array {
	$base = plugin_dir_url( TUTOR_FILE ) . 'assets/images/qalam-addons/';
	return array(
		'video_ads'    => $base . 'video-ads.svg',
		'gift_courses' => $base . 'gift.svg',
		'lesson_notes' => $base . 'lesson-notes.svg',
	);
}

function qalam_200_group_for_child( string $feature_key ): string {
	foreach ( qalam_200_feature_groups() as $group_key => $group ) {
		if ( in_array( $feature_key, (array) $group['children'], true ) ) {
			return (string) $group_key;
		}
	}
	return '';
}

/** SaaS can license a whole product group through one stable key. */
function qalam_200_group_access( string $group_key ): array {
	$default = array( 'allowed'=>true, 'reason'=>'', 'plan'=>'', 'source'=>'local' );
	$access = apply_filters( 'qalam_saas_feature_access', $default, $group_key );
	if ( is_bool( $access ) ) {
		$access = array( 'allowed'=>$access, 'reason'=>$access ? '' : 'الخدمة غير متاحة في الباقة الحالية.', 'plan'=>'', 'source'=>'saas' );
	}
	$access = is_array( $access ) ? array_merge( $default, $access ) : $default;
	$access['allowed'] = ! empty( $access['allowed'] );
	return $access;
}

function qalam_200_raw_leaf_enabled( string $feature_key ): bool {
	$defs = qalam_180_feature_definitions();
	if ( empty( $defs[ $feature_key ] ) ) { return false; }
	$def = $defs[ $feature_key ];
	if ( 'packaged' === (string) $def['type'] ) {
		$state = qalam_180_packaged_state( (string) $def['addon'] );
		return ! empty( $state['enabled'] );
	}
	return qalam_180_internal_state( $feature_key );
}

function qalam_200_group_local_enabled( string $group_key ): bool {
	$groups = qalam_200_feature_groups();
	if ( empty( $groups[ $group_key ] ) ) { return false; }
	$states = get_option( QALAM_200_GROUP_OPTION, array() );
	$states = is_array( $states ) ? $states : array();
	if ( array_key_exists( $group_key, $states ) ) { return ! empty( $states[ $group_key ] ); }
	// Upgrade-safe default: if any old sub-feature was enabled, the new unified service is enabled.
	foreach ( (array) $groups[ $group_key ]['children'] as $child ) {
		if ( qalam_200_raw_leaf_enabled( (string) $child ) ) { return true; }
	}
	return false;
}

function qalam_200_group_enabled( string $group_key, array $seen = array() ): bool {
	$groups = qalam_200_feature_groups();
	if ( empty( $groups[ $group_key ] ) || isset( $seen[ $group_key ] ) ) { return false; }
	$seen[ $group_key ] = true;
	$access = qalam_200_group_access( $group_key );
	if ( empty( $access['allowed'] ) || ! qalam_200_group_local_enabled( $group_key ) ) { return false; }
	foreach ( (array) ( $groups[ $group_key ]['depends_groups'] ?? array() ) as $dependency ) {
		if ( ! qalam_200_group_enabled( (string) $dependency, $seen ) ) { return false; }
	}
	return true;
}

/** Keep the old 45 keys as internal implementation details, but a group now dominates them. */
function qalam_200_enforce_group_gate( $enabled, string $feature_key, array $def ): bool {
	if ( function_exists( 'qalam_290_cloud_managed' ) && qalam_290_cloud_managed() ) { return (bool) $enabled; }
	$group_key = qalam_200_group_for_child( $feature_key );
	if ( ! $group_key ) { return (bool) $enabled; }
	return qalam_200_group_enabled( $group_key );
}
add_filter( 'qalam_feature_enabled', 'qalam_200_enforce_group_gate', PHP_INT_MAX, 3 );

/** Seed one product state per group and normalize old sub-feature states once. */
function qalam_200_seed_groups(): void {
	if ( get_option( QALAM_200_GROUP_SCHEMA ) === QALAM_200_GROUP_SCHEMA_VALUE ) { return; }
	$states = get_option( QALAM_200_GROUP_OPTION, array() );
	$states = is_array( $states ) ? $states : array();
	foreach ( qalam_200_feature_groups() as $group_key => $group ) {
		if ( ! array_key_exists( $group_key, $states ) ) {
			$enabled = false;
			foreach ( (array) $group['children'] as $child ) {
				if ( qalam_200_raw_leaf_enabled( (string) $child ) ) { $enabled = true; break; }
			}
			$states[ $group_key ] = $enabled ? 1 : 0;
		}
	}
	update_option( QALAM_200_GROUP_OPTION, $states, false );
	// Normalize the old low-level storage once so any legacy helper that still reads
	// tutor_addons_config/tutor_option sees the same binary product state.
	foreach ( qalam_200_feature_groups() as $group_key => $group ) {
		$enabled = ! empty( $states[ $group_key ] );
		foreach ( (array) $group['children'] as $child ) { qalam_200_set_leaf_state( (string) $child, $enabled ); }
	}
	update_option( QALAM_200_GROUP_SCHEMA, QALAM_200_GROUP_SCHEMA_VALUE, false );
}
add_action( 'admin_init', 'qalam_200_seed_groups', 8 );

/** Product catalog: grouped services plus truly independent add-ons. */
function qalam_200_product_catalog(): array {
	$leaf_catalog = qalam_feature_catalog();
	$grouped_children = array();
	$out = array();
	foreach ( qalam_200_feature_groups() as $group_key => $group ) {
		$children = array_values( (array) $group['children'] );
		$grouped_children = array_merge( $grouped_children, $children );
		$access = qalam_200_group_access( $group_key );
		$missing = array(); $errors = array();
		foreach ( $children as $child ) {
			if ( isset( $leaf_catalog[ $child ] ) ) {
				$missing = array_replace( $missing, (array) ( $leaf_catalog[ $child ]['missing'] ?? array() ) );
				if ( ! empty( $leaf_catalog[ $child ]['error'] ) ) { $errors[] = $leaf_catalog[ $child ]['error']; }
			}
		}
		$dependency_labels = array();
		foreach ( (array) ( $group['depends_groups'] ?? array() ) as $dep ) {
			if ( ! qalam_200_group_enabled( (string) $dep ) ) {
				$dependency_labels[] = qalam_200_feature_groups()[ $dep ]['name'] ?? $dep;
			}
		}
		$local = qalam_200_group_local_enabled( $group_key );
		$effective = qalam_200_group_enabled( $group_key );
		$status = 'متوقف'; $status_key = 'disabled';
		if ( ! $access['allowed'] ) { $status='غير متاح في الباقة'; $status_key='locked'; }
		elseif ( $missing || $dependency_labels ) { $status='ينقصه متطلب'; $status_key='dependency'; }
		elseif ( $effective ) { $status='مفعل'; $status_key='enabled'; }
		$out[ $group_key ] = array_merge( $group, array(
			'key'=>$group_key, 'type'=>'group', 'enabled'=>$local, 'effective_enabled'=>$effective,
			'access'=>$access, 'missing'=>$missing, 'dependency_labels'=>$dependency_labels,
			'error'=>$errors ? reset( $errors ) : array(), 'status'=>$status, 'status_key'=>$status_key,
			'manage_url_resolved'=>(string) $group['manage_url'],
		) );
	}
	$grouped_children = array_unique( $grouped_children );
	$icon_overrides = qalam_200_single_icon_overrides();
	foreach ( $leaf_catalog as $key => $feature ) {
		if ( in_array( $key, $grouped_children, true ) ) { continue; }
		if ( isset( $icon_overrides[ $key ] ) ) { $feature['icon_url'] = $icon_overrides[ $key ]; }
		$out[ $key ] = $feature;
	}
	return $out;
}

function qalam_200_set_leaf_state( string $feature_key, bool $enable ) {
	$defs = qalam_180_feature_definitions();
	if ( empty( $defs[ $feature_key ] ) ) { return new WP_Error( 'missing_feature', 'الميزة غير موجودة.' ); }
	$def = $defs[ $feature_key ];
	if ( 'packaged' === (string) $def['type'] ) {
		return qalam_180_set_packaged_addon( (string) $def['addon'], $enable );
	}
	if ( ! empty( $def['option_key'] ) && function_exists( 'tutor_utils' ) ) {
		tutor_utils()->update_option( (string) $def['option_key'], $enable ? 1 : 0 );
		return true;
	}
	$states = get_option( QALAM_180_FEATURE_OPTION, array() );
	$states = is_array( $states ) ? $states : array();
	$states[ $feature_key ] = $enable ? 1 : 0;
	update_option( QALAM_180_FEATURE_OPTION, $states, false );
	return true;
}

function qalam_200_set_group_state( string $group_key, bool $enable ) {
	$groups = qalam_200_feature_groups();
	if ( empty( $groups[ $group_key ] ) ) { return new WP_Error( 'missing_group', 'الخدمة غير موجودة.' ); }
	if ( $enable ) {
		$access = qalam_200_group_access( $group_key );
		if ( empty( $access['allowed'] ) ) { return new WP_Error( 'locked', $access['reason'] ?: 'الخدمة غير متاحة في الباقة الحالية.' ); }
		foreach ( (array) ( $groups[ $group_key ]['depends_groups'] ?? array() ) as $dependency ) {
			if ( ! qalam_200_group_enabled( (string) $dependency ) ) {
				return new WP_Error( 'dependency', 'فعّل أولًا: ' . ( qalam_200_feature_groups()[ $dependency ]['name'] ?? $dependency ) );
			}
		}
	}
	// Roll back all relevant options if one packaged add-on rejects the transition.
	$snapshot = array(
		'groups'=>get_option( QALAM_200_GROUP_OPTION, array() ),
		'internal'=>get_option( QALAM_180_FEATURE_OPTION, array() ),
		'tutor'=>get_option( 'tutor_option', array() ),
		'addons'=>get_option( 'tutor_addons_config', array() ),
	);
	foreach ( (array) $groups[ $group_key ]['children'] as $child ) {
		$result = qalam_200_set_leaf_state( (string) $child, $enable );
		if ( is_wp_error( $result ) ) {
			update_option( QALAM_200_GROUP_OPTION, $snapshot['groups'], false );
			update_option( QALAM_180_FEATURE_OPTION, $snapshot['internal'], false );
			update_option( 'tutor_option', $snapshot['tutor'], false );
			update_option( 'tutor_addons_config', $snapshot['addons'], false );
			return $result;
		}
	}
	$states = get_option( QALAM_200_GROUP_OPTION, array() );
	$states = is_array( $states ) ? $states : array();
	$states[ $group_key ] = $enable ? 1 : 0;
	update_option( QALAM_200_GROUP_OPTION, $states, false );
	return true;
}

/** Unified product toggle endpoint. */
function qalam_200_toggle_product(): void {
	check_ajax_referer( 'qalam_200_toggle_product', 'nonce' );
	if ( ! current_user_can( 'manage_tutor' ) ) { wp_send_json_error( array( 'message'=>'معندكش صلاحية.' ), 403 ); }
	$key = sanitize_key( (string) ( $_POST['feature'] ?? '' ) );
	$enable = isset( $_POST['enable'] ) && '1' === (string) wp_unslash( $_POST['enable'] );
	$catalog = qalam_200_product_catalog();
	if ( function_exists( 'qalam_290_feature_visible' ) && ! qalam_290_feature_visible( $key ) ) { wp_send_json_error( array( 'message'=>'هذا الخيار غير متاح لنوع الموقع الحالي.' ), 403 ); }
	if ( ! isset( $catalog[ $key ] ) ) { wp_send_json_error( array( 'message'=>'الملحق غير موجود.' ), 404 ); }
	if ( 'group' === (string) $catalog[ $key ]['type'] ) {
		$result = qalam_200_set_group_state( $key, $enable );
	} else {
		$access = qalam_feature_access( $key );
		if ( $enable && empty( $access['allowed'] ) ) { wp_send_json_error( array( 'message'=>$access['reason'] ?: 'الملحق غير متاح في الباقة.' ), 403 ); }
		$result = qalam_200_set_leaf_state( $key, $enable );
	}
	if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message'=>$result->get_error_message() ), 422 ); }
	wp_send_json_success( array( 'message'=>$enable ? 'تم تفعيل الملحق بالكامل.' : 'تم تعطيل الملحق بالكامل.' ) );
}
add_action( 'wp_ajax_qalam_200_toggle_product', 'qalam_200_toggle_product' );

/** Prevent old low-level endpoint from splitting a unified product back into sub-features. */
function qalam_200_block_legacy_child_toggle(): void {
	$key = sanitize_key( (string) ( $_REQUEST['feature'] ?? '' ) );
	if ( $key && qalam_200_group_for_child( $key ) ) {
		wp_send_json_error( array( 'message'=>'يتم التحكم في هذه الوظيفة من الملحق الرئيسي فقط.' ), 409 );
	}
}
add_action( 'wp_ajax_qalam_180_toggle_feature', 'qalam_200_block_legacy_child_toggle', 0 );

/** Native Tutor add-on requests may not split a unified Qalam product either. */
function qalam_200_block_native_group_child_toggle(): void {
	$raw = isset( $_REQUEST['addonFieldNames'] ) ? (string) wp_unslash( $_REQUEST['addonFieldNames'] ) : '';
	$form = $raw ? json_decode( $raw, true ) : array();
	if ( ! is_array( $form ) ) { return; }
	foreach ( $form as $reference => $state ) {
		$leaf = qalam_180_feature_key_for_addon_reference( (string) $reference );
		if ( $leaf && qalam_200_group_for_child( $leaf ) ) {
			wp_send_json_error( array( 'message'=>'هذا الملحق جزء من خدمة موحدة في قلم ويتم التحكم فيه من بطاقة الخدمة الرئيسية.' ), 409 );
		}
	}
}
add_action( 'wp_ajax_addon_enable_disable', 'qalam_200_block_native_group_child_toggle', -10 );

/** Product image renderer: actual image assets only, no letters/Dashicons on catalog cards. */
function qalam_200_render_product_icon( string $key, array $feature ): void {
	$url = (string) ( $feature['icon_url'] ?? '' );
	if ( ! $url && 'packaged' === (string) ( $feature['type'] ?? '' ) && ! empty( $feature['addon'] ) && function_exists( 'qalam_060_addon_icon_url' ) ) {
		$url = qalam_060_addon_icon_url( (string) $feature['addon'] );
	}
	if ( ! $url ) { $url = plugin_dir_url( TUTOR_FILE ) . 'assets/images/qalam-addons/question-bank.svg'; }
	echo '<div class="qalam-200-product-icon"><img src="' . esc_url( $url ) . '" alt="" loading="lazy"></div>';
}

function qalam_200_render_addons(): void {
	if ( ! current_user_can( 'manage_tutor' ) ) { wp_die( 'غير مسموح.' ); }
	$catalog = qalam_200_product_catalog();
	$categories = qalam_180_feature_categories();
	$enabled_count = 0;
	foreach ( $catalog as $row ) { if ( ! empty( $row['effective_enabled'] ) ) { $enabled_count++; } }
	?>
	<div class="wrap qalam-050-wrap qalam-180-wrap qalam-200-wrap" dir="rtl">
		<div class="qalam-050-hero qalam-180-hero qalam-200-hero">
			<div><span class="qalam-050-eyebrow">Qalam LMS</span><h1>الملحقات</h1><p>فعّل الخدمات التي تحتاجها وأدر إعداداتها من مكان واحد.</p></div>
			<div class="qalam-180-stats"><div><strong><?php echo esc_html( count( $catalog ) ); ?></strong><span>ملحق فعلي</span></div><div><strong><?php echo esc_html( $enabled_count ); ?></strong><span>مفعّل حاليًا</span></div></div>
		</div>
		<div class="qalam-180-toolbar"><input type="search" class="qalam-050-search" placeholder="ابحث في الملحقات..." data-qalam-feature-search><select data-qalam-feature-category><option value="">كل الأقسام</option><?php foreach($categories as $cat_key=>$cat_label):?><option value="<?php echo esc_attr($cat_key);?>"><?php echo esc_html($cat_label);?></option><?php endforeach;?></select><span class="qalam-200-toolbar-hint">ابحث أو اختر قسمًا للوصول للملحق المطلوب بسرعة.</span></div>
		<?php foreach ( $categories as $cat_key=>$cat_label ) : $rows=array_filter($catalog,static fn($r)=>(($r['category']??'')===$cat_key)); if(!$rows)continue; ?>
		<section class="qalam-180-section" data-qalam-feature-section data-category="<?php echo esc_attr($cat_key);?>">
			<div class="qalam-050-section-head"><div><h2><?php echo esc_html($cat_label);?></h2><p><?php echo esc_html(count($rows));?> ملحق في هذا القسم</p></div></div>
			<div class="qalam-addon-grid qalam-180-grid qalam-200-grid" data-qalam-feature-grid>
			<?php foreach($rows as $key=>$feature): if ( function_exists( 'qalam_290_feature_visible' ) && ! qalam_290_feature_visible( (string) $key ) ) { continue; } $locked=empty($feature['access']['allowed']); $can_enable=!$locked&&empty($feature['missing'])&&empty($feature['dependency_labels']); $search=$feature['name'].' '.$feature['description'].' '.$cat_label; ?>
			<article class="qalam-addon-card qalam-180-card qalam-200-card <?php echo $locked?'is-locked':'';?>" data-qalam-feature-card data-category="<?php echo esc_attr($cat_key);?>" data-qalam-search-text="<?php echo esc_attr($search);?>">
				<div class="qalam-addon-top"><?php qalam_200_render_product_icon((string)$key,$feature);?><span class="qalam-addon-status is-<?php echo esc_attr($feature['status_key']);?>"><?php echo esc_html($feature['status']);?></span></div>
				<h3><?php echo esc_html($feature['name']);?></h3><p><?php echo esc_html($feature['description']);?></p>
				<?php if($locked):?><div class="qalam-addon-note is-locked"><strong>الباقة:</strong> <?php echo esc_html($feature['access']['reason']?:'غير متاح في الباقة الحالية.');?></div><?php endif;?>
				<?php if(!empty($feature['dependency_labels'])):?><div class="qalam-addon-note"><strong>فعّل أولًا:</strong> <?php echo esc_html(implode('، ',$feature['dependency_labels']));?></div><?php endif;?>
				<?php if(!empty($feature['missing'])):?><div class="qalam-addon-note"><strong>متطلب خارجي:</strong> <?php echo esc_html(implode('، ',array_values($feature['missing'])));?></div><?php endif;?>
				<div class="qalam-addon-actions qalam-180-actions"><button type="button" class="button <?php echo $feature['enabled']?'':'button-primary';?>" data-qalam-product-toggle data-feature="<?php echo esc_attr($key);?>" data-enable="<?php echo $feature['enabled']?'0':'1';?>" <?php disabled(!$feature['enabled']&&!$can_enable);?>><?php echo esc_html($feature['enabled']?'تعطيل':'تفعيل');?></button><?php if(!empty($feature['manage_url_resolved'])&&!empty($feature['effective_enabled'])):?><a class="button" href="<?php echo esc_url($feature['manage_url_resolved']);?>">إدارة</a><?php endif;?></div>
			</article>
			<?php endforeach;?>
			</div>
		</section>
		<?php endforeach;?>
	</div>
	<?php
}

/** Replace the low-level 0.18 catalog callback with the product-level catalog. */
function qalam_200_admin_menu_callback( $menu ) {
	if ( isset( $menu['group_three']['addons'] ) && is_array( $menu['group_three']['addons'] ) ) {
		$menu['group_three']['addons']['page_title']='ملحقات قلم';
		$menu['group_three']['addons']['menu_title']='الملحقات';
		$menu['group_three']['addons']['callback']='qalam_200_render_addons';
	}
	return $menu;
}
add_filter( 'tutor_admin_menu', 'qalam_200_admin_menu_callback', PHP_INT_MAX );

function qalam_200_admin_assets(): void {
	$page = isset($_GET['page']) ? sanitize_key((string)wp_unslash($_GET['page'])) : '';
	if('tutor-addons'!==$page)return;
	$base=plugin_dir_url(TUTOR_FILE);
	wp_enqueue_style('qalam-200-addons',$base.'assets/css/qalam-200-addons.css',array('qalam-180-addons'),QALAM_LMS_UI_VERSION);
	wp_enqueue_script('qalam-200-addons',$base.'assets/js/qalam-200-addons.js',array(),QALAM_LMS_UI_VERSION,true);
	wp_localize_script('qalam-200-addons','Qalam200',array('ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('qalam_200_toggle_product'),'toggleFailed'=>'تعذر تغيير حالة الملحق.'));
}
add_action('admin_enqueue_scripts','qalam_200_admin_assets',PHP_INT_MAX);

/** Export only customer-facing SaaS products. Children are metadata, not separate plans. */
function qalam_200_catalog_for_saas(): array {
	$out=array();
	foreach(qalam_200_product_catalog() as $key=>$feature){
		$out[$key]=array('key'=>$key,'name'=>$feature['name'],'category'=>$feature['category'],'type'=>$feature['type'],'children'=>array_values((array)($feature['children']??array())));
	}
	return $out;
}
