<?php
/**
 * Qalam TOTP service — adapted from QALAM_LEGACY_SALVAGE_PACK.
 * Standalone security utility; no legacy runtime dependency.
 */
namespace Qalam\Security;

use RuntimeException;

final class TotpService {
    const SECRET_META = '_qalam_totp_secret_encrypted';
    const ENABLED_META = '_qalam_totp_enabled';
    const RECOVERY_META = '_qalam_totp_recovery_hashes';
    const LAST_COUNTER_META = '_qalam_totp_last_counter';

    public static function is_enrolled( $user_id ) {
        return '1' === (string) get_user_meta( (int) $user_id, self::ENABLED_META, true ) && '' !== (string) get_user_meta( (int) $user_id, self::SECRET_META, true );
    }

    public static function begin_enrollment( $user_id ) {
        $user_id=(int)$user_id; $user=get_userdata($user_id);
        if(!$user instanceof \WP_User){throw new RuntimeException('الحساب غير موجود.');}
        $existing=get_transient('qalam_totp_enroll_'.$user_id); $codes=get_transient('qalam_totp_recovery_'.$user_id);
        if(is_string($existing) && is_array($codes)){
            try{$secret=self::decrypt($existing);}catch(\Throwable $e){$secret='';}
            if($secret!==''){return self::enrollment_payload($user,$secret,$codes);}
        }
        $secret=self::base32_encode(random_bytes(20));
        set_transient('qalam_totp_enroll_'.$user_id,self::encrypt($secret),15*MINUTE_IN_SECONDS);
        $codes=self::generate_recovery_codes();
        set_transient('qalam_totp_recovery_'.$user_id,$codes,15*MINUTE_IN_SECONDS);
        return self::enrollment_payload($user,$secret,$codes);
    }

    private static function enrollment_payload($user,$secret,$codes){
        $issuer=rawurlencode((string)get_bloginfo('name').' - Qalam LMS');
        $label=rawurlencode((string)get_bloginfo('name').':'.$user->user_email);
        $uri='otpauth://totp/'.$label.'?secret='.rawurlencode($secret).'&issuer='.$issuer.'&algorithm=SHA1&digits=6&period=30';
        return array('secret'=>$secret,'uri'=>$uri,'recovery_codes'=>array_values($codes));
    }

