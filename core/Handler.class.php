<?php

namespace core;
use core\Config;
use core\Command;
use core\DB;

class Handler {
	public $db;
	/**
	 * 服务启动回调
	 * @param  swoole_server $serv [description]
	 * @return [type]              [description]
	 */
	public function start(\swoole_server $serv) {
		$this->db = new DB();
		echo "Server [".date("H:i:s",time())."] : 服务启动成功！ \n";
		//服务启动时清空在线列表
		$this->db->query("TRUNCATE TABLE %t", array("online"));

	}

	/**
	 * 进程启动
	 * @return [type] [description]
	 */
	public function workStart($serv) {
//		$serv->addtimer(5000);
	}

	/**
	 * 进程关闭
	 * @return [type] [description]
	 */
	public function workStop() {
		
	}

	/**
	 * 进程异程
	 * @return [type] [description]
	 */
	public function workError() {

	}

	/**
	 * 新链接进入时的回调
	 * @return [type] [description]
	 */
	public function connect($serv, $fd, $from_id) {
		#可以在这里做将用户加入到在线列表		
		$info = $serv->connection_info($fd, $from_id);
		$data = array(
			'from_id' => $from_id,
			'fd' => $fd,
			'ip' => ip2long($info['remote_ip']),
			'lasttime' => TIME,
		);

		$db = new DB();
		$msgid = $db->insert("online", $data);
		echo date("H:i:s")." : #".$fd." 进入频道 \n";
	}

	/**
	 * 服务器收到客户端消息时的回调
	 * @return [type] [description]
	 */
	public function receive(\swoole_server $serv, $fd, $from_id, $data) {
		//http握手
		$this->revHttp($serv, $fd, $from_id, $data);
		$ttt = $this->decode($data);

		$msg = trim($ttt);
		//忽略空消息
		if(empty($msg)) return false;

		#检查普通用户命令
		$this->checkcmd($serv, $fd, $from_id,$msg);

		#确认用户是否管理员

		#管理员命令
		$d = array(
			'msg' => $msg,
			'posttime' => TIME,
			'fd' => $fd,
			'from_id' =>$from_id,
			'type' => 'message'
		);

		$serv->send($fd,$this->frame($d['msg']),$from_id);

		echo date("H:i:s")." : #".$fd." 发送了消息 ".$d['msg']." \n";
//		$serv->task( json_encode($d));


		/////////

		$db = new DB();
		#1 将用户消息入库
		unset($d['type']);
		$msgid = $db->insert("message", $d);

		#2 将消息发给其它在线用户
		$onlines = $db->fetch_all("select * from %t where fd != %d", array('online', $d['fd']));

		echo date("H:i:s")." : 当前有 ".count($onlines)." 人在线，开始发送消息 \"".$d['msg']."\" \n";
		if($onlines) {
			foreach ($onlines as $key => $val) {
				if($val['nickname']) {
					$nick = $val['nickname']." : ";
				}

				$serv->send($val['fd'], $this->frame($d['msg'])."\n", $val['from_id']);
				echo date("H:i:s")." : 消息已发送给 #".$val['fd']." \n";

			}
		}

		/////////


	}

	/**
	 * 用户退出
	 * @return [type] [description]
	 */
	public function close($serv, $fd, $from_id) {
		//从在线列表移除
		echo date("H:i:s")." : #".$fd." 离开了  \n";
		$db = new DB();
		$db->delete("online", "fd=".$fd);
	}

