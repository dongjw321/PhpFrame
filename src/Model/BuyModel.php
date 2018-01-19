<?php


namespace DongPHP\System\Model;

use DongPHP\System\Data;
use DongPHP\System\Libraries\Ipku\IP;
use DongPHP\System\Libraries\TcpLog;
use DongPHP\System\Libraries\String;
use DongPHP\System\Libraries\Lock;
use DongPHP\System\Model\Goods\GoodsBuyRecordModel;
use DongPHP\System\Model\Goods\GoodsCodeModel;
use DongPHP\System\Model\Goods\GoodsJXModel;
use DongPHP\System\Model\Goods\GoodsPeriodModel;
use DongPHP\System\Model\User\UserGoldModel;
use DongPHP\System\Model\User\UserGoldModelException;
use DongPHP\System\Model\User\UserInfoModel;
use DongPHP\System\Model\User\UserLoginModel;
use DongPHP\System\Model\User\UserJoinRecordModel;

class BuyModelException extends \Exception
{
}

class BuyModel extends AbstractModel
{
    const R_GOODS_CHANGE_STATUS_LIST = 'goods:change:status:list';

    public function execute($g_id, $period, $uid, $num, $order_id = null , $period_info = [])
    {

        if (!$period || !$g_id) {
            throw new BuyModelException('该期商品已筹满，可前往下一期参与', 1);
        }

        if(! $period_info) {
            $goodsPeriodModel = new GoodsPeriodModel();
            $period_info = $goodsPeriodModel->getGoodsPeriod($period);
        }
       
        $period_status = $period_info['status'];
        if ($period_status != GOODS_PERIOD_STATUS_ONGOING) {
            throw new BuyModelException('该期商品已筹满，可前往下一期参与', 1);
        }


        //定义初始化时间
        $startTime = microtime(true);

        $goodsCodeModel = new GoodsCodeModel();
        $codes          = $goodsCodeModel->pop($g_id, $period, $num);
        $real_num       = count($codes);

        //定义初始化时间
        $endTime = microtime(true);

        //计算pop时间
        $execTime1 = $endTime - $startTime;

        //初始化开始时间
        $startTime = microtime(true);

        if ($real_num == 0) {
            throw new BuyModelException('该期商品已筹满，可前往下一期参与');
        }

        $userGoldModel = new UserGoldModel($uid);

        //添加锁
        $lockFlag = Lock::add(__METHOD__ . ':' . $g_id . ':' . $period);

        if(! $lockFlag) {

            $endTime = microtime(true);

            $execTime2 = $endTime - $startTime;

            $saveLog = [];
            $saveLog['g_id'] = $g_id;
            $saveLog['period'] = $period;
            $saveLog['uid'] = $uid;
            $saveLog['num'] = $num;
            $saveLog['real_num'] = $real_num;
            $saveLog['pop_time'] = $execTime1;
            $saveLog['lock_time'] = $execTime2;
            $saveLog['desc'] = 'lock timeout';
            TcpLog::record('sdk/buycode' , json_encode($saveLog));

            throw new BuyModelException('商品正在被购买，请稍后尝试');
        }

        try {
            $userGoldModel->sub($real_num, $g_id, $period, $order_id);
        } catch (UserGoldModelException $e) {
            $goodsCodeModel->pushCodeBack($g_id, $period, $codes);
            throw new BuyModelException('扣钱失败');
        }


        //删除锁
        Lock::del(__METHOD__ . ':' . $g_id . ':' . $period);

        //定义解锁时间
        $endTime = microtime(true);
        //计算执行时间
        $execTime2 = $endTime - $startTime;

        //定义日志格式
        $saveLog = [];
        $saveLog['g_id'] = $g_id;
        $saveLog['period'] = $period;
        $saveLog['uid'] = $uid;
        $saveLog['num'] = $num;
        $saveLog['real_num'] = $real_num;
        $saveLog['pop_time'] = $execTime1;
        $saveLog['lock_time'] = $execTime2;

        TcpLog::record('sdk/buycode' , json_encode($saveLog));


        //验证是否被买光
        $remain = $goodsCodeModel->remain($g_id, $period);
        if ($remain < 1) {
            $period_status = GOODS_PERIOD_STATUS_WAITING;
        }

        if ($period_status == GOODS_PERIOD_STATUS_WAITING) {
            //如果商品已经结束
            $this->noticeChange($g_id, $period, $period_info);
        } else {
            //更新剩余数量
            (new GoodsPeriodModel())->updateRemain($g_id, $period, $remain , $period_info['g_buy_total']);
        }


        //添加一个商口购买记录
        list($sec, $msec) = explode('.', round(microtime(true), 3));
        $cacheip          = (new UserLoginModel())->getLoginIpFormMem($uid);
        $ip               = $cacheip ?: String::getClientIp();
        $ip_info          = IP::getInfo($ip);


        $ip_info['user_ip'] = $cacheip;

        //TcpLog::record('sdk/userip' , json_encode($ip_info) );


        $user_info        = (new UserInfoModel($uid))->get();

        $record['g_id']       = $g_id;
        $record['period']     = $period;
        $record['uid']        = $uid;
        $record['uname']      = $user_info['uname'];
        $record['avatar']     = $user_info['avatar'];
        $record['ip']         = $ip;
        $record['ip_city']    = $ip_info['regionName'] . $ip_info['cityName'];
        $record['num']        = $real_num;
        $record['codes']      = implode(',', $codes);
        $record['atime']      = $sec;
        $record['atime_msec'] = str_pad($msec, 3, '0', STR_PAD_RIGHT);

        $record_id = (new GoodsBuyRecordModel())->addRecord($g_id, $period, $record);
        //绑定uid到code
        $goodsCodeModel->bindToRecord($g_id, $period, $codes, $record_id);

        //添加用户购买记录
        (new UserJoinRecordModel($uid))->add($g_id, $period, $real_num, $period_status);

        return ['codes' => implode(',', $codes), 'num' => $real_num];
    }


    public function noticeChange($g_id, $period, $period_info)
    {
        //通知商品
        $global_record = (new GoodsBuyRecordModel())->getGlobalRecord();
        (new GoodsPeriodModel)->changeStatusToWaiting($g_id, $period, json_encode($global_record), $period_info);
    }
}
