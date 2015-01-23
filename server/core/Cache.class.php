<?php
/**
 * Created by PhpStorm.
 * User: wuzhuo
 * Date: 15/1/23
 * Time: 上午10:49
 */

namespace core;

/**
 * Class Cache
 * @package core
 */
class Cache {
    private $mm;

    public function __construct() {
        $mm = new \Memcached();
        $mm->addServer("127.0.0.1",9022);
        $this->mm = $mm;
    }

    /**
     * 设置
     * @param $k
     * @param $v
     */
    public function set($k,$v) {
        $this->mm->set($k,$v);
    }

    /**
     * 获取
     * @param $k
     * @param null $default
     * @return null
     */
    public function get($k,$default=null) {
        $ret = $this->mm->get($k);
        return $ret ? $ret : $default;
    }

    /**
     * 删除
     * @param $k
     */
    public function delete($k) {
        $this->mm->delete($k);
    }

}