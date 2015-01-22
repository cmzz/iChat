<?php

global $config;

error_reporting(7);

//定义类文件后缀名
const CLASS_EXT = ".class.php";

//定义框架目录
const _ROOT_ = __DIR__.'/';

//定义项目其他目录
const CONF_PATH = _ROOT_.'config/';
const CORE_PATH = _ROOT_.'core/';

define('TIME', $_SERVER['REQUEST_TIME'] ?  $_SERVER['REQUEST_TIME'] : time());

//引入公共函数
include _ROOT_.'functions.php';


include_once CORE_PATH.'/App'.CLASS_EXT;
$app = \core\App::get();

//启动
$app->start();