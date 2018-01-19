<?php
/**
 * this is part of xyfree
 *
 * @file UserchargeRecordModel.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-11 15:31
 *
 */

namespace DongPHP\Model\User;


use DongPHP\System\Libraries\DB;

class UserChargeRecordModel extends AbstractUserModel
{
    public function __construct($uid)
    {
        parent::__construct($uid);
        $this->builder = DB::builder('user_charge_record', $this->uid);
    }
}