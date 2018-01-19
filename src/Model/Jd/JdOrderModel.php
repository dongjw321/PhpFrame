<?php

namespace DongPHP\System\Model\Jd;

use DongPHP\System\Libraries\DB;
use DongPHP\System\Libraries\TcpLog;
use DongPHP\System\Model\Goods\GoodsJXModel;
use DongPHP\System\Model\Goods\GoodsPeriodModel;
use DongPHP\System\Model\User\UserAddressModel;

class JdOrderModel extends AbstractJdModel
{

    public function __construct()
    {
        parent::__construct();
        $this->builder = DB::builder('xydb.jd_order');
    }


    //创建下单
    public function creatOrder($uid, $addressId, $period) {
        $periodInfo = (new GoodsPeriodModel())->getGoodsPeriod($period);
        $goodsId = (new JdGoodsModel())->getJdMinPriceGoodsId($periodInfo['g_id']);
        $addressInfo = (new UserAddressModel($uid))->getOne($addressId);
        $invoiceInfo = (new JdGoodsModel())->checkGoods($goodsId);
        $provinces = (new JdAddressModel())->getJdProvince();
        $pId = isset($provinces[$addressInfo['province']]) ? $provinces[$addressInfo['province']] : 0;

        $city = (new JdAddressModel())->getJdCity($pId);
        $cityId = isset($city[$addressInfo['city']]) ? $city[$addressInfo['city']] : 0;

        $area = (new JdAddressModel())->getJdArea($cityId);
        $areaId = isset($area[$addressInfo['area']]) ? $area[$addressInfo['area']] : 0;

        $town = (new JdAddressModel())->getJdTown($areaId);
        $townId = isset($town[$addressInfo['town']]) ? $town[$addressInfo['town']] : 0;

        $method = 'biz.order.unite.submit';
        $params = [];
        $params['thirdOrder'] = $period; //第三方订单号
        $params['sku'] = [
            ['id'=>$goodsId, 'num'=>1]
        ]; //商品信息

        $params['name'] = $addressInfo['name']; //收货人姓名
        $params['province'] = $pId; //收货人一级地址
        $params['city'] = $cityId; //收货人二级地址
        $params['county'] = $areaId; //收货人三级地址
        $params['town'] = $townId;//收货人四级地址
        $params['address'] = $addressInfo['detail']; //收货人详细地址
        $params['zip'] = ''; //邮编
        $params['mobile'] = $addressInfo['phone']; //手机号
        $params['email'] = $uid . '@duobao.com'; //邮箱

        $params['invoiceState'] = 2; //开票方式(1为随货开票，0为订单预借，2为集中开票 )
        $params['invoiceType'] = isset($invoiceInfo[$goodsId]['isCanVAT']) && $invoiceInfo[$goodsId]['isCanVAT'] ? 2 : 1; //1普通发票2增值税发票
        $params['selectedInvoiceTitle'] = 5; //4个人，5单位
        $params['companyName'] = '浙江欢游网络科技有限公司'; //发票抬头  (如果selectedInvoiceTitle=5则此字段Y)
        $params['invoiceContent'] = 1; //1:明细，3：电脑配件，19:耗材，22：办公用品（备注:若增值发票则只能选1 明细）
        $params['paymentType'] = 4; //1：货到付款，2：邮局付款，4：在线支付（余额支付），5：公司转账，6：银行转账，7：网银钱包， 101：金采支付
        $params['isUseBalance'] = 1; //非预存款下单固定0 不使用余额
        $params['submitState'] = 1; //是否预占库存，0是预占库存（需要调用确认订单接口），1是不预占库存
        $params['invoiceName'] = '杨静超'; //增值票收票人姓名
        $params['invoicePhone'] = '13661534629'; //增值票收票人电话
        $params['invoiceProvice'] = '2'; //增值票收票人所在省(京东地址编码) 上海2
        $params['invoiceCity'] = '2825'; //增值票收票人所在市(京东地址编码) 闵行2825
        $params['invoiceCounty'] = '51934'; //增值票收票人所在区/县(京东地址编码) 浦江镇51934
        $params['invoiceAddress'] = '上海市闵行区陈行路2388号浦江科技广场3号楼3F'; //增值票收票人所在地址
        $url = $this->getRequestUrl($method, $params);

        $tcpLogData = [
            'uid' => $uid,
            'period' => $period,
            'order_params' => $params
        ];
        //记录日志
        TcpLog::record('xydb_jd_create_order', json_encode($tcpLogData));

        $result = $this->requestApi($url, [], 'get', 1, 1);
        $addData = [];
        if($result && !isset($result[0])) {
            //更改开奖记录表
            (new GoodsJXModel())->changeGoodsPeriodNeedJXStatus($period, $uid , GOODS_PERIOD_STATUS_CONFIRM_EXPRESS);

            //更改本期的状态
            DB::builder('xydb.xydb_goods_period')
                ->where(['period' => $period])
                ->update(['status' => GOODS_PERIOD_STATUS_CONFIRM_EXPRESS, 'utime' => time()]);


            $addData['status'] = GOODS_PERIOD_STATUS_CONFIRM_EXPRESS;
            $addData['jd_order_id'] = $result['jdOrderId'];
            $addData['price'] = $result['orderPrice'];
            $addData['naked_price'] = $result['orderNakedPrice'];
            $addData['tax_price'] = $result['orderTaxPrice'];
            $addData['freight'] = $result['freight'];
        }else {
            $addData['status'] = GOODS_PERIOD_STATUS_CONFIRM_ADDRESS;
            $addData['jd_order_id'] = 0;
            $addData['price'] = 0;
            $addData['naked_price'] = 0;
            $addData['tax_price'] = 0;
            $addData['freight'] = 0;
        }
        $addData['uid'] = $uid;
        $addData['period'] = $period;
        $addData['json_data'] = json_encode($params);

        return $this->addJdOrderData($addData);
    }

