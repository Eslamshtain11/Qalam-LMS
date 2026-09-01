<?php
$root = dirname( __DIR__ );
$main = file_get_contents( $root . '/qalam-lms.php' );
$release = file_get_contents( $root . '/qalam/release-300.php' );
$auth = file_get_contents( $root . '/qalam/mobile/Auth.php' );
$learning = file_get_contents( $root . '/qalam/mobile/Learning.php' );
$api = file_get_contents( $root . '/qalam/mobile/Api.php' );
$checks = array(
    '0.31 product version' => false !== strpos( $main, 'Version: 0.32.0' ) && false !== strpos( $main, "QALAM_LMS_PRODUCT_VERSION', '0.32.0-mobile-push-bridge" ),
    'release 300 loaded' => false !== strpos( $main, "qalam/release-300.php" ),
    'versioned namespace' => false !== strpos( $api, "const NS = 'qalam-mobile/v1'" ),
    'opaque access tokens' => false !== strpos( $auth, "hash( 'sha256', \$token )" ) && false !== strpos( $auth, 'random_bytes( 32 )' ),
    'refresh rotation' => false !== strpos( $auth, 'function rotate' ) && false !== strpos( $api, '/auth/refresh' ) && false !== strpos( $auth, 'refresh_token_reused' ),
    'logout revokes session' => false !== strpos( $auth, 'revoke_request' ) && false !== strpos( $api, '/auth/logout' ),
    'no wordpress cookies' => false === strpos( $api, 'wp_set_auth_cookie' ),
    'student-only role block' => false !== strpos( $auth, "'qalam_owner', 'qalam_manager', 'tutor_instructor'" ),
    'login rate limiting' => false !== strpos( $api, 'login_rate_limited' ) && false !== strpos( $api, 'set_transient' ),
    'server enrollment gate' => false !== strpos( $learning, 'EnrollmentModel::is_enrolled' ),
    'courses endpoint' => false !== strpos( $api, "'/courses'" ) && false !== strpos( $api, "'/courses/(?P<id>\\d+)'" ),
    'lessons and progress' => false !== strpos( $api, "'/lessons/(?P<id>\\d+)'" ) && false !== strpos( $api, '/complete' ) && false !== strpos( $api, '/progress' ) && false !== strpos( $learning, 'qalam_mobile_progress' ),
    'resume endpoint' => false !== strpos( $api, "'/resume'" ) && false !== strpos( $learning, 'function resume' ),
    'signed lesson media' => false !== strpos( $learning, 'signed_media_url' ) && false !== strpos( $learning, 'hash_hmac' ) && false !== strpos( $api, '/media/' ),
    'quiz immutable snapshot' => false !== strpos( $learning, 'qalam_mobile_quiz_snapshots' ) && false !== strpos( $learning, 'public_snapshot' ) && false !== strpos( $learning, 'grading_snapshot' ),
    'correct answers never public' => false !== strpos( $learning, "'questions'          => json_decode( (string) \$snapshot->public_snapshot" ) && false === strpos( $api, 'grading_snapshot' ),
    'concurrent quiz submit lock' => false !== strpos( $learning, 'FOR UPDATE' ) && false !== strpos( $learning, 'attempt_not_active' ),
    'quiz draft save' => false !== strpos( $api, '/answers' ) && false !== strpos( $learning, 'save_quiz_answers' ),
    'quiz submit/result' => false !== strpos( $api, '/submit' ) && false !== strpos( $api, "'/quiz-results'" ) && false !== strpos( $api, '/quiz-results/' ),
    'assignments' => false !== strpos( $api, "'/assignments'" ) && false !== strpos( $api, '/assignments/(?P<id>\\d+)/submit' ) && false !== strpos( $learning, "feature_guard( 'assignments' )" ),
    'certificates' => false !== strpos( $api, "'/certificates'" ) && false !== strpos( $learning, "feature_guard( 'certificates' )" ),
    'announcements' => false !== strpos( $api, "'/announcements'" ),
    'notification inbox' => false !== strpos( $api, "'/notifications/inbox'" ) && false !== strpos( $api, '/read' ) && false !== strpos( $learning, "feature_guard( 'notifications' )" ),
    'profile update bounded' => false !== strpos( $api, "array_key_exists( 'name', \$body )" ) && false === strpos( $api, "user_email' =>" ),
    'tenant isolation header' => false !== strpos( $api, 'x-qalam-tenant' ) && false !== strpos( $api, 'tenant_mismatch' ),
    'suspended tenant denied' => false !== strpos( $api, 'account_suspended' ),
    'progress schema migration' => false !== strpos( $release, "QALAM_300_SCHEMA_VALUE  = '2'" ) && false !== strpos( $release, 'install_progress_table' ),
    'password reset revokes sessions' => false !== strpos( $release, 'after_password_reset' ) && false !== strpos( $release, 'revoke_user_sessions' ),
);
$failed = array();
foreach ( $checks as $label => $ok ) {
    echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . PHP_EOL;
    if ( ! $ok ) { $failed[] = $label; }
}
if ( $failed ) {
    fwrite( STDERR, 'Failed: ' . implode( ', ', $failed ) . PHP_EOL );
    exit( 1 );
}
echo 'Qalam 0.32.0 Mobile API contracts passed.' . PHP_EOL;
