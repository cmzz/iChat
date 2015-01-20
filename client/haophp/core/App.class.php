<?php

namespace core;
use core\Socket;
use core\DB;


class App {
    private static $_instance; 

    /**
     * 初始化方法
     * @return [type] [description]
     */
    public function init() {
    	// 注册自动加载方法
    	spl_autoload_register(array($this,'autoload'));    	
        set_exception_handler(array($this,"ichatException"));

        date_default_timezone_set("Asia/Chongqing");
    }

    /**
     * 启动程序
     * @return [type] [description]
     */
    public function start() {
    	$this->init();



    	// 这里还可以做其他操作
    }

    public static function autoload($class) {
    	$arr = explode('\\', $class);
    	$className = array_pop($arr);
    	$path = _ROOT_.implode('/', $arr).'/';
    	$path.$className.CLASS_EXT;

    	include_once $path.$className.CLASS_EXT;
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

    public function ichatException(\Exception $e) {
        echo "Exception ('{$e->getMessage()}')\n{$e}\n";
    }

    // 禁止外面实例化及复制
    private function __construct() {}
    private function __clone()  {}
}