<?php
/**
 * Qalam 0.8.0 — resilient question generation, exact student previews,
 * random-bank quiz assembly and dynamic per-attempt exams.
 *
 * @package QalamLMS
 */

defined( 'ABSPATH' ) || exit;

const QALAM_080_JOB_PREFIX             = 'qalam_080_gen_';
const QALAM_080_JOB_TTL                = 2 * HOUR_IN_SECONDS;
const QALAM_080_BATCH_SIZE             = 5;
const QALAM_080_MAX_STALLS             = 6;
const QALAM_080_DYNAMIC_META           = '_qalam_dynamic_exam';
const QALAM_080_DYNAMIC_RULES_META     = '_qalam_dynamic_exam_rules';
const QALAM_080_DYNAMIC_PARENT_META    = '_qalam_dynamic_parent_quiz';
const QALAM_080_DYNAMIC_USER_META      = '_qalam_dynamic_user_id';
const QALAM_080_DYNAMIC_CONTENTS_META  = '_qalam_dynamic_content_ids';
const QALAM_080_PREVIEW_META           = '_qalam_student_preview_quiz';
const QALAM_080_USAGE_OPTION_PREFIX    = 'qalam_dynamic_usage_';
const QALAM_080_HISTORY_USERMETA       = '_qalam_dynamic_history';
const QALAM_080_DIFFICULTY_REASON_META = '_qalam_question_difficulty_reason';

/** Exact Arabic additions for the learner quiz experience and generator. */
function qalam_080_dictionary( $map ) {
	$extra = array(
		'Start Quiz' => 'ابدأ الاختبار',
		'Skip Quiz' => 'تخطي الاختبار',
		'Skip Question' => 'تخطي السؤال',
		'Write your answer here' => 'اكتب إجابتك هنا',
		'No background image configured for this Image Marking question.' => 'السؤال ده ناقص صورة الخلفية المطلوبة للتحديد على الصورة.',
		'No background image configured for this Pin question.' => 'السؤال ده ناقص صورة الخلفية المطلوبة لتحديد النقطة.',
		'No source image configured for this Puzzle question.' => 'السؤال ده ناقص الصورة المطلوبة للـ Puzzle.',
		'Move the scale left or right to set the correct value' => 'حرّك المقياس لليمين أو الشمال وحدد القيمة الصحيحة',
		'Interactive scale: drag or use arrow keys to select your answer value.' => 'حرّك المقياس أو استخدم الأسهم لاختيار إجابتك.',
		'Question' => 'السؤال',
		'Questions' => 'الأسئلة',
		'Total Marks' => 'إجمالي الدرجات',
		'Passing Grade' => 'درجة النجاح',
		'Preview' => 'معاينة',
	);
	return array_merge( (array) $map, $extra );
}
add_filter( 'qalam_lms_dictionary', 'qalam_080_dictionary', 90 );

