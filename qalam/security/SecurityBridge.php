<?php
/** Qalam source-level security closure. Adapted from the legacy salvage security contracts only. */
namespace Qalam\Security;

use Tutor\Helpers\SessionHelper;
use Tutor\Models\EnrollmentModel;

final class SocialIdentityService {
    public static function meta_key($provider){return '_qalam_social_identity_'.sanitize_key((string)$provider);}
    public static function bind_or_validate($provider,$subject,$email,$user=null){
        $provider=sanitize_key((string)$provider); $subject=sanitize_text_field((string)$subject); $email=sanitize_email((string)$email);
        if(!in_array($provider,array('google','facebook','twitter'),true)||''===$subject||!is_email($email))return new \WP_Error('qalam_social_invalid','بيانات تسجيل الدخول الاجتماعي غير صالحة.');
        $key=self::meta_key($provider); $bound=get_users(array('meta_key'=>$key,'meta_value'=>$subject,'number'=>2,'fields'=>'ids'));
        if($bound){$id=(int)$bound[0]; if($user instanceof \WP_User && $id===(int)$user->ID)return $user; $u=get_userdata($id); return $u instanceof \WP_User?$u:new \WP_Error('qalam_social_binding','تعذر التحقق من ربط الحساب الاجتماعي.');}
        if(!$user instanceof \WP_User){$user=get_user_by('email',$email);}
        if($user instanceof \WP_User){
            if(user_can($user,'manage_options')||user_can($user,'activate_plugins')||user_can($user,'edit_users'))return new \WP_Error('qalam_social_privileged_link','لأمان الحسابات الإدارية، لازم ربط الحساب الاجتماعي من داخل الحساب أولًا.');
            $existing=(string)get_user_meta($user->ID,$key,true); if($existing!==''&&!hash_equals($existing,$subject))return new \WP_Error('qalam_social_mismatch','الحساب مربوط بهوية اجتماعية مختلفة.');
            update_user_meta($user->ID,$key,$subject); return $user;
        }
        return null;
    }
    public static function bind_new_user($user_id,$provider,$subject){$key=self::meta_key($provider);update_user_meta((int)$user_id,$key,sanitize_text_field((string)$subject));}
}

final class OtpSecurity {
    const TTL=300; const MAX_ATTEMPTS=5;
    public static function hash_code($code){return hash_hmac('sha256',(string)$code,wp_salt('auth').'|qalam-login-otp-v1');}
    public static function verify_code($code,$hash){return is_string($hash)&&$hash!==''&&hash_equals($hash,self::hash_code((string)$code));}
    public static function challenge($user,$method,$remember=false,$send_email=true){
        if(!$user instanceof \WP_User)return new \WP_Error('qalam_2fa_user','الحساب غير صالح.'); $method='totp'===$method?'totp':'email';
        $data=(object)array('user'=>$user,'remember'=>(bool)$remember,'method'=>$method,'expires_at'=>time()+self::TTL,'attempts'=>0);
        if('email'===$method){if(!$send_email)return new \WP_Error('qalam_2fa_mail','تعذر إرسال كود التحقق.');$otp=random_int(100000,999999);$data->code_hash=self::hash_code((string)$otp); if(!\TutorPro\Auth\Utils::sent_login_otp($user->user_email,$otp))return new \WP_Error('qalam_2fa_mail','تعذر إرسال كود التحقق. حاول مرة تانية.');SessionHelper::set('resent_otp_at',time()+\TutorPro\Auth\_2FA::MINUTE_IN_SECONDS);}
        SessionHelper::set('tutor_login_otp',$data); return true;
    }
    public static function expired($data){return !is_object($data)||empty($data->expires_at)||(int)$data->expires_at<time()||(int)($data->attempts??0)>=self::MAX_ATTEMPTS;}
    public static function bump($data){if(is_object($data)){$data->attempts=(int)($data->attempts??0)+1;SessionHelper::set('tutor_login_otp',$data);}return $data;}
}

