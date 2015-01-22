<?php

namespace core;
use core\DB,
    core\Router;


class App {
    private static $_instance; 

    /**
     * 初始化方法
     * @return [type] [description]
     */
    public function init() {
        session_start();

    	// 注册自动加载方法
    	spl_autoload_register(array($this,'autoload'));    	
        set_exception_handler(array($this,"haoException"));

        date_default_timezone_set("Asia/Chongqing");

        if($const = Config::get('global','const')) {
            foreach ($const as $ck => $cv) {
                defined($ck) || define($ck, $cv);
            }
        }

        if(file_exists(APP_PATH.'functions.php')) {
            include_once APP_PATH.'functions.php';
        }

        define("APP_ROOT", str_replace($_SERVER['DOCUMENT_ROOT'],'',APP_DIR) );
        define('_STATIC', str_replace($_SERVER['DOCUMENT_ROOT'],'',APP_DIR.'static'));
    }

    /**
     * 启动程序
     * @return [type] [description]
     */
    public function start() {
    	$this->init();
        //数据库初使化
        DB::init();

        //路由初使化
        Router::init();

        //TODO:: 还需要支持自定义的路由
        if(file_exists(APP_PATH.'config/Router.php')) {

        }

        //调用控制器
        $controllerFile = APP_PATH.MODULE.'controller/'.CONTROLLER.CLASS_EXT;
        if(file_exists($controllerFile)) {
            include_once $controllerFile;
            $controller = MODULE ? MODULE : "Controller".'\\'.CONTROLLER;
            $action = ACTION;
            $obj = new $controller();
            if(method_exists($obj,$action)) {
                    $obj->$action();
            } else {
                throw new \Exception("非法操作: ".ACTION);
            }

        } else {
            throw new \Exception("无法加载控制器: ".CONTROLLER);
        }
    }

    public static function autoload($class) {
    	$arr = explode('\\', $class);
    	$className = array_pop($arr);

        $tmp = array(
            'coreConfig' => _ROOT_.implode('/', $arr).'/',
            'appLibConfig' => APP_PATH.'lib/',
        );

//    	$path.$className.CLASS_EXT;

        foreach ($tmp as $path) {
            $cfile = $path.$className.CLASS_EXT;
            if(file_exists($cfile)) {
                include_once $cfile;
            }
        }

    }

    /**
     * 单例
     * @return [type] [description]
     */
    public static function get() {    
        if(! (self::$_instance instanceof self) ) {    
            self::$_instance = new self();    
        }  
        return self::$_instance;
    }

    public function haoException(\Exception $e) {
        include _ROOT_."template/exception.php";
    }

    // 禁止外面实例化及复制
    private function __construct() {}
    private function __clone()  {}
}