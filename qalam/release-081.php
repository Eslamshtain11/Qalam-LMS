<?php
/**
 * Qalam 0.8.1 — background generation worker, public standalone exams,
 * results/review workspace, bank CRUD, and a high-contrast public exam shell.
 *
 * @package QalamLMS
 */

defined( 'ABSPATH' ) || exit;

const QALAM_081_PUBLIC_REQUIREMENTS_META = '_qalam_public_requirements';
const QALAM_081_PUBLIC_PASSWORD_META     = '_qalam_public_password_hash';
const QALAM_081_GUEST_META               = '_qalam_guest_participant';
const QALAM_081_GUEST_QUIZ_META          = '_qalam_guest_quiz_id';
const QALAM_081_GUEST_PHONE_META         = '_qalam_guest_phone';
const QALAM_081_GUEST_PARENT_META        = '_qalam_guest_parent_phone';
const QALAM_081_GUEST_NAME_META          = '_qalam_guest_name';
const QALAM_081_WORKER_HOOK              = 'qalam_081_generation_worker';
const QALAM_081_WORKER_LOCK_PREFIX       = 'qalam_081_worker_lock_';
const QALAM_081_WORKER_MAX_ERRORS        = 4;
const QALAM_081_WORKER_BATCH_SIZE        = 4;
const QALAM_081_WORKER_TOKEN_CONTEXT     = 'qalam-081-generation-worker';

/** Arabic additions. */
function qalam_081_dictionary( $map ) {
	$extra = array(
		'Quiz Attempts' => 'محاولات الاختبار',
		'Attempts' => 'المحاولات',
		'Marks' => 'الدرجات',
		'Time' => 'الوقت',
		'Result' => 'النتيجة',
		'Attempt not found' => 'المحاولة غير موجودة',
		'Access denied!' => 'غير مسموح بالدخول',
		'Edit' => 'تعديل',
		'Delete Content' => 'حذف المحتوى',
		'Are you sure you want to delete this content?' => 'متأكد إنك عايز تحذف المحتوى ده؟',
		'Delete selected' => 'حذف المحدد',
		'Select all' => 'تحديد الكل',
		'Quiz info' => 'بيانات الاختبار',
		'Quiz' => 'الاختبار',
		'Quiz Time' => 'مدة الاختبار',
		'Total Attempted' => 'عدد المحاولات',
		'Start Quiz' => 'ابدأ الاختبار',
		'Skip Quiz' => 'تخطي الاختبار',
		'Skip Question' => 'تخطي السؤال',
		'Back' => 'السابق',
		'Submit & Next' => 'حفظ والتالي',
		'Submit Quiz' => 'إنهاء الاختبار',
		'Finish' => 'إنهاء',
		'Marks : ' => 'الدرجة: ',
		'Questions' => 'الأسئلة',
		'Passing Grade' => 'درجة النجاح',
		'Write your answer here' => 'اكتب إجابتك هنا',
		'No background image configured for this Image Marking question.' => 'السؤال ده ناقص صورة التحديد المطلوبة.',
		'No background image configured for this Pin question.' => 'السؤال ده ناقص صورة تحديد النقطة المطلوبة.',
	);
	return array_merge( (array) $map, $extra );
}
add_filter( 'qalam_lms_dictionary', 'qalam_081_dictionary', 100 );

/* -------------------------------------------------------------------------
 * Generation worker: browser only polls; AI/PDF runs in wp-cron background.
 * ---------------------------------------------------------------------- */

function qalam_081_job_lock_key( string $job_id ): string {
	return QALAM_081_WORKER_LOCK_PREFIX . sanitize_key( $job_id );
}

function qalam_081_acquire_job_lock( string $job_id ): bool {
	$key = qalam_081_job_lock_key( $job_id );
	$now = time();
	if ( add_option( $key, $now, '', false ) ) {
		return true;
	}
	$started = (int) get_option( $key, 0 );
	if ( $started && ( $now - $started ) > 90 ) {
		delete_option( $key );
		return add_option( $key, $now, '', false );
	}
	return false;
}

function qalam_081_release_job_lock( string $job_id ): void {
	delete_option( qalam_081_job_lock_key( $job_id ) );
}

function qalam_081_worker_token( string $job_id ): string {
	return hash_hmac( 'sha256', sanitize_key( $job_id ) . '|' . QALAM_081_WORKER_TOKEN_CONTEXT, wp_salt( 'auth' ) );
}

/** Fire a detached loopback request. Cron remains a fallback, not the primary runner. */
function qalam_081_dispatch_generation_worker( string $job_id ): void {
	$job_id = sanitize_key( $job_id );
	if ( ! $job_id ) { return; }
	wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'timeout'   => 1,
			'blocking'  => false,
			'redirection'=> 0,
			'sslverify' => true,
			'body'      => array(
				'action' => 'qalam_081_generation_worker_ping',
				'job_id' => $job_id,
				'token'  => qalam_081_worker_token( $job_id ),
			),
		)
	);
}

function qalam_081_schedule_generation_worker( string $job_id, int $delay = 0 ): void {
	$job_id = sanitize_key( $job_id );
	if ( ! $job_id ) { return; }
	$args = array( $job_id );
	if ( ! wp_next_scheduled( QALAM_081_WORKER_HOOK, $args ) ) {
		wp_schedule_single_event( time() + max( 0, $delay ), QALAM_081_WORKER_HOOK, $args );
	}
	if ( 0 === $delay ) {
		qalam_081_dispatch_generation_worker( $job_id );
		if ( function_exists( 'spawn_cron' ) ) { spawn_cron( time() ); }
	}
}

/** Signed, no-login loopback endpoint used only by Qalam's own worker dispatcher. */
function qalam_081_generation_worker_ping(): void {
	$job_id = sanitize_key( (string) ( $_POST['job_id'] ?? '' ) );
	$token  = (string) ( $_POST['token'] ?? '' );
	if ( ! $job_id || ! $token || ! hash_equals( qalam_081_worker_token( $job_id ), $token ) ) {
		status_header( 403 ); wp_die();
	}
	ignore_user_abort( true );
	if ( function_exists( 'set_time_limit' ) ) { @set_time_limit( 180 ); } // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	qalam_081_process_generation_job_batch( $job_id );
	wp_die();
}
add_action( 'wp_ajax_nopriv_qalam_081_generation_worker_ping', 'qalam_081_generation_worker_ping' );
add_action( 'wp_ajax_qalam_081_generation_worker_ping', 'qalam_081_generation_worker_ping' );

/** Return only status; never perform AI inside an admin-ajax browser request. */
function qalam_081_generation_status_payload( array $job ): array {
	$status = (string) ( $job['status'] ?? 'running' );
	$created = (int) ( $job['created'] ?? 0 );
	$total = (int) ( $job['total'] ?? 0 );
	$remaining = array_sum( array_map( 'absint', (array) ( $job['remaining'] ?? array() ) ) );
	$message = (string) ( $job['last_error'] ?? '' );
	if ( ! $message ) {
		if ( 'complete' === $status ) { $message = 'اكتمل إنشاء وحفظ الأسئلة.'; }
		elseif ( 'failed' === $status ) { $message = 'تعذر إكمال التوليد.'; }
		elseif ( 'paused' === $status ) { $message = 'الموديل لم يرجع أسئلة صالحة كفاية للأنواع المطلوبة.'; }
		else { $message = 'جاري إنشاء الأسئلة في الخلفية...'; }
	}
	return array(
		'done'       => 'complete' === $status,
		'failed'     => 'failed' === $status,
		'paused'     => 'paused' === $status,
		'status'     => $status,
		'created'    => $created,
		'total'      => $total,
		'remaining'  => $remaining,
		'rejected'   => (int) ( $job['rejected'] ?? 0 ),
		'message'    => $message,
		'created_ids'=> array_values( array_map( 'absint', (array) ( $job['created_ids'] ?? array() ) ) ),
	);
}

