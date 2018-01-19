<?php
/**
 * this is part of xyfree
 *
 * @file AbstractUserModel.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-06 10:15
 *
 */

namespace DongPHP\System\Model\User;

use DongPHP\System\Model\AbstractModel;

class AbstractUserModel extends AbstractModel
{
    protected $uid;

    public function __construct($uid)
    {
        parent::__construct();
        if (!$uid || !intval($uid)) {
            throw new \Exception('参数错误', 999);
        }
        $this->uid = $uid;
    }
}