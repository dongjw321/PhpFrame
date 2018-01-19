<?php
/**
 * this is part of xyfree
 *
 * @file GoodsExpandModel.php
 * @use  扩展信息
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-18 16:01
 *
 */

namespace DongPHP\System\Model\Goods;


use DongPHP\System\Data;
use DongPHP\System\Libraries\DB;
use DongPHP\System\Model\AbstractModel;

class GoodsPeriodExpandModel extends AbstractModel
{

    CONST R_PERIOD_EXPAND_STRING = 'goods:period:expand:string:';

    protected $builder;
    protected $period;
    protected $info;
    public function __construct($period)
    {
        $this->period  = $period;
        $this->builder = DB::builder('xydb.goods_period_expand');
        $this->redis   = Data::redis('xydb.goods_period_expand');

        $this->setInfo();
    }

    public function setInfo()
    {
        $info = $this->redis->get(self::R_PERIOD_EXPAND_STRING.$this->period);
        $info = @json_decode($info, true);

        if (is_array($info)) {
            $this->info = $info;
            return true;
        }

        $info = $this->builder->where(['period' => $this->period])->value('info');
        $info = json_decode($info, true);

        if (!is_array($info)) {
            $this->info = [];
            $this->builder->insert(['period' => $this->period, 'info' => json_encode($this->info), 'utime' => time()]);
        } else {
            $this->info = $info;
            $this->redis->set(self::R_PERIOD_EXPAND_STRING.$this->period, json_encode($this->info));
        }
    }

    public function getInfo()
    {
        return $this->info;
    }

    /**
     * 更新中奖者信息
     * @param $win_info
     * @return int
     */
    public function updateWinInfo($win_info)
    {
        $this->info['win_info'] = $win_info;
        $this->info['status']   = GOODS_PERIOD_STATUS_END;
        return $this->save();
    }



    //@更新晒单信息
    public function updateBaskInfo($bask_info , $status)
    {
        $this->info['win_info'] = $bask_info;
        $this->info['status']   = GOODS_PERIOD_STATUS_END;
        $this->info['bask_status']   = $status;
        return $this->save();
    }




    /**
     * 更新收货地址
     * @param array $address
     * @return int
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function confirmDeliveryAddress($address=[])
    {
        $this->info['address'] = $address;
        $this->info['status']  = GOODS_PERIOD_STATUS_CONFIRM_ADDRESS;
        return $this->save();
    }

    /**
     * 更新快递信息
     * @param array $express
     * @return int
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function confirmExpressInfo( $express=[])
    {
        $this->info['express'] = $express;
        $this->info['status']  = GOODS_PERIOD_STATUS_CONFIRM_EXPRESS;
        return $this->save();
    }

    /**
     * 确认收货
     * @return int
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function confirmReceipt()
    {
        $this->info['status']  = GOODS_PERIOD_STATUS_CONFIRM_RECEIPT;
        return $this->save();
    }

    /**
     * 保存操作
     * @return int
     */
    public function save()
    {
        /**
         * 更改主表里面的状态
         */
        $goodsPeriodModel = new GoodsPeriodModel();
        $goodsPeriodModel->changedGoodsPeriodStatus($this->period, $this->info['status']);

        $info = json_encode($this->info);
        $this->redis->set(self::R_PERIOD_EXPAND_STRING.$this->period, $info);
        return $this->builder->where(['period'=>$this->period])->update(['info'=>$info,'utime'=>time()]);
    }

    public static function multGet($periods=[])
    {
        $periods_keys = array_map(function($a){return self::R_PERIOD_EXPAND_STRING.$a;}, $periods);
        $infos   = Data::redis('xydb.goods_period_expand')->mget($periods_keys);
        $ret     = [];
        foreach($periods as $k => $period) {
            $ret[$period] = $infos[$k];
        }

        return $ret;
    }

}
