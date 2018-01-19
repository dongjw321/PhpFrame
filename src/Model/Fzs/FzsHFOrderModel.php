<?php

namespace DongPHP\System\Model\Fzs;

use DongPHP\System\Libraries\DB;
use DongPHP\System\Model\Goods\GoodsJXModel;
use DongPHP\System\Model\Goods\GoodsPeriodModel;
use DongPHP\System\Model\User\UserAddressModel;
use DongPHP\System\Libraries\Http\Curl;

use DongPHP\System\Model\Fzs\AbstractFzsModel;


class FzsHFOrderModel extends AbstractFzsModel 
{

    public function __construct()
    {
        parent::__construct();
        $this->builder = DB::builder('xydb.fzs_tariffe_order');
    }

    
    //@notice 生成检验信息
    public function createAuthStr($params = [])
    {

        $rawParams = [
            'channelNo'       => 100168,  //渠道
            'userpws'         => md5('@13CD2qw'),  //用户密码
            'phone'           => $params['phone'],  //用户手机
            'tariffeValue'    => $params['tariffeValue'],  //用户手机
            'isLarge'         => ($params['tariffeValue'] >= 100) ? 0 : 1,  //是否为大额
            'orderId'         => $params['orderId'],
            'txnDate'         => date('Ymd' , time()),
            'version'         => '1.0',
        ];

        $userId = 'flow_xhtx';


    //包体= channelNo + MD5(userpws) + phone+ tariffeValue + isLarge + orderId + txnDate+ version 

        $authStr              = (md5(implode('' , $rawParams) . $userId));

        $rawParams['md5Str']  = $authStr;
        $rawParams['version'] = '1.0';
        $rawParams['retUrl']  = "http://www.ixydb.com/fzs/fzscallback/tariffe";
        $rawParams['g_id']    = $params['g_id'];
        $rawParams['period']  = $params['period'];
        $rawParams['uid']     = $params['uid'];

        //插入订单
        $this->insert($rawParams);

        return $rawParams;
    
    }


    //@notice 发送流量充值请求
    public function sendTariffeRequest($params = [])
    {
        $url = sprintf('%s%s' , 'http://tariffe.tx.phone580.com/fzsTariffe/api/external/tariffeOrderApi?' , http_build_query($params));
        return Curl::get($url);
    }


    //生成订单
    public function createFlowOrder()
    {
        return  mt_rand(10000000,99999999). '-' . time() . '-' . mt_rand(10000000, 99999999);
    }


    //插入订单数据
    public function insert($data = [])
    {
        $insert = [];

        $insert['order_id']     = $data['orderId'];
        $insert['period']       = $data['period'];
        $insert['g_id']         = $data['g_id'];
        $insert['phone']        = $data['phone'];
        $insert['uid']          = $data['uid'];
        $insert['tariffevalue'] = $data['tariffeValue'];
        $insert['txnDate']      = $data['txnDate'];
        $insert['atime']        = time();
        $insert['version']      = '1.0';
        $insert['order_status'] = 1;

        return $this->builder->insert($insert);
    }


    //更新蜂助手订单回调状态
    public function updateOrderStatus($orderid = '')
    {
        $result = $this->builder->where(['order_id' => $orderid])->first();
        if($result) {
            $this->builder->where(['order_id' => $orderid])->update(['order_status' => 2 , 'utime' => time()]);
            return true;
        }else{
            return false;
        }
    
    }


    public function getOrderStatus($orderid = '') 
    {
        return $this->builder->where(['order_id' => $orderid])->first();
    }


    public function changeOrderStatus($uid = '' , $period = '')
    {

        if(! $uid OR ! $period) {
            return false;
        }
    
        //修改订单状态
        (new GoodsJXModel())->changeGoodsPeriodNeedJXStatus($period, $uid , GOODS_PERIOD_STATUS_CONFIRM_RECEIPT);

        //更改本期的状态
        DB::builder('xydb.xydb_goods_period')->where(['period' => $period])->update(['status' => GOODS_PERIOD_STATUS_CONFIRM_RECEIPT]);

        $recordLog = [];

        $recordLog['uid'] = $uid;
        $recordLog['period'] = $period;
        $recordLog['status'] = GOODS_PERIOD_STATUS_CONFIRM_RECEIPT;

        TcpLog::record('fzs/tariffe_callback_change_order',$recordLog);

        return true;

    }
 
}
