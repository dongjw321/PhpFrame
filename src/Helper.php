<?php
/**
 * this is part of xyfree
 *
 * @file Helper.php
 * @use  加载helper文件
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2015-11-03 18:11
 *
 */

namespace DongPHP\System;

class Helper
{
    public static function load($name, $public=false)
    {
        $file_path = $public === false ? APP_PATH.'Helper/'.$name.'.php'  : dirname(__FILE__).'/Helper/'.$name.'.php';
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}
