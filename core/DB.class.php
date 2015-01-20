<?php

namespace core;
use core\Config;

class DB {
	public $pre;
	public $conn;
	public $errno;
	public $error;
	public $sql;
	public $lastSql;


	function __construct(){
		$config = Config::get('database');
		$config = array(
			'db_host'               =>  '127.0.0.1',
			'db_name'               =>  'iChat',
			'db_user'               =>  'root',
			'db_pwd'                =>  'root',
			'db_port'               =>  '3306',
			'db_pre'                =>  'chat_',
		);

		$this->pre = $config['db_pre'];

		if(!$config['db_host'] || !$config['db_name'] || !$config['db_user']) {
            throw new \Exception("请先设置数据库连接参数");  		
		}


		$mysqli = mysqli_init();

		if (!$mysqli) {
			throw new \Exception("mysqli_init failed");
			exit;
		}

		if (!$mysqli->real_connect($config['db_host'], $config['db_user'], $config['db_pwd'], $config['db_name'])) {
			throw new \Exception('Connect Error (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
			exit;
		}

		$this->conn = $mysqli;
	}

	/**
	 * 执行
	 * @return [type] [description]
	 */
	public function exec() {
		$result = $this->conn->query($this->sql);
		if($this->conn->errno) {
			throw new \Exception($this->conn->error);			
		}

		$this->lastSql = $this->sql;
		$this->sql = '';

		return $result;
	}

	/**
	 * 直接操作sql
	 * @param  [type] $sql    [description]
	 * @param  array  $params [description]
	 * @return [type]         [description]
	 */
	public function query($sql, $params=array()) {
		$this->sql = $this->format($sql,$params);
		$result = $this->exec();
		if(!$result) {
			return false;
		}
		return $result;		
	}

	/**
	 * 获取第一条记录
	 * @return [type] [description]
	 */
	public function fetch_first($sql,$params=array()) {
		$result = $this->query($sql,$params);
		$tmp = $result->fetch_array(MYSQLI_ASSOC);
		return $tmp;
	}

	/**
	 * 获取记录
	 * @return [type] [description]
	 */
	public function fetch_all($sql,$params=array()) {
		$result = $this->query($sql,$params);
		for ($res = array(); $tmp = $result->fetch_array(MYSQLI_ASSOC);) $res[] = $tmp;
		return $res;
	}

	/**
	 * 插入数据
	 * @param  [type] $table [description]
	 * @param  array  $data  [description]
	 * @return [type]        [description]
	 */
	public function insert($table, $data=array()) {
		if(!$data || !is_array($data)) return false;
		$data = $this->quote($data);

		$keys = $vals = '';
		foreach ($data as $k => $v) {
			if(!is_array($k)) {
				$keys[] = $k;
				$vals[] = $v;
			}
		}

		$keys = implode(' , ', $keys);
		$vals = implode(' , ', $vals);

		$this->sql = "INSERT INTO ".$this->table($table)." ({$keys}) VALUES ({$vals}) ";

		$result = $this->exec();
		if($result) {
			return $this->conn->insert_id;
		} else {
			return false;
		}
	}

	/**
	 * 更新数据
	 * @param  [type]  $table   [description]
	 * @param  array   $data    [description]
	 * @param  boolean $replace [description]
	 * @return [type]           [description]
	 */
	public function update($table, $data=array(), $condition="") {
		if(!$table || !$condition || !$data) return false;
		if(is_array($condition)) {

		} else {
			$where = $condition;
		}

		$data = $this->quote($data);
		foreach ($data as $k => $v) {
			$tmp[] = " $k = $v ";
		}
		$vals = implode(',', $tmp);

		$this->sql = "UPDATE ".$this->table($table)." SET  {$vals} WHERE {$where}";
		
		return $result = $this->exec();
	}

	/**
	 * 删除数据
	 * @param  [type]  $table     [description]
	 * @param  [type]  $condition [description]
	 * @param  integer $limit     [description]
	 * @return [type]             [description]
	 */
	public function delete($table, $condition) {
		$this->sql = "DELETE FROM ".$this->table($table)." where ". $condition;
		return $this->exec();
	}

	/**
	 * 生成sql
	 * @param  [type] $sql  [description]
	 * @param  [type] $args [description]
	 * @return [type]       [description]
	 */
	public function format($sql, $args) {
		// 检查sql语句中的模式
		$count = substr_count($sql, "%");
		if(!$count) {
			return $sql;
		}

		// 模式多于给定的参数字数
		if($count > count($args)) {
			throw new \Exception("Sql format error! ".$count ." args need,".count($args)." args given!");
		}

		$len = strlen($sql);
		$i = $find = 0;
		$ret = '';

		while ($i < $len && $find < $count) {
			$char = $sql[$i];
			$nextchar = $sql[$i+1];

			if($char == "%") {
				$next = $sql{$i + 1};
				if ($next == 't') {
					$ret .= $this->table($args[$find]);
				} elseif ($next == 's') {
					$ret .= $this->quote(is_array($args[$find]) ? serialize($args[$find]) : (string) $args[$find]);
				} elseif ($next == 'f') {
					$ret .= sprintf('%F', $args[$find]);
				} elseif ($next == 'd') {
					$ret .= intval($args[$find]);
				} elseif ($next == 'i') {
					$ret .= $args[$find];
				} elseif ($next == 'n') {
					if (!empty($args[$find])) {
						$ret .= is_array($args[$find]) ? implode(',', $this->quote($args[$find])) : $this->quote($args[$find]);
					} else {
						$ret .= '0';
					}
				} else {
					$ret .= $this->quote($args[$find]);
				}

				$i++;
				$find++;
			} else {
				$ret .= $char;
			}
			$i++;
		}

		return $ret;
	}


	/**
	 * 返回带前缀的表名
	 * @param  [type] $table [description]
	 * @return [type]        [description]
	 */
	public function table($table) {
		return $this->pre.$table;
	}

	/**
	 * 引号
	 * @param  [type]  $str     [description]
	 * @param  boolean $noarray [description]
	 * @return [type]           [description]
	 */
	public function quote($str, $noarray = false) {
		if (is_string($str))
			return '\'' . mysqli_real_escape_string($this->conn, $str) . '\'';

		if (is_int($str) or is_float($str))
			return '\'' . $str . '\'';

		if (is_array($str)) {
			if($noarray === false) {
				foreach ($str as &$v) {
					$v = $this->quote($v, true);
				}
				return $str;
			} else {
				return '\'\'';
			}
		}

		if (is_bool($str))
			return $str ? '1' : '0';

		return '\'\'';
	}

	/**
	 * 计时
	 */
	public function timeuse() {
		$t = explode(',', microtime());
		$t = $t[0] + $t[1];
		return $t;
	}
}