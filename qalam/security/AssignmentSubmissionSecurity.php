<?php
/**
 * Private assignment submission files.
 *
 * Student submissions are confidential records. They must not be left under
 * wp-content/uploads where a guessed/static URL can bypass WordPress ACLs.
 */
namespace Qalam\Security;

defined( 'ABSPATH' ) || exit;

final class AssignmentSubmissionSecurity {
    private const META_KEY = 'uploaded_attachments';
    private const QUERY_KEY = 'qalam_assignment_file';

    public static function init(): void {
        add_action( 'template_redirect', array( __CLASS__, 'stream' ), -1000 );
    }

    private static function root(): string {
        $root = PrivateAttachmentStore::root();
        if ( '' === $root ) {
            return '';
        }
        $dir = trailingslashit( $root ) . 'assignment-submissions';
        if ( ! wp_mkdir_p( $dir ) ) {
            return '';
        }
        return rtrim( wp_normalize_path( $dir ), '/\\' );
    }

    private static function submission_dir( int $submission_id ): string {
        $root = self::root();
        if ( '' === $root || $submission_id < 1 ) {
            return '';
        }
        $dir = $root . '/' . $submission_id;
        if ( ! wp_mkdir_p( $dir ) ) {
            return '';
        }
        @chmod( $dir, 0700 );
        return $dir;
    }

    private static function allowed_extension( string $name ): string {
        $ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
        return in_array( $ext, array( 'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png' ), true ) ? $ext : '';
    }

    /** Store a newly uploaded assignment file directly in the private store. */
    public static function store_upload( array $file, int $assignment_id, int $submission_id, int $owner_id ) {
        if ( $assignment_id < 1 || $submission_id < 1 || $owner_id < 1 ) {
            return new \WP_Error( 'qalam_assignment_context', 'سياق ملف الواجب غير صالح.' );
        }
        if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
            return new \WP_Error( 'qalam_assignment_upload', 'تعذر رفع ملف الواجب.' );
        }
        $tmp  = (string) ( $file['tmp_name'] ?? '' );
        $name = sanitize_file_name( wp_basename( (string) ( $file['name'] ?? '' ) ) );
        $size = absint( $file['size'] ?? 0 );
        $ext  = self::allowed_extension( $name );
        if ( '' === $tmp || ! is_uploaded_file( $tmp ) || $size < 1 || '' === $ext ) {
            return new \WP_Error( 'qalam_assignment_upload_type', 'نوع ملف الواجب غير مسموح.' );
        }

        $wp_type = wp_check_filetype( $name, get_allowed_mime_types() );
        $mime    = (string) ( $wp_type['type'] ?? '' );
        if ( '' === $mime || ! PrivateAttachmentStore::is_safe_mime( $mime ) ) {
            return new \WP_Error( 'qalam_assignment_upload_mime', 'نوع ملف الواجب غير آمن.' );
        }
        if ( in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
            $actual = (string) wp_get_image_mime( $tmp );
            if ( '' === $actual || 0 !== strpos( $actual, 'image/' ) ) {
                return new \WP_Error( 'qalam_assignment_upload_image', 'ملف الصورة غير صالح.' );
            }
            $mime = $actual;
        }

        $dir = self::submission_dir( $submission_id );
        if ( '' === $dir ) {
            return new \WP_Error( 'qalam_assignment_private_store', 'التخزين الخاص غير متاح؛ تم رفض الملف لحماية خصوصية الطالب.' );
        }

        $private_id = wp_generate_uuid4();
        $stored_name = $private_id . '.' . preg_replace( '/[^a-z0-9]/', '', $ext );
        $dest = $dir . '/' . $stored_name;
        if ( ! @move_uploaded_file( $tmp, $dest ) ) {
            return new \WP_Error( 'qalam_assignment_move', 'تعذر نقل ملف الواجب إلى التخزين الخاص.' );
        }
        @chmod( $dest, 0600 );

