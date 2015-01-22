<?php
/**
 * Created by PhpStorm.
 * User: wuzhuo
 * Date: 15/1/21
 * Time: 上午11:50
 */


namespace core;

class Controller {

    public function __construct() {

    }

    protected function redirect($u, $vars="") {
        $tmp = explode('/',$u);

        if (count($tmp) > 1) {
            $controller = array_shift($tmp);
        }
        $action = array_shift($tmp);

        $url = APP_ROOT.str_replace('//','/','index.php/'.$controller.'/'.$action);
        if($vars) {
            $vars = str_replace(array('=','&'),'/',$vars);
            $url .= '/'.$vars;
        }

        header("location:".$url);
    }

    protected function success() {

    }

    protected function error() {

    }

    protected function returnJson() {

    }

}