<?php

namespace core;
use core\Config;
use core\Command;
use core\DB;

class Handler {
	const OPCODE_CONTINUATION_FRAME = 0x0;
	const OPCODE_TEXT_FRAME         = 0x1;
	const OPCODE_BINARY_FRAME       = 0x2;
	const OPCODE_CONNECTION_CLOSE   = 0x8;
	const OPCODE_PING               = 0x9;
	const OPCODE_PONG               = 0xa;

	const CLOSE_NORMAL              = 1000;
	const CLOSE_GOING_AWAY          = 1001;
	const CLOSE_PROTOCOL_ERROR      = 1002;
	const CLOSE_DATA_ERROR          = 1003;
	const CLOSE_STATUS_ERROR        = 1005;
	const CLOSE_ABNORMAL            = 1006;
	const CLOSE_MESSAGE_ERROR       = 1007;
	const CLOSE_POLICY_ERROR        = 1008;
	const CLOSE_MESSAGE_TOO_BIG     = 1009;
	const CLOSE_EXTENSION_MISSING   = 1010;
	const CLOSE_SERVER_ERROR        = 1011;
	const CLOSE_TLS                 = 1015;
	const WEBSOCKET_VERSION         = 13;

	const TIMEER_ONLINEMEMBERS		= 1000*60*1;

	const TYPE_NORMAL				= 100; //普通消息
	const TYPE_PERSERNOL			= 101; //私语
	const TYPE_SYSTEM_NOTICE		= 200; //系统通知
	const TYPE_PUSH_ONLINEMEMBERS   = 1000;//推送在线会员列表消息


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
		// 进程0 执行在线用户检查及推送 3分钟
		if ($serv->worker_id == 0) $serv->addtimer(self::TIMEER_ONLINEMEMBERS);
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