/** Process exactly one generation batch outside the browser request. */
function qalam_081_process_generation_job_batch( string $id ): void {
	$continue = false;
	$id = sanitize_key( $id );
	if ( ! $id || ! qalam_081_acquire_job_lock( $id ) ) { return; }
	$job = qalam_080_get_job( $id );
	try {
		if ( ! $job || in_array( (string) ( $job['status'] ?? '' ), array( 'complete','failed','cancelled' ), true ) ) { return; }
		$job['last_heartbeat'] = time();
		$job['status'] = 'running';
		qalam_080_put_job( $id, $job );

		$collection = qalam_070_question_bank_collection_id( (int) ( $job['user_id'] ?? 0 ) );
		if ( ! $collection ) { throw new RuntimeException( 'فعّل بنك المحتوى الأول.' ); }

		$batch_size = ! empty( $job['pdf_path'] ) ? 2 : QALAM_081_WORKER_BATCH_SIZE;
		$batch = qalam_080_next_batch_counts( (array) $job['remaining'], $batch_size );
		if ( ! $batch ) {
			$job['status'] = 'complete';
			qalam_080_put_job( $id, $job );
			return;
		}

		$prompt = qalam_080_ai_prompt( $batch, (string) $job['mode'], (string) $job['difficulty'], (string) $job['instructions'], (array) $job['created_titles'] );
		$items  = ! empty( $job['pdf_path'] )
			? qalam_080_generate_pdf_questions( $job['pdf_path'], $job['pdf_name'], $prompt )
			: qalam_080_generate_text_questions( $prompt );

		$accepted_batch = 0;
		$reject_messages = array();
		foreach ( (array) $items as $raw ) {
			if ( ! is_array( $raw ) ) { continue; }
			$type = sanitize_key( (string) ( $raw['question_type'] ?? '' ) );
			if ( empty( $job['remaining'][ $type ] ) ) { continue; }
			try {
				if ( 'mixed' !== $job['difficulty'] ) { $raw['difficulty'] = $job['difficulty']; }
				$raw = qalam_080_validate_item( $raw, ! empty( $job['pdf_path'] ) );
				$fp = qalam_080_question_fingerprint( $raw );
				if ( isset( $job['fingerprints'][ $fp ] ) ) { throw new RuntimeException( 'سؤال مكرر.' ); }
				$payload = qalam_080_native_payload( $raw );
				if ( ! empty( $job['pdf_path'] ) ) {
					qalam_080_apply_source_media( $payload, $raw, $job['pdf_path'], $job['pdf_name'] );
				}
				if ( in_array( $type, array( 'image_answering','draw_image','pin_image','puzzle' ), true ) ) {
					$a = $payload['question_answers'][0] ?? array();
					if ( empty( $a['image_id'] ) ) { throw new RuntimeException( 'تعذر تكوين صورة مرتبطة بالسؤال.' ); }
					if ( in_array( $type, array( 'draw_image','pin_image' ), true ) && empty( $a['answer_two_gap_match'] ) ) {
						throw new RuntimeException( 'تعذر تكوين منطقة الإجابة على الصورة.' );
					}
				}
				$cid = qalam_070_save_content_bank_question(
					$payload,
					$collection,
					(int) $job['term_id'],
					array(
						'mode'        => $job['mode'],
						'provider'    => 'qalam-ai',
						'source_page' => absint( $raw['source_page'] ?? 0 ),
						'pdf'         => $job['pdf_name'],
						'difficulty'  => $raw['difficulty'],
						'author_id'   => absint( $job['user_id'] ?? 0 ),
					)
				);
				if ( ! empty( $raw['difficulty_reason'] ) ) {
					update_post_meta( $cid, QALAM_080_DIFFICULTY_REASON_META, sanitize_text_field( $raw['difficulty_reason'] ) );
				}
				$job['created_ids'][] = $cid;
				$job['created_titles'][] = sanitize_text_field( $raw['question_title'] );
				$job['fingerprints'][ $fp ] = 1;
				++$job['created'];
				++$accepted_batch;
				--$job['remaining'][ $type ];
			} catch ( \Throwable $inner ) {
				++$job['rejected'];
				$reject_messages[] = sanitize_text_field( $inner->getMessage() );
			}
		}

		$job['worker_errors'] = 0;
		$job['last_error'] = $reject_messages ? implode( ' — ', array_slice( array_unique( $reject_messages ), 0, 2 ) ) : '';
		if ( $accepted_batch < 1 ) { ++$job['stalls']; } else { $job['stalls'] = 0; }

		if ( (int) $job['stalls'] >= QALAM_080_MAX_STALLS ) {
			$job['status'] = 'paused';
			$job['last_error'] = 'الموديل رجّع دفعات غير صالحة للأنواع المطلوبة عدة مرات. غيّر الأنواع التفاعلية أو المصدر وحاول من جديد.';
		} elseif ( array_sum( $job['remaining'] ) <= 0 ) {
			$job['status'] = 'complete';
			if ( 'quiz' === $job['target'] && ! empty( $job['quiz_id'] ) ) {
				qalam_070_copy_content_questions_to_quiz( (int) $job['quiz_id'], (array) $job['created_ids'] );
			}
			if ( ! empty( $job['pdf_path'] ) && is_file( $job['pdf_path'] ) ) {
				@unlink( $job['pdf_path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$job['pdf_path'] = '';
			}
		} else {
			$job['status'] = 'running';
		}
		$job['last_heartbeat'] = time();
		qalam_080_put_job( $id, $job );
		$continue = 'running' === $job['status'];
	} catch ( \Throwable $e ) {
		$job = $job ?: qalam_080_get_job( $id );
		$errors = (int) ( $job['worker_errors'] ?? 0 ) + 1;
		$job['worker_errors'] = $errors;
		$job['last_error'] = sanitize_text_field( $e->getMessage() );
		$job['last_heartbeat'] = time();
		if ( $errors >= QALAM_081_WORKER_MAX_ERRORS ) {
			$job['status'] = 'failed';
		} else {
			$job['status'] = 'running';
		}
		qalam_080_put_job( $id, $job );
		$continue = 'running' === $job['status'];
	} finally {
		qalam_081_release_job_lock( $id );
	}
	if ( $continue ) {
		// Dispatch immediately after releasing the lock; cron is a safety fallback.
		qalam_081_schedule_generation_worker( $id, 2 );
		qalam_081_dispatch_generation_worker( $id );
	}
}
add_action( QALAM_081_WORKER_HOOK, 'qalam_081_process_generation_job_batch', 10, 1 );

/* -------------------------------------------------------------------------
 * Question-bank real CRUD.
 * ---------------------------------------------------------------------- */

function qalam_081_can_manage_bank_question( int $content_id ): bool {
	$post = get_post( $content_id );
	if ( ! $post || 'cb-question' !== $post->post_type ) { return false; }
	return current_user_can( 'manage_tutor' ) || ( current_user_can( 'manage_tutor_instructor' ) && (int) $post->post_author === get_current_user_id() );
}

/** Delete only the Content Bank source. Existing copies already inside quizzes stay intact. */
function qalam_081_delete_bank_question_source( int $content_id ): bool {
	if ( ! qalam_081_can_manage_bank_question( $content_id ) ) { return false; }
	global $wpdb;
	$rows = $wpdb->get_col( $wpdb->prepare( "SELECT question_id FROM {$wpdb->prefix}tutor_quiz_questions WHERE content_id=%d", $content_id ) );
	if ( $rows ) {
		$in = implode( ',', array_map( 'absint', $rows ) );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}tutor_quiz_question_answers WHERE belongs_question_id IN ($in)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->delete( $wpdb->prefix . 'tutor_quiz_questions', array( 'content_id' => $content_id ) );
	}
	wp_delete_post( $content_id, true );
	return true;
}

function qalam_081_bank_bulk_delete() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) { wp_die( 'غير مسموح.' ); }
	check_admin_referer( 'qalam_081_bank_bulk_delete', 'qalam_081_bank_nonce' );
	$ids = array_values( array_filter( array_map( 'absint', (array) ( $_POST['question_ids'] ?? array() ) ) ) );
	if ( ! empty( $_POST['single_delete'] ) ) { $ids = array( absint( $_POST['single_delete'] ) ); }
	$delete_all = ! empty( $_POST['delete_all'] );
	if ( $delete_all ) {
		$args = array( 'post_type'=>'cb-question','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids' );
		if ( ! current_user_can( 'manage_tutor' ) ) { $args['author'] = get_current_user_id(); }
		$ids = array_map( 'absint', get_posts( $args ) );
	}
	$deleted = 0;
	foreach ( array_unique( $ids ) as $id ) { if ( qalam_081_delete_bank_question_source( $id ) ) { ++$deleted; } }
	$redirect_args = array( 'page' => 'qalam-question-bank', 'qalam_deleted' => $deleted );
	$return_category = absint( $_POST['return_category'] ?? 0 );
	$return_q = sanitize_text_field( wp_unslash( $_POST['return_q'] ?? '' ) );
	if ( $return_category ) { $redirect_args['question_category'] = $return_category; }
	if ( '' !== $return_q ) { $redirect_args['q'] = $return_q; }
	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_qalam_081_bank_bulk_delete', 'qalam_081_bank_bulk_delete' );

