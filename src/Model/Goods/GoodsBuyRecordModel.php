<?php

/**
 * this is part of xyfree
 *
 * @file GoodsBuyRecord.php
 * @use     商品购买记录
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-05 10:11
 *
 */
namespace DongPHP\System\Model\Goods;

use DongPHP\System\Data;
use DongPHP\System\Libraries\DB;
use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Model\Goods\GoodsPeriodModel;

class GoodsBuyRecordModel extends AbstractModel
{

    const R_GOODS_GLOBAL_BUY_RECORD_LIST = 'goods:global:buy:record:list';

    const M_GOODS_BUYRECORD = 'goods:buyrecord';


    /**
     * 取得一条记录
     * @param $g_id
     * @param $period
     * @param $id
     * @return mixed|static
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function getOne($g_id, $period, $id)
    {
        return DB::builder('xydb.goods_buy_record', $g_id.':'.$period)
            ->where(['id'=>intval($id)])
            ->first();
    }

    /**
     * 取得某期商品购买记录的分页数据
     * @param $g_id
     * @param $period
     * @param int $page
     * @param int $size
     * @return array|static[]
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function getRecord($g_id, $period, $page=1, $size=30)
    {

        $key = sprintf('%s:%s:%s:%s' , self::M_GOODS_BUYRECORD , $g_id , $period , $page , $size);

        $periodInfo = (new GoodsPeriodModel())->getRawGoodsPeriod($period);

        if($periodInfo AND $periodInfo['status'] > GOODS_PERIOD_STATUS_ONGOING) {

            $cache = Data::Memcache('xydb.user_account')->get($key);

            $cache = false;

            if($cache) {
                return json_decode($cache , true);
            }else{
                $data = $this->getRawRecord($g_id , $period , $page , $size);
                Data::Memcache('xydb.user_account')->set($key , json_encode($data));
                return $data;
            }
        }else{
            return $this->getRawRecord($g_id , $period , $page , $size);
        }

      
    }

    public function getRawRecord($g_id, $period, $page=1, $size=30)
    {
    
        return DB::builder('xydb.goods_buy_record', $g_id.':'.$period)
            ->where(['g_id'=>$g_id, 'period'=>$period])
            ->select(['g_id','period','uid','uname','avatar','ip','ip_city','num','atime','atime_msec'])
            ->orderBy('id', 'desc')
            ->pageInfo($page, $size);  

    }


    public function getRawBuyTopMan($g_id , $period) 
    {

        $querysql = "select g_id , period , uid , uname , avatar , sum(num) as total 
            from goods_buy_record where g_id = {$g_id} and period = {$period} group by uid  order by total desc,atime asc, id desc limit 3";

        return DB::table('xydb.goods_buy_record', $g_id.':'.$period)->getRows($querysql);
    
    }


    /**
     * 根据用户取得某期商品中的购买记录
     * @param $g_id
     * @param $period
     * @param $uid
     * @return array|static[]
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function getRecordByUid($g_id, $period, $uid)
    {
        return DB::builder('xydb.goods_buy_record', $g_id.':'.$period)
            ->where(['g_id'=>$g_id, 'period'=>$period, 'uid'=>$uid])
            ->get();
    }

    /**
     * 添加商品购买记录
     * @param $g_id
     * @param $period
     * @param $record
     * @return bool
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function addRecord($g_id, $period, $record)
    {
        $id = DB::builder('xydb.goods_buy_record',  $g_id.':'.$period)->insert($record);
        $this->addGlobalRecord($record);
        return $id;
    }

    /**
     * 取得唯一的用户数
     * @param $g_id
     * @param $period
     * @return array|static[]
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function getUniqueUids($g_id, $period)
    {
        return DB::builder('xydb.goods_buy_record',  $g_id.':'.$period)
            ->where(['g_id'=>"{$g_id}", 'period'=>"$period"])
            ->distinct()
            ->get(['uid']);
    }

    /**
     * 返回全站购买记录
     * @return array
     */
    public function getGlobalRecord()
    {
        return Data::redis('xydb.goods_global_buy_record')->lRange(self::R_GOODS_GLOBAL_BUY_RECORD_LIST, 0 , 49);
    }

    /**
     * 记录全站购买记录
     * @param $record
     * @return bool
     */
    protected function addGlobalRecord($record)
    {
        unset($record['codes']);
        $redis = Data::redis('xydb.goods_global_buy_record');
        //添加全站购买记录
        $len = $redis->lPush(self::R_GOODS_GLOBAL_BUY_RECORD_LIST, json_encode($record));
        if ($len > 50) {
            $redis->rPop(self::R_GOODS_GLOBAL_BUY_RECORD_LIST);
        }
        return true;
    }
}
