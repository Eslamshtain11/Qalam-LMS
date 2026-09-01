<?php
/**
 * Banner part for google meet set API
 *
 * @since v2.1.0
 *
 * @package TutorPro\GoogleMeet\Views
 */

use TutorPro\GoogleMeet\GoogleMeet;

$plugin_data = GoogleMeet::meta_data();
?>
<div class="tutor-google-meet-api-banner tutor-card <?php echo qalam_220_is_admin_surface_context( 'google_meet' ) ? 'tutor-card-no-border' : ''; ?>">
	<div class="tutor-row tutor-align-center tutor-gx-xl-5">
		<div class="tutor-col-md-7 tutor-mb-32 tutor-mb-lg-0">
			<div class="tutor-p-lg-48 tutor-p-28">
				<div class="tutor-fs-3 tutor-mb-0 tutor-color-black">
					<?php esc_html_e( 'Setup your Google Meet Integration', 'tutor-pro' ); ?>
				</div>
				<div class="tutor-mt-12 tutor-fs-7 tutor-color-muted">
					<?php
					printf(
						wp_kses_post( 'اربط Google Meet بمنصة قلم عن طريق إنشاء بيانات OAuth من <a href="%s" target="_blank" rel="noopener noreferrer">Google Cloud Console</a>، وبعدها انسخ رابط إعادة التوجيه الموجود بالأسفل وحطه في Authorized Redirect URI.' ),
						esc_url( 'https://console.cloud.google.com/apis/dashboard' )
					);
					?>
				</div>
				<div class="tutor-clipboard-input-field tutor-mt-28">
					<button class="tutor-btn tutor-btn-outline-primary tutor-btn-sm tutor-copy" data-tutor-clipboard-copy-target="tutor-google-meet-redirect-url">
						<?php esc_html_e( 'Copy', 'tutor-pro' ); ?>
					</button>
					<input type="text" class="tutor-form-control" placeholder="" value="<?php echo esc_url( ( function_exists( 'qalam_220_is_admin_surface_context' ) && qalam_220_is_admin_surface_context( 'google_meet' ) && ! is_admin() && function_exists( 'qalam_220_google_meet_callback_url' ) ) ? qalam_220_google_meet_callback_url() : ( is_admin() ? admin_url() . 'admin.php?page=google-meet&tab=set-api' : tutor_utils()->tutor_dashboard_url( 'google-meet/set-api' ) ) ); ?>" id="tutor-google-meet-redirect-url" />
				</div>
			</div>
		</div>

		<div class="tutor-col-md-5 tutor-text-right">
			<img class="tutor-img-responsive" src="<?php echo esc_url( trailingslashit( $plugin_data['assets'] . 'images' ) . 'setup-google-meet-illustration.svg' ); ?>" alt="google-meet config">
		</div>

	</div>
</div>