function qalam_081_save_bank_question_basic() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) { wp_die( 'غير مسموح.' ); }
	$id = absint( $_POST['question_id'] ?? 0 );
	check_admin_referer( 'qalam_081_edit_question_' . $id, 'qalam_081_edit_nonce' );
	if ( ! qalam_081_can_manage_bank_question( $id ) ) { wp_die( 'غير مسموح.' ); }
	$title = sanitize_text_field( wp_unslash( $_POST['question_title'] ?? '' ) );
	$description = wp_kses_post( wp_unslash( $_POST['question_description'] ?? '' ) );
	$mark = max( 0.01, (float) ( $_POST['question_mark'] ?? 1 ) );
	$difficulty = sanitize_key( (string) ( $_POST['difficulty'] ?? 'medium' ) );
	if ( ! in_array( $difficulty, array( 'easy','medium','hard' ), true ) ) { $difficulty = 'medium'; }
	if ( ! $title ) { wp_die( 'عنوان السؤال مطلوب.' ); }
	wp_update_post( array( 'ID'=>$id,'post_title'=>$title,'post_content'=>$description ) );
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT question_id,question_settings FROM {$wpdb->prefix}tutor_quiz_questions WHERE content_id=%d LIMIT 1", $id ) );
	if ( $row ) {
		$settings = maybe_unserialize( $row->question_settings );
		$settings = is_array( $settings ) ? $settings : array();
		$settings['qalam_difficulty'] = $difficulty;
		$wpdb->update(
			$wpdb->prefix . 'tutor_quiz_questions',
			array( 'question_title'=>$title,'question_description'=>$description,'question_mark'=>$mark,'question_settings'=>maybe_serialize($settings) ),
			array( 'question_id'=>(int)$row->question_id )
		);
	}
	update_post_meta( $id, QALAM_QBANK_DIFFICULTY_META, $difficulty );
	wp_safe_redirect( add_query_arg( 'qalam_updated', 1, admin_url( 'admin.php?page=qalam-question-bank' ) ) );
	exit;
}
add_action( 'admin_post_qalam_081_save_bank_question_basic', 'qalam_081_save_bank_question_basic' );

/* -------------------------------------------------------------------------
 * General quiz deletion, public access requirements, public guest sessions,
 * results and attempt review.
 * ---------------------------------------------------------------------- */

function qalam_081_public_requirements( int $quiz_id ): array {
	$defaults = array( 'name'=>0,'phone'=>0,'parent_phone'=>0,'password'=>0 );
	$saved = get_post_meta( $quiz_id, QALAM_081_PUBLIC_REQUIREMENTS_META, true );
	return array_merge( $defaults, is_array( $saved ) ? $saved : array() );
}

function qalam_081_save_public_requirements() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) { wp_die( 'غير مسموح.' ); }
	$quiz_id = absint( $_POST['quiz_id'] ?? 0 );
	check_admin_referer( 'qalam_081_public_requirements_' . $quiz_id, 'qalam_081_public_nonce' );
	if ( '1' !== (string) get_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, true ) ) { wp_die( 'اختبار غير صالح.' ); }
	$req = array(
		'name'         => ! empty( $_POST['require_name'] ) ? 1 : 0,
		'phone'        => ! empty( $_POST['require_phone'] ) ? 1 : 0,
		'parent_phone' => ! empty( $_POST['require_parent_phone'] ) ? 1 : 0,
		'password'     => ! empty( $_POST['require_password'] ) ? 1 : 0,
	);
	update_post_meta( $quiz_id, QALAM_081_PUBLIC_REQUIREMENTS_META, $req );
	$password = (string) wp_unslash( $_POST['quiz_password'] ?? '' );
	$existing_hash = (string) get_post_meta( $quiz_id, QALAM_081_PUBLIC_PASSWORD_META, true );
	if ( $req['password'] && '' === trim( $password ) && ! $existing_hash ) {
		wp_safe_redirect( admin_url( 'admin.php?page=qalam-quiz-builder&quiz_id=' . $quiz_id . '&access_error=' . rawurlencode( 'اكتب باسورد للاختبار قبل تفعيل شرط الباسورد.' ) ) );
		exit;
	}
	if ( $req['password'] && '' !== trim( $password ) ) {
		update_post_meta( $quiz_id, QALAM_081_PUBLIC_PASSWORD_META, wp_hash_password( trim( $password ) ) );
	} elseif ( ! $req['password'] ) {
		delete_post_meta( $quiz_id, QALAM_081_PUBLIC_PASSWORD_META );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=qalam-quiz-builder&quiz_id=' . $quiz_id . '&saved_access=1' ) );
	exit;
}
add_action( 'admin_post_qalam_081_save_public_requirements', 'qalam_081_save_public_requirements' );

function qalam_081_delete_quiz_data( int $quiz_id ): void {
	if ( ! $quiz_id || tutor()->quiz_post_type !== get_post_type( $quiz_id ) ) { return; }
	global $wpdb;
	do_action( 'tutor_delete_quiz_before', $quiz_id );
	$attempt_ids = $wpdb->get_col( $wpdb->prepare( "SELECT attempt_id FROM {$wpdb->prefix}tutor_quiz_attempts WHERE quiz_id=%d", $quiz_id ) );
	$attempt_paths = $attempt_ids ? apply_filters( 'tutor_quiz/attempt_file_paths_for_deletion', array(), array_map( 'absint', $attempt_ids ) ) : array();
	$wpdb->delete( $wpdb->prefix . 'tutor_quiz_attempt_answers', array( 'quiz_id'=>$quiz_id ) );
	$wpdb->delete( $wpdb->prefix . 'tutor_quiz_attempts', array( 'quiz_id'=>$quiz_id ) );
	if ( class_exists( '\Tutor\Models\QuizModel' ) ) { \Tutor\Models\QuizModel::delete_files_by_paths( (array) $attempt_paths ); }
	$qids = $wpdb->get_col( $wpdb->prepare( "SELECT question_id FROM {$wpdb->prefix}tutor_quiz_questions WHERE quiz_id=%d", $quiz_id ) );
	if ( $qids ) {
		$in = implode( ',', array_map( 'absint', $qids ) );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}tutor_quiz_question_answers WHERE belongs_question_id IN ($in)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	$wpdb->delete( $wpdb->prefix . 'tutor_quiz_questions', array( 'quiz_id'=>$quiz_id ) );
	wp_delete_post( $quiz_id, true );
	do_action( 'tutor_delete_quiz_after', $quiz_id );
}