final class PrivateSecretStore {
    public static function base(){
        $root=PrivateAttachmentStore::root();
        if($root==='')return '';
        $dir=rtrim(wp_normalize_path($root),'/\\').'/secrets';
        if(!wp_mkdir_p($dir)||!is_writable($dir))return '';
        @chmod($dir,0700);
        if(!is_file($dir.'/index.php'))@file_put_contents($dir.'/index.php',"<?php\nhttp_response_code(404);exit;\n",LOCK_EX);
        return $dir;
    }
    public static function directory($namespace,$owner_id){
        $base=self::base(); if($base==='')return '';
        $namespace=sanitize_key((string)$namespace); $owner_id=absint($owner_id);
        if($namespace===''||$owner_id<1)return '';
        $dir=$base.'/'.$namespace.'/'.$owner_id;
        if(!wp_mkdir_p($dir)||!is_writable($dir))return '';
        @chmod($dir,0700); return $dir;
    }
    public static function write_json($path,$data){
        $path=wp_normalize_path((string)$path); $base=wp_normalize_path(self::base());
        if($base===''||strpos($path,trailingslashit($base))!==0||!is_array($data))return false;
        $json=wp_json_encode($data,JSON_UNESCAPED_SLASHES); if(!is_string($json))return false;
        $tmp=$path.'.'.wp_generate_uuid4().'.tmp';
        if(false===@file_put_contents($tmp,$json,LOCK_EX)){@unlink($tmp);return false;}
        @chmod($tmp,0600); if(!@rename($tmp,$path)){@unlink($tmp);return false;} @chmod($path,0600); return true;
    }
    public static function read_json($path,$max_bytes=2097152){
        $path=wp_normalize_path((string)$path); $base=wp_normalize_path(self::base());
        if($base===''||strpos($path,trailingslashit($base))!==0||!is_file($path)||!is_readable($path))return null;
        $size=@filesize($path); if(false===$size||$size<1||$size>$max_bytes)return null;
        $raw=@file_get_contents($path); if(!is_string($raw))return null; $data=json_decode($raw,true); return is_array($data)?$data:null;
    }
}

