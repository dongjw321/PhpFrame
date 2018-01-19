<?php
/**
 * this is part of xyfree
 *
 * @file DayCounter.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2017-11-22 11:11
 *
 */

namespace DongPHP\System\Libraries;


abstract class AbstractDayCounter
{
    protected static $data;
    const KEY_PREFIX = 'counter:';

    public static function add($key, $type, $param='', $value=1)
    {
        $key      = self::getKey($key, $type, $param);
        $total    = self::getRedis()->incrBy($key, $value);
        if ($total == $value) {
            self::getRedis()->expireAt($key, strtotime(date("Y-m-d 23:59:59")));//设置key的有效器
        }
        self::$data[$key] = $total;
        return self::$data[$key];
    }

    public static function get($key, $type, $param='')
    {
        $key = self::getKey($key, $type, $param);
        if (!isset(self::$data[$key])) {
            self::$data[$key] = (int)Cache::redis('default')->get($key);
        }
        return self::$data[$key];
    }

    /**
     * @return \Redis
     */
    protected static function getRedis()
    {
        return Cache::redis('default');
    }

    public static function clear($key, $type, $param='')
    {
        $key = self::getKey($key, $type, $param);
        if (isset(self::$data[$key])) {
            unset(self::$data[$key]);
        }
        return self::getRedis()->del($key);
    }

    protected static function getKey($key, $type, $param='')
    {
        if (!$param) {
            $key = date('Ymd').'_'.$key.'_'.$type;
        } elseif (is_array($param)) {
            $key = date('Ymd').'_'.$key.'_'.$type.'_'.json_encode($param);
        } else {
            $key = date('Ymd').'_'.$key.'_'.$type.'_'.$param;
        }
        return static::KEY_PREFIX.md5($key);
    }
}