function qalam_081_delete_general_quiz() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) { wp_die( 'غير مسموح.' ); }
	$quiz_id = absint( $_POST['quiz_id'] ?? 0 );
	check_admin_referer( 'qalam_081_delete_quiz_' . $quiz_id, 'qalam_081_delete_nonce' );
	if ( '1' !== (string) get_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, true ) ) { wp_die( 'اختبار غير صالح.' ); }
	$instances = get_posts( array( 'post_type'=>tutor()->quiz_post_type,'post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>QALAM_080_DYNAMIC_PARENT_META,'meta_value'=>$quiz_id ) );
	foreach ( $instances as $instance ) { qalam_081_delete_quiz_data( (int) $instance ); }
	qalam_081_delete_quiz_data( $quiz_id );
	wp_safe_redirect( admin_url( 'admin.php?page=qalam-quiz-builder&deleted=1' ) );
	exit;
}
add_action( 'admin_post_qalam_081_delete_general_quiz', 'qalam_081_delete_general_quiz' );

function qalam_081_guest_user_for_quiz( int $quiz_id, array $data ): int {
	if ( is_user_logged_in() && '1' !== (string) get_user_meta( get_current_user_id(), QALAM_081_GUEST_META, true ) ) {
		$uid = get_current_user_id();
		if ( ! empty( $data['name'] ) ) { update_user_meta( $uid, QALAM_081_GUEST_NAME_META, sanitize_text_field( (string) $data['name'] ) ); }
		if ( ! empty( $data['phone'] ) ) { update_user_meta( $uid, QALAM_081_GUEST_PHONE_META, sanitize_text_field( (string) $data['phone'] ) ); }
		if ( ! empty( $data['parent_phone'] ) ) { update_user_meta( $uid, QALAM_081_GUEST_PARENT_META, sanitize_text_field( (string) $data['parent_phone'] ) ); }
		return $uid;
	}
	$token = strtolower( wp_generate_password( 14, false, false ) );
	$login = 'qalam_guest_' . $quiz_id . '_' . $token;
	$name = sanitize_text_field( (string) ( $data['name'] ?? '' ) );
	$user_id = wp_insert_user( array(
		'user_login'   => $login,
		'user_pass'    => wp_generate_password( 32, true, true ),
		'display_name' => $name ?: 'طالب زائر',
		'role'         => 'subscriber',
	) );
	if ( is_wp_error( $user_id ) || ! $user_id ) { throw new RuntimeException( 'تعذر بدء جلسة الاختبار. حاول مرة تانية.' ); }
	update_user_meta( $user_id, QALAM_081_GUEST_META, '1' );
	update_user_meta( $user_id, QALAM_081_GUEST_QUIZ_META, $quiz_id );
	update_user_meta( $user_id, QALAM_081_GUEST_NAME_META, $name ?: 'طالب زائر' );
	update_user_meta( $user_id, QALAM_081_GUEST_PHONE_META, sanitize_text_field( (string) ( $data['phone'] ?? '' ) ) );
	update_user_meta( $user_id, QALAM_081_GUEST_PARENT_META, sanitize_text_field( (string) ( $data['parent_phone'] ?? '' ) ) );
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true, is_ssl() );
	return (int) $user_id;
}

function qalam_081_enroll_general_quiz_user( int $quiz_id, int $user_id ): void {
	$course_id = (int) wp_get_post_parent_id( (int) wp_get_post_parent_id( $quiz_id ) );
	if ( $course_id && class_exists( '\Tutor\Models\EnrollmentModel' ) ) {
		\Tutor\Models\EnrollmentModel::do_enroll( $course_id, 0, $user_id, false );
	}
}

function qalam_081_render_public_gate( int $quiz_id, string $error = '' ): void {
	$quiz = get_post( $quiz_id );
	$req = qalam_081_public_requirements( $quiz_id );
	status_header( 200 ); nocache_headers();
	?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html( $quiz ? $quiz->post_title : 'اختبار قلم' ); ?></title><?php wp_head(); ?></head><body class="qalam-public-exam-gate"><main class="qalam-gate-shell"><section class="qalam-gate-card"><span class="qalam-gate-badge">Qalam LMS</span><h1><?php echo esc_html( $quiz ? $quiz->post_title : 'اختبار قلم' ); ?></h1><p>ادخل البيانات المطلوبة وابدأ الاختبار مباشرة من غير إنشاء حساب أو تسجيل دخول.</p><?php if($error):?><div class="qalam-gate-error"><?php echo esc_html($error);?></div><?php endif;?><form method="post" action=""><input type="hidden" name="qalam_exam_enter" value="1"><input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id);?>"><?php wp_nonce_field('qalam_081_enter_public_'.$quiz_id,'qalam_public_enter_nonce');?><?php if($req['name']):?><label><span>اسم الطالب</span><input type="text" name="student_name" required autocomplete="name"></label><?php endif;?><?php if($req['phone']):?><label><span>رقم الهاتف</span><input type="tel" name="phone" required inputmode="tel"></label><?php endif;?><?php if($req['parent_phone']):?><label><span>رقم ولي الأمر</span><input type="tel" name="parent_phone" required inputmode="tel"></label><?php endif;?><?php if($req['password']):?><label><span>باسورد الاختبار</span><input type="password" name="quiz_password" required autocomplete="off"></label><?php endif;?><button type="submit">ابدأ الاختبار</button></form></section></main><?php wp_footer();?></body></html><?php
	exit;
}

function qalam_081_enter_public_quiz() {
	$quiz_id = absint( $_POST['quiz_id'] ?? 0 );
	check_admin_referer( 'qalam_081_enter_public_' . $quiz_id, 'qalam_public_enter_nonce' );
	if ( ! $quiz_id || '1' !== (string) get_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, true ) ) { wp_die( 'الاختبار غير موجود.' ); }
	$req = qalam_081_public_requirements( $quiz_id );
	$name = sanitize_text_field( wp_unslash( $_POST['student_name'] ?? '' ) );
	$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
	$parent = sanitize_text_field( wp_unslash( $_POST['parent_phone'] ?? '' ) );
	$password = (string) wp_unslash( $_POST['quiz_password'] ?? '' );
	$error = '';
	if ( $req['name'] && mb_strlen( trim( $name ), 'UTF-8' ) < 2 ) { $error = 'اكتب اسم الطالب.'; }
	elseif ( $req['phone'] && ! preg_match( '/^[0-9+()\-\s]{7,20}$/', $phone ) ) { $error = 'اكتب رقم هاتف صحيح.'; }
	elseif ( $req['parent_phone'] && ! preg_match( '/^[0-9+()\-\s]{7,20}$/', $parent ) ) { $error = 'اكتب رقم ولي الأمر بشكل صحيح.'; }
	elseif ( $req['password'] ) {
		$hash = (string) get_post_meta( $quiz_id, QALAM_081_PUBLIC_PASSWORD_META, true );
		if ( ! $hash || ! wp_check_password( $password, $hash ) ) { $error = 'باسورد الاختبار غير صحيح.'; }
	}
	if ( $error ) { qalam_081_render_public_gate( $quiz_id, $error ); }
	try {
		$user_id = qalam_081_guest_user_for_quiz( $quiz_id, array( 'name'=>$name,'phone'=>$phone,'parent_phone'=>$parent ) );
		$target = $quiz_id;
		if ( '1' === (string) get_post_meta( $quiz_id, QALAM_080_DYNAMIC_META, true ) ) {
			$target = qalam_080_create_dynamic_instance( $quiz_id, $user_id );
		}
		qalam_081_enroll_general_quiz_user( $target, $user_id );
		wp_safe_redirect( get_permalink( $target ) );
		exit;
	} catch ( \Throwable $e ) {
		qalam_081_render_public_gate( $quiz_id, $e->getMessage() );
	}
}
add_action( 'admin_post_nopriv_qalam_081_enter_public_quiz', 'qalam_081_enter_public_quiz' );
add_action( 'admin_post_qalam_081_enter_public_quiz', 'qalam_081_enter_public_quiz' );

