<?php
/**
 * Qalam Student mobile authentication.
 *
 * Opaque, rotating tokens are used intentionally. The Android app never receives
 * WordPress cookies, application passwords, Cloud site secrets, or Firebase secrets.
 */
defined( 'ABSPATH' ) || exit;

final class Qalam_Mobile_Auth {
    const ACCESS_TTL  = 1800;      // 30 minutes.
    const REFRESH_TTL = 2592000;   // 30 days.

    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'qalam_mobile_sessions';
    }

    public static function install(): void {
        global $wpdb;
        $table = self::table();
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            access_hash char(64) NOT NULL,
            refresh_hash char(64) NOT NULL,
            access_expires_at datetime NOT NULL,
            refresh_expires_at datetime NOT NULL,
            device_id varchar(191) NULL,
            user_agent varchar(255) NULL,
            ip_hash char(64) NULL,
            revoked_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY access_hash (access_hash),
            UNIQUE KEY refresh_hash (refresh_hash),
            KEY user_id (user_id),
            KEY refresh_expiry (refresh_expires_at),
            KEY revoked_at (revoked_at)
        ) {$charset};" );
        update_option( 'qalam_mobile_schema', '1', false );
    }

    public static function maybe_install(): void {
        if ( '1' !== (string) get_option( 'qalam_mobile_schema', '' ) ) {
            self::install();
        }
    }

    private static function random_token(): string {
        return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
    }

    private static function hash_token( string $token ): string {
        return hash( 'sha256', $token );
    }

    private static function now_mysql(): string {
        return current_time( 'mysql', true );
    }

    private static function future_mysql( int $seconds ): string {
        return gmdate( 'Y-m-d H:i:s', time() + $seconds );
    }

    public static function is_student_account( WP_User $user ): bool {
        if ( ! $user->exists() || user_can( $user, 'manage_options' ) ) {
            return false;
        }
        $blocked_roles = array( 'qalam_owner', 'qalam_manager', 'tutor_instructor' );
        return ! (bool) array_intersect( $blocked_roles, (array) $user->roles );
    }

    public static function issue( int $user_id, string $device_id = '' ) {
        global $wpdb;
        $access  = self::random_token();
        $refresh = self::random_token();
        $now     = self::now_mysql();
        $ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $agent   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

        $wpdb->insert(
            self::table(),
            array(
                'user_id'            => $user_id,
                'access_hash'        => self::hash_token( $access ),
                'refresh_hash'       => self::hash_token( $refresh ),
                'access_expires_at'  => self::future_mysql( self::ACCESS_TTL ),
                'refresh_expires_at' => self::future_mysql( self::REFRESH_TTL ),
                'device_id'          => $device_id ? substr( sanitize_text_field( $device_id ), 0, 191 ) : null,
                'user_agent'         => $agent ? substr( $agent, 0, 255 ) : null,
                'ip_hash'            => $ip ? hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ) : null,
                'created_at'         => $now,
                'updated_at'         => $now,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( ! $wpdb->insert_id ) {
            return new WP_Error( 'session_create_failed', 'تعذر إنشاء جلسة التطبيق.', array( 'status' => 500 ) );
        }

        return array(
            'access_token'       => $access,
            'refresh_token'      => $refresh,
            'expires_in'         => self::ACCESS_TTL,
            'refresh_expires_in' => self::REFRESH_TTL,
        );
    }

    public static function bearer( WP_REST_Request $request ): string {
        $header = trim( (string) $request->get_header( 'authorization' ) );
        if ( preg_match( '/^Bearer\s+([A-Za-z0-9_-]{20,})$/i', $header, $m ) ) {
            return $m[1];
        }
        return '';
    }

    public static function authenticate( WP_REST_Request $request ) {
        global $wpdb;
        $token = self::bearer( $request );
        if ( ! $token ) {
            return new WP_Error( 'mobile_auth_required', 'يلزم تسجيل الدخول.', array( 'status' => 401 ) );
        }
        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::table() . ' WHERE access_hash = %s AND revoked_at IS NULL LIMIT 1',
                self::hash_token( $token )
            )
        );
        if ( ! $row || strtotime( (string) $row->access_expires_at . ' UTC' ) <= time() ) {
            return new WP_Error( 'mobile_token_expired', 'انتهت جلسة التطبيق.', array( 'status' => 401 ) );
        }
        $user = get_userdata( (int) $row->user_id );
        if ( ! $user || ! self::is_student_account( $user ) ) {
            return new WP_Error( 'mobile_account_denied', 'هذا الحساب غير متاح في تطبيق الطالب.', array( 'status' => 403 ) );
        }
        wp_set_current_user( (int) $row->user_id );
        $request->set_param( '_qalam_mobile_user_id', (int) $row->user_id );
        $request->set_param( '_qalam_mobile_session_id', (int) $row->id );
        return true;
    }

    public static function rotate( string $refresh_token ) {
        global $wpdb;
        $hash = self::hash_token( trim( $refresh_token ) );
        $row  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE refresh_hash = %s AND revoked_at IS NULL LIMIT 1', $hash ) );
        if ( ! $row || strtotime( (string) $row->refresh_expires_at . ' UTC' ) <= time() ) {
            return new WP_Error( 'invalid_refresh_token', 'انتهت جلسة تسجيل الدخول. سجّل الدخول مرة أخرى.', array( 'status' => 401 ) );
        }
        $user = get_userdata( (int) $row->user_id );
        if ( ! $user || ! self::is_student_account( $user ) ) {
            return new WP_Error( 'mobile_account_denied', 'هذا الحساب غير متاح في تطبيق الطالب.', array( 'status' => 403 ) );
        }
        $access  = self::random_token();
        $refresh = self::random_token();
        $table   = self::table();
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET access_hash=%s,refresh_hash=%s,access_expires_at=%s,refresh_expires_at=%s,updated_at=%s WHERE id=%d AND refresh_hash=%s AND revoked_at IS NULL",
                self::hash_token( $access ),
                self::hash_token( $refresh ),
                self::future_mysql( self::ACCESS_TTL ),
                self::future_mysql( self::REFRESH_TTL ),
                self::now_mysql(),
                (int) $row->id,
                $hash
            )
        );
        if ( 1 !== (int) $updated ) {
            return new WP_Error( 'refresh_token_reused', 'تم استخدام رمز التجديد بالفعل. أعد المحاولة بالرمز الأحدث أو سجّل الدخول من جديد.', array( 'status' => 401 ) );
        }
        return array(
            'access_token'       => $access,
            'refresh_token'      => $refresh,
            'expires_in'         => self::ACCESS_TTL,
            'refresh_expires_in' => self::REFRESH_TTL,
            'user_id'            => (int) $row->user_id,
        );
    }

    public static function revoke_request( WP_REST_Request $request ): bool {
        global $wpdb;
        $session_id = (int) $request->get_param( '_qalam_mobile_session_id' );
        if ( $session_id < 1 ) {
            return false;
        }
        return false !== $wpdb->update(
            self::table(),
            array( 'revoked_at' => self::now_mysql(), 'updated_at' => self::now_mysql() ),
            array( 'id' => $session_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    public static function revoke_user_sessions( int $user_id ): void {
        global $wpdb;
        $wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . ' SET revoked_at = %s, updated_at = %s WHERE user_id = %d AND revoked_at IS NULL', self::now_mysql(), self::now_mysql(), $user_id ) );
    }
}
