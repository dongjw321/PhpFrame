<?php
namespace DongPHP\System\Libraries;
class Exceptions
{
    public static function info($msg, $code = 404)
    {
        throw new InfoException($msg, $code);
    }

    public static function notice($msg, $code = 404)
    {
        throw new NoticeException($msg, $code);
    }

    public static function error($msg, $code = 404)
    {
        throw new \ErrorException($msg, $code);
    }

    public static function capture(\Exception $e)
    {
        $record['code'] = $e->getCode();
        $record['msg']  = $e->getMessage();
        $record['file'] = $e->getFile();
        $record['line'] = $e->getLine();
        if (ENVIRONMENT !='idc4' && !IS_DEBUG && !IS_CLI) {
            if (
                $e instanceof MemcacheException ||
                $e instanceof DBException ||
                $e instanceof \RedisException ||
                $e instanceof \LogicException
            ) {
                $record['msg'] = '系统发重错误，请重试';
            }
        }
        unset($record['file'],$record['line'], $record['file']);
        header("Content-type: application/json;charset=utf-8");
        echo json_encode($record, JSON_UNESCAPED_UNICODE);
    }
}

class RecordException extends \Exception
{
}

class NoticeException extends \Exception
{
}

class InfoException extends \Exception
{
}
