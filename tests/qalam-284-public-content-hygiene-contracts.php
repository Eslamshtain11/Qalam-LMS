<?php
$root = dirname(__DIR__);
$files = array(
    'qalam/release-230.php',
    'qalam/release-240.php',
    'qalam/release-270.php',
    'qalam/release-280.php',
);
$forbidden = array(
    'يظهر عند إضافة التصنيف',
    'تظهر عند إضافة التصنيف',
    'تتحدث تلقائيًا',
    'تتنظم تلقائيًا حسب التصنيفات',
    'ستظهر تلقائيًا',
    'هيظهروا هنا تلقائيًا',
    'أضف حسابات المدرسين من لوحة قلم',
    'أضف المدرسين من لوحة قلم',
    'أنشئ تصنيفات للكورسات',
    'أضف صورة مستقلة للنبذة من استوديو تصميم قلم',
    'صورة النبذة مستقلة عن صورة الـHero',
    'القسم جاهز لعرض نتائج المتفوقين',
    'قسم جاهز لعرض المتفوقين',
    'شرح منظم ومتابعة مستمرة للطلاب.',
);
$bad = array();
foreach ( $files as $rel ) {
    $s = file_get_contents( $root . '/' . $rel );
    foreach ( $forbidden as $needle ) {
        if ( false !== strpos( $s, $needle ) ) {
            $bad[] = $rel . ': ' . $needle;
        }
    }
}
if ( $bad ) {
    fwrite( STDERR, "FAIL public copy hygiene\n" . implode( "\n", $bad ) . "\n" );
    exit( 1 );
}
$s240 = file_get_contents( $root . '/qalam/release-240.php' );
$s280 = file_get_contents( $root . '/qalam/release-280.php' );
$checks = array(
    'no fake grade cards' => false === strpos( $s240, '<h3>الصف الأول</h3>' )
        && false === strpos( $s240, '<h3>الصف الثاني</h3>' )
        && false === strpos( $s240, '<h3>الصف الثالث</h3>' ),
    'grade section hides without terms' => false !== strpos( $s240, 'if ( ! $categories ) { return; }' ),
    'subjects hide without categories' => false !== strpos( $s280, '<?php if ( $categories ) : ?><section class="q28-section q28-white" id="subjects">' ),
    'about media only when real image' => false !== strpos( $s280, "if ( ! empty( \$brand['about_image_url'] ) )" ),
    'fake honor removed' => false === strpos( $s280, 'q28-honor-shell' ),
    'picker hides with no real choices' => false !== strpos( $s280, 'if ( ! $instructors && ! $categories ) { return; }' ),
    'hero copy is not reused as teacher bio' => false === strpos( $s280, "teacher_bio'] ?: \$brand['hero_text" ),
);
foreach ( $checks as $name => $ok ) {
    if ( ! $ok ) {
        fwrite( STDERR, "FAIL {$name}\n" );
        exit( 1 );
    }
}
echo "PASS qalam-284-public-content-hygiene\n";
