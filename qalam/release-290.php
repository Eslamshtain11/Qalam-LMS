<?php
/**
 * Qalam LMS 0.30.0 — Qalam Cloud licensing, entitlements and event connector.
 *
 * The connector is deliberately maintenance-owned: LMS owners and managers cannot
 * see credentials, activate sites, or alter cloud connectivity.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_290_VERSION       = '0.31.0-mobile-entitlements';
const QALAM_290_STATE_OPTION  = 'qalam_cloud_state_v1';
const QALAM_290_SECRET_OPTION = 'qalam_cloud_secret_v1';
const QALAM_290_QUEUE_OPTION  = 'qalam_cloud_event_queue_v1';
const QALAM_290_VERSION_OPTION = 'qalam_cloud_connector_version';
const QALAM_290_CLOUD_URL     = 'https://cloud.qalam-qa.com';
const QALAM_290_TENANT_CODE   = 'F1QA2026';
const QALAM_290_GRACE_SECONDS = 259200;
// The feature catalog itself is unchanged through 0.30.0, so keep the Cloud contract version stable.
const QALAM_290_CATALOG_VERSION = 'qalam-lms-0.29.3:categories-12:groups-9:features-45';

function qalam_290_is_maintenance_user( $user = null ): bool {
    $user = $user ?: wp_get_current_user();
    if ( ! $user || ! $user->exists() ) { return false; }
    $roles = (array) $user->roles;
    if ( array_intersect( array( 'qalam_owner', 'qalam_manager' ), $roles ) ) { return false; }
    return user_can( $user, 'manage_options' ) || ( defined( 'QALAM_230_DESIGN_CAP' ) && user_can( $user, QALAM_230_DESIGN_CAP ) );
}

function qalam_290_state(): array {
    $state = get_option( QALAM_290_STATE_OPTION, array() );
    return is_array( $state ) ? $state : array();
}

function qalam_290_save_state( array $state ): void {
    update_option( QALAM_290_STATE_OPTION, $state, false );
}

function qalam_290_crypto_key(): string {
    return hash( 'sha256', wp_salt( 'auth' ) . '|' . wp_salt( 'secure_auth' ) . '|qalam-cloud-v1', true );
}

function qalam_290_encrypt_secret( string $plain ) {
    if ( ! function_exists( 'openssl_encrypt' ) ) { return new WP_Error( 'openssl_missing', 'OpenSSL مطلوب لحماية مفتاح الموقع.' ); }
    try { $iv = random_bytes( 12 ); } catch ( Throwable $error ) { return new WP_Error( 'random_failed', 'تعذر إنشاء قيمة عشوائية آمنة.' ); }
    $tag = '';
    $cipher = openssl_encrypt( $plain, 'aes-256-gcm', qalam_290_crypto_key(), OPENSSL_RAW_DATA, $iv, $tag );
    if ( false === $cipher ) { return new WP_Error( 'encrypt_failed', 'تعذر تشفير مفتاح الموقع.' ); }
    return rtrim( strtr( base64_encode( $iv . $tag . $cipher ), '+/', '-_' ), '=' );
}

function qalam_290_decrypt_secret() {
    $encoded = (string) get_option( QALAM_290_SECRET_OPTION, '' );
    if ( '' === $encoded || ! function_exists( 'openssl_decrypt' ) ) { return new WP_Error( 'secret_missing', 'مفتاح الموقع غير متاح.' ); }
    $raw = base64_decode( strtr( $encoded, '-_', '+/' ), true );
    if ( false === $raw || strlen( $raw ) < 29 ) { return new WP_Error( 'secret_invalid', 'مفتاح الموقع المخزن غير صالح.' ); }
    $plain = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', qalam_290_crypto_key(), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ) );
    return false === $plain ? new WP_Error( 'secret_invalid', 'تعذر فك مفتاح الموقع.' ) : $plain;
}

function qalam_290_is_list( array $value ): bool {
    if ( array() === $value ) { return true; }
    return array_keys( $value ) === range( 0, count( $value ) - 1 );
}

function qalam_290_canonicalize( $value ) {
    if ( ! is_array( $value ) ) { return $value; }
    if ( qalam_290_is_list( $value ) ) { return array_map( 'qalam_290_canonicalize', $value ); }
    ksort( $value, SORT_STRING );
    foreach ( $value as $key => $item ) { $value[ $key ] = qalam_290_canonicalize( $item ); }
    return $value;
}

function qalam_290_json( $value ): string {
    return (string) wp_json_encode( qalam_290_canonicalize( $value ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

function qalam_290_b64url( string $raw ): string {
    return rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
}

function qalam_290_cache_signature( array $manifest ): string {
    return hash_hmac( 'sha256', qalam_290_json( $manifest ), hash( 'sha256', qalam_290_crypto_key() . 'cache', true ) );
}

function qalam_290_cached_manifest(): ?array {
    $state = qalam_290_state();
    $manifest = isset( $state['manifest'] ) && is_array( $state['manifest'] ) ? $state['manifest'] : null;
    if ( ! $manifest || empty( $state['cache_signature'] ) ) { return null; }
    return hash_equals( qalam_290_cache_signature( $manifest ), (string) $state['cache_signature'] ) ? $manifest : null;
}

/**
 * Expose a safe credit-only contract to the standalone Qalam dashboard.
 * No activation secret, license key or Cloud credential is sent to the browser.
 */
