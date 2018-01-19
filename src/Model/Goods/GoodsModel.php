<?php

namespace DongPHP\System\Model\Goods;

use DongPHP\System\Libraries\DB;
use DongPHP\System\Data;
use DongPHP\System\Model\AbstractModel;


class GoodsModel extends AbstractModel
{

    const R_HOME_RECOMMEND_GOODS_STRING = 'home:recommend:goods:string';
    const R_GOODS_DETAIL_DIYPAGE_STRING = 'goods:detail:diypage:string:';

    const R_GOODS_BASE_INFO_STRING = 'goods:baseinfo:string';
    const R_GOODS_GUESS_LIKE_DATA_STRING = 'goods:guess:like:data:string';
    const R_GOODS_GUESS_LIKE_NUM_STRING = 'goods:guess:like:num:string';

    public function __construct()
    {
        $this->redis   = Data::redis('xydb.goods');
        $this->builder = DB::builder('xydb.xydb_goods');
    }


    //@notice 通过价格排序获取商品
    public function getGoodsByPriceSort($sort = 'l2h', $limit = 80)
    {
        $sorting = $sort == 'l2h' ? 'asc' : 'desc';
        return DB::builder('xydb.xydb_goods')->orderBy('g_price', $sorting)->limit($limit)->get('id');
    }

    //@notice 通过价格排序获取商品
    public function getGoodsByPriceSortNew($sort = 'l2h', $page = 1, $size = 20)
    {
        $sorting = $sort == 'l2h' ? 'asc' : 'desc';
        return DB::builder('xydb.xydb_goods')->where(['g_condition' => 0])->orderBy('g_price', $sorting)->pageInfo($page, $size);
    }


    //@notice 获取最新上架商品
    public function getGoodsByDateSort($limit = 80)
    {
        return DB::builder('xydb.xydb_goods')->orderBy('g_add_date', 'desc')->limit($limit)->get('id');
    }


    //@notice 获取最新上架商品
    public function getGoodsByDateSortNew($page = 1, $size = 20)
    {
        return DB::builder('xydb.xydb_goods')->where(['g_condition' => 0])->orderBy('g_add_date', 'desc')->pageInfo($page, $size);
    }


    /**
     * 获取商品详情数据 先从redis中获取 如果cache失效 从DB中获取
     *
     * @param $g_id
     */
    public function getGoodsInfoByGid($g_id = '')
    {
        $key   = sprintf('%s:%s', self::R_GOODS_BASE_INFO_STRING, $g_id);
        $cache = Data::redis('xydb.goods_code')->get($key);
        if (!$cache) {
            $goodsBaseInfo = DB::builder('xydb.xydb_goods')->where(['id' => $g_id])->first();
            Data::redis('xydb.goods_code')->set($key, json_encode($goodsBaseInfo));
            return $goodsBaseInfo;
        } else {
            return json_decode($cache, true);
        }
    }

    public function getGoodsBaseInfoByGid($g_id)
    {
        return DB::builder('xydb.xydb_goods')->where(['id' => $g_id])->first();
    }


    /**
     * 获取商品详情自定义页面
     *
     * @param $g_id
     */
    public function getDiyPageHtmlByGid($g_id = '')
    {
        return DB::builder('xydb.xydb_diypage')->where(['g_id' => $g_id, 'status' => 2])
            ->first('html');
    }

    public function getDiyPageCacheData($g_id)
    {
        $cache  = $this->redis->get(self::R_GOODS_DETAIL_DIYPAGE_STRING . $g_id);
        $result = '';
        if (!$cache) {
            $data = $this->getDiyPageHtmlByGid($g_id);
            if (isset($data['html']) && $data['html']) {
                $this->redis->set(self::R_GOODS_DETAIL_DIYPAGE_STRING . $g_id, json_encode($data['html']));
                $result = $data['html'];
            }
        } else {
            $result = json_decode($cache, true);
        }

        return $result;
    }

    public function getRecommendHotGoods($grade)
    {
        $data = $this->redis->get(self::R_HOME_RECOMMEND_GOODS_STRING . $grade);
        return $data ? json_decode($data) : array();
    }

    public function setRecommendHotGoods($grade, $data)
    {
        return $this->redis->set(self::R_HOME_RECOMMEND_GOODS_STRING . $grade, json_encode($data));
    }

    public function incGoodsRank($gid)
    {
        DB::builder('xydb.xydb_goods')->where(['id' => $gid])->increment('g_rank');
    }

    public function getGradeByGid($gid)
    {
        return DB::builder('xydb.xydb_goods')->where(['id' => $gid])->value('g_grade');
    }

    public function getRecommendHotGoodsFromDB($grade)
    {
        return DB::builder('xydb.xydb_goods')->where(['g_grade' => $grade])->orderBy('g_rank', 'desc')->get('id');
    }

    public function upRecommendHotGoods($gid)
    {
        $this->incGoodsRank($gid);
        $grade               = $this->getGradeByGid($gid);
        $tmpNewRecommendData = $this->getRecommendHotGoodsFromDB($grade);
        $newRecommendData    = array();
        foreach ($tmpNewRecommendData as $item) {
            $newRecommendData[] = $item['id'];
        }
        $this->setRecommendHotGoods($grade, $newRecommendData);
    }

    public function getGuessLikeData()
    {
        $idList = $this->redis->get(self::R_GOODS_GUESS_LIKE_DATA_STRING);
        return $idList ? json_decode($idList, true) : [];
    }

    public function getGuessLikeNum()
    {
        $num = $this->redis->get(self::R_GOODS_GUESS_LIKE_NUM_STRING);
        return $num ? $num : 9;
    }


}