/** Replace old login-forced routes with the public gate. */
remove_action( 'template_redirect', 'qalam_070_general_quiz_share_route', -5 );
remove_action( 'template_redirect', 'qalam_080_dynamic_share_route', -10 );
function qalam_081_public_general_quiz_route() {
	if ( empty( $_GET['qalam_general_quiz'] ) ) { return; }
	$quiz_id = absint( $_GET['qalam_general_quiz'] );
	if ( ! $quiz_id || '1' !== (string) get_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, true ) ) { wp_die( 'الاختبار غير موجود.' ); }
	qalam_081_render_public_gate( $quiz_id );
}
add_action( 'template_redirect', 'qalam_081_public_general_quiz_route', -30 );

/** Attempts belonging to fixed quiz and dynamic child quizzes. */
function qalam_081_attempts_for_general_quiz( int $quiz_id ): array {
	global $wpdb;
	$quiz_ids = array( $quiz_id );
	$children = get_posts( array( 'post_type'=>tutor()->quiz_post_type,'post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>QALAM_080_DYNAMIC_PARENT_META,'meta_value'=>$quiz_id ) );
	$quiz_ids = array_values( array_unique( array_merge( $quiz_ids, array_map( 'absint', $children ) ) ) );
	$in = implode( ',', array_map( 'absint', $quiz_ids ) );
	return (array) $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE quiz_id IN ($in) AND attempt_status != 'attempt_started' ORDER BY attempt_id DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function qalam_081_render_attempt_review( int $quiz_id, int $attempt_id ): void {
	$attempt = tutor_utils()->get_attempt( $attempt_id );
	if ( ! $attempt ) { echo '<div class="notice notice-error"><p>المحاولة غير موجودة.</p></div>'; return; }
	$allowed_ids = array_map( static fn($a)=>(int)$a->attempt_id, qalam_081_attempts_for_general_quiz( $quiz_id ) );
	if ( ! in_array( $attempt_id, $allowed_ids, true ) ) { wp_die( 'المحاولة لا تخص الاختبار ده.' ); }
	$user_id = (int) $attempt->user_id;
	$user = get_userdata( $user_id );
	$display_name = get_user_meta( $user_id, QALAM_081_GUEST_NAME_META, true ) ?: ( $user ? $user->display_name : 'طالب' );
	?><div class="wrap qalam-050-wrap qalam-081-wrap" dir="rtl"><div class="qalam-050-hero"><div><a href="<?php echo esc_url(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$quiz_id.'&view=results'));?>">← رجوع للنتائج</a><h1>مراجعة إجابات الطالب</h1><p><?php echo esc_html( $display_name );?></p></div></div><section class="qalam-050-panel qalam-attempt-review"><?php
	tutor_load_template_from_custom_path(
		tutor()->path . '/views/quiz/attempt-details.php',
		array( 'attempt_id'=>$attempt_id,'attempt_data'=>$attempt,'user_id'=>$user_id,'context'=>'backend-dashboard-students-attempts' )
	);
	?></section></div><?php
}

function qalam_081_render_general_quiz_results( int $quiz_id ): void {
	$quiz = get_post( $quiz_id );
	$attempts = qalam_081_attempts_for_general_quiz( $quiz_id );
	?><div class="wrap qalam-050-wrap qalam-081-wrap" dir="rtl"><div class="qalam-050-hero"><div><a href="<?php echo esc_url(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$quiz_id));?>">← رجوع للمنشئ</a><h1>نتائج <?php echo esc_html($quiz?$quiz->post_title:'الاختبار');?></h1><p>درجات كل الطلاب مع مراجعة إجابات كل محاولة.</p></div></div><section class="qalam-050-panel"><div class="qalam-050-table-wrap"><table class="widefat striped qalam-050-table"><thead><tr><th>الطالب</th><th>الهاتف</th><th>ولي الأمر</th><th>الدرجة</th><th>النسبة</th><th>النتيجة</th><th>التاريخ</th><th></th></tr></thead><tbody><?php if(!$attempts):?><tr><td colspan="8">لسه مفيش محاولات مكتملة.</td></tr><?php else:foreach($attempts as $attempt):$uid=(int)$attempt->user_id;$u=get_userdata($uid);$name=get_user_meta($uid,QALAM_081_GUEST_NAME_META,true)?:($u?$u->display_name:'طالب');$phone=get_user_meta($uid,QALAM_081_GUEST_PHONE_META,true);$parent=get_user_meta($uid,QALAM_081_GUEST_PARENT_META,true);$total=(float)$attempt->total_marks;$earned=(float)$attempt->earned_marks;$pct=$total>0?round(($earned/$total)*100,1):0;?><tr><td><strong><?php echo esc_html($name);?></strong></td><td><?php echo esc_html($phone?:'—');?></td><td><?php echo esc_html($parent?:'—');?></td><td><?php echo esc_html($earned.' / '.$total);?></td><td><?php echo esc_html($pct.'%');?></td><td><?php echo esc_html('pass'===$attempt->result?'ناجح':('fail'===$attempt->result?'غير ناجح':'قيد المراجعة'));?></td><td><?php echo esc_html($attempt->attempt_ended_at?:$attempt->attempt_started_at);?></td><td><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$quiz_id.'&view=results&attempt_id='.(int)$attempt->attempt_id));?>">مراجعة الإجابات</a></td></tr><?php endforeach;endif;?></tbody></table></div></section></div><?php
}

/* -------------------------------------------------------------------------
 * Replacement admin renderers with real actions.
 * ---------------------------------------------------------------------- */

function qalam_081_admin_menu( $menu ) {
	if ( isset( $menu['group_one']['qalam_question_bank'] ) ) { $menu['group_one']['qalam_question_bank']['callback'] = 'qalam_081_render_question_bank'; }
	if ( isset( $menu['group_one']['qalam_quiz_builder'] ) ) { $menu['group_one']['qalam_quiz_builder']['callback'] = 'qalam_081_render_quiz_builder'; }
	return $menu;
}
add_filter( 'tutor_admin_menu', 'qalam_081_admin_menu', PHP_INT_MAX );

function qalam_081_render_quiz_builder() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) { wp_die( 'غير مسموح.' ); }
	$quiz_id = absint( $_GET['quiz_id'] ?? 0 );
	$view = sanitize_key( (string) ( $_GET['view'] ?? '' ) );
	if ( $quiz_id && 'results' === $view ) {
		$attempt_id = absint( $_GET['attempt_id'] ?? 0 );
		if ( $attempt_id ) { qalam_081_render_attempt_review( $quiz_id, $attempt_id ); }
		else { qalam_081_render_general_quiz_results( $quiz_id ); }
		return;
	}
	if ( $quiz_id ) { qalam_081_render_general_quiz_editor( $quiz_id ); return; }
	$quizzes = get_posts( array( 'post_type'=>tutor()->quiz_post_type,'post_status'=>array('publish','draft','private'),'posts_per_page'=>100,'meta_key'=>QALAM_GENERAL_QUIZ_META,'meta_value'=>'1','orderby'=>'date','order'=>'DESC' ) );
	?><div class="wrap qalam-050-wrap qalam-081-wrap" dir="rtl"><div class="qalam-050-hero"><div><span class="qalam-050-eyebrow">Qalam LMS</span><h1>منشئ الاختبارات العامة</h1><p>اختبارات مستقلة، رابط عام من غير تسجيل دخول، ونتائج ومراجعة إجابات من نفس المكان.</p></div></div><section class="qalam-050-panel"><h2>إنشاء اختبار عام جديد</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="qalam-050-form"><input type="hidden" name="action" value="qalam_070_create_general_quiz"><?php wp_nonce_field('qalam_070_create_general_quiz','qalam_quiz_nonce');?><label class="qalam-050-grow"><span>اسم الاختبار</span><input type="text" name="quiz_title" required placeholder="مثال: اختبار تجريبي شامل"></label><button class="button button-primary qalam-050-primary">إنشاء وفتح الاختبار</button></form></section><section class="qalam-050-panel"><h2>الاختبارات العامة</h2><div class="qalam-050-table-wrap"><table class="widefat striped qalam-050-table"><thead><tr><th>الاختبار</th><th>الأسئلة</th><th>المحاولات</th><th>الرابط</th><th>الإجراءات</th></tr></thead><tbody><?php if(!$quizzes):?><tr><td colspan="5">لا توجد اختبارات عامة.</td></tr><?php else:global $wpdb;foreach($quizzes as $q):$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tutor_quiz_questions WHERE quiz_id=%d",$q->ID));$attempts=count(qalam_081_attempts_for_general_quiz((int)$q->ID));?><tr><td><strong><?php echo esc_html($q->post_title);?></strong></td><td><?php echo esc_html($count);?></td><td><?php echo esc_html($attempts);?></td><td><a href="<?php echo esc_url(qalam_070_general_quiz_share_url((int)$q->ID));?>" target="_blank">فتح رابط الطلاب</a></td><td><div class="qalam-row-actions"><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$q->ID));?>">فتح المنشئ</a><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$q->ID.'&view=results'));?>">النتائج</a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" onsubmit="return confirm('حذف الاختبار وكل محاولاته نهائيًا؟');"><input type="hidden" name="action" value="qalam_081_delete_general_quiz"><input type="hidden" name="quiz_id" value="<?php echo esc_attr($q->ID);?>"><?php wp_nonce_field('qalam_081_delete_quiz_'.$q->ID,'qalam_081_delete_nonce');?><button class="button qalam-danger-button">حذف</button></form></div></td></tr><?php endforeach;endif;?></tbody></table></div></section></div><?php
}