/** Helpers. */
function qalam_080_job_key( string $id ): string {
	return QALAM_080_JOB_PREFIX . sanitize_key( $id );
}
function qalam_080_get_job( string $id ): array {
	$job = get_transient( qalam_080_job_key( $id ) );
	return is_array( $job ) ? $job : array();
}
function qalam_080_put_job( string $id, array $job ): void {
	set_transient( qalam_080_job_key( $id ), $job, QALAM_080_JOB_TTL );
}
function qalam_080_delete_job( string $id ): void {
	$job = qalam_080_get_job( $id );
	if ( ! empty( $job['pdf_path'] ) && is_string( $job['pdf_path'] ) && is_file( $job['pdf_path'] ) ) {
		@unlink( $job['pdf_path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
	delete_transient( qalam_080_job_key( $id ) );
}
function qalam_080_requested_counts_from_request(): array {
	$out = array();
	foreach ( qalam_070_ai_question_types() as $slug => $label ) {
		$out[ $slug ] = min( 100, max( 0, absint( $_POST['type_counts'][ $slug ] ?? 0 ) ) );
	}
	return $out;
}
function qalam_080_next_batch_counts( array $remaining, int $limit = QALAM_080_BATCH_SIZE ): array {
	$batch = array_fill_keys( array_keys( $remaining ), 0 );
	while ( $limit > 0 && array_sum( $remaining ) > 0 ) {
		$advanced = false;
		foreach ( $remaining as $type => $count ) {
			if ( $limit <= 0 ) { break; }
			if ( $count > $batch[ $type ] ) {
				++$batch[ $type ];
				--$limit;
				$advanced = true;
			}
		}
		if ( ! $advanced ) { break; }
	}
	return array_filter( $batch );
}
function qalam_080_valid_bbox( $bbox ): bool {
	if ( ! is_array( $bbox ) || 4 !== count( $bbox ) ) { return false; }
	$v = array_map( 'floatval', array_values( $bbox ) );
	return $v[2] > $v[0] && $v[3] > $v[1] && $v[0] >= 0 && $v[1] >= 0 && $v[2] <= 1000 && $v[3] <= 1000;
}
function qalam_080_valid_point( $point ): bool {
	return is_array( $point ) && count( $point ) >= 2 && is_numeric( $point[0] ) && is_numeric( $point[1] ) && (float) $point[0] >= 0 && (float) $point[0] <= 1000 && (float) $point[1] >= 0 && (float) $point[1] <= 1000;
}
function qalam_080_question_fingerprint( array $item ): string {
	$title = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', mb_strtolower( (string) ( $item['question_title'] ?? '' ), 'UTF-8' ) );
	return md5( trim( preg_replace( '/\s+/u', ' ', $title ) ) );
}

/**
 * Production system prompt. It explicitly separates extraction from authoring,
 * defines difficulty by cognitive demand, and forbids random image crops.
 */
function qalam_080_ai_prompt( array $counts, string $mode, string $difficulty, string $instructions, array $previous_titles = array() ): string {
	$requested = array_filter( array_map( 'absint', $counts ) );
	$requested_json = wp_json_encode( $requested, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$prior = array_slice( array_values( array_filter( array_map( 'sanitize_text_field', $previous_titles ) ) ), -50 );
	$prior_json = wp_json_encode( $prior, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$is_extract = 'pdf_extract' === $mode;
	$is_pdf     = 0 === strpos( $mode, 'pdf_' );

	$difficulty_rule = 'mixed' === $difficulty
		? "صنّف صعوبة كل سؤال بعد صياغته، ولا توزع المستويات عشوائيًا. easy = معرفة مباشرة أو خطوة واحدة واضحة. medium = تطبيق/ربط مفهومين أو خطوتين إلى ثلاث خطوات. hard = استدلال متعدد الخطوات أو دمج مفاهيم أو تمييز بين بدائل متقاربة. أعد difficulty وdifficulty_reason لكل سؤال."
		: "كل الأسئلة المطلوبة difficulty={$difficulty}. برر التصنيف في difficulty_reason باختصار.";

	$source_rule = $is_extract
		? "الوضع EXTRACT: استخرج الأسئلة الموجودة فعلًا في PDF بأمانة. لا تخترع أسئلة جديدة، ولا تغيّر المعنى أو الأرقام أو الوحدات. لو نوع السؤال الأصلي لا يطابق نوعًا مطلوبًا، لا تحشره بالقوة؛ تخطّه."
		: ( $is_pdf
			? "الوضع AUTHOR-FROM-PDF: الـPDF مصدر معرفة فقط. أنشئ أسئلة جديدة سليمة تربويًا من مفاهيمه. عند التحويل لنوع مختلف أعد صياغة السؤال بالكامل بما يناسب تفاعل ذلك النوع، ولا تنسخ شكل السؤال القديم بشكل مشوه."
			: "الوضع AUTHOR: أنشئ أسئلة جديدة من التعليمات فقط، بدون افتراض صور أو صفحات غير موجودة." );

	return <<<PROMPT
أنت Qalam Question Engine، محرر امتحانات عربي متخصص. أخرج JSON صالح فقط بلا Markdown ولا أي شرح خارج JSON.

الهدف العددي لهذه الدفعة بالضبط: {$requested_json}
الأسئلة السابقة الممنوع تكرار فكرتها أو صياغتها: {$prior_json}

قواعد المصدر:
{$source_rule}

قواعد الجودة الإلزامية:
1) السؤال لازم يكون مكتمل المعنى وقابل للإجابة من البيانات الموجودة، ولا تستخدم نصوص مبتورة أو إشارات غامضة مثل "كما بالشكل" من غير الشكل المناسب.
2) لا تخلط سؤالًا مع صورة من سؤال آخر. image_bbox يستخدم فقط إذا الصورة داخل الـPDF مرتبطة دلاليًا بهذا السؤال ولازم تكون ضرورية لفهمه. وإلا اجعله null.
3) لو أعدت صياغة سؤال من PDF لنوع لا يحتاج صورة، لا تُرفق أي crop حتى لو السؤال الأصلي كان بجواره رسم.
4) للأسئلة التي تحتاج صورة فعلًا (image_answering, draw_image, pin_image, puzzle): لا تنشئ السؤال إلا لو وجدت صورة/رسم مناسب واضح في المصدر، وحدد source_page وimage_bbox بدقة. وإلا لا ترجع عنصرًا لهذا النوع في هذه الدفعة.
5) draw_image: أعد target_bbox داخل الصورة نفسها normalized 0..1000 لتحديد المنطقة الصحيحة التي يجب على الطالب تظليلها.
6) pin_image: أعد target_point داخل الصورة نفسها normalized 0..1000 لموضع الإجابة الصحيح.
7) coordinates: أعد coordinates_points من 1 إلى 5 نقاط صحيحة بأعداد صحيحة داخل -10..10 أو -20..20، وحدد coordinates_axis_range = 10 أو 20.
8) scale: أعد min,max,step,value بحيث min < value < max والقيم منطقية للسؤال.
9) single_choice: من 4 إلى 5 اختيارات، إجابة صحيحة واحدة فقط بالضبط، مشتتات منطقية.
10) multiple_choice: من 4 إلى 6 اختيارات، إجابتان صحيحتان أو أكثر، ويجب أن تكون صياغة السؤال واضحة أنه يسمح بأكثر من إجابة.
10) true_false: عبارة واحدة واضحة وcorrect=true/false.
11) fill_in_the_blank: استخدم {dash} مكان كل فراغ، وblanks بنفس ترتيب الفراغات.
12) matching: من 3 إلى 6 أزواج غير ملتبسة.
13) ordering: من 3 إلى 7 خطوات في ترتيب صحيح منطقي.
14) image_answering: الصورة هي المطلوب تفسيرها/تسميتها، وأعد answers وفي أول عنصر النص الصحيح المتوقع.
15) puzzle: استخدم صورة ذات معنى تعليمي وليست قصاصة نص عشوائية.
16) لا تولّد سؤالًا من نوع تفاعلي ببيانات ناقصة. إذا تعذر النوع، اتركه ناقصًا وسيطلب النظام استكماله في دفعة لاحقة.
17) تجنب التكرار الدلالي حتى لو اختلفت الكلمات.
18) صياغة عربية واضحة، والأرقام والوحدات والرموز العلمية تُحفظ بدقة.

قواعد الصعوبة:
{$difficulty_rule}

تعليمات المستخدم:
{$instructions}

الأنواع المسموحة فقط:
true_false,single_choice,multiple_choice,open_ended,fill_in_the_blank,short_answer,matching,image_matching,image_answering,ordering,draw_image,scale,pin_image,coordinates,puzzle

JSON schema المطلوب:
{
  "questions": [
    {
      "question_type":"multiple_choice",
      "question_title":"...",
      "question_description":"...",
      "question_mark":1,
      "difficulty":"easy|medium|hard",
      "difficulty_reason":"سبب مختصر",
      "correct":true,
      "answers":[{"text":"...","correct":true,"match":""}],
      "blanks":["..."],
      "pairs":[{"left":"...","right":"..."}],
      "scale":{"min":0,"max":100,"step":1,"value":50},
      "coordinates_points":[{"x":2,"y":3}],
      "coordinates_axis_range":10,
      "source_page":1,
      "image_bbox":[100,100,800,700],
      "target_bbox":[200,200,500,500],
      "target_point":[500,500],
      "uses_source_image":false
    }
  ]
}

image_bbox إحداثيات على صفحة PDF normalized 0..1000. target_bbox وtarget_point normalized داخل الصورة المقصوصة نفسها 0..1000. استخدم null بدل [0,0,0,0].
PROMPT;
}

