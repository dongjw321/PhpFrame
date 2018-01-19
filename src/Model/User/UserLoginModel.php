<?php

namespace DongPHP\System\Model\User;

use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Data;
use DongPHP\System\Libraries\Input;
use DongPHP\System\Libraries\Security;
use DongPHP\System\Libraries\DB;

class UserLoginModel extends AbstractModel
{
    private $sessionTokenTime = '604800'; //7天
    private  $sessionTokenMemKey = 'session_token:';
    private  $loginIpMemKey = 'login:ip:';

    public function __construct()
    {
        parent::__construct();
    }

    public function getLoginId() {
        $uid = Input::int('uid');
        $sessionToken = Input::string('session_token');
        $sessionToken = $sessionToken ? $sessionToken : '';
        $userSessionTokenData = $this->checkLogin($uid);
        $loginId = 0;
        if (in_array($sessionToken, $userSessionTokenData)) {
            $sessInfo      = Security::decrypt(hex2bin($sessionToken));
            if(substr($sessInfo, -10) + $this->sessionTokenTime > time()) {
                $loginId = substr($sessInfo, 0, -10);
            }else {
                $key = array_search($sessionToken, $userSessionTokenData);
                unset($userSessionTokenData[$key]);
                $this->setSessionTokenData($uid, $userSessionTokenData);
            }
        }

        return $loginId;
    }

    /**
     * 生成session_token
     * @name createSessionToken
     * @param $uid
     * @return string
     */
    public function createSessionToken($uid)
    {
        return bin2hex(Security::encrypt($uid . time()));
    }

    /**
     * 验证用户登录
     * @param $sessionToken
     * @return array|string
     */
    public function checkLogin($uid)
    {
        return $this->getSessionToken($uid);
    }

    /**
     * @name getUserLoginInfo 获取用户登录信息
     * @param $account
     * @param int $type
     * @return array|mixed|static
     */
    public function getUserLoginInfo($account, $type=0) {
        $data = DB::builder('xydb.user_login', $account)->where(['account'=>$account, 'type'=>$type])->first();
        return $data ? $data : array();
    }

    /**
     * @name checkAccountExists 检查账号是否注册过
     * @param $account
     * @param int $type
     * @return bool
     */
    public function checkAccountExists($account, $type=0) {
        return DB::builder('xydb.user_login', $account)->where(['account'=>$account, 'type'=>$type])->exists();
    }

    /**
     * @name addLoginData 添加用户登录信息
     * @param $account
     * @param string $password
     * @param $uid
     * @param int $type
     */
    public function addLoginData($account, $password='', $uid, $type=0){
        $data = array(
            'uid' => $uid,
            'account' => $account,
            'password' => $password != '' ? $password : '',
            'type' => $type,
            'status' => 1,
            'atime' => time(),
        );

        DB::builder('xydb.user_login',$account)->insert($data);
    }

    /**
     * @name upLoginData 更新用户登录信息
     * @param $account
     * @param $type
     * @param $data
     * @return int
     */
    public function upLoginData($account, $type, $data) {
        return DB::builder('xydb.user_login', $account)->where(['account'=>$account, 'type'=>$type])->update($data);
    }


    public function setSessionToken($uid, $sessionToken) {
        $sessionTokenData = $this->getSessionToken($uid);
        $sessionTokenData[] = $sessionToken;
        return $this->setSessionTokenData($uid, $sessionTokenData);
    }

    public function setSessionTokenData($uid, $sessionTokenData) {
        return Data::Memcache('xydb.user_account')->set($this->sessionTokenMemKey . $uid, json_encode($sessionTokenData, JSON_NUMERIC_CHECK), 0);
    }

    public function getSessionToken($uid) {
        $sessionTokenData =  Data::Memcache('xydb.user_account')->get($this->sessionTokenMemKey . $uid);
        return $sessionTokenData ? json_decode($sessionTokenData, true) : array();
    }

    public function delSessionToken($uid) {
        try {
            Data::Memcache('xydb.user_account')->delete($this->sessionTokenMemKey . $uid);
        }catch(\Exception $e) {

        }
    }

    public function getLoginIpFormMem($uid) {
        $loginIp =  Data::Memcache('xydb.user_account')->get($this->loginIpMemKey . $uid);
        return $loginIp ? $loginIp : '';
    }

    public function setLoginIpToMem($uid, $ip) {
        return Data::Memcache('xydb.user_account')->set($this->loginIpMemKey . $uid, $ip, 0, $this->sessionTokenTime);
    }
}