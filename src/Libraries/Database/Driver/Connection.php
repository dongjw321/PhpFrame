<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2016/11/9
 * Time: 15:41
 */

namespace DongPHP\System\Libraries\Database\Driver;

use DongPHP\System\Logger;
use Illuminate\Database\MySqlConnection;

class Connection extends MySqlConnection
{
    public static $processing = false;

    public function logQuery($query, $bindings, $time = null)
    {
        if ($time > 0.1) {
            Logger::get('mysql')->alert('[S] sql:'.$query.', bindings:'.json_encode($bindings).', time:'.$time);
        }
        Logger::get('mysql')->debug('[S] sql:'.$query . ', bindings:' . json_encode($bindings) . ', time:' . $time);
    }

    protected function run($query, $bindings, \Closure $callback)
    {
        $ret =  parent::run($query, $bindings, $callback);
        if (defined('MYSQL_AUTO_CLOSE') && MYSQL_AUTO_CLOSE == true && self::$processing === false) {
            $this->disconnect();
        }

        return $ret;
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int    $start
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return (microtime(true) - $start);
    }

}