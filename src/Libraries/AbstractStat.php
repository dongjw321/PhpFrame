<?php
/**
 * 用户统计数据
 */

namespace DongPHP\System\Libraries;


abstract class AbstractStat
{
    protected static $data;

    const REDIS_PREFIX = 'stat:hash:';

    protected static function getKey($key)
    {
        return static::REDIS_PREFIX.$key;
    }

    /**
     * @return \Redis
     */
    protected static function redis()
    {
        return Cache::redis('default');
    }

    public static function set($key, $status, $value)
    {
        if (is_array($status) || is_array($value)) {
            if (count($status) != count($value)) {
                throw new \ErrorException('参数错误', 786);
            }

            return self::redis()->hMset(self::getKey($key), array_combine($status,  $value));
        } else {
            return self::redis()->hSet(self::getKey($key), $status, $value);
        }
    }

    public static function add($key, $status, $value=1)
    {
        return self::redis()->hIncrBy(self::getKey($key), $status, intval($value));
    }

    public static function get($key, $status)
    {
        if (!is_array($status) &&  strpos($status, ",") !== false) {
            $status = explode(",", $status);
        }

        if (is_array($status)) {
            return self::redis()->hMGet(self::getKey($key), $status);
        } else {
            return self::redis()->hGet(self::getKey($key), $status);
        }
    }

    public static function clear($key, $status)
    {
        return self::redis()->hDel(self::getKey($key), $status);
    }
}
