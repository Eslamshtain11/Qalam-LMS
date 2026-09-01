<?php
/**
 * Check addon is enabled or not
 *
 * @since v2.1.0
 *
 * @package TutorPro\GoogleMeet\Validator
 */

namespace TutorPro\GoogleMeet\Validator;

use TutorPro\GoogleMeet\GoogleMeet;

/**
 * Manage security & validation
 */
class Validator {

	/**
	 * Check if addon is enabled, if not enabled then
	 * return
	 *
	 * @since v2.1.0
	 *
	 * @return bool
	 */
	public static function is_addon_enabled() {
		$plugin_data = GoogleMeet::meta_data();
		return tutor_utils()->is_addon_enabled( $plugin_data['basename'] );
	}

	/** Check whether current user can mutate a specific Google Meet post. */
	public static function can_manage_meeting( $post_id ) {
		$post_id = absint( $post_id );
		$post = $post_id ? get_post( $post_id ) : null;
		if ( ! $post || 'tutor-google-meet' !== $post->post_type ) { return false; }
		if ( current_user_can( 'manage_options' ) || current_user_can( 'qalam_manage_addons' ) ) { return true; }
		$user_id = get_current_user_id();
		if ( $user_id < 1 ) { return false; }
		if ( (int) $post->post_author === $user_id ) { return true; }
		$course_id = (int) tutor_utils()->get_course_id_by_content( $post_id );
		return $course_id > 0 && tutor_utils()->can_user_manage( 'course', $course_id, $user_id, false );
	}

	/**
	 * Check if current user can access google meet
	 *
	 * Check If user is administrator or tutor instructor
	 *
	 * @since v2.1.0
	 *
	 * @return bool
	 */
	public static function current_user_has_access() {
		return \current_user_can( 'administrator' ) || \current_user_can( 'qalam_manage_addons' ) || \current_user_can( tutor()->instructor_role );
	}
	
}