final class PrivateAttachmentStore {
    const KEY_META='_qalam_private_object_key'; const CONTEXT_META='_qalam_private_context_post'; const SOURCE_META='_qalam_private_source_id';
    private static $protecting=false;
    public static function root(){
        $configured=defined('QALAM_PRIVATE_STORAGE_DIR')?trim((string)QALAM_PRIVATE_STORAGE_DIR):''; $configured=(string)apply_filters('qalam_private_storage_root',$configured);
        if($configured!=='')return rtrim(wp_normalize_path($configured),'/\\');
        $parent=rtrim(wp_normalize_path(dirname(rtrim(ABSPATH,'/\\'))),'/\\').'/qalam-private'; if(@wp_mkdir_p($parent))return $parent;
        // Fail closed: a private store inside public uploads is not reliably private on Nginx.
        return '';
    }
    private static function dir(){ $root=self::root(); if(!wp_mkdir_p($root))return ''; foreach(array('index.php'=>"<?php\nhttp_response_code(404);exit;\n",'.htaccess'=>"Deny from all\n",'web.config'=>'<configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>') as $name=>$body){if(!is_file($root.'/'.$name))@file_put_contents($root.'/'.$name,$body);} return $root; }
    public static function is_safe_mime($mime){$mime=strtolower((string)$mime); if($mime===''||preg_match('#(?:php|x-httpd|html|javascript|svg|xml)#',$mime))return false; return 0===strpos($mime,'image/')||0===strpos($mime,'video/')||0===strpos($mime,'audio/')||in_array($mime,array('application/pdf','application/zip','application/x-zip-compressed','text/plain','text/csv','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation'),true);}
    public static function protect_meta($meta_id,$post_id,$meta_key,$value){if(self::$protecting||'_tutor_attachments'!==$meta_key||!is_array($value))return; $new=array();$changed=false;self::$protecting=true;foreach($value as $aid){$aid=absint($aid);if(!$aid)continue;$private=self::clone_private($aid,(int)$post_id);if($private&&$private!==$aid)$changed=true;$new[]=$private?:$aid;}if($changed)update_post_meta((int)$post_id,'_tutor_attachments',array_values(array_unique(array_map('absint',$new))));self::$protecting=false;}
    public static function clone_private($attachment_id,$context_id){if(get_post_meta($attachment_id,self::KEY_META,true))return $attachment_id;$src=get_attached_file($attachment_id);if(!$src||!is_file($src)||!is_readable($src))return 0;$mime=(string)get_post_mime_type($attachment_id);if(!self::is_safe_mime($mime))return 0;$dir=self::dir();if($dir==='')return 0;$ext=strtolower(pathinfo($src,PATHINFO_EXTENSION));$name=wp_generate_uuid4().($ext?'.'.preg_replace('/[^a-z0-9]/','',$ext):'');$dest=$dir.'/'.$name;if(!@copy($src,$dest))return 0;@chmod($dest,0640);$new_id=wp_insert_attachment(array('post_title'=>get_the_title($attachment_id),'post_mime_type'=>$mime,'post_status'=>'inherit','post_parent'=>$context_id),$dest,$context_id,true);if(is_wp_error($new_id)||!$new_id){@unlink($dest);return 0;}update_post_meta($new_id,'_wp_attached_file',$dest);update_post_meta($new_id,self::KEY_META,$name);update_post_meta($new_id,self::CONTEXT_META,$context_id);update_post_meta($new_id,self::SOURCE_META,$attachment_id);return (int)$new_id;}
    public static function signed_url($attachment_id){$attachment_id=absint($attachment_id);$uid=get_current_user_id();$exp=time()+600;$sig=hash_hmac('sha256',$attachment_id.'|'.$uid.'|'.$exp,wp_salt('secure_auth'));return add_query_arg(array('qalam_private_file'=>$attachment_id,'u'=>$uid,'e'=>$exp,'s'=>$sig),home_url('/'));}
    public static function filter_attachment($data){if(!is_array($data))return $data;$id=absint($data['id']??$data['post_id']??0);if($id&&get_post_meta($id,self::KEY_META,true))$data['url']=self::signed_url($id);return $data;}
    public static function can_access($id,$uid){$context=absint(get_post_meta($id,self::CONTEXT_META,true));if(!$context)return false;$course=tutor_utils()->get_course_id_by_content($context);if(!$course&&get_post_type($context)===tutor()->course_post_type)$course=$context;if(!$course)return false;if($uid&&tutor_utils()->can_user_manage('course',$course,$uid))return true;if(class_exists('TUTOR\\Course_List')&&\TUTOR\Course_List::is_public((int)$course))return true;return $uid>0&&EnrollmentModel::is_enrolled($course,$uid);}
    public static function stream(){if(empty($_GET['qalam_private_file']))return;$id=absint($_GET['qalam_private_file']);$uid=absint($_GET['u']??0);$exp=absint($_GET['e']??0);$sig=sanitize_text_field(wp_unslash($_GET['s']??''));if($uid!==get_current_user_id()||$exp<time()||$exp>time()+7200||!hash_equals(hash_hmac('sha256',$id.'|'.$uid.'|'.$exp,wp_salt('secure_auth')),$sig)||!self::can_access($id,$uid)){status_header(403);exit;}$path=get_attached_file($id);if(!$path||!is_file($path)||strpos(wp_normalize_path($path),trailingslashit(wp_normalize_path(self::root())))!==0){status_header(404);exit;}$mime=(string)get_post_mime_type($id);if(!self::is_safe_mime($mime)){status_header(415);exit;}nocache_headers();header('Content-Type: '.$mime);header('X-Content-Type-Options: nosniff');header('Content-Length: '.filesize($path));$mode='view'===apply_filters('tutor_pro_attachment_open_mode',null)?'inline':'attachment';header('Content-Disposition: '.$mode.'; filename="'.rawurlencode(basename($path)).'"');readfile($path);exit;}
}

/** Social login gate: never create an auth cookie before a configured second factor succeeds. */
function prepare_social_2fa($user){if(!$user instanceof \WP_User||!class_exists('TutorPro\\Auth\\Settings')||!\TutorPro\Auth\Settings::is_2fa_enabled())return false;$method=\TutorPro\Auth\Settings::get_2fa_method();if('totp'===$method&&!TotpService::is_enrolled($user->ID))return new \WP_Error('qalam_totp_not_enrolled','الحساب مطلوب له تطبيق مصادقة لكنه غير مُعد. تواصل مع إدارة المنصة.');if('email'===$method&&!tutor_utils()->is_addon_enabled('tutor-email'))return new \WP_Error('qalam_email_2fa_unavailable','التحقق بالبريد غير متاح حاليًا.');$res=OtpSecurity::challenge($user,$method,false,true);return is_wp_error($res)?$res:true;}


/** Pending social 2FA has no auth cookie yet; route the browser to the challenge page before Tutor sends it back to login. */
function redirect_pending_2fa(){
    if(is_user_logged_in()||wp_doing_ajax()||'tutor-2fa'===(string)($_GET['step']??''))return;
    if(class_exists('Tutor\Helpers\SessionHelper')&&SessionHelper::get('tutor_login_otp')){wp_safe_redirect(add_query_arg('step','tutor-2fa',home_url('/')));exit;}
}
add_action('template_redirect',__NAMESPACE__.'\redirect_pending_2fa',-9999);