function qalam_290_dashboard_credit_bootstrap(): void {
    if ( ! function_exists( 'qalam_210_is_dashboard_request' ) || ! qalam_210_is_dashboard_request() ) { return; }
    if ( function_exists( 'qalam_210_user_is_platform_admin' ) && ! qalam_210_user_is_platform_admin() && ! current_user_can( 'manage_options' ) ) { return; }

    $manifest = qalam_290_cached_manifest();
    $credits  = is_array( $manifest ) && is_array( $manifest['ai_credits'] ?? null ) ? $manifest['ai_credits'] : array();
    $limit    = max( 0, absint( $credits['limit'] ?? 0 ) );
    $used     = max( 0, absint( $credits['used'] ?? 0 ) );
    $reserved = max( 0, absint( $credits['reserved'] ?? 0 ) );
    $remaining = array_key_exists( 'remaining', $credits )
        ? max( 0, absint( $credits['remaining'] ) )
        : max( 0, $limit - $used - $reserved );
    $subscription = is_array( $manifest ) && is_array( $manifest['subscription'] ?? null ) ? $manifest['subscription'] : array();
    $state = qalam_290_state();

    $payload = array(
        'available'   => ! empty( $credits ),
        'limit'       => $limit,
        'used'        => $used,
        'reserved'    => $reserved,
        'remaining'   => $remaining,
        'status'      => sanitize_key( $subscription['status'] ?? 'unknown' ),
        'last_sync'   => sanitize_text_field( (string) ( $state['last_sync_at'] ?? '' ) ),
        'ai_url'      => function_exists( 'qalam_210_dashboard_url' ) ? qalam_210_dashboard_url( 'ai' ) : '',
    );
    echo '<script>window.QalamCloudCredits=' . wp_json_encode( $payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';</script>';
}
add_action( 'wp_head', 'qalam_290_dashboard_credit_bootstrap', 40 );

function qalam_290_platform_type(): string {
    $manifest = qalam_290_cached_manifest();
    if ( is_array( $manifest ) && in_array( (string) ( $manifest['platform_type'] ?? '' ), array( 'individual', 'academy' ), true ) ) {
        return (string) $manifest['platform_type'];
    }
    $brand = function_exists( 'qalam_230_brand' ) ? qalam_230_brand() : array();
    return 'individual' === ( $brand['platform_type'] ?? '' ) ? 'individual' : 'academy';
}

function qalam_290_platform_feature_allowed( string $feature_key ): bool {
    $manifest = qalam_290_cached_manifest();
    if ( ! $manifest ) { return true; }
    $policy = isset( $manifest['platform_policy'] ) && is_array( $manifest['platform_policy'] ) ? $manifest['platform_policy'] : array();
    if ( in_array( $feature_key, (array) ( $policy['hidden_features'] ?? array() ), true ) ) { return false; }
    if ( in_array( $feature_key, (array) ( $policy['hidden_groups'] ?? array() ), true ) ) { return false; }
    return true;
}

function qalam_290_feature_visible( string $feature_key ): bool {
    return qalam_290_platform_feature_allowed( $feature_key );
}

function qalam_290_instance_id(): string {
    $id = (string) get_option( 'qalam_cloud_instance_id', '' );
    if ( '' === $id ) {
        $id = wp_generate_uuid4() . '-' . wp_generate_password( 24, false, false );
        update_option( 'qalam_cloud_instance_id', $id, false );
    }
    return $id;
}

function qalam_290_cloud_url(): string {
    $state = qalam_290_state();
    return untrailingslashit( esc_url_raw( $state['cloud_url'] ?? QALAM_290_CLOUD_URL ) );
}

function qalam_290_decode_response( $response ) {
    if ( is_wp_error( $response ) ) { return $response; }
    $status = (int) wp_remote_retrieve_response_code( $response );
    $body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
    if ( $status < 200 || $status > 299 || ! is_array( $body ) ) {
        return new WP_Error( 'cloud_http_error', 'Qalam Cloud أعادت استجابة غير ناجحة.', array( 'status' => $status, 'code' => is_array( $body ) ? sanitize_key( $body['error'] ?? '' ) : '' ) );
    }
    return $body;
}

function qalam_290_public_request( string $path, array $body ) {
    return qalam_290_decode_response( wp_remote_post( qalam_290_cloud_url() . $path, array(
        'timeout' => 20,
        'headers' => array( 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
        'body'    => qalam_290_json( $body ),
    ) ) );
}

function qalam_290_signed_request( string $path, array $body, array $extra_headers = array() ) {
    $state = qalam_290_state();
    $secret = qalam_290_decrypt_secret();
    if ( is_wp_error( $secret ) || empty( $state['activation_id'] ) ) { return is_wp_error( $secret ) ? $secret : new WP_Error( 'activation_missing', 'الموقع غير مفعّل.' ); }
    $timestamp = time();
    $nonce = wp_generate_password( 32, false, false );
    $canonical = qalam_290_json( $body );
    $signature = qalam_290_b64url( hash_hmac( 'sha256', $timestamp . "\n" . $nonce . "\n" . $canonical, $secret, true ) );
    $headers = array_merge( array(
        'Content-Type'       => 'application/json',
        'Accept'             => 'application/json',
        'X-Qalam-Activation' => (string) $state['activation_id'],
        'X-Qalam-Timestamp'  => (string) $timestamp,
        'X-Qalam-Nonce'      => $nonce,
        'X-Qalam-Signature'  => $signature,
    ), $extra_headers );
    return qalam_290_decode_response( wp_remote_post( qalam_290_cloud_url() . $path, array( 'timeout' => 20, 'headers' => $headers, 'body' => $canonical ) ) );
}

function qalam_290_ai_usage_request( string $action, array $payload = array(), string $idempotency_key = '' ) {
    $body = array_merge( array( 'action' => sanitize_key( $action ) ), $payload );
    $headers = $idempotency_key ? array( 'Idempotency-Key' => sanitize_text_field( $idempotency_key ) ) : array();
    return qalam_290_signed_request( '/api/v1/ai/usage', $body, $headers );
}

function qalam_290_adjust_local_ai_credits( int $used_delta ): void {
    if ( $used_delta < 1 ) { return; }
    $state = qalam_290_state();
    if ( empty( $state['manifest'] ) || ! is_array( $state['manifest'] ) ) { return; }
    $credits = is_array( $state['manifest']['ai_credits'] ?? null ) ? $state['manifest']['ai_credits'] : array();
    $credits['used'] = absint( $credits['used'] ?? 0 ) + $used_delta;
    $credits['remaining'] = max( 0, absint( $credits['limit'] ?? 0 ) - $credits['used'] - absint( $credits['reserved'] ?? 0 ) );
    $state['manifest']['ai_credits'] = $credits;
    $state['cache_signature'] = qalam_290_cache_signature( $state['manifest'] );
    qalam_290_save_state( $state );
}

function qalam_290_ai_reserve( int $quantity, string $idempotency_key ) {
    if ( $quantity < 1 || strlen( $idempotency_key ) < 12 ) { return new WP_Error( 'qalam_ai_usage_invalid', 'بيانات رصيد الذكاء الاصطناعي غير صالحة.' ); }
    return qalam_290_ai_usage_request( 'reserve', array( 'quantity' => $quantity, 'idempotency_key' => $idempotency_key ), $idempotency_key );
}

function qalam_290_ai_commit( string $reservation_id, int $quantity = 0 ) {
    $payload = array( 'reservation_id' => sanitize_text_field( $reservation_id ) );
    if ( $quantity > 0 ) { $payload['quantity'] = $quantity; }
    $result = qalam_290_ai_usage_request( 'commit', $payload );
    if ( ! is_wp_error( $result ) && 'committed' === ( $result['status'] ?? '' ) && empty( $result['duplicate'] ) ) { qalam_290_adjust_local_ai_credits( absint( $result['quantity'] ?? $quantity ) ); }
    return $result;
}

function qalam_290_ai_release( string $reservation_id ) {
    return qalam_290_ai_usage_request( 'release', array( 'reservation_id' => sanitize_text_field( $reservation_id ) ) );
}

function qalam_290_store_manifest( array $manifest ): void {
    $state = qalam_290_state();
    $state['manifest'] = $manifest;
    $state['cache_signature'] = qalam_290_cache_signature( $manifest );
    $state['last_success'] = time();
    $state['last_attempt'] = time();
    $state['last_error'] = '';
    $subscription = isset( $manifest['subscription'] ) && is_array( $manifest['subscription'] ) ? $manifest['subscription'] : array();
    $state['status'] = ! empty( $subscription['active'] ) ? sanitize_key( $subscription['status'] ?? 'active' ) : 'suspended';
    qalam_290_save_state( $state );
    qalam_290_reconcile_manifest_features( $manifest );
}

/** Whether Qalam Cloud is the active source of truth for feature access. */
function qalam_290_cloud_managed(): bool {
    $state = qalam_290_state();
    return ! empty( $state['activation_id'] ) && ! empty( $state['manifest'] ) && is_array( $state['manifest'] );
}

/** Mirror the signed manifest into the real local add-on states. */
function qalam_290_reconcile_manifest_features( array $manifest ): void {
    static $running = false;
    if ( $running || ! function_exists( 'qalam_180_feature_definitions' ) || ! function_exists( 'qalam_200_set_leaf_state' ) ) { return; }
    $running = true;
    $features = is_array( $manifest['features'] ?? null ) ? $manifest['features'] : array();
    $errors = array();
    foreach ( qalam_180_feature_definitions() as $key => $definition ) {
        $allowed = ! empty( $features[ $key ] );
        $current = function_exists( 'qalam_200_raw_leaf_enabled' ) ? qalam_200_raw_leaf_enabled( (string) $key ) : false;
        if ( $current === $allowed ) { continue; }
        $result = qalam_200_set_leaf_state( (string) $key, $allowed );
        if ( is_wp_error( $result ) ) { $errors[ sanitize_key( (string) $key ) ] = sanitize_text_field( $result->get_error_message() ); }
    }
    if ( defined( 'QALAM_200_GROUP_OPTION' ) && function_exists( 'qalam_290_group_features' ) ) {
        $groups = get_option( QALAM_200_GROUP_OPTION, array() );
        $groups = is_array( $groups ) ? $groups : array();
        foreach ( qalam_290_group_features() as $group => $children ) { $groups[ $group ] = ! empty( $features[ $group ] ) ? 1 : 0; }
        update_option( QALAM_200_GROUP_OPTION, $groups, false );
    }
    $state = qalam_290_state();
    $state['entitlement_reconcile_errors'] = $errors;
    qalam_290_save_state( $state );
    $running = false;
}

function qalam_290_mark_sync_error( string $message ): void {
    $state = qalam_290_state();
    $state['last_attempt'] = time();
    $state['last_error'] = sanitize_text_field( $message );
    $last = (int) ( $state['last_success'] ?? 0 );
    $state['status'] = $last && ( time() - $last ) <= QALAM_290_GRACE_SECONDS ? 'offline_grace' : 'suspended';
    qalam_290_save_state( $state );
}

function qalam_290_brand_payload(): array {
    $branding = class_exists( 'Qalam_Mobile_Experience' ) ? Qalam_Mobile_Experience::branding() : array();
    $brand = function_exists( 'qalam_230_brand' ) ? qalam_230_brand() : array();
    if ( ! $branding ) {
        $palettes = function_exists( 'qalam_230_palettes' ) ? qalam_230_palettes() : array();
        $palette = isset( $palettes[ $brand['palette'] ?? '' ] ) ? $palettes[ $brand['palette'] ] : array();
        $branding = array(
            'platform_name' => sanitize_text_field( $brand['platform_name'] ?? get_bloginfo( 'name' ) ),
            'logo_url'      => esc_url_raw( $brand['logo_url'] ?? '' ),
            'custom_primary'=> sanitize_hex_color( $brand['custom_primary'] ?? '' ) ?: ( $palette['primary'] ?? '' ),
            'custom_primary_2'=> sanitize_hex_color( $brand['custom_primary_2'] ?? '' ) ?: ( $palette['primary_2'] ?? '' ),
            'custom_accent' => sanitize_hex_color( $brand['custom_accent'] ?? '' ) ?: ( $palette['accent'] ?? '' ),
            'email'         => sanitize_email( $brand['email'] ?? get_option( 'admin_email' ) ),
            'phone'         => sanitize_text_field( $brand['phone'] ?? '' ),
            'whatsapp'      => sanitize_text_field( $brand['whatsapp'] ?? '' ),
        );
    }
    return array(
        'name'          => sanitize_text_field( $branding['platform_name'] ?? $brand['platform_name'] ?? get_bloginfo( 'name' ) ),
        'platform_type' => function_exists( 'qalam_290_platform_type' ) ? qalam_290_platform_type() : ( 'individual' === ( $brand['platform_type'] ?? '' ) ? 'individual' : 'academy' ),
        'api_base_url'  => rest_url( 'qalam-mobile/v1' ),
        'branding'      => $branding,
    );
}

function qalam_290_activate_site( string $license_key ) {
    $license_key = trim( $license_key );
    if ( strlen( $license_key ) < 20 ) { return new WP_Error( 'license_invalid', 'مفتاح الترخيص غير صالح.' ); }
    $response = qalam_290_public_request( '/api/v1/licenses/activate', array(
        'license_key'    => $license_key,
        'site_url'       => home_url( '/' ),
        'instance_id'    => qalam_290_instance_id(),
        'plugin_version' => QALAM_290_VERSION,
    ) );
    if ( is_wp_error( $response ) ) { return $response; }
    if ( empty( $response['activation_id'] ) || empty( $response['tenant_id'] ) || empty( $response['site_secret'] ) || empty( $response['manifest'] ) || ! is_array( $response['manifest'] ) ) {
        return new WP_Error( 'activation_response_invalid', 'استجابة التفعيل غير مكتملة.' );
    }
    $encrypted = qalam_290_encrypt_secret( (string) $response['site_secret'] );
    if ( is_wp_error( $encrypted ) ) { return $encrypted; }
    update_option( QALAM_290_SECRET_OPTION, $encrypted, false );
    $state = array(
        'cloud_url'    => QALAM_290_CLOUD_URL,
        'activation_id'=> sanitize_text_field( $response['activation_id'] ),
        'tenant_id'    => sanitize_text_field( $response['tenant_id'] ),
        'tenant_code'  => sanitize_text_field( $response['manifest']['code'] ?? QALAM_290_TENANT_CODE ),
        'activated_at' => time(),
        'status'       => 'active',
    );
    qalam_290_save_state( $state );
    qalam_290_store_manifest( $response['manifest'] );
    $branding = qalam_290_signed_request( '/api/v1/sites/branding', qalam_290_brand_payload() );
    if ( is_wp_error( $branding ) ) { qalam_290_mark_sync_error( $branding->get_error_message() ); }
    qalam_290_sync_license();
    return true;
}

function qalam_290_sync_license() {
    $state = qalam_290_state();
    if ( empty( $state['activation_id'] ) ) { return new WP_Error( 'activation_missing', 'الموقع غير مفعّل.' ); }
    $response = qalam_290_signed_request( '/api/v1/licenses/check', array( 'tenant_code' => $state['tenant_code'] ?? QALAM_290_TENANT_CODE, 'plugin_version' => QALAM_290_VERSION ) );
    if ( is_wp_error( $response ) || empty( $response['manifest'] ) || ! is_array( $response['manifest'] ) ) {
        $error = is_wp_error( $response ) ? $response : new WP_Error( 'manifest_missing', 'الـManifest غير موجودة.' );
        qalam_290_mark_sync_error( $error->get_error_message() );
        return $error;
    }
    qalam_290_store_manifest( $response['manifest'] );
    qalam_290_signed_request( '/api/v1/sites/branding', qalam_290_brand_payload() );
    return true;
}

function qalam_290_control_permission( WP_REST_Request $request ) {
    $state = qalam_290_state();
    $activation_id = sanitize_text_field( (string) $request->get_header( 'x-qalam-activation' ) );
    $timestamp = absint( $request->get_header( 'x-qalam-timestamp' ) );
    $nonce = sanitize_text_field( (string) $request->get_header( 'x-qalam-nonce' ) );
    $signature = sanitize_text_field( (string) $request->get_header( 'x-qalam-signature' ) );
    if ( empty( $state['activation_id'] ) || ! hash_equals( (string) $state['activation_id'], $activation_id ) || ! $timestamp || abs( time() - $timestamp ) > 300 || strlen( $nonce ) < 12 || strlen( $signature ) < 32 ) {
        return new WP_Error( 'qalam_control_unauthorized', 'طلب التحكم غير صالح.', array( 'status' => 401 ) );
    }
    $replay_key = 'qalam_control_' . hash( 'sha256', $activation_id . '|' . $nonce );
    if ( get_transient( $replay_key ) ) { return new WP_Error( 'qalam_control_replay', 'تم استخدام الطلب من قبل.', array( 'status' => 409 ) ); }
    $secret = qalam_290_decrypt_secret();
    $body = $request->get_json_params();
    if ( is_wp_error( $secret ) || ! is_array( $body ) || 'sync' !== sanitize_key( $body['action'] ?? '' ) ) {
        return new WP_Error( 'qalam_control_unauthorized', 'طلب التحكم غير صالح.', array( 'status' => 401 ) );
    }
    $expected = qalam_290_b64url( hash_hmac( 'sha256', $timestamp . "\n" . $nonce . "\n" . qalam_290_json( $body ), $secret, true ) );
    if ( ! hash_equals( $expected, $signature ) ) { return new WP_Error( 'qalam_control_unauthorized', 'توقيع التحكم غير صالح.', array( 'status' => 401 ) ); }
    set_transient( $replay_key, 1, 10 * MINUTE_IN_SECONDS );
    return true;
}

function qalam_290_control_sync( WP_REST_Request $request ) {
    $result = qalam_290_sync_license();
    if ( is_wp_error( $result ) ) { return $result; }
    $state = qalam_290_state();
    return rest_ensure_response( array( 'ok' => true, 'status' => sanitize_key( $state['status'] ?? 'inactive' ), 'synced_at' => time() ) );
}

function qalam_290_register_control_route(): void {
    register_rest_route( 'qalam-cloud/v1', '/control', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'qalam_290_control_sync',
        'permission_callback' => 'qalam_290_control_permission',
    ) );
}
add_action( 'rest_api_init', 'qalam_290_register_control_route' );

function qalam_290_group_features(): array {
    return array(
        'question_bank_suite' => array( 'question_bank', 'content_bank', 'quiz_import_export' ),
        'advanced_exams' => array( 'standalone_exams', 'randomized_exams', 'dynamic_exams' ),
        'artificial_intelligence' => array( 'ai_question_generation', 'pdf_question_generation', 'ai_background_worker' ),
        'video_player' => array( 'qalam_video_player', 'video_subtitles' ),
        'certificates_suite' => array( 'certificates', 'certificate_builder' ),
        'instructor_suite' => array( 'multi_instructor', 'instructor_marketplace' ),
        'reports_suite' => array( 'advanced_reports', 'student_analytics', 'progress_reset' ),
        'communications_suite' => array( 'notifications', 'email_notifications' ),
        'account_access_suite' => array( 'social_login', 'email_update' ),
    );
}

function qalam_290_feature_catalog_contract(): array {
    $definitions = function_exists( 'qalam_180_feature_definitions' ) ? qalam_180_feature_definitions() : array();
    $out = array();
    foreach ( $definitions as $key => $definition ) {
        $out[ sanitize_key( (string) $key ) ] = array(
            'key' => sanitize_key( (string) $key ),
            'name' => sanitize_text_field( $definition['name'] ?? $key ),
            'category' => sanitize_key( $definition['category'] ?? '' ),
            'type' => sanitize_key( $definition['type'] ?? 'internal' ),
            'addon' => sanitize_text_field( $definition['addon'] ?? '' ),
            'depends' => array_values( array_map( 'sanitize_key', (array) ( $definition['depends'] ?? array() ) ) ),
        );
    }
    return array( 'version' => QALAM_290_CATALOG_VERSION, 'categories' => function_exists( 'qalam_180_feature_categories' ) ? qalam_180_feature_categories() : array(), 'groups' => qalam_290_group_features(), 'features' => $out );
}

function qalam_290_group_for_feature( string $feature_key ): string {
    foreach ( qalam_290_group_features() as $group => $features ) {
        if ( in_array( $feature_key, $features, true ) ) { return $group; }
    }
    return '';
}

function qalam_290_saas_feature_access( $default, string $feature_key ) {
    $state = qalam_290_state();
    if ( empty( $state['activation_id'] ) ) { return $default; }
    $manifest = qalam_290_cached_manifest();
    if ( ! $manifest || 'suspended' === ( $state['status'] ?? '' ) ) {
        return array( 'allowed' => false, 'reason' => 'تعذر التحقق من الاشتراك بعد انتهاء فترة السماح.', 'plan' => '', 'source' => 'qalam_cloud' );
    }
    $features = isset( $manifest['features'] ) && is_array( $manifest['features'] ) ? $manifest['features'] : array();
    if ( ! qalam_290_platform_feature_allowed( $feature_key ) ) {
        return array( 'allowed' => false, 'reason' => 'هذا الخيار غير متاح لنوع الموقع الحالي.', 'plan' => sanitize_key( $manifest['plan'] ?? '' ), 'source' => 'qalam_cloud' );
    }
    // Every leaf is independently licensable. Legacy group keys remain available,
    // but a false group must never cancel an explicitly allowed leaf feature.
    $allowed = ! empty( $features[ $feature_key ] );
    return array(
        'allowed' => $allowed,
        'reason'  => $allowed ? '' : 'الميزة غير متاحة في اشتراك المؤسسة الحالي.',
        'plan'    => sanitize_key( $manifest['plan'] ?? '' ),
        'source'  => 'qalam_cloud',
    );
}
add_filter( 'qalam_saas_feature_access', 'qalam_290_saas_feature_access', 100, 2 );

function qalam_290_schedule(): void {
    qalam_290_instance_id();
    if ( ! wp_next_scheduled( 'qalam_cloud_hourly_sync' ) ) { wp_schedule_event( time() + 300, 'hourly', 'qalam_cloud_hourly_sync' ); }
    if ( QALAM_290_VERSION !== (string) get_option( QALAM_290_VERSION_OPTION, '' ) ) {
        update_option( QALAM_290_VERSION_OPTION, QALAM_290_VERSION, false );
        if ( ! wp_next_scheduled( 'qalam_cloud_upgrade_sync' ) ) {
            wp_schedule_single_event( time() + 5, 'qalam_cloud_upgrade_sync' );
        }
    }
}
add_action( 'init', 'qalam_290_schedule', 30 );
add_action( 'qalam_cloud_hourly_sync', 'qalam_290_sync_license' );
add_action( 'qalam_cloud_upgrade_sync', 'qalam_290_sync_license' );

function qalam_290_queue_event( string $type, array $payload ): void {
    $state = qalam_290_state();
    if ( empty( $state['activation_id'] ) ) { return; }
    $queue = get_option( QALAM_290_QUEUE_OPTION, array() );
    $queue = is_array( $queue ) ? $queue : array();
    $item = array( 'type' => sanitize_key( $type ), 'payload' => $payload, 'created_at' => time() );
    $item['key'] = hash( 'sha256', qalam_290_json( $item ) . '|' . wp_generate_uuid4() );
    $queue[] = $item;
    if ( count( $queue ) > 500 ) { $queue = array_slice( $queue, -500 ); }
    update_option( QALAM_290_QUEUE_OPTION, $queue, false );
    if ( ! wp_next_scheduled( 'qalam_cloud_process_events' ) ) { wp_schedule_single_event( time() + 5, 'qalam_cloud_process_events' ); }
}

function qalam_290_process_events(): void {
    $queue = get_option( QALAM_290_QUEUE_OPTION, array() );
    $queue = is_array( $queue ) ? $queue : array();
    $remaining = array();
    foreach ( array_slice( $queue, 0, 20 ) as $item ) {
        $response = qalam_290_signed_request( '/api/v1/events', array( 'type' => $item['type'], 'payload' => $item['payload'] ), array( 'Idempotency-Key' => $item['key'] ) );
        if ( is_wp_error( $response ) ) { $remaining[] = $item; }
    }
    $remaining = array_merge( $remaining, array_slice( $queue, 20 ) );
    update_option( QALAM_290_QUEUE_OPTION, $remaining, false );
    if ( $remaining && ! wp_next_scheduled( 'qalam_cloud_process_events' ) ) { wp_schedule_single_event( time() + 300, 'qalam_cloud_process_events' ); }
}
add_action( 'qalam_cloud_process_events', 'qalam_290_process_events' );

function qalam_290_lesson_event( $lesson_id, $user_id ): void {
    qalam_290_queue_event( 'lesson.completed', array( 'lesson_id' => absint( $lesson_id ), 'student_id' => absint( $user_id ), 'course_id' => absint( wp_get_post_parent_id( $lesson_id ) ) ) );
}
add_action( 'tutor_lesson_completed_after', 'qalam_290_lesson_event', 100, 2 );

function qalam_290_course_event( $course_id, $user_id ): void {
    $payload = array( 'course_id' => absint( $course_id ), 'student_id' => absint( $user_id ) );
    qalam_290_queue_event( 'course.completed', $payload );
    qalam_290_queue_event( 'certificate.available', $payload );
}
add_action( 'tutor_course_complete_after', 'qalam_290_course_event', 100, 2 );

function qalam_290_quiz_event( $attempt_id, $course_id = 0, $user_id = 0 ): void {
    qalam_290_queue_event( 'quiz.attempt_ended', array( 'attempt_id' => absint( $attempt_id ), 'course_id' => absint( $course_id ), 'student_id' => absint( $user_id ) ) );
}
add_action( 'tutor_quiz/attempt_ended', 'qalam_290_quiz_event', 100, 3 );

function qalam_290_content_event( int $post_id, WP_Post $post, bool $update ): void {
    if ( wp_is_post_revision( $post_id ) || 'publish' !== $post->post_status ) { return; }
    $map = array( 'courses' => 'course.published', 'lesson' => 'lesson.published', 'tutor_announcements' => 'announcement.published', 'tutor_zoom_meeting' => 'session.published', 'tutor-google-meet' => 'session.published' );
    if ( isset( $map[ $post->post_type ] ) ) { qalam_290_queue_event( $map[ $post->post_type ], array( 'content_id' => $post_id, 'parent_id' => absint( $post->post_parent ), 'updated' => $update ) ); }
}
add_action( 'save_post', 'qalam_290_content_event', 100, 3 );

function qalam_290_ability_permission(): bool {
    return qalam_290_is_maintenance_user();
}

function qalam_290_ability_status( $input = null ): array {
    $state    = qalam_290_state();
    $manifest = qalam_290_cached_manifest();
    $features = $manifest && is_array( $manifest['features'] ?? null ) ? $manifest['features'] : array();
    $credits = $manifest && is_array( $manifest['ai_credits'] ?? null ) ? $manifest['ai_credits'] : array();
    return array(
        'ok'                 => true,
        'activated'          => ! empty( $state['activation_id'] ),
        'tenant_code'        => sanitize_key( $state['tenant_code'] ?? '' ),
        'activation_id'      => sanitize_text_field( $state['activation_id'] ?? '' ),
        'status'             => sanitize_key( $state['status'] ?? 'inactive' ),
        'plan'               => sanitize_key( $manifest['plan'] ?? '' ),
        'entitlements_total' => count( $features ),
        'entitlements_on'    => count( array_filter( $features ) ),
        'platform_type'      => sanitize_key( $manifest['platform_type'] ?? qalam_290_platform_type() ),
        'catalog_version'    => sanitize_text_field( $manifest['feature_catalog_version'] ?? ( $manifest['catalog_version'] ?? '' ) ),
        'ai_questions_limit' => absint( $credits['limit'] ?? 0 ),
        'ai_questions_used'  => absint( $credits['used'] ?? 0 ),
        'ai_questions_remaining' => absint( $credits['remaining'] ?? 0 ),
        'entitlement_reconcile_errors' => is_array( $state['entitlement_reconcile_errors'] ?? null ) ? $state['entitlement_reconcile_errors'] : array(),
        'last_success'       => absint( $state['last_success'] ?? 0 ),
        'last_error'         => sanitize_text_field( $state['last_error'] ?? '' ),
        'secret_stored'      => ! is_wp_error( qalam_290_decrypt_secret() ),
        'plugin_version'     => QALAM_290_VERSION,
    );
}

function qalam_290_ability_activate( $input ) {
    $input  = is_array( $input ) ? $input : array();
    $result = qalam_290_activate_site( sanitize_text_field( $input['license_key'] ?? '' ) );
    return is_wp_error( $result ) ? $result : qalam_290_ability_status();
}

function qalam_290_ability_sync( $input = null ) {
    $result = qalam_290_sync_license();
    return is_wp_error( $result ) ? $result : qalam_290_ability_status();
}

function qalam_290_ability_runtime_qa( $input = null ) {
    $state = qalam_290_state();
    if ( empty( $state['activation_id'] ) ) {
        return new WP_Error( 'qalam_cloud_not_activated', 'Qalam Cloud is not activated.' );
    }
    $probe_id = wp_generate_uuid4();
    $event = qalam_290_signed_request(
        '/api/v1/events',
        array( 'type' => 'qa.runtime_probe', 'payload' => array( 'probe_id' => $probe_id, 'site_url' => home_url( '/' ) ) ),
        array( 'Idempotency-Key' => 'qa-runtime-' . $probe_id )
    );
    if ( is_wp_error( $event ) ) { return $event; }

    $device_id = 'qa-' . substr( hash( 'sha256', qalam_290_instance_id() ), 0, 24 );
    $device = qalam_290_signed_request( '/api/v1/devices/register', array(
        'student_id' => 'qa-runtime', 'device_id' => $device_id,
        'fcm_token' => 'qa-no-firebase-' . substr( hash( 'sha256', $probe_id ), 0, 32 ),
        'app_version' => QALAM_290_VERSION,
    ) );
    if ( is_wp_error( $device ) ) { return $device; }

    $notification = qalam_290_signed_request( '/api/v1/notifications', array(
        'student_id' => 'qa-runtime', 'channel' => 'system',
        'title' => 'Qalam Cloud Runtime QA', 'body' => 'Firebase credentials are intentionally absent in this release.',
        'data' => array( 'probe_id' => $probe_id ),
    ) );
    if ( is_wp_error( $notification ) ) { return $notification; }
    $inbox = qalam_290_signed_request( '/api/v1/notifications/inbox', array( 'student_id' => 'qa-runtime', 'limit' => 10 ) );
    if ( is_wp_error( $inbox ) ) { return $inbox; }

    return array(
        'ok' => true,
        'probe_id' => $probe_id,
        'event_duplicate' => ! empty( $event['duplicate'] ),
        'device_registered' => ! empty( $device['id'] ),
        'notification_id' => sanitize_text_field( $notification['id'] ?? '' ),
        'notification_status' => sanitize_key( $notification['status'] ?? '' ),
        'inbox_count' => is_array( $inbox['data'] ?? null ) ? count( $inbox['data'] ) : 0,
        'group_allow' => qalam_290_saas_feature_access( false, 'question_bank_suite' ),
        'feature_allow' => qalam_290_saas_feature_access( false, 'question_bank' ),
        'unknown_feature_deny' => qalam_290_saas_feature_access( true, 'qalam_runtime_unknown' ),
    );
}

function qalam_290_register_ability_category(): void {
    if ( function_exists( 'wp_register_ability_category' ) ) {
        wp_register_ability_category( 'qalam-cloud', array( 'label' => 'Qalam Cloud', 'description' => 'Secure Qalam Cloud maintenance and runtime verification.' ) );
    }
}
add_action( 'wp_abilities_api_categories_init', 'qalam_290_register_ability_category' );

function qalam_290_register_abilities(): void {
    if ( ! function_exists( 'wp_register_ability' ) ) { return; }
    $output = array( 'type' => 'object' );
    $base = array(
        'category' => 'qalam-cloud',
        'permission_callback' => 'qalam_290_ability_permission',
        'meta' => array( 'show_in_rest' => true ),
    );
    wp_register_ability( 'qalam-cloud/status', array_merge( $base, array(
        'label' => 'Qalam Cloud Status', 'description' => 'Returns the site activation, entitlement, and synchronization status without exposing credentials.',
        'execute_callback' => 'qalam_290_ability_status', 'output_schema' => $output,
        'meta' => array( 'show_in_rest' => true, 'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ) ),
    ) ) );
    wp_register_ability( 'qalam-cloud/activate-site', array_merge( $base, array(
        'label' => 'Activate Qalam Cloud Site', 'description' => 'Activates this F1 site with a Qalam Cloud license and stores its returned site secret encrypted.',
        'input_schema' => array( 'type' => 'object', 'additionalProperties' => false, 'properties' => array( 'license_key' => array( 'type' => 'string', 'minLength' => 20, 'maxLength' => 200 ) ), 'required' => array( 'license_key' ) ),
        'execute_callback' => 'qalam_290_ability_activate', 'output_schema' => $output,
        'meta' => array( 'show_in_rest' => true, 'annotations' => array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ) ),
    ) ) );
    wp_register_ability( 'qalam-cloud/sync', array_merge( $base, array(
        'label' => 'Synchronize Qalam Cloud', 'description' => 'Synchronizes the signed entitlement manifest and actual site branding with Qalam Cloud.',
        'execute_callback' => 'qalam_290_ability_sync', 'output_schema' => $output,
        'meta' => array( 'show_in_rest' => true, 'annotations' => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ) ),
    ) ) );
    wp_register_ability( 'qalam-cloud/run-runtime-qa', array_merge( $base, array(
        'label' => 'Run Qalam Cloud Runtime QA', 'description' => 'Runs a signed event, device, notification, inbox, and entitlement workflow against Qalam Cloud.',
        'execute_callback' => 'qalam_290_ability_runtime_qa', 'output_schema' => $output,
        'meta' => array( 'show_in_rest' => true, 'annotations' => array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ) ),
    ) ) );
}
add_action( 'wp_abilities_api_init', 'qalam_290_register_abilities' );

