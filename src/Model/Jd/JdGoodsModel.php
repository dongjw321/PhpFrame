<?php

namespace  DongPHP\System\Model\Jd;

use  DongPHP\System\Model\Goods\GoodsModel;

class JdGoodsModel extends AbstractJdModel
{

    public function __construct()
    {
        parent::__construct();
    }


    //获取商品详细信息
    public function getGoodsInfoById($gid) {
        $method = 'biz.product.detail.query';
        $params = ['sku'=>$gid];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //获取商品详细信息
    public function getGoodsDetailInfoById($gid) {
        $method = 'jingdong.ware.product.detail.search.list.get';
        $params = ['skuId'=>$gid, 'isLoadWareScore'=>false];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //获取商品上下架状态
    public function getGoodsState($gid) {
        $method = 'biz.product.state.query';
        $params = ['sku'=>$gid];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //获取同类商品
    public function getSameGoodsIds($gid) {
        $method = 'jingdong.new.ware.sameproductskuids.query';
        $params = ['skuskuId'=>$gid];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    public function checkGoods($goodsId) {
        $method = 'biz.product.sku.check';
        $params = ['skuIds'=>$goodsId];
        $url = $this->getRequestUrl($method, $params);

        $tmpCheckInfo = $this->requestApi($url);
        $checkInfo = [];
        if($tmpCheckInfo) {
            foreach($tmpCheckInfo as $item) {
                if(isset($item['skuId'])) {
                    $checkInfo[$item['skuId']] = $item;
                }
            }
        }

        return $checkInfo;
    }

    public function getPriceByIds($ids){
        $method = 'biz.price.sellPrice.get';
        $params = ['sku'=>$ids];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url,[], 'get', 0);
    }

    public function getGoodsList(){
        $method = 'biz.product.PageNum.query';
        $params = [];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    public function getGoodsIdList($pageNum){
        $method = 'biz.product.sku.query';
        $params = ['pageNum'=>$pageNum];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    public function getJdMinPriceGoodsId($gid) {
        $goodsInfo = (new GoodsModel())->getGoodsInfoByGid($gid);
        $jdGoodsIds = $goodsInfo['jd_goods_ids'] ? json_decode($goodsInfo['jd_goods_ids'], true) : [];
        if(empty($jdGoodsIds)) {
            return 0;
        }else {
            $jdGoodsIds = implode(',', $jdGoodsIds);
        }

        $jdPriceInfos = $this->getPriceByIds($jdGoodsIds);
        $checkInfo = $this->checkGoods($jdGoodsIds);

        $minPrice = 0;
        $minGid = 0;
        if(isset($jdPriceInfos['biz_price_sellPrice_get_response']['result']) && !empty($jdPriceInfos['biz_price_sellPrice_get_response']['result'])) {
            foreach($jdPriceInfos['biz_price_sellPrice_get_response']['result'] as $item) {
                //是否可售
                if(!$checkInfo[$item['skuId']]['saleState']) {
                    continue;
                }
                if($minPrice === 0 || $item['jdPrice'] < $minPrice) {
                    $minPrice = $item['jdPrice'];
                    $minGid = $item['skuId'];
                }
            }
        }

        return $minGid;
    }
}