/** Validate model item before persistence. Invalid interactive data is rejected, never saved broken. */
function qalam_080_validate_item( array $item, bool $has_pdf ): array {
	$allowed = array_keys( qalam_070_ai_question_types() );
	$type = sanitize_key( (string) ( $item['question_type'] ?? '' ) );
	if ( ! in_array( $type, $allowed, true ) ) { throw new RuntimeException( 'نوع سؤال غير مدعوم.' ); }
	$title = trim( wp_strip_all_tags( (string) ( $item['question_title'] ?? '' ) ) );
	if ( mb_strlen( $title, 'UTF-8' ) < 8 ) { throw new RuntimeException( 'عنوان السؤال ناقص أو قصير جدًا.' ); }
	if ( preg_match( '/(?:سؤال بدون عنوان|ضع السؤال هنا|lorem ipsum)/iu', $title ) ) { throw new RuntimeException( 'الموديل رجّع Placeholder بدل سؤال.' ); }

	$difficulty = sanitize_key( (string) ( $item['difficulty'] ?? '' ) );
	if ( ! in_array( $difficulty, array( 'easy', 'medium', 'hard' ), true ) ) { throw new RuntimeException( 'السؤال بلا تصنيف صعوبة صالح.' ); }
	$item['difficulty'] = $difficulty;
	$item['difficulty_reason'] = sanitize_text_field( (string) ( $item['difficulty_reason'] ?? '' ) );

	$answers = is_array( $item['answers'] ?? null ) ? $item['answers'] : array();
	if ( in_array( $type, array( 'single_choice', 'multiple_choice' ), true ) ) {
		if ( count( $answers ) < 4 || count( $answers ) > 6 ) { throw new RuntimeException( 'سؤال الاختيارات لازم يكون 4–6 اختيارات.' ); }
		$correct = 0;
		foreach ( $answers as $a ) { if ( is_array( $a ) && ! empty( $a['correct'] ) ) { ++$correct; } }
		if ( 'single_choice' === $type && 1 !== $correct ) { throw new RuntimeException( 'اختيار واحد لازم يحتوي إجابة صحيحة واحدة بالضبط.' ); }
		if ( 'multiple_choice' === $type && $correct < 2 ) { throw new RuntimeException( 'الاختيارات المتعددة لازم تحتوي إجابتين صحيحتين على الأقل.' ); }
	}
	if ( 'true_false' === $type && ! array_key_exists( 'correct', $item ) && empty( $answers ) ) { throw new RuntimeException( 'صح/خطأ بلا إجابة صحيحة.' ); }
	if ( 'fill_in_the_blank' === $type ) {
		$blanks = is_array( $item['blanks'] ?? null ) ? array_values( array_filter( $item['blanks'], 'strlen' ) ) : array();
		if ( ! $blanks || substr_count( (string) $item['question_title'], '{dash}' ) !== count( $blanks ) ) { throw new RuntimeException( 'سؤال أكمل غير متوافق بين الفراغات والإجابات.' ); }
	}
	if ( 'matching' === $type && count( (array) ( $item['pairs'] ?? array() ) ) < 3 ) { throw new RuntimeException( 'سؤال التوصيل محتاج 3 أزواج على الأقل.' ); }
	if ( 'ordering' === $type && count( $answers ) < 3 ) { throw new RuntimeException( 'سؤال الترتيب محتاج 3 عناصر على الأقل.' ); }
	if ( 'scale' === $type ) {
		$s = is_array( $item['scale'] ?? null ) ? $item['scale'] : array();
		$min = (float) ( $s['min'] ?? 0 ); $max = (float) ( $s['max'] ?? 0 ); $value = (float) ( $s['value'] ?? 0 ); $step = (float) ( $s['step'] ?? 0 );
		if ( $max <= $min || $value < $min || $value > $max || $step <= 0 ) { throw new RuntimeException( 'بيانات سؤال المقياس غير صالحة.' ); }
	}
	if ( 'coordinates' === $type ) {
		$points = is_array( $item['coordinates_points'] ?? null ) ? $item['coordinates_points'] : array();
		$axis = 20 === absint( $item['coordinates_axis_range'] ?? 10 ) ? 20 : 10;
		if ( ! $points || count( $points ) > 5 ) { throw new RuntimeException( 'سؤال الرسم البياني بلا نقاط صحيحة.' ); }
		foreach ( $points as $p ) {
			if ( ! is_array( $p ) || ! isset( $p['x'], $p['y'] ) || ! is_numeric( $p['x'] ) || ! is_numeric( $p['y'] ) || (int) $p['x'] != $p['x'] || (int) $p['y'] != $p['y'] || abs( (int) $p['x'] ) > $axis || abs( (int) $p['y'] ) > $axis ) {
				throw new RuntimeException( 'إحداثيات سؤال الرسم خارج النطاق.' );
			}
		}
	}
	$image_types = array( 'image_answering', 'draw_image', 'pin_image', 'puzzle' );
	if ( in_array( $type, $image_types, true ) ) {
		if ( ! $has_pdf || ! qalam_080_valid_bbox( $item['image_bbox'] ?? null ) ) { throw new RuntimeException( 'نوع السؤال ده يحتاج صورة مرتبطة فعلًا من PDF.' ); }
		$item['uses_source_image'] = true;
	}
	if ( 'draw_image' === $type && ! qalam_080_valid_bbox( $item['target_bbox'] ?? null ) ) { throw new RuntimeException( 'Image Marking محتاج target_bbox صالح داخل الصورة.' ); }
	if ( 'pin_image' === $type && ! qalam_080_valid_point( $item['target_point'] ?? null ) ) { throw new RuntimeException( 'Pin محتاج target_point صالح داخل الصورة.' ); }
	if ( 'image_answering' === $type && empty( $answers[0]['text'] ) ) { throw new RuntimeException( 'Image Answering محتاج إجابة نصية صحيحة للصورة.' ); }
	if ( ! in_array( $type, $image_types, true ) && empty( $item['uses_source_image'] ) ) { $item['image_bbox'] = null; }
	return $item;
}

