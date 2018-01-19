<?php
namespace DongPHP\System\Event;
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2016/11/23
 * Time: 19:16
 */
interface ListenerInterface
{
    public function handle($params);
}