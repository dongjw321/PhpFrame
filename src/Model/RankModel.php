<?php

namespace DongPHP\System\Model;

use DongPHP\System\Data;
use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Model\Goods\GoodsCodeModel;
use DongPHP\System\Libraries\DB;
use DongPHP\System\Libraries\TcpLog;

class RankModel extends AbstractModel
{

    public function __construct()
    {

    }


    //@notice 获取进度榜单
    public function getPeriodByProcess()
    {
        return DB::builder('xydb.xydb_goods_period')
            ->where('status', GOODS_PERIOD_STATUS_ONGOING)
            //->where('p_per' , '>' , '0.5')
            ->orderBy('p_per', 'desc')
            ->limit(100)
            ->get();
    }


    //@notice 获取进度榜单
    public function getPeriodByProcessNew($page = 1, $size = 20)
    {
        return DB::builder('xydb.xydb_goods_period')
            ->where('status', GOODS_PERIOD_STATUS_ONGOING)
            //->where('p_per' , '>' , '0.5')
            ->orderBy('p_per', 'desc')
            ->pageInfo($page, $size);
    }


    //@notice 获取热销榜单数据
    public function getPeriodByBasesale()
    {

    }


    //@notice 获取牛人榜单数据
    public function getPeriodByTopman()
    {

    }


}
