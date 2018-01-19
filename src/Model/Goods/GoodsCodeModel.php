<?php
/**
 * this is part of xyfree
 *
 * @file GoodsCodeModel.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-05 20:42
 *
 */

namespace DongPHP\System\Model\Goods;

use DongPHP\System\Data;
use DongPHP\System\Model\AbstractModel;

class GoodsCodeModel extends AbstractModel
{
    const R_GOODS_CODE_SET           = 'goods:code:set';
    const R_GOODS_CODE_BIND_HASH     = 'goods:code:bind:hash';


    public function initGoodsCodeSet($g_id, $period, $count)
    {
        $codes = [self::R_GOODS_CODE_SET . ':' . $g_id . '_' . $period];
        for ($i = 1; $i <= $count; $i++) {
            $codes[] = 10000000+$i;
        }

        return call_user_func_array([Data::redis('xydb.goods_code'), 'sAdd'], $codes);
    }

    /**
     * 把编号重新放回集合
     * @param $g_id
     * @param $period
     * @param $codes
     * @return mixed
     */
    public function pushCodeBack($g_id, $period, $codes)
    {
        array_unshift($codes, self::R_GOODS_CODE_SET . ':' . $g_id . '_' . $period);
        return call_user_func_array([Data::redis('xydb.goods_code'), 'sAdd'], $codes);
    }


    /**
     * 从集合中随机取出code
     * @param $g_id
     * @param $period
     * @param $num
     * @return array
     */
    public function pop($g_id, $period, $num)
    {
        $key  = self::R_GOODS_CODE_SET.':'.$g_id.'_'.$period;
        $pipe = Data::redis('xydb.goods_code')->multi(\Redis::PIPELINE);
        for ($i=0;$i<$num;$i++) {
            $pipe->sPop($key);
        }
        $codes = array_filter($pipe->exec());
        return $codes;
    }

    public function remain($g_id, $period)
    {
        $key = self::R_GOODS_CODE_SET.':'.$g_id.'_'.$period;
        return Data::redis('xydb.goods_code')->sCard($key);
    }

    /**
     * 绑
     * @param $g_id
     * @param $period
     * @param $codes
     * @param $uid
     * @return bool
     */
    public function bindToUid($g_id, $period, $codes, $uid, $id)
    {
        $hashKeys = [];
        foreach($codes as $code) {
            $hashKeys[$code] = $id;
        }
        return Data::redis('xydb.goods_code')->hMset(self::R_GOODS_CODE_BIND_HASH.':'.$g_id.'_'.$period,$hashKeys);
    }

    /**
     * 绑定到record中的ID
     * @param $g_id
     * @param $period
     * @param $codes
     * @param $id
     * @return bool
     */
    public function bindToRecord($g_id, $period, $codes, $id)
    {
        $hashKeys = [];
        foreach($codes as $code) {
            $hashKeys[$code] = $id;
        }
        return Data::redis('xydb.goods_code')->hMset(self::R_GOODS_CODE_BIND_HASH.':'.$g_id.'_'.$period,$hashKeys);
    }

    /**
     * 根据code取得购买记录
     * @param $g_id
     * @param $period
     * @param $code
     * @return string
     */
    public function getRecordByCode($g_id, $period, $code)
    {
        $key = self::R_GOODS_CODE_BIND_HASH.':'.$g_id.'_'.$period;
        $id  =  Data::redis('xydb.goods_code')->hGet($key, $code);
        $goodsBuyRecordModel = new GoodsBuyRecordModel();
        return $goodsBuyRecordModel->getOne($g_id, $period, $id);
    }
}