    public function cancelConfirmOrder($jdOrderId) {
        $url = 'https://bizapi.jd.com/api/order/cancel';
        $params = ['token'=>$this->token, 'jdOrderId'=>$jdOrderId];

        return $this->requestApi($url, json_encode($params), 'post');
    }

    public function cancelOrder($jdOrderId) {
        $method = 'biz.order.cancelorder';
        $params = ['jdOrderId' => $jdOrderId];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //获取订单详情
    public function getOrderInfo($jdOrderId){
        $method = 'biz.order.jdOrder.query';
        $params = ['jdOrderId' => $jdOrderId];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //根据第三方订单号获取jd订单号
    public function getOrderIdByThirdOrderId($thirdOrderId){
        $method = 'biz.order.jdOrderIDByThridOrderID.query';
        $params = ['thirdOrder' => $thirdOrderId];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    //支付
    public function doPay($jdOrderId) {
        $method = 'biz.order.doPay';
        $params = ['jdOrderId' => $jdOrderId];
        $url = $this->getRequestUrl($method, $params);

        return $this->requestApi($url);
    }

    public function addJdOrderData($data = []) {
        $data['utime'] = time();

        return $this->builder->insert($data);
    }

    public function getOrderListByStatus($status) {
        return $this->builder->where(['status'=>$status])->get();
    }

    public function updateJdOrder($where, $data) {
        return $this->builder->where($where)->update($data);
    }

    public function getMessage($type=10) {
        $method = 'biz.message.get';
        $params = ['type'=>$type];
        $url = $this->getRequestUrl($method, $params);

        return  $this->requestApi($url);
    }

    public function delMessage($messageId) {
        $method = 'biz.message.del';
        $params = ['id'=>$messageId];
        $url = $this->getRequestUrl($method, $params);

        return  $this->requestApi($url);
    }
}