function qalam_081_render_general_quiz_editor( int $quiz_id ) {
	if ( '1' !== (string) get_post_meta( $quiz_id, QALAM_GENERAL_QUIZ_META, true ) ) { echo '<div class="notice notice-error"><p>اختبار غير صالح.</p></div>'; return; }
	$quiz = get_post( $quiz_id ); if ( ! $quiz ) { return; }
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tutor_quiz_questions WHERE quiz_id=%d ORDER BY question_order ASC", $quiz_id ) );
	$options = get_post_meta( $quiz_id, \TUTOR\Quiz::META_QUIZ_OPTION, true ); $options = is_array($options)?$options:array();
	$question_bank_enabled = ! function_exists( 'qalam_feature_enabled' ) || qalam_feature_enabled( 'question_bank' );
	$ai_enabled = ! function_exists( 'qalam_feature_enabled' ) || qalam_feature_enabled( 'ai_question_generation' );
	$pdf_enabled = ! function_exists( 'qalam_feature_enabled' ) || qalam_feature_enabled( 'pdf_question_generation' );
	$randomized_enabled = ! function_exists( 'qalam_feature_enabled' ) || qalam_feature_enabled( 'randomized_exams' );
	$dynamic_enabled = ! function_exists( 'qalam_feature_enabled' ) || qalam_feature_enabled( 'dynamic_exams' );
	$bank = $question_bank_enabled ? get_posts( array( 'post_type'=>'cb-question','post_status'=>array('publish','draft','private'),'posts_per_page'=>200,'orderby'=>'modified','order'=>'DESC' ) ) : array();
	$req = qalam_081_public_requirements( $quiz_id );
	$access_error = sanitize_text_field( wp_unslash( $_GET['access_error'] ?? '' ) );
	?><div class="wrap qalam-050-wrap qalam-081-wrap" dir="rtl"><div class="qalam-050-hero"><div><a href="<?php echo esc_url(admin_url('admin.php?page=qalam-quiz-builder'));?>">← كل الاختبارات</a><h1><?php echo esc_html($quiz->post_title);?></h1><p>منشئ اختبار عام مستقل. رابط الطلاب لا يحتاج تسجيل دخول.</p></div><div class="qalam-050-hero-actions"><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$quiz_id.'&view=results'));?>">عرض النتائج</a><a class="button button-primary" target="_blank" href="<?php echo esc_url(qalam_070_general_quiz_share_url($quiz_id));?>">مشاركة / معاينة</a></div></div><?php if($access_error):?><div class="notice notice-error inline"><p><?php echo esc_html($access_error);?></p></div><?php endif;?>
	<section class="qalam-050-panel"><h2>تفاصيل الاختبار</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="qalam-quiz-settings-grid"><input type="hidden" name="action" value="qalam_070_save_general_quiz"><input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id);?>"><?php wp_nonce_field('qalam_070_save_general_quiz_'.$quiz_id,'qalam_quiz_save_nonce');?><label><span>اسم الاختبار</span><input type="text" name="post_title" value="<?php echo esc_attr($quiz->post_title);?>" required></label><label class="qalam-wide"><span>الوصف</span><textarea name="post_content" rows="3"><?php echo esc_textarea($quiz->post_content);?></textarea></label><label><span>درجة النجاح %</span><input type="number" min="0" max="100" name="passing_grade" value="<?php echo esc_attr((int)($options['passing_grade']??50));?>"></label><label><span>مدة الاختبار بالدقائق</span><input type="number" min="0" name="time_value" value="<?php echo esc_attr((int)($options['time_limit']['time_value']??0));?>"></label><label><span>عدد المحاولات (0 = غير محدود)</span><input type="number" min="0" name="attempts_allowed" value="<?php echo esc_attr((int)($options['attempts_allowed']??0));?>"></label><button class="button button-primary">حفظ الاختبار</button></form></section>
	<section class="qalam-050-panel"><div class="qalam-050-section-head"><div><h2>متطلبات الدخول من الرابط</h2><p>كل شرط له زر تشغيل/إيقاف. الطالب يدخل من الرابط العام من غير حساب.</p></div></div><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="qalam-access-grid"><input type="hidden" name="action" value="qalam_081_save_public_requirements"><input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id);?>"><?php wp_nonce_field('qalam_081_public_requirements_'.$quiz_id,'qalam_081_public_nonce');?><?php foreach(array('name'=>'اسم الطالب','phone'=>'رقم الهاتف','parent_phone'=>'رقم ولي الأمر','password'=>'باسورد الاختبار') as $key=>$label):?><label class="qalam-access-toggle"><span><?php echo esc_html($label);?></span><input type="checkbox" name="require_<?php echo esc_attr($key);?>" value="1" <?php checked(!empty($req[$key]));?>><i></i></label><?php endforeach;?><label class="qalam-access-password"><span>تعيين/تغيير باسورد الاختبار</span><input type="password" name="quiz_password" placeholder="سيبها فاضية لو مش هتغير الباسورد"></label><button class="button button-primary">حفظ متطلبات الدخول</button></form></section>
	<section class="qalam-050-panel"><div class="qalam-050-section-head"><div><h2>أسئلة الاختبار</h2><p><?php $methods=array(); if($question_bank_enabled){$methods[]='بنك الأسئلة';} if($ai_enabled){$methods[]='الذكاء الاصطناعي'.($pdf_enabled?' وPDF':'');} if($randomized_enabled){$methods[]='الاختيار العشوائي';} if($dynamic_enabled){$methods[]='الاختبار الديناميكي';} echo esc_html($methods?'طرق الإضافة المتاحة: '.implode('، ',$methods).'.':'إدارة الأسئلة الموجودة في الاختبار.'); ?></p></div></div><table class="widefat striped qalam-050-table"><thead><tr><th>#</th><th>السؤال</th><th>النوع</th><th>الصعوبة</th><th>الدرجة</th><th></th></tr></thead><tbody><?php $labels=qalam_060_question_type_labels();if(!$rows):?><tr><td colspan="6">لا توجد أسئلة.</td></tr><?php else:foreach($rows as $i=>$row):?><tr><td><?php echo esc_html($i+1);?></td><td><?php echo esc_html($row->question_title);?></td><td><?php echo esc_html($labels[$row->question_type]??$row->question_type);?></td><td><?php $qs=maybe_unserialize($row->question_settings);qalam_071_render_difficulty_badge(is_array($qs)?(string)($qs['qalam_difficulty']??''):'');?></td><td><?php echo esc_html($row->question_mark);?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" onsubmit="return confirm('حذف السؤال من الاختبار؟');"><input type="hidden" name="action" value="qalam_070_remove_quiz_question"><input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id);?>"><input type="hidden" name="question_id" value="<?php echo esc_attr($row->question_id);?>"><?php wp_nonce_field('qalam_070_remove_quiz_question_'.$quiz_id,'qalam_remove_nonce');?><button class="button">حذف</button></form></td></tr><?php endforeach;endif;?></tbody></table></section>
	<?php if($question_bank_enabled):?><section class="qalam-050-panel"><h2>إضافة من بنك الأسئلة</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="qalam_070_import_questions_to_quiz"><input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id);?>"><?php wp_nonce_field('qalam_070_import_questions_to_quiz_'.$quiz_id,'qalam_import_nonce');?><div class="qalam-bank-picker"><?php if(!$bank):?><p>بنك الأسئلة فاضي.</p><?php else:foreach($bank as $content):$r=$wpdb->get_row($wpdb->prepare("SELECT question_type FROM {$wpdb->prefix}tutor_quiz_questions WHERE content_id=%d LIMIT 1",$content->ID));?><label><input type="checkbox" name="content_ids[]" value="<?php echo esc_attr($content->ID);?>"><span><?php echo esc_html($content->post_title);?></span><small><?php echo esc_html($r?($labels[$r->question_type]??$r->question_type):'');?> · <?php $d=qalam_071_difficulty_data((string)get_post_meta($content->ID,QALAM_QBANK_DIFFICULTY_META,true));echo esc_html($d['label']);?></small></label><?php endforeach;endif;?></div><button class="button button-primary">إضافة المحدد للاختبار</button></form></section><?php endif;?>
	<?php if($ai_enabled){qalam_070_render_ai_generator(array('target'=>'quiz','quiz_id'=>$quiz_id,'term_id'=>0));}?></div><?php
}


