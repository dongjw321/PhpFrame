<?php
/**
 * this is part of xyfree
 *
 * @file OrderModel.php
 * @use  定单模块
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-08 14:40
 *
 */

namespace DongPHP\System\Model\Order;

use DongPHP\System\Libraries\DB;
use DongPHP\System\Libraries\Http\Curl;
use DongPHP\System\Libraries\TcpLog;
use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Libraries\String;
use DongPHP\System\Data;
use DongPHP\System\Model\User\UserGoldModel;
use DongPHP\System\Model\BuyModel;
use DongPHP\System\Model\CreditsModel;
use DongPHP\System\Model\BuyModelException;
use DongPHP\System\Model\Goods\GoodsPeriodModel;
use DongPHP\System\Model\User\UserCollectionModel;


class OrderModel extends AbstractModel
{
    private $id;
    const   ORDER_GOODS = 'orderGoods:';
    const   MEM_EQ_ORDER_GOODS = 'mem:eq:orderGoods:';
    const   M_USER_BUYRECORD = 'user:buyrecord';

    public function __construct($id)
    {
        $this->id = $id;
        $this->builder = DB::builder('xydb.sdk_order',$id);
    }


    public function setBaseEQInfo()
    {
        $baseEQInfo = [
            'clientversion'    => CLIENT_VERSION,
            'clientsysversion' => CLIENT_SYSVERSION,
            'equipment'        => CLIENT_EQUIPMENT,
            'channel'          => CLIENT_CHANNEL,
            'did'              => CLIENT_DID,
            'ip'               => CLIENT_IP,
            'devicetype'       => CLIENT_DEVICETYPE
        ];

        Data::memcache('xydb.order')->set(self::MEM_EQ_ORDER_GOODS . $this->id ,  $baseEQInfo , false, 1800);

    }


    //@notice 获取基础设备信息 用与日志传递
    public function getBaseEQInfo($orderId)
    {
        return Data::memcache('xydb.order')->get(self::MEM_EQ_ORDER_GOODS . $orderId);
    }



    /**
     * 生成订单
     * @param string $uid
     * @param string $price
     * @param string $payChannel
     * @param string $type
     * @param string $buyInfo
     * @return 
     * @throws \DongPHP\System\Libraries\DBException
     */

    public function create($uid, $price, $pay_channel, $type, $issueNo, $goodsId, $num)
    {
        $order['order_id']    = $this->id;
        $order['uid']         = $uid;
        $order['price']       = $price;
        $order['pay_channel'] = $pay_channel;
        $order['stat']        = 0;
        $order['create_time'] = time();
        $order['ip']          = String::getClientIp();
        $order['type']        = $type;
        $order['goods_info']  = json_encode(array('issueNo' =>$issueNo, 'goodsId' =>$goodsId, 'num' =>$num));



        $this->insert($order);
        return true;
    }


    /**
     * 清单生成
     * @param string $uid
     * @param string $price
     * @param string $payChannel
     * @param string $type
     * @param string $buyInfo
     * @return 
     * @throws \DongPHP\System\Libraries\DBException
     */
    public function createMuti($uid , $price , $pay_channel , $type , $buyInfo)
    {

        $order['order_id']    = $this->id;
        $order['uid']         = $uid;
        $order['price']       = $price;
        $order['pay_channel'] = $pay_channel;
        $order['stat']        = 0;
        $order['create_time'] = time();
        $order['ip']          = String::getClientIp();
        $order['type']        = $type;
        $order['goods_info']  = $buyInfo;

        $this->insert($order);
        return true;
    }


    public function get()
    {
        return $this->select(['order_id' => $this->id]);
    }

    public function pay($order_id)
    {
        return $this->update(['status' => 1, 'utime' => time()], ['id' => $order_id]);
    }


    public static function generateOrderId($uid, $issueNo, $goodsId)
    {
        $issueNo = $issueNo ? $issueNo : 0;
        $goodsId = $goodsId ? $goodsId : 0;
        return $issueNo . '-' . $goodsId . '-' . $uid . '-' . time() . '-' . mt_rand(10000, 99999);
    }


    public static function generateOrderIdMuti($uid, $prefix)
    {
        return mt_rand(100000,999999) . '-' . mt_rand(1000000,9999999). '-' . $uid . '-' . time() . '-' . mt_rand(10000, 99999);
    }