/** Create attachment for an exact PDF crop. Returns [attachment_id,width,height]. */
function qalam_080_pdf_crop_attachment( string $pdf_path, string $filename, int $page, array $bbox, string $title ): array {
	if ( ! class_exists( 'Imagick' ) || ! qalam_080_valid_bbox( $bbox ) || $page < 1 ) { return array( 0, 0, 0 ); }
	try {
		$im = new \Imagick();
		$im->setResolution( 180, 180 );
		$im->readImage( $pdf_path . '[' . ( $page - 1 ) . ']' );
		$im->setImageFormat( 'png' );
		$w = $im->getImageWidth(); $h = $im->getImageHeight();
		$v = array_map( 'floatval', array_values( $bbox ) );
		$x1 = (int) round( $v[0] / 1000 * $w ); $y1 = (int) round( $v[1] / 1000 * $h );
		$x2 = (int) round( $v[2] / 1000 * $w ); $y2 = (int) round( $v[3] / 1000 * $h );
		$cw = max( 1, $x2 - $x1 ); $ch = max( 1, $y2 - $y1 );
		$mx = max( 32, (int) round( $cw * .08 ) ); $my = max( 32, (int) round( $ch * .08 ) );
		$cx = max( 0, $x1 - $mx ); $cy = max( 0, $y1 - $my );
		$cw = min( $w - $cx, $cw + 2 * $mx ); $ch = min( $h - $cy, $ch + 2 * $my );
		$im->cropImage( $cw, $ch, $cx, $cy );
		$uploads = wp_upload_dir(); if ( ! empty( $uploads['error'] ) ) { return array( 0, 0, 0 ); }
		$dir = trailingslashit( $uploads['basedir'] ) . 'qalam-question-crops'; wp_mkdir_p( $dir );
		$safe = sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) . '-p' . $page . '-' . wp_generate_password( 10, false, false ) . '.png' );
		$dest = trailingslashit( $dir ) . $safe; $im->writeImage( $dest );
		$out_w = $im->getImageWidth(); $out_h = $im->getImageHeight(); $im->clear();
		$type = wp_check_filetype( $safe, null );
		$att = wp_insert_attachment( array( 'post_mime_type' => $type['type'] ?: 'image/png', 'post_title' => sanitize_text_field( $title ), 'post_status' => 'inherit' ), $dest );
		if ( ! $att || is_wp_error( $att ) ) { return array( 0, 0, 0 ); }
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $dest ) );
		return array( (int) $att, (int) $out_w, (int) $out_h );
	} catch ( \Throwable $e ) { return array( 0, 0, 0 ); }
}

/** Build a transparent instructor mask and save it through Tutor Pro's native mask storage. */
function qalam_080_create_mask( int $width, int $height, string $type, array $item ): string {
	if ( $width < 2 || $height < 2 || ! class_exists( 'Imagick' ) || ! class_exists( '\TutorPro\Models\QuizModel' ) ) { return ''; }
	try {
		$im = new \Imagick(); $im->newImage( $width, $height, new \ImagickPixel( 'transparent' ), 'png' );
		$draw = new \ImagickDraw(); $draw->setFillColor( new \ImagickPixel( 'rgba(111,45,217,0.95)' ) );
		if ( 'pin_image' === $type ) {
			$p = array_map( 'floatval', array_values( $item['target_point'] ?? array( 500, 500 ) ) );
			$x = $p[0] / 1000 * $width; $y = $p[1] / 1000 * $height; $r = max( 12, min( $width, $height ) * .08 );
			$draw->circle( $x, $y, $x + $r, $y );
		} else {
			$b = array_map( 'floatval', array_values( $item['target_bbox'] ?? array( 250, 250, 750, 750 ) ) );
			$draw->rectangle( $b[0] / 1000 * $width, $b[1] / 1000 * $height, $b[2] / 1000 * $width, $b[3] / 1000 * $height );
		}
		$im->drawImage( $draw ); $blob = $im->getImageBlob(); $im->clear();
		$data = 'data:image/png;base64,' . base64_encode( $blob );
		return (string) \TutorPro\Models\QuizModel::save_quiz_draw_image_mask( $data, $type, array( 'data_status' => 'new' ) );
	} catch ( \Throwable $e ) { return ''; }
}

/** Better native payload for the generated item. */
function qalam_080_native_payload( array $item ): array {
	$payload = qalam_070_native_question_payload( $item );
	$type = $payload['question_type'];
	// Keep single-choice and multiple-choice semantically distinct in Qalam.
	if ( 'single_choice' === $type ) { $payload['question_settings']['has_multiple_correct_answer'] = '0'; }
	if ( 'multiple_choice' === $type ) { $payload['question_settings']['has_multiple_correct_answer'] = '1'; }
	if ( 'coordinates' === $type ) {
		$axis = 20 === absint( $item['coordinates_axis_range'] ?? 10 ) ? 20 : 10;
		$payload['question_settings']['coordinates_axis_range'] = $axis;
		$points = array();
		foreach ( (array) ( $item['coordinates_points'] ?? array() ) as $p ) { $points[] = array( 'x' => (int) $p['x'], 'y' => (int) $p['y'] ); }
		$payload['question_answers'] = array( array( '_data_status'=>'new','answer_title'=>'','is_correct'=>1,'answer_order'=>0,'answer_two_gap_match'=>wp_json_encode( $points ),'answer_view_format'=>'coordinates','image_id'=>null,'belongs_question_type'=>'coordinates' ) );
	}
	if ( 'image_answering' === $type ) {
		$answer_text = sanitize_text_field( (string) ( $item['answers'][0]['text'] ?? '' ) );
		$payload['question_answers'] = array( array( '_data_status'=>'new','answer_title'=>$answer_text,'is_correct'=>1,'answer_order'=>1,'answer_two_gap_match'=>'','answer_view_format'=>'text_image','image_id'=>null,'belongs_question_type'=>'image_answering' ) );
	}
	return $payload;
}

/** Attach the correct source media only to types that need it. */
function qalam_080_apply_source_media( array &$payload, array $item, string $pdf_path, string $pdf_name ): void {
	if ( ! $pdf_path || ! qalam_080_valid_bbox( $item['image_bbox'] ?? null ) || empty( $item['uses_source_image'] ) ) { return; }
	$page = max( 1, absint( $item['source_page'] ?? 1 ) );
	list( $att, $w, $h ) = qalam_080_pdf_crop_attachment( $pdf_path, $pdf_name, $page, $item['image_bbox'], $payload['question_title'] . ' — صورة السؤال' );
	if ( ! $att ) { return; }
	$type = $payload['question_type'];
	if ( in_array( $type, array( 'image_answering','draw_image','pin_image','puzzle' ), true ) ) {
		if ( empty( $payload['question_answers'] ) ) {
			$payload['question_answers'][] = array( '_data_status'=>'new','answer_title'=>'','is_correct'=>1,'answer_order'=>0,'answer_two_gap_match'=>'','answer_view_format'=>$type,'image_id'=>$att,'belongs_question_type'=>$type );
		} else {
			$payload['question_answers'][0]['image_id'] = $att;
			$payload['question_answers'][0]['answer_view_format'] = 'image_answering' === $type ? 'text_image' : $type;
		}
		if ( in_array( $type, array( 'draw_image','pin_image' ), true ) ) {
			$mask = qalam_080_create_mask( $w, $h, $type, $item );
			if ( $mask ) { $payload['question_answers'][0]['answer_two_gap_match'] = $mask; }
		}
		return;
	}
	$url = wp_get_attachment_image_url( $att, 'full' );
	if ( $url ) { $payload['question_description'] .= '<figure class="qalam-question-source-image"><img src="' . esc_url( $url ) . '" alt="صورة مرتبطة بالسؤال"></figure>'; }
}