    public static function confirm_enrollment($user_id,$code){
        $user_id=(int)$user_id; $encrypted=get_transient('qalam_totp_enroll_'.$user_id); $codes=get_transient('qalam_totp_recovery_'.$user_id);
        if(!is_string($encrypted)||!is_array($codes)){return false;}
        try{$secret=self::decrypt($encrypted);}catch(\Throwable $e){return false;}
        if(!self::verify_code($secret,(string)$code,time(),false,0)){return false;}
        update_user_meta($user_id,self::SECRET_META,self::encrypt($secret)); update_user_meta($user_id,self::ENABLED_META,'1');
        $hashes=array(); foreach($codes as $v){$hashes[]=password_hash(strtoupper((string)$v),PASSWORD_DEFAULT);} update_user_meta($user_id,self::RECOVERY_META,$hashes);
        delete_user_meta($user_id,self::LAST_COUNTER_META); delete_transient('qalam_totp_enroll_'.$user_id); delete_transient('qalam_totp_recovery_'.$user_id); return true;
    }
    public static function disable($user_id){$user_id=(int)$user_id; delete_user_meta($user_id,self::SECRET_META);delete_user_meta($user_id,self::ENABLED_META);delete_user_meta($user_id,self::RECOVERY_META);delete_user_meta($user_id,self::LAST_COUNTER_META);}
    public static function verify_user_code($user_id,$code){
        $user_id=(int)$user_id; if(!self::is_enrolled($user_id))return false; try{$secret=self::decrypt((string)get_user_meta($user_id,self::SECRET_META,true));}catch(\Throwable $e){return false;} return self::verify_code($secret,(string)$code,time(),true,$user_id);
    }
    public static function consume_recovery_code($user_id,$code){
        $user_id=(int)$user_id; $code=strtoupper(preg_replace('/[^A-Z0-9]/i','',(string)$code)); if(strlen($code)<8)return false; $hashes=get_user_meta($user_id,self::RECOVERY_META,true); if(!is_array($hashes))return false;
        foreach($hashes as $i=>$hash){if(is_string($hash)&&password_verify($code,$hash)){unset($hashes[$i]);update_user_meta($user_id,self::RECOVERY_META,array_values($hashes));return true;}} return false;
    }
    public static function code_at($secret,$timestamp,$period=30,$digits=6){
        $counter=intdiv(max(0,(int)$timestamp),(int)$period); $binary=pack('N2',(int)floor($counter/4294967296),$counter%4294967296); $hash=hash_hmac('sha1',$binary,self::base32_decode((string)$secret),true); $offset=ord($hash[19])&0x0f;
        $value=((ord($hash[$offset])&0x7f)<<24)|((ord($hash[$offset+1])&0xff)<<16)|((ord($hash[$offset+2])&0xff)<<8)|(ord($hash[$offset+3])&0xff); $mod=pow(10,(int)$digits); return str_pad((string)($value%$mod),(int)$digits,'0',STR_PAD_LEFT);
    }
    public static function verify_code($secret,$code,$timestamp,$prevent_replay=false,$user_id=0){
        $code=preg_replace('/\D/','',(string)$code); if(6!==strlen($code))return false; $current=intdiv((int)$timestamp,30); $last=($prevent_replay&&(int)$user_id>0)?(int)get_user_meta((int)$user_id,self::LAST_COUNTER_META,true):-1;
        for($delta=-1;$delta<=1;$delta++){ $candidate=$current+$delta; if($candidate<0||($prevent_replay&&$candidate<=$last))continue; if(hash_equals(self::code_at($secret,$candidate*30),$code)){if($prevent_replay&&(int)$user_id>0)update_user_meta((int)$user_id,self::LAST_COUNTER_META,$candidate);return true;}} return false;
    }
    private static function generate_recovery_codes(){ $out=array(); for($i=0;$i<8;$i++)$out[]=strtoupper(substr(bin2hex(random_bytes(6)),0,12)); return $out; }
    private static function encrypt($plain){$key=hash('sha256',wp_salt('auth').'|qalam-totp-v1',true); if(function_exists('sodium_crypto_secretbox')){$nonce=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);return 's1:'.base64_encode($nonce.sodium_crypto_secretbox($plain,$nonce,$key));}$iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($plain,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag,'qalam-totp-v1');if(false===$cipher)throw new RuntimeException('تعذر حماية مفتاح التحقق بخطوتين.');return 'o1:'.base64_encode($iv.$tag.$cipher);}
    private static function decrypt($payload){$key=hash('sha256',wp_salt('auth').'|qalam-totp-v1',true);if(0===strpos($payload,'s1:')&&function_exists('sodium_crypto_secretbox_open')){$raw=base64_decode(substr($payload,3),true);if(false===$raw||strlen($raw)<=SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)throw new RuntimeException('مفتاح التحقق بخطوتين تالف.');$nonce=substr($raw,0,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);$plain=sodium_crypto_secretbox_open(substr($raw,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),$nonce,$key);if(false===$plain)throw new RuntimeException('تعذر فك حماية مفتاح التحقق بخطوتين.');return $plain;}if(0===strpos($payload,'o1:')){$raw=base64_decode(substr($payload,3),true);if(false===$raw||strlen($raw)<=28)throw new RuntimeException('مفتاح التحقق بخطوتين تالف.');$iv=substr($raw,0,12);$tag=substr($raw,12,16);$plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag,'qalam-totp-v1');if(false===$plain)throw new RuntimeException('تعذر فك حماية مفتاح التحقق بخطوتين.');return $plain;}throw new RuntimeException('صيغة مفتاح التحقق بخطوتين غير مدعومة.');}
    private static function base32_encode($data){$alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$bits='';foreach(str_split($data) as $c)$bits.=str_pad(decbin(ord($c)),8,'0',STR_PAD_LEFT);$out='';foreach(str_split($bits,5) as $chunk){$chunk=str_pad($chunk,5,'0');$out.=$alphabet[bindec($chunk)];}return $out;}
    private static function base32_decode($secret){$alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$secret=strtoupper(preg_replace('/[^A-Z2-7]/','',(string)$secret));$bits='';foreach(str_split($secret) as $c){$pos=strpos($alphabet,$c);if(false===$pos)throw new RuntimeException('مفتاح TOTP غير صالح.');$bits.=str_pad(decbin($pos),5,'0',STR_PAD_LEFT);}$out='';foreach(str_split($bits,8) as $chunk){if(strlen($chunk)<8)break;$out.=chr(bindec($chunk));}return $out;}
}
