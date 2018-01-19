<?php

/**
 * this is part of xyfree
 *
 * @file UserInfoData.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-11 17:22
 *
 */
namespace DongPHP\System\Model\User;
use DongPHP\System\Libraries\DB;
use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Data;

class UserInfoData extends AbstractModel
{
    protected $uid;
    CONST R_USER_DETAIL_INFO = 'xydb:user:detail:info:';

    public function __construct($uid)
    {
        parent::__construct();
        $this->uid     = $uid;
        $this->builder = DB::builder('xydb.user_base_info', $this->uid);
        $this->redis = Data::redis('xydb.user_detail_info');
    }

    public function get() {
        $redisData = $this->redis->get(self::R_USER_DETAIL_INFO . $this->uid);
        if(!$redisData) {
            $userInfo = $this->builder->where(['uid'=>$this->uid])->first();
            if(!empty($userInfo)) {
                $this->redis->set(self::R_USER_DETAIL_INFO . $this->uid, json_encode($userInfo));
            }
        }else {
            $userInfo = json_decode($redisData, true);
        }

        return $userInfo;
    }

    public static function multGet($uids)
    {
        $periods_keys = array_map(function($a){return self::R_USER_DETAIL_INFO.$a;}, $uids);
        $infos   = Data::redis('xydb.user_detail_info')->mget($periods_keys);
        $ret     = [];
        foreach($uids as $k => $uid) {
            $ret[$uid] = $infos[$k];
        }

        return $ret;
    }

    //删除用户详细信息的redis缓存
    public static function delUserDetailInfoCache($uid) {
        return Data::redis('xydb.user_detail_info')->del(self::R_USER_DETAIL_INFO . $uid);
    }
}