<?php
/**
 * this is part of xyfree
 *
 * @file UserAddress.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-05 11:47
 *
 */

namespace DongPHP\System\Model\User;

use DongPHP\System\Libraries\DB;
use DongPHP\System\Data;

class UserAddressModel extends AbstractUserModel
{
    protected $uid;
    protected $builder;
    protected $userClearAddressRedisKey = 'user:clear:address:string:';

    public function __construct($uid)
    {
        parent::__construct($uid);
        $this->uid     = $uid;
        $this->builder = DB::builder('xydb.user_address', $this->uid);
        $this->redis = Data::redis('xydb.xydb_user');
    }

    public function getList()
    {
        return $this->builder->where(['uid'=>$this->uid])->get();
    }

    public function getListNew()
    {
        if(!$this->getUserClearAddressStatus()) {
            $this->delUserAddressList();
            $this->setUserClearAddressStatus();
        }
        return $this->builder->where(['uid'=>$this->uid])->get();
    }

    public function getOne($id)
    {
        return $this->builder->where(['id'=>$id])->first();
    }

    public function modify($id, $values)
    {
        $values['utime'] = time();
        return $this->update($values,['id'=>$id]);
    }

    public function add($values)
    {
        $now = time();
        $values['atime'] = $now;
        $values['utime'] = $now;

        return $this->insert($values);
    }

    /**
     * @name getDefAddressId 获取用户默认收货地址的id
     * @param $uid
     * @return array
     */
    public function getDefAddressId() {
        return $this->builder->where(['uid'=>$this->uid, 'isdef'=>1])->first();
    }

    /**
     * @name delUserAddress 删除用户的收货地址
     * @param $uid
     * @param $id
     * @return bool
     */
    public function delUserAddress($id) {
        return $this->builder->delete($id);
    }


    /**
     * @name delUserAddress 删除用户的收货地址
     * @param $uid
     * @param $id
     * @return bool
     */
    public function delUserAddressList() {
        $querysql = "delete from user_address where uid=$this->uid";
        return $this->builder->query($querysql);
    }

    /**
     * @name checkUserAddressExists 检查用户收货地址是否存在
     * @param $uid
     * @param $id
     * @return bool
     */
    public function checkUserAddressExists($id) {
        return $this->builder->where(['uid'=>$this->uid, 'id'=>$id])->exists();
    }

    public function getUserClearAddressStatus() {
        $res = $this->redis->get($this->userClearAddressRedisKey . $this->uid);

        return $res;
    }

    public function setUserClearAddressStatus() {
        return $this->redis->set($this->userClearAddressRedisKey . $this->uid, 1);
    }
}