function qalam_082_render_question_category_options( array $terms, int $parent = 0, int $selected = 0, int $depth = 0 ): void {
	foreach ( $terms as $term ) {
		if ( (int) $term->parent !== $parent ) { continue; }
		$prefix = $depth ? str_repeat( '— ', $depth ) : '';
		printf(
			'<option value="%d" %s>%s%s</option>',
			(int) $term->term_id,
			selected( $selected, (int) $term->term_id, false ),
			esc_html( $prefix ),
			esc_html( $term->name )
		);
		qalam_082_render_question_category_options( $terms, (int) $term->term_id, $selected, $depth + 1 );
	}
}

function qalam_081_render_question_bank() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) { wp_die( 'غير مسموح.' ); }
	$edit_id = absint( $_GET['edit_question'] ?? 0 );
	if ( $edit_id && qalam_081_can_manage_bank_question( $edit_id ) ) {
		qalam_081_render_question_quick_edit( $edit_id ); return;
	}
	$term_id=absint($_GET['question_category']??0);$search=sanitize_text_field(wp_unslash($_GET['q']??''));$terms=get_terms(array('taxonomy'=>QALAM_QUESTION_CATEGORY_TAX,'hide_empty'=>false));$terms=is_wp_error($terms)?array():$terms;$tax_query=$term_id?array(array('taxonomy'=>QALAM_QUESTION_CATEGORY_TAX,'field'=>'term_id','terms'=>$term_id,'include_children'=>true)):array();$questions=get_posts(array('post_type'=>'cb-question','post_status'=>array('publish','draft','private'),'posts_per_page'=>200,'s'=>$search,'tax_query'=>$tax_query,'orderby'=>'modified','order'=>'DESC'));$labels=qalam_060_question_type_labels();$types=array();$ai_enabled=!function_exists('qalam_feature_enabled')||qalam_feature_enabled('ai_question_generation');$pdf_enabled=!function_exists('qalam_feature_enabled')||qalam_feature_enabled('pdf_question_generation');global $wpdb;if($questions){$ids=array_map(static fn($q)=>(int)$q->ID,$questions);$ph=implode(',',array_fill(0,count($ids),'%d'));$sql=$wpdb->prepare("SELECT content_id,question_type FROM {$wpdb->prefix}tutor_quiz_questions WHERE content_id IN ($ph)",$ids);foreach((array)$wpdb->get_results($sql) as $row){$types[(int)$row->content_id]=(string)$row->question_type;}}$deleted=absint($_GET['qalam_deleted']??0);$updated=!empty($_GET['qalam_updated']);
	?><div class="wrap qalam-050-wrap qalam-081-wrap" dir="rtl"><div class="qalam-050-hero"><div><span class="qalam-050-eyebrow">Qalam LMS</span><h1>بنك الأسئلة</h1><p>تعديل، حذف، تحديد جماعي، ومعاينة حقيقية للسؤال.</p></div><div class="qalam-050-hero-actions"><a class="button button-primary" href="<?php echo esc_url(qalam_070_native_question_url($term_id));?>">+ إنشاء سؤال يدوي</a><?php if($ai_enabled):?><a class="button" href="#qalam-ai-question-generator">✨ إنشاء بالذكاء الاصطناعي<?php echo $pdf_enabled?' / PDF':'';?></a><?php endif;?></div></div><?php if($deleted):?><div class="notice notice-success inline"><p>تم حذف <?php echo esc_html($deleted);?> سؤال من البنك.</p></div><?php endif;?><?php if($updated):?><div class="notice notice-success inline"><p>تم تحديث السؤال.</p></div><?php endif;?><div class="qalam-question-layout"><aside class="qalam-question-sidebar qalam-050-panel"><h2>التصنيفات</h2><a class="qalam-category-link <?php echo $term_id?'':'is-active';?>" href="<?php echo esc_url(admin_url('admin.php?page=qalam-question-bank'));?>">كل الأسئلة</a><?php qalam_060_render_term_tree($terms,0,$term_id);?><hr><h3>إضافة تصنيف</h3><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="qalam_060_add_question_category"><?php wp_nonce_field('qalam_060_add_question_category','qalam_qcat_nonce');?><input type="text" name="name" required placeholder="اسم التصنيف"><select name="parent"><option value="0">بدون تصنيف أب</option><?php foreach($terms as $term):?><option value="<?php echo esc_attr($term->term_id);?>"><?php echo esc_html($term->name);?></option><?php endforeach;?></select><button class="button button-primary">إضافة التصنيف</button></form></aside><main class="qalam-question-main"><section class="qalam-050-panel"><div class="qalam-050-section-head"><div><h2>الأسئلة</h2><p>حذف السؤال من البنك لا يحذف النسخ الموجودة بالفعل داخل اختبارات سابقة.</p></div><form method="get" class="qalam-bank-filter-form"><input type="hidden" name="page" value="qalam-question-bank"><select name="question_category" aria-label="فلترة حسب التصنيف"><option value="0">كل التصنيفات</option><?php qalam_082_render_question_category_options($terms,0,$term_id);?></select><input type="search" name="q" value="<?php echo esc_attr($search);?>" placeholder="ابحث في بنك الأسئلة..."><button class="button" type="submit">تطبيق الفلتر</button><?php if($term_id||$search):?><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=qalam-question-bank'));?>">إلغاء الفلتر</a><?php endif;?></form></div><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" data-qalam-bank-bulk><input type="hidden" name="action" value="qalam_081_bank_bulk_delete"><input type="hidden" name="return_category" value="<?php echo esc_attr($term_id);?>"><input type="hidden" name="return_q" value="<?php echo esc_attr($search);?>"><?php wp_nonce_field('qalam_081_bank_bulk_delete','qalam_081_bank_nonce');?><div class="qalam-bank-bulkbar"><label><input type="checkbox" data-qalam-select-all> تحديد كل الظاهر</label><span class="qalam-bank-selected-count" data-qalam-selected-count>تم تحديد 0 سؤال</span><button class="button qalam-danger-button" type="submit" name="delete_selected" value="1" data-qalam-delete-selected disabled onclick="return confirm('حذف الأسئلة المحددة من البنك؟');">حذف المحدد</button><button class="button qalam-danger-button" type="submit" name="delete_all" value="1" onclick="return confirm('حذف كل أسئلة بنك الأسئلة نهائيًا؟');">حذف كل أسئلة البنك</button></div><div class="qalam-050-table-wrap"><table class="widefat striped qalam-050-table"><thead><tr><th></th><th>السؤال</th><th>النوع</th><th>الصعوبة</th><th>التصنيف</th><th>الإجراءات</th></tr></thead><tbody><?php if(!$questions):?><tr><td colspan="6">لسه مفيش أسئلة.</td></tr><?php else:foreach($questions as $q):$qterms=wp_get_post_terms($q->ID,QALAM_QUESTION_CATEGORY_TAX,array('fields'=>'names'));?><tr><td><input type="checkbox" name="question_ids[]" value="<?php echo esc_attr($q->ID);?>" data-qalam-bank-check></td><td><strong><?php echo esc_html($q->post_title);?></strong></td><td><?php echo esc_html($labels[$types[$q->ID]??'']??($types[$q->ID]??'—'));?></td><td><?php qalam_071_render_difficulty_badge((string)get_post_meta($q->ID,QALAM_QBANK_DIFFICULTY_META,true));?></td><td><?php echo esc_html($qterms?implode(' ← ',$qterms):'غير مصنف');?></td><td><div class="qalam-row-actions"><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=qalam-question-bank&edit_question='.$q->ID));?>">تعديل</a><a class="button" target="_blank" href="<?php echo esc_url(home_url('/?qalam_question_preview='.$q->ID));?>">معاينة الطالب</a><button class="button qalam-danger-button" type="submit" name="single_delete" value="<?php echo esc_attr($q->ID);?>" onclick="return confirm('حذف السؤال من البنك؟');">حذف</button></div></td></tr><?php endforeach;endif;?></tbody></table></div></form></section><?php if($ai_enabled){qalam_070_render_ai_generator(array('target'=>'bank','term_id'=>$term_id));}?></main></div></div><?php
}

