<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2016/11/9
 * Time: 19:11
 */
if (!function_exists('json')) {
    function json($data) {
        echo json_encode($data);
    }
}

/**
 * @param $key
 * @param null $hash
 * @return \Redis
 */
function redis($key, $hash=null)
{
    $config = \DongPHP\System\Libraries\DataConfigLoader::redis($key, $hash);
    return \DongPHP\System\Libraries\Redis::getInstance($config['host'], $config['port'], $config['timeout'], $config['auth']);
}

/**
 * @param $key
 * @param null $hash
 * @return \Memcache
 */
function memcache($key, $hash=null)
{
    $config = \DongPHP\System\Libraries\DataConfigLoader::memcache($key, $hash);
    return \DongPHP\System\Libraries\Memcache::getInstance($config['host'], $config['port']);
}