/** Provider call with a per-request timeout below common nginx upstream limits. */
function qalam_080_generate_pdf_questions( string $path, string $filename, string $prompt ): array {
	if ( ! class_exists( '\TutorPro\TutorAI\Helper' ) ) { throw new RuntimeException( 'ميزة الذكاء الاصطناعي غير متاحة.' ); }
	$cfg = \TutorPro\TutorAI\Helper::get_ai_provider_config();
	$provider = sanitize_key( (string) ( $cfg['provider'] ?? '' ) ); $key = (string) ( $cfg['api_key'] ?? '' ); $model = (string) ( $cfg['model'] ?? '' );
	if ( ! $key || ! $model ) { throw new RuntimeException( 'فعّل مزود الذكاء الاصطناعي واختار موديل الأول.' ); }
	$bytes = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false === $bytes ) { throw new RuntimeException( 'تعذر قراءة PDF.' ); }
	$b64 = base64_encode( $bytes ); $headers = array( 'Content-Type' => 'application/json' ); $endpoint = ''; $body = array();
	if ( 'google' === $provider ) {
		$google_model = (string) preg_replace( '#^models/#i', '', trim( $model ) );
		$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $google_model ) . ':generateContent?key=' . rawurlencode( $key );
		$body = array( 'contents'=>array(array('parts'=>array(array('inline_data'=>array('mime_type'=>'application/pdf','data'=>$b64)),array('text'=>$prompt)))),'generationConfig'=>array('temperature'=>0.1,'responseMimeType'=>'application/json') );
	} elseif ( 'openrouter' === $provider ) {
		$endpoint='https://openrouter.ai/api/v1/chat/completions'; $headers['Authorization']='Bearer '.$key; $headers['HTTP-Referer']=home_url('/'); $headers['X-OpenRouter-Title']=get_bloginfo('name')?:'Qalam LMS';
		$body=array('model'=>$model,'temperature'=>0.1,'messages'=>array(array('role'=>'user','content'=>array(array('type'=>'text','text'=>$prompt),array('type'=>'file','file'=>array('filename'=>$filename,'file_data'=>'data:application/pdf;base64,'.$b64))))));
	} elseif ( 'openai' === $provider ) {
		$endpoint='https://api.openai.com/v1/responses'; $headers['Authorization']='Bearer '.$key;
		$body=array('model'=>$model,'input'=>array(array('role'=>'user','content'=>array(array('type'=>'input_file','filename'=>$filename,'file_data'=>'data:application/pdf;base64,'.$b64),array('type'=>'input_text','text'=>$prompt)))),'temperature'=>0.1);
	} else {
		throw new RuntimeException( 'المزود الحالي لا يدعم PDF مباشرة. استخدم Google AI Studio أو OpenAI أو OpenRouter.' );
	}
	$response = wp_safe_remote_post( $endpoint, array( 'timeout'=>120, 'redirection'=>1, 'sslverify'=>true, 'headers'=>$headers, 'body'=>wp_json_encode($body), 'data_format'=>'body' ) );
	if ( is_wp_error( $response ) ) { throw new RuntimeException( 'الاتصال بالمزود فشل: ' . $response->get_error_message() ); }
	$status=(int)wp_remote_retrieve_response_code($response); $raw=(string)wp_remote_retrieve_body($response); $json=json_decode($raw,true);
	if ( $status < 200 || $status >= 300 ) { $msg=is_array($json)?($json['error']['message']??$json['message']??'HTTP '.$status):'HTTP '.$status; throw new RuntimeException( 'فشل تحليل PDF: ' . sanitize_text_field((string)$msg) ); }
	$content='';
	if('google'===$provider){$content=(string)($json['candidates'][0]['content']['parts'][0]['text']??'');}
	elseif('openrouter'===$provider){$content=(string)($json['choices'][0]['message']['content']??'');}
	else{ if(isset($json['output_text'])){$content=(string)$json['output_text'];} if(!$content&&!empty($json['output'])&&is_array($json['output'])){foreach($json['output'] as $o){foreach((array)($o['content']??array()) as $part){if(isset($part['text'])){$content.=(string)$part['text'];}}}} }
	return qalam_070_decode_ai_json( $content );
}
function qalam_080_generate_text_questions( string $prompt ): array {
	if ( ! class_exists( '\TutorPro\TutorAI\Helper' ) ) { throw new RuntimeException( 'ميزة الذكاء الاصطناعي غير متاحة.' ); }
	$client = \TutorPro\TutorAI\Helper::get_openai_client();
	$response = $client->chat()->create( \TutorPro\TutorAI\Helper::create_openai_chat_input( array( array( 'role'=>'system','content'=>'You are Qalam Question Engine. Return strict JSON only.' ), array( 'role'=>'user','content'=>$prompt ) ), array( 'temperature'=>0.1 ) ) );
	$data = \TutorPro\TutorAI\Helper::check_openai_response( $response );
	$content = ! empty( $data->choices[0]->message->content ) ? (string) $data->choices[0]->message->content : '';
	return qalam_070_decode_ai_json( $content );
}

