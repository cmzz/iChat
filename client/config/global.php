<?php
/**
 * Created by PhpStorm.
 * User: wuzhuo
 * Date: 15/1/20
 * Time: 下午7:58
 */

/**
 * 关于模式， 黑认为我控制器，单模块
 * multiModule ＝ true  可开启多模块， 模块目录需位于APP_PATH路径下
 * allowModule ＝ array() 指定允许访问的模块列表
 *
 * 如果应用很简单，也可以开启单控制器模式，即只有一个控制器，路由只需要传方法即可
 */
return array(
    'singleController'   => true,
    'defauleController'  => 'Index',

    'allowModule' => array(),

    //创建自定义的常量
    'const' => array(

    ),

    //登陆
    'qqconnect' => array(
        'appid'     => '101188364',
        'appkey'    => '493f767490f5a3694b126d981b470f57'
    ),
);