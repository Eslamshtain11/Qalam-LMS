<?php
/**
 * Qalam visible product layer for the direct Tutor fork.
 * Internal Tutor hooks/classes/slugs intentionally remain for compatibility.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'QALAM_LMS_UI_VERSION' ) ) { define( 'QALAM_LMS_UI_VERSION', '0.31.0' ); }


/**
 * True only for WordPress-admin screens that are part of the Qalam/Tutor product UI.
 * Keep Qalam's translation/design runtime completely off native WordPress maintenance
 * screens (users, plugins, themes, tools, etc.). This is especially important on
 * mobile where MutationObserver/select styling can interfere with native controls.
 */
function qalam_is_product_admin_surface(): bool {
    if ( ! is_admin() ) { return false; }

    $page = isset( $_GET['page'] ) ? sanitize_key( (string) wp_unslash( $_GET['page'] ) ) : '';
    if ( $page ) {
        if ( preg_match( '/^(?:qalam[-_]|tutor(?:[-_]|$))/', $page ) ) { return true; }
        if ( in_array( $page, array( 'create-course', 'google-meet', 'google-classroom' ), true ) ) { return true; }
    }

    if ( function_exists( 'get_current_screen' ) ) {
        $screen = get_current_screen();
        if ( $screen ) {
            $haystack = strtolower( implode( ' ', array_filter( array(
                (string) ( $screen->id ?? '' ),
                (string) ( $screen->base ?? '' ),
                (string) ( $screen->post_type ?? '' ),
            ) ) ) );
            if ( preg_match( '/(?:^|[^a-z])(qalam|tutor)(?:[^a-z]|$)/', $haystack ) ) { return true; }
        }
    }

    return false;
}

