<?php
/**
 * Created by PhpStorm.
 * User: wuzhuo
 * Date: 15/1/20
 * Time: 下午7:48
 */


namespace Controller;
use core\Config;
use core\Controller;

class Index extends Controller {

    public function __construct() {

        parent::__construct();
    }

    public function index() {
//        if(!session('uid')) {
//            $this->redirect('login');
//        }


        include template();
    }

    /**
     * 登陆
     */
    public function login() {
        $api = Config::get('global','qqconnect');
        $callback = url('qqlogin','','',1);

        $loginurl = $this->getQqLoginUrl($api['appid'],$callback);


        include template();
    }

    /**
     * 注册
     */
    public function reg () {
        $api = Config::get('global','qqconnect');
        if($_REQUEST['state'] == session('state')) //csrf
        {
            $callback = url('qqlogin','','',1);
            $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
                . "client_id=" . $api["appid"]. "&redirect_uri=" . urlencode($callback)
                . "&client_secret=" . $api["appkey"]. "&code=" . $_REQUEST["code"];

            $response = file_get_contents($token_url);
            if (strpos($response, "callback") !== false)
            {
                $lpos = strpos($response, "(");
                $rpos = strrpos($response, ")");
                $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                $msg = json_decode($response);
                if (isset($msg->error))
                {
                    echo "<h3>error:</h3>" . $msg->error;
                    echo "<h3>msg  :</h3>" . $msg->error_description;
                    exit;
                }
            }

            $params = array();
            parse_str($response, $params);

            //debug
            print_r($params);

            //set access token to session
            $_SESSION["access_token"] = $params["access_token"];
        } else {
            echo("The state does not match. You may be a victim of CSRF.");
        }
    }

    /**
     * QQ登陆回调函数
     */
    public function qqlogin() {

    }

    protected function getQqLoginUrl($appid, $callback)
    {
        $state = md5(uniqid(rand(), TRUE)); //CSRF protection
        session('state', $state);

        $login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
            . $appid . "&redirect_uri=" . urlencode($callback)
            . "&state=" . $state
            . "&scope=get_user_info";
        return $login_url;
    }
}