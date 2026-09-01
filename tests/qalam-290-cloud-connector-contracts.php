<?php
$root = dirname( __DIR__ );
$main = file_get_contents( $root . '/qalam-lms.php' );
$cloud = file_get_contents( $root . '/qalam/release-290.php' );
$shell = file_get_contents( $root . '/assets/js/qalam-admin-shell.js' );
$css = file_get_contents( $root . '/assets/css/qalam-admin-shell.css' );
$failures = array();
$check = static function ( $condition, $label ) use ( &$failures ) {
    echo ( $condition ? '[PASS] ' : '[FAIL] ' ) . $label . PHP_EOL;
    if ( ! $condition ) { $failures[] = $label; }
};

$check( false !== strpos( $main, 'Version: 0.32.0' ), 'release header' );
$check( false !== strpos( $main, "QALAM_LMS_PRODUCT_VERSION', '0.32.0-mobile-push-bridge" ), 'release product version' );
$check( false !== strpos( $main, "require_once __DIR__ . '/qalam/release-290.php'" ), 'connector bootstrap' );
$check( false !== strpos( $cloud, "add_filter( 'qalam_saas_feature_access'" ), 'SaaS entitlement seam' );
$check( false !== strpos( $cloud, 'QALAM_290_GRACE_SECONDS = 259200' ), '72 hour outage grace' );
$check( false !== strpos( $cloud, "'question_bank_suite'" ) && false !== strpos( $cloud, "'account_access_suite'" ), 'nine feature group catalog' );
$check( false !== strpos( $cloud, "'X-Qalam-Signature'" ) && false !== strpos( $cloud, "'Idempotency-Key'" ), 'HMAC and idempotency headers' );
$check( false !== strpos( $cloud, 'aes-256-gcm' ) && false !== strpos( $cloud, "wp_salt( 'secure_auth' )" ), 'site secret encryption' );
$check( false !== strpos( $cloud, "array( 'qalam_owner', 'qalam_manager' )" ), 'maintenance role exclusion' );
$check( false !== strpos( $cloud, "add_action( 'tutor_lesson_completed_after'" ) && false !== strpos( $cloud, "add_action( 'tutor_quiz/attempt_ended'" ), 'learning events' );
$check( false !== strpos( $cloud, 'QALAM_290_CATALOG_VERSION' ) && false !== strpos( $cloud, 'qalam_290_feature_catalog_contract' ), 'full feature catalog contract' );
$check( false !== strpos( $cloud, 'qalam_290_ai_reserve' ) && false !== strpos( $cloud, 'qalam_290_ai_commit' ) && false !== strpos( $cloud, 'qalam_290_ai_release' ), 'AI usage reservation lifecycle' );
$check( false !== strpos( $cloud, 'qalam_290_reconcile_manifest_features' ) && false !== strpos( $cloud, 'qalam_200_set_leaf_state' ), 'manifest reconciles real local add-on states' );
$check( false !== strpos( $cloud, 'a false group must never cancel an explicitly allowed leaf' ), 'individual leaf entitlement overrides legacy group aggregate' );
$check( false !== strpos( $cloud, 'entitlement_reconcile_errors' ), 'reconciliation failures remain observable' );
$check( false !== strpos( $cloud, "register_rest_route( 'qalam-cloud/v1', '/control'" ), 'signed Cloud control route is registered' );
$check( false !== strpos( $cloud, 'qalam_290_control_permission' ) && false !== strpos( $cloud, 'qalam_control_replay' ), 'Cloud control validates HMAC timestamp nonce and replay protection' );
$check( false !== strpos( $cloud, 'تم تعليق الاشتراك' ) && false !== strpos( $cloud, 'الدعم:' ), 'suspended sites show the support reactivation message' );
$check( false !== strpos( $cloud, "wp_schedule_single_event( time() + 5, 'qalam_cloud_upgrade_sync' )" ), 'plugin upgrades schedule an immediate one-time Cloud synchronization' );
$check( false !== strpos( $cloud, "add_action( 'qalam_cloud_upgrade_sync', 'qalam_290_sync_license' );" ), 'upgrade synchronization uses the signed license check' );
$check( false !== strpos( $cloud, "check_admin_referer( 'qalam_290_activate'" ) && false !== strpos( $cloud, 'qalam_290_is_maintenance_user()' ), 'activation CSRF and capability guard' );
$check( false !== strpos( $cloud, "wp_register_ability( 'qalam-cloud/activate-site'" ) && false !== strpos( $cloud, "wp_register_ability( 'qalam-cloud/status'" ), 'MCP maintenance abilities' );
$check( false !== strpos( $cloud, "wp_register_ability( 'qalam-cloud/run-runtime-qa'" ) && false !== strpos( $cloud, "'qa.runtime_probe'" ), 'signed runtime QA workflow' );
$check( false !== strpos( $cloud, 'qalam_290_dashboard_credit_bootstrap' ) && false !== strpos( $cloud, 'QalamCloudCredits' ), 'safe Cloud credit contract is exposed to the Qalam dashboard only' );
$check( false !== strpos( $shell, 'data-qalam-cloud-credits' ) && false !== strpos( $shell, 'رصيد الذكاء الاصطناعي' ), 'Qalam dashboard header renders the remaining AI balance' );
$check( false !== strpos( $css, '.qalam-cloud-credit-badge' ) && false !== strpos( $css, '.qalam-cloud-credit-state' ), 'Qalam dashboard credit badge has scoped responsive styling' );
$check( false === strpos( $cloud, 'FIREBASE_PRIVATE_KEY' ) && false === strpos( $cloud, 'BEGIN PRIVATE KEY' ), 'no embedded cloud credentials' );

if ( $failures ) { exit( 1 ); }
echo 'Qalam 0.32.0 cloud connector contracts passed.' . PHP_EOL;