function qalam_lms_dictionary(): array {
    static $map = null;
    if ( null !== $map ) { return $map; }
    $map = array(
        'Qalam LMS' => 'Qalam LMS',
        'Qalam LMS' => 'Qalam LMS',
        'Dashboard' => 'لوحة التحكم',
        'Home' => 'الرئيسية',
        'Courses' => 'الدورات',
        'Course' => 'الدورة',
        'My Courses' => 'دوراتي',
        'Create Course' => 'إنشاء دورة',
        'Add New Course' => 'إضافة دورة جديدة',
        'Course Builder' => 'منشئ الدورة',
        'Tutor Course Builder' => 'منشئ دورات قلم',
        'Edit with Course Builder' => 'تعديل بمنشئ الدورة',
        'Curriculum' => 'المنهج',
        'Course Info' => 'بيانات الدورة',
        'Course Details' => 'تفاصيل الدورة',
        'Course Settings' => 'إعدادات الدورة',
        'Course Type' => 'نوع الدورة',
        'Course Price' => 'سعر الدورة',
        'Course Category' => 'تصنيف الدورة',
        'Course Thumbnail' => 'صورة الدورة',
        'Course Duration' => 'مدة الدورة',
        'Course Marketplace' => 'سوق الدورات',
        'Marketplace' => 'السوق',
        'Enable Course Marketplace' => 'تفعيل سوق الدورات',
        'Public Course' => 'دورة عامة',
        'Maximum Students' => 'الحد الأقصى للطلاب',
        'Difficulty Level' => 'مستوى الصعوبة',
        'Beginner' => 'مبتدئ', 'Intermediate' => 'متوسط', 'Expert' => 'متقدم', 'All Levels' => 'كل المستويات',
        'Lessons' => 'الدروس', 'Lesson' => 'الدرس', 'Add Lesson' => 'إضافة درس', 'Edit Lesson' => 'تعديل الدرس',
        'Students' => 'الطلاب', 'Student' => 'الطالب', 'Student List' => 'قائمة الطلاب', 'Total Students' => 'إجمالي الطلاب',
        'Instructors' => 'المعلمون', 'Instructor' => 'المعلم', 'Instructor Registration' => 'تسجيل المعلمين',
        'Assignments' => 'الواجبات', 'Assignment' => 'الواجب', 'Add Assignment' => 'إضافة واجب',
        'Assignment Submissions' => 'تسليمات الواجبات', 'Submission' => 'التسليم', 'Submitted' => 'تم التسليم',
        'Quizzes' => 'الاختبارات', 'Quiz' => 'الاختبار', 'Add Quiz' => 'إضافة اختبار', 'Edit Quiz' => 'تعديل الاختبار',
        'Quiz Builder' => 'منشئ الاختبار', 'Quiz Attempts' => 'محاولات الاختبارات', 'Quiz Attempt' => 'محاولة اختبار',
        'Questions' => 'الأسئلة', 'Question' => 'السؤال', 'Question Bank' => 'بنك الأسئلة', 'Content Bank' => 'بنك المحتوى',
        'Add Question' => 'إضافة سؤال', 'Question Type' => 'نوع السؤال', 'Answer' => 'الإجابة', 'Correct Answer' => 'الإجابة الصحيحة',
        'Single Choice' => 'اختيار واحد', 'Multiple Choice' => 'اختيارات متعددة', 'True/False' => 'صح/خطأ',
        'True' => 'صح', 'False' => 'خطأ', 'true' => 'صح', 'false' => 'خطأ',
        'Start Quiz' => 'ابدأ الاختبار', 'Skip Question' => 'تخطي السؤال', 'Submit Quiz' => 'إنهاء الاختبار',
        'Previous' => 'السابق', 'Next' => 'التالي',
        'Fill in the Blanks' => 'أكمل الفراغات', 'Short Answer' => 'إجابة قصيرة', 'Open Ended/Essay' => 'مقالي',
        'Matching' => 'توصيل', 'Ordering' => 'ترتيب', 'Points' => 'الدرجات', 'Pass Mark' => 'درجة النجاح',
        'Time Limit' => 'الوقت المحدد', 'Attempts Allowed' => 'عدد المحاولات المسموحة', 'Randomize' => 'ترتيب عشوائي',
        'Results' => 'النتائج', 'Result' => 'النتيجة', 'Passed' => 'ناجح', 'Failed' => 'غير ناجح', 'Pending' => 'قيد الانتظار',
        'Completed' => 'مكتمل', 'In Progress' => 'قيد التقدم', 'Not Started' => 'لم يبدأ', 'Enrolled' => 'مسجل',
        'Gradebook' => 'سجل الدرجات', 'Grade' => 'الدرجة', 'Grades' => 'الدرجات', 'Evaluate' => 'تقييم', 'Evaluation' => 'التقييم',
        'Instructor Note' => 'ملاحظة المعلم',
        'Certificates' => 'الشهادات', 'Certificate' => 'الشهادة', 'Certificate Template' => 'قالب الشهادة',
        'Generate Certificate' => 'إنشاء الشهادة', 'Download Certificate' => 'تحميل الشهادة', 'View Certificate' => 'عرض الشهادة',
        'Reports' => 'التقارير', 'Report' => 'التقرير', 'Analytics' => 'التحليلات',
        'Notifications' => 'الإشعارات', 'Notification' => 'الإشعار', 'Email' => 'البريد',
        'Withdrawals' => 'السحوبات', 'Withdraw Requests' => 'طلبات السحب', 'Withdraw' => 'سحب', 'Earnings' => 'الأرباح',
        'Total Earnings' => 'إجمالي الأرباح', 'Balance' => 'الرصيد', 'Commission' => 'العمولة',
        'Settings' => 'الإعدادات', 'Tools' => 'الأدوات', 'Addons' => 'الإضافات', 'Add-ons' => 'الإضافات',
        'Categories' => 'التصنيفات', 'Category' => 'التصنيف', 'Tags' => 'الوسوم', 'Tag' => 'الوسم',
        'Announcements' => 'الإعلانات', 'Announcement' => 'الإعلان', 'Discussions' => 'المناقشات', 'Q&A' => 'الأسئلة والأجوبة',
        'Reviews' => 'التقييمات', 'Review' => 'التقييم', 'Profile' => 'الملف الشخصي', 'Account' => 'الحساب', 'Logout' => 'تسجيل الخروج',
        'Calendar' => 'التقويم', 'Live Class' => 'حصة مباشرة', 'Live Classes' => 'الحصص المباشرة',
        'Zoom Integration' => 'تكامل Zoom', 'Google Meet' => 'Google Meet', 'Google Classroom' => 'Google Classroom',
        'Meeting' => 'الاجتماع', 'Meetings' => 'الاجتماعات', 'Create Meeting' => 'إنشاء اجتماع', 'Join Meeting' => 'الانضمام للاجتماع',
        'Host' => 'المضيف', 'Password' => 'كلمة المرور', 'API Key' => 'مفتاح API', 'Client ID' => 'معرّف العميل',
        'Client Secret' => 'الرمز السري للعميل', 'Redirect URI' => 'رابط إعادة التوجيه', 'Connect' => 'اتصال', 'Disconnect' => 'قطع الاتصال',
        'Authorize' => 'تفويض', 'Connected' => 'متصل', 'Not Connected' => 'غير متصل',
        'Multi Instructors' => 'تعدد المعلمين', 'Multi-Instructors' => 'تعدد المعلمين', 'Course Bundle' => 'حزم الدورات',
        'Subscriptions' => 'الاشتراكات', 'Subscription' => 'الاشتراك', 'Gift Course' => 'إهداء دورة',
        'Course Preview' => 'معاينة الدورة', 'Course Attachments' => 'مرفقات الدورة', 'Content Drip' => 'التدرج في المحتوى',
        'Prerequisites' => 'المتطلبات السابقة', 'Enrollments' => 'التسجيلات', 'Social Login' => 'تسجيل الدخول الاجتماعي',
        'Authentication' => 'المصادقة', 'Import/Export' => 'استيراد/تصدير', 'Quiz Import/Export' => 'استيراد/تصدير الاختبارات',
        'Add New' => 'إضافة جديد', 'Add' => 'إضافة', 'Save' => 'حفظ', 'Save Changes' => 'حفظ التغييرات', 'Update' => 'تحديث',
        'Publish' => 'نشر', 'Draft' => 'مسودة', 'Edit' => 'تعديل', 'Delete' => 'حذف', 'Remove' => 'إزالة', 'Cancel' => 'إلغاء',
        'Continue' => 'متابعة', 'Next' => 'التالي', 'Previous' => 'السابق', 'Back' => 'رجوع', 'Finish' => 'إنهاء', 'Close' => 'إغلاق',
        'Search' => 'بحث', 'Filter' => 'تصفية', 'Apply' => 'تطبيق', 'Reset' => 'إعادة ضبط', 'Status' => 'الحالة',
        'Title' => 'العنوان', 'Description' => 'الوصف', 'Name' => 'الاسم', 'Date' => 'التاريخ', 'Time' => 'الوقت', 'Duration' => 'المدة',
        'Start Date' => 'تاريخ البدء', 'End Date' => 'تاريخ الانتهاء', 'Price' => 'السعر', 'Regular Price' => 'السعر الأساسي', 'Sale Price' => 'سعر التخفيض',
        'Thumbnail' => 'الصورة المصغرة', 'Featured Image' => 'الصورة البارزة', 'Video' => 'الفيديو', 'Upload' => 'رفع', 'Select' => 'اختيار',
        'Topic' => 'قسم', 'Topics' => 'الأقسام', 'Add Topic' => 'إضافة قسم', 'Requirements' => 'المتطلبات', 'Target Audience' => 'الفئة المستهدفة',
        'Materials Included' => 'المواد المرفقة', 'Intro Video' => 'الفيديو التعريفي',
        'Enable' => 'تفعيل', 'Disable' => 'تعطيل', 'Enabled' => 'مفعّل', 'Disabled' => 'معطّل', 'Active' => 'نشط', 'Inactive' => 'غير نشط',
        'Success' => 'تم بنجاح', 'Error' => 'خطأ', 'Warning' => 'تنبيه', 'Actions' => 'الإجراءات', 'Action' => 'الإجراء',
        'All' => 'الكل', 'Yes' => 'نعم', 'No' => 'لا', 'None' => 'لا شيء', 'View' => 'عرض', 'Details' => 'التفاصيل',
        'Create Bundle' => 'إنشاء حزمة', 'Bundle' => 'الحزمة', 'Bundles' => 'الحزم',
        'Course Prerequisites' => 'المتطلبات السابقة للدورة',
        'Search courses for prerequisites' => 'ابحث عن الدورات لإضافتها كمتطلبات سابقة',
        'No course selected' => 'لم يتم اختيار دورة',
        'Select a course to add as a prerequisite.' => 'اختر دورة لإضافتها كمتطلب سابق.',
        'Create a Zoom Meeting' => 'إنشاء اجتماع Zoom',
        'Attachments' => 'المرفقات',
        'Upload Attachments' => 'تحميل المرفقات',
        'Additional' => 'إضافي',
        'Basics' => 'الأساسيات',
        'Course Basics' => 'أساسيات الدورة',
        'Course Curriculum' => 'منهج الدورة',
        'Course Additional' => 'إعدادات إضافية',
        'Add Topic' => 'إضافة موضوع',
        'Add a Topic' => 'إضافة موضوع',
        'Add a topic' => 'إضافة موضوع',
        'Start building your course!' => 'ابدأ في بناء دورتك التدريبية!',
        'Add topics, lessons, and quizzes to get started.' => 'أضف الموضوعات والدروس والاختبارات للبدء.',
        'Add topics, lessons and quizzes to get started.' => 'أضف الموضوعات والدروس والاختبارات للبدء.',
        'Prerequisites' => 'المتطلبات السابقة',
        'Course Prerequisite' => 'المتطلب السابق للدورة',
        'Zoom' => 'Zoom',
        'Zoom Meeting' => 'اجتماع Zoom',
        'Zoom Meetings' => 'اجتماعات Zoom',
        'Create Zoom Meeting' => 'إنشاء اجتماع Zoom',
        'Create Meeting' => 'إنشاء اجتماع',
        'Meeting Title' => 'عنوان الاجتماع',
        'Meeting Time' => 'وقت الاجتماع',
        'Meeting Duration' => 'مدة الاجتماع',
        'Start Meeting' => 'بدء الاجتماع',
        'Join Zoom Meeting' => 'الانضمام إلى اجتماع Zoom',
        'Host Email' => 'بريد المضيف',
        'Google Meet Integration' => 'تكامل Google Meet',
        'Create Google Meet' => 'إنشاء اجتماع Google Meet',
        'Create a Google Meet' => 'إنشاء اجتماع Google Meet',
        'Google Meet Meeting' => 'اجتماع Google Meet',
        'Certificate Builder' => 'منشئ الشهادات',
        'Certificate Templates' => 'قوالب الشهادات',
        'Certificate Name' => 'اسم الشهادة',
        'Assignments Settings' => 'إعدادات الواجبات',
        'Assignment Name' => 'اسم الواجب',
        'Assignment Description' => 'وصف الواجب',
        'Upload Attachment' => 'رفع مرفق',
        'Maximum File Size' => 'الحد الأقصى لحجم الملف',
        'Maximum File Uploads' => 'الحد الأقصى لعدد الملفات',
        'Quiz Info' => 'بيانات الاختبار',
        'Quiz Questions' => 'أسئلة الاختبار',
        'Quiz Settings' => 'إعدادات الاختبار',
        'Question Settings' => 'إعدادات السؤال',
        'Question Title' => 'عنوان السؤال',
        'Question Description' => 'وصف السؤال',
        'Add an Option' => 'إضافة اختيار',
        'Add Option' => 'إضافة اختيار',
        'Option' => 'اختيار',
        'Options' => 'الاختيارات',
        'Correct' => 'صحيح',
        'Incorrect' => 'غير صحيح',
        'Required' => 'مطلوب',
        'Optional' => 'اختياري',
        'Explanation' => 'الشرح',
        'Answer Explanation' => 'شرح الإجابة',
        'Display Points' => 'عرض الدرجات',
        'Feedback Mode' => 'وضع التغذية الراجعة',
        'Default' => 'افتراضي',
        'Retry Mode' => 'وضع إعادة المحاولة',
        'Reveal Mode' => 'وضع إظهار الإجابات',
        'Question Order' => 'ترتيب الأسئلة',
        'Random' => 'عشوائي',
        'Course Content' => 'محتوى الدورة',
        'Course Description' => 'وصف الدورة',
        'Course Benefits' => 'مميزات الدورة',
        'What Will I Learn?' => 'ماذا سيتعلم الطالب؟',
        'Targeted Audience' => 'الفئة المستهدفة',
        'Total Course Duration' => 'إجمالي مدة الدورة',
        'Hours' => 'ساعات',
        'Minutes' => 'دقائق',
        'Seconds' => 'ثوانٍ',
        'Video Source' => 'مصدر الفيديو',
        'Select Video Source' => 'اختر مصدر الفيديو',
        'External URL' => 'رابط خارجي',
        'Upload Video' => 'رفع فيديو',
        'Choose Video' => 'اختيار فيديو',
        'Remove Video' => 'إزالة الفيديو',
        'Featured Image' => 'الصورة البارزة',
        'Choose Image' => 'اختيار صورة',
        'Remove Image' => 'إزالة الصورة',
        'Publish Course' => 'نشر الدورة',
        'Update Course' => 'تحديث الدورة',
        'Save as Draft' => 'حفظ كمسودة',
        'Preview Course' => 'معاينة الدورة',
        'Exit' => 'خروج',
        'Discard' => 'تجاهل',
        'Addons' => 'الملحقات',
        'Add-ons' => 'الملحقات',
        'Add-ons List' => 'قائمة الملحقات',
        'All Add-ons' => 'كل الملحقات',
        'No add-ons found' => 'لم يتم العثور على ملحقات',
        'No addons found' => 'لم يتم العثور على ملحقات',
        'Enable Add-on' => 'تفعيل الملحق',
        'Disable Add-on' => 'تعطيل الملحق',
        'Activate' => 'تفعيل',
        'Deactivate' => 'تعطيل',
        'Course Bundle' => 'حزم الدورات',
        'Content Drip' => 'التدرج في المحتوى',
        'Course Preview' => 'معاينة الدورة',
        'Course Attachments' => 'مرفقات الدورة',
        'Multi Instructors' => 'تعدد المعلمين',
        'Tutor Multi Instructors' => 'تعدد المعلمين',
        'Zoom Integration' => 'تكامل Zoom',
        'Report' => 'التقرير',
        'Reports' => 'التقارير',
        'Email' => 'البريد',
        'Google Classroom Integration' => 'تكامل Google Classroom',
        'Quiz Export/Import' => 'تصدير/استيراد الاختبارات',
        'Enrollment' => 'التسجيل',
        'Certificate' => 'الشهادة',
        'Gradebook' => 'سجل الدرجات',
        'Content Bank' => 'بنك المحتوى',
        'Enable to award certificates upon course completion.' => 'إصدار شهادات للطلاب عند إكمال الدورة.',
        'Connect Qalam LMS with Zoom to host live online classes.' => 'اربط قلم بـ Zoom لإنشاء الحصص المباشرة عبر الإنترنت.',
        'Collaborate and add multiple instructors to a course.' => 'أضف أكثر من معلم إلى الدورة وتعاونوا في إدارتها.',
        'Group multiple courses to sell together.' => 'اجمع عدة دورات داخل حزمة واحدة.',
        'Create content once and use it across multiple courses.' => 'أنشئ المحتوى مرة واحدة واستخدمه في أكثر من دورة.',
        'Unlock lessons by schedule or when students meet a specific condition.' => 'افتح الدروس حسب جدول زمني أو عند تحقق شرط محدد.',
        'Import and export quizzes' => 'استيراد وتصدير الاختبارات',
        'Search...' => 'بحث...',
        'Search Add-ons' => 'بحث في الملحقات',
        'Search addons' => 'بحث في الملحقات',
        'Free' => 'مجاني',
        'Paid' => 'مدفوع',
        'Public' => 'عام',
        'Private' => 'خاص',
        'Course visibility' => 'ظهور الدورة',
        'Visibility' => 'الظهور',
        'Enrollment Expiry' => 'انتهاء صلاحية التسجيل',
        'Instructor' => 'المعلم',
        'Instructors' => 'المعلمون',
        'Student' => 'الطالب',
        'Students' => 'الطلاب',
        'Course' => 'الدورة',
        'Courses' => 'الدورات',
        'Lesson' => 'الدرس',
        'Lessons' => 'الدروس',
        'Quiz' => 'الاختبار',
        'Quizzes' => 'الاختبارات',
        'Assignment' => 'الواجب',
        'Assignments' => 'الواجبات',
        'Question' => 'السؤال',
        'Questions' => 'الأسئلة',
        'Publish' => 'نشر',
        'Save' => 'حفظ',
        'Cancel' => 'إلغاء',
        'Next' => 'التالي',
        'Previous' => 'السابق',
        'Back' => 'رجوع',
        'Continue' => 'متابعة',
        'Settings' => 'الإعدادات',
        'General' => 'عام',
        'Advanced' => 'متقدم',
        'Advanced Settings' => 'إعدادات متقدمة',
        'No results found' => 'لا توجد نتائج',
        'Nothing found' => 'لا توجد نتائج',
        'Loading...' => 'جاري التحميل...',
        'Select a course' => 'اختر دورة',
        'Select Course' => 'اختر دورة',
        'Search courses' => 'ابحث عن الدورات',
        'Select' => 'اختيار',
        'Selected' => 'تم الاختيار',
        'Remove' => 'إزالة',
        'Delete' => 'حذف',
        'Edit' => 'تعديل',
        'AI Studio' => 'استوديو الذكاء الاصطناعي',
        'AI Provider' => 'مزود الذكاء الاصطناعي',
        'AI Provider API Key' => 'مفتاح API للمزود',
        'AI Model' => 'نموذج الذكاء الاصطناعي',
        'Custom OpenAI Base URL' => 'رابط Base URL للمزود المخصص',
        'Custom OpenAI-compatible' => 'مزود مخصص متوافق مع OpenAI',
        'Set OpenAI API key' => 'إعداد مزود الذكاء الاصطناعي',
        'OpenAI API key' => 'مفتاح API للمزود',
        'Enter your OpenAI API key' => 'أدخل مفتاح API للمزود المختار',
        'Enable OpenAI' => 'تفعيل الذكاء الاصطناعي',
        'API is not connected' => 'مزود الذكاء الاصطناعي غير متصل',
        'Connect API KEY' => 'ربط مفتاح API',
        'Update' => 'تحديث',
    );

    $map = array_merge( $map, array(
        'Settings exported successfully' => 'تم تصدير الإعدادات بنجاح',
        'Invalid file' => 'ملف غير صالح',
        'Invalid json file' => 'ملف JSON غير صالح',
        'Data not found or invalid' => 'البيانات غير موجودة أو غير صالحة',
        'Settings not found' => 'لم يتم العثور على الإعدادات',
        'Settings imported successfully!' => 'تم استيراد الإعدادات بنجاح!',
        'Total share percentage must be 100% or less' => 'يجب ألا تتجاوز نسبة المشاركة الإجمالية 100%',
        'Settings Saved' => 'تم حفظ الإعدادات',
        'General' => 'عام',
        'General Settings' => 'الإعدادات العامة',
        'Dashboard Page' => 'صفحة لوحة التحكم',
        'This page will be used for student and instructor dashboard' => 'ستُستخدم هذه الصفحة كلوحة تحكم للطلاب والمعلمين',
        'Terms and Conditions Page' => 'صفحة الشروط والأحكام',
        'This page will be used as the Terms and Conditions page' => 'ستُستخدم هذه الصفحة لعرض الشروط والأحكام',
        'Privacy Policy' => 'سياسة الخصوصية',
        'Choose the page for privacy policy.' => 'اختر صفحة سياسة الخصوصية.',
        'Others' => 'أخرى',
        'Enable Marketplace' => 'تفعيل سوق الدورات',
        'Allow multiple instructors to sell their courses.' => 'السماح لعدة معلمين ببيع دوراتهم عبر المنصة.',
        'Pagination' => 'عدد العناصر في الصفحة',
        'Set the number of rows to be displayed per page' => 'حدد عدد الصفوف التي تظهر في كل صفحة',
        'Instructor' => 'المعلم',
        'Become an Instructor Button' => 'زر «كن معلماً»',
        'Enable the option to display this button on the student dashboard.' => 'فعّل هذا الخيار لإظهار زر التحول إلى معلم داخل لوحة الطالب.',
        'Allow Instructors to Publish Courses' => 'السماح للمعلمين بنشر الدورات',
        'Enable instructors to publish the course directly. If disabled, admins will be able to review course content before publishing.' => 'يسمح للمعلم بنشر الدورة مباشرة. عند تعطيله، يراجع المدير محتوى الدورة قبل النشر.',
        'Allow Instructors to Trash Courses' => 'السماح للمعلمين بحذف الدورات',
        'Enable this setting to allow instructors to delete courses.' => 'فعّل هذا الخيار للسماح للمعلمين بنقل دوراتهم إلى سلة المهملات.',
        'Course' => 'الدورة',
        'Course Settings' => 'إعدادات الدورة',
        'Course Visibility' => 'ظهور الدورة',
        'Students must be logged in to view course' => 'يجب تسجيل الدخول لعرض الدورة',
        'Course Content Access' => 'الوصول إلى محتوى الدورة',
        'Allow instructors and admins to view the course content without enrolling' => 'السماح للمعلمين والمديرين بعرض محتوى الدورة دون تسجيل',
        'Content Summary' => 'ملخص المحتوى',
        'Enabling this feature will show a course content summary on the Course Details page.' => 'يعرض ملخص محتوى الدورة في صفحة تفاصيل الدورة.',
        'Spotlight Mode' => 'وضع التركيز',
        'This will hide the header and the footer and enable spotlight (full screen) mode when students view lessons.' => 'يخفي رأس وتذييل الصفحة ويعرض الدروس بوضع تركيز كامل الشاشة.',
        'Auto Complete Course on All Lesson Completion' => 'إكمال الدورة تلقائياً عند إنهاء المحتوى',
        'If enabled, an Enrolled Course will be automatically completed if all its Lessons, Quizzes, and Assignments are already completed by the Student' => 'عند التفعيل، تُعتبر الدورة مكتملة تلقائياً بعد إنهاء الطالب كل الدروس والاختبارات والواجبات.',
        'Course Completion Process' => 'طريقة إكمال الدورة',
        'Flexible' => 'مرن',
        'Students can complete courses anytime in the Flexible mode' => 'يمكن للطلاب إنهاء الدورة في أي وقت في الوضع المرن',
        'Strict' => 'صارم',
        'Students must complete all lessons, quizzes, and assignments to mark their courses as complete.' => 'يجب إكمال جميع الدروس والاختبارات والواجبات لاعتبار الدورة مكتملة.',
        'Choose when a user can click on the <strong>“Complete Course”</strong> button' => 'حدد متى يمكن للمستخدم الضغط على زر <strong>«إكمال الدورة»</strong>',
        'Course Retake' => 'إعادة الدورة',
        'Enabling this feature will allow students to reset course progress and start over.' => 'يسمح للطلاب بإعادة ضبط تقدم الدورة والبدء من جديد.',
        'Course Reset Progress' => 'إعادة ضبط تقدم الدورة',
        'Enabling this feature allows students to reset their progress and start over before completing the course.' => 'يسمح للطالب بإعادة ضبط تقدمه والبدء من جديد قبل إكمال الدورة.',
        'Publish Course Review on Admin\'s Approval' => 'نشر تقييم الدورة بعد موافقة المدير',
        'Enable to publish/re-publish Course Review after the approval of Site Admin' => 'ينشر أو يعيد نشر تقييم الدورة بعد موافقة مدير الموقع.',
        'Lesson' => 'الدرس',
        'WP Editor for Lesson' => 'المحرر المتقدم للدروس',
        'Enable classic editor to edit lesson.' => 'استخدم المحرر التقليدي لتحرير الدرس.',
        'Automatically Load Next Course Content.' => 'تحميل المحتوى التالي تلقائياً',
        'Enable this feature to automatically load the next course content after the current one is finished.' => 'تحميل المحتوى التالي تلقائياً بعد الانتهاء من الحالي.',
        'Enable Lesson Comment' => 'تفعيل تعليقات الدروس',
        'Enable this feature to allow students to post comments on lessons.' => 'السماح للطلاب بكتابة تعليقات على الدروس.',
        'Quiz' => 'الاختبار',
        'When time expires' => 'عند انتهاء الوقت',
        'Auto Submit' => 'إرسال تلقائي',
        'The current quiz answers are submitted automatically.' => 'يتم إرسال إجابات الاختبار الحالية تلقائياً.',
        'Auto Abandon' => 'إلغاء المحاولة تلقائياً',
        'Attempts must be submitted before time expires, otherwise they will not be counted.' => 'يجب إرسال المحاولة قبل انتهاء الوقت وإلا لن يتم احتسابها.',
        'Choose which action to follow when the quiz time expires.' => 'اختر الإجراء المطلوب عند انتهاء وقت الاختبار.',
        'Correct Answer Display Time (When Reveal Mode is enabled)' => 'مدة عرض الإجابة الصحيحة (عند تفعيل وضع الكشف)',
        'Put the answer display time in seconds' => 'أدخل مدة عرض الإجابة بالثواني',
        'Show Quiz Previous Button' => 'إظهار زر السؤال السابق',
        'Choose whether to show or hide the previous button for each question.' => 'حدد إظهار أو إخفاء زر السابق لكل سؤال.',
        'Final Grade Calculation' => 'حساب الدرجة النهائية',
        'Highest Grade' => 'أعلى درجة',
        'Average Grade' => 'متوسط الدرجات',
        'First Attempt' => 'المحاولة الأولى',
        'Last Attempt' => 'المحاولة الأخيرة',
        'Video' => 'الفيديو',
        'Preferred Video Source' => 'مصدر الفيديو المفضل',
        'Select the video hosting platform(s) you want to enable.' => 'اختر منصات استضافة الفيديو التي تريد تفعيلها.',
        'Use Tutor Player for YouTube' => 'استخدام مشغل قلم مع YouTube',
        'Enable this option to use Qalam LMS video player for YouTube.' => 'فعّل هذا الخيار لاستخدام مشغل قلم لفيديوهات YouTube.',
        'Use Tutor Player for Vimeo' => 'استخدام مشغل قلم مع Vimeo',
        'Enable this option to use Qalam LMS video player for Vimeo.' => 'فعّل هذا الخيار لاستخدام مشغل قلم لفيديوهات Vimeo.',
        'Monetization' => 'تحقيق الدخل',
        'Monetization Settings' => 'إعدادات تحقيق الدخل',
        'Options' => 'الخيارات',
        'Select eCommerce Engine' => 'اختر نظام التجارة الإلكترونية',
        'Disable Monetization' => 'تعطيل تحقيق الدخل',
        'Select a monetization option to generate revenue by selling courses.' => 'اختر طريقة تحقيق الدخل من بيع الدورات.',
        'WooCommerce' => 'المتجر الإلكتروني',
        'Automatically Complete WooCommerce Orders' => 'إكمال طلبات المتجر تلقائيًا',
        'If enabled, in the case of Courses, WooCommerce Orders will get the "Completed" status .' => 'عند التفعيل، تحصل طلبات الدورات في المتجر على حالة «مكتمل» تلقائيًا.',
        'Auto Redirect to Courses' => 'إعادة التوجيه إلى الدورات تلقائياً',
        'Enable Guest Mode' => 'تفعيل الشراء كزائر',
        'Allow customers to place orders without an account.' => 'السماح للعملاء بإجراء الطلبات دون إنشاء حساب.',
        'Revenue Sharing' => 'تقاسم الإيرادات',
        'Enable Revenue Sharing' => 'تفعيل تقاسم الإيرادات',
        'Allow revenue generated from selling courses to be shared with course creators.' => 'تقاسم إيرادات بيع الدورات مع منشئي الدورات.',
        'Sharing Percentage' => 'نسب المشاركة',
        'Instructor Takes' => 'حصة المعلم',
        'Admin Takes' => 'حصة الإدارة',
        'Set how the sales revenue will be shared among admins and instructors.' => 'حدد كيفية توزيع إيرادات المبيعات بين الإدارة والمعلمين.',
        'Fees' => 'الرسوم',
        'Deduct Fees' => 'خصم الرسوم',
        'Fees are charged from the entire sales amount. The remaining amount will be divided among admin and instructors.' => 'تُخصم الرسوم من إجمالي المبيعات، ثم يُوزع المبلغ المتبقي بين الإدارة والمعلمين.',
        'Fee Description' => 'وصف الرسوم',
        'Set a description for the fee that you are deducting. Make sure to give a reasonable explanation to maintain transparency with your site’s instructors.' => 'اكتب وصفاً واضحاً للرسوم المخصومة لضمان الشفافية مع المعلمين.',
        'Fee Amount & Type' => 'قيمة ونوع الرسوم',
        'Select the fee type and add fee amount/percentage' => 'اختر نوع الرسوم وأدخل القيمة أو النسبة',
        'Percent' => 'نسبة مئوية',
        'Fixed' => 'قيمة ثابتة',
        'Withdraw' => 'السحب',
        'Minimum Withdrawal Amount' => 'الحد الأدنى للسحب',
        'Instructors should earn equal or above this amount to make a withdraw request.' => 'يجب أن يصل رصيد المعلم إلى هذا المبلغ أو أكثر لإرسال طلب سحب.',
        'Minimum Days Before Balance is Available' => 'أقل مدة قبل إتاحة الرصيد',
        'Any income has to remain this many days in the platform before it is available for withdrawal.' => 'تبقى الأرباح هذه المدة داخل المنصة قبل أن تصبح متاحة للسحب.',
        'Enable Withdraw Method' => 'تفعيل طرق السحب',
        'Set how you would like to withdraw money from the website.' => 'حدد طرق سحب الأموال من الموقع.',
        'Bank Instructions' => 'تعليمات التحويل البنكي',
        'Write the up to date bank informations of your instructor here.' => 'اكتب بيانات التحويل البنكي المحدثة للمعلمين هنا.',
        'Write bank instructions for the instructors to conduct withdrawals.' => 'اكتب تعليمات التحويل البنكي التي سيتبعها المعلمون عند السحب.',
        'Design' => 'التصميم',
        'Design Settings' => 'إعدادات التصميم',
        'Appearance' => 'المظهر',
        'Learning Mode' => 'نمط التعلم',
        'Decide how students will experience the courses you create.' => 'حدد تجربة عرض الدورات للطلاب.',
        'Modern' => 'حديث',
        'Kids' => 'أطفال',
        'Legacy' => 'تقليدي',
        'Default Theme' => 'المظهر الافتراضي',
        'Set the default appearance for learners across your platform. Learners can switch between dark and light mode if enabled.' => 'حدد المظهر الافتراضي للطلاب، مع إمكانية التبديل بين الوضعين الفاتح والداكن إذا كان ذلك متاحاً.',
        'Light' => 'فاتح',
        'Dark' => 'داكن',
        'Auto' => 'تلقائي',
        'Brand Color' => 'لون الهوية',
        'Customize the primary accent color used across your learning platform to match your brand identity.' => 'خصص اللون الرئيسي للمنصة بما يتوافق مع هوية قلم.',
        'Column Per Row' => 'عدد الأعمدة في الصف',
        'One' => 'واحد',
        'Two' => 'اثنان',
        'Three' => 'ثلاثة',
        'Four' => 'أربعة',
        'Define how many columns you want to use to display courses.' => 'حدد عدد الأعمدة المستخدمة لعرض الدورات.',
        'Courses Per Page' => 'عدد الدورات في الصفحة',
        'Set the number of courses to display per page on the Course List page.' => 'حدد عدد الدورات التي تظهر في كل صفحة بقائمة الدورات.',
        'Course Filter' => 'فلترة الدورات',
        'Show sorting and filtering options on course archive page' => 'إظهار خيارات الترتيب والتصفية في صفحة أرشيف الدورات',
        'Preferred Course Filters' => 'فلاتر الدورات المفضلة',
        'Keyword Search' => 'البحث بالكلمات',
        'Category' => 'التصنيف',
        'Tag' => 'الوسم',
        'Difficulty Level' => 'مستوى الصعوبة',
        'Price Type' => 'نوع السعر',
        'Course Sorting' => 'ترتيب الدورات',
        'If enabled, the courses will be sortable by Course Name or Creation Date in either Ascending or Descending order' => 'عند التفعيل يمكن ترتيب الدورات حسب الاسم أو تاريخ الإنشاء تصاعدياً أو تنازلياً.',
        'Layout' => 'التخطيط',
        'Instructor List Layout' => 'تصميم قائمة المعلمين',
        'Choose a layout for the list of instructors inside a course page. You can change this at any time.' => 'اختر تصميم قائمة المعلمين داخل صفحة الدورة ويمكن تغييره لاحقاً.',
        'Portrait' => 'عمودي',
        'Cover' => 'غلاف',
        'Minimal' => 'مبسّط',
        'Portrait Horizontal' => 'عمودي أفقي',
        'Minimal Horizontal' => 'مبسّط أفقي',
        'Instructor Public Profile Layout' => 'تصميم الملف العام للمعلم',
        'Choose a layout design for a instructor’s public profile' => 'اختر تصميم الملف الشخصي العام للمعلم',
        'Private' => 'خاص',
        'Classic' => 'كلاسيكي',
        'Student Public Profile Layout' => 'تصميم الملف العام للطالب',
        'Choose a layout design for a student’s public profile' => 'اختر تصميم الملف الشخصي العام للطالب',
        'Course Details' => 'تفاصيل الدورة',
        'Page Features' => 'عناصر الصفحة',
        'You can keep the following features active or inactive as per the need of your business model' => 'يمكنك تفعيل أو تعطيل العناصر التالية حسب احتياجات منصتك.',
        'Instructor Info' => 'بيانات المعلم',
        'Toggle to show instructor info' => 'إظهار أو إخفاء بيانات المعلم',
        'Wishlist' => 'المفضلة',
        'Toggle to disable/enable wishlist' => 'تفعيل أو تعطيل قائمة المفضلة',
        'Q&A' => 'الأسئلة والأجوبة',
        'Enable to add a Q&A section' => 'تفعيل قسم الأسئلة والأجوبة',
        'Author' => 'المؤلف',
        'Enable to show course author name' => 'إظهار اسم منشئ الدورة',
        'Level' => 'المستوى',
        'Enable to show course level' => 'إظهار مستوى الدورة',
        'Social Share' => 'المشاركة الاجتماعية',
        'Toggle to enable course social share' => 'تفعيل مشاركة الدورة على الشبكات الاجتماعية',
        'Duration' => 'المدة',
        'Enable to show course duration' => 'إظهار مدة الدورة',
        'Total Enrolled' => 'إجمالي المسجلين',
        'Enable to show total enrolled students' => 'إظهار إجمالي الطلاب المسجلين',
        'Update Date' => 'تاريخ التحديث',
        'Enable to show course update information' => 'إظهار معلومات تحديث الدورة',
        'Progress Bar' => 'شريط التقدم',
        'Enable to show course progress for Students' => 'إظهار تقدم الدورة للطلاب',
        'Material' => 'المواد',
        'Enable to show course materials' => 'إظهار مواد الدورة',
        'About' => 'نبذة',
        'Enable to show course about section' => 'إظهار قسم نبذة عن الدورة',
        'Description' => 'الوصف',
        'Enable to show course description' => 'إظهار وصف الدورة',
        'Benefits' => 'الفوائد',
        'Enable to show course benefits section' => 'إظهار قسم فوائد الدورة',
        'Requirements' => 'المتطلبات',
        'Enable to show courses requirements section' => 'إظهار متطلبات الدورة',
        'Target Audience' => 'الفئة المستهدفة',
        'Enable to show course target audience section' => 'إظهار قسم الفئة المستهدفة',
        'Announcements' => 'الإعلانات',
        'Enable to show course announcements section' => 'إظهار قسم إعلانات الدورة',
        'Review' => 'التقييم',
        'Enable to show course review section' => 'إظهار قسم تقييمات الدورة',
        'Advanced' => 'متقدم',
        'Advanced Settings' => 'الإعدادات المتقدمة',
        'Gutenberg Editor' => 'محرر Gutenberg',
        'Enable this to create courses using the Gutenberg Editor.' => 'استخدم محرر Gutenberg لإنشاء الدورات.',
        'Hide Course Products on Shop Page' => 'إخفاء منتجات الدورات من صفحة المتجر',
        'Enable to hide course products on shop page.' => 'إخفاء منتجات الدورات من صفحة المتجر.',
        'Course Archive Page' => 'صفحة أرشيف الدورات',
        'This page will be used to list all the published courses.' => 'تُستخدم هذه الصفحة لعرض جميع الدورات المنشورة.',
        'Instructor Registration Page' => 'صفحة تسجيل المعلمين',
        'Choose the page for instructor registration.' => 'اختر صفحة تسجيل المعلمين.',
        'Student Registration Page' => 'صفحة تسجيل الطلاب',
        'Choose the page for student registration.' => 'اختر صفحة تسجيل الطلاب.',
        'YouTube API Key' => 'مفتاح YouTube API',
        'To host live videos on your platform using YouTube, enter your YouTube API key.' => 'أدخل مفتاح YouTube API لاستخدام البث المباشر عبر YouTube.',
        'Insert API key here' => 'أدخل مفتاح API هنا',
        'Base Permalink' => 'بنية الروابط',
        'Course Permalink' => 'رابط الدورة',
        'Lesson Permalink' => 'رابط الدرس',
        'Quiz Permalink' => 'رابط الاختبار',
        'Profile Completion' => 'اكتمال الملف الشخصي',
        'Enabling this feature will show a notification bar to students and instructors to complete their profile information' => 'يعرض شريط تنبيه للطلاب والمعلمين لاستكمال بيانات ملفاتهم الشخصية.',
        'Enable Qalam LMS Login' => 'تفعيل تسجيل دخول قلم',
        'Enable to use the Qalam LMS native login system instead of the WordPress login page' => 'استخدام نظام تسجيل دخول قلم المستقل.',
        'Erase upon uninstallation' => 'حذف البيانات عند إزالة الإضافة',
        'Delete all data during uninstallation' => 'حذف جميع بيانات قلم عند إزالة الإضافة',
        'Maintenance Mode' => 'وضع الصيانة',
        'Enabling maintenance mode will display a custom message on the frontend. During maintenance mode, visitors cannot access site content, but the wp-admin dashboard remains accessible.' => 'يعرض وضع الصيانة رسالة مخصصة للزوار ويمنع الوصول للمحتوى، بينما تظل أدوات الصيانة الخلفية متاحة للمشرف.',
        'Legal Consents' => 'الموافقات القانونية',
        'Search ...⌃⌥ + S or Alt+S for shortcut' => 'ابحث في الإعدادات…  Alt+S للاختصار',
        'Reset to Default' => 'استعادة الافتراضي',
        'Select Option' => 'اختر خياراً',
        'Create Announcement' => 'إنشاء إعلان',
        'Notify all students of your course' => 'إشعار جميع طلاب دورتك',
        'Add New Announcement' => 'إضافة إعلان جديد',
        'Bulk Action' => 'إجراء جماعي',
        'Filters' => 'الفلاتر',
        'No Data Found' => 'لا توجد بيانات',
        'Apply Filters' => 'تطبيق الفلاتر',
        'What\'s New' => 'ما الجديد',
        'What\'s New 🥳 in Qalam LMS' => 'ما الجديد في قلم 🥳',
        'New version available. You didn\'t update yet!' => 'يتوفر إصدار جديد.',
        'Update Now' => 'تحديث الآن',
        'More info...' => 'مزيد من المعلومات…',
        'You are using' => 'أنت تستخدم',
        'Here are features and improvements made to this version!' => 'هذه أبرز التحسينات الموجودة في هذا الإصدار.',
        'Changelog' => 'سجل التغييرات',
        'New' => 'جديد',
        'Fix' => 'إصلاح',
        'Pro' => 'متقدم',
        'Documentation' => 'التوثيق',
        'Pro Features' => 'الميزات المتقدمة',
        'Priority Support' => 'الدعم المميز',
        'Installing Plugin: %s' => 'جارٍ تثبيت الإضافة: %s',
        'No Data Found.' => 'لا توجد بيانات.',
        'Search...' => 'بحث…',
        'Search ...' => 'بحث…',
        'Filter by' => 'تصفية حسب',
        'Clear All' => 'مسح الكل',
        'Order by' => 'ترتيب حسب',
        'Move to' => 'نقل إلى',
        'Content Security' => 'حماية المحتوى',
        'Prevent Hotlinking' => 'منع الربط المباشر',
        'Use hotlink protection for your self-hosted images and videos' => 'حماية الصور والفيديوهات المستضافة على موقعك من الربط المباشر',
        'Copy Protection' => 'حماية النسخ',
        'Prevent right-click and copy actions on your website' => 'منع النقر بزر الفأرة الأيمن ونسخ المحتوى',
        'Email Verification' => 'التحقق من البريد الإلكتروني',
        'Enable Email Update' => 'السماح بتغيير البريد',
        'Allow students and instructors to change their email directly from their profile' => 'السماح للطلاب والمعلمين بتغيير بريدهم مباشرة من الملف الشخصي',
        'Allow Instructors to Reset Student Progress' => 'السماح للمعلمين بإعادة ضبط تقدم الطلاب',
        'Enable to allow instructors to reset a student’s course progress.' => 'السماح للمعلم بإعادة ضبط تقدم الطالب في الدورة.',
        'Enable Lesson Notes' => 'تفعيل ملاحظات الدروس',
        'Enable/disable lesson notes.' => 'تفعيل أو تعطيل ملاحظات الدروس.',
        'Notes' => 'الملاحظات',
        'Take Note' => 'إضافة ملاحظة',
        'Live Classes' => 'الحصص المباشرة',
        'Today' => 'اليوم',
        'Expired' => 'منتهية',
        'Live Session' => 'جلسة مباشرة',
        'Join' => 'انضمام',
        'Duplicate' => 'نسخ',
        'Course Duplicated Successfully!' => 'تم نسخ الدورة بنجاح!',
        'Answer Explanation' => 'شرح الإجابة',
        'Show Explanation' => 'عرض الشرح',
        'The answer for this question is required' => 'إجابة هذا السؤال مطلوبة',

        // Qalam Student Experience.
        'Hi,' => 'مرحبًا،',
        'Hi, Welcome back!' => 'مرحبًا بعودتك إلى قلم!',
        'Back to dashboard' => 'العودة إلى لوحة التحكم',
        'Toggle course sidebar' => 'فتح قائمة محتوى الدورة',
        'Close course sidebar' => 'إغلاق قائمة محتوى الدورة',
        'Learning area' => 'منطقة التعلم',
        'Continue Learning' => 'أكمل التعلّم',
        'See All' => 'عرض الكل',
        'Enrolled Courses' => 'الدورات المسجّل بها',
        'Active Courses' => 'الدورات النشطة',
        'Completed Courses' => 'الدورات المكتملة',
        'Time Spent' => 'وقت التعلّم',
        'No Courses Found' => 'لا توجد دورات بعد',
        'No Quiz Attempts Found' => 'لا توجد محاولات اختبارات بعد',
        'Quiz info' => 'بيانات الاختبار',
        'Marks' => 'الدرجات',
        'Clear all' => 'مسح الكل',
        'Search quizzes...' => 'ابحث في الاختبارات…',
        'Explore Courses' => 'استكشف الدورات',
        'Explore' => 'استكشف',
        'Welcome to %s' => 'مرحبًا بك في %s',
        'You haven\'t enrolled in %s a course yet.' => 'لم تسجل في %s أي دورة حتى الآن.',
        'Explore course and start building your %s skills today.' => 'استكشف الدورات وابدأ تطوير %s مهاراتك اليوم.',
        'Quick Tips' => 'نصائح سريعة',
        'Use notes to save key ideas as you watch' => 'استخدم الملاحظات لحفظ الأفكار المهمة أثناء المشاهدة',
        'Check the calendar for upcoming live sessions' => 'راجع التقويم لمعرفة مواعيد الحصص المباشرة القادمة',
        'Discussions are a great place to ask questions' => 'استخدم المناقشات لطرح أسئلتك والتواصل مع المعلم',
        'You can pause and resume courses any time' => 'يمكنك إيقاف الدورة ومتابعتها في أي وقت',
        'Course Progress' => 'تقدم الدورة',
        '%s Completed' => 'اكتمل %s',
        '%1$s%% Complete' => 'مكتمل بنسبة %1$s%%',
        'Complete' => 'إكمال',
        'Complete Course' => 'إكمال الدورة',
        'Finish Course Early?' => 'هل تريد إنهاء الدورة الآن؟',
        'Complete Anyway' => 'إكمال على أي حال',
        'Reset Progress' => 'إعادة ضبط التقدم',
        'Reset Course Progress?' => 'إعادة ضبط تقدم الدورة؟',
        'No, Keep My Progress' => 'لا، احتفظ بتقدمي',
        'Yes, Reset Everything' => 'نعم، ابدأ من جديد',
        'This will remove your completed lessons, quizzes, and assignments. You will start the course from the beginning.' => 'سيتم حذف تقدمك في الدروس والاختبارات والواجبات والبدء من أول الدورة.',
        'Mark as Complete' => 'تحديد كمكتمل',
        'Continue to lesson' => 'متابعة إلى الدرس',
        'Exercise Files' => 'ملفات الدرس',
        'Lesson Comments' => 'تعليقات الدرس',
        'Comments' => 'التعليقات',
        'Join The Conversation' => 'شارك في النقاش',
        'Please enter a comment' => 'اكتب تعليقك',
        'Question & Answer' => 'الأسئلة والأجوبة',
        'Asked questions...' => 'ابحث في الأسئلة…',
        'Be the first to ask a question about this lesson!' => 'كن أول من يطرح سؤالًا عن هذا الدرس!',
        'Please enter your response.' => 'اكتب ردك.',
        'Just drop your response here!' => 'اكتب ردك هنا…',
        'Previous Attempts' => 'المحاولات السابقة',
        'Passing Grade' => 'درجة النجاح',
        'Quiz Time' => 'وقت الاختبار',
        'Quiz Submitted' => 'تم تسليم الاختبار',
        'View Results' => 'عرض النتيجة',
        'Go Back to Course' => 'العودة إلى الدورة',
        'Go to Dashboard' => 'الذهاب إلى لوحة التحكم',
        'No course found for this quiz' => 'لم يتم العثور على دورة مرتبطة بهذا الاختبار',
        'Question No: %s' => 'السؤال رقم: %s',
        'Questions No' => 'عدد الأسئلة',
        'Points:' => 'الدرجات:',
        'Points: ' => 'الدرجات: ',
        'Quit' => 'خروج',
        'Leave this Quiz?' => 'هل تريد مغادرة الاختبار؟',
        'If you leave now, your quiz will be submitted with the answers completed so far.' => 'إذا غادرت الآن فسيتم تسليم الاختبار بالإجابات التي أكملتها حتى هذه اللحظة.',
        'Do You Want to Skip This Quiz?' => 'هل تريد تخطي هذا الاختبار؟',
        'Are you sure you want to skip this quiz? Please confirm your choice.' => 'هل أنت متأكد من تخطي الاختبار؟',
        'Next page' => 'الصفحة التالية',
        'Previous page' => 'الصفحة السابقة',
        'Enter fullscreen' => 'ملء الشاشة',
        'Exit fullscreen' => 'الخروج من ملء الشاشة',
        'Expand panel' => 'توسيع اللوحة',
        'Collapse panel' => 'طي اللوحة',
        'More' => 'المزيد',
        'Profile Photo' => 'الصورة الشخصية',
        'Cover Photo Info' => 'صورة الغلاف',
        'Display Name' => 'الاسم الظاهر',
        'First Name' => 'الاسم الأول',
        'Last Name' => 'اسم العائلة',
        'Phone Number' => 'رقم الهاتف',
        'Bio' => 'نبذة شخصية',
        'Account Email' => 'البريد الإلكتروني للحساب',
        'Change Account Password' => 'تغيير كلمة مرور الحساب',
        'Current Password' => 'كلمة المرور الحالية',
        'New Password' => 'كلمة المرور الجديدة',
        'Confirm New Password' => 'تأكيد كلمة المرور الجديدة',
        'Preferences' => 'التفضيلات',
        'Accessibility' => 'إمكانية الوصول',
        'Font Size' => 'حجم الخط',
        'High Contrast' => 'تباين مرتفع',
        'Interactive Effects' => 'المؤثرات التفاعلية',
        'Motion Effects' => 'مؤثرات الحركة',
        'Adjust the text size for better readability.' => 'اضبط حجم النص لقراءة أكثر راحة.',
        'Increase contrast to improve text and element visibility.' => 'ارفع التباين لتحسين وضوح النصوص والعناصر.',
        'Limit animations and motion effects to reduce visual strain.' => 'قلل الحركة والمؤثرات لراحة أكبر أثناء التعلّم.',
        'Order History' => 'سجل الطلبات',
        'No Orders Found!' => 'لا توجد طلبات بعد',
        'Public Profile' => 'الملف الشخصي العام',
        'Linked social media profiles' => 'الحسابات الاجتماعية المرتبطة',
        'Open user menu' => 'فتح قائمة الحساب',
        'Become an Instructor' => 'التقديم كمعلم',
        'Application Under Review' => 'طلب المعلم قيد المراجعة',
        'Application Approved' => 'تم قبول طلب المعلم',
        'Login' => 'تسجيل الدخول',
        'Username or Email Address' => 'اسم المستخدم أو البريد الإلكتروني',
        'Keep me signed in' => 'تذكرني',
        'Forgot Password?' => 'نسيت كلمة المرور؟',
        'Sign In' => 'دخول',
        'Don\'t have an account?' => 'ليس لديك حساب؟',
        'Register Now' => 'إنشاء حساب',
        'Already have an account?' => 'لديك حساب بالفعل؟',
        'Register' => 'إنشاء حساب',
        'Enter Your Email' => 'أدخل بريدك الإلكتروني',
        'Enter your username' => 'أدخل اسم المستخدم',
        'Password Confirmation' => 'تأكيد كلمة المرور',
        'Course Details' => 'تفاصيل الدورة',
        'About Course' => 'عن الدورة',
        'About this Course' => 'عن هذه الدورة',
        'Material Includes' => 'محتويات الدورة',
        'Materials' => 'المواد',
        'Audience' => 'الفئة المستهدفة',
        'A course by' => 'دورة يقدمها',
        'By' => 'بواسطة',
        'Learners' => 'الطلاب',
        'Enroll Now' => 'سجّل الآن',
        'Buy Now' => 'اشترِ الآن',
        'Add to Cart' => 'أضف إلى السلة',
        'Free access this course' => 'الوصول المجاني إلى هذه الدورة',
        'Last Updated' => 'آخر تحديث',
        'Course Completion Rate' => 'نسبة إكمال الدورة',
        'Overview' => 'نظرة عامة',
        'Announcements' => 'الإعلانات',
        'No Announcements Found' => 'لا توجد إعلانات بعد',
        'No Announcements Found!' => 'لا توجد إعلانات بعد',
        'No Questions Found!' => 'لا توجد أسئلة بعد',
        'No Reviews Found!' => 'لا توجد تقييمات بعد',
        'No Review Yet' => 'لا توجد تقييمات بعد',
        'How Was Your Experience?' => 'كيف كانت تجربتك؟',
        'Edit Review' => 'تعديل التقييم',
        'Delete Review' => 'حذف التقييم',
        'Notifications' => 'الإشعارات',
        'Mark as Read' => 'تحديد كمقروء',
        'Mark as Unread' => 'تحديد كغير مقروء',
        'Mark as Important' => 'تحديد كمهم',
        'Mark as Not Important' => 'إلغاء التحديد كمهم',
        'Archive' => 'أرشفة',
        'Archived' => 'مؤرشف',
        'Invoice' => 'الفاتورة',
        'Plan:' => 'الخطة:',

        'Assignment Submission' => 'تسليم الواجب',
        'Assignment Info' => 'بيانات الواجب',
        'Assignment Description' => 'وصف الواجب',
        'Your Assignment' => 'واجبك',
        'Your Submission' => 'تسليمك',
        'Written Response' => 'الإجابة المكتوبة',
        'Start Assignment' => 'بدء الواجب',
        'Submit Assignment' => 'تسليم الواجب',
        'Resubmit Assignment' => 'إعادة تسليم الواجب',
        'Resubmit' => 'إعادة التسليم',
        'Skip Assignment' => 'تخطي الواجب',
        'Skip To Next' => 'الانتقال إلى التالي',
        'Confirm Submission' => 'تأكيد التسليم',
        'Are you sure you want to submit this assignment? You won\'t be able to make changes after submission.' => 'هل أنت متأكد من تسليم الواجب؟ لن تتمكن من تعديل الإجابة بعد التسليم.',
        'Assignment answer is required' => 'إجابة الواجب مطلوبة',
        'Please share your response with any explanations or relevant information.' => 'اكتب إجابتك مع أي شرح أو معلومات مرتبطة بالمطلوب.',
        'Drop files here or click to upload' => 'اسحب الملفات هنا أو اضغط للرفع',
        'Select Files' => 'اختيار الملفات',
        'Choose file' => 'اختيار ملف',
        'File Attached' => 'الملف المرفق',
        'Attachments' => 'المرفقات',
        'Total Marks' => 'إجمالي الدرجات',
        'Earned Marks' => 'الدرجة المحصّلة',
        'Obtained Marks' => 'الدرجة المحصّلة',
        'Pass Marks' => 'درجة النجاح',
        'Passing Mark:' => 'درجة النجاح:',
        'Submitted Date' => 'تاريخ التسليم',
        'Attempt Date' => 'تاريخ المحاولة',
        'Deadline:' => 'آخر موعد:',
        'Instructor Feedback' => 'ملاحظات المعلم',
        'Your assignment is being reviewed. You\'ll be notified once grading is complete' => 'يتم الآن مراجعة واجبك، وسيصلك إشعار عند اكتمال التقييم.',
        'No Assignments Found!' => 'لا توجد واجبات بعد',
        'Search assignments...' => 'ابحث في الواجبات…',
        'Notes' => 'الملاحظات',
        'Video Notes' => 'ملاحظات الفيديو',
        'No Notes Found!' => 'لا توجد ملاحظات بعد',
        'Search notes...' => 'ابحث في الملاحظات…',
        'From the lesson' => 'من الدرس',
        'Play Video Clip %s' => 'تشغيل مقطع الفيديو %s',
        'Delete This Note?' => 'حذف هذه الملاحظة؟',
        'Are you sure you want to delete this note permanently? Please confirm your choice.' => 'هل أنت متأكد من حذف هذه الملاحظة نهائيًا؟',
        'No Notifications Yet!' => 'لا توجد إشعارات بعد',
        'No Unread Notifications!' => 'لا توجد إشعارات غير مقروءة',
        'You are all caught up for now.' => 'اطلعت على كل الإشعارات حتى الآن.',
        'Mark all as read' => 'تحديد الكل كمقروء',
        'View all notifications' => 'عرض كل الإشعارات',
        'Open notifications' => 'فتح الإشعارات',
        'Show notifications' => 'عرض الإشعارات',
        'Unread' => 'غير مقروء',
        'Certificate of Achievement' => 'شهادة إنجاز',
        'Course Completion Certificate' => 'شهادة إتمام الدورة',
        'Certificate ID' => 'رقم الشهادة',
        'Valid Certificate ID' => 'رقم شهادة صالح',
        'VERIFIED' => 'تم التحقق',
        'Issued' => 'تاريخ الإصدار',
        'Download Certificate' => 'تحميل الشهادة',
        'Print Certificate' => 'طباعة الشهادة',
        'Share Certificate' => 'مشاركة الشهادة',
        'Quick Share' => 'مشاركة سريعة',
        'Share To' => 'مشاركة عبر',
        'This is to certify that' => 'تشهد منصة قلم بأن',
        'has successfully completed' => 'قد أتم بنجاح',
        'online course of' => 'الدورة التدريبية',
        'Course completed by' => 'أكمل الدورة',
        'Congratulations!' => 'تهانينا!',
        'Live Classes' => 'الحصص المباشرة',
        'Upcoming Live Classes' => 'الحصص المباشرة القادمة',
        'Live Session' => 'جلسة مباشرة',
        'No upcoming live lessons available.' => 'لا توجد حصص مباشرة قادمة حاليًا.',
        'Start Learning' => 'ابدأ التعلّم',
        'Continue Lesson' => 'متابعة الدرس',
        'Google Meet' => 'Google Meet',
        'Zoom' => 'Zoom',
        'Subscriptions' => 'الاشتراكات',
        'Payment History' => 'سجل المدفوعات',
        'Active Payment Method' => 'وسيلة الدفع الحالية',
        'Next Payment' => 'الدفعة القادمة',
        'Cancel Plan' => 'إلغاء الخطة',
        'Keep Plan' => 'الاحتفاظ بالخطة',
        'Renew Now' => 'تجديد الآن',
        'Renew' => 'تجديد',
        'Trial' => 'فترة تجريبية',
        'Access' => 'الوصول',
        'Gifted Course' => 'دورة مُهداه',
        'You have received a Gift from' => 'تلقيت هدية من',
        'Reveal Gift' => 'عرض الهدية',
    ) );
    return apply_filters( 'qalam_lms_dictionary', $map );
}

