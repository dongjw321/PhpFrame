<?php

namespace  DongPHP\System\Model\Jd;

use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Libraries\Http\Curl;
use DongPHP\System\Libraries\TcpLog;
use DongPHP\System\Data;

class AbstractJdModel extends AbstractModel
{
    protected $token = '';
    //夺宝
//    protected $app_key = '42bc618d8f0b47339b4293c797dec25b';
//    protected $app_secret = 'c351cbd36ea34e4a98c045396529fe0d';
//    protected $account = 'XYduobao';
//    protected $password = 'www.jd.com';
//    protected $memcachekey = 'jd:token:string';

    //欢游
    protected $app_key = '6655a83a61164a99ac0770af2fbef83e';
    protected $app_secret = '6435a30206e94489aceed8982f797dcd';
    protected $account = 'hyduobao';
    protected $password = 'hy0428';
    protected $memcachekey = 'jd:token:string:hy';

    public function __construct()
    {
        parent::__construct();

        $this->memcache = Data::Memcache('xydb.jd');

        $this->token = $this->memcache->get($this->memcachekey);
        if(!$this->token){
            $this->token = $this->getToken();
            $this->memcache->set($this->memcachekey, $this->token);
        }
    }

    protected function getToken() {
        $url = 'https://kploauth.jd.com/oauth/token?grant_type=password&state=0&'
            .'app_key='. $this->app_key
            .'&app_secret=' . $this->app_secret
            .'&username=' . $this->account
            .'&password=' . md5($this->password);
        $response = $this->decodeJson(Curl::get($url));

        return isset($response['access_token']) ? $response['access_token'] : '';
    }

    protected function refreshToken() {
        $url = 'https://kploauth.jd.com/oauth/token?grant_type=refresh_token&state=0&'
            .'app_key='. $this->app_key
            .'&app_secret=' . $this->app_secret
            .'&username=' . $this->account
            .'&password=' . md5($this->password);
        $response = $this->decodeJson(Curl::get($url));

        return isset($response['access_token']) ? $response['access_token'] : '';
    }

    protected function getRequestUrl($method, $params=[]) {
        return  'https://router.jd.com/api?method=' . $method
        .'&app_key=' . $this->app_key
        .'&access_token=' . $this->token
        .'&timestamp=' . date('Y-m-dH:i:s')
        .'&v=1.0&format=json&param_json=' . ($params ? json_encode($params) : '{}');
    }

    protected function requestApi($url, $params=[], $type='get', $format=1, $tcpLog=0) {
        $response = $this->decodeJson(Curl::$type($url, $params));

        if($tcpLog) {
            $tcpLogData = [
                'request_url' => $url,
                'request_params' => $params,
                'request_type' => $type,
                'response_data' => $response
            ];
            //记录日志
            TcpLog::record('xydb_jd_request', json_encode($tcpLogData));
        }

        if($format && is_array($response)) {
            $response = array_values($response);
            if(isset($response[0]['success']) && $response[0]['success']) {
                return $response[0]['result'];
            }

            //token 过期
            if((isset($response[0]['code']) && $response[0]['code'] == 1004) || (isset($response[0]['resultCode']) && $response[0]['resultCode'] == 2007)) {
                $this->token = $this->refreshToken();
                $this->memcache->set($this->memcachekey, $this->token);
            }
        }

        return $response;
    }

    protected function decodeJson($str) {
        return json_decode($str, true);
    }
}