/** Start an async/resumable generation job. */
function qalam_080_ajax_start_generation() {
	if ( ! current_user_can( 'manage_tutor_instructor' ) ) { wp_send_json_error( array( 'message'=>'غير مسموح.' ), 403 ); }
	check_ajax_referer( 'qalam_070_generate_questions', 'qalam_070_ai_nonce' );
	$requested = qalam_080_requested_counts_from_request(); $total = array_sum( $requested );
	if ( $total < 1 ) { wp_send_json_error( array( 'message'=>'حدد عدد سؤال واحد على الأقل.' ), 400 ); }
	if ( $total > 100 ) { wp_send_json_error( array( 'message'=>'الحد الأقصى للعملية الواحدة 100 سؤال.' ), 400 ); }
	$mode=sanitize_key((string)($_POST['source_mode']??'prompt')); $difficulty=sanitize_key((string)($_POST['difficulty']??'mixed'));
	if(!in_array($difficulty,array('mixed','easy','medium','hard'),true)){$difficulty='mixed';}
	$job_id = wp_generate_uuid4(); $pdf_path=''; $pdf_name='';
	if(0===strpos($mode,'pdf_')){
		if(empty($_FILES['pdf_file']['tmp_name'])){wp_send_json_error(array('message'=>'اختار ملف PDF.'),400);}
		$file=$_FILES['pdf_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if((int)$file['size']>20*1024*1024){wp_send_json_error(array('message'=>'حجم PDF أكبر من 20MB.'),400);}
		$check=wp_check_filetype_and_ext($file['tmp_name'],$file['name'],array('pdf'=>'application/pdf')); if('pdf'!==($check['ext']??'')){wp_send_json_error(array('message'=>'الملف لازم يكون PDF صالح.'),400);}
		$pdf_path=wp_tempnam('qalam-'.$job_id.'.pdf'); if(!$pdf_path||!@move_uploaded_file($file['tmp_name'],$pdf_path)){wp_send_json_error(array('message'=>'تعذر حفظ PDF مؤقتًا.'),500);} // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$pdf_name=sanitize_file_name($file['name']);
	}
	$job=array(
		'id'=>$job_id,'user_id'=>get_current_user_id(),'requested'=>$requested,'remaining'=>$requested,'total'=>$total,'created'=>0,'created_ids'=>array(),'created_titles'=>array(),'fingerprints'=>array(),'rejected'=>0,'stalls'=>0,'status'=>'running','mode'=>$mode,'difficulty'=>$difficulty,'instructions'=>sanitize_textarea_field(wp_unslash($_POST['instructions']??'')),'term_id'=>absint($_POST['term_id']??0),'target'=>sanitize_key((string)($_POST['target']??'bank')),'quiz_id'=>absint($_POST['quiz_id']??0),'pdf_path'=>$pdf_path,'pdf_name'=>$pdf_name,'started'=>time(),'last_error'=>'',
	);
	$job['worker_errors']=0;$job['last_heartbeat']=time();
	qalam_080_put_job($job_id,$job);
	if(function_exists('qalam_081_schedule_generation_worker')){qalam_081_schedule_generation_worker($job_id,0);}
	wp_send_json_success(array('job_id'=>$job_id,'total'=>$total,'created'=>0,'message'=>'بدأ التوليد في الخلفية.'));
}
add_action('wp_ajax_qalam_080_start_generation','qalam_080_ajax_start_generation');

/** Process one small generation batch, persist immediately, and return progress. */
function qalam_080_ajax_process_generation() {
	if(!current_user_can('manage_tutor_instructor')){wp_send_json_error(array('message'=>'غير مسموح.'),403);}
	check_ajax_referer('qalam_080_process_generation','nonce');
	$id=sanitize_key((string)($_POST['job_id']??''));
	$job=qalam_080_get_job($id);
	if(!$job||($job['user_id']??0)!==get_current_user_id()){wp_send_json_error(array('message'=>'مهمة التوليد غير موجودة أو انتهت.'),404);}
	if(function_exists('qalam_081_schedule_generation_worker')&&'running'===(string)($job['status']??'running')){
		$heartbeat=(int)($job['last_heartbeat']??0);
		if(!$heartbeat||(time()-$heartbeat)>4){qalam_081_schedule_generation_worker($id,0);}
	}
	$payload=function_exists('qalam_081_generation_status_payload')?qalam_081_generation_status_payload($job):array('done'=>'complete'===($job['status']??''),'created'=>(int)($job['created']??0),'total'=>(int)($job['total']??0),'rejected'=>(int)($job['rejected']??0),'remaining'=>array_sum((array)($job['remaining']??array())),'message'=>(string)($job['last_error']??''));
	wp_send_json_success($payload);
}

add_action('wp_ajax_qalam_080_process_generation','qalam_080_ajax_process_generation');

function qalam_080_ajax_resume_generation(){if(!current_user_can('manage_tutor_instructor'))wp_send_json_error(array('message'=>'غير مسموح.'),403);check_ajax_referer('qalam_080_process_generation','nonce');$id=sanitize_key((string)($_POST['job_id']??''));$job=qalam_080_get_job($id);if(!$job||($job['user_id']??0)!==get_current_user_id())wp_send_json_error(array('message'=>'المهمة غير موجودة.'),404);$job['status']='running';$job['stalls']=0;$job['last_error']='';qalam_080_put_job($id,$job);wp_send_json_success(array('message'=>'تم استكمال المهمة.'));}
add_action('wp_ajax_qalam_080_resume_generation','qalam_080_ajax_resume_generation');

/** Question bank exact learner preview: create a one-question hidden quiz and open Tutor's real learner UI. */
function qalam_080_question_preview_route(){
	if(empty($_GET['qalam_question_preview']))return; if(!is_user_logged_in()||!current_user_can('manage_tutor_instructor')){auth_redirect();exit;}
	$content_id=absint($_GET['qalam_question_preview']); if(!$content_id||'cb-question'!==get_post_type($content_id)){wp_die('السؤال غير موجود.');}
	$container=qalam_060_general_quiz_container(); $builder=new \TUTOR\QuizBuilder(false); $source=get_post($content_id);
	$result=$builder->save_quiz($container['topic_id'],array('post_title'=>'معاينة الطالب — '.($source?$source->post_title:'سؤال'),'post_content'=>'','quiz_option'=>array('passing_grade'=>0,'limit_attempts_allowed'=>'0','attempts_allowed'=>0,'time_limit'=>array('time_value'=>0,'time_type'=>'minutes'),'questions_order'=>'sorting'),'questions'=>array()));
	if(empty($result->success)||empty($result->data))wp_die('تعذر إنشاء معاينة الطالب.'); $quiz_id=absint($result->data); update_post_meta($quiz_id,QALAM_GENERAL_QUIZ_META,'1'); update_post_meta($quiz_id,QALAM_080_PREVIEW_META,'1'); qalam_070_copy_content_questions_to_quiz($quiz_id,array($content_id)); qalam_060_prepare_general_quiz_access(); wp_safe_redirect(add_query_arg('qalam_general_quiz',$quiz_id,home_url('/')));exit;
}
add_action('template_redirect','qalam_080_question_preview_route',-20);

/** Candidate bank selection for fixed-random and dynamic exams. */
function qalam_080_candidate_question_ids( int $term_id, string $difficulty='any' ): array {
	$args=array('post_type'=>'cb-question','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'ASC');
	if($term_id){$args['tax_query']=array(array('taxonomy'=>QALAM_QUESTION_CATEGORY_TAX,'field'=>'term_id','terms'=>$term_id,'include_children'=>true));}
	if(in_array($difficulty,array('easy','medium','hard'),true)){$args['meta_query']=array(array('key'=>QALAM_QBANK_DIFFICULTY_META,'value'=>$difficulty));}
	$ids=get_posts($args); if(!$ids)return array(); global $wpdb; $valid=array(); foreach($ids as $id){$exists=(int)$wpdb->get_var($wpdb->prepare("SELECT question_id FROM {$wpdb->prefix}tutor_quiz_questions WHERE content_id=%d LIMIT 1",$id));if($exists)$valid[]=(int)$id;} return $valid;
}
function qalam_080_select_questions( int $term_id, int $count, string $difficulty, array $exclude, int $template_id=0 ): array {
	$candidates=qalam_080_candidate_question_ids($term_id,$difficulty); if(!$candidates)return array(); $exclude=array_flip(array_map('absint',$exclude)); $usage=$template_id?(array)get_option(QALAM_080_USAGE_OPTION_PREFIX.$template_id,array()):array();
	usort($candidates,static function($a,$b)use($usage,$exclude){$ae=isset($exclude[$a])?1:0;$be=isset($exclude[$b])?1:0;if($ae!==$be)return $ae<=>$be;$au=(int)($usage[$a]??0);$bu=(int)($usage[$b]??0);if($au!==$bu)return $au<=>$bu;return wp_rand(-1,1);});
	return array_slice($candidates,0,min($count,count($candidates)));
}
function qalam_080_random_fill_quiz(){if(!current_user_can('manage_tutor_instructor'))wp_die('غير مسموح.');$quiz=absint($_POST['quiz_id']??0);check_admin_referer('qalam_080_quiz_tools_'.$quiz,'qalam_080_quiz_nonce');$term=absint($_POST['category_id']??0);$count=min(100,max(1,absint($_POST['question_count']??10)));$difficulty=sanitize_key((string)($_POST['difficulty']??'any'));$ids=qalam_080_select_questions($term,$count,$difficulty,array(),$quiz);$added=qalam_070_copy_content_questions_to_quiz($quiz,$ids);wp_safe_redirect(add_query_arg(array('page'=>'qalam-quiz-builder','quiz_id'=>$quiz,'qalam_created'=>$added),admin_url('admin.php')));exit;}
add_action('admin_post_qalam_080_random_fill_quiz','qalam_080_random_fill_quiz');

function qalam_080_save_dynamic_rules(){if(!current_user_can('manage_tutor_instructor'))wp_die('غير مسموح.');$quiz=absint($_POST['quiz_id']??0);check_admin_referer('qalam_080_quiz_tools_'.$quiz,'qalam_080_quiz_nonce');$enabled=!empty($_POST['dynamic_enabled'])?'1':'0';$rules=array('category_id'=>absint($_POST['category_id']??0),'question_count'=>min(100,max(1,absint($_POST['question_count']??10))),'difficulty'=>sanitize_key((string)($_POST['difficulty']??'any')));update_post_meta($quiz,QALAM_080_DYNAMIC_META,$enabled);update_post_meta($quiz,QALAM_080_DYNAMIC_RULES_META,$rules);wp_safe_redirect(admin_url('admin.php?page=qalam-quiz-builder&quiz_id='.$quiz.'&saved=1'));exit;}
add_action('admin_post_qalam_080_save_dynamic_rules','qalam_080_save_dynamic_rules');

function qalam_080_dynamic_history( int $user_id, int $template_id ): array {
	$all=get_user_meta($user_id,QALAM_080_HISTORY_USERMETA,true);$all=is_array($all)?$all:array();
	$history=(array)($all[$template_id]['all']??$all[$template_id]??array());
	return array_values(array_unique(array_map('absint',$history)));
}
function qalam_080_dynamic_last_attempt( int $user_id, int $template_id ): array {
	$all=get_user_meta($user_id,QALAM_080_HISTORY_USERMETA,true);$all=is_array($all)?$all:array();
	$row=(array)($all[$template_id]??array());
	return array_values(array_unique(array_map('absint',(array)($row['last']??array()))));
}
function qalam_080_record_dynamic_usage( int $user_id, int $template_id, array $ids ): void {
	$all=get_user_meta($user_id,QALAM_080_HISTORY_USERMETA,true);$all=is_array($all)?$all:array();
	$current=(array)($all[$template_id]??array());$legacy=isset($current['all'])?(array)$current['all']:$current;
	$merged=array_values(array_unique(array_merge(array_map('absint',$legacy),array_map('absint',$ids))));if(count($merged)>2000)$merged=array_slice($merged,-2000);
	$all[$template_id]=array('all'=>$merged,'last'=>array_values(array_unique(array_map('absint',$ids))),'updated'=>time());update_user_meta($user_id,QALAM_080_HISTORY_USERMETA,$all);
	$usage=(array)get_option(QALAM_080_USAGE_OPTION_PREFIX.$template_id,array());foreach($ids as $id){$usage[$id]=(int)($usage[$id]??0)+1;}update_option(QALAM_080_USAGE_OPTION_PREFIX.$template_id,$usage,false);
}

/** Create a separate real Tutor quiz instance for each dynamic attempt — no shared-question race. */
function qalam_080_create_dynamic_instance( int $template_id, int $user_id ): int {
	$rules=get_post_meta($template_id,QALAM_080_DYNAMIC_RULES_META,true);$rules=is_array($rules)?$rules:array();$count=min(100,max(1,absint($rules['question_count']??10)));$term=absint($rules['category_id']??0);$difficulty=sanitize_key((string)($rules['difficulty']??'any'));$history=qalam_080_dynamic_history($user_id,$template_id);$ids=qalam_080_select_questions($term,$count,$difficulty,$history,$template_id);if(count($ids)<$count){$ids=qalam_080_select_questions($term,$count,$difficulty,qalam_080_dynamic_last_attempt($user_id,$template_id),$template_id);}if(count($ids)<$count)throw new RuntimeException('بنك الأسئلة لا يحتوي عددًا كافيًا يطابق قواعد الامتحان الديناميكي.');
	$container=qalam_060_general_quiz_container();$template=get_post($template_id);$options=get_post_meta($template_id,\TUTOR\Quiz::META_QUIZ_OPTION,true);$options=is_array($options)?$options:array();$options['limit_attempts_allowed']='1';$options['attempts_allowed']=1;$options['max_questions_for_answer']=0;$options['questions_order']='rand';$builder=new \TUTOR\QuizBuilder(false);$result=$builder->save_quiz($container['topic_id'],array('post_title'=>($template?$template->post_title:'اختبار').' — محاولة ديناميكية','post_content'=>$template?$template->post_content:'','quiz_option'=>$options,'questions'=>array()));if(empty($result->success)||empty($result->data))throw new RuntimeException('تعذر إنشاء محاولة الامتحان الديناميكي.');$instance=absint($result->data);update_post_meta($instance,QALAM_GENERAL_QUIZ_META,'1');update_post_meta($instance,QALAM_080_DYNAMIC_PARENT_META,$template_id);update_post_meta($instance,QALAM_080_DYNAMIC_USER_META,$user_id);update_post_meta($instance,QALAM_080_DYNAMIC_CONTENTS_META,$ids);qalam_070_copy_content_questions_to_quiz($instance,$ids);qalam_080_record_dynamic_usage($user_id,$template_id,$ids);return $instance;
}

/** Handle dynamic template links before the old fixed general-quiz route. */
function qalam_080_dynamic_share_route(){if(empty($_GET['qalam_general_quiz']))return;$template=absint($_GET['qalam_general_quiz']);if('1'!==(string)get_post_meta($template,QALAM_080_DYNAMIC_META,true))return;if(!is_user_logged_in()){auth_redirect();exit;}try{$instance=qalam_080_create_dynamic_instance($template,get_current_user_id());qalam_060_prepare_general_quiz_access();wp_safe_redirect(add_query_arg('qalam_general_quiz',$instance,home_url('/')));exit;}catch(\Throwable $e){wp_die(esc_html($e->getMessage()),'Qalam LMS');}}
add_action('template_redirect','qalam_080_dynamic_share_route',-10);

/** Hide preview/dynamic instance quizzes from the standalone template list. */
function qalam_080_hide_internal_quiz_instances( $query ) {
	if(!is_admin()||!$query instanceof WP_Query)return;$page=isset($_GET['page'])?sanitize_key(wp_unslash($_GET['page'])):'';if('qalam-quiz-builder'!==$page)return;if(tutor()->quiz_post_type!==$query->get('post_type'))return;$mq=(array)$query->get('meta_query');$mq[]=array('key'=>QALAM_080_PREVIEW_META,'compare'=>'NOT EXISTS');$mq[]=array('key'=>QALAM_080_DYNAMIC_PARENT_META,'compare'=>'NOT EXISTS');$query->set('meta_query',$mq);
}
add_action('pre_get_posts','qalam_080_hide_internal_quiz_instances');

/** Frontend helper for a dynamic instance: new-attempt link points back to template. */
function qalam_080_dynamic_retake_banner(){if(is_admin()||tutor()->quiz_post_type!==get_post_type())return;$parent=absint(get_post_meta(get_the_ID(),QALAM_080_DYNAMIC_PARENT_META,true));if(!$parent)return;$url=add_query_arg('qalam_general_quiz',$parent,home_url('/'));echo '<a class="qalam-dynamic-new-attempt" href="'.esc_url($url).'">↻ محاولة جديدة بأسئلة مختلفة</a>';}
add_action('wp_footer','qalam_080_dynamic_retake_banner',40);

/** Localize admin data used by progress UI, preview links and quiz tools. */
function qalam_080_admin_assets(){
	if(!is_admin())return;$page=isset($_GET['page'])?sanitize_key(wp_unslash($_GET['page'])):'';$pages=array('qalam-question-bank','qalam-quiz-builder','create-course','tutor-content-bank');if(!in_array($page,$pages,true))return;$base=plugin_dir_url(TUTOR_FILE);wp_enqueue_style('qalam-080-admin',$base.'assets/css/qalam-080-admin.css',array('qalam-070-admin'),QALAM_LMS_UI_VERSION);wp_enqueue_script('qalam-080-admin',$base.'assets/js/qalam-080-admin.js',array(),QALAM_LMS_UI_VERSION,true);
	$terms=get_terms(array('taxonomy'=>QALAM_QUESTION_CATEGORY_TAX,'hide_empty'=>false));if(is_wp_error($terms))$terms=array();$cats=array();foreach($terms as $t){$cats[]=array('id'=>(int)$t->term_id,'name'=>$t->name,'parent'=>(int)$t->parent);}
	$quiz_id='qalam-quiz-builder'===$page?absint($_GET['quiz_id']??0):0;$dynamic=$quiz_id?'1'===(string)get_post_meta($quiz_id,QALAM_080_DYNAMIC_META,true):false;$rules=$quiz_id?get_post_meta($quiz_id,QALAM_080_DYNAMIC_RULES_META,true):array();
	wp_localize_script('qalam-080-admin','Qalam080',array('ajaxUrl'=>admin_url('admin-ajax.php'),'processNonce'=>wp_create_nonce('qalam_080_process_generation'),'adminPost'=>admin_url('admin-post.php'),'previewBase'=>home_url('/?qalam_question_preview='),'categories'=>$cats,'quizId'=>$quiz_id,'quizToolsNonce'=>$quiz_id?wp_create_nonce('qalam_080_quiz_tools_'.$quiz_id):'','dynamicEnabled'=>$dynamic,'dynamicRules'=>is_array($rules)?$rules:array(),'randomizedFeatureEnabled'=>!function_exists('qalam_feature_enabled')||qalam_feature_enabled('randomized_exams'),'dynamicFeatureEnabled'=>!function_exists('qalam_feature_enabled')||qalam_feature_enabled('dynamic_exams')));
}
add_action('admin_enqueue_scripts','qalam_080_admin_assets',PHP_INT_MAX);

/** Force the new light, high-contrast exam skin to load on every Tutor quiz/learning-area page. */
function qalam_080_front_assets(){if(is_admin())return;$base=plugin_dir_url(TUTOR_FILE);wp_enqueue_style('qalam-080-quiz',$base.'assets/css/qalam-080-quiz.css',array('qalam-lms-student'),QALAM_LMS_UI_VERSION);}
add_action('wp_enqueue_scripts','qalam_080_front_assets',PHP_INT_MAX);
