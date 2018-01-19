<?php

namespace DongPHP\System\Model\Jd;

class JdAddressModel extends AbstractJdModel
{

    public function __construct()
    {
        parent::__construct();
    }


    //获取jd一级地址
    public function getJdProvince() {
        $method = 'biz.address.allProvinces.query';
        $url = $this->getRequestUrl($method);

        return $this->requestApi($url);
    }

    //获取jd二级地址
    public function getJdCity($id) {
        $method = 'biz.address.citysByProvinceId.query';
        $params = ['id'=>$id];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //获取jd三级地址
    public function getJdArea($id) {
        $method = 'biz.address.countysByCityId.query';
        $params = ['id'=>$id];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //获取jd四级地址
    public function getJdTown($id) {
        $method = 'biz.address.townsByCountyId.query';
        $params = ['id'=>$id];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }
}