function qalam_translate_visible_string( $translation, $text, $domain = '' ) {
    if ( ! in_array( $domain, array( 'tutor', 'tutor-pro' ), true ) ) { return $translation; }
    $map = qalam_lms_dictionary();
    if ( isset( $map[ $text ] ) ) { return $map[ $text ]; }
    $translation = str_replace( array( 'Qalam LMS', 'Qalam LMS' ), 'Qalam LMS', $translation );
    return $translation;
}
add_filter( 'gettext', 'qalam_translate_visible_string', PHP_INT_MAX, 3 );

function qalam_translate_visible_string_context( $translation, $text, $context, $domain ) {
    return qalam_translate_visible_string( $translation, $text, $domain );
}
add_filter( 'gettext_with_context', 'qalam_translate_visible_string_context', PHP_INT_MAX, 4 );

function qalam_translate_plural( $translation, $single, $plural, $number, $domain ) {
    $key = 1 === (int) $number ? $single : $plural;
    return qalam_translate_visible_string( $translation, $key, $domain );
}
add_filter( 'ngettext', 'qalam_translate_plural', PHP_INT_MAX, 5 );

function qalam_addon_branding( $addons ) {
    if ( ! is_array( $addons ) ) { return $addons; }
    $labels = array(
        'tutor-assignments' => array( 'واجبات قلم', 'إنشاء الواجبات واستلام تسليمات الطلاب وتقييمها داخل قلم.' ),
        'tutor-certificate' => array( 'شهادات قلم', 'إصدار شهادات للطلاب عند إكمال الدورات.' ),
        'gradebook' => array( 'سجل الدرجات', 'إدارة درجات الاختبارات والواجبات والطلاب.' ),
        'tutor-report' => array( 'تقارير قلم', 'تقارير وتحليلات الدورات والطلاب والأداء.' ),
        'tutor-email' => array( 'بريد قلم', 'إرسال رسائل وتنبيهات بريدية مرتبطة بأحداث التعلم.' ),
        'tutor-notifications' => array( 'إشعارات قلم', 'إشعارات فورية للطلاب والمعلمين داخل المنصة.' ),
        'tutor-multi-instructors' => array( 'تعدد المعلمين', 'إضافة أكثر من معلم إلى الدورة وإدارة تعاونهم.' ),
        'course-bundle' => array( 'حزم الدورات', 'جمع عدة دورات في حزمة واحدة.' ),
        'subscription' => array( 'الاشتراكات', 'إدارة خطط الاشتراك والوصول المتكرر.' ),
        'tutor-zoom' => array( 'تكامل Zoom', 'ربط قلم بخدمة Zoom لإنشاء الحصص المباشرة.' ),
        'google-meet' => array( 'Google Meet', 'ربط قلم بخدمة Google Meet للحصص والاجتماعات المباشرة.' ),
        'google-classroom' => array( 'Google Classroom', 'تكامل قلم مع Google Classroom.' ),
        'calendar' => array( 'تقويم قلم', 'عرض مواعيد الدروس والاختبارات والواجبات والحصص.' ),
        'content-bank' => array( 'بنك المحتوى', 'إدارة الدروس والمحتوى القابل لإعادة الاستخدام بين الدورات.' ),
        'quiz-import-export' => array( 'استيراد/تصدير الاختبارات', 'نقل الاختبارات والأسئلة بين المواقع والنسخ.' ),
        'tutor-course-preview' => array( 'معاينة الدورة', 'إتاحة معاينة أجزاء محددة من الدورة قبل التسجيل.' ),
        'tutor-course-attachments' => array( 'مرفقات الدورة', 'إضافة ملفات ومصادر قابلة للتنزيل إلى الدورات.' ),
        'content-drip' => array( 'التدرج في المحتوى', 'جدولة إتاحة الدروس والمحتوى تدريجيًا.' ),
        'tutor-prerequisites' => array( 'المتطلبات السابقة', 'تحديد دورات يجب إكمالها قبل بدء دورة أخرى.' ),
        'enrollments' => array( 'التسجيلات', 'إدارة تسجيل الطلاب وفترات صلاحية الوصول.' ),
        'social-login' => array( 'تسجيل الدخول الاجتماعي', 'تمكين تسجيل الدخول بالحسابات الاجتماعية المدعومة.' ),
        'auth' => array( 'أمان الحساب', 'خيارات مصادقة وأمان إضافية للحسابات.' )
    );
    foreach ( $addons as $basename => &$addon ) {
        foreach ( $labels as $needle => $data ) {
            if ( false !== strpos( (string) $basename, '/'.$needle.'/' ) || false !== strpos( (string) $basename, $needle.'/'.$needle.'.php' ) ) {
                $addon['name'] = $data[0]; $addon['description'] = $data[1]; break;
            }
        }
        if ( isset( $addon['name'] ) ) { $addon['name'] = str_replace( array( 'Qalam LMS', 'Tutor' ), array( 'Qalam LMS', 'Qalam' ), $addon['name'] ); }
        if ( isset( $addon['description'] ) ) { $addon['description'] = str_replace( array( 'Qalam LMS', 'Tutor' ), array( 'Qalam LMS', 'Qalam' ), $addon['description'] ); }
    }
    unset( $addon );
    return $addons;
}
add_filter( 'tutor_addons_lists_config', 'qalam_addon_branding', PHP_INT_MAX );

