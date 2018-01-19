<?php
/**
 * this is part of xyfree
 *
 * @file Mutiversion.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2015-10-30 17:29
 *
 */

namespace DongPHP\System;

class Multiversion
{
    public static function autoDetectionVersion($class_obj, $method, $args, $version)
    {
        $class_name = get_class($class_obj);

        if (!$version) {
            return call_user_func_array(array($class_obj, $method), $args);
        }

        $class_name = strtolower($class_name);
        $class      = new \ReflectionClass($class_name);
        $methods    = $class->getMethods();

        $real_version = 0;
        if (file_exists(APP_PATH . 'config/multiversion.php')) {
            require_once APP_PATH . 'config/multiversion.php';
            if (isset($config['multiversion'][$version])) {
                $real_version = $config['multiversion'][$version];
            } else {
                foreach ($config['multiversion'] as $k => $val) {
                    if (version_compare($version, $k)>-1) {
                        $real_version = $val;
                        break;
                    }
                }
            }
        } else {
            $real_version = str_replace('.', '', $version);
        }

        $sames   = array($method);
        if ($real_version) {
            foreach ($methods as $row) {
                if (strtolower($row->class) == $class_name && strpos($row->name, $method) === 0) {
                    $method_version = intval(str_replace($method, '', $row->name));
                    if ($method_version && $method_version <= $real_version) {
                        $sames[] = $row->name;
                    }
                }
            }
        }
        rsort($sames);
        $method = array_shift($sames);

        return call_user_func_array(array($class_obj, $method), $args);
    }
}