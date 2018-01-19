<?php

namespace DongPHP\System\Model\Goods;

use DongPHP\System\Data;
use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Model\Goods\GoodsCodeModel;
use DongPHP\System\Libraries\DB;
use DongPHP\System\Libraries\TcpLog;

class GoodsPeriodModel extends AbstractModel
{
    const R_GOODS_PERIOD_INCR_STRING = 'goods:period:incr:string';
    const R_GOODS_LATEST_PERIOD_STRING = 'goods:latest:period:string';
    const R_GOODS_CHANGE_STATUS_LIST = 'goods:change:status:list';

    public function __construct()
    {
    }

    //@notice 上架新一期商品
    public function addGoodsPeriod($gid)
    {
        $record     = [];
        $period     = $this->getGoodsPeriodTodayId(false);
        $goodsModel = new GoodsModel();
        $goodsInfo  = $goodsModel->getGoodsInfoByGid($gid);

        if ($goodsInfo['g_condition'] == '99') {
            //如果商品已经下架，则不进行自动上架处理逻辑
            return false;
        }

        $record['g_id']         = $goodsInfo['id'];
        $record['c_id']         = $goodsInfo['c_id'];
        $record['corner']       = $goodsInfo['corner'];
        $record['g_name']       = $goodsInfo['g_name'];
        $record['g_price']      = $goodsInfo['g_price'];
        $record['g_remain']     = $goodsInfo['g_price'];
        $record['g_uprice']     = $goodsInfo['g_uprice'];
        $record['g_buy_copies'] = $goodsInfo['g_buy_copies'];
        $record['g_img']        = $goodsInfo['g_img'];
        $record['g_block_img']  = $goodsInfo['g_block_img'];
        $record['g_list_img']   = $goodsInfo['g_list_img'];
        $record['status']       = GOODS_PERIOD_STATUS_ONGOING;
        $record['p_add_date']   = time();
        $record['period']       = $period;

        //@notice 插入到数据库
        DB::builder('xydb.xydb_goods_period')->insert($record);
        //@notice 添加商品最新的期号
        Data::redis('xydb.goods_code')->set(self::R_GOODS_LATEST_PERIOD_STRING . ':' . $gid, $period);

        //@notice 记录添加商品
        TcpLog::record('period', sprintf('%s', json_encode($record)));

        (new GoodsCodeModel())->initGoodsCodeSet($gid, $period, $goodsInfo['g_price']);
    }

    //@notice 通过商品期号获取信息
    public function getRawGoodsPeriod($period)
    {
        return is_array($period) ? DB::builder('xydb.xydb_goods_period')->whereIn('period', $period)->get()
            : DB::builder('xydb.xydb_goods_period')->where(['period' => $period])->first();
    }

    //@notice 获取商品最新一期期号
    public function getGoodsLatestPeriod($gid)
    {
        return Data::redis('xydb.goods_code')->get(self::R_GOODS_LATEST_PERIOD_STRING . ':' . $gid);
        //return $latestPeriod ?: DB::builder('xydb.xydb_goods_period')->where(['g_id'=>$gid])->orderBy('period' , 'desc')->first('period');
    }


    //@notice 通过期号获取本期信息
    public function getGoodsPeriod($period)
    {

        $value = $this->getRawGoodsPeriod($period);
        if (!$value) return false;
        $tmp                 = [];
        $tmp['status']       = $value['status'];
        $tmp['g_id']         = $value['g_id'];
        $tmp['c_id']         = $value['c_id'];
        $tmp['corner']       = $value['corner'];
        $tmp['g_name']       = $value['g_name'];
        $tmp['g_img']        = $value['g_img'];
        $tmp['g_list_img']   = $value['g_list_img'];
        $tmp['g_block_img']  = $value['g_block_img'];
        $tmp['period']       = $value['period'];
        $tmp['kj_time']      = $value['kj_time'];
        $tmp['g_buy_total']  = $value['g_price'];
        $tmp['g_buy_unit']   = $value['g_uprice'];
        $tmp['g_buy_copies'] = $value['g_buy_copies'];
        $tmp['g_buy_limit']  = $value['g_remain'];
        $tmp['g_status']     = $value['status'];
        if ($value['status'] >= GOODS_PERIOD_STATUS_END) {//已揭晓
            $goodsPeriodExpandModel = new GoodsPeriodExpandModel($period);
            $tmp['expand']          = $goodsPeriodExpandModel->getInfo();
            if (!$tmp['expand']) {
                unset($tmp['expand']);
            }
        }

        if ($value['corner'] == 11) { //五元购
            $tmp['corner_img'] = 'http://pic.xyzs.com/xydb/corner/wuyuangou.png';
        } else if ($value['corner'] == 12) { //十元购
            $tmp['corner_img'] = 'http://pic.xyzs.com/xydb/corner/shiyuangou.png';
        }

        return $tmp;
    }

    //@notice 获取今日商品期号当前数量
    public function getGoodsPeriodTodayId($incr = false)
    {
        $key       = self::R_GOODS_PERIOD_INCR_STRING . ':' . date('Ymd');
        $todayIncr = $incr ? Data::redis('xydb.goods_code')->get($key) : Data::redis('xydb.goods_code')->incr($key);
        return (date('Y') - 2016 + 1) . date('md') . str_pad($todayIncr, 4, '0', STR_PAD_LEFT);
    }

    //商品期号变成等待开奖
    public function changedGoodsPeriodStatus($period = '', $status = '')
    {
        return DB::builder('xydb.xydb_goods_period')->where(['period' => $period])->update(['status' => $status]);
    }