function qalam_290_admin_menu(): void {
    add_menu_page( 'Qalam Cloud', 'Qalam Cloud', defined( 'QALAM_230_DESIGN_CAP' ) ? QALAM_230_DESIGN_CAP : 'manage_options', 'qalam-cloud-maintenance', 'qalam_290_render_admin', 'dashicons-cloud', 3 );
}
add_action( 'admin_menu', 'qalam_290_admin_menu', 120 );

function qalam_290_admin_guard(): void {
    if ( ! qalam_290_is_maintenance_user() ) { wp_die( esc_html__( 'غير مسموح.', 'tutor' ), 403 ); }
}

function qalam_290_activate_handler(): void {
    qalam_290_admin_guard();
    check_admin_referer( 'qalam_290_activate', 'qalam_290_nonce' );
    $result = qalam_290_activate_site( sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) ) );
    $url = add_query_arg( is_wp_error( $result ) ? array( 'qalam_cloud_error' => rawurlencode( $result->get_error_message() ) ) : array( 'qalam_cloud_activated' => 1 ), admin_url( 'admin.php?page=qalam-cloud-maintenance' ) );
    wp_safe_redirect( $url ); exit;
}
add_action( 'admin_post_qalam_290_activate', 'qalam_290_activate_handler' );

function qalam_290_sync_handler(): void {
    qalam_290_admin_guard();
    check_admin_referer( 'qalam_290_sync', 'qalam_290_nonce' );
    $result = qalam_290_sync_license();
    $url = add_query_arg( is_wp_error( $result ) ? array( 'qalam_cloud_error' => rawurlencode( $result->get_error_message() ) ) : array( 'qalam_cloud_synced' => 1 ), admin_url( 'admin.php?page=qalam-cloud-maintenance' ) );
    wp_safe_redirect( $url ); exit;
}
add_action( 'admin_post_qalam_290_sync', 'qalam_290_sync_handler' );