function qalam_prepare_marketplace_defaults(): void {
    $options = get_option( 'tutor_option', array() );
    $options = is_array( $options ) ? $options : array();
    if ( ! isset( $options['enable_course_marketplace'] ) || 'on' !== $options['enable_course_marketplace'] ) {
        $options['enable_course_marketplace'] = 'on';
        update_option( 'tutor_option', $options, false );
    }
}
// Since 0.18, the marketplace is controlled from Qalam Add-ons/SaaS entitlements.
// Do not force it on every request; existing sites keep their saved option during upgrade.

function qalam_force_course_builder_brand( $data ) {
    if ( ! is_array( $data ) ) { return $data; }
    if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) { $data['settings'] = array(); }
    $options = get_option( 'tutor_option', array() );
    $options = is_array( $options ) ? $options : array();
    $data['settings']['course_builder_logo_url'] = plugin_dir_url( TUTOR_FILE ) . 'assets/images/qalam-logo.svg';
    $data['settings']['qalam_ai_provider'] = sanitize_key( (string) ( $options['qalam_ai_provider'] ?? 'openai' ) );
    $data['settings']['qalam_ai_model'] = sanitize_text_field( (string) ( $options['qalam_ai_model'] ?? '' ) );
    $data['settings']['qalam_ai_base_url'] = esc_url_raw( (string) ( $options['qalam_ai_base_url'] ?? '' ) );
    return $data;
}
add_filter( 'tutor_course_builder_localized_data', 'qalam_force_course_builder_brand', PHP_INT_MAX );


