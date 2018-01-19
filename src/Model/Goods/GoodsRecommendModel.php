<?php

namespace DongPHP\Model\Goods;

use DongPHP\System\Libraries\DB;
use DongPHP\Model\AbstractModel;

class GoodsRecommendModel extends AbstractModel
{
    public function __construct()
    {
    }


    //@notice 获取商品信息
    public function getGoodsInfoByGid($g_id = '')
    {
        return DB::builder('xydb.xydb_goods')->where(['id' => $g_id])
            ->first();
    }


    //@notice get category info by categoryid from mysql
    protected function getCategoryInfoByCidFromDB($categoryid = '')
    {
        $querysql = "select * from xydb_category";
    }


    //@notice 获取推荐位置分类
    public function getHomepageRecommendClass()
    {
        return DB::builder('xydb.xydb_recommend_category')->orderBy('rank', 'desc')->get();
    }

    public function getRecommendGoodsByRecommendClassCode($code)
    {
        $raw              = DB::builder('xydb.xydb_recommend')->where(['recommend_class_code' => $code])->orderBy('rank', 'desc')->get();
        $return           = array();
        $goodsPeriodModel = new GoodsPeriodModel();
        $goodsModel       = new GoodsModel();

        foreach ($raw as $value) {
            $goodsInfo = $goodsModel->getGoodsInfoByGid($value['g_id']);

            $tmp                = [];
            $tmp['g_id']        = $value['g_id'];
            $tmp['c_id']        = $goodsInfo['c_id'];
            $tmp['g_name']      = $goodsInfo['g_name'];
            $tmp['g_img']       = $goodsInfo['g_img'];
            $tmp['period']      = $goodsPeriodModel->getGoodsLatestPeriod($value['g_id']);
            $tmpGoodsPeriod     = $goodsPeriodModel->getRawGoodsPeriod($tmp['period']);
            $tmp['g_buy_total'] = $tmpGoodsPeriod['g_price'];
            $tmp['g_buy_unit']  = $tmpGoodsPeriod['g_uprice'];
            $tmp['g_buy_limit'] = $tmpGoodsPeriod['g_remain'];

            $return[] = $tmp;
        }

        return $return;
    }

    //@notice 
    public function getRecommendAdvertByRecommendClassCode($code)
    {
        return DB::builder('xydb.xydb_advert')->where(['recommend_class_code' => $code])->get();
    }


    //@notice get category goods by categoryid from mysql
    public function getCategoryGoodsByCidFromDB($categoryid = '')
    {
    }


    //@notice get category info from cache
    public function getCategoryInfoByCategory()
    {
    }


    //@notice get category goods by categoryid from cache
    public function getCategoryGoodsByCid()
    {
    }
}
