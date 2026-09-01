<?php
/** Android device registration and Qalam Cloud Push Relay bridge. */
defined( 'ABSPATH' ) || exit;

final class Qalam_Mobile_Push {
    private static function cloud_ready() {
        if ( ! function_exists( 'qalam_290_signed_request' ) ) {
            return new WP_Error( 'cloud_connector_missing', 'موصل Qalam Cloud غير متاح.', array( 'status' => 503 ) );
        }
        if ( function_exists( 'qalam_290_state' ) ) {
            $state = qalam_290_state();
            if ( empty( $state['activation_id'] ) ) {
                return new WP_Error( 'cloud_activation_missing', 'المنصة غير مفعلة مع Qalam Cloud.', array( 'status' => 503 ) );
            }
        }
        return true;
    }

    public static function register_device( int $user_id, array $body ) {
        $ready = self::cloud_ready();
        if ( is_wp_error( $ready ) ) { return $ready; }
        $device_id = sanitize_text_field( (string) ( $body['device_id'] ?? '' ) );
        $push_token = trim( (string) ( $body['push_token'] ?? '' ) );
        $provider = sanitize_key( (string) ( $body['push_provider'] ?? 'fcm' ) );
        if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,190}$/', $device_id ) || 'fcm' !== $provider || strlen( $push_token ) < 20 || strlen( $push_token ) > 4096 ) {
            return new WP_Error( 'push_token_invalid', 'بيانات تسجيل الإشعارات غير صالحة.', array( 'status' => 400 ) );
        }
        $result = qalam_290_signed_request( '/api/v1/devices/register', array(
            'student_id' => (string) $user_id,
            'device_id'  => $device_id,
            'fcm_token'  => $push_token,
            'app_version'=> sanitize_text_field( (string) ( $body['app_version'] ?? '' ) ),
        ) );
        if ( is_wp_error( $result ) ) { return $result; }
        return array( 'ok' => true, 'device_id' => $device_id, 'registered' => true );
    }

    public static function unregister_device( int $user_id, array $body ) {
        $ready = self::cloud_ready();
        if ( is_wp_error( $ready ) ) { return $ready; }
        $device_id = sanitize_text_field( (string) ( $body['device_id'] ?? '' ) );
        if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,190}$/', $device_id ) ) {
            return new WP_Error( 'device_id_invalid', 'معرف الجهاز غير صالح.', array( 'status' => 400 ) );
        }
        $result = qalam_290_signed_request( '/api/v1/devices/unregister', array( 'student_id' => (string) $user_id, 'device_id' => $device_id ) );
        if ( is_wp_error( $result ) ) { return $result; }
        return array( 'ok' => true );
    }

    public static function send( int $student_id, string $channel, string $title, string $body, array $data = array(), string $idempotency_key = '' ) {
        $ready = self::cloud_ready();
        if ( is_wp_error( $ready ) ) { return $ready; }
        if ( $student_id < 1 || '' === trim( $title ) || '' === trim( $body ) ) {
            return new WP_Error( 'push_payload_invalid', 'بيانات الإشعار غير مكتملة.' );
        }
        if ( strlen( $idempotency_key ) < 12 ) {
            $idempotency_key = 'lms-' . hash( 'sha256', $student_id . '|' . $channel . '|' . $title . '|' . wp_json_encode( $data ) . '|' . microtime( true ) );
        }
        return qalam_290_signed_request( '/api/v1/notifications', array(
            'student_id' => (string) $student_id,
            'channel'    => sanitize_key( $channel ?: 'qalam-learning' ),
            'title'      => sanitize_text_field( $title ),
            'body'       => sanitize_textarea_field( $body ),
            'data'       => $data,
        ), array( 'Idempotency-Key' => sanitize_text_field( $idempotency_key ) ) );
    }
}

function qalam_mobile_push_send( int $student_id, string $channel, string $title, string $body, array $data = array(), string $idempotency_key = '' ) {
    return Qalam_Mobile_Push::send( $student_id, $channel, $title, $body, $data, $idempotency_key );
}
