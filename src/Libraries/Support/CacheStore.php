<?php
namespace DongPHP\System\Libraries\Support;
use DongPHP\System\Data;

/**
 * this is part of xyfree
 *
 * @file CacheStore.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-03-25 10:33
 *
 */
class CacheStore
{

    public $key ;

    public $store;

    public function __construct ($store, $key, $type='memcache', $extime=0)
    {
        $this->store = Data::$type($store);
        $this->key   = $key;
    }

    public function get()
    {
        if (IS_CLEAR) {
            return false;
        }
        return $this->store->get($this->key);
    }


    public function set($info)
    {
        return $this->store->set($this->key, $info);
    }
}