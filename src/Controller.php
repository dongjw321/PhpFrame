<?php
/**
 * this is part of xyfree
 *
 * @file Controller.php
 * @use   初始控制器
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2015-10-30 10:08
 *
 */

namespace DongPHP\System;

use Monolog\Logger as MonologLogger;
use Pimple\Container;

abstract class Controller
{
    /**
     * @var MonologLogger
     */
    public $logger;
    /**
     * @var Container
     */
    public $container;


    protected $controller   = '';
    protected $method       = '';
    

    public function __construct()
    {
        $this->container  = new Container();
        $controller       = str_replace(NAME_SPACE.'\Controller\\', '', Application::$instance->getController());
        $this->controller = strtolower(str_replace('Controller', '', $controller));
        $this->method     = strtolower(Application::$instance->getMethod());
    }

    protected function outJson($data)
    {
        if (IS_DEBUG) {
            var_dump($this->toString($data));
        } else {
            $out = json_encode($this->toString($data), JSON_UNESCAPED_UNICODE);
            header("Content-type: application/json;charset=utf-8");
            echo $out;
        }
    }

    protected function outError($msg, $code = 404)
    {
        throw new \Exception($msg, $code);
    }

    protected function outHtmlError($msg, $code = 404)
    {
        View::show('html_error',['msg'=>$msg]);
        exit;
        //throw new \Exception($msg, $code);
    }

    protected function outResult($result, $code = 200)
    {
        $data['code'] = $code;
        $data['data'] = $result;
        $data['time'] = time();
        $this->outJson($data);
    }

    /**
     * 设置容器
     * @param $property
     * @param $callable
     */
    protected function setProperty($property, $callable) {
        $this->container[$property] = $this->container->factory($callable);
        unset($this->$property);
    }

    public function __get($key)
    {
        static $obj;
        if ( !isset($obj[$key]) ) {
            $obj[$key] = $this->container[$key];
        }
        return $obj[$key];
    }


    protected function toString($data)
    {
        foreach ($data as &$val) {
            if (is_array($val)){
                if (empty($val)) {
                    $val = null;
                } else {
                    $val = $this->toString($val);
                }
            } else {
                $val = "$val";
            }
        }

        return $data;
    }
}
