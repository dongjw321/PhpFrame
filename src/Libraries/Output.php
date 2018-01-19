<?php
/**
 * this is part of xyfree
 *
 * @file Output.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2015-12-15 15:11
 *
 */

namespace DongPHP\System\Libraries;

use DongPHP\System\Libraries\Http\Response;

class Output
{
    public static function result($message = '', $code = 200, array $header = ['Content-Type' => 'text/plain;charset=utf-8', 'Connection' => 'close'])
    {
        $response = new Response($code);
        $response->setHeaders($header);
        $response->setBody($message);
        $response->send();
    }

    public static function json($message, $code = 200, $header = ['Content-Type' => 'application/json;charset=utf-8', 'Connection' => 'close'])
    {
        self::result(json_encode($message), $code, $header);
    }

    public static function error($message, $code = 404, $header = [])
    {
        self::result($message, $code, $header);
    }
}
