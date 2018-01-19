<?php
/**
 * this is part of xyfree
 *
 * @file UserOrderModel.php
 * @use  用户定单信息
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-08 14:56
 *
 */

namespace DongPHP\Model\User;

use DongPHP\System\Libraries\DB;
class UserOrderModel extends AbstractUserModel
{
    protected $uid;

    public function __construct($uid)
    {
        parent::__construct($uid);
        $this->builder = DB::builder('user_order', $this->uid);
    }

    public function add($id, $goods, $price, $pay_way)
    {
        $order['id']      = $id;
        $order['uid']     = $this->uid;
        $order['goods']   = $goods;
        $order['price']   = $price;
        $order['pay_way'] = $pay_way;
        $order['status']  = 0;
        $order['atime']   = time();
        $order['utime']   = time();

        $this->insert($order);
    }

    public function getList($page=1,$size=10)
    {
        return $this->builder->where(['uid'=>$this->uid])->pageInfo($page, $size);
    }
}