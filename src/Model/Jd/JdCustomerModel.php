<?php

namespace DongPHP\System\Model\Jd;

class JdCustomerModel extends AbstractJdModel
{

    public function __construct()
    {
        parent::__construct();
    }


    //查询配送信息
    public function orderTrack($jdOrderId) {
        $method = 'biz.order.orderTrack.query';
        $params = ['jdOrderId' => $jdOrderId];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //查询配送信息
    public function wuLiu($jdOrderId) {
        $method = 'biz.order.waybilltrack.search';
        $params = ['jdOrderId' => $jdOrderId];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //根据日期查询订单
    public function checkNewOrder($date, $page=1) {
        $method = 'biz.order.checkNewOrder.query';
        $params = ['date' => $date, 'page' => $page];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //根据日期查询妥投订单
    public function checkDlokOrder($date, $page=1) {
        $method = 'biz.order.checkDlokOrder.query';
        $params = ['date' => $date, 'page' => $page];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //根据日期查询拒收订单
    public function checkRefuseOrder($date, $page=1) {
        $method = 'biz.order.checkRefuseOrder.query';
        $params = ['date' => $date, 'page' => $page];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    public function balance() {
        $method = 'biz.price.balance.get';
        $params = ['payType' => 4];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }
}