		//更新用户在线状态
//		$db = new DB();
//		$db->insert("online", $data);
		echo "\n".date("H:i:s")." : #".$fd." 进入频道 \n";
	}

	/**
	 * 服务器收到客户端消息时的回调
	 * @return [type] [description]
	 */
	public function receive(\swoole_server $serv, $fd, $from_id, $data) {
		//http握手
		$this->revHttp($serv, $fd, $from_id, $data);

		$wsdata = $this->parseFrame($data);
		$msg = $wsdata['message'];
		$msg = trim($msg);

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

		$serv->send($fd,$this->frame($msg));
		$serv->task( json_encode($d));
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
	 * 异步任务
	 * @return [type] [description]
	 */
	public function task($serv, $task_id, $from_id, $data) {
		static $db = null;
		if(!$db) {$db = new DB();}

		$d = json_decode($data, true);
		$type = $d['type'];
		unset($d['type']);

		//获取在线用户列表
		$onlines = $db->fetch_all('select * from %t where 1', array('online'));
		$notme = false;

		$message = "";
		switch($type) {
			//处理消息
			case 'message':
				#1 将用户消息入库
				$db->insert("message", $d);
				$notme = true;
				$message = $d['msg'];
				$type = self::TYPE_NORMAL;
				break;

			//推送在线会员列表
			case 'pushOnline':
				$message = $db->fetch_all("select * from %t where 1", array('online'));
				$type = self::TYPE_PUSH_ONLINEMEMBERS;

				//给单独用户推送在线会员列表
				if($d['sendto']) {
					$msg = $this->createRecMsg($message, $type);
					$serv->send((int)$d['sendto'],$this->frame($msg),$from_id);
				}

				break;

			case 'login':
				if(DB::fetch_first("select * from %t where openid=%s",array('online',$d['openid']))) {
					DB::update('online',array('fd'=>$d['fd'])," openid='".$d['openid']."'");
				} else {
					$serv->close($d['fd']);
				}

				break;
		}

		//将消息发给在线用户
		if($onlines) {
			echo date("H:i:s")." : 当前有 ".count($onlines)." 人在线，开始发送消息 == ". json_encode($message) ." \n";
			foreach ($onlines as $key => $val) {
				if($notme && $val['fd'] == $d['fd']) continue;

				$msg = $this->createRecMsg($message, $type, $val['fd']);

				//开始发送消息
				$serv->send((int)$val['fd'],$this->frame($msg),$from_id);
			}
		} else {
			echo "没有在线用户";
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
		switch ($interval) {
			//3分钟任务，检查在线用户
			case self::TIMEER_ONLINEMEMBERS:
				$data['type'] = "pushOnline";
				$serv->task( json_encode($data) );
				break;
		}

		echo $serv->worker_id;
		echo "Timer[$interval] is call\n";
	}


	/**
	 * 创建返回给客户的数据
	 * @param $data
	 * @param $type
	 * 	100 为普通消息
	 *  101 用户上线消息
	 *  109 用户离线消息
	 */
	public function createRecMsg($data, $type, $from="", $to='') {
		$tmp = array();
		$tmp['type'] = $type;
		$tmp['message'] = $data;
		$tmp['from'] = $from;
		$tmp['to'] = $to;

		return json_encode($tmp);
	}

	/**
	 * 解析命令
	 * @param $serv
	 * @param $fd
	 * @param $from_id
	 * @param $data
	 * @return bool
	 */
	public function checkcmd($serv, $fd, $from_id, $data) {
		preg_match("/^cmd-([a-zA-Z]{1,20}:)(.*)/", $data, $match);
		if(empty($match) || !$match[1]) return false;

		$cmd = trim(str_replace('cmd-', '', $match[1]),":");
		$value = $match[2];

		switch($cmd) {
			case "getOnlineList":
					$d['type'] = "pushOnline";
					$d['sendto'] = $fd;

					$serv->task( json_encode($d) );
				exit;
				break;

			case "login":
				$d['type'] = "login";
				$d['openid'] = $value;
				$d['fd'] = $fd;

				$serv->task( json_encode($d) );

				exit;
				break;
		}



		$msg = $this->createRecMsg("Error: 未找到命令 ".$cmd, self::TYPE_SYSTEM_NOTICE);
		$serv->send($fd, $this->frame($msg), $from_id);

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
	public function frame($message,  $opcode = self::OPCODE_TEXT_FRAME, $end = true ) {
		$fin    = true === $end ? 0x1 : 0x0;
		$rsv1   = 0x0;
		$rsv2   = 0x0;
		$rsv3   = 0x0;
		$mask   = 0x1;
		$length = strlen($message);
		$out    = chr(
			($fin  << 7)
			| ($rsv1 << 6)
			| ($rsv2 << 5)
			| ($rsv3 << 4)
			| $opcode
		);

		if(0xffff < $length)
			$out .= chr(0x7f) . pack('NN', 0, $length);
		elseif(0x7d < $length)
			$out .= chr(0x7e) . pack('n', $length);
		else
			$out .= chr($length);

		$out .= $message;
		return $out;
	}

	private function parseFrame(&$data)
	{
		//websocket
		$ws  = array();
		$ws['finish'] = false;
		$data_offset = 0;

		//fin:1 rsv1:1 rsv2:1 rsv3:1 opcode:4
		$handle        = ord($data[$data_offset]);
		$ws['fin']    = ($handle >> 7) & 0x1;
		$ws['rsv1']   = ($handle >> 6) & 0x1;
		$ws['rsv2']   = ($handle >> 5) & 0x1;
		$ws['rsv3']   = ($handle >> 4) & 0x1;
		$ws['opcode'] =  $handle       & 0xf;
		$data_offset++;

		//mask:1 length:7
		$handle        = ord($data[$data_offset]);
		$ws['mask']   = ($handle >> 7) & 0x1;
		//0-125
		$ws['length'] =  $handle       & 0x7f;
		$length        = &$ws['length'];
		$data_offset++;

		if(0x0 !== $ws['rsv1'] || 0x0 !== $ws['rsv2'] || 0x0 !== $ws['rsv3'])
		{
			//$this->close(self::CLOSE_PROTOCOL_ERROR);
			return false;
		}
		if(0 === $length)
		{
			$ws['message'] = '';
			$data = substr($data, $data_offset + 4);
			return $ws;
		}
		//126 short
		elseif(0x7e === $length)
		{
			//2
			$handle = unpack('nl', substr($data, $data_offset, 2));
			$data_offset += 2;
			$length = $handle['l'];
		}
		//127 int64
		elseif(0x7f === $length)
		{
			//8
			$handle = unpack('N*l', substr($data, $data_offset, 8));
			$data_offset += 8;
			$length = $handle['l2'];
			if($length > $this->max_frame_size)
			{
				$this->log('Message is too long.');
				return false;
			}
		}

		if(0x0 !== $ws['mask'])
		{
			//int32
			$ws['mask'] = array_map('ord', str_split(substr($data, $data_offset, 4)));
			$data_offset += 4;
		}

		//把头去掉
		$data = substr($data, $data_offset);
		//完整的一个数据帧
		if (strlen($data) >= $length) {
			$ws['finish'] = true;
			$ws['buffer'] =  substr($data, 0, $length);
			$ws['message'] = $this->parseMessage($ws);
			//截取数据
			$data = substr($data, $length);
			return $ws;
		} else { //需要继续等待数据
			$ws['finish'] = false;
			$ws['buffer'] = $buffer;
			$buffer = "";
			return $ws;
		}

	}

	protected function parseMessage($ws)
	{
		$buffer = $ws['buffer'];
		//没有mask
		if(0x0 !== $ws['mask'])
		{
			$maskC = 0;
			for($j = 0, $_length = $ws['length']; $j < $_length; ++$j)
			{
				$buffer[$j] = chr(ord($buffer[$j]) ^ $ws['mask'][$maskC]);
				$maskC       = ($maskC + 1) % 4;
			}
		}
		return $buffer;
	}
}