function qalam_081_render_question_quick_edit( int $content_id ): void {
	$post=get_post($content_id);global $wpdb;$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutor_quiz_questions WHERE content_id=%d LIMIT 1",$content_id));if(!$post||!$row){echo '<div class="notice notice-error"><p>تعذر تحميل السؤال.</p></div>';return;}$settings=maybe_unserialize($row->question_settings);$settings=is_array($settings)?$settings:array();$difficulty=(string)get_post_meta($content_id,QALAM_QBANK_DIFFICULTY_META,true);$collection=(int)$post->post_parent;
	?><div class="wrap qalam-050-wrap qalam-081-wrap" dir="rtl"><div class="qalam-050-hero"><div><a href="<?php echo esc_url(admin_url('admin.php?page=qalam-question-bank'));?>">← رجوع لبنك الأسئلة</a><h1>تعديل السؤال</h1><p>تعديل سريع للنص والشرح والدرجة والصعوبة. للمحرر التفاعلي الكامل افتح السؤال من بنك المحتوى.</p></div><div><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tutor-content-bank&collection_id='.$collection));?>">فتح المحرر الكامل في بنك المحتوى</a></div></div><section class="qalam-050-panel"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="qalam-question-edit-form"><input type="hidden" name="action" value="qalam_081_save_bank_question_basic"><input type="hidden" name="question_id" value="<?php echo esc_attr($content_id);?>"><?php wp_nonce_field('qalam_081_edit_question_'.$content_id,'qalam_081_edit_nonce');?><label><span>نص السؤال</span><textarea name="question_title" rows="3" required><?php echo esc_textarea($row->question_title);?></textarea></label><label><span>الوصف / التفاصيل</span><textarea name="question_description" rows="6"><?php echo esc_textarea(wp_strip_all_tags($row->question_description));?></textarea></label><div class="qalam-050-form"><label><span>الدرجة</span><input type="number" min="0.01" step="0.01" name="question_mark" value="<?php echo esc_attr($row->question_mark);?>"></label><label><span>الصعوبة</span><select name="difficulty"><option value="easy" <?php selected($difficulty,'easy');?>>سهل</option><option value="medium" <?php selected($difficulty,'medium');?>>متوسط</option><option value="hard" <?php selected($difficulty,'hard');?>>صعب</option></select></label><button class="button button-primary">حفظ التعديل</button></div></form></section></div><?php
}

/* -------------------------------------------------------------------------
 * Frontend design and assets.
 * ---------------------------------------------------------------------- */

function qalam_081_body_class( $classes ) {
	if ( is_singular( tutor()->quiz_post_type ) ) {
		$id = get_queried_object_id();
		if ( '1' === (string) get_post_meta( $id, QALAM_GENERAL_QUIZ_META, true ) || get_post_meta( $id, QALAM_080_DYNAMIC_PARENT_META, true ) ) {
			$classes[] = 'qalam-public-quiz';
		}
	}
	return $classes;
}
add_filter( 'body_class', 'qalam_081_body_class', 99 );

function qalam_081_assets() {
	$base = plugin_dir_url( TUTOR_FILE );
	if ( is_admin() ) {
		$page = sanitize_key( (string) ( $_GET['page'] ?? '' ) );
		if ( in_array( $page, array( 'qalam-question-bank','qalam-quiz-builder' ), true ) ) {
			wp_enqueue_style( 'qalam-081-admin', $base . 'assets/css/qalam-081-admin.css', array('qalam-080-admin'), QALAM_LMS_UI_VERSION );
			wp_enqueue_script( 'qalam-081-admin', $base . 'assets/js/qalam-081-admin.js', array(), QALAM_LMS_UI_VERSION, true );
			wp_localize_script( 'qalam-081-admin', 'Qalam081', array( 'ajaxUrl'=>admin_url('admin-ajax.php'),'statusNonce'=>wp_create_nonce('qalam_080_process_generation') ) );
		}
	} else {
		wp_enqueue_style( 'qalam-081-public', $base . 'assets/css/qalam-081-public.css', array('qalam-080-quiz'), QALAM_LMS_UI_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'qalam_081_assets', PHP_INT_MAX );
add_action( 'wp_enqueue_scripts', 'qalam_081_assets', PHP_INT_MAX );

/** Guest exam sessions should never expose WordPress chrome. */
function qalam_081_hide_admin_bar_for_guest( $show ) {
	if ( is_user_logged_in() && '1' === (string) get_user_meta( get_current_user_id(), QALAM_081_GUEST_META, true ) ) { return false; }
	return $show;
}
add_filter( 'show_admin_bar', 'qalam_081_hide_admin_bar_for_guest', 99 );