    /**
     * 修改订单状态和执行相关的操作
     */
    public function changeOrderStat($stat , &$tcplog='')
    {
        $orderId = $this->id;
        $tcplog['cos_start'] = '-------------';
        $orderInfo = $this->get($orderId);
        //订单已经是支付成功状态 不执行操作
        if ($orderInfo['stat'] == 2) {
            $tcplog['cos_result'] = 'fail';
            $tcplog['cos_reason'] = 'order  already has  paid';
            return false;
        }
        
        $tcplog['cos_result'] = $this->update(array('stat' => $stat, 'finish_time'=>time()), array('order_id' => $orderId)) ? 'success':'fail';

        //初始化 flag值
        $flag = 'normal';

        if($stat == 2){//如果是将订单状态改成支付成功 执行发货操作
            //给用户增加金币
            $tcplog['cos_goldNum'] = $goldNum = $orderInfo['price'];
            (new UserGoldModel($orderInfo['uid']))->add($goldNum, 1, ['orderId' => $orderInfo['order_id'],'uid'=> $orderInfo['uid']]);
            $tcplog['cos_addGold'] = 'success';
            //加积分
            $reditsModel = new CreditsModel($orderInfo['uid']);
            $tcplog['cos_creditsNum'] = $credits = $orderInfo['price'];
            $tcplog['cos_creditsOrderId'] = $reditsModel->operate(1, ['uid' => $orderInfo['uid'],'db_order_num' => $orderInfo['order_id'],'credits' => $credits]);
            //如果是购买订单 执行发货操作
            if ($orderInfo['type'] == 2) {//用户想要购买的商品
                $tcplog['cos_goodsInfo'] = $goodsInfo = json_decode($orderInfo['goods_info'], true);

                //判断层级
                $mutiBuyResult = [];

                if(isset($goodsInfo['issueNo'])) {
                    $tcplog['cos_buyInfo']   = $buyInfo   = self::sendGoods($goodsInfo['issueNo'] , $goodsInfo['goodsId'] , $orderInfo['uid'] , $goodsInfo['num'] , $tcplog , $orderId);
                    $mutiBuyResult = array_merge($goodsInfo , $buyInfo);
                    $flag = 'normal';
                }else{
                    foreach($goodsInfo as $buyUnit) {
                        $tcplog['cos_buyInfo'][] = $buyInfo = self::sendGoods($buyUnit['issueNo'] , $buyUnit['goodsId'] , $orderInfo['uid'] , $buyUnit['num'] , $tcplog , $orderId);
                        $mutiBuyResult[] = array_merge($buyUnit , $buyInfo);
                    }
                    $flag = 'muti';
                }

                $tcplog['cos_memcache']  = Data::memcache('xydb.order')->set(self::ORDER_GOODS . $orderId,  $mutiBuyResult , false, 1800);
            } else {
                $buyInfo['detail'] = '恭喜你获得'.intval($goldNum).'个夺宝币,'.intval($credits).'个优惠券.';
                Data::memcache('xydb.order')->set(self::ORDER_GOODS . $orderId, $buyInfo, false, 1800);
            }
            $tcplog['cos_end'] = '------------';
        }
        $paylog = array(
            'operate'       => 'update',
            'f_order_id'    => $orderId,
            'f_uid'         => $orderInfo['uid'],
            'f_price'       => $orderInfo['price'],
            'f_pay_channel' => $orderInfo['pay_channel'],
            'f_stat'        => $stat,
            'f_create_time' => $orderInfo['create_time'],
            'f_ip'          => $orderInfo['ip'],
            'f_type'        => $orderInfo['type'],
            'f_goods_info'  => json_decode($orderInfo['goods_info'] , true),
            'result'        => array('state'=>$tcplog['cos_result']?'success':'failed')
        );


        $baseEQInfo = $this->getBaseEQInfo($orderId);

        if($flag == 'normal') {
            TcpLog::record('sdk/paylog',$paylog);
        }else{
            TcpLog::record('sdk/mutipaylog',$paylog);
        }

        //@notice 添加基础日志信息
        TcpLog::setGlobalInfo(json_encode($baseEQInfo));

        return true;
    }

 
    public static function sendToDatacenter($ar_data,$userIp,$createTime){
        $debuglog = $ar_data;
        //$push_url = 'http://dev3.pay.xy.com/?action=manual/xyzs&resource_id=1259154';//测试url
        $push_url = 'http://pay3.xy.com/?action=manual/xyzs';//正式url
        $resource_id = '1259154';
        asort($ar_data, SORT_STRING);

        $ar_data['sign']                = md5('582df15de91b3f12d8e710073e43f4f8' . join('', $ar_data));

        $ar_data['resource_id']         = $resource_id;
        $ar_data['user_ip']             = $userIp;
        $ar_data['app_name']            = 'xy夺宝';
        $ar_data['xyzs_order_time']     = $createTime;
        $ar_data['xyzs_deviceid']       = 1;

        $post_string = '';

        foreach($ar_data as $key=>$val) {
            $post_string .= $key.'='.$val.'&';
        }
        $result = Curl::post($push_url,$post_string,array('MAXRETRIES'));

        if(! $result) {
             $result = Curl::post($push_url,$post_string,array('MAXRETRIES'));
        }

        if(! $result) {
            $result = Curl::post($push_url,$post_string,array('MAXRETRIES'));
        }



        $debuglog['result'] = $result;
        TcpLog::save('sdk/debug',json_encode($debuglog) , 'xydb.xyzs.com');
        return $result;
    }


