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

class Chat extends Controller {

    public function __construct() {

        parent::__construct();
    }

    public function index() {
        $info = DB::fetch_first('select * from %t where openid = %s',array('online', session('openid')));

        if(!$info['uid']) {
            $this->redirect('Login/index');
        }
        $member = DB::fetch_first("select * from %t where id = %d", array('member',$info['uid']));

        $info['avatar'] = $member['figureurl'] ? $member['figureurl'] : 'http://tp3.sinaimg.cn/1221788390/180/1289279591/0';

        include template();
    }

    /**
     * 登陆
     */
    public function login() {
        $api = Config::get('global','qqconnect');
        $weibo = Config::get('global','weibo');
        $callback = url('qqlogin','','',1);

        $loginurl = $this->getQqLoginUrl($api['appid'],$callback);

        include template();
    }
}