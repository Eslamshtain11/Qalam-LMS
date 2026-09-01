<?php
/** Qalam local release page. */
use TUTOR\WhatsNew;
?>
<div class="wrap qalam-release-page" dir="rtl">
  <div class="qalam-release-hero">
    <img src="<?php echo esc_url( tutor()->url . 'assets/images/qalam-logo.svg' ); ?>" alt="Qalam LMS">
    <div><span class="qalam-release-kicker">Qalam LMS</span><h1>ما الجديد في قلم؟</h1>
    <p>أنت تستخدم إصدار <strong><?php echo esc_html( QALAM_LMS_UI_VERSION ); ?></strong>. هذه الصفحة تعرض تغييرات قلم المحلية فقط، ولا تتصل بتحديثات Tutor الخارجية.</p></div>
  </div>
  <div class="qalam-release-grid">
    <section class="qalam-release-card"><h2>تحسينات هذا الإصدار</h2><ul>
      <li>إصلاح تحميل الميزات المتقدمة والملحقات عند تحميل Pro قبل Core.</li>
      <li>تعريب موسع لصفحات المدرس والإعدادات والإعلانات.</li>
      <li>هوية مرئية خاصة بقلم وألوان بنفسجية موحدة.</li>
      <li>تحديث تصميم الأزرار والبطاقات والحقول مع الحفاظ على كل الوظائف.</li>
    </ul></section>
    <section class="qalam-release-card"><h2>ملاحظة التكاملات</h2><p>Zoom وGoogle Meet وباقي التكاملات موجودة داخل الحزمة، لكن الخدمات الخارجية تحتاج بيانات اعتماد صحيحة من حساباتك عند الاستخدام.</p></section>
  </div>
</div>
