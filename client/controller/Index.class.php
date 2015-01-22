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

    }

    /**
     * QQ登陆回调函数
     */
    public function qqlogin() {

    }

    protected function getQqLoginUrl($appid, $callback)
    {
        $state = md5(uniqid(rand(), TRUE)); //CSRF protection
        $login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
            . $appid . "&redirect_uri=" . urlencode($callback)
            . "&state=" . $state
            . "&scope=get_user_info";
        return $login_url;
    }
}