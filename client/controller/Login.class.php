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
use lib\SaeTOAuth;

class Login extends Controller {

    protected $wbo;

    public function __construct() {
        $weibo = Config::get('global','weibo');
        $this->wbo = new SaeTOAuth($weibo['appkey'],$weibo['appsecret']);

        parent::__construct();
    }

    public function index() {
        if(isset($_POST)) {
            $post = $_POST;

            if($info = DB::fetch_first("select * from %t where email = %s", array('member',$post['email']))) {

                if(checkpassword($info['id'],$info['password'],$info['salt'],$post['password'])) {
                    //登陆成功
                    $this->loginState($info);
                    $this->redirect('Chat/index');

                } else {
                    exit('密码错误');
                }
            } else {
                exit('用户不存在');
            }

            exit;
        }

        unset($_POST);

        $api = Config::get('global','qqconnect');

        $qqcallback = url('Login/qqlogin','','',1);
        $weibocallback = url('Login/weibologin','','',1);
        $weixincallback = url('Login/weibologin','','',1);


        $wbloginurl = $this->wbo->getAuthorizeURL( $weibocallback  );
        $qqloginurl = $this->getQqLoginUrl($api['appid'],$qqcallback);



        include template('login');
    }


    private function loginState ($info) {
        session('uid', $info['id']);
        session('info', $info);
        if(!$info['openid']) {
            $info['openid'] = md5($info['id'].getRandStr());
        }
        session('openid', $info['openid']);

        $ol['nickname'] = $info['nickname'];
        $ol['uid'] = $info['id'];
        $ol['lasttime'] = TIME;
        $ol['openid'] = $info['openid'];

        if(!DB::fetch_first("select * from %t where uid=%d",array('online',$info['id']))) {
            $oid = DB::insert('online',$ol);
        }
        unset($_POST);
    }

    public function reg() {
        if( isset($_POST['nickname']) ) {
            $post = $_POST;

            dump($post);
            if($post['password'] != $post['repassword']) {
                echo  "两次密码不一至";
                exit;
            }

            if(DB::fetch_first("select * from %t where email=%s", array('member',$post['email']))) {
                exit('邮箱被占用');
            }


            $post['salt'] = getRandStr();
            $post['createtime'] = TIME;
            $id = DB::insert('member',$post);
            if($id) {
                $up['password'] = hashpassword($id,$post['password'],$post['salt']);
                DB::update('member',$up, 'id='.$id);
                $this->redirect('Login/index');
            }

            exit;
        }

        include template();
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

                //保存登陆状态
                $this->loginState($info);
                $this->redirect('index','openid='.$info['openid']);
                exit;
            }

            $this->redirect('login');

        } else {
            echo("need login");
        }
    }

    protected function get_openid() {
        $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=". session('access_token');

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