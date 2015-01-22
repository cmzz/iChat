<?php

/**
 * 配置处理类
 */

namespace core;

class Config {
	static public $confs;

	/**
	 * 获取配置
	 * @param $conf
	 * @param string $val
	 * @return mixed
	 */
	static public function get($conf, $val='') {

		if(self::$confs[$conf]) {
			return $val ? self::$confs[$conf][$val] : self::$confs[$conf];
		}

		if(!strpos($conf, '.')) {
			$file = $conf;			
		} else {
			$tmp = explode('.', $conf);
			$file = $tmp[0];
			$key = $tmp[0];
		}

		$tmp = array(
			CONF_PATH,
			APP_PATH.'config/'
		);

		foreach($tmp as $path) {
			$file_path = $path.$file.'.php';
			if(file_exists($file_path)) {
				self::$confs[$conf] = $config = include_once $file_path;
			}
		}

		if($val) {
			return $config[$val];
		}

		return $config;
	}

	/**
	 * 运行时设置属性值，不会写入配置文件
	 * @param $conf
	 * @param $key
	 * @param $val
	 */
	static public function set($conf, $key, $val) {
		if(!self::$confs[$conf]) {
			$config = self::get($conf);
			if(!$config) return false;
		}


		self::$confs[$conf][$key] = $val;
		return true;
	}


}