<?php
$root = dirname( __DIR__ );
$main = file_get_contents( $root . '/qalam-lms.php' );
$release = file_get_contents( $root . '/qalam/release-300.php' );
$experience = file_get_contents( $root . '/qalam/mobile/Experience.php' );
$api = file_get_contents( $root . '/qalam/mobile/Api.php' );
$cloud = file_get_contents( $root . '/qalam/release-290.php' );
$checks = array(
    '0.31 product version' => false !== strpos( $main, 'Version: 0.32.0' ) && false !== strpos( $main, "0.32.0-mobile-push-bridge" ),
    'experience runtime loaded' => false !== strpos( $release, "mobile/Experience.php" ),
    'public mobile config route' => false !== strpos( $api, "'/config'" ) && false !== strpos( $api, "Qalam_Mobile_Experience::config()" ),
    'effective 45-key feature source' => false !== strpos( $experience, 'qalam_180_feature_definitions' ) && false !== strpos( $experience, 'qalam_feature_enabled' ),
    'platform policy enforced' => false !== strpos( $experience, 'qalam_290_platform_feature_allowed' ) && false !== strpos( $experience, 'platform_type' ),
    'mobile surfaces are explicit' => false !== strpos( $experience, "'assignments'") && false !== strpos( $experience, "'certificates'") && false !== strpos( $experience, "'notifications'" ),
    'assignment route gate' => false !== strpos( $api, 'assignments_permission' ) && false !== strpos( $api, "feature_permission( \$request, 'assignments'" ),
    'certificate route gate' => false !== strpos( $api, 'certificates_permission' ) && false !== strpos( $api, "feature_permission( \$request, 'certificates'" ),
    'notification route gate' => false !== strpos( $api, 'notifications_permission' ) && false !== strpos( $api, "feature_permission( \$request, 'notifications'" ),
    'feature denial is server side' => false !== strpos( $api, 'feature_not_entitled' ) && false !== strpos( $api, "'status' => 403" ),
    'full branding fields' => false !== strpos( $experience, "'hero_image_url'") && false !== strpos( $experience, "'teacher_image_url'") && false !== strpos( $experience, "'about_image_url'") && false !== strpos( $experience, "'custom_primary'" ),
    'support fields' => false !== strpos( $experience, "'whatsapp'") && false !== strpos( $experience, "'phone'") && false !== strpos( $experience, "'email'" ),
    'cloud branding sync expanded' => false !== strpos( $cloud, 'Qalam_Mobile_Experience::branding()' ) && false !== strpos( $cloud, "'api_base_url'  => rest_url( 'qalam-mobile/v1' )" ),
    'no mobile secret exposure' => false === strpos( $experience, 'site_secret' ) && false === strpos( $experience, 'license_key' ) && false === strpos( $experience, 'private_key' ),
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
echo 'Qalam 0.32.0 Mobile entitlement contracts passed.' . PHP_EOL;
