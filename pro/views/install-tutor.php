<?php
/**
 * Installer notice
 *
 * @package TutorPro\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'تثبيت / تفعيل Qalam LMS Core', 'tutor-pro' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php
	$tutor_file = defined( 'TUTOR_FILE' ) ? TUTOR_FILE : WP_PLUGIN_DIR . '/qalam-lms/qalam-lms.php';
	if ( file_exists( $tutor_file ) && ! defined( 'QALAM_LMS_PLUGIN_BASENAME' ) && is_plugin_active( QALAM_LMS_PLUGIN_BASENAME ) ) {
		?>
		<div class="tutor-install-notice-wrap notice-warning notice" style="background: #ffffff; padding: 30px 20px; font-size: 20px;">
			<?php
			printf(
				// translators: text.
				esc_html__(
					'You must have %1$sQalam LMS%2$s Free version installed and activated on this website in order to use Qalam LMS. You %3$s can activate Qalam LMS%4$s.',
					'tutor-pro'
				),
				'<a href="https://wordpress.org/plugins/tutor/" target="_blank">',
				'</a>',
				'<a href="' . esc_url( add_query_arg( array( 'action' => 'activate_tutor_free' ), admin_url() ) ) . '">',
				'</a>'
			);
			?>
		</div>
		<?php
	} elseif ( ! file_exists( $tutor_file ) ) {
		?>
		<div class="tutor-install-notice-wrap notice-warning notice" style="background: #ffffff; padding: 30px 20px; font-size: 20px;">
			<?php
			printf(
				// translators: text.
				esc_html__(
					'You must have %1$sQalam LMS%2$s Free version installed and activated on this website in order to use Qalam LMS. You can %3$sInstall Qalam LMS Now%4$s',
					'tutor-pro'
				),
				'<a href="https://wordpress.org/plugins/tutor/" target="_blank">',
				'</a>',
				'<a class="install-tutor-btn" data-slug="tutor" href="' . esc_url( add_query_arg( array( 'action' => 'install_tutor_free' ), admin_url() ) ) . '">',
				'</a>'
			);
			?>
		</div>

		<div id="tutor_install_msg"></div>
		<?php
	}
	?>
</div>
