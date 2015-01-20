<?php

/**
 * 配置处理类
 */

namespace core;

class Command {

	######## 普通命令
	/**
	 * 设置及修改昵称
	 * @return [type] [description]
	 */
	public function name(\swoole_server $serv, $fd, $from_id='', $data='') {
		$ret = DB::update("online",array('nickname'=>$data)," fd = ".$fd);
	}

	/**
	 * 关闭连接
	 * 如果传了fd ，则关闭指定连接
	 * @return [type] [description]
	 */
	public function close(\swoole_server $serv, $fd='', $from_id='', $data='') {
		$serv->close($fd);
	}



	######## 系统管理命令 
	/**
	 * 得新加载
	 * @return [type] [description]
	 */
	public function reload(\swoole_server $serv, $fd='', $from_id='', $data='') {
		$serv->reload();
	}

	/**
	 * 结束
	 * @return [type] [description]
	 */
	public function shutdown(\swoole_server $serv) {
		$serv->shutdown();
	}

	/**
	 * 获取连接信息
	 * @return [type] [description]
	 */
	public function info(\swoole_server $serv, $fd) {

	}

}