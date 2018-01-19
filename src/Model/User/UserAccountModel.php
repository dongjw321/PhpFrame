<?php

namespace DongPHP\Model\User;

use DongPHP\System\Libraries\DB;
use DongPHP\Model\AbstractModel;
use DongPHP\System\Data;

class UserAccountModel extends AbstractModel
{
    public function __construct()
    {
        parent::__construct();

        $this->builder = DB::builder('xydb.user_regist');
        $this->memcache = Data::Memcache('xydb.user_account');
        $this->redis = Data::redis('xydb.user_detail_info');
        $this->redisKey = 'xydb:user:detail:info:';
        $this->getCodeNumMemKey = 'xydb:get:code:num:key:';
        $this->getCodeNum = 3;
    }

    /************************ user_regist表 start **************************/
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

    /************************ user_regist表 end **************************/


    /************************ user_base_info表 start **************************/
    /**
     * @name addUserInfo 添加用户基本信息
     * @param $uid
     * @param $values
     */
    public function addUserInfo($uid, $values) {
        $now = time();
        $values['atime'] = $now;
        $values['utime'] = $now;

        DB::builder('xydb.user_base_info', $uid)->insert($values);
    }

    /**
     * @name upUserInfo 更新用户基本信息
     * @param $uid
     * @param $values
     * @return int
     */
    public function upUserInfo($uid, $values) {
        $values['utime'] = time();

        $this->_delUserDetailInfoCache($uid);
        return DB::builder('xydb.user_base_info', $uid)->where(['uid'=>$uid])->update($values);
    }
    /**
     * @name getUserInfo获取用户基本信息
     * @param $uid
     * @return array
     */
    public function getUserInfo($uid) {
        return DB::builder('xydb.user_base_info', $uid)->where(['uid'=>$uid])->first();
    }

    /************************ user_base_info表 end **************************/


    /************************ user_address表 strat **************************/
    /**
     * @name getUserAddress 获取用户收货地址信息
     * @param $uid
     * @return array|static[]
     */
    public function getUserAddress($uid) {
        return DB::builder('xydb.user_address', $uid)->where(['uid'=>$uid])->get();
    }

    /**
     * @name getUserAddressById 根据id获取用户收货地址信息
     * @param $uid
     * @param $id
     * @return array|static[]
     */
    public function getUserAddressById($uid, $id) {
        return DB::builder('xydb.user_address', $uid)->find($id);
    }

    /**
     * @name addUserAddress 添加用户收货地址
     * @param $uid
     * @param $data
     * @return bool
     */
    public function addUserAddress($uid, $data) {
        $now = time();
        $data['atime'] = $now;
        $data['utime'] = $now;

        $this->_delUserDetailInfoCache($uid);
        return DB::builder('xydb.user_address', $uid)->where(['uid'=>$uid])->insert($data);
    }

    /**
     * @name upUserAddress 更新用户收货地址
     * @param $uid
     * @param $data
     * @return bool
     */
    public function upUserAddress($uid, $id, $data) {
        $now = time();
        $data['utime'] = $now;

        $this->_delUserDetailInfoCache($uid);
        return DB::builder('xydb.user_address', $uid)->where(['id'=>$id])->update($data);
    }

    /**
     * @name getAidByIsdef 获取用户默认收货地址的id
     * @param $uid
     * @return array
     */
    public function getAidByIsdef($uid) {
        return DB::builder('xydb.user_address', $uid)->where(['uid'=>$uid, 'isdef'=>1])->first( );
    }

    /**
     * @name delUserAddress 删除用户的收货地址
     * @param $uid
     * @param $id
     * @return bool
     */
    public function delUserAddress($uid, $id) {
        $this->_delUserDetailInfoCache($uid);
        return DB::builder('xydb.user_address', $uid)->delete($id);
    }

    /**
     * @name checkUserAddressExists 检查用户收货地址是否存在
     * @param $uid
     * @param $id
     * @return bool
     */
    public function checkUserAddressExists($uid, $id) {
        return DB::builder('xydb.user_address', $uid)->where(['id'=>$id])->exists();
    }



    /************************ user_address表 end **************************/


    /************************ user_login表 strat **************************/
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

    /************************ user_login表 end **************************/


    /************************ memcache strat **************************/

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

    /************************ memcache end **************************/


    /************************ other start **************************/

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

        $this->addLoginData($account, $password, $uid, $type);
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
        $this->addUserInfo($uid, $userInfo);

        return $userInfo;
    }

    /**
     * @name getUserDetailInfo 获取用户详细信息
     * @param $uid
     * @return array
     */
    public function getUserDetailInfo($uid, $filter=array('atime', 'utime')) {
        if(is_array($uid) && count($uid) > 0) {
            $keys = array();
            foreach($uid as $value) {
                $keys[] = $this->redisKey . $value;
            }

            $redisData = $this->redis->mget($keys);
            $upInfo = array();
            foreach($uid as $k => $value) {
                $redisData[$k] = json_decode($redisData[$k], true);
                if(!$redisData[$k]) {
                    $userInfo = $this->getUserInfo($value);
                    if($userInfo) {
                        $userAddress = $this->getUserAddress($value);
                        $userInfo['u_address'] = $userAddress ? $userAddress : array();

                        $redisData[$k] = $userInfo;
                        $upInfo[$this->redisKey . $value] = json_encode($userInfo);
                    }
                }

                //过滤
                if(!empty($filter)) {
                    foreach($filter as $key) {
                        if(isset($redisData[$k][$key])) {
                            unset($redisData[$k][$key]);
                        }
                    }
                }
            }

            if(!empty($upInfo)) {
                $this->redis->mset($upInfo);
            }

            $result = $redisData;
        }else {
            $redisData = $this->redis->get($this->redisKey . $uid);
            if(!$redisData) {
                $userInfo = $this->getUserInfo($uid);
                $userAddress = $this->getUserAddress($uid);
                $userInfo['u_address'] = $userAddress ? $userAddress : array();

                $redisData = $userInfo;
                $this->redis->set($this->redisKey . $uid, json_encode($userInfo));
            }else {
                $redisData = json_decode($redisData, true);
            }

            //过滤
            if(!empty($filter)) {
                foreach($filter as $key) {
                    if(isset($redisData[$key])) {
                        unset($redisData[$key]);
                    }
                }
            }

            $result = $redisData;
        }

        return $result;
    }

    //删除用户详细信息的redis缓存
    private function _delUserDetailInfoCache($uid) {
        return $this->redis->del($this->redisKey . $uid);
    }

    /************************ other end **************************/
    public function getDB($uid)
    {
        $this->_delUserDetailInfoCache($uid);
        return DB::builder('xydb.user_base_info', $uid);
    }
}