<?php
/**
 * this is part of xyfree
 *
 * @file UserGoldLogModel.php
 * @use  金币流水日志
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-07 19:38
 *
 */

namespace DongPHP\System\Model\User;


use DongPHP\System\Libraries\DB;
use DongPHP\System\Libraries\TcpLog;

class UserGoldLogModel extends AbstractUserModel
{
    protected $db;

    public function __construct($uid)
    {
        parent::__construct($uid);
        $this->builder = DB::builder('xydb.user_gold_log', $this->uid);
    }

    public function add($change=0, $remain=0, $event=1, $params=[])
    {
        $record['uid']    = $this->uid;
        $record['change'] = $change;
        $record['remain'] = $remain;
        $record['event']  = $event;
        $record['params'] = json_encode($params);
        $record['atime']  = time();

        TcpLog::record('goldLog', $record);
        return $this->builder->insert($record);
    }

    public function getList($page=1, $size=30)
    {
        return $this->builder->where(['uid'=>$this->uid])->pageInfo($page, $size);
    }
}