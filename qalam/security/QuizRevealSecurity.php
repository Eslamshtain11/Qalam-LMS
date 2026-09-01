<?php
/**
 * Secure active-quiz answer reveal.
 *
 * Tutor 4.0.4 preloads every correct choice ID in window.tutor_quiz_context. That makes
 * active quiz answers recoverable from page source. Qalam commits the learner response
 * server-side first and only then returns the correct IDs for already-committed questions.
 */
namespace Qalam\Security;

use Tutor\Models\QuizModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QuizRevealSecurity {
	const OPTION_PREFIX = 'qalam_quiz_reveal_commit_';
	const NONCE_ACTION  = 'qalam_quiz_reveal_commit';

	public static function init(): void {
		add_action( 'wp_ajax_qalam_quiz_reveal_commit', array( __CLASS__, 'ajax_commit' ) );
		add_filter( 'qalam_quiz_attempt_answers_before_submit', array( __CLASS__, 'merge_committed_answers' ), PHP_INT_MAX, 4 );
		add_action( 'tutor_quiz/attempt_ended', array( __CLASS__, 'cleanup_attempt' ), PHP_INT_MAX, 1 );
		add_action( 'tutor_quiz_timeout', array( __CLASS__, 'cleanup_attempt' ), PHP_INT_MAX, 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), PHP_INT_MAX );
	}

	private static function option_name( int $attempt_id ): string {
		return self::OPTION_PREFIX . $attempt_id;
	}

	private static function load_commits( int $attempt_id ): array {
		$value = get_option( self::option_name( $attempt_id ), array() );
		return is_array( $value ) ? $value : array();
	}

	private static function save_commits( int $attempt_id, array $commits ): bool {
		return update_option( self::option_name( $attempt_id ), $commits, false );
	}

	private static function supported_type( string $type ): bool {
		return in_array( $type, array( QuizModel::QUESTION_TYPE_TRUE_FALSE, QuizModel::QUESTION_TYPE_SINGLE_CHOICE, QuizModel::QUESTION_TYPE_MULTIPLE_CHOICE ), true );
	}

	private static function validate_attempt( int $attempt_id, int $user_id ) {
		$attempt = tutor_utils()->get_attempt( $attempt_id );
		if ( ! is_object( $attempt ) || (int) ( $attempt->user_id ?? 0 ) !== $user_id || QuizModel::ATTEMPT_STARTED !== (string) ( $attempt->attempt_status ?? '' ) ) {
			return false;
		}
		return $attempt;
	}

	private static function reveal_enabled( int $quiz_id ): bool {
		$settings = tutor_utils()->get_quiz_option( $quiz_id );
		return is_array( $settings ) && '1' === (string) ( $settings['enable_answer_reveal'] ?? '0' );
	}

	private static function normalize_selected( $selected ): array {
		$selected = is_array( $selected ) ? $selected : array( $selected );
		$selected = array_values( array_unique( array_filter( array_map( 'absint', $selected ) ) ) );
		return $selected;
	}

	private static function validate_question_response( int $quiz_id, int $question_id, $selected ) {
		global $wpdb;
		$question = QuizModel::get_quiz_question_by_id( $question_id );
		if ( ! is_object( $question ) || (int) ( $question->quiz_id ?? 0 ) !== $quiz_id || ! self::supported_type( (string) $question->question_type ) ) {
			return new \WP_Error( 'qalam_reveal_question', 'السؤال غير صالح لكشف الإجابة.' );
		}

		$selected = self::normalize_selected( $selected );
		$allowed  = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT answer_id FROM {$wpdb->prefix}tutor_quiz_question_answers WHERE belongs_question_id = %d AND belongs_question_type = %s",
				$question_id,
				$question->question_type
			)
		);
		$allowed = array_map( 'absint', $allowed );
		if ( array_diff( $selected, $allowed ) ) {
			return new \WP_Error( 'qalam_reveal_answer', 'الإجابة المختارة غير مرتبطة بالسؤال.' );
		}
		if ( QuizModel::QUESTION_TYPE_MULTIPLE_CHOICE !== $question->question_type && count( $selected ) > 1 ) {
			return new \WP_Error( 'qalam_reveal_answer_count', 'هذا النوع يقبل إجابة واحدة فقط.' );
		}
		return array( 'type' => (string) $question->question_type, 'selected' => $selected );
	}

	private static function correct_ids_for_commits( int $quiz_id, array $commits ): array {
		global $wpdb;
		$ids = array();
		foreach ( $commits as $question_id => $commit ) {
			$question_id = absint( $question_id );
			$question = $question_id ? QuizModel::get_quiz_question_by_id( $question_id ) : null;
			if ( ! is_object( $question ) || (int) ( $question->quiz_id ?? 0 ) !== $quiz_id || ! self::supported_type( (string) $question->question_type ) ) {
				continue;
			}
			$correct = (array) $wpdb->get_col(
				$wpdb->prepare(
					"SELECT answer_id FROM {$wpdb->prefix}tutor_quiz_question_answers WHERE belongs_question_id = %d AND belongs_question_type = %s AND is_correct = 1",
					$question_id,
					$question->question_type
				)
			);
			$ids = array_merge( $ids, array_map( 'strval', array_map( 'absint', $correct ) ) );
		}
		return array_values( array_unique( $ids ) );
	}

	public static function ajax_commit(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$user_id    = get_current_user_id();
		$attempt_id = absint( $_POST['attempt_id'] ?? 0 );
		$quiz_id    = absint( $_POST['quiz_id'] ?? 0 );
		if ( ! $user_id || ! $attempt_id || ! $quiz_id ) {
			wp_send_json_error( array( 'message' => 'طلب غير صالح.' ), 400 );
		}
		$attempt = self::validate_attempt( $attempt_id, $user_id );
		if ( ! $attempt || (int) ( $attempt->quiz_id ?? 0 ) !== $quiz_id || ! self::reveal_enabled( $quiz_id ) ) {
			wp_send_json_error( array( 'message' => 'غير مصرح بكشف الإجابة.' ), 403 );
		}

		$raw = isset( $_POST['responses'] ) ? wp_unslash( $_POST['responses'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$responses = json_decode( (string) $raw, true );
		if ( ! is_array( $responses ) || count( $responses ) > 250 ) {
			wp_send_json_error( array( 'message' => 'بيانات الإجابات غير صالحة.' ), 400 );
		}

		$commits = self::load_commits( $attempt_id );
		foreach ( $responses as $question_id => $selected ) {
			$question_id = absint( $question_id );
			if ( ! $question_id ) { continue; }
			// A revealed answer is immutable for this attempt. Never overwrite a prior commit.
			if ( isset( $commits[ $question_id ] ) ) { continue; }
			$validated = self::validate_question_response( $quiz_id, $question_id, $selected );
			if ( is_wp_error( $validated ) ) {
				wp_send_json_error( array( 'message' => $validated->get_error_message() ), 400 );
			}
			$commits[ $question_id ] = $validated;
		}
		self::save_commits( $attempt_id, $commits );

		wp_send_json_success(
			array(
				'correct_ids'         => self::correct_ids_for_commits( $quiz_id, $commits ),
				'committed_questions' => array_values( array_map( 'absint', array_keys( $commits ) ) ),
			)
		);
	}

	public static function merge_committed_answers( $attempt_answers, $attempt, $attempt_id, $user_id ) {
		if ( ! is_array( $attempt_answers ) || ! is_object( $attempt ) || (int) ( $attempt->user_id ?? 0 ) !== (int) $user_id ) {
			return $attempt_answers;
		}
		$commits = self::load_commits( (int) $attempt_id );
		if ( ! $commits ) { return $attempt_answers; }
		$key = (int) $attempt_id;
		if ( ! isset( $attempt_answers[ $key ] ) || ! is_array( $attempt_answers[ $key ] ) ) { $attempt_answers[ $key ] = array(); }
		if ( ! isset( $attempt_answers[ $key ]['quiz_question'] ) || ! is_array( $attempt_answers[ $key ]['quiz_question'] ) ) { $attempt_answers[ $key ]['quiz_question'] = array(); }
		if ( ! isset( $attempt_answers[ $key ]['quiz_question_ids'] ) || ! is_array( $attempt_answers[ $key ]['quiz_question_ids'] ) ) { $attempt_answers[ $key ]['quiz_question_ids'] = array(); }

		foreach ( $commits as $question_id => $commit ) {
			$question_id = absint( $question_id ); $selected = self::normalize_selected( $commit['selected'] ?? array() );
			if ( ! in_array( $question_id, array_map( 'absint', $attempt_answers[ $key ]['quiz_question_ids'] ), true ) ) { $attempt_answers[ $key ]['quiz_question_ids'][] = $question_id; }
			if ( ! $selected ) { unset( $attempt_answers[ $key ]['quiz_question'][ $question_id ] ); continue; }
			$attempt_answers[ $key ]['quiz_question'][ $question_id ] = QuizModel::QUESTION_TYPE_MULTIPLE_CHOICE === ( $commit['type'] ?? '' ) ? $selected : (string) reset( $selected );
		}
		return $attempt_answers;
	}

	public static function cleanup_attempt( $attempt_id ): void {
		$attempt_id = absint( $attempt_id ); if ( $attempt_id ) { delete_option( self::option_name( $attempt_id ) ); }
	}

	public static function enqueue(): void {
		if ( is_admin() ) { return; }
		$attempt = tutor_utils()->is_started_quiz();
		if ( ! is_object( $attempt ) || ! self::reveal_enabled( (int) ( $attempt->quiz_id ?? 0 ) ) ) { return; }
		$handle = 'qalam-quiz-reveal-security';
		wp_enqueue_script( $handle, plugin_dir_url( TUTOR_FILE ) . 'assets/js/qalam-quiz-reveal-security.js', array(), defined( 'QALAM_LMS_UI_VERSION' ) ? QALAM_LMS_UI_VERSION : TUTOR_VERSION, true );
		$commits = self::load_commits( (int) $attempt->attempt_id );
		$selected = array();
		foreach ( $commits as $question_id => $commit ) { $selected[ absint( $question_id ) ] = self::normalize_selected( $commit['selected'] ?? array() ); }
		wp_localize_script(
			$handle,
			'QalamQuizReveal',
			array(
				'ajaxurl'             => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( self::NONCE_ACTION ),
				'attemptId'           => (int) $attempt->attempt_id,
				'quizId'              => (int) $attempt->quiz_id,
				'committedQuestions'  => array_values( array_map( 'absint', array_keys( $commits ) ) ),
				'committedSelections' => $selected,
			)
		);
	}
}

QuizRevealSecurity::init();
