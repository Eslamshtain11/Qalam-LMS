<?php
/**
 * Help page.
 *
 * @package TutorPro\Addons
 * @subpackage Zoom\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="tutor-admin-wrap">
	<div class="tutor-admin-body">
		<div class="tutor-admin-container tutor-admin-container-sm">
			<div class="tutor-zoom-settings">
				<?php if ( qalam_220_is_admin_surface_context( 'zoom' ) ) : ?>
					<div class="tutor-zoom-page-title tutor-mb-16">
						<div class="tutor-fs-4 tutor-fw-medium tutor-color-black"><?php esc_html_e( 'FAQ', 'tutor-pro' ); ?></div>
					</div>
				<?php endif; ?>

				<div class="tutor-zoom-accordion-item tutor-card tutor-p-16 tutor-mb-16">
					<div class="tutor-zoom-accordion-panel">
						<span class="tutor-zoom-accordion-panel-handler tutor-d-flex tutor-align-center tutor-cursor-pointer">
							<span class="tutor-iconic-btn tutor-iconic-btn-secondary"><i class="tutor-icon-angle-down"></i></span>
							<span class="tutor-accordion-panel-handler-label tutor-fs-6 tutor-fw-medium tutor-color-black tutor-ml-24">إزاي أربط Zoom بمنصة قلم؟</span>
						</span>
					</div>

					<div class="tutor-zoom-accordion-body tutor-pt-16" style="display: none;">
						<div class="tutor-fs-7 tutor-color-secondary">
							افتح Zoom App Marketplace وأنشئ تطبيق Server-to-Server OAuth، وبعدها انسخ معرّف الحساب ومعرّف العميل والرمز السري وحطهم في Qalam LMS ← Zoom ← إعداد الربط.
						</div>
					</div>
				</div>

				<div class="tutor-zoom-accordion-item tutor-card tutor-p-16 tutor-mb-16">
					<div class="tutor-zoom-accordion-panel">
						<span class="tutor-zoom-accordion-panel-handler tutor-d-flex tutor-align-center tutor-cursor-pointer">
							<span class="tutor-iconic-btn tutor-iconic-btn-secondary"><i class="tutor-icon-angle-down"></i></span>
							<span class="tutor-accordion-panel-handler-label tutor-fs-6 tutor-fw-medium tutor-color-black tutor-ml-24">إزاي أنشئ حصة مباشرة على Qalam LMS؟</span>
						</span>
					</div>

					<div class="tutor-zoom-accordion-body tutor-pt-16" style="display: none;">
						<div class="tutor-fs-7 tutor-color-secondary">
							تقدر تنشئ حصة Zoom من منشئ الدورات. ممكن تعمل اجتماع للدورة كلها أو تدخل على قسم معين وتضيف حصة Zoom مباشرة للجزء ده من المنهج.
						</div>
					</div>
				</div>

				<div class="tutor-zoom-accordion-item tutor-card tutor-p-16 tutor-mb-16">
					<div class="tutor-zoom-accordion-panel">
						<span class="tutor-zoom-accordion-panel-handler tutor-d-flex tutor-align-center tutor-cursor-pointer">
							<span class="tutor-iconic-btn tutor-iconic-btn-secondary"><i class="tutor-icon-angle-down"></i></span>
							<span class="tutor-accordion-panel-handler-label tutor-fs-6 tutor-fw-medium tutor-color-black tutor-ml-24">إزاي أبعت للطلاب تنبيه عن الحصص المباشرة؟</span>
						</span>
					</div>

					<div class="tutor-zoom-accordion-body tutor-pt-16" style="display: none;">
						<div class="tutor-fs-7 tutor-color-secondary">
							تقدر تبعت للطلاب تنبيهات عن الحصص المباشرة باستخدام إشعارات البريد والإعلانات داخل قلم.
						</div>
					</div>
				</div>

				<div class="tutor-zoom-accordion-item tutor-card tutor-p-16 tutor-mb-16">
					<div class="tutor-zoom-accordion-panel">
						<span class="tutor-zoom-accordion-panel-handler tutor-d-flex tutor-align-center tutor-cursor-pointer">
							<span class="tutor-iconic-btn tutor-iconic-btn-secondary"><i class="tutor-icon-angle-down"></i></span>
							<span class="tutor-accordion-panel-handler-label tutor-fs-6 tutor-fw-medium tutor-color-black tutor-ml-24">هل Zoom مجاني؟</span>
						</span>
					</div>

					<div class="tutor-zoom-accordion-body tutor-pt-16" style="display: none;">
						<div class="tutor-fs-7 tutor-color-secondary">
							Zoom عنده خطة مجانية مناسبة للاستخدام المحدود، ولو احتياجاتك أكبر تقدر تستخدم خطة مدفوعة حسب استخدامك.
						</div>
					</div>
				</div>

				<div class="tutor-zoom-accordion-item tutor-card tutor-p-16 tutor-mb-16">
					<div class="tutor-zoom-accordion-panel">
						<span class="tutor-zoom-accordion-panel-handler tutor-d-flex tutor-align-center tutor-cursor-pointer">
							<span class="tutor-iconic-btn tutor-iconic-btn-secondary"><i class="tutor-icon-angle-down"></i></span>
							<span class="tutor-accordion-panel-handler-label tutor-fs-6 tutor-fw-medium tutor-color-black tutor-ml-24">إيه الأجهزة المطلوبة علشان أعمل حصة مباشرة؟</span>
						</span>
					</div>

					<div class="tutor-zoom-accordion-body tutor-pt-16" style="display: none;">
						<div class="tutor-fs-7 tutor-color-secondary">
							هتحتاج ميكروفون وكمبيوتر Windows أو Mac، ويفضل كاميرا ويب علشان تقدم الحصة المباشرة بشكل كويس.
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
