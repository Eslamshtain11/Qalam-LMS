<?php
/** Qalam Student learning-domain adapters over the bundled LMS engine. */
defined( 'ABSPATH' ) || exit;

final class Qalam_Mobile_Learning {
    public static function user_id( WP_REST_Request $request ): int {
        return (int) $request->get_param( '_qalam_mobile_user_id' );
    }

    private static function feature_guard( string $feature_key ) {
        if ( function_exists( 'qalam_feature_enabled' ) && ! qalam_feature_enabled( $feature_key ) ) {
            return new WP_Error( 'feature_not_enabled', 'هذه الميزة غير متاحة ضمن اشتراك المؤسسة.', array( 'status' => 403, 'feature' => $feature_key ) );
        }
        return true;
    }

    public static function student( int $user_id ): array {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return array();
        }
        return array(
            'id'         => (int) $user->ID,
            'name'       => (string) $user->display_name,
            'first_name' => (string) get_user_meta( $user_id, 'first_name', true ),
            'last_name'  => (string) get_user_meta( $user_id, 'last_name', true ),
            'email'      => (string) $user->user_email,
            'avatar_url' => (string) get_avatar_url( $user_id, array( 'size' => 256 ) ),
        );
    }

    public static function course_access( int $course_id, int $user_id ): bool {
        if ( $course_id < 1 || ! get_post( $course_id ) ) {
            return false;
        }
        if ( class_exists( '\Tutor\Models\EnrollmentModel' ) && \Tutor\Models\EnrollmentModel::is_enrolled( $course_id, $user_id ) ) {
            return true;
        }
        return function_exists( 'tutor_utils' ) && tutor_utils()->has_user_course_content_access( $user_id, $course_id );
    }

    public static function content_course_id( int $content_id ): int {
        if ( ! function_exists( 'tutor_utils' ) ) {
            return 0;
        }
        return (int) tutor_utils()->get_course_id_by_content( $content_id );
    }

    public static function content_access( int $content_id, int $user_id ): bool {
        $course_id = self::content_course_id( $content_id );
        if ( ! self::course_access( $course_id, $user_id ) ) {
            return false;
        }
        wp_set_current_user( $user_id );
        return (bool) apply_filters( 'tutor_course/single/content/show_permalink', true, $content_id );
    }

    private static function category( int $course_id ): string {
        $terms = get_the_terms( $course_id, 'course-category' );
        return is_array( $terms ) && $terms ? (string) $terms[0]->name : '';
    }

    private static function next_lesson_id( int $course_id, int $user_id ) {
        $contents = tutor_utils()->get_course_contents_by_id( $course_id );
        foreach ( (array) $contents as $content ) {
            if ( tutor()->lesson_post_type !== (string) $content->post_type ) {
                continue;
            }
            if ( ! tutor_utils()->is_completed_lesson( (int) $content->ID, $user_id ) && self::content_access( (int) $content->ID, $user_id ) ) {
                return (int) $content->ID;
            }
        }
        return null;
    }

    public static function course_card( WP_Post $course, int $user_id ): array {
        $stats      = tutor_utils()->get_course_completed_percent( $course->ID, $user_id, true );
        $instructor = get_userdata( (int) $course->post_author );
        return array(
            'id'                => (int) $course->ID,
            'title'             => (string) $course->post_title,
            'thumbnail_url'     => (string) ( get_the_post_thumbnail_url( $course->ID, 'large' ) ?: '' ),
            'instructor_name'   => $instructor ? (string) $instructor->display_name : '',
            'category'          => self::category( (int) $course->ID ),
            'progress_percent'  => (int) ( $stats['completed_percent'] ?? 0 ),
            'completed_lessons' => (int) ( $stats['completed_count'] ?? 0 ),
            'total_lessons'     => (int) ( $stats['total_count'] ?? 0 ),
            'next_lesson_id'    => self::next_lesson_id( (int) $course->ID, $user_id ),
        );
    }

    public static function courses( int $user_id ): array {
        if ( ! class_exists( '\Tutor\Models\CourseModel' ) ) {
            return array();
        }
        $query = \Tutor\Models\CourseModel::get_enrolled_courses_by_user( $user_id, array( 'publish', 'private' ) );
        $posts = is_object( $query ) && method_exists( $query, 'get_posts' ) ? $query->get_posts() : array();
        return array_values( array_map( static fn( $course ) => self::course_card( $course, $user_id ), (array) $posts ) );
    }

    public static function course_detail( int $course_id, int $user_id ) {
        $course = get_post( $course_id );
        if ( ! $course || ! self::course_access( $course_id, $user_id ) ) {
            return new WP_Error( 'course_forbidden', 'الدورة غير متاحة لهذا الطالب.', array( 'status' => 403 ) );
        }
        $data = self::course_card( $course, $user_id );
        $data['description'] = wp_strip_all_tags( (string) $course->post_content );
        $data['sections']    = array();
        $topics = tutor_utils()->get_topics( $course_id );
        foreach ( (array) ( $topics->posts ?? array() ) as $topic ) {
            $section = array( 'id' => (int) $topic->ID, 'title' => (string) $topic->post_title, 'lessons' => array() );
            $content_query = tutor_utils()->get_course_contents_by_topic( $topic->ID, -1 );
            foreach ( (array) ( $content_query->posts ?? array() ) as $content ) {
                if ( tutor()->lesson_post_type !== (string) $content->post_type ) {
                    continue;
                }
                $section['lessons'][] = self::lesson_summary( $content, $user_id );
            }
            $data['sections'][] = $section;
        }
        return $data;
    }

    private static function duration_seconds( $video_info ): int {
        if ( ! is_object( $video_info ) ) {
            return 0;
        }
        $runtime = isset( $video_info->runtime ) && is_array( $video_info->runtime ) ? $video_info->runtime : array();
        if ( $runtime ) {
            return (int) ( (int) ( $runtime['hours'] ?? 0 ) * 3600 + (int) ( $runtime['minutes'] ?? 0 ) * 60 + (int) ( $runtime['seconds'] ?? 0 ) );
        }
        $parts = array_reverse( array_map( 'intval', explode( ':', (string) ( $video_info->playtime ?? '' ) ) ) );
        return (int) ( ( $parts[0] ?? 0 ) + 60 * ( $parts[1] ?? 0 ) + 3600 * ( $parts[2] ?? 0 ) );
    }

    public static function lesson_summary( WP_Post $lesson, int $user_id ): array {
        $video = tutor_utils()->get_video_info( $lesson->ID );
        return array(
            'id'               => (int) $lesson->ID,
            'title'            => (string) $lesson->post_title,
            'type'             => $video ? 'video' : 'text',
            'duration_seconds' => self::duration_seconds( $video ),
            'is_completed'     => (bool) tutor_utils()->is_completed_lesson( $lesson->ID, $user_id ),
            'is_locked'        => ! self::content_access( (int) $lesson->ID, $user_id ),
        );
    }

    public static function lesson( int $lesson_id, int $user_id ) {
        $lesson = get_post( $lesson_id );
        if ( ! $lesson || tutor()->lesson_post_type !== (string) $lesson->post_type || ! self::content_access( $lesson_id, $user_id ) ) {
            return new WP_Error( 'lesson_forbidden', 'الدرس غير متاح لهذا الطالب.', array( 'status' => 403 ) );
        }
        $data = self::lesson_summary( $lesson, $user_id );
        $data['content_text'] = wp_strip_all_tags( (string) $lesson->post_content );
        $data['content_html'] = wp_kses_post( (string) $lesson->post_content );
        $video = tutor_utils()->get_video_info( $lesson_id );
        $data['video_url'] = self::video_url( $lesson_id, $user_id, $video );
        $attachments = tutor_utils()->get_attachments( $lesson_id );
        $resources = array();
        foreach ( (array) $attachments as $attachment ) {
            $aid = (int) ( $attachment->id ?? 0 );
            if ( $aid ) {
                $resources[] = array(
                    'id'   => $aid,
                    'name' => (string) ( $attachment->name ?? get_the_title( $aid ) ),
                    'url'  => self::signed_media_url( $aid, $user_id, $lesson_id ),
                );
            }
        }
        $data['resources']       = $resources;
        $data['resource_url']    = $resources ? (string) $resources[0]['url'] : '';
        $data['playback_progress'] = self::lesson_progress( $lesson_id, $user_id );
        return $data;
    }

    private static function video_url( int $lesson_id, int $user_id, $video ): string {
        if ( ! is_object( $video ) ) {
            return '';
        }
        $source = (string) ( $video->source ?? '' );
        if ( 'html5' === $source && ! empty( $video->source_video_id ) ) {
            return self::signed_media_url( (int) $video->source_video_id, $user_id, $lesson_id );
        }
        foreach ( array( 'source_youtube', 'source_vimeo', 'source_external_url', 'url' ) as $field ) {
            if ( ! empty( $video->{$field} ) && filter_var( $video->{$field}, FILTER_VALIDATE_URL ) ) {
                return esc_url_raw( (string) $video->{$field} );
            }
        }
        return '';
    }

    private static function media_signature( int $attachment_id, int $user_id, int $lesson_id, int $expires ): string {
        return hash_hmac( 'sha256', $attachment_id . '|' . $user_id . '|' . $lesson_id . '|' . $expires, wp_salt( 'secure_auth' ) );
    }

    public static function signed_media_url( int $attachment_id, int $user_id, int $lesson_id, int $ttl = 900 ): string {
        $expires = time() + max( 60, min( 3600, $ttl ) );
        return add_query_arg(
            array(
                'uid' => $user_id,
                'lid' => $lesson_id,
                'exp' => $expires,
                'sig' => self::media_signature( $attachment_id, $user_id, $lesson_id, $expires ),
            ),
            rest_url( 'qalam-mobile/v1/media/' . $attachment_id )
        );
    }

    public static function validate_media_request( int $attachment_id, WP_REST_Request $request ) {
        $uid = absint( $request->get_param( 'uid' ) );
        $lid = absint( $request->get_param( 'lid' ) );
        $exp = absint( $request->get_param( 'exp' ) );
        $sig = sanitize_text_field( (string) $request->get_param( 'sig' ) );
        if ( ! $uid || ! $lid || $exp < time() || $exp > time() + 3700 || ! hash_equals( self::media_signature( $attachment_id, $uid, $lid, $exp ), $sig ) ) {
            return new WP_Error( 'invalid_media_token', 'رابط الملف غير صالح أو انتهت مدته.', array( 'status' => 401 ) );
        }
        if ( ! self::content_access( $lid, $uid ) ) {
            return new WP_Error( 'media_forbidden', 'الملف غير متاح لهذا الطالب.', array( 'status' => 403 ) );
        }
        $allowed = array_map( 'intval', wp_list_pluck( (array) tutor_utils()->get_attachments( $lid ), 'id' ) );
        $video   = tutor_utils()->get_video_info( $lid );
        if ( is_object( $video ) && ! empty( $video->source_video_id ) ) {
            $allowed[] = (int) $video->source_video_id;
        }
        if ( ! in_array( $attachment_id, $allowed, true ) ) {
            return new WP_Error( 'media_forbidden', 'الملف لا يتبع هذا الدرس.', array( 'status' => 403 ) );
        }
        return true;
    }

    public static function progress_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'qalam_mobile_progress';
    }

    public static function install_progress_table(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $table = self::progress_table();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            course_id bigint(20) unsigned NOT NULL,
            lesson_id bigint(20) unsigned NOT NULL,
            position_seconds int(10) unsigned NOT NULL DEFAULT 0,
            duration_seconds int(10) unsigned NOT NULL DEFAULT 0,
            percent decimal(5,2) NOT NULL DEFAULT 0.00,
            completed tinyint(1) NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_lesson (user_id, lesson_id),
            KEY user_updated (user_id, updated_at),
            KEY user_course (user_id, course_id)
        ) {$charset};" );
    }

    public static function lesson_progress( int $lesson_id, int $user_id ): array {
        global $wpdb;
        $table = self::progress_table();
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT position_seconds,duration_seconds,percent,completed,updated_at FROM {$table} WHERE user_id=%d AND lesson_id=%d LIMIT 1", $user_id, $lesson_id ) );
        if ( ! $row ) {
            return array( 'position_seconds' => 0, 'duration_seconds' => 0, 'percent' => 0.0, 'completed' => (bool) tutor_utils()->is_completed_lesson( $lesson_id, $user_id ), 'updated_at' => null );
        }
        return array(
            'position_seconds' => (int) $row->position_seconds,
            'duration_seconds' => (int) $row->duration_seconds,
            'percent'          => (float) $row->percent,
            'completed'        => (bool) $row->completed || (bool) tutor_utils()->is_completed_lesson( $lesson_id, $user_id ),
            'updated_at'       => mysql2date( 'c', (string) $row->updated_at, false ),
        );
    }

    public static function record_progress( int $lesson_id, int $user_id, int $position_seconds, int $duration_seconds, bool $completed = false ) {
        global $wpdb;
        if ( ! self::content_access( $lesson_id, $user_id ) ) {
            return new WP_Error( 'lesson_forbidden', 'لا يمكن تسجيل تقدم هذا الدرس.', array( 'status' => 403 ) );
        }
        $course_id = self::content_course_id( $lesson_id );
        $position_seconds = max( 0, $position_seconds );
        $duration_seconds = max( 0, $duration_seconds );
        if ( $duration_seconds > 0 ) {
            $position_seconds = min( $position_seconds, $duration_seconds );
            $percent = min( 100, round( ( $position_seconds / $duration_seconds ) * 100, 2 ) );
        } else {
            $percent = 0.0;
        }
        if ( $completed ) {
            $percent = 100.0;
            if ( class_exists( '\\Tutor\Models\LessonModel' ) ) {
                \Tutor\Models\LessonModel::mark_lesson_complete( $lesson_id, $user_id );
            }
        }
        $table = self::progress_table();
        $now = current_time( 'mysql', true );
        $sql = $wpdb->prepare(
            "INSERT INTO {$table} (user_id,course_id,lesson_id,position_seconds,duration_seconds,percent,completed,updated_at) VALUES (%d,%d,%d,%d,%d,%f,%d,%s)
             ON DUPLICATE KEY UPDATE course_id=VALUES(course_id),position_seconds=VALUES(position_seconds),duration_seconds=VALUES(duration_seconds),percent=VALUES(percent),completed=GREATEST(completed,VALUES(completed)),updated_at=VALUES(updated_at)",
            $user_id, $course_id, $lesson_id, $position_seconds, $duration_seconds, $percent, $completed ? 1 : 0, $now
        );
        $result = $wpdb->query( $sql );
        if ( false === $result ) {
            return new WP_Error( 'progress_save_failed', 'تعذر حفظ تقدم الدرس.', array( 'status' => 500 ) );
        }
        return array( 'ok' => true, 'lesson_id' => $lesson_id, 'course_id' => $course_id, 'progress' => self::lesson_progress( $lesson_id, $user_id ) );
    }

    public static function resume( int $user_id, int $course_id = 0 ) {
        global $wpdb;
        $table = self::progress_table();
        $where = 'user_id=%d';
        $args = array( $user_id );
        if ( $course_id > 0 ) {
            if ( ! self::course_access( $course_id, $user_id ) ) {
                return new WP_Error( 'course_forbidden', 'الدورة غير متاحة لهذا الطالب.', array( 'status' => 403 ) );
            }
            $where .= ' AND course_id=%d';
            $args[] = $course_id;
        }
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY updated_at DESC LIMIT 20", ...$args ) );
        foreach ( (array) $rows as $row ) {
            $lesson_id = (int) $row->lesson_id;
            if ( ! self::content_access( $lesson_id, $user_id ) ) {
                continue;
            }
            return array(
                'lesson_id'        => $lesson_id,
                'lesson_title'     => (string) get_the_title( $lesson_id ),
                'course_id'        => (int) $row->course_id,
                'course_title'     => (string) get_the_title( (int) $row->course_id ),
                'position_seconds' => (int) $row->position_seconds,
                'duration_seconds' => (int) $row->duration_seconds,
                'percent'          => (float) $row->percent,
                'completed'        => (bool) $row->completed,
                'updated_at'       => mysql2date( 'c', (string) $row->updated_at, false ),
            );
        }
        return null;
    }

    public static function mark_complete( int $lesson_id, int $user_id ) {
        if ( ! self::content_access( $lesson_id, $user_id ) ) {
            return new WP_Error( 'lesson_forbidden', 'لا يمكن إكمال هذا الدرس.', array( 'status' => 403 ) );
        }
        if ( class_exists( '\Tutor\Models\LessonModel' ) ) {
            \Tutor\Models\LessonModel::mark_lesson_complete( $lesson_id, $user_id );
        }
        $current = self::lesson_progress( $lesson_id, $user_id );
        self::record_progress( $lesson_id, $user_id, (int) ( $current['duration_seconds'] ?: $current['position_seconds'] ), (int) $current['duration_seconds'], true );
        return array( 'ok' => true, 'progress_percent' => (int) tutor_utils()->get_course_completed_percent( self::content_course_id( $lesson_id ), $user_id ) );
    }

    private static function enrolled_course_ids( int $user_id ): array {
        return array_values( array_unique( array_map( 'intval', (array) tutor_utils()->get_enrolled_courses_ids_by_user( $user_id, false ) ) ) );
    }

    private static function content_items( int $user_id, string $post_type ): array {
        $items = array();
        foreach ( self::enrolled_course_ids( $user_id ) as $course_id ) {
            foreach ( (array) tutor_utils()->get_course_contents_by_id( $course_id ) as $content ) {
                if ( $post_type === (string) $content->post_type && self::content_access( (int) $content->ID, $user_id ) ) {
                    $content->_qalam_course_id = $course_id;
                    $items[] = $content;
                }
            }
        }
        return $items;
    }

    public static function quizzes( int $user_id ): array {
        global $wpdb;
        $out = array();
        foreach ( self::content_items( $user_id, 'tutor_quiz' ) as $quiz ) {
            $settings = (array) tutor_utils()->get_quiz_option( $quiz->ID );
            $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_quiz_attempts WHERE quiz_id=%d AND user_id=%d", $quiz->ID, $user_id ) );
            $limited = '1' === (string) ( $settings['limit_attempts_allowed'] ?? '0' );
            $allowed = (int) ( $settings['attempts_allowed'] ?? 0 );
            $left = $limited && $allowed > 0 ? max( 0, $allowed - $count ) : null;
            $best = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(earned_marks) FROM {$wpdb->prefix}tutor_quiz_attempts WHERE quiz_id=%d AND user_id=%d AND attempt_status<>%s", $quiz->ID, $user_id, 'attempt_started' ) );
            $out[] = array(
                'id'            => (int) $quiz->ID,
                'title'         => (string) $quiz->post_title,
                'course_title'  => (string) get_the_title( (int) $quiz->_qalam_course_id ),
                'status'        => $count > 0 ? 'completed' : 'available',
                'due_at'        => null,
                'attempts_left' => $left,
                'best_score'    => null === $best ? null : (float) $best,
                'passing_score' => isset( $settings['passing_grade'] ) ? (float) $settings['passing_grade'] : null,
            );
        }
        return $out;
    }

    public static function quiz_access( int $quiz_id, int $user_id ) {
        $quiz = get_post( $quiz_id );
        if ( ! $quiz || 'tutor_quiz' !== (string) $quiz->post_type || ! self::content_access( $quiz_id, $user_id ) ) {
            return new WP_Error( 'quiz_forbidden', 'الاختبار غير متاح لهذا الطالب.', array( 'status' => 403 ) );
        }
        return true;
    }

    public static function attempt_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'qalam_mobile_quiz_snapshots';
    }

    public static function install_attempt_table(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $table = self::attempt_table();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( "CREATE TABLE {$table} (
            attempt_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            quiz_id bigint(20) unsigned NOT NULL,
            public_snapshot longtext NOT NULL,
            grading_snapshot longtext NOT NULL,
            draft_answers longtext NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (attempt_id),
            KEY user_quiz (user_id, quiz_id)
        ) {$charset};" );
    }

    private static function sanitize_question_type( string $type ): string {
        $map = array(
            'single_choice'   => 'single_choice',
            'multiple_choice' => 'multiple_choice',
            'true_false'      => 'true_false',
            'short_answer'    => 'short_answer',
            'open_ended'      => 'short_answer',
        );
        return $map[ $type ] ?? '';
    }

    private static function create_quiz_snapshot( int $attempt_id, int $quiz_id, int $user_id ) {
        global $wpdb;
        wp_set_current_user( $user_id );
        $questions = tutor_utils()->get_random_questions_by_quiz( $quiz_id );
        if ( ! is_array( $questions ) ) {
            $questions = array();
        }
        $public  = array();
        $grading = array();
        foreach ( $questions as $q ) {
            $type = self::sanitize_question_type( (string) $q->question_type );
            if ( ! $type ) {
                return new WP_Error( 'mobile_unsupported_quiz_type', 'يحتوي الاختبار على نوع سؤال غير مدعوم في تطبيق الطالب حاليًا.', array( 'status' => 409, 'question_type' => (string) $q->question_type ) );
            }
            $answers = $wpdb->get_results( $wpdb->prepare( "SELECT answer_id,answer_title,is_correct,answer_order FROM {$wpdb->prefix}tutor_quiz_question_answers WHERE belongs_question_id=%d ORDER BY answer_order ASC", $q->question_id ) );
            $options = array();
            $correct = array();
            foreach ( (array) $answers as $a ) {
                $options[] = array( 'id' => (int) $a->answer_id, 'text' => wp_strip_all_tags( (string) $a->answer_title ) );
                if ( (int) $a->is_correct === 1 ) {
                    $correct[] = (int) $a->answer_id;
                }
            }
            $public[] = array(
                'id'      => (int) $q->question_id,
                'text'    => wp_strip_all_tags( (string) $q->question_title ),
                'type'    => $type,
                'points'  => (float) $q->question_mark,
                'options' => 'short_answer' === $type ? array() : $options,
            );
            $grading[(int) $q->question_id] = array(
                'type'    => $type,
                'points'  => (float) $q->question_mark,
                'correct' => $correct,
            );
        }
        $wpdb->replace(
            self::attempt_table(),
            array(
                'attempt_id'       => $attempt_id,
                'user_id'          => $user_id,
                'quiz_id'          => $quiz_id,
                'public_snapshot'  => wp_json_encode( $public, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                'grading_snapshot' => wp_json_encode( $grading, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                'draft_answers'    => '{}',
                'created_at'       => current_time( 'mysql', true ),
                'updated_at'       => current_time( 'mysql', true ),
            ),
            array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
        );
        return $public;
    }

    private static function load_snapshot( int $attempt_id, int $user_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::attempt_table() . ' WHERE attempt_id=%d AND user_id=%d LIMIT 1', $attempt_id, $user_id ) );
    }

    public static function start_quiz( int $quiz_id, int $user_id ) {
        global $wpdb;
        $access = self::quiz_access( $quiz_id, $user_id );
        if ( is_wp_error( $access ) ) {
            return $access;
        }
        wp_set_current_user( $user_id );
        $active = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE quiz_id=%d AND user_id=%d AND attempt_status=%s ORDER BY attempt_id DESC LIMIT 1", $quiz_id, $user_id, 'attempt_started' ) );
        if ( $active ) {
            return self::attempt_payload( (int) $active->attempt_id, $user_id );
        }
        $settings = (array) tutor_utils()->get_quiz_option( $quiz_id );
        $attempted = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_quiz_attempts WHERE quiz_id=%d AND user_id=%d", $quiz_id, $user_id ) );
        $limited = '1' === (string) ( $settings['limit_attempts_allowed'] ?? '0' );
        $allowed = (int) ( $settings['attempts_allowed'] ?? 0 );
        if ( $limited && $allowed > 0 && $attempted >= $allowed ) {
            return new WP_Error( 'quiz_attempt_limit', 'تم الوصول للحد الأقصى لمحاولات هذا الاختبار.', array( 'status' => 409 ) );
        }
        $course_id = self::content_course_id( $quiz_id );
        $time_value = (int) tutor_utils()->get_quiz_option( $quiz_id, 'time_limit.time_value' );
        $time_type  = (string) tutor_utils()->get_quiz_option( $quiz_id, 'time_limit.time_type' );
        $multipliers = array( 'seconds' => 1, 'minutes' => 60, 'hours' => 3600, 'days' => 86400, 'weeks' => 604800 );
        $time_seconds = $time_value * ( $multipliers[ $time_type ] ?? 1 );
        $attempt_info = $settings;
        if ( ! isset( $attempt_info['time_limit'] ) || ! is_array( $attempt_info['time_limit'] ) ) {
            $attempt_info['time_limit'] = array();
        }
        $attempt_info['time_limit']['time_limit_seconds'] = $time_seconds;
        $wpdb->insert(
            $wpdb->prefix . 'tutor_quiz_attempts',
            array(
                'course_id'                => $course_id,
                'quiz_id'                  => $quiz_id,
                'user_id'                  => $user_id,
                'total_questions'          => (int) tutor_utils()->max_questions_for_take_quiz( $quiz_id ),
                'total_answered_questions' => 0,
                'attempt_info'             => maybe_serialize( $attempt_info ),
                'attempt_status'           => 'attempt_started',
                'attempt_ip'               => tutor_utils()->get_ip(),
                'attempt_started_at'       => date( 'Y-m-d H:i:s', tutor_time() ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            )
        );
        $attempt_id = (int) $wpdb->insert_id;
        if ( ! $attempt_id ) {
            return new WP_Error( 'quiz_start_failed', 'تعذر بدء الاختبار.', array( 'status' => 500 ) );
        }
        do_action( 'tutor_quiz/start/after', $quiz_id, $user_id, $attempt_id );
        self::install_attempt_table();
        $snapshot = self::create_quiz_snapshot( $attempt_id, $quiz_id, $user_id );
        if ( is_wp_error( $snapshot ) ) {
            $wpdb->delete( $wpdb->prefix . 'tutor_quiz_attempts', array( 'attempt_id' => $attempt_id ), array( '%d' ) );
            return $snapshot;
        }
        return self::attempt_payload( $attempt_id, $user_id );
    }

    public static function attempt_payload( int $attempt_id, int $user_id ) {
        global $wpdb;
        $attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id=%d AND user_id=%d LIMIT 1", $attempt_id, $user_id ) );
        if ( ! $attempt ) {
            return new WP_Error( 'attempt_not_found', 'محاولة الاختبار غير موجودة.', array( 'status' => 404 ) );
        }
        $snapshot = self::load_snapshot( $attempt_id, $user_id );
        if ( ! $snapshot ) {
            self::install_attempt_table();
            $made = self::create_quiz_snapshot( $attempt_id, (int) $attempt->quiz_id, $user_id );
            if ( is_wp_error( $made ) ) {
                return $made;
            }
            $snapshot = self::load_snapshot( $attempt_id, $user_id );
        }
        $info = maybe_unserialize( $attempt->attempt_info );
        $time = is_array( $info ) ? (int) ( $info['time_limit']['time_limit_seconds'] ?? 0 ) : 0;
        return array(
            'attempt_id'         => (string) $attempt_id,
            'quiz_id'            => (int) $attempt->quiz_id,
            'title'              => (string) get_the_title( (int) $attempt->quiz_id ),
            'time_limit_seconds' => $time > 0 ? $time : null,
            'started_at'         => mysql2date( 'c', (string) $attempt->attempt_started_at, false ),
            'questions'          => json_decode( (string) $snapshot->public_snapshot, true ) ?: array(),
            'draft_answers'      => json_decode( (string) $snapshot->draft_answers, true ) ?: array(),
        );
    }

    private static function attempt_expired( $attempt ): bool {
        if ( ! is_object( $attempt ) ) {
            return true;
        }
        $info = maybe_unserialize( $attempt->attempt_info );
        $limit = is_array( $info ) ? (int) ( $info['time_limit']['time_limit_seconds'] ?? 0 ) : 0;
        if ( $limit <= 0 ) {
            return false;
        }
        $started = strtotime( (string) $attempt->attempt_started_at );
        return $started > 0 && time() > ( $started + $limit + 5 );
    }

    private static function timeout_attempt( $attempt ): void {
        if ( ! is_object( $attempt ) ) {
            return;
        }
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'tutor_quiz_attempts',
            array(
                'attempt_status' => 'attempt_timeout',
                'earned_marks' => 0,
                'attempt_ended_at' => date( 'Y-m-d H:i:s', tutor_time() ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            ),
            array( 'attempt_id' => (int) $attempt->attempt_id ),
            array( '%s', '%f', '%s' ),
            array( '%d' )
        );
        do_action( 'tutor_quiz_timeout', (int) $attempt->attempt_id, (int) $attempt->quiz_id, (int) $attempt->user_id );
    }

    public static function save_quiz_answers( int $quiz_id, int $attempt_id, int $user_id, array $answers ) {
        global $wpdb;
        $attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id=%d AND quiz_id=%d AND user_id=%d AND attempt_status=%s LIMIT 1", $attempt_id, $quiz_id, $user_id, 'attempt_started' ) );
        if ( ! $attempt ) {
            return new WP_Error( 'attempt_not_active', 'محاولة الاختبار غير نشطة.', array( 'status' => 409 ) );
        }
        if ( self::attempt_expired( $attempt ) ) {
            self::timeout_attempt( $attempt );
            return new WP_Error( 'quiz_time_expired', 'انتهى وقت الاختبار.', array( 'status' => 409 ) );
        }
        $snapshot = self::load_snapshot( $attempt_id, $user_id );
        if ( ! $snapshot ) {
            return new WP_Error( 'attempt_snapshot_missing', 'تعذر تحميل نسخة المحاولة.', array( 'status' => 500 ) );
        }
        $questions = json_decode( (string) $snapshot->public_snapshot, true ) ?: array();
        $allowed   = array_map( 'intval', wp_list_pluck( $questions, 'id' ) );
        $clean     = array();
        foreach ( $answers as $qid => $answer ) {
            $qid = absint( $qid );
            if ( ! in_array( $qid, $allowed, true ) ) {
                continue;
            }
            if ( is_array( $answer ) ) {
                $clean[$qid] = array_values( array_map( 'sanitize_text_field', $answer ) );
            } else {
                $clean[$qid] = sanitize_textarea_field( (string) $answer );
            }
        }
        $wpdb->update( self::attempt_table(), array( 'draft_answers' => wp_json_encode( $clean, JSON_UNESCAPED_UNICODE ), 'updated_at' => current_time( 'mysql', true ) ), array( 'attempt_id' => $attempt_id ), array( '%s', '%s' ), array( '%d' ) );
        return array( 'ok' => true, 'saved_answers' => count( $clean ) );
    }

    public static function submit_quiz( int $quiz_id, int $attempt_id, int $user_id, array $answers ) {
        global $wpdb;
        $attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id=%d AND quiz_id=%d AND user_id=%d AND attempt_status=%s LIMIT 1", $attempt_id, $quiz_id, $user_id, 'attempt_started' ) );
        if ( ! $attempt ) {
            return new WP_Error( 'attempt_not_active', 'محاولة الاختبار غير نشطة أو تم تسليمها بالفعل.', array( 'status' => 409 ) );
        }
        if ( self::attempt_expired( $attempt ) ) {
            self::timeout_attempt( $attempt );
            return new WP_Error( 'quiz_time_expired', 'انتهى وقت الاختبار.', array( 'status' => 409 ) );
        }
        $snapshot = self::load_snapshot( $attempt_id, $user_id );
        if ( ! $snapshot ) {
            return new WP_Error( 'attempt_snapshot_missing', 'تعذر تحميل نسخة المحاولة.', array( 'status' => 500 ) );
        }
        $grading = json_decode( (string) $snapshot->grading_snapshot, true ) ?: array();
        $public  = json_decode( (string) $snapshot->public_snapshot, true ) ?: array();
        $total_marks = 0.0;
        $earned      = 0.0;
        $answered    = 0;
        $review      = false;
        $wpdb->query( 'START TRANSACTION' );
        try {
            // Serialize concurrent submissions for the same attempt. Only the first
            // request may move an attempt out of attempt_started.
            $locked_attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id=%d AND quiz_id=%d AND user_id=%d FOR UPDATE", $attempt_id, $quiz_id, $user_id ) );
            if ( ! $locked_attempt || 'attempt_started' !== (string) $locked_attempt->attempt_status ) {
                $wpdb->query( 'ROLLBACK' );
                return new WP_Error( 'attempt_not_active', 'محاولة الاختبار غير نشطة أو تم تسليمها بالفعل.', array( 'status' => 409 ) );
            }
            $attempt = $locked_attempt;
            if ( self::attempt_expired( $attempt ) ) {
                self::timeout_attempt( $attempt );
                $wpdb->query( 'COMMIT' );
                return new WP_Error( 'quiz_time_expired', 'انتهى وقت الاختبار.', array( 'status' => 409 ) );
            }
            $wpdb->delete( $wpdb->prefix . 'tutor_quiz_attempt_answers', array( 'quiz_attempt_id' => $attempt_id ), array( '%d' ) );
            foreach ( $public as $question ) {
                $qid = (int) ( $question['id'] ?? 0 );
                $g   = $grading[$qid] ?? null;
                if ( ! $qid || ! is_array( $g ) ) {
                    continue;
                }
                $points = (float) ( $g['points'] ?? 0 );
                $total_marks += $points;
                $provided = array_key_exists( (string) $qid, $answers ) ? $answers[(string) $qid] : ( $answers[$qid] ?? null );
                if ( null === $provided || '' === $provided || array() === $provided ) {
                    continue;
                }
                ++$answered;
                $type = (string) ( $g['type'] ?? '' );
                $is_correct = null;
                $achieved   = 0.0;
                $given      = '';
                if ( 'single_choice' === $type || 'true_false' === $type ) {
                    $aid = absint( $provided );
                    $given = (string) $aid;
                    $is_correct = in_array( $aid, array_map( 'intval', (array) ( $g['correct'] ?? array() ) ), true );
                } elseif ( 'multiple_choice' === $type ) {
                    $ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $provided ) ) ) );
                    sort( $ids );
                    $correct = array_values( array_unique( array_map( 'intval', (array) ( $g['correct'] ?? array() ) ) ) );
                    sort( $correct );
                    $given = maybe_serialize( $ids );
                    $is_correct = $ids === $correct;
                } else {
                    $given = sanitize_textarea_field( (string) $provided );
                    $review = true;
                }
                if ( true === $is_correct ) {
                    $achieved = $points;
                    $earned += $points;
                }
                $wpdb->insert(
                    $wpdb->prefix . 'tutor_quiz_attempt_answers',
                    array(
                        'user_id'         => $user_id,
                        'quiz_id'         => $quiz_id,
                        'question_id'     => $qid,
                        'quiz_attempt_id' => $attempt_id,
                        'given_answer'    => $given,
                        'question_mark'   => $points,
                        'achieved_mark'   => $achieved,
                        'minus_mark'      => 0,
                        'is_correct'      => null === $is_correct ? null : ( $is_correct ? 1 : 0 ),
                    )
                );
            }
            $status = $review ? 'review_required' : 'attempt_ended';
            $wpdb->update(
                $wpdb->prefix . 'tutor_quiz_attempts',
                array(
                    'total_questions'          => count( $public ),
                    'total_answered_questions' => $answered,
                    'total_marks'              => $total_marks,
                    'earned_marks'             => $earned,
                    'attempt_status'           => $status,
                    'attempt_ended_at'         => date( 'Y-m-d H:i:s', tutor_time() ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                ),
                array( 'attempt_id' => $attempt_id )
            );
            if ( class_exists( '\Tutor\Models\QuizModel' ) ) {
                \Tutor\Models\QuizModel::update_attempt_result( $attempt_id );
            }
            $wpdb->query( 'COMMIT' );
        } catch ( Throwable $e ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'quiz_submit_failed', 'تعذر تسليم الاختبار.', array( 'status' => 500 ) );
        }
        do_action( 'tutor_quiz/attempt_ended', $attempt_id, (int) $attempt->course_id, $user_id );
        return self::quiz_result( $attempt_id, $user_id );
    }

    public static function quiz_results( int $user_id, int $limit = 50 ): array {
        global $wpdb;
        $limit = max( 1, min( 100, $limit ) );
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT attempt_id,quiz_id,course_id FROM {$wpdb->prefix}tutor_quiz_attempts WHERE user_id=%d AND attempt_status<>%s ORDER BY attempt_id DESC LIMIT %d",
                $user_id,
                'attempt_started',
                $limit
            )
        );
        $out = array();
        foreach ( (array) $rows as $row ) {
            if ( ! self::course_access( (int) $row->course_id, $user_id ) ) {
                continue;
            }
            $result = self::quiz_result( (int) $row->attempt_id, $user_id );
            if ( is_wp_error( $result ) ) {
                continue;
            }
            $result['quiz_title']   = (string) get_the_title( (int) $row->quiz_id );
            $result['course_id']    = (int) $row->course_id;
            $result['course_title'] = (string) get_the_title( (int) $row->course_id );
            $out[] = $result;
        }
        return $out;
    }

    public static function quiz_result( int $attempt_id, int $user_id ) {
        global $wpdb;
        $attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id=%d AND user_id=%d LIMIT 1", $attempt_id, $user_id ) );
        if ( ! $attempt ) {
            return new WP_Error( 'attempt_not_found', 'النتيجة غير موجودة.', array( 'status' => 404 ) );
        }
        $settings = (array) tutor_utils()->get_quiz_option( (int) $attempt->quiz_id );
        $passing  = (float) ( $settings['passing_grade'] ?? 0 );
        $percent  = (float) $attempt->total_marks > 0 ? ( (float) $attempt->earned_marks * 100 / (float) $attempt->total_marks ) : 0;
        return array(
            'attempt_id'      => (string) $attempt_id,
            'quiz_id'         => (int) $attempt->quiz_id,
            'score'           => (float) $attempt->earned_marks,
            'max_score'       => (float) $attempt->total_marks,
            'score_percent'   => round( $percent, 2 ),
            'passing_score'   => $passing,
            'passed'          => 'review_required' === (string) $attempt->attempt_status ? null : $percent >= $passing,
            'status'          => (string) $attempt->attempt_status,
            'answered'        => (int) $attempt->total_answered_questions,
            'total_questions' => (int) $attempt->total_questions,
            'message'         => 'review_required' === (string) $attempt->attempt_status ? 'تحتاج بعض الإجابات إلى مراجعة المعلم.' : ( $percent >= $passing ? 'أحسنت!' : 'تم تسجيل نتيجتك.' ),
        );
    }

    public static function assignments( int $user_id ) {
        $gate = self::feature_guard( 'assignments' );
        if ( is_wp_error( $gate ) ) { return $gate; }
        $out = array();
        foreach ( self::content_items( $user_id, 'tutor_assignments' ) as $assignment ) {
            $course_id = (int) $assignment->_qalam_course_id;
            $submission = tutor_utils()->is_assignment_submitted( $assignment->ID, $user_id );
            $deadline = tutor_utils()->get_assignment_deadline_date_in_gmt( $assignment->ID, null, $user_id, $course_id );
            $out[] = array(
                'id'           => (int) $assignment->ID,
                'title'        => (string) $assignment->post_title,
                'course_id'    => $course_id,
                'course_title' => (string) get_the_title( $course_id ),
                'due_at'       => $deadline ? mysql2date( 'c', $deadline, false ) : null,
                'submitted'    => (bool) $submission,
                'submission_id'=> $submission ? (int) $submission->comment_ID : null,
            );
        }
        return $out;
    }

    public static function assignment( int $assignment_id, int $user_id ) {
        $gate = self::feature_guard( 'assignments' );
        if ( is_wp_error( $gate ) ) { return $gate; }
        $post = get_post( $assignment_id );
        if ( ! $post || 'tutor_assignments' !== (string) $post->post_type || ! self::content_access( $assignment_id, $user_id ) ) {
            return new WP_Error( 'assignment_forbidden', 'الواجب غير متاح لهذا الطالب.', array( 'status' => 403 ) );
        }
        $course_id = self::content_course_id( $assignment_id );
        $submission = tutor_utils()->is_assignment_submitted( $assignment_id, $user_id );
        return array(
            'id'             => $assignment_id,
            'title'          => (string) $post->post_title,
            'content'        => wp_kses_post( (string) $post->post_content ),
            'course_id'      => $course_id,
            'course_title'   => (string) get_the_title( $course_id ),
            'due_at'         => ( $deadline = tutor_utils()->get_assignment_deadline_date_in_gmt( $assignment_id, null, $user_id, $course_id ) ) ? mysql2date( 'c', $deadline, false ) : null,
            'total_mark'     => (float) tutor_utils()->get_assignment_option( $assignment_id, 'total_mark', 0 ),
            'pass_mark'      => (float) tutor_utils()->get_assignment_option( $assignment_id, 'pass_mark', 0 ),
            'submitted'      => (bool) $submission,
            'submission_id'  => $submission ? (int) $submission->comment_ID : null,
            'submitted_text' => $submission ? wp_kses_post( (string) $submission->comment_content ) : '',
        );
    }

    public static function submit_assignment( int $assignment_id, int $user_id, string $answer ) {
        $gate = self::feature_guard( 'assignments' );
        if ( is_wp_error( $gate ) ) { return $gate; }
        global $wpdb;
        $access = self::assignment( $assignment_id, $user_id );
        if ( is_wp_error( $access ) ) {
            return $access;
        }
        $answer = wp_kses_post( trim( $answer ) );
        if ( '' === wp_strip_all_tags( $answer ) ) {
            return new WP_Error( 'assignment_answer_required', 'اكتب إجابة الواجب أولًا.', array( 'status' => 400 ) );
        }
        $course_id = self::content_course_id( $assignment_id );
        $deadline = tutor_utils()->get_assignment_deadline_date_in_gmt( $assignment_id, null, $user_id, $course_id );
        if ( $deadline && strtotime( $deadline . ' UTC' ) < time() ) {
            return new WP_Error( 'assignment_expired', 'انتهى موعد تسليم الواجب.', array( 'status' => 409 ) );
        }
        $existing = tutor_utils()->is_assignment_submitted( $assignment_id, $user_id );
        $user = get_userdata( $user_id );
        $data = apply_filters( 'tutor_assignment_submit_updating_data', array(
            'comment_post_ID'  => $assignment_id,
            'comment_author'   => $user ? $user->user_login : 'student',
            'comment_date'     => current_time( 'mysql' ),
            'comment_date_gmt' => current_time( 'mysql', true ),
            'comment_agent'    => 'QalamStudentApp',
            'comment_type'     => 'tutor_assignment',
            'comment_parent'   => $course_id,
            'user_id'          => $user_id,
            'comment_content'  => $answer,
            'comment_approved' => 'submitted',
        ) );
        if ( $existing && ! empty( $existing->comment_ID ) ) {
            $wpdb->update( $wpdb->comments, array( 'comment_content' => $answer, 'comment_date' => current_time( 'mysql' ), 'comment_date_gmt' => current_time( 'mysql', true ), 'comment_approved' => 'submitted' ), array( 'comment_ID' => (int) $existing->comment_ID ) );
            $submission_id = (int) $existing->comment_ID;
        } else {
            $wpdb->insert( $wpdb->comments, $data );
            $submission_id = (int) $wpdb->insert_id;
        }
        if ( ! $submission_id ) {
            return new WP_Error( 'assignment_submit_failed', 'تعذر تسليم الواجب.', array( 'status' => 500 ) );
        }
        do_action( 'tutor_assignment/after/submitted', $submission_id );
        return array( 'ok' => true, 'submission_id' => $submission_id );
    }

    public static function certificates( int $user_id ) {
        $gate = self::feature_guard( 'certificates' );
        if ( is_wp_error( $gate ) ) { return $gate; }
        if ( ! class_exists( '\TUTOR_CERT\Certificate' ) ) {
            return array();
        }
        $cert = new \TUTOR_CERT\Certificate( true );
        $rows = get_comments( array( 'type' => 'course_completed', 'status' => 'approve', 'user_id' => $user_id, 'number' => 200, 'orderby' => 'comment_date_gmt', 'order' => 'DESC' ) );
        $out  = array();
        foreach ( (array) $rows as $completion ) {
            $course_id = absint( $completion->comment_post_ID ?? 0 );
            if ( ! $course_id || ! self::course_access( $course_id, $user_id ) ) {
                continue;
            }
            $revoked = '1' === (string) get_comment_meta( (int) $completion->comment_ID, '_qalam_certificate_revoked', true );
            $url = '';
            if ( ! $revoked ) {
                try { $url = (string) $cert->get_certificate( $course_id, false, $user_id ); } catch ( Throwable $e ) { $url = ''; }
            }
            $out[] = array(
                'id'              => (int) $completion->comment_ID,
                'title'           => 'شهادة إتمام ' . get_the_title( $course_id ),
                'course_title'    => (string) get_the_title( $course_id ),
                'issued_at'       => mysql2date( 'c', (string) $completion->comment_date_gmt, false ),
                'certificate_url' => $url,
                'revoked'         => $revoked,
            );
        }
        return $out;
    }

    public static function announcements( int $user_id ): array {
        $course_ids = self::enrolled_course_ids( $user_id );
        if ( ! $course_ids ) {
            return array();
        }
        $posts = get_posts( array(
            'post_type'      => 'tutor_announcements',
            'post_status'    => 'publish',
            'post_parent__in'=> $course_ids,
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );
        return array_values( array_map( static function( $post ) {
            return array(
                'id'           => (int) $post->ID,
                'title'        => (string) $post->post_title,
                'body'         => wp_kses_post( (string) $post->post_content ),
                'course_id'    => (int) $post->post_parent,
                'course_title' => (string) get_the_title( (int) $post->post_parent ),
                'created_at'   => get_post_time( 'c', true, $post ),
            );
        }, $posts ) );
    }

    public static function notifications( int $user_id, int $limit = 50 ) {
        $gate = self::feature_guard( 'notifications' );
        if ( is_wp_error( $gate ) ) { return $gate; }
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_notifications';
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return array();
        }
        $limit = max( 1, min( 100, $limit ) );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE receiver_id=%d ORDER BY ID DESC LIMIT %d", $user_id, $limit ) );
        return array_values( array_map( static function( $row ) {
            return array(
                'id'         => (int) $row->ID,
                'title'      => wp_strip_all_tags( (string) $row->title ),
                'body'       => wp_strip_all_tags( (string) $row->content ),
                'created_at' => mysql2date( 'c', (string) $row->created_at, false ),
                'read'       => 'READ' === (string) $row->status,
                'data'       => array( 'type' => (string) $row->type, 'post_id' => (int) $row->post_id, 'topic_url' => (string) $row->topic_url ),
            );
        }, (array) $rows ) );
    }

    public static function mark_notification_read( int $notification_id, int $user_id ) {
        $gate = self::feature_guard( 'notifications' );
        if ( is_wp_error( $gate ) ) { return $gate; }
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_notifications';
        $updated = $wpdb->update( $table, array( 'status' => 'READ' ), array( 'ID' => $notification_id, 'receiver_id' => $user_id ), array( '%s' ), array( '%d', '%d' ) );
        return array( 'ok' => false !== $updated );
    }
}