	/**
	 * 任务
	 * @return [type] [description]
	 */
	public function task($serv,$task_id,$from_id, $data) {
		static $db = null;
		if(!$db) {
			$db = new DB();
		}

		$d = json_decode($data, true);
		$type = $d['type'];
		unset($d['type']);

		switch($type) {
			case "message":
				#1 将用户消息入库
				$msgid = $db->insert("message", $d);

				#2 将消息发给其它在线用户
				$onlines = $db->fetch_all("select * from %t where fd != %d", array('online', $d['fd']));

				echo date("H:i:s")." : 当前有 ".count($onlines)." 人在线，开始发送消息 #".$d['msg']." \n";
				if($onlines) {
					foreach ($onlines as $key => $val) {
						if($val['nickname']) {
							$nick = $val['nickname']." : ";
						}

						$serv->send($val['fd'], $this->frame($d['msg'])."\n", $val['from_id']);
						echo date("H:i:s")." : 消息已发送给 #".$val['fd']." \n";

					}
				}

				echo "\n";
				echo "\n";

				exit;
				break;
		}

	}

	/**
	 * 任务完成后
	 * @return [type] [description]
	 */
	public function finish($serv, $task_id, $data) {
		echo "Task {$task_id} finish\n";
		echo "Result: {$data}\n";
	}

	/**
	 * 服务器关闭
	 * @return [type] [description]
	 */
	public function shutdown() {
		echo "Task {$task_id} finish\n";
		echo "Result: {$data}\n";
	}

	/**
	 * 定时器
	 * @return [type] [description]
	 */
	public function timer($serv, $interval) {
		echo "Timer[$interval] is call\n";
	}

	public function checkcmd($serv, $fd, $from_id, $data) {
		preg_match("/(^cmd-[a-zA-Z]{1,20}:)(.*)/iUs", $data, $match);
		if(empty($match) || !$match[1]) return false;

		$cmd = trim(str_replace('cmd-', '', $match[1]),":");
		$value = str_replace($match[1], '', $data);

		$cmdobj = new Command();

		if(method_exists($cmdobj, $cmd) && in_array($cmd, array('name','close'))) {
			$cmdobj->$cmd($serv,$fd,$from_id,$value);
		} else {
			$serv->send($fd,"Error: 未找到命令 ".$cmd."\n", $from_id);
		}

		exit;
	}

	/**
	 * ws握手
	 * @return [type] [description]
	 */
	public function revHttp($serv, $fd, $from_id, $data) {
		$match = array();
		preg_match("/Sec-WebSocket-Key:(.*)/", $data, $match);
		if(!$match || !$match[1]) return false;

		$key = trim($match[1])."258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
		$response_key = base64_encode(sha1($key, true));
		$retstr = "HTTP/1.1 101 Web Socket Protocol Handshaker\r\n 
Upgrade:websocket\r\n 
Connection:Upgrade\r\n 
Sec-WebSocket-Accept:".$response_key." \r\n\r\n";
		$serv->send($fd,$retstr, $from_id);
		exit;
	}

	/**
	 * 解析tcp收到的二进制数据
	 * @param  [type] $buffer [description]
	 * @return [type]         [description]
	 */
	public function decode($buffer)  {
	    $len = $masks = $data = $decoded = null;
	    $len = ord($buffer[1]) & 127;
	    if ($len === 126)  {
	        $masks = substr($buffer, 4, 4);
	        $data = substr($buffer, 8);
	    } else if ($len === 127)  {
	        $masks = substr($buffer, 10, 4);
	        $data = substr($buffer, 14);
	    } else  {
	        $masks = substr($buffer, 2, 4);
	        $data = substr($buffer, 6);
	    }
	    for ($index = 0; $index < strlen($data); $index++) {
	        $decoded .= $data[$index] ^ $masks[$index % 4];
	    }
	    return $decoded;
	}

	/**
	 * 给tcp返回二进制数据
	 * @param  [type] $s [description]
	 * @return [type]    [description]
	 */
	public function frame($s) {
	    $a = str_split($s, 125);
	    if (count($a) == 1) {
	        return "\x81" . chr(strlen($a[0])) . $a[0];
	    }
	    $ns = "";
	    foreach ($a as $o) {
	        $ns .= "\x81" . chr(strlen($o)) . $o;
	    }
	    return $ns;
	}
}