/** Enrollment UI inside the existing Tutor account profile; no new runtime module. */
function render_totp_profile($user=null){$uid=get_current_user_id();if(!$uid)return;$enabled=TotpService::is_enrolled($uid);echo '<div class="tutor-card tutor-p-20 tutor-mt-24 qalam-totp-card"><h4>تطبيق المصادقة بخطوتين</h4>';wp_nonce_field('qalam_totp_profile_'.$uid,'qalam_totp_nonce');if($enabled){echo '<p>تطبيق المصادقة متفعل على الحساب.</p><label><input type="checkbox" name="qalam_totp_disable" value="1"> إلغاء ربط تطبيق المصادقة</label>';}else{try{$p=TotpService::begin_enrollment($uid);echo '<p>أضف المفتاح التالي في Google Authenticator أو أي تطبيق TOTP، وبعدها اكتب الكود الحالي واحفظ الحساب.</p><code style="direction:ltr;display:block;word-break:break-all">'.esc_html($p['secret']).'</code><input class="tutor-form-control tutor-mt-12" type="text" inputmode="numeric" name="qalam_totp_confirm_code" maxlength="6" placeholder="كود من 6 أرقام"><details class="tutor-mt-12"><summary>أكواد الاسترداد</summary><code style="direction:ltr;display:block">'.esc_html(implode(' · ',$p['recovery_codes'])).'</code></details>';}catch(\Throwable $e){echo '<p>'.esc_html($e->getMessage()).'</p>';}}echo '</div>';}
function save_totp_profile($user_id){$user_id=absint($user_id);if(!$user_id||$user_id!==get_current_user_id())return;$nonce=sanitize_text_field(wp_unslash($_POST['qalam_totp_nonce']??''));if(!$nonce||!wp_verify_nonce($nonce,'qalam_totp_profile_'.$user_id))return;if(!empty($_POST['qalam_totp_disable'])){TotpService::disable($user_id);return;}$code=sanitize_text_field(wp_unslash($_POST['qalam_totp_confirm_code']??''));if($code!=='')TotpService::confirm_enrollment($user_id,$code);}

/** Certificate revocation is bound to Tutor's canonical course-completion comment. */
function certificate_access($allowed,$completed){if(!$allowed||!is_object($completed))return (bool)$allowed;$id=absint($completed->certificate_id??$completed->comment_ID??0);return $id?('1'!==(string)get_comment_meta($id,'_qalam_certificate_revoked',true)):(bool)$allowed;}
function certificate_revoke_action(){if(!\TUTOR\User::is_admin()&&!current_user_can('qalam_manage_addons'))wp_die('غير مصرح.');$id=absint($_POST['completion_id']??0);check_admin_referer('qalam_certificate_revoke_'.$id);$comment=get_comment($id);if(!$comment||'course_completed'!==$comment->comment_type)wp_die('سجل الإكمال غير صالح.');$revoke=!empty($_POST['revoke']);if($revoke)update_comment_meta($id,'_qalam_certificate_revoked','1');else delete_comment_meta($id,'_qalam_certificate_revoked');wp_safe_redirect(wp_get_referer()?:home_url('/qalam-admin/'));exit;}

/** Runtime hooks */
add_action('added_post_meta',array(__NAMESPACE__.'\\PrivateAttachmentStore','protect_meta'),30,4); add_action('updated_post_meta',array(__NAMESPACE__.'\\PrivateAttachmentStore','protect_meta'),30,4);
add_filter('tutor/posts/attachments',array(__NAMESPACE__.'\\PrivateAttachmentStore','filter_attachment'),PHP_INT_MAX); add_action('template_redirect',array(__NAMESPACE__.'\\PrivateAttachmentStore','stream'),0);
add_action('tutor_profile_edit_input_after',__NAMESPACE__.'\\render_totp_profile',25,1); add_action('tutor_profile_update_before',__NAMESPACE__.'\\save_totp_profile',25,1);
add_filter('tutor_pro_certificate_access',__NAMESPACE__.'\\certificate_access',PHP_INT_MAX,2); add_action('admin_post_qalam_certificate_revoke',__NAMESPACE__.'\\certificate_revoke_action');
