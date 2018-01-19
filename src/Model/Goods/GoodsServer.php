<?php
namespace DongPHP\Model\Category;
use DongPHP\Model\AbstractModel;


class GoodsServer extends AbstractModel
{

    public function __construct() 
    {

    }


    //@notice get category info by categoryid from mysql
    protected function getCategoryInfoByCidFromDB($categoryid = '')
    {
    
        $querysql = "select * from xydb_category";
    
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
