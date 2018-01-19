<?php
namespace DongPHP\System\Model\Duiba;

use DongPHP\System\Libraries\Config;
use DongPHP\System\Data;
use DongPHP\System\Libraries\DB;
use DongPHP\System\Model\AbstractModel;

class DuibaModel extends AbstractModel
{
    public function __construct(&$uid)
    {
        $this->uid = &$uid;
    }

    //免登入url
    public function getLoginUrl($credits)
    {
        if (!$this->uid) return false;
        $url            = Config::get('duiba.url',true);
        $parame         = [
            'uid'       => $this->uid,
            'credits'   => $credits,
            'appKey'    => Config::get('duiba.appkey',true),
            'timestamp' => $this->getMillisecond(),
        ];
        $parame['sign'] = $this->getSign($parame);
        return $url . "?" . http_build_query($parame);
    }

    //得到签名
    public function getSign($parame)
    {
        $sign                = '';
        $parame['appSecret'] = Config::get('duiba.appsecret',true);
        ksort($parame);
        foreach ($parame as $v) {
            $sign .= $v;
        }
        return md5($sign);
    }

    public function checkSign()
    {
        $parame = $_GET;
        $sign1  = isset($parame['sign']) ? $parame['sign'] : '';
        unset($parame['sign']);
        $sign2 = $this->getSign($parame);
        if ($sign1 != $sign2) {
            return false;
        }
        return true;
    }

    //获取毫秒
    public function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }
}