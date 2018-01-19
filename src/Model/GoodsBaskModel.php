<?php
namespace DongPHP\System\Model;

use DongPHP\System\Libraries\DB;
use DongPHP\System\Libraries\Input;
use DongPHP\System\Model\Goods\GoodsPeriodModel;

class GoodsBaskModel extends AbstractModel
{
    public function __construct()
    {
        $this->builder = DB::builder("xydb.xydb_goods_bask");
    }

    public function getPage($where, $page, $size)
    {
        //$querysql = "select * from xydb_banner where b_status=2 order by b_rank desc";
        //return DB::table('xydb.xyzs_xydb')->getRows($querysql);
        return $this->builder
            ->where($where)
            ->orderBy('atime', 'desc')
            ->pageInfo($page, $size);
    }

    /**
     * 插入一条信息更新更新其他记录的状态值
     * @param $data    插入信息
     * @param $userInfo  用户信息
     */
    public function addRow($data, $userInfo)
    {
        //用户信息
        $data['uid']    = $userInfo['uid'];
        $data['uname']  = $userInfo['uname'];
        $data['avatar'] = $userInfo['avatar'];
        $data['atime']  = time();

        //删除重复信息
        $GoodsPeriodModel = new GoodsPeriodModel();
        $this->delete(['period' => $data['period']]);
        $this->insert($data);
        //同步商品信息状态
        $GoodsPeriodModel->changedGoodsPeriodStatus($data['period'], $data['status']);
    }

    public function getOneInfo($where) {
        return $this->builder
            ->where($where)
            ->first();
    }
}