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
        $this->redirect('Chat/index');



        include template();
    }
}