function qalam_290_render_admin(): void {
    qalam_290_admin_guard();
    $state = qalam_290_state();
    $manifest = qalam_290_cached_manifest();
    $enabled = $manifest && is_array( $manifest['features'] ?? null ) ? count( array_filter( $manifest['features'] ) ) : 0;
    $credits = $manifest && is_array( $manifest['ai_credits'] ?? null ) ? $manifest['ai_credits'] : array();
    ?>
    <div class="wrap" dir="rtl"><h1>ربط Qalam Cloud</h1><p>واجهة صيانة خاصة بمؤسسة قلم. مفتاح الموقع يُشفّر بمفاتيح WordPress ولا يظهر مرة أخرى.</p>
    <?php if ( isset( $_GET['qalam_cloud_error'] ) ) : ?><div class="notice notice-error"><p><?php echo esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['qalam_cloud_error'] ) ) ) ); ?></p></div><?php endif; ?>
    <?php if ( isset( $_GET['qalam_cloud_activated'] ) || isset( $_GET['qalam_cloud_synced'] ) ) : ?><div class="notice notice-success"><p>تمت العملية بنجاح.</p></div><?php endif; ?>
    <table class="widefat striped" style="max-width:850px"><tbody>
    <tr><th>الحالة</th><td><strong><?php echo esc_html( $state['status'] ?? 'غير مفعّل' ); ?></strong></td></tr>
    <tr><th>Tenant</th><td><?php echo esc_html( $state['tenant_code'] ?? '—' ); ?></td></tr>
    <tr><th>نوع الموقع</th><td><?php echo esc_html( 'individual' === ( $manifest['platform_type'] ?? '' ) ? 'فردي' : 'مؤسسة / أكاديمية' ); ?></td></tr>
    <tr><th>Activation ID</th><td><code><?php echo esc_html( $state['activation_id'] ?? '—' ); ?></code></td></tr>
    <tr><th>الخطة / الميزات</th><td><?php echo esc_html( ( $manifest['plan'] ?? '—' ) . ' / ' . $enabled ); ?></td></tr>
    <tr><th>رصيد الذكاء الاصطناعي</th><td><?php echo esc_html( absint( $credits['used'] ?? 0 ) . ' مستخدم من ' . absint( $credits['limit'] ?? 0 ) . ' — المتبقي ' . absint( $credits['remaining'] ?? 0 ) ); ?></td></tr>
    <tr><th>نسخة كتالوج المزايا</th><td><code><?php echo esc_html( $manifest['feature_catalog_version'] ?? ( $manifest['catalog_version'] ?? '—' ) ); ?></code></td></tr>
    <tr><th>آخر مزامنة ناجحة</th><td><?php echo ! empty( $state['last_success'] ) ? esc_html( wp_date( 'Y-m-d H:i:s', (int) $state['last_success'] ) ) : '—'; ?></td></tr>
    <tr><th>آخر خطأ</th><td><?php echo esc_html( $state['last_error'] ?? '—' ); ?></td></tr>
    </tbody></table>
    <?php if ( empty( $state['activation_id'] ) ) : ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:850px;margin-top:24px;padding:20px;background:#fff;border:1px solid #ccd0d4">
        <input type="hidden" name="action" value="qalam_290_activate"><?php wp_nonce_field( 'qalam_290_activate', 'qalam_290_nonce' ); ?>
        <label><strong>مفتاح الترخيص</strong><br><input type="password" name="license_key" autocomplete="off" required class="regular-text" style="direction:ltr;margin-top:8px"></label>
        <?php submit_button( 'تفعيل الموقع' ); ?>
    </form>
    <?php else : ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:20px"><input type="hidden" name="action" value="qalam_290_sync"><?php wp_nonce_field( 'qalam_290_sync', 'qalam_290_nonce' ); ?><?php submit_button( 'مزامنة الآن', 'primary', 'submit', false ); ?></form>
    <?php endif; ?></div>
    <?php
}

