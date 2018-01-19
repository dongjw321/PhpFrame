<?php

/**
 * @file CategoryModel.php
 *
 * @date 2016/01/03
 *
 * @author Setsuna.F <lyf021408@gmail.com>
 */

namespace DongPHP\System\Model\Category;
use DongPHP\System\Libraries\DB;
use DongPHP\System\Model\AbstractModel;


class CategoryModel extends AbstractModel
{

    public function __construct() 
    {

    }


    //@notice get category info by categoryid from mysql
    public function getCategoryInfoByCidFromDB($categoryid = '')
    {
        $querysql = "select * from xydb_category order by c_rank desc";
        return DB::table('xydb.xyzs_xydb')->getRows($querysql);
    }

    
    //@notice get category goods by categoryid from mysql
    public function getCategoryGoodsByCidFromDB($categoryid = '')
    {
    
    }


    //@notice get category info from cache
    public function getCategoryInfoByCategoryId($c_id)
    {
        return DB::builder('xydb.xydb_category')->where(['id' => $c_id])->first();
    }


    //@notice 分页获取分类商品
    public function getCategoryGoodsByCid($cid = '' , $page=1, $size=20, $status=false)
    {
        $where = [];
        if ($cid != 'all') {
            $where['c_id'] = $cid;
        }

       
        if ($status) {
            $where['status'] = $status;
        }
        $builder = DB::builder('xydb.xydb_goods_period');
        if ($where) {
            $builder->where($where);
        }

        if(in_array(CLIENT_VERSION , ['1.0.0' , '1.0.1' , '1.0.2'])) {
            $builder->whereNotIn(['g_id' => [4 , 40]]);
        }

        return $builder->orderBy('period','desc')->pageInfo($page, $size);
    }
    

    //@notice 分页获取分类商品
    public function getSyzqGoods($page=1, $size=20)
    {
        return DB::builder('xydb.xydb_goods_period')->where(['status'=> GOODS_PERIOD_STATUS_ONGOING , 'g_buy_copies' => 10])
            ->orderBy('p_add_date' , 'desc')->pageInfo($page , $size);
    }




}
