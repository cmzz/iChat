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
use core\DB;

class Index extends Controller {

    public function __construct() {

        parent::__construct();
    }

    public function index() {
        if(!session('uid')) {
            $this->redirect('login');
        }

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
            session('access_token',$params["access_token"]);

            $this->get_openid();
            $data = $this->get_user_info();
            $data['openid'] = session('openid');

            if($info = DB::fetch_first("select * from %t where openid=%s",array('member',$data['openid']))) {
                $id = $info['id'];
            } else {
                $data['figureurl'] = $data['figureurl_1'];
                $id = DB::insert('member', $data);
                $info = $data;
            }

            if($id) {
                $ol['nickname'] = $info['nickname'];
                $ol['uid'] = $id;
                $ol['lasttime'] = TIME;

                if(!DB::fetch_first("select * from %t where uid=%d",array('online',$id))) {
                    $oid = DB::insert('online',$ol);
                }

                session('uid',$id);
                session('info', $info);
                session('openid', $info['openid']);

                $this->redirect('index','openid='.$info['openid']);
                exit;
            }

            $this->redirect('login');

        } else {
            echo("need login");
        }
    }

    protected function get_openid() {
        $graph_url = "https://graph.qq.com/oauth2.0/me?access_token="
            . session('access_token');

        $str  = file_get_contents($graph_url);

        if (strpos($str, "callback") !== false ) {
            $lpos = strpos($str, "(");
            $rpos = strrpos($str, ")");
            $str  = substr($str, $lpos + 1, $rpos - $lpos -1);
        }

        $user = json_decode($str);
        if (isset($user->error))
        {
            echo "<h3>error:</h3>" . $user->error;
            echo "<h3>msg  :</h3>" . $user->error_description;
            exit;
        }

        session("openid", $user->openid);
    }

    protected function get_user_info() {
        $api = Config::get('global','qqconnect');
        $get_user_info = "https://graph.qq.com/user/get_user_info?"
            . "access_token=" . session('access_token')
            . "&oauth_consumer_key=" . $api["appid"]
            . "&openid=" . session("openid")
            . "&format=json";

        $info = file_get_contents($get_user_info);
        $arr = json_decode($info, true);

        return $arr;
    }

    protected function getQqLoginUrl($appid, $callback) {
        $state = md5(uniqid(rand(), TRUE));
        session('state', $state);

        $login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
            . $appid . "&redirect_uri=" . urlencode($callback)
            . "&state=" . $state
            . "&scope=get_user_info";
        return $login_url;
    }
}