function qalam_290_suspension_gate(): void {
    if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || qalam_290_is_maintenance_user() ) { return; }
    $state = qalam_290_state();
    if ( empty( $state['activation_id'] ) || 'suspended' !== ( $state['status'] ?? '' ) ) { return; }
    $brand = function_exists( 'qalam_230_brand' ) ? qalam_230_brand() : array();
    $manifest = qalam_290_cached_manifest();
    $suspension = is_array( $manifest ) && is_array( $manifest['suspension'] ?? null ) ? $manifest['suspension'] : array();
    status_header( 503 ); nocache_headers();
    $name = sanitize_text_field( $brand['platform_name'] ?? get_bloginfo( 'name' ) );
    $message = sanitize_text_field( $suspension['message'] ?? 'تم تعليق الاشتراك. تواصل مع الدعم لإعادة التفعيل مرة أخرى.' );
    $contact = sanitize_text_field( $suspension['whatsapp'] ?? ( $suspension['phone'] ?? ( $suspension['email'] ?? ( $brand['whatsapp'] ?? ( $brand['phone'] ?? ( $brand['email'] ?? '' ) ) ) ) ) );
    echo '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>تم تعليق الاشتراك — ' . esc_html( $name ) . '</title><style>body{font-family:system-ui;background:#f4f7fb;color:#17212b;display:grid;place-items:center;min-height:100vh;margin:0;padding:24px;box-sizing:border-box}.card{width:min(100%,680px);background:#fff;border:1px solid #dce6ef;border-radius:24px;padding:40px;box-sizing:border-box;text-align:center;box-shadow:0 20px 60px #10203318}.badge{display:inline-block;padding:8px 14px;border-radius:999px;background:#fff1f2;color:#be123c;font-weight:700;margin-bottom:18px}h1{margin:0 0 14px;color:#17212b}p{line-height:1.9;color:#687685;font-size:17px}.contact{display:inline-block;margin-top:8px;padding:12px 18px;border-radius:12px;background:#eef2ff;color:#3730a3;font-weight:700}</style><main class="card"><span class="badge">الاشتراك معلّق</span><h1>' . esc_html( $name ) . '</h1><p>' . esc_html( $message ) . '</p>' . ( $contact ? '<p class="contact">الدعم: ' . esc_html( $contact ) . '</p>' : '' ) . '<small>مؤسسة قلم للخدمات الإلكترونية</small></main></html>';
    exit;
}
add_action( 'template_redirect', 'qalam_290_suspension_gate', 0 );
