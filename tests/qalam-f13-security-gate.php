<?php
/**
 * Qalam LMS source/static security gate.
 *
 * This intentionally runs without bootstrapping WordPress. It verifies that the
 * hardening contracts discovered during the Tutor 4.0.4 parity/security audit
 * remain present in the unified fork.
 */
$root = dirname( __DIR__ );
$fail = array();
$pass = array();
$read = static function ( $rel ) use ( $root ) {
    $path = $root . '/' . ltrim( $rel, '/' );
    return is_file( $path ) ? file_get_contents( $path ) : '';
};
$ok = static function ( $cond, $label ) use ( &$fail, &$pass ) {
    if ( $cond ) { $pass[] = $label; } else { $fail[] = $label; }
};
$has_all = static function ( $haystack, array $needles ) {
    foreach ( $needles as $needle ) { if ( false === strpos( $haystack, $needle ) ) { return false; } }
    return true;
};

// 1) Device-management IDOR + external IP lookup.
$device = $read( 'pro/classes/DeviceManagement.php' );
$ok( $has_all( $device, array(
    'WHERE umeta_id = %d AND user_id = %d LIMIT 1',
    "0 === strpos( (string) \$meta->meta_key, self::LOGIN_INFO_KEY )",
    "'umeta_id' => \$umeta_id, 'user_id' => \$current_id, 'meta_key' => \$meta->meta_key",
) ), 'device removal is bound to current user and Tutor device meta' );
$ok( false !== strpos( $device, 'wp_safe_remote_get(' ) && false !== strpos( $device, "'https://ipinfo.io/'" ), 'device IP enrichment uses safe HTTPS HTTP' );

// 2) Private secret store + Google integrations.
$bridge = $read( 'qalam/security/SecurityBridge.php' );
$ok( $has_all( $bridge, array( 'final class PrivateSecretStore', '@chmod($dir,0700)', '@chmod($tmp,0600)', 'rename($tmp,$path)' ) ), 'private secret store is permissioned and atomic' );
$ok( false !== strpos( $bridge, 'Fail closed: a private store inside public uploads is not reliably private on Nginx.' ), 'private storage fails closed instead of relying on public uploads' );

$classroom = $read( 'pro/addons/google-classroom/classes/Classroom.php' );
$ok( $has_all( $classroom, array( 'PrivateSecretStore::directory', 'PrivateSecretStore::write_json', 'PrivateSecretStore::read_json', 'oauth_state_meta_key', 'verify_oauth_state' ) ), 'Google Classroom credentials/tokens use private store and OAuth state' );
$ok( $has_all( $classroom, array( "'accounts.google.com'", "'oauth2.googleapis.com'" ) ), 'Google Classroom OAuth endpoints are allow-listed' );

$meet_event = $read( 'pro/addons/google-meet/includes/GoogleEvent/GoogleEvent.php' );
$meet_events = $read( 'pro/addons/google-meet/includes/GoogleEvent/Events.php' );
$meet_validator = $read( 'pro/addons/google-meet/includes/Validator/Validator.php' );
$ok( $has_all( $meet_event, array( 'PrivateSecretStore::directory', 'PrivateSecretStore::write_json', 'PrivateSecretStore::read_json', 'oauth_state_meta_key', 'verify_oauth_state' ) ), 'Google Meet credentials/tokens use private store and OAuth state' );
$ok( $has_all( $meet_events, array( 'Validator::can_manage_meeting', "'tutor-google-meet' !== \$meeting->post_type" ) ) && false !== strpos( $meet_validator, 'public static function can_manage_meeting' ), 'Google Meet mutations enforce meeting ownership/course management' );

// 3) Active quiz answer secrecy and grading integrity.
$reveal = $read( 'qalam/security/QuizRevealSecurity.php' );
$quiz_body = $read( 'templates/single/quiz/body.php' );
$quiz = $read( 'classes/Quiz.php' );
$ok( $has_all( $reveal, array( 'qalam_quiz_reveal_commit', 'check_ajax_referer', 'validate_question_response', 'belongs_question_id = %d', 'A revealed answer is immutable for this attempt' ) ), 'active quiz reveal is commit-bound, nonce-protected and question-bound' );
$ok( false !== strpos( $quiz_body, "window.tutor_quiz_context = '[]'" ) && false === strpos( $quiz_body, 'correct_answer_ids' ), 'active quiz page does not preload correct answer IDs into quiz context' );
$ok( $has_all( $quiz, array(
    '(int) $attempt->quiz_id !== (int) $quiz_id',
    'SELECT question_id FROM {$wpdb->prefix}tutor_quiz_questions WHERE quiz_id = %d AND question_id IN',
    '(int) ( $question->quiz_id ?? 0 ) !== (int) $attempt->quiz_id',
    'WHERE answer_id = %d AND belongs_question_id = %d AND belongs_question_type = %s',
) ), 'quiz submission binds attempt, quiz, question and answer IDs together' );