    /**
     * 更改状态 为等待开奖
     * @param $g_id
     * @param $period
     * @param $global_record
     */
    public function changeStatusToWaiting($g_id, $period, $global_record, $period_info = [])
    {
        $period_info || $period_info = $this->getGoodsPeriod($period);

        //更改本期的状态
        $kjinfo = getKJInfo();

        //记录到开奖表里
        $goodsJXModel = new GoodsJXModel();
        $goodsJXModel->recordGoodsNeedJX($global_record, $kjinfo, $period_info);

        DB::builder('xydb.xydb_goods_period')
            ->where(['period' => $period])
            ->update(['status' => GOODS_PERIOD_STATUS_WAITING, 'g_remain' => 0, 'kj_time' => $kjinfo['cp_opentime'], 'utime' => time()]);

        //上架新的商品(添加了判断，如果存在多期，那么老的开奖后不会再创建新的)
        if ($this->getGoodsLatestPeriod($g_id) == $period) {
            $this->addGoodsPeriod($g_id);
        }

        //添加任务队列，【通知所有参与用户更新状态】
        $task = json_encode(['g_id' => $g_id, 'period' => $period, 'status' => GOODS_PERIOD_STATUS_WAITING, 'time' => time()]);
        Data::redis('xydb.goods')->lPush(self::R_GOODS_CHANGE_STATUS_LIST, $task);
    }

    /**
     * 更改状态 为开奖结束
     * @param $g_id
     * @param $period
     * @param array $win_info
     * @throws \DongPHP\System\System\Libraries\DBException
     */
    public function changeStatusToEnd($g_id, $uid, $period, $win_info = [])
    {
        //更改开奖记录表
        (new GoodsJXModel())->changeGoodsPeriodNeedJXStatus($period, $uid, GOODS_PERIOD_STATUS_END);

        //更改本期的状态
        DB::builder('xydb.xydb_goods_period')
            ->where(['period' => $period])
            ->update(['status' => GOODS_PERIOD_STATUS_END, 'utime' => time()]);

        //更改中奖信息
        (new GoodsPeriodExpandModel($period))->updateWinInfo($win_info);

        //更改首页热榜推荐权重
        //(new GoodsModel())->upRecommendHotGoods($g_id);


        //添加任务队列，【通知所有参与用户更新状态】
        $task = json_encode(['g_id' => $g_id, 'period' => $period, 'status' => GOODS_PERIOD_STATUS_END, 'time' => time()]);
        Data::redis('xydb.goods')->lPush(self::R_GOODS_CHANGE_STATUS_LIST, $task);
    }

    public function upInfo($where, $data)
    {
        $data['utime'] = time();
        DB::builder('xydb.xydb_goods_period')
            ->where($where)
            ->update($data);
    }


    /**
     * 确认收货地址
     * @param $period
     * @param $address
     * @throws \DongPHP\System\System\Libraries\DBException
     */
    public function confirmDeliveryAddress($period, $address)
    {
        //更改本期的状态
        DB::builder('xydb.xydb_goods_period')
            ->where(['period' => $period])
            ->update(['status' => GOODS_PERIOD_STATUS_CONFIRM_ADDRESS, 'utime' => time()]);

        //更改收货地址
        (new GoodsPeriodExpandModel($period))->confirmDeliveryAddress($address);
    }

    /**
     * 更新剩余数量, 计算百分比值
     * @param $g_id
     * @param $period
     * @param $remain
     * @param $total
     * @throws \DongPHP\System\System\Libraries\DBException
     */
    public function updateRemain($g_id, $period, $remain, $total)
    {
        $per = round((($total - $remain) / $total), 3);
        DB::builder('xydb.xydb_goods_period')
            ->where(['period' => $period])
            ->update(['g_remain' => $remain, 'p_per' => $per, 'utime' => time()]);
    }


    //@notice 格式化 期号
    public function formatGoodsPeriod($period, $type = 'list')
    {

        $format['g_id'] = isset($period['g_id']) ? $period['g_id'] : $period['id'];
        //获取最新一期的旗号
        $format['period'] = isset($period['period']) ? $period['period'] : $this->getGoodsLatestPeriod($format['g_id']);

        if (!$format['period']) {
            //如果没有获取到期号，不返回数据
            return false;
        }

        //获取
        $tmpGoodsPeriod = $this->getRawGoodsPeriod($format['period']);
        //如果这一期状态已经下架，列表中不显示
        if ($tmpGoodsPeriod['status'] != GOODS_PERIOD_STATUS_ONGOING) {
            return false;
        }

        $format['c_id']         = $tmpGoodsPeriod['c_id'];
        $format['g_name']       = $tmpGoodsPeriod['g_name'];
        $format['g_buy_total']  = $tmpGoodsPeriod['g_price'];
        $format['g_buy_unit']   = '1';  //默认设置为1
        $format['g_buy_copies'] = $tmpGoodsPeriod['g_buy_copies'] ?: '1';
        $format['g_buy_limit']  = $tmpGoodsPeriod['g_remain'];
        $format['g_img']        = ($type == 'list') ? $tmpGoodsPeriod['g_list_img'] : $tmpGoodsPeriod['g_block_img'];
        $format['corner']       = $tmpGoodsPeriod['corner'] ?: '';

        if ($format['corner'] == 11) { //五元购
            $format['corner_img'] = 'http://dbimg.ixydb.com/00/00/wuyuangou.png';
        } else if ($format['corner'] == 12) { //十元购
            $format['corner_img'] = 'http://dbimg.ixydb.com/00/00/shiyuangou.png';
        }


        return $format;

    }

}
