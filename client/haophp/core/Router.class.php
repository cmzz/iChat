<?php
/**
 * Created by PhpStorm.
 * User: wuzhuo
 * Date: 15/1/20
 * Time: 下午7:51
 */

namespace core;

class Router {

    static public function init() {
        $config = Config::get('global');
        $pathinfo = $_SERVER['PATH_INFO'];
        $queryString = $_SERVER['QUERY_STRING'];

        $pathinfo = str_replace('.html','',$pathinfo);

        $patharr = explode("/", trim($pathinfo,'/'));

        if($config['mutileModule']) {
            $module = array_shift($patharr);
            $controller = array_shift($patharr);
            $action = array_shift($patharr);
        } else if($config['singleController']) {
            $controller = $config['defauleController'] ? $config['defauleController'] : array_shift($patharr);
            $action = array_shift($patharr);
        } else {
            $controller = array_shift($patharr);
            $action = array_shift($patharr);
        }

        if($patharr) {
            for ($i = 0; $i < count($patharr); ) {
                $_GET[$patharr[$i]] = $patharr[$i+1];
                $i += 2;
            }
        }



        define("MODULE",$module ? $module.'/' : '');
        define('CONTROLLER',$controller ? $controller : 'Index');
        define('ACTION',$action ? $action : 'index');
    }
}