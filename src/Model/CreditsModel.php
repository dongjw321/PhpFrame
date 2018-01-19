<?php
namespace DongPHP\System\Model;

use DongPHP\System\Libraries\DB;
use DongPHP\System\Model\User\UserInfoModel;


class CreditsModel extends AbstractModel
{
    public function __construct(&$uid)
    {
        $this->uid         = &$uid;
        $this->DBRecord    = DB::builder("xydb.user_credits_record", $uid);
    }

    /**
     * 积分操作
     * @param $cmd 1 增加积分 2减少积分
     * @param array $param 增加一些基本信息字段
     * @param int $type 默认1充值  2兑换 3回滚
     * @return bool|string
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function operate($cmd, $param = [], $type = 1)
    {
        $time                       = time();
        $param['credits_order_num'] = $this->getOrderNum($time, $type);
        $param['addtime']           = $time;
        $param['operate']           = $cmd;
        $param['operate_type']      = $type;
        $operate                    = $cmd == 1 ? 'increment' : 'decrement';
        $param['credits'] = $param['credits'] ? $param['credits'] : 0;
        if (!(new UserInfoModel($this->uid))->getBuilder()->where(['uid' => $this->uid])->$operate('credits', $param['credits'])) {
            return false;
        }else {
            (new UserInfoModel($this->uid))->modify([]);
        }
        $this->DBRecord->insert($param);
        return $param['credits_order_num'];
    }

    /**
     * 生成积分订单号
     * @param int $time 时间
     * @param int $type 1充值购买  2积分欢悦商品 3回滚
     * @return string  积分订单号
     */
    public function getOrderNum($time, $type = 1)
    {
        $prefix = 'CZ';
        if ($type == 2) $prefix = 'HY';
        if ($type == 3) $prefix = 'HG';
        return $prefix . '_' . $time . '_' . $this->uid . '_' . rand(1000, 9999);
    }

    /**
     * 回滚操作
     * @param $where array  查询出需要回滚的订单
     * @return bool|string
     */
    public function rollBack($where)
    {
        $record = $this->DBRecord->where($where)->first();
        if ($record['operate'] == 1) {
            $record['operate'] = 2;
        } else {
            $record['operate'] = 1;
        }
        $data = $record;
        return $this->operate($record['operate'], $data, 3);
    }

    /**
     * 修改状态
     */
    public function update($where = [], $data = [])
    {
        return $this->DBRecord->where($where)->update($data);
    }

    /*
     * 获取当前信息
     */
    public function getRecordInfo($where = [])
    {
        return $this->DBRecord->where($where)->first();
    }
}