// 4) H5P score/attempt forgery hardening.
$h5p = $read( 'pro/addons/h5p/src/Quiz.php' );
$ok( $has_all( $h5p, array( 'validate_h5p_attempt_tuple', 'Invalid H5P quiz attempt.', 'trusted_result', 'raw_score,max_score,min_score,scaled_score' ) ), 'H5P scoring is bound to a validated attempt tuple and trusted persisted result' );
$ok( false !== strpos( $h5p, '$wpdb->prepare( "DELETE FROM {$wpdb->prefix}tutor_h5p_quiz_result WHERE attempt_id IN ($placeholders)"' ), 'H5P bulk result deletion is prepared' );

// 5) Anonymous internal editor/API leakage.
foreach ( array( 'includes/droip/backend/Pages.php', 'includes/kirki/backend/Pages.php' ) as $page_file ) {
    $pages = $read( $page_file );
    $ok( false === strpos( $pages, 'wp_ajax_nopriv_tde_get_apis' ) && false !== strpos( $pages, 'wp_ajax_tde_get_apis' ), $page_file . ' has no anonymous tde_get_apis endpoint' );
}

// 6) SSRF/network hardening across importers and HTTP wrappers.
$http_helper = $read( 'helpers/HttpHelper.php' );
$openai_http = $read( 'pro/openai/Http/Request.php' );
$template_helper = $read( 'pro/template-import/TemplateImportHelper.php' );
$template_importer = $read( 'pro/template-import/TemplateImporter.php' );
$ok( false !== strpos( $http_helper, 'wp_safe_remote_' ) && false !== strpos( $openai_http, 'wp_safe_remote_' ), 'generic and OpenAI HTTP wrappers use safe WordPress remote functions' );
$ok( $has_all( $template_helper, array( 'wp_http_validate_url', "'https' !== strtolower", 'wp_safe_remote_get', "'sslverify' => true" ) ), 'template source/download endpoints require valid HTTPS and safe remote GET' );
$ok( false !== strpos( $template_importer, 'wp_safe_remote_get' ) && false !== strpos( $template_importer, "'sslverify' => true" ), 'template dependency importer uses safe verified HTTPS transport' );

// 7) Zoom/Meet ownership and query injection hardening.
$zoom = $read( 'pro/addons/tutor-zoom/classes/Zoom.php' );
$zoom_request = $read( 'pro/addons/tutor-zoom/zoom-app/Interfaces/Request.php' );
$meet_model = $read( 'pro/addons/google-meet/includes/Models/EventsModel.php' );
$ok( $has_all( $zoom, array( "\$this->zoom_meeting_post_type !== \$meeting_post->post_type", "tutor_utils()->can_user_manage( 'course'", "tutor_utils()->can_user_manage( 'topic'" ) ), 'Zoom meeting mutations validate type and course/topic ownership' );
$ok( $has_all( $zoom, array( "'ASC' === strtoupper", 'QueryHelper::prepare_in_clause', '$wpdb->esc_like', '$wpdb->prepare' ) ), 'Zoom meeting filters constrain order and prepare user-controlled filters' );
$ok( $has_all( $zoom_request, array( 'wp_safe_remote_post', "'sslverify' => true", "'redirection' => 0" ) ), 'Zoom OAuth token request uses safe verified transport' );
$ok( $has_all( $meet_model, array( "'DESC' === strtoupper", 'QueryHelper::prepare_in_clause', '$wpdb->esc_like', '$wpdb->prepare', 'AND ( course.ID =' ) ), 'Google Meet listing constrains and prepares filter input' );

// 8) Payment webhook replay protection.
$paypal = $read( 'ecommerce/PaymentGateways/Paypal/src/Payments/Paypal/Paypal.php' );
$ok( $has_all( $paypal, array( 'qalam_paypal_webhook_', 'get_transient( $replay_key )', 'set_transient( $replay_key, 1, 7 * DAY_IN_SECONDS )' ) ), 'verified PayPal events have replay/idempotency protection' );

// 9) Private assignment files and cross-student authorization.
$assignment_security = $read( 'qalam/security/AssignmentSubmissionSecurity.php' );
$assignments = $read( 'pro/addons/tutor-assignments/classes/Assignments.php' );
$ok( $has_all( $assignment_security, array( 'final class AssignmentSubmissionSecurity', 'move_uploaded_file', '@chmod( $dest, 0600 )', 'signed_url', 'hash_hmac', 'self::can_access', 'Fail closed' ) ), 'assignment submissions are private, signed and fail closed' );
$ok( false !== strpos( $assignments, 'AssignmentSubmissionSecurity' ) && ( false !== strpos( $assignments, 'can_user_manage' ) || false !== strpos( $assignments, 'get_current_user_id' ) ), 'assignment upload/delete flow is connected to private store and authorization checks' );

