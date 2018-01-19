<?php
namespace DongPHP\System\Logger;
use Iframe\Controller\AbstractController;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

/**
 * this is part of xyfree
 *
 * @file MysqlLogger.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2015-11-03 15:15
 *
 */
class MysqlLogger extends AbstractLogger
{
    public function __construct()
    {
        $logger = new Logger(__CLASS__);
        $logger->pushHandler($this->getDebugHandler(Logger::DEBUG));
        $logger->pushHandler( new RotatingFileHandler('mysql',30,Logger::ERROR));
        $this->logger = $logger;
        return $this->logger;
    }
}