<?php
/**
 * Created by PhpStorm.
 * User: wuzhuo
 * Date: 15/1/21
 * Time: 下午2:34
 */

function http_get($url) {
    $oCurl = curl_init();
    if (stripos($url, "https://") !== FALSE) {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:' . CLIENTIP, 'CLIENT-IP:' . CLIENTIP));
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);

    curl_close($oCurl);

    if (intval($aStatus["http_code"]) == 200) {
        return $sContent;
    } else {
        return false;
    }
}



/**
 * @param string $email 电子邮箱
 * @return boolean false 失败, true 正确
 */
function isEmail($email) {
    $pattern='/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i';
    if(preg_match($pattern,$email)) {
        return true;
    }else{
        return false;
    }
}


/**
 * @param int $length
 * @return string
 */
function getRandStr($length = 6){
    $changestr = array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    $rand = array_rand($changestr,$length);
    $randStr = '';
    foreach($rand as $k=>$v){
        $randStr.=$changestr[$v];
    }
    return $randStr;
}


/**
 * 获取ip地址
 * @return string
 */
function getIP()
{
    static $realip;
    if (isset($_SERVER)){
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $realip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $realip = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")){
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }
    return $realip;
}

/**
 * 生成密码
 *
 * @param $uid
 * @param $password
 * @param $salt
 * @return bool|string
 */
function hashpassword ($uid,$password,$salt) {
    if(!$password) return false;
    $tmp = md5(md5($password.$salt).$uid);
    return $tmp;
}

/**
 * 验证码密
 * @param $uid
 * @param $password
 * @param $salt
 * @param $_pass
 * @return bool
 */
function checkpassword($uid,$password,$salt,$_pass) {
    return hashpassword($uid,$_pass,$salt) == $password ? true : false;
}