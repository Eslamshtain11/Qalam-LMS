<?php
/**
 * Qalam LMS 0.32.0 — Student Mobile Learning API + Android push bridge.
 *
 * Provides a Qalam-owned, versioned API so the Android application never needs
 * to know WordPress/Tutor internals. Authorization is always enforced server-side.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_300_VERSION = '0.32.0-mobile-push-bridge';
const QALAM_300_SCHEMA_OPTION = 'qalam_300_mobile_schema';
const QALAM_300_SCHEMA_VALUE  = '2';

require_once __DIR__ . '/mobile/Auth.php';
require_once __DIR__ . '/mobile/Learning.php';
require_once __DIR__ . '/mobile/Experience.php';
require_once __DIR__ . '/mobile/Push.php';
require_once __DIR__ . '/mobile/Api.php';

function qalam_300_install_schema(): void {
    if ( QALAM_300_SCHEMA_VALUE === (string) get_option( QALAM_300_SCHEMA_OPTION, '' ) ) {
        return;
    }
    Qalam_Mobile_Auth::install();
    Qalam_Mobile_Learning::install_attempt_table();
    Qalam_Mobile_Learning::install_progress_table();
    update_option( QALAM_300_SCHEMA_OPTION, QALAM_300_SCHEMA_VALUE, false );
}
add_action( 'init', 'qalam_300_install_schema', 4 );

add_action( 'rest_api_init', array( 'Qalam_Mobile_Api', 'register' ) );

/** Revoke all mobile sessions when a student's password is reset. */
function qalam_300_after_password_reset( WP_User $user ): void {
    Qalam_Mobile_Auth::revoke_user_sessions( (int) $user->ID );
}
add_action( 'after_password_reset', 'qalam_300_after_password_reset', 10, 1 );

/** Cheap opportunistic cleanup; no extra cron job is required. */
function qalam_300_cleanup_expired_sessions(): void {
    if ( get_transient( 'qalam_300_session_cleanup' ) ) {
        return;
    }
    set_transient( 'qalam_300_session_cleanup', 1, DAY_IN_SECONDS );
    global $wpdb;
    $table = Qalam_Mobile_Auth::table();
    $cutoff = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE refresh_expires_at < %s OR (revoked_at IS NOT NULL AND revoked_at < %s)", current_time( 'mysql', true ), $cutoff ) );
}
add_action( 'init', 'qalam_300_cleanup_expired_sessions', 50 );

/** Allow deployments to expose current Mobile API capability without secrets. */
function qalam_300_capability_manifest(): array {
    return array(
        'version' => QALAM_300_VERSION,
        'base_url' => rest_url( Qalam_Mobile_Api::NS ),
        'auth' => array( 'opaque_access_token', 'rotating_refresh_token' ),
        'domains' => array( 'config', 'profile', 'courses', 'lessons', 'progress', 'quizzes', 'assignments', 'certificates', 'announcements', 'notifications', 'devices' ),
        'dynamic_surfaces' => true,
    );
}