        return array(
            'name'            => $name,
            'type'            => $mime,
            'size'            => $size,
            'qalam_private'   => 1,
            'private_id'      => $private_id,
            'private_file'    => $stored_name,
            'assignment_id'   => $assignment_id,
            'submission_id'   => $submission_id,
            'owner_id'        => $owner_id,
            // Compatibility keys remain present but never point to a public URL/path.
            'url'             => '',
            'uploaded_path'   => '',
        );
    }

    private static function decode_raw( int $submission_id ): array {
        $raw = get_comment_meta( $submission_id, self::META_KEY, true );
        if ( is_array( $raw ) ) {
            return $raw;
        }
        if ( ! is_string( $raw ) || '' === $raw ) {
            return array();
        }
        $decoded = json_decode( $raw, true );
        return is_array( $decoded ) ? $decoded : array();
    }

    /**
     * Migrate an old public-uploads submission on first access.
     * If private storage is unavailable, legacy URLs are deliberately not returned.
     */
    private static function migrate_legacy( int $submission_id, array $files ): array {
        $changed = false;
        $upload  = wp_get_upload_dir();
        $base    = trailingslashit( wp_normalize_path( (string) ( $upload['basedir'] ?? '' ) ) );
        $comment = get_comment( $submission_id );
        $owner   = $comment ? absint( $comment->user_id ) : 0;
        $assignment_id = $comment ? absint( $comment->comment_post_ID ) : 0;
        $dir = self::submission_dir( $submission_id );

        foreach ( $files as $index => $attachment ) {
            if ( ! is_array( $attachment ) || ! empty( $attachment['qalam_private'] ) ) {
                continue;
            }
            $relative = ltrim( wp_normalize_path( (string) ( $attachment['uploaded_path'] ?? '' ) ), '/\\' );
            if ( '' === $relative || '' === $base || '' === $dir ) {
                // Fail closed. Keep metadata for future migration but do not mint a public URL.
                $files[ $index ]['url'] = '';
                continue;
            }
            $src = wp_normalize_path( $base . $relative );
            if ( 0 !== strpos( $src, $base ) || ! is_file( $src ) || ! is_readable( $src ) ) {
                $files[ $index ]['url'] = '';
                continue;
            }
            $name = sanitize_file_name( (string) ( $attachment['name'] ?? wp_basename( $src ) ) );
            $ext  = self::allowed_extension( $name );
            $mime = (string) ( $attachment['type'] ?? '' );
            if ( '' === $ext || ( '' !== $mime && ! PrivateAttachmentStore::is_safe_mime( $mime ) ) ) {
                $files[ $index ]['url'] = '';
                continue;
            }
            $private_id = wp_generate_uuid4();
            $stored_name = $private_id . '.' . preg_replace( '/[^a-z0-9]/', '', $ext );
            $dest = $dir . '/' . $stored_name;
            if ( ! @rename( $src, $dest ) ) {
                if ( ! @copy( $src, $dest ) ) {
                    $files[ $index ]['url'] = '';
                    continue;
                }
                @unlink( $src );
            }
            @chmod( $dest, 0600 );
            $files[ $index ] = array(
                'name'          => $name,
                'type'          => $mime ?: 'application/octet-stream',
                'size'          => absint( @filesize( $dest ) ),
                'qalam_private' => 1,
                'private_id'    => $private_id,
                'private_file'  => $stored_name,
                'assignment_id' => $assignment_id,
                'submission_id' => $submission_id,
                'owner_id'      => $owner,
                'url'           => '',
                'uploaded_path' => '',
            );
            $changed = true;
        }

        if ( $changed ) {
            update_comment_meta( $submission_id, self::META_KEY, wp_json_encode( $files, JSON_UNESCAPED_UNICODE ) );
        }
        return $files;
    }

    /** Return attachment records decorated with a fresh access-controlled URL. */
    public static function get_attachments( int $submission_id, bool $objects = false ): array {
        $files = self::migrate_legacy( $submission_id, self::decode_raw( $submission_id ) );
        foreach ( $files as $i => $file ) {
            if ( ! is_array( $file ) ) {
                unset( $files[ $i ] );
                continue;
            }
            $file['url']  = self::signed_url( $submission_id, $file );
            $file['size'] = self::size( $file, $submission_id );
            $files[ $i ] = $objects ? (object) $file : $file;
        }
        return array_values( $files );
    }

    public static function size( $file, int $submission_id = 0 ): int {
        $file = is_object( $file ) ? (array) $file : (array) $file;
        $size = absint( $file['size'] ?? 0 );
        if ( $size ) {
            return $size;
        }
        $path = self::private_path( $submission_id ?: absint( $file['submission_id'] ?? 0 ), $file );
        return $path && is_file( $path ) ? absint( filesize( $path ) ) : 0;
    }

    private static function private_path( int $submission_id, array $file ): string {
        $dir = self::submission_dir( $submission_id );
        $name = sanitize_file_name( (string) ( $file['private_file'] ?? '' ) );
        if ( '' === $dir || '' === $name ) {
            return '';
        }
        $path = wp_normalize_path( $dir . '/' . $name );
        return 0 === strpos( $path, trailingslashit( wp_normalize_path( $dir ) ) ) ? $path : '';
    }

    private static function can_access( int $submission_id, int $user_id ): bool {
        if ( $submission_id < 1 || $user_id < 1 ) {
            return false;
        }
        $comment = get_comment( $submission_id );
        if ( ! $comment || 'tutor_assignment' !== $comment->comment_type ) {
            return false;
        }
        if ( $user_id === absint( $comment->user_id ) ) {
            return true;
        }
        $course_id = absint( $comment->comment_parent );
        return $course_id > 0 && tutor_utils()->can_user_edit_course( $user_id, $course_id );
    }

    public static function signed_url( int $submission_id, $file ): string {
        $file = is_object( $file ) ? (array) $file : (array) $file;
        $private_id = sanitize_text_field( (string) ( $file['private_id'] ?? '' ) );
        $uid = get_current_user_id();
        if ( $submission_id < 1 || '' === $private_id || $uid < 1 || ! self::can_access( $submission_id, $uid ) ) {
            return '';
        }
        $exp = time() + 600;
        $payload = $submission_id . '|' . $private_id . '|' . $uid . '|' . $exp;
        $sig = hash_hmac( 'sha256', $payload, wp_salt( 'secure_auth' ) );
        return add_query_arg(
            array(
                self::QUERY_KEY => $submission_id,
                'f'             => rawurlencode( $private_id ),
                'u'             => $uid,
                'e'             => $exp,
                's'             => $sig,
            ),
            home_url( '/' )
        );
    }

    public static function delete_file( int $submission_id, string $file_name ): bool {
        $files = self::decode_raw( $submission_id );
        $next  = array();
        $deleted = false;
        foreach ( $files as $file ) {
            if ( ! is_array( $file ) ) {
                continue;
            }
            if ( hash_equals( (string) ( $file['name'] ?? '' ), $file_name ) ) {
                $path = self::private_path( $submission_id, $file );
                if ( $path && is_file( $path ) ) {
                    @unlink( $path );
                }
                $deleted = true;
                continue;
            }
            $next[] = $file;
        }
        if ( $deleted ) {
            update_comment_meta( $submission_id, self::META_KEY, wp_json_encode( $next, JSON_UNESCAPED_UNICODE ) );
        }
        return $deleted;
    }

    public static function delete_submission_files( int $submission_id ): void {
        foreach ( self::decode_raw( $submission_id ) as $file ) {
            if ( is_array( $file ) ) {
                $path = self::private_path( $submission_id, $file );
                if ( $path && is_file( $path ) ) {
                    @unlink( $path );
                }
            }
        }
        $dir = self::submission_dir( $submission_id );
        if ( $dir && is_dir( $dir ) ) {
            @rmdir( $dir );
        }
    }

    public static function stream(): void {
        if ( empty( $_GET[ self::QUERY_KEY ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- signed read-only URL.
            return;
        }
        $submission_id = absint( $_GET[ self::QUERY_KEY ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $private_id    = sanitize_text_field( wp_unslash( $_GET['f'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $uid           = absint( $_GET['u'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $exp           = absint( $_GET['e'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $sig           = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $current       = get_current_user_id();
        $payload       = $submission_id . '|' . $private_id . '|' . $uid . '|' . $exp;
        $expected      = hash_hmac( 'sha256', $payload, wp_salt( 'secure_auth' ) );
        if ( $uid !== $current || $exp < time() || $exp > time() + 7200 || ! hash_equals( $expected, $sig ) || ! self::can_access( $submission_id, $uid ) ) {
            status_header( 403 );
            exit;
        }
        $match = null;
        foreach ( self::decode_raw( $submission_id ) as $file ) {
            if ( is_array( $file ) && hash_equals( (string) ( $file['private_id'] ?? '' ), $private_id ) ) {
                $match = $file;
                break;
            }
        }
        if ( ! $match ) {
            status_header( 404 );
            exit;
        }
        $path = self::private_path( $submission_id, $match );
        if ( ! $path || ! is_file( $path ) || ! is_readable( $path ) ) {
            status_header( 404 );
            exit;
        }
        $mime = sanitize_mime_type( (string) ( $match['type'] ?? 'application/octet-stream' ) );
        if ( ! PrivateAttachmentStore::is_safe_mime( $mime ) ) {
            status_header( 415 );
            exit;
        }
        nocache_headers();
        header( 'Content-Type: ' . $mime );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Content-Length: ' . filesize( $path ) );
        header( 'Content-Disposition: attachment; filename="' . rawurlencode( (string) ( $match['name'] ?? wp_basename( $path ) ) ) . '"' );
        readfile( $path );
        exit;
    }
}

AssignmentSubmissionSecurity::init();