// 10) reCAPTCHA safe transport.
$recaptcha = $read( 'pro/addons/auth/classes/Recaptcha.php' );
$ok( false !== strpos( $recaptcha, 'wp_safe_remote_post' ) && preg_match( "/'redirection'\s*=>\s*0/", $recaptcha ) && preg_match( "/'sslverify'\s*=>\s*true/", $recaptcha ) && false === strpos( $recaptcha, 'file_get_contents(' ), 'reCAPTCHA verification uses safe verified remote POST' );

// 11) Remote importer path traversal/write hardening.
foreach ( array( 'pro/tools/Helper.php', 'classes/SampleCourse.php' ) as $importer_file ) {
    $importer = $read( $importer_file );
    $ok( $has_all( $importer, array( 'wp_http_validate_url', 'wp_basename', 'sanitize_file_name', 'wp_unique_filename', 'wp_safe_remote_get', 'LOCK_EX' ) ), $importer_file . ' remote file importer sanitizes destination and safe-fetches source' );
}

// 12) Unsafe object unserialization: direct non-vendor calls must explicitly disallow classes.
$unsafe_unserialize = array();
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $f ) {
    if ( ! $f->isFile() || 'php' !== strtolower( $f->getExtension() ) ) { continue; }
    $path = $f->getPathname();
    if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR ) || false !== strpos( $path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR ) ) { continue; }
    $data = file_get_contents( $path );
    if ( ! preg_match_all( '/(?<!maybe_)unserialize\s*\((.*?)\)/s', $data, $m ) ) { continue; }
    foreach ( $m[0] as $call ) {
        if ( false === strpos( $call, 'allowed_classes' ) ) { $unsafe_unserialize[] = substr( $path, strlen( $root ) + 1 ) . ': ' . preg_replace( '/\s+/', ' ', substr( $call, 0, 160 ) ); }
    }
}
$ok( 0 === count( $unsafe_unserialize ), 'all direct non-vendor unserialize calls disallow object instantiation' );

// 13) Security quarantine / secrets / forbidden updater markers.
$bad_markers = array(
    'tutor.gpltimes.com'          => 'forbidden donor proxy',
    "'sslverify' => false"       => 'ssl verification disabled',
    'themeum-products/v1'        => 'commercial Tutor updater endpoint',
);
$hardcoded_secret_patterns = array(
    '/-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----/' => 'private key material',
    '/\bsk-[A-Za-z0-9_-]{24,}\b/'                         => 'OpenAI-style API key',
    '/\bAIza[0-9A-Za-z_-]{30,}\b/'                         => 'Google API key',
);
$quarantine_hits = array();
$secret_hits = array();
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $f ) {
    if ( ! $f->isFile() ) { continue; }
    $path = $f->getPathname();
    if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR ) || false !== strpos( $path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR ) ) { continue; }
    $ext = strtolower( $f->getExtension() );
    if ( ! in_array( $ext, array( 'php','js','json','txt','md','yml','yaml','env' ), true ) ) { continue; }
    $data = file_get_contents( $path );
    foreach ( $bad_markers as $needle => $label ) { if ( false !== strpos( $data, $needle ) ) { $quarantine_hits[] = $label . ' in ' . substr( $path, strlen( $root ) + 1 ); } }
    if ( preg_match( '/update_option\s*\(\s*[\'\"]tutor_license_info[\'\"]/', $data ) ) { $quarantine_hits[] = 'forced Tutor license injection in ' . substr( $path, strlen( $root ) + 1 ); }
    foreach ( $hardcoded_secret_patterns as $pattern => $label ) { if ( preg_match( $pattern, $data ) ) { $secret_hits[] = $label . ' in ' . substr( $path, strlen( $root ) + 1 ); } }
}
$ok( 0 === count( $quarantine_hits ), 'security quarantine contains no forbidden donor/updater/ssl markers' );
$ok( 0 === count( $secret_hits ), 'source tree contains no obvious hard-coded private/API key material' );

// Emit useful diagnostics without leaking file contents.
if ( $unsafe_unserialize ) { foreach ( $unsafe_unserialize as $x ) { $fail[] = 'unsafe unserialize: ' . $x; } }
if ( $quarantine_hits ) { foreach ( array_unique( $quarantine_hits ) as $x ) { $fail[] = $x; } }
if ( $secret_hits ) { foreach ( array_unique( $secret_hits ) as $x ) { $fail[] = $x; } }

if ( $fail ) {
    fwrite( STDERR, "FAIL qalam-f13-security-gate\n - " . implode( "\n - ", array_values( array_unique( $fail ) ) ) . "\n" );
    exit( 1 );
}
echo 'PASS qalam-f13-security-gate (' . count( $pass ) . " contracts)\n";
