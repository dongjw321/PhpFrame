<?php
namespace DongPHP\System\Event;
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2016/11/23
 * Time: 19:16
 */
class Event
{
    public static function emit(ListenerInterface $event, $params=[])
    {
        return call_user_func_array([$event,'handle'],[$params]);
    }
}