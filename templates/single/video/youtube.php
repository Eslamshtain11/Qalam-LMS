<?php
/** Qalam LMS YouTube transport with Qalam-owned controls, subtitles and in-player ads. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$video_info = (array) ( tutor_utils()->get_video_info() ?: array() );
$youtube_video_id = tutor_utils()->get_youtube_video_id( tutor_utils()->avalue_dot( 'source_youtube', $video_info ) );
$player_id = 'qalam-youtube-' . wp_generate_uuid4();
$logo_url = plugin_dir_url( TUTOR_FILE ) . 'assets/images/qalam-logo.svg';
$home_parts = wp_parse_url( home_url( '/' ) ); $origin='';
if(is_array($home_parts)&&!empty($home_parts['scheme'])&&!empty($home_parts['host'])){$origin=$home_parts['scheme'].'://'.$home_parts['host'].(!empty($home_parts['port'])?':'.(int)$home_parts['port']:'');}
$ctx = function_exists('qalam_150_video_context') ? qalam_150_video_context() : array('ads'=>array(),'subtitle_url'=>'','subtitle_label'=>'العربية');
$custom_player_enabled = ! function_exists( 'qalam_feature_enabled' ) || qalam_feature_enabled( 'qalam_video_player' );
do_action( 'tutor_lesson/single/before/video/youtube' );
if ( $youtube_video_id && ! $custom_player_enabled ) {
    $src = 'https://www.youtube.com/embed/' . rawurlencode( $youtube_video_id ) . '?rel=0&modestbranding=1&playsinline=1';
    echo '<div class="tutor-video-player qalam-native-video-fallback"><iframe src="' . esc_url( $src ) . '" title="فيديو الدرس" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe></div>';
    do_action( 'tutor_lesson/single/after/video/youtube' );
    return;
}
?>
<?php if($youtube_video_id):?>
<div class="tutor-video-player qalam-video-player" data-qalam-video-player data-video-id="<?php echo esc_attr($youtube_video_id);?>" data-origin="<?php echo esc_attr($origin);?>" data-qalam-video-ads="<?php echo esc_attr(wp_json_encode($ctx['ads']??array(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));?>" data-qalam-subtitle-url="<?php echo esc_url($ctx['subtitle_url']??'');?>" data-qalam-subtitle-label="<?php echo esc_attr($ctx['subtitle_label']??'العربية');?>" tabindex="0" role="region" aria-label="مشغل فيديو قلم">
  <div class="qalam-video-stage">
    <div id="<?php echo esc_attr($player_id);?>" class="qalam-youtube-host" data-qalam-youtube-host></div>
    <div class="qalam-video-shield" data-qalam-video-toggle aria-hidden="true"></div>
    <div class="qalam-video-top-chrome" aria-hidden="true"><div class="qalam-video-brand"><img src="<?php echo esc_url($logo_url);?>" alt=""><span>مشغل قلم</span></div><div class="qalam-video-source-badge">فيديو الدرس</div></div>
    <button class="qalam-video-center-play" type="button" data-qalam-video-action="toggle" aria-label="تشغيل الفيديو"><svg viewBox="0 0 24 24"><path d="M8.5 6.1a1 1 0 0 1 1.52-.85l9 5.9a1 1 0 0 1 0 1.7l-9 5.9A1 1 0 0 1 8.5 17.9V6.1Z"/></svg></button>
    <div class="qalam-video-loading" data-qalam-video-loading aria-live="polite"><span class="qalam-video-spinner"></span><span>جاري تجهيز الفيديو…</span></div>
    <div class="qalam-video-toast" data-qalam-video-toast role="status" aria-live="polite"></div>
    <div class="qalam-video-caption" data-qalam-video-caption hidden></div>
    <div class="qalam-video-ad-overlay" data-qalam-video-ad hidden><div class="qalam-video-ad-badge">إعلان</div><div class="qalam-video-ad-media" data-qalam-video-ad-media></div><div class="qalam-video-ad-bottom"><span data-qalam-video-ad-countdown></span><button type="button" data-qalam-video-ad-skip disabled>تخطي الإعلان</button></div></div>
    <div class="qalam-video-controls" data-qalam-video-controls dir="rtl">
      <div class="qalam-video-progress-wrap"><input class="qalam-video-progress" data-qalam-video-progress type="range" min="0" max="1000" step="1" value="0" aria-label="التقدم في الفيديو" dir="ltr"><div class="qalam-video-time" dir="ltr"><span data-qalam-current-time>00:00</span><span>/</span><span data-qalam-duration>00:00</span></div></div>
      <div class="qalam-video-control-row">
        <div class="qalam-video-control-group qalam-video-control-primary">
          <button type="button" class="qalam-video-control-btn qalam-video-play-btn" data-qalam-video-action="toggle" aria-label="تشغيل أو إيقاف"><svg class="qalam-icon-play" viewBox="0 0 24 24"><path d="M8.5 6.1a1 1 0 0 1 1.52-.85l9 5.9a1 1 0 0 1 0 1.7l-9 5.9A1 1 0 0 1 8.5 17.9V6.1Z"/></svg><svg class="qalam-icon-pause" viewBox="0 0 24 24"><path d="M7 5.5A1.5 1.5 0 0 1 8.5 4h1A1.5 1.5 0 0 1 11 5.5v13A1.5 1.5 0 0 1 9.5 20h-1A1.5 1.5 0 0 1 7 18.5v-13Zm6 0A1.5 1.5 0 0 1 14.5 4h1A1.5 1.5 0 0 1 17 5.5v13a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-13Z"/></svg></button>
          <button type="button" class="qalam-video-control-btn qalam-video-seek" data-qalam-video-action="backward" aria-label="الرجوع 10 ثواني">−10</button><button type="button" class="qalam-video-control-btn qalam-video-seek" data-qalam-video-action="forward" aria-label="التقديم 10 ثواني">+10</button>
        </div>
        <div class="qalam-video-control-group qalam-video-control-secondary">
          <div class="qalam-video-volume-wrap"><button type="button" class="qalam-video-control-btn" data-qalam-video-action="mute" aria-label="كتم أو تشغيل الصوت"><svg class="qalam-icon-volume" viewBox="0 0 24 24"><path d="M4 9.5h4L13 5v14l-5-4.5H4v-5Zm11.8-1.8a6 6 0 0 1 0 8.6l-1.2-1.2a4.3 4.3 0 0 0 0-6.2l1.2-1.2Zm2.5-2.5a9.5 9.5 0 0 1 0 13.6l-1.2-1.2a7.8 7.8 0 0 0 0-11.2l1.2-1.2Z"/></svg><svg class="qalam-icon-muted" viewBox="0 0 24 24"><path d="M4 9.5h4L13 5v14l-5-4.5H4v-5Zm12.2-.9 1.4-1.4 2 2 2-2 1.4 1.4-2 2 2 2-1.4 1.4-2-2-2 2-1.4-1.4 2-2-2-2Z"/></svg></button><input type="range" class="qalam-video-volume" data-qalam-video-volume min="0" max="100" value="100" aria-label="مستوى الصوت" dir="ltr"></div>
          <div class="qalam-video-menu-wrap"><button type="button" class="qalam-video-control-btn qalam-video-settings-btn" data-qalam-video-menu-button="settings" aria-haspopup="menu" aria-expanded="false" aria-label="إعدادات المشغل"><svg viewBox="0 0 24 24"><path d="M19.4 13a7.8 7.8 0 0 0 .05-1 7.8 7.8 0 0 0-.05-1l2.1-1.65-2-3.46-2.55 1a7.5 7.5 0 0 0-1.74-1L14.82 3h-4l-.4 2.89a7.5 7.5 0 0 0-1.74 1l-2.55-1-2 3.46L6.23 11a7.8 7.8 0 0 0-.05 1 7.8 7.8 0 0 0 .05 1l-2.1 1.65 2 3.46 2.55-1a7.5 7.5 0 0 0 1.74 1l.4 2.89h4l.4-2.89a7.5 7.5 0 0 0 1.74-1l2.55 1 2-3.46L19.4 13ZM12.82 15.5A3.5 3.5 0 1 1 12.82 8a3.5 3.5 0 0 1 0 7.5Z"/></svg></button>
            <div class="qalam-video-menu qalam-video-settings-menu" data-qalam-video-menu="settings" role="menu" hidden>
              <section><div class="qalam-settings-label">السرعة <strong data-qalam-speed-label>1×</strong></div><div class="qalam-settings-options" data-qalam-speed-options></div></section>
              <section class="qalam-quality-setting"><div class="qalam-settings-label">الجودة <strong data-qalam-quality-label>تلقائي</strong></div><div data-qalam-quality-status class="qalam-video-quality-auto"></div></section>
              <section class="qalam-caption-setting"><div><strong>ترجمة قلم</strong><small data-qalam-caption-label><?php echo esc_html($ctx['subtitle_label']??'العربية');?></small></div><button type="button" data-qalam-caption-toggle aria-pressed="false" <?php disabled(empty($ctx['subtitle_url']));?>>تشغيل</button></section>
            </div>
          </div>
          <button type="button" class="qalam-video-control-btn" data-qalam-video-action="fullscreen" aria-label="ملء الشاشة"><svg class="qalam-icon-fullscreen" viewBox="0 0 24 24"><path d="M5 9H3V3h6v2H5v4Zm14 0V5h-4V3h6v6h-2ZM5 15v4h4v2H3v-6h2Zm14 0h2v6h-6v-2h4v-4Z"/></svg><svg class="qalam-icon-exit-fullscreen" viewBox="0 0 24 24"><path d="M7 3h2v6H3V7h4V3Zm8 0h2v4h4v2h-6V3ZM3 15h6v6H7v-4H3v-2Zm12 0h6v2h-4v4h-2v-6Z"/></svg></button>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif;?>
<?php do_action( 'tutor_lesson/single/after/video/youtube' ); ?>