    public static function sendGoods($issueNo, $goodsId, $uid, $num, &$tcplog = '', $orderId='')
    {
        $goodsPeriodModel = new GoodsPeriodModel();
        $period_info      = $goodsPeriodModel->getGoodsPeriod($issueNo);

        $buylog = array(
            'uid'     => $uid,
            'issueNo' => $issueNo,
            'goodsId' => $goodsId,
            'num'     => $num,
            'orderId' => $orderId,
        );
        $buyModel = new BuyModel();        
        try {
            $buyInfo = $buyModel->execute($goodsId, $issueNo, $uid, $num,$orderId , $period_info);
        } catch (BuyModelException $e) {//异常处理
              $tcplog['error'] = array(
                 'message' => $e->getMessage(),
                 'code' => $e->getCode(),
                 'line' => $e->getLine(),
                 'file' => $e->getFile()                 
             );
             $orderGoods['no'] = $orderGoods['createTime'] = '';
             $orderGoods['num'] = 0;
             $orderGoods['detail']  = $e->getMessage();
             $orderGoods['g_name'] = $period_info['g_name'];
             $buylog['cost'] = 0;
             $buylog['result']=array('state'=>'failed');
             $buylog['error'] = $tcplog['error'];
             $orderGoods['state'] = 'failed';
             TcpLog::record('sdk/buylog',$buylog);
             return $orderGoods;
        }

        //用户购买过的商品自动添加为收藏(老版本不自动添加)
        if(version_compare(CLIENT_VERSION, '1.1.6', '>=')) {
            $userCollectionModel = new UserCollectionModel($uid);
            $collection = $userCollectionModel->getUserCollection();
            if(!in_array($goodsId, $collection)) {
                $collection[] = $goodsId;

                //更新
                $collection = array_values($collection);
                $userCollectionModel->setUserCollection($collection);
            }
        }
        //结束自动添加收藏逻辑

        //清掉购买记录的mc缓存
        $key = sprintf('%s:%s:%s:%s', self::M_USER_BUYRECORD, $goodsId , $issueNo , $uid);
        if(Data::Memcache('xydb.user_account')->get($key)) {
            Data::Memcache('xydb.user_account')->delete($key);
        }
        //清掉购买记录的mc缓存 结束



     
        if ($buyInfo['num'] > 0) {
            //保存信息方便回调时显示
            $orderGoods['state'] = 'success';
            $orderGoods['detail'] = $period_info['g_name'];
            $orderGoods['g_name'] = $period_info['g_name'];
        } else {
            $orderGoods['detail'] = '商品不足购买失败';
            $orderGoods['state'] = 'failed';
            $orderGoods['g_name'] = $period_info['g_name'];
        }
        $buylog['cost'] = $orderGoods['num'] = $buyInfo['num'];
        $orderGoods['no'] = $buyInfo['codes'];
        $orderGoods['createTime'] = time();
        unset($buyInfo['codes']);//降低日志长度

        $wpPid = sprintf('%s-%s-%s-%s-%s-%s' ,$issueNo , $goodsId , $uid , $buyInfo['num'] , time() , mt_rand(10000 , 99999));

        $buyInfo['wp_pid'] = $wpPid;

        $buylog['result']= array_merge($buyInfo,array('state'=>'success'));

        TcpLog::save('sdk/buylog' , $buylog , 'xydb.xyzs.com');

        $centerData = array(
            'app_id'            => 19999,//临时设置id
            'uid'               => $uid,
            'sid'               => 1,
            'pay_rmb'           => $buyInfo['num'],
            'openuid'           => 1,
            'agentid'           => 1,
            'num'               => 1,
            'wp_pid'            => $wpPid,
            'qudao_id'          => 1,
            'merchantorder_id'  => 1
        );

       
        if(in_array(ENVIRONMENT ,['testing' , 'appstore' , 'production'])) {
           self::sendToDatacenter($centerData , '' , time());  
        }

        return $orderGoods;
    }

    /**
     * 获取充值订单对应的商品信息
     */
    public static function orderGoods($orderId)
    {
        return Data::memcache('xydb.order')->get(self::ORDER_GOODS . $orderId);
    }
}
