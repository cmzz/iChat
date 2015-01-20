<?php

/**
 * 配置处理类
 */

namespace core;

class Config {

	static public function get($conf, $val='') {
		if(!strpos($conf, '.')) {
			$file = $conf;			
		} else {
			$tmp = explode('.', $conf);
			$file = $tmp[0];
			$key = $tmp[0];
		}

		$file_path = CONF_PATH.$file.'.php';
		$config = include_once $file_path;

		if(!$val) {
			return $config;
		} 
	}

}