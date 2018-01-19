<?php

/**
 * @file CategoryServer.php
 *
 * @date 2016/01/03
 *
 * @author Setsuna.F <lyf021408@gmail.com>
 */

namespace DongPHP\System\Model\Category;
use DongPHP\System\Model\Category\CategoryModel;

class CategoryServer 
{

    public function __construct()
    {
        $this->CategoryModel = new CategoryModel();
    }


    public function getCategoryInfoByCid()
    {

        $data = $this->CategoryModel->getCategoryInfoByCidFromDB();
        $return = array();
        foreach($data as $value) {
            $tmp = array();
            $tmp['c_id'] = '全部商品' == $value['c_name'] ? 'all' : $value['id'];
            $tmp['c_name'] = $value['c_name'];
            $tmp['c_img'] =  $value['c_img'];
        
            $return[] = $tmp;
        }

        return $return;
    
    }

}

