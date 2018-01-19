<?php

namespace DongPHP\System\Model\User;

use DongPHP\System\Libraries\DB;
use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Data;

class UserRegistModel extends AbstractModel
{
    public function __construct()
    {
        parent::__construct();

        $this->builder = DB::builder('xydb.user_regist');
        $this->memcache = Data::Memcache('xydb.user_account');
        $this->getCodeNumMemKey = 'xydb:get:code:num:key:';
        $this->getCodeNum = 3;
    }

    /**
     * @name getAutoUid 通过添加账号信息获取uid
     * @param $account
     * @return int
     */
    public function getAutoUid($account, $type=0) {
        return $this->builder->insertGetId(array('account'=>$account, 'type'=>$type));
    }

    /**
     * @name getAccountInfoByUid 通过uid获取用户注册时的账号信息
     * @param $uid
     * @return array
     */
    public function getAccountInfoByUid($uid) {
        return $this->builder->where(array('uid'=>$uid))->first();
    }

    /**
     * @name regist 注册
     * @param $account
     * @param $password
     * @param int $type
     * @return array|bool
     */
    public function regist($account, $password, $type=0, $channel='', $ip='') {
        $uid = $this->getAutoUid($account, $type);
        if(!$uid) {
            return false;
        }

        (new UserLoginModel())->addLoginData($account, $password, $uid, $type);
        $uname = 0==$type ? preg_replace('/^(\d{5})(\d{4})(\d{2})$/', "$1****$3", $account) : $uid;
        $userInfo = array(
            'uid' => $uid,
            'uname' => $uname,
            'avatar' => '',
            'credits' => 0,
            'phone' => 0==$type ? $account : '',
            'channel' => $channel ? $channel : '',
            'ip' => $ip ? $ip : '',
        );
        (new UserInfoModel($uid))->add($userInfo);

        return $userInfo;
    }

    /**
     * @name getUserPhoneCode 获取用户手机验证码
     * @param $tel
     * @return array|string
     */
    public function getUserPhoneCode($tel) {
        $getCodeNum = (int)$this->memcache->get($this->getCodeNumMemKey .$tel);
        if($getCodeNum >= $this->getCodeNum) {
            return false;
        }
        //次数加1
        $this->memcache->set($this->getCodeNumMemKey .$tel, $getCodeNum + 1);

        return $this->memcache->get($tel);
    }

    /**
     * @name setUserPhoneCode 设置用户手机验证码
     * @param $tel
     * @param $val
     * @param int $time
     * @return bool
     */
    public function setUserPhoneCode($tel, $val, $time=0) {
        if($this->memcache->get($this->getCodeNumMemKey .$tel)) {
            $this->memcache->delete($this->getCodeNumMemKey .$tel);
        }

        return $this->memcache->set($tel, $val, 0, $time);
    }


    //注册送红包活动
    public function registReward($uid) {
        $returnNum = 0;
        $now = time();
        if($now >= REGISTE_REWARD_GOLD_STARTTIME && $now < REGISTE_REWARD_GOLD_ENDTIME) {
            (new UserGoldModel((int)$uid))->add(REGISTE_REWARD_GOLD_NUM, 1, ['reward_type' => 'regist', 'uid'=> $uid]);
            $returnNum = REGISTE_REWARD_GOLD_NUM;
        }

        return $returnNum;
    }
}