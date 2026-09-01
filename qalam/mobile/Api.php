<?php
/** REST surface consumed by Qalam Student. */
defined( 'ABSPATH' ) || exit;

final class Qalam_Mobile_Api {
    const NS = 'qalam-mobile/v1';

    public static function register(): void {
        register_rest_route( self::NS, '/health', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'health' ), 'permission_callback' => '__return_true',
        ) );
        register_rest_route( self::NS, '/config', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'config' ), 'permission_callback' => array( __CLASS__, 'public_tenant_permission' ),
        ) );
        register_rest_route( self::NS, '/auth/login', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'login' ), 'permission_callback' => array( __CLASS__, 'public_tenant_permission' ),
        ) );
        register_rest_route( self::NS, '/auth/refresh', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'refresh' ), 'permission_callback' => array( __CLASS__, 'public_tenant_permission' ),
        ) );
        register_rest_route( self::NS, '/auth/logout', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'logout' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/me', array(
            array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'me' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ) ),
            array( 'methods' => 'PATCH,POST', 'callback' => array( __CLASS__, 'update_me' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ) ),
        ) );
        register_rest_route( self::NS, '/courses', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'courses' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/courses/(?P<id>\d+)', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'course' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/lessons/(?P<id>\d+)', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'lesson' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/lessons/(?P<id>\d+)/complete', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'complete_lesson' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/lessons/(?P<id>\d+)/progress', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'lesson_progress' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/resume', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'resume' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/quizzes', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'quizzes' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/quizzes/(?P<id>\d+)/start', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'start_quiz' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/quizzes/(?P<id>\d+)/attempts/(?P<attempt_id>\d+)', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'quiz_attempt' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/quizzes/(?P<id>\d+)/attempts/(?P<attempt_id>\d+)/answers', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'save_quiz_answers' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/quizzes/(?P<id>\d+)/submit', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'submit_quiz' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/quiz-results', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'quiz_results' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/quiz-results/(?P<attempt_id>\d+)', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'quiz_result' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/assignments', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'assignments' ), 'permission_callback' => array( __CLASS__, 'assignments_permission' ),
        ) );
        register_rest_route( self::NS, '/assignments/(?P<id>\d+)', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'assignment' ), 'permission_callback' => array( __CLASS__, 'assignments_permission' ),
        ) );
        register_rest_route( self::NS, '/assignments/(?P<id>\d+)/submit', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'submit_assignment' ), 'permission_callback' => array( __CLASS__, 'assignments_permission' ),
        ) );
        register_rest_route( self::NS, '/certificates', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'certificates' ), 'permission_callback' => array( __CLASS__, 'certificates_permission' ),
        ) );
        register_rest_route( self::NS, '/announcements', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'announcements' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/notifications/inbox', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'notifications' ), 'permission_callback' => array( __CLASS__, 'notifications_permission' ),
        ) );
        register_rest_route( self::NS, '/notifications/(?P<id>\d+)/read', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'notification_read' ), 'permission_callback' => array( __CLASS__, 'notifications_permission' ),
        ) );
        register_rest_route( self::NS, '/devices/register', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'device_register' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/devices/unregister', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'device_unregister' ), 'permission_callback' => array( __CLASS__, 'auth_permission' ),
        ) );
        register_rest_route( self::NS, '/media/(?P<id>\d+)', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'media' ), 'permission_callback' => '__return_true',
        ) );
    }

    public static function response( $data, int $status = 200 ) {
        if ( is_wp_error( $data ) ) {
            return $data;
        }
        return new WP_REST_Response( $data, $status );
    }

    private static function tenant_matches( WP_REST_Request $request ) {
        if ( ! function_exists( 'qalam_290_state' ) ) {
            return true;
        }
        $state = qalam_290_state();
        $expected = strtoupper( trim( (string) ( $state['tenant_code'] ?? '' ) ) );
        $provided = strtoupper( trim( (string) $request->get_header( 'x-qalam-tenant' ) ) );
        if ( $expected && ( ! $provided || ! hash_equals( $expected, $provided ) ) ) {
            return new WP_Error( 'tenant_mismatch', 'كود المؤسسة لا يطابق هذه المنصة.', array( 'status' => 403 ) );
        }
        if ( isset( $state['status'] ) && 'suspended' === (string) $state['status'] ) {
            return new WP_Error( 'account_suspended', 'اشتراك المؤسسة موقوف حاليًا.', array( 'status' => 503 ) );
        }
        return true;
    }

    public static function public_tenant_permission( WP_REST_Request $request ) {
        return self::tenant_matches( $request );
    }

    public static function auth_permission( WP_REST_Request $request ) {
        $tenant = self::tenant_matches( $request );
        if ( is_wp_error( $tenant ) ) {
            return $tenant;
        }
        return Qalam_Mobile_Auth::authenticate( $request );
    }

    private static function feature_permission( WP_REST_Request $request, string $feature_key ) {
        $auth = self::auth_permission( $request );
        if ( is_wp_error( $auth ) ) {
            return $auth;
        }
        if ( ! Qalam_Mobile_Experience::feature_enabled( $feature_key ) ) {
            return new WP_Error( 'feature_not_entitled', 'الميزة غير متاحة في اشتراك المؤسسة الحالي.', array( 'status' => 403, 'feature' => $feature_key ) );
        }
        return true;
    }

    public static function assignments_permission( WP_REST_Request $request ) { return self::feature_permission( $request, 'assignments' ); }
    public static function certificates_permission( WP_REST_Request $request ) { return self::feature_permission( $request, 'certificates' ); }
    public static function notifications_permission( WP_REST_Request $request ) { return self::feature_permission( $request, 'notifications' ); }

    public static function health(): WP_REST_Response {
        return new WP_REST_Response( array( 'ok' => true, 'service' => 'qalam-mobile', 'version' => defined( 'QALAM_300_VERSION' ) ? QALAM_300_VERSION : 'unknown' ), 200 );
    }

    public static function config( WP_REST_Request $request ): WP_REST_Response {
        return new WP_REST_Response( Qalam_Mobile_Experience::config(), 200 );
    }

    private static function json( WP_REST_Request $request ): array {
        $body = $request->get_json_params();
        return is_array( $body ) ? $body : array();
    }

    private static function rate_key( string $login ): string {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        return 'qalam_mobile_login_' . hash( 'sha256', strtolower( trim( $login ) ) . '|' . $ip );
    }

    public static function login( WP_REST_Request $request ) {
        $body     = self::json( $request );
        $login    = sanitize_text_field( (string) ( $body['login'] ?? '' ) );
        $password = (string) ( $body['password'] ?? '' );
        $device   = sanitize_text_field( (string) ( $body['device_id'] ?? '' ) );
        if ( '' === $login || '' === $password ) {
            return new WP_Error( 'invalid_credentials', 'بيانات تسجيل الدخول غير صحيحة.', array( 'status' => 401 ) );
        }
        $key = self::rate_key( $login );
        $attempts = (int) get_transient( $key );
        if ( $attempts >= 7 ) {
            return new WP_Error( 'login_rate_limited', 'محاولات كثيرة. انتظر قليلًا ثم حاول مرة أخرى.', array( 'status' => 429 ) );
        }
        $user = wp_authenticate( $login, $password );
        if ( is_wp_error( $user ) || ! $user instanceof WP_User || ! Qalam_Mobile_Auth::is_student_account( $user ) ) {
            set_transient( $key, $attempts + 1, 10 * MINUTE_IN_SECONDS );
            return new WP_Error( 'invalid_credentials', 'بيانات تسجيل الدخول غير صحيحة.', array( 'status' => 401 ) );
        }
        delete_transient( $key );
        $tokens = Qalam_Mobile_Auth::issue( (int) $user->ID, $device );
        if ( is_wp_error( $tokens ) ) {
            return $tokens;
        }
        $tokens['student'] = Qalam_Mobile_Learning::student( (int) $user->ID );
        return self::response( $tokens );
    }

    public static function refresh( WP_REST_Request $request ) {
        $body = self::json( $request );
        $rotated = Qalam_Mobile_Auth::rotate( (string) ( $body['refresh_token'] ?? '' ) );
        if ( is_wp_error( $rotated ) ) {
            return $rotated;
        }
        $rotated['student'] = Qalam_Mobile_Learning::student( (int) $rotated['user_id'] );
        unset( $rotated['user_id'] );
        return self::response( $rotated );
    }

    public static function logout( WP_REST_Request $request ) {
        return self::response( array( 'ok' => Qalam_Mobile_Auth::revoke_request( $request ) ) );
    }

    public static function me( WP_REST_Request $request ) {
        return self::response( Qalam_Mobile_Learning::student( Qalam_Mobile_Learning::user_id( $request ) ) );
    }

    public static function update_me( WP_REST_Request $request ) {
        $uid  = Qalam_Mobile_Learning::user_id( $request );
        $body = self::json( $request );
        $update = array( 'ID' => $uid );
        if ( array_key_exists( 'name', $body ) ) {
            $update['display_name'] = sanitize_text_field( (string) $body['name'] );
        }
        if ( isset( $body['first_name'] ) ) {
            update_user_meta( $uid, 'first_name', sanitize_text_field( (string) $body['first_name'] ) );
        }
        if ( isset( $body['last_name'] ) ) {
            update_user_meta( $uid, 'last_name', sanitize_text_field( (string) $body['last_name'] ) );
        }
        if ( count( $update ) > 1 ) {
            $result = wp_update_user( $update );
            if ( is_wp_error( $result ) ) {
                return new WP_Error( 'profile_update_failed', 'تعذر تحديث الملف الشخصي.', array( 'status' => 400 ) );
            }
        }
        return self::me( $request );
    }

    public static function courses( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::courses( Qalam_Mobile_Learning::user_id( $request ) ) ); }
    public static function course( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::course_detail( absint( $request['id'] ), Qalam_Mobile_Learning::user_id( $request ) ) ); }
    public static function lesson( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::lesson( absint( $request['id'] ), Qalam_Mobile_Learning::user_id( $request ) ) ); }
    public static function complete_lesson( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::mark_complete( absint( $request['id'] ), Qalam_Mobile_Learning::user_id( $request ) ) ); }

    public static function lesson_progress( WP_REST_Request $request ) {
        $body = self::json( $request );
        return self::response( Qalam_Mobile_Learning::record_progress(
            absint( $request['id'] ),
            Qalam_Mobile_Learning::user_id( $request ),
            max( 0, (int) ( $body['position_seconds'] ?? 0 ) ),
            max( 0, (int) ( $body['duration_seconds'] ?? 0 ) ),
            ! empty( $body['completed'] )
        ) );
    }

    public static function resume( WP_REST_Request $request ) {
        return self::response( Qalam_Mobile_Learning::resume( Qalam_Mobile_Learning::user_id( $request ), absint( $request->get_param( 'course_id' ) ) ) );
    }

    public static function quizzes( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::quizzes( Qalam_Mobile_Learning::user_id( $request ) ) ); }
    public static function start_quiz( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::start_quiz( absint( $request['id'] ), Qalam_Mobile_Learning::user_id( $request ) ), 201 ); }
    public static function quiz_attempt( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::attempt_payload( absint( $request['attempt_id'] ), Qalam_Mobile_Learning::user_id( $request ) ) ); }

    public static function save_quiz_answers( WP_REST_Request $request ) {
        $body = self::json( $request );
        return self::response( Qalam_Mobile_Learning::save_quiz_answers( absint( $request['id'] ), absint( $request['attempt_id'] ), Qalam_Mobile_Learning::user_id( $request ), is_array( $body['answers'] ?? null ) ? $body['answers'] : array() ) );
    }

    public static function submit_quiz( WP_REST_Request $request ) {
        $body = self::json( $request );
        $attempt_id = absint( $body['attempt_id'] ?? 0 );
        if ( ! $attempt_id ) {
            return new WP_Error( 'attempt_required', 'رقم محاولة الاختبار مطلوب.', array( 'status' => 400 ) );
        }
        return self::response( Qalam_Mobile_Learning::submit_quiz( absint( $request['id'] ), $attempt_id, Qalam_Mobile_Learning::user_id( $request ), is_array( $body['answers'] ?? null ) ? $body['answers'] : array() ) );
    }

    public static function quiz_results( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::quiz_results( Qalam_Mobile_Learning::user_id( $request ), absint( $request->get_param( 'limit' ) ?: 50 ) ) ); }
    public static function quiz_result( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::quiz_result( absint( $request['attempt_id'] ), Qalam_Mobile_Learning::user_id( $request ) ) ); }
    public static function assignments( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::assignments( Qalam_Mobile_Learning::user_id( $request ) ) ); }
    public static function assignment( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::assignment( absint( $request['id'] ), Qalam_Mobile_Learning::user_id( $request ) ) ); }

    public static function submit_assignment( WP_REST_Request $request ) {
        $body = self::json( $request );
        return self::response( Qalam_Mobile_Learning::submit_assignment( absint( $request['id'] ), Qalam_Mobile_Learning::user_id( $request ), (string) ( $body['answer'] ?? '' ) ) );
    }

    public static function certificates( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::certificates( Qalam_Mobile_Learning::user_id( $request ) ) ); }
    public static function announcements( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::announcements( Qalam_Mobile_Learning::user_id( $request ) ) ); }
    public static function notifications( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::notifications( Qalam_Mobile_Learning::user_id( $request ), absint( $request->get_param( 'limit' ) ?: 50 ) ) ); }
    public static function notification_read( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Learning::mark_notification_read( absint( $request['id'] ), Qalam_Mobile_Learning::user_id( $request ) ) ); }
    public static function device_register( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Push::register_device( Qalam_Mobile_Learning::user_id( $request ), self::json( $request ) ) ); }
    public static function device_unregister( WP_REST_Request $request ) { return self::response( Qalam_Mobile_Push::unregister_device( Qalam_Mobile_Learning::user_id( $request ), self::json( $request ) ) ); }

    public static function media( WP_REST_Request $request ) {
        $attachment_id = absint( $request['id'] );
        $valid = Qalam_Mobile_Learning::validate_media_request( $attachment_id, $request );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }
        $path = get_attached_file( $attachment_id );
        if ( ! $path || ! is_file( $path ) || ! is_readable( $path ) ) {
            return new WP_Error( 'media_missing', 'الملف غير موجود.', array( 'status' => 404 ) );
        }
        $size  = filesize( $path );
        $start = 0;
        $end   = max( 0, $size - 1 );
        $code  = 200;
        $range = isset( $_SERVER['HTTP_RANGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) ) : '';
        if ( $range && preg_match( '/bytes=(\d*)-(\d*)/', $range, $m ) ) {
            if ( '' !== $m[1] ) { $start = min( $end, (int) $m[1] ); }
            if ( '' !== $m[2] ) { $end = min( $end, (int) $m[2] ); }
            if ( $end < $start ) { $end = $start; }
            $code = 206;
        }
        $mime = get_post_mime_type( $attachment_id ) ?: 'application/octet-stream';
        status_header( $code );
        header( 'Content-Type: ' . $mime );
        header( 'Accept-Ranges: bytes' );
        header( 'Cache-Control: private, max-age=300' );
        header( 'Content-Length: ' . ( $end - $start + 1 ) );
        if ( 206 === $code ) {
            header( "Content-Range: bytes {$start}-{$end}/{$size}" );
        }
        $fh = fopen( $path, 'rb' );
        if ( false === $fh ) {
            return new WP_Error( 'media_open_failed', 'تعذر فتح الملف.', array( 'status' => 500 ) );
        }
        fseek( $fh, $start );
        $remaining = $end - $start + 1;
        while ( $remaining > 0 && ! feof( $fh ) ) {
            $chunk = fread( $fh, min( 1024 * 1024, $remaining ) );
            if ( false === $chunk || '' === $chunk ) { break; }
            echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $remaining -= strlen( $chunk );
            if ( function_exists( 'fastcgi_finish_request' ) && $remaining <= 0 ) { break; }
            flush();
        }
        fclose( $fh );
        exit;
    }
}
