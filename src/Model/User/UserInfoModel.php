<?php
/**
 * this is part of xyfree
 *
 * @file UserInfo.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-05 11:46
 *
 */

namespace DongPHP\System\Model\User;

use DongPHP\System\Model\User\UserInfoData;
use DongPHP\System\Libraries\Config;

class UserInfoModel extends AbstractUserModel
{
    protected $uid;
    protected $info;
    protected $userInfoData;

    public function __construct($uid)
    {
        parent::__construct($uid);
        $this->uid          = $uid;
        $this->userInfoData = new UserInfoData($this->uid);
        $this->info         = $this->userInfoData->get();
        $this->builder      = $this->userInfoData->getBuilder();
    }

    public function get() {
        return $this->info;
    }

    public function add($value) {
        $now = time();
        $value['atime'] = $now;
        $value['utime'] = $now;

        $this->userInfoData->insert($value);
    }

    public function modify($values)
    {
        $values['utime'] = time();
        UserInfoData::delUserDetailInfoCache($this->uid);
        return $this->userInfoData->update($values,['uid'=>$this->uid]);
    }

    public function getField($field)
    {
        if (is_array($field)) {
            return $this->getFields($field);
        }
        if (isset($this->info[$field])) {
            return $this->info[$field];
        }
        return false;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function getFields($fields=[])
    {
        return array_filter_keys($this->info, $fields);
    }

    public function hasDirtyWord($str) {
        $config = Config::get('dirtywords');
        foreach($config as $value) {
            if(strpos($str, trim($value)) === false){
                continue;
            }else {
                return true;
            }
        }

        return false;
    }
}