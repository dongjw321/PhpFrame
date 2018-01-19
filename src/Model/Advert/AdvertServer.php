<?php

/**
 * @file CategoryServer.php
 *
 * @date 2016/01/03
 *
 * @author Setsuna.F <lyf021408@gmail.com>
 */

namespace DongPHP\System\Model\Advert;


class AdvertServer
{

    public function __construct()
    {
    
        $this->AdvertModel = new AdvertModel();
    
    }


    public function getRecommendBanner()
    {

        $format = array();
    
        $rawData = $this->AdvertModel->getRecommendBannerFromDB();

        foreach($rawData as $value) {

            $tmp['b_img'] = $value['b_img'];
            $tmp['b_img_new'] = $value['b_img_new'];
            $tmp['b_type'] = $value['b_type'];
            $tmp['b_url'] = $value['b_url'];
            $format[] = $tmp;
        }
        return $format;
    }

}

