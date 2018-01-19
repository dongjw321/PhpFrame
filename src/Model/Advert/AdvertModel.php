<?php

/**
 * @file AdvertModel.php
 *
 * @date 2016/01/03
 *
 * @author Setsuna.F <lyf021408@gmail.com>
 */


namespace DongPHP\System\Model\Advert;
use DongPHP\System\Data;
use DongPHP\System\Libraries\DB;


class AdvertModel extends \DongPHP\System\Model
{

    public function __construct() 
    {

    }



    //@notice get category info by categoryid from mysql
    public function getRecommendBannerFromDB()
    {
        $querysql = "select * from xydb_banner where b_status=2 and b_type!=3 order by b_rank desc";
        return DB::table('xydb.xyzs_xydb')->getRows($querysql);
    }
    
}
