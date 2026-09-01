<?php
$root = dirname( __DIR__ );
$main = file_get_contents( $root . '/qalam-lms.php' );
$release = file_get_contents( $root . '/qalam/release-300.php' );
$api = file_get_contents( $root . '/qalam/mobile/Api.php' );
$push = file_get_contents( $root . '/qalam/mobile/Push.php' );
$checks = array(
    '0.32 product version' => false !== strpos( $main, 'Version: 0.32.0' ) && false !== strpos( $main, "0.32.0-mobile-push-bridge" ),
    'push bridge loaded' => false !== strpos( $release, "mobile/Push.php" ),
    'device register route' => false !== strpos( $api, "'/devices/register'" ) && false !== strpos( $api, 'device_register' ),
    'device unregister route' => false !== strpos( $api, "'/devices/unregister'" ) && false !== strpos( $api, 'device_unregister' ),
    'student id server derived' => false !== strpos( $api, 'Qalam_Mobile_Learning::user_id( $request )' ),
    'fcm token proxied server side' => false !== strpos( $push, "'/api/v1/devices/register'" ) && false !== strpos( $push, "'fcm_token'" ),
    'cloud notification relay helper' => false !== strpos( $push, "'/api/v1/notifications'" ) && false !== strpos( $push, 'Idempotency-Key' ),
    'no firebase credential in plugin' => false === strpos( $push, 'private_key' ) && false === strpos( $push, 'service_account' ),
);
$failed = array();
foreach ( $checks as $label => $ok ) { echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . PHP_EOL; if ( ! $ok ) $failed[] = $label; }
if ( $failed ) { fwrite( STDERR, 'Failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
echo 'Qalam 0.32.0 Mobile push bridge contracts passed.' . PHP_EOL;
