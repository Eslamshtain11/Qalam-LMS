<?php
/**
 * Manage admin menu and plugin related logic
 *
 * @package Tutor
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TUTOR;

defined( 'ABSPATH' ) || exit;

use Tutor\Ecommerce\OrderController;
use Tutor\Helpers\HttpHelper;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use Tutor\Traits\JsonResponse;

/**
 * Admin Class
 *
 * @since 1.0.0
 */
class Admin {
	use JsonResponse;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'show_unstable_version_admin_notice' ) );

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		// Force activate menu for necessary.
		add_filter( 'parent_file', array( $this, 'parent_menu_active' ) );
		add_filter( 'submenu_file', array( $this, 'submenu_file_active' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'filter_posts_for_instructors' ) );
		add_action( 'admin_init', array( $this, 'redirect_to_welcome_page' ) );
		add_action( 'load-post.php', array( $this, 'check_if_current_users_post' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( TUTOR_FILE ), array( $this, 'plugin_action_links' ) );

		// Plugin Row Meta.
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		// Admin Footer Text.
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
		// Register Course Widget.
		add_action( 'widgets_init', array( $this, 'register_course_widget' ) );

		// Handle flash toast message for redirect_to util helper.
		add_action( 'admin_head', array( new Utils(), 'handle_flash_message' ), 999 );

		add_action( 'admin_bar_menu', array( $this, 'add_toolbar_items' ), 100 );

		add_action( 'wp_ajax_tutor_do_not_show_feature_page', array( $this, 'handle_do_not_show_feature_page' ) );

		add_action( 'upgrader_process_complete', array( $this, 'set_permalink_flag_on_upgrade' ), 10, 2 );

		add_action( 'admin_notices', array( $this, 'show_offer_notice' ) );
		add_action( 'wp_ajax_tutor_dismiss_offer_notice', array( $this, 'ajax_dismiss_offer_notice' ) );
	}

	/**
	 * Flush Tutor permalink rewrite rules after updates.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $upgrader_object Upgrader instance.
	 * @param array $options      Extra arguments passed to the hook.
	 *
	 * @return void
	 */
	public function set_permalink_flag_on_upgrade( $upgrader_object, $options ) {
		Permalink::set_permalink_reset_flag( $upgrader_object, $options );
	}

	/**
	 * Check offer notice is dismissed.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	private function is_offer_notice_dismissed() {
		return '4.0.0' === get_transient( 'tutor_offer_notice_dismissed' );
	}

	/**
	 * Show offer notice
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function show_offer_notice() {
		if ( ! User::is_admin() || Input::has( 'welcome', Input::GET_REQUEST ) ) {
			return;
		}

		$is_free_user = defined( 'QALAM_LMS_UNIFIED' ) ? false : ! tutor()->has_pro;
		$cta_label    = __( 'Claim 30% OFF', 'tutor' );
		$cta_link     = admin_url( 'admin.php?page=tutor' );

		$now           = new \DateTimeImmutable( 'now', wp_timezone() );
		$expiration_dt = '2026-07-15 23:59:59';
		$expiration    = new \DateTimeImmutable( $expiration_dt, wp_timezone() );

		$remaining = max( 0, $expiration->getTimestamp() - $now->getTimestamp() );

		$days    = floor( $remaining / DAY_IN_SECONDS );
		$hours   = floor( ( $remaining % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
		$minutes = floor( ( $remaining % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
		$seconds = $remaining % MINUTE_IN_SECONDS;

		$expire_in = sprintf(
			'%dd:%02dh:%02d:%02d',
			$days,
			$hours,
			$minutes,
			$seconds
		);

		if ( $is_free_user && $remaining > 0 && ! self::is_offer_notice_dismissed() ) {
			?>
			<div class="tutor-offer-notice">
				<div class="tutor-offer-notice-wrapper">
					<div class="tutor-offer-notice-left">
						<div data-subheader><?php esc_html_e( '4.0 launch offer', 'tutor' ); ?></div>
						<div data-header><?php esc_html_e( '30% OFF', 'tutor' ); ?></div>
						<div data-subtext><?php esc_html_e( 'on all annual plans', 'tutor' ); ?></div>
					</div>
					<div class="tutor-offer-notice-right">
						<div class="tutor-offer-notice-text"><?php esc_html_e( 'Offer ends by', 'tutor' ); ?> <span class="tutor-offer-notice-timer" data-expiry="<?php echo esc_attr( $expiration->getTimestamp() ); ?>"><?php echo esc_html( $expire_in ); ?></span></div>
						<a class="tutor-offer-notice-cta" href="<?php echo esc_url( $cta_link ); ?>" target="_blank">
							<?php echo esc_html( $cta_label ); ?>
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path fill="#fff" d="M13.938 6.937a.55.55 0 0 0-.786 0 .555.555 0 0 0 0 .778l3.338 3.34H5.243a.55.55 0 0 0-.55.551c0 .305.242.558.55.558h11.246l-3.337 3.334a.563.563 0 0 0 0 .785.55.55 0 0 0 .786 0L18.217 12a.543.543 0 0 0 0-.78z"/></svg>
						</a>	
					</div>
				</div>
				<button type="button" class="tutor-offer-notice-dismiss"><span class="tutor-icon-times"></span></button>
			</div>
			<?php
		}
	}

	/**
	 * Dismiss offer notice.
	 *
	 * @since 4.0.0
	 *
	 * @return void JSON response.
	 */
	public function ajax_dismiss_offer_notice() {
		if ( ! User::is_admin() ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}

		if ( self::is_offer_notice_dismissed() ) {
			$this->response_bad_request( __( 'You have already dismissed the offer notice.', 'tutor' ) );
		}

		// Set expiry until next day.
		$expiration = strtotime( 'tomorrow' ) - time();
		set_transient( 'tutor_offer_notice_dismissed', '4.0.0', $expiration );
		$this->json_response( __( 'Notice dismissed', 'tutor' ) );
	}

	/**
	 * Show unstable version notice.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function show_unstable_version_admin_notice() {
		$version = tutor_utils()->extract_version_details( TUTOR_VERSION );
		if ( ! $version->is_stable ) {
			/* translators: %s: version name */
			$message = sprintf( __( 'You\'re currently using Qalam LMS %s. To ensure stability, please do not use it on a live site.', 'tutor' ), '<strong>' . $version->version . '</strong>' );
			?>
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Warning!', 'tutor' ); ?></strong></p>
				<p><?php echo wp_kses_post( $message ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Register admin menus
	 *
	 * @since 1.0.0
	 * @since 3.8.0 re-organize admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$has_pro = defined( 'QALAM_LMS_UNIFIED' ) ? true : tutor()->has_pro;

		$unanswered_questions = tutor_utils()->unanswered_question_count();
		$unanswered_bubble    = '';
		if ( $unanswered_questions ) {
			$unanswered_bubble = '<span class="update-plugins count-' . $unanswered_questions . '"><span class="plugin-count">' . $unanswered_questions . '</span></span>';
		}

		$course_post_type = tutor()->course_post_type;

		$pro_text = '';
		if ( $has_pro ) {
			$pro_text = ' ' . apply_filters( 'tutor_pro_flag', __( 'Pro', 'tutor' ) );
		}

		$enable_course_marketplace = (bool) tutor_utils()->get_option( 'enable_course_marketplace' );

		$welcome = Tutor_Setup::is_welcome_page_visited();

		$root_page = array( $this, 'tutor_course_list' );
		if ( false === $welcome && Input::has( 'page' ) && 'tutor' === Input::get( 'page' ) && Input::has( 'welcome' ) ) {
			$root_page = array( $this, 'welcome_page' );
		}

		// Qalam-owned WordPress admin menu mark. Never reuse the legacy Tutor glyph.
		$icon_base64_uri = plugins_url( 'assets/images/qalam-admin-menu.svg', TUTOR_FILE );
		$menu_position   = 2;

		add_menu_page(
			__( 'Qalam LMS', 'tutor' ) . $pro_text,
			__( 'Qalam LMS', 'tutor' ) . $pro_text,
			'manage_tutor_instructor',
			'tutor',
			$root_page,
			$icon_base64_uri,
			$menu_position
		);

		new WhatsNew();

		$admin_menu = apply_filters(
			'tutor_admin_menu',
			array(
				'group_one'   => array(
					'courses'        => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Courses', 'tutor' ),
						'menu_title'  => __( 'Courses', 'tutor' ),
						'capability'  => 'manage_tutor_instructor',
						'menu_slug'   => 'tutor',
						'callback'    => array( $this, 'tutor_course_list' ),
					),
					'content_bank'   => null,
					'course_builder' => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Course Builder', 'tutor' ),
						'menu_title'  => '<span class="tutor-create-course">Create Course</span>',
						'capability'  => 'manage_tutor_instructor',
						'menu_slug'   => 'create-course',
						'callback'    => array( new Course( false ), 'load_course_builder' ),
					),
					'categories'     => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Categories', 'tutor' ),
						'menu_title'  => __( 'Categories', 'tutor' ),
						'capability'  => 'manage_tutor',
						'menu_slug'   => 'edit-tags.php?taxonomy=course-category&post_type=' . $course_post_type,
					),
					'tags'           => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Tags', 'tutor' ),
						'menu_title'  => __( 'Tags', 'tutor' ),
						'capability'  => 'manage_tutor',
						'menu_slug'   => 'edit-tags.php?taxonomy=course-tag&post_type=' . $course_post_type,
					),
				),
				'group_two'   => array(
					'orders'            => null,
					'subscriptions'     => null,
					'coupons'           => null,
					'students'          => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Students', 'tutor' ),
						'menu_title'  => __( 'Students', 'tutor' ),
						'capability'  => 'manage_tutor',
						'menu_slug'   => Students_List::STUDENTS_LIST_PAGE,
						'callback'    => array( $this, 'tutor_students' ),

					),
					'announcements'     => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Announcements', 'tutor' ),
						'menu_title'  => __( 'Announcements', 'tutor' ),
						'capability'  => 'manage_tutor_instructor',
						'menu_slug'   => 'tutor_announcements',
						'callback'    => array( $this, 'tutor_announcements' ),

					),
					'assignments'       => null,
					'quiz_attempts'     => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Quiz Attempts', 'tutor' ),
						'menu_title'  => __( 'Quiz Attempts', 'tutor' ),
						'capability'  => 'manage_tutor_instructor',
						'menu_slug'   => Quiz_Attempts_List::QUIZ_ATTEMPT_PAGE,
						'callback'    => array( $this, 'quiz_attempts' ),
					),
					'q_and_a'           => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Q&A', 'tutor' ),
						'menu_title'  => __( 'Q&A ', 'tutor' ) . $unanswered_bubble,
						'capability'  => 'manage_tutor_instructor',
						'menu_slug'   => Question_Answers_List::QUESTION_ANSWER_PAGE,
						'callback'    => array( $this, 'question_answer' ),
					),
					'enrollments'       => null,
					'reports'           => null,
					'gradebook'         => null,
					'instructors'       => $enable_course_marketplace ? array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Instructors', 'tutor' ),
						'menu_title'  => __( 'Instructors', 'tutor' ),
						'capability'  => 'manage_tutor',
						'menu_slug'   => Instructors_List::INSTRUCTOR_LIST_PAGE,
						'callback'    => array( $this, 'tutor_instructors' ),
					) : null,
					'withdraw_requests' => $enable_course_marketplace ? array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Withdraw Requests', 'tutor' ),
						'menu_title'  => __( 'Withdraw Requests', 'tutor' ),
						'capability'  => 'manage_tutor',
						'menu_slug'   => Withdraw_Requests_List::WITHDRAW_REQUEST_LIST_PAGE,
						'callback'    => array( $this, 'withdraw_requests' ),
					) : null,
				),
				'group_three' => array(
					'google_classroom' => null,
					'zoom'             => null,
					'google_meet'      => null,
					'h5p'              => null,
					'addons'           => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Addons', 'tutor' ),
						'menu_title'  => __( 'Addons', 'tutor' ),
						'capability'  => 'manage_tutor',
						'menu_slug'   => 'tutor-addons',
						'callback'    => array( $this, 'enable_disable_addons' ),
					),
					'tools'            => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Tools', 'tutor' ),
						'menu_title'  => __( 'Tools', 'tutor' ),
						'capability'  => 'manage_tutor',
						'menu_slug'   => 'tutor-tools',
						'callback'    => array( new Tools_V2(), 'load_tools_page' ),
					),
					'settings'         => array(
						'parent_slug' => 'tutor',
						'page_title'  => __( 'Settings', 'tutor' ),
						'menu_title'  => __( 'Settings', 'tutor' ),
						'capability'  => 'manage_tutor',
						'menu_slug'   => 'tutor_settings',
						'callback'    => array( new Options_V2(), 'load_settings_page' ),
					),
					'license'          => null,
					'whats_new'        => null,
					'upgrade_to_pro'   => null,
				),
			)
		);

		foreach ( $admin_menu as $group => $menu ) {
			foreach ( $menu as $name => $args ) {
				if ( empty( $args ) ) {
					continue;
				}

				// Backward compatibility hook.
				if ( 'addons' === $name ) {
					do_action( 'tutor_admin_register' );
				}

				// Backward compatibility hook.
				do_action( "tutor_before_{$name}_admin_menu" );

				do_action( "tutor_before_{$name}_menu" );
				add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['callback'] ?? '', $args['position'] ?? null );

				// Backward compatibility hook.
				do_action( "tutor_after_{$name}_menu" );

				do_action( "tutor_after_{$name}_admin_menu" );
			}

			if ( 'group_three' !== $group ) {
				if ( 'group_two' === $group && ! current_user_can( 'manage_options' ) ) {
					continue;
				}

				add_submenu_page( 'tutor', '', '<span class="tutor-admin-menu-separator"></span>', 'manage_tutor_instructor', '#' );
			}
		}
	}

	/**
	 * Welcome page opt-out
	 *
	 * @since 3.0.0
	 */
	public function handle_do_not_show_feature_page() {
		tutor_utils()->check_nonce();

		if ( ! User::is_admin() ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		update_option( 'tutor-new-feature', '3.0.0' );
		$this->json_response( __( 'Success', 'tutor' ) );
	}

	/**
	 * Show Feature Promotion Page for Free User.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function feature_promotion_page() {
		include tutor()->path . 'views/pages/welcome.php';
	}

	/**
	 * Show students page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function tutor_students() {
		include tutor()->path . 'views/pages/students.php';
	}

	/**
	 * Show instructor page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function tutor_instructors() {
		include tutor()->path . 'views/pages/instructors.php';
	}

	/**
	 * Show announcements page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function tutor_announcements() {
		include tutor()->path . 'views/pages/announcements.php';
	}

	/**
	 * Show Q&A page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function question_answer() {
		include tutor()->path . 'views/pages/question_answer.php';
	}

	/**
	 * Show quiz attempts page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function quiz_attempts() {
		include tutor()->path . 'views/pages/quiz_attempts.php';
	}

	/**
	 * Show the withdraw requests table
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function withdraw_requests() {
		include tutor()->path . 'views/pages/withdraw_requests.php';
	}

	/**
	 * Enable or disable addons
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enable_disable_addons() {
		include tutor()->path . 'views/pages/enable_disable_addons.php';
	}

	/**
	 * Tutor tools page (OLD)
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function tutor_tools_old() {
		$tutor_admin_tools_page = Input::get( 'tutor_admin_tools_page' );
		if ( $tutor_admin_tools_page ) {
			include apply_filters( 'tutor_admin_tools_page', tutor()->path . "views/pages/{$tutor_admin_tools_page}.php", $tutor_admin_tools_page );
		} else {
			$pages = apply_filters(
				'tutor_tool_pages',
				array(
					'tutor_pages' => array( 'title' => __( 'Tutor Pages', 'tutor' ) ),
					'status'      => __( 'Status', 'tutor' ),
				)
			);

			$current_page   = 'tutor_pages';
			$requested_page = Input::get( 'sub_page' );
			if ( $requested_page ) {
				$current_page = $requested_page;
			}

			$current_page = str_replace( '/', '', $current_page );
			$current_page = str_replace( '.', '', $current_page );
			$current_page = str_replace( '\\', '', $current_page );
			$current_page = trim( $current_page );

			include tutor()->path . 'views/pages/tools.php';
		}
	}

	/**
	 * Show pro upgrade page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function tutor_get_pro() {
		include tutor()->path . 'views/pages/get-pro.php';
	}

	/**
	 * Parent menu active
	 *
	 * @since 1.0.0
	 *
	 * @param string $parent_file parent file.
	 *
	 * @return string
	 */
	public function parent_menu_active( $parent_file ) {
		$taxonomy = Input::get( 'taxonomy' );
		if ( CourseModel::COURSE_CATEGORY === $taxonomy || CourseModel::COURSE_TAG === $taxonomy ) {
			return 'tutor';
		}

		return $parent_file;
	}

	/**
	 * Sub-menu active
	 *
	 * @since 1.0.0
	 *
	 * @param string $submenu_file submenu file.
	 * @param string $parent_file parent file.
	 *
	 * @return string
	 */
	public function submenu_file_active( $submenu_file, $parent_file ) {
		$taxonomy         = Input::get( 'taxonomy' );
		$course_post_type = tutor()->course_post_type;

		if ( CourseModel::COURSE_CATEGORY === $taxonomy ) {
			return 'edit-tags.php?taxonomy=course-category&post_type=' . $course_post_type;
		}
		if ( CourseModel::COURSE_TAG === $taxonomy ) {
			return 'edit-tags.php?taxonomy=course-tag&post_type=' . $course_post_type;
		}

		return $submenu_file;
	}

	/**
	 * Filter posts for instructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function filter_posts_for_instructors() {
		if ( ! current_user_can( 'administrator' ) && current_user_can( tutor()->instructor_role ) ) {
			@remove_menu_page( 'edit-comments.php' ); // Comments.
			add_filter( 'posts_clauses_request', array( $this, 'posts_clauses_request' ) );
		}
	}

	/**
	 * Request for posts clauses
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $clauses clauses.
	 *
	 * @return mixed
	 */
	public function posts_clauses_request( $clauses ) {

		if ( ! is_admin() || ( ! Input::has( 'post_type' ) || Input::get( 'post_type' ) != tutor()->course_post_type ) || tutor_utils()->has_user_role( array( 'administrator', 'editor' ) ) ) {
			return $clauses;
		}

		// Need multi instructor check.
		global $wpdb;

		$user_id = get_current_user_id();

		$get_assigned_courses_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value from {$wpdb->usermeta} WHERE meta_key = '_tutor_instructor_course_id' AND user_id = %d", $user_id ) );
		$own_courses              = is_array( $get_assigned_courses_ids ) ? $get_assigned_courses_ids : array();

		$in_query_pre   = count( $own_courses ) ? implode( ',', $own_courses ) : null;
		$in_query_where = $in_query_pre ? " OR {$wpdb->posts}.ID IN({$in_query_pre})" : '';

		$course_post_type    = tutor()->course_post_type;
		$custom_author_query = "  AND ({$wpdb->posts}.post_type!='{$course_post_type}' OR {$wpdb->posts}.post_author = {$user_id}) {$in_query_where}";

		$clauses['where'] .= $custom_author_query;

		return $clauses;
	}

	/**
	 * Prevent unauthorised course/lesson edit page by direct URL
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function check_if_current_users_post() {
		if ( current_user_can( 'administrator' ) || ! current_user_can( tutor()->instructor_role ) ) {
			return;
		}

		if ( ! empty( Input::get( 'post' ) ) ) {
			$get_post_id      = Input::get( 'post', Input::TYPE_INT );
			$get_post         = get_post( $get_post_id );
			$tutor_post_types = array( tutor()->course_post_type, tutor()->lesson_post_type );

			$current_user = get_current_user_id();

			if ( in_array( $get_post->post_type, $tutor_post_types ) && $get_post->post_author != $current_user ) {
				global $wpdb;

				$get_assigned_courses_ids = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT user_id
					from {$wpdb->usermeta}
					WHERE user_id = %d AND meta_key = '_tutor_instructor_course_id' AND meta_value = %d ",
						$current_user,
						$get_post_id
					)
				);

				if ( ! $get_assigned_courses_ids ) {
					wp_die( esc_html__( 'Permission Denied', 'tutor' ) );
				}
			}
		}
	}

	/**
	 * Scan template files
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_path template file path.
	 *
	 * @return array
	 */
	public static function scan_template_files( $template_path = null ) {
		if ( ! $template_path ) {
			$template_path = tutor()->path . 'templates/';
		}

		$files  = @scandir($template_path); // @codingStandardsIgnoreLine.
		$result = array();

		if ( ! empty( $files ) ) {
			foreach ( $files as $key => $value ) {
				if ( ! in_array( $value, array( '.', '..', '.DS_Store' ), true ) ) {
					if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
						$sub_files = self::scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
						foreach ( $sub_files as $sub_file ) {
							$result[] = $value . DIRECTORY_SEPARATOR . $sub_file;
						}
					} else {
						$result[] = $value;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Get Template overridden files
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function template_overridden_files() {
		$template_files = self::scan_template_files();

		$override_files = array();
		foreach ( $template_files as $file ) {
			$file_path = null;
			if ( file_exists( trailingslashit( get_stylesheet_directory() ) . tutor()->template_path . $file ) ) {
				$file_path = $file;
			} elseif ( file_exists( trailingslashit( get_template_directory() ) . tutor()->template_path . $file ) ) {
				$file_path = $file;
			}

			if ( $file_path ) {
				$override_files[] = str_replace( WP_CONTENT_DIR . '/themes/', '', $file_path );
			}
		}

		return $override_files;
	}

	/**
	 * Plugin activation link
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions action list.
	 *
	 * @return array
	 */
	public function plugin_action_links( $actions ) {
		$has_pro = defined( 'QALAM_LMS_UNIFIED' ) ? true : tutor()->has_pro;

		$actions['settings'] = '<a href="admin.php?page=tutor_settings">' . __( 'Settings', 'tutor' ) . '</a>';

		return $actions;
	}

	/**
	 * Add plugin meta data in WP plugins list page
	 *
	 * @since 1.0.0
	 *
	 * @param array  $plugin_meta plugin meta data.
	 * @param string $plugin_file plugin file.
	 *
	 * @return array
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		// Qalam is a unified product; no donor commercial/docs metadata is exposed.
		return $plugin_meta;
	}

	/**
	 * Add footer text only on tutor pages
	 *
	 * @since 1.0.0
	 *
	 * @param string $footer_text footer text.
	 *
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		$current_screen = get_current_screen();
		if ( $current_screen && apply_filters( 'tutor_display_admin_footer_text', ( tutor_utils()->array_get( 'parent_base', $current_screen ) === 'tutor' ) ) ) {
			return 'Qalam LMS — بكل فخر ❤️ صنع في مصر — تطوير مؤسسة قلم للخدمات الإلكترونية';
		}
		return $footer_text;
	}

	/**
	 * Register course widget
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_course_widget() {
		register_widget( Course_Widget::class );
	}

	/**
	 * Tutor Course List
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function tutor_course_list() {
		include tutor()->path . 'views/pages/course-list.php';
	}

	/**
	 * Orders view page
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function orders_view() {
		$current_page = Input::get( 'page' );
		$action       = Input::get( 'action' );

		if ( OrderController::PAGE_SLUG === $current_page && 'edit' === $action ) {
			?>
				<div class="tutor-admin-wrap tutor-order-details-wrapper">
					<div id="tutor-order-details-root">
					</div>
				</div>
			<?php
			return;
		}

		include tutor()->path . 'views/pages/ecommerce/order-list.php';
	}

	/**
	 * Coupons view page
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function coupons_view() {
		$action = Input::get( 'action' );
		if ( in_array( $action, array( 'add_new', 'edit' ) ) ) {
			?>
				<div class="tutor-admin-wrap">
					<div id="tutor-coupon-root">
					</div>
				</div>
			<?php
			return;
		}
		include tutor()->path . 'views/pages/ecommerce/coupon-list.php';
	}

	/**
	 * Show welcome page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function welcome_page() {
		Tutor_Setup::mark_as_visited();
		include tutor()->path . 'views/pages/welcome.php';
		exit;
	}

	/**
	 * Add toolbar items
	 *
	 * @since 1.4.6
	 *
	 * @param \WP_Admin_Bar $admin_bar admin bar object.
	 *
	 * @return mixed
	 */
	public function add_toolbar_items( \WP_Admin_Bar $admin_bar ) {
		global $post;

		$course_id        = Input::get( 'post', 0, Input::TYPE_INT );
		$course_post_type = tutor()->course_post_type;

		if ( $admin_bar->get_node( 'new-courses' ) ) {
			$args                = $admin_bar->get_node( 'new-courses' );
			$args->href          = '#';
			$args->meta['class'] = 'tutor-create-new-course';
			$admin_bar->add_node( $args );
		}

		if ( ! tutor_utils()->can_user_edit_course( get_current_user_id(), $course_id ) ) {
			return $admin_bar;
		}

		if (
				( is_admin() && $post && $course_id && $post->post_type === $course_post_type ) ||
				( ! is_admin() && is_single() && $post && $course_post_type === $post->post_type )
			) {

			$admin_bar->add_menu(
				array(
					'id'    => 'edit',
					'title' => __( 'Edit with Course Builder', 'tutor' ),
					'href'  => tutor_utils()->course_edit_link( $post->ID ),
					'meta'  => array(
						'title'  => __( 'Edit with Course Builder', 'tutor' ),
						'target' => '_blank',
					),
				)
			);
		}

		return $admin_bar;
	}

	/**
	 * Redirect to welcome page if user is not logged in.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function redirect_to_welcome_page() {
		// Skip for fresh installs, during the setup wizard, or during AJAX.
		if ( ! get_option( 'tutor_wizard' ) || 'tutor-setup' === Input::get( 'page' ) || wp_doing_ajax() ) {
			return;
		}

		$tutor_new_feature = get_option( 'tutor-new-feature' );
		if ( version_compare( $tutor_new_feature, '4.0.0-rc.2', '>=' ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=tutor&welcome=1' ) );
		update_option( 'tutor-new-feature', TUTOR_VERSION );
	}
}
