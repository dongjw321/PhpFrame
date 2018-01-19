<?php

namespace DongPHP\System\Model\Goods;

use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Libraries\DB;

class GoodsJXModel extends AbstractModel
{

    public function __construct() 
    {
    }

    /**
     * 商品人次购买完毕添加全局购买记录
     * @param $record
     * @param array $kjinfo
     * @param array $period_info
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function recordGoodsNeedJX($record, $kjinfo=[], $period_info=[])
    {
        $data['period']          = $period_info['period'];
        $data['g_id']            = $period_info['g_id'];
        $data['buy_record']      = $record;
        $data['period_end_time'] = time();
        $data['j_add_time']      = time();
        $data['g_price']         = $period_info['g_buy_total'];
        $data['j_status']        = GOODS_PERIOD_STATUS_WAITING;
        $data['o_expect']        = $kjinfo['period_expect'];
        $data['o_opentime']      = $kjinfo['cp_opentime'];

        // log record
        DB::builder('xydb.xydb_period_need_jx')->insert($data);
    
    }

    //@notice 获取商品往期揭晓数据
    public function getGoodsWQJX($g_id , $page=1 , $size = 20)
    {
        return DB::builder('xydb.xydb_goods_period')->where(['g_id' => $g_id])
            ->where('status' , '>=' , GOODS_PERIOD_STATUS_END)
            ->orderBy('period','desc')
            ->pageInfo($page, $size);
    }

    
    //@notice 获取带揭晓期号
    public function getGoodsPeriodNeedJXByExpert($expert)
    {
        return DB::builder('xydb.xydb_period_need_jx')->where(['o_expect' => $expert])->get();
    }


    //@notice 获取所有带揭晓期号
    public function getGoodsPeriodNeedJXAll()
    {
        return DB::builder('xydb.xydb_period_need_jx')->where(['j_status' => GOODS_PERIOD_STATUS_WAITING])->get();
    }



    //@notice 获取商品最新揭晓
    public function getGoodsPeriodZXJX($page=1 , $size = 20)
    {
        return DB::builder('xydb.xydb_period_need_jx')->where('j_status' , '>' , GOODS_PERIOD_STATUS_ONGOING)
            ->orderBy('id' , 'desc')
            ->pageInfo($page, $size);
    }


    //@notice 通过期号查找揭晓信息
    public function getGoodsPeriodNeedJXByPeriod($period)
    {
         return DB::builder('xydb.xydb_period_need_jx')->where(['period' => $period])->first();
    }


    public function changeGoodsPeriodNeedJXStatus($period = '' , $uid ,  $status = '')
    {
        return DB::builder('xydb.xydb_period_need_jx')->where(['period'=> $period])->update(['j_status' => $status , 'uid' => $uid]);
    }

    //@notice 获取期号获取开奖信息
    public function getGoodsPeriodKJXX($period)
    {
        return (new GoodsPeriodExpandModel($period))->getInfo();
    }


    //@notice 通过彩票期号获取公开彩票开奖信息
    public function getOpenCaiInfoByExpert($expect)
    {
         return DB::builder('xydb.xydb_grab_opencai')->where(['o_expect' => $expect])->first();
    }
    
}