function qalam_enqueue_l10n_early(): void {
    if ( is_admin() && ! qalam_is_product_admin_surface() ) { return; }
    if ( ! function_exists( 'tutor' ) && ! defined( 'TUTOR_VERSION' ) ) { return; }
    $base = plugin_dir_url( TUTOR_FILE );
    wp_enqueue_script( 'qalam-lms-ui', $base . 'assets/js/qalam-ui.js', array( 'wp-i18n' ), QALAM_LMS_UI_VERSION, true );
    wp_localize_script( 'qalam-lms-ui', 'QalamL10n', array( 'dictionary' => qalam_lms_dictionary(), 'brand' => 'Qalam LMS', 'logo' => $base . 'assets/images/qalam-logo.svg', 'version' => QALAM_LMS_UI_VERSION ) );
}
add_action( 'admin_enqueue_scripts', 'qalam_enqueue_l10n_early', 1 );
add_action( 'wp_enqueue_scripts', 'qalam_enqueue_l10n_early', 1 );

/** Qalam multi-provider AI modal enhancer. Never localize API secrets. */
function qalam_enqueue_ai_provider_ui(): void {
    if ( ! is_admin() || ! qalam_is_product_admin_surface() ) { return; }
    $base = plugin_dir_url( TUTOR_FILE );
    $options = get_option( 'tutor_option', array() );
    $options = is_array( $options ) ? $options : array();
    wp_enqueue_script( 'qalam-ai-providers', $base . 'assets/js/qalam-ai-providers.js', array(), QALAM_LMS_UI_VERSION, true );
    wp_localize_script( 'qalam-ai-providers', 'QalamAIConfig', array(
        'provider' => sanitize_key( (string) ( $options['qalam_ai_provider'] ?? 'openai' ) ),
        'model' => sanitize_text_field( (string) ( $options['qalam_ai_model'] ?? '' ) ),
        'base_url' => esc_url_raw( (string) ( $options['qalam_ai_base_url'] ?? '' ) ),
        'key_exists' => ! empty( $options['chatgpt_api_key'] ),
        'presets' => array(
            'openai' => array( 'label' => 'OpenAI', 'model' => 'gpt-4o' ),
            'deepseek' => array( 'label' => 'DeepSeek', 'model' => 'deepseek-v4-flash' ),
            'openrouter' => array( 'label' => 'OpenRouter', 'model' => 'openai/gpt-4o' ),
            'google' => array( 'label' => 'Google AI Studio', 'model' => 'gemini-3.5-flash' ),
            'custom' => array( 'label' => 'مزود مخصص', 'model' => '' ),
        ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'qalam_enqueue_ai_provider_ui', 2 );

function qalam_enqueue_brand_css(): void {
    if ( is_admin() && ! qalam_is_product_admin_surface() ) { return; }
    $base = plugin_dir_url( TUTOR_FILE );
    wp_enqueue_style( 'qalam-lms-brand', $base . 'assets/css/qalam-brand.css', array(), QALAM_LMS_UI_VERSION );
}
add_action( 'admin_enqueue_scripts', 'qalam_enqueue_brand_css', PHP_INT_MAX );
add_action( 'wp_enqueue_scripts', 'qalam_enqueue_brand_css', PHP_INT_MAX );



/** Qalam student experience: visual layer only, Tutor runtime remains unchanged. */
function qalam_enqueue_student_experience(): void {
    if ( is_admin() ) {
        return;
    }
    $base = plugin_dir_url( TUTOR_FILE );
    wp_enqueue_style( 'qalam-lms-student', $base . 'assets/css/qalam-student.css', array( 'qalam-lms-brand' ), QALAM_LMS_UI_VERSION );
    wp_enqueue_style( 'qalam-lms-video-player', $base . 'assets/css/qalam-video-player.css', array( 'qalam-lms-student' ), QALAM_LMS_UI_VERSION );
    wp_enqueue_script( 'qalam-lms-video-player', $base . 'assets/js/qalam-video-player.js', array(), QALAM_LMS_UI_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'qalam_enqueue_student_experience', PHP_INT_MAX );

function qalam_student_body_class( $classes ) {
    if ( is_admin() ) {
        return $classes;
    }
    $is_student = class_exists( '\\TUTOR\\User' ) && \TUTOR\User::is_student_view();
    $is_learning = false;
    if ( function_exists( 'is_single_course' ) && is_single_course() ) {
        $is_learning = true;
    }
    if ( function_exists( 'tutor_utils' ) && method_exists( tutor_utils(), 'is_tutor_frontend_dashboard' ) && tutor_utils()->is_tutor_frontend_dashboard() ) {
        $is_learning = true;
    }
    if ( $is_student || $is_learning ) {
        $classes[] = 'qalam-student-ui';
    }
    return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'qalam_student_body_class', PHP_INT_MAX );


/** Prevent WordPress.org from overwriting the direct fork because the internal slug remains tutor for compatibility. */
function qalam_block_upstream_tutor_update( $transient ) {
    if ( is_object( $transient ) && isset( $transient->response ) && is_array( $transient->response ) ) {
        unset( $transient->response[ plugin_basename( TUTOR_FILE ) ] );
    }
    return $transient;
}
add_filter( 'site_transient_update_plugins', 'qalam_block_upstream_tutor_update', PHP_INT_MAX );
add_filter( 'auto_update_plugin', function ( $update, $item ) {
    $plugin = is_object( $item ) && isset( $item->plugin ) ? (string) $item->plugin : '';
    return plugin_basename( TUTOR_FILE ) === $plugin ? false : $update;
}, PHP_INT_MAX, 2 );

function qalam_plugin_row_meta( $links, $file ) {
    if ( in_array( $file, array( plugin_basename( TUTOR_FILE ), defined( 'TUTOR_PRO_FILE' ) ? plugin_basename( TUTOR_PRO_FILE ) : '' ), true ) ) {
        $links = array_values( array_filter( (array) $links, function ( $link ) {
            return false === stripos( (string) $link, 'tutorlms.com' ) && false === stripos( (string) $link, 'themeum.com' );
        } ) );
    }
    return $links;
}
add_filter( 'plugin_row_meta', 'qalam_plugin_row_meta', PHP_INT_MAX, 2 );

function qalam_admin_footer( $text ) {
    if ( isset( $_GET['page'] ) && false !== strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'tutor' ) ) {
        return 'بكل فخر ❤️ صنع في مصر — Qalam LMS';
    }
    if ( isset( $_GET['post_type'] ) && 'courses' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) {
        return 'بكل فخر ❤️ صنع في مصر — Qalam LMS';
    }
    return $text;
}
add_filter( 'admin_footer_text', 'qalam_admin_footer', PHP_INT_MAX );
