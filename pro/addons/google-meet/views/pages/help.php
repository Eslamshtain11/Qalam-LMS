<?php
/**
 * Google meet FAQ page
 *
 * @since v2.1.0
 *
 * @package TutorPro\GoogleMeet\Views
 */

?>
<div class="tutor-google-meet-help-content">
	<div class="tutor-admin-container tutor-admin-container-sm">
		<div class="">
			<?php if ( qalam_220_is_admin_surface_context( 'google_meet' ) ) : ?>
				<div class="tutor-zoom-page-title tutor-mb-16">
					<div class="tutor-fs-4 tutor-fw-medium tutor-color-black"><?php esc_html_e( 'FAQ', 'tutor-pro' ); ?></div>
				</div>
			<?php endif; ?>

			<div class="tutor-accordion tutor-accordion-google-meet-help tutor-mt-24">
				<div class="tutor-accordion-item">
					<div class="tutor-accordion-item-header tutor-card tutor-mb-16">
						<span class="tutor-iconic-btn tutor-iconic-btn-secondary"><i class="tutor-icon-angle-down"></i></span>
						<span class="tutor-fs-6 tutor-fw-medium tutor-color-black tutor-ml-24">
							<?php echo esc_html_e( 'How do I connect Google Meet with my LMS Website?', 'tutor-pro' ); ?>
						</span>
					</div>

					<div class="tutor-accordion-item-body" style="display: none;">
						<div class="tutor-accordion-item-body-content">
							<div class="tutor-fs-7 tutor-color-secondary">
								<?php
								$dashboard_url = 'https://console.cloud.google.com/apis/dashboard';
								printf(
									wp_kses_post( 'افتح <a class="tutor-text-brand" href="%s" target="_blank" rel="noopener noreferrer">Google Cloud Console</a> وأنشئ بيانات OAuth، وبعدها انسخ رابط إعادة التوجيه من تبويب إعداد الربط وحطه في Authorized Redirect URI داخل إعدادات Google.' ),
									esc_url( $dashboard_url )
								);
								?>
							</div>
						</div>
					</div>
				</div>
				<div class="tutor-accordion-item">
					<div class="tutor-accordion-item-header tutor-card tutor-p-16 tutor-mb-16">
						<span class="tutor-iconic-btn tutor-iconic-btn-secondary"><i class="tutor-icon-angle-down"></i></span>
						<span class="tutor-fs-6 tutor-fw-medium tutor-color-black tutor-ml-24">
							<?php esc_html_e( 'How do I create a Live Lesson on Qalam LMS?', 'tutor-pro' ); ?>
						</span>
					</div>

					<div class="tutor-accordion-item-body" style="display: none;">
						<div class="tutor-accordion-item-body-content">
							<div class="tutor-fs-7 tutor-color-secondary">
								<?php
								$live_lesson_content = _x( 'You can schedule a live lesson directly from the Course Builder. Scroll to the new Google Meet section to create a course-wide meeting, or navigate to a specific topic and select the \'Google Meet Live Lesson\' option to attach a meeting directly to that curriculum item.', 'google meet live lesson FAQ', 'tutor-pro' );
								echo wp_kses_post( html_entity_decode( $live_lesson_content ) );
								?>
							</div>
						</div>
					</div>
				</div>
				<div class="tutor-accordion-item">
					<div class="tutor-accordion-item-header tutor-card tutor-p-16 tutor-mb-16">
						<span class="tutor-iconic-btn tutor-iconic-btn-secondary"><i class="tutor-icon-angle-down"></i></span>
						<span class="tutor-fs-6 tutor-fw-medium tutor-color-black tutor-ml-24">
							<?php esc_html_e( 'How do I notify students about live lessons?', 'tutor-pro' ); ?>
						</span>
					</div>

					<div class="tutor-accordion-item-body" style="display: none;">
						<div class="tutor-accordion-item-body-content">
							<div class="tutor-fs-7 tutor-color-secondary">
								<?php
								esc_html_e( 'You can notify students about live lessons using Email Notifications of Qalam LMS and from the Google Meet settings on Qalam LMS frontend and backend.', 'tutor-pro' );
								?>
							</div>
						</div>
					</div>
				</div>
				<div class="tutor-accordion-item">
					<div class="tutor-accordion-item-header tutor-card tutor-p-16 tutor-mb-16">
						<span class="tutor-iconic-btn tutor-iconic-btn-secondary"><i class="tutor-icon-angle-down"></i></span>
						<span class="tutor-fs-6 tutor-fw-medium tutor-color-black tutor-ml-24">
							<?php esc_html_e( 'Do I need a Google account to integrate Google Meet with Qalam LMS?', 'tutor-pro' ); ?>
						</span>
					</div>

					<div class="tutor-accordion-item-body" style="display: none;">
						<div class="tutor-accordion-item-body-content">
							<div class="tutor-fs-7 tutor-color-secondary">
								<?php
								esc_html_e(
									'Yes, an active Google Account is required to configure the API credentials and to act as the primary host for the scheduled live meetings.',
									'tutor-pro'
								);
								?>
							</div>
						</div>
					</div>
				</div>
				<div class="tutor-accordion-item">
					<div class="tutor-accordion-item-header tutor-card tutor-p-16 tutor-mb-16">
						<span class="tutor-iconic-btn tutor-iconic-btn-secondary"><i class="tutor-icon-angle-down"></i></span>
						<span class="tutor-fs-6 tutor-fw-medium tutor-color-black tutor-ml-24">
							<?php esc_html_e( 'What Equipment Do I Need To Hold a Live Class?', 'tutor-pro' ); ?>
						</span>
					</div>

					<div class="tutor-accordion-item-body" style="display: none;">
						<div class="tutor-accordion-item-body-content">
							<div class="tutor-fs-7 tutor-color-secondary">
								<?php
								esc_html_e( 'You will need a Microphone, a PC running Windows or Mac OS, and preferably a Webcam to effectively hold a live class.', 'tutor-pro' );
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
