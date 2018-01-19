<?php
/**
 * this is part of xyfree
 *
 * @file UserBuyRecord.php
 * @use  用户参与记录
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-05 10:47
 *
 */
namespace DongPHP\System\Model\User;

use DongPHP\System\Libraries\DB;

class UserJoinRecordModel extends AbstractUserModel
{
    protected $uid;

    public function __construct($uid)
    {
        parent::__construct($uid);
        $this->builder = DB::builder('xydb.user_join_record', $this->uid);
    }

    public function add($g_id, $period, $num, $status)
    {
        $exist = $this->builder->where(['uid' => $this->uid, 'g_id' => $g_id, 'period' => $period])->exists();
        if ($exist) {
            $this->builder->where(['uid' => $this->uid, 'g_id' => $g_id, 'period' => $period])->increment('num',$num);
        } else {
            $this->builder->insert(['uid'    => $this->uid,
                          'g_id'   => $g_id,
                          'period' => $period,
                          'num'    => $num,
                          'status' => $status,
                          'atime'  => time()
                ]);
        }
    }

    public function getList($status = 9, $page = 1, $size = 10)
    {
        $status       = intval($status);
        $where['uid'] = $this->uid;

        if ($status == 2) {//进行中
            return $this->builder->where($where)->where('status', '<=', $status)->orderBy('atime', 'desc')->orderBy('id' , 'desc')->pageInfo($page, $size);
        }

        if ($status == 4) {//已筹满和已开奖
            return $this->builder->where($where)->where('status', '>=', 2)->orderBy('atime', 'desc')->orderBy('id' , 'desc')->pageInfo($page, $size);
        }

        if ($status != 9) {
            $where['status'] = $status;
        }

        return $this->builder->where($where)->orderBy('atime', 'desc')->orderBy('id' , 'desc')->pageInfo($page, $size);
    }

    public function updateStatus($g_id, $period, $status = 1)
    {
        return $this->builder->where(['uid' => $this->uid, 'g_id' => $g_id, 'period' => $period])
                                ->update(['status' => $status]);
    }

    /**
     * 获取用户某期参与的总数
     * @param $g_id
     * @param $period
     * @return mixed
     */
    public function getNum($g_id, $period)
    {
        return $this->builder->where(['uid' => $this->uid, 'g_id' => $g_id, 'period' => $period])
            ->value('num');
    }
}
