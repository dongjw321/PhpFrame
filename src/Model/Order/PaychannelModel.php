<?php
/**
 * 各充值渠道扣费层封装
 */
namespace DongPHP\System\Model\Order;

use DongPHP\System\Libraries\String;
use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Libraries\DB;

class PaychannelModel extends AbstractModel
{

    /**
     * 官方微信版
     */
    public static function weixin($orderid,$amount,$subject){
        include_once(SYS_PATH . "Config/Sdk/wxpay.config.php");
        include_once(SYS_PATH . "Libraries/Sdk/weixin/weixin_submit.class.php");

        $accessToken = \WeiXinSubmit::getAccessToken();


        $ar_data = array(
            'orderid'     => $orderid,
            'amount'      => $amount * 100,
            'title'       => iconv('utf-8','gbk',$subject),
            'attach'      => $orderid,
            'accessToken' => $accessToken,
            'noncestr'    => createNonceStr(),
            'timestamp'   => time()
        );

        $prepayid = \WeiXinSubmit::getPerPay($ar_data);

        $ret = \WeiXinSubmit::getPayBody($prepayid,$ar_data['timestamp']);

        return $ret;
    }

    /**********************************无线代扣start*******************************************/
    /**
     * 判断用户是否已经签约
     */
    public static function isSigned($uid){
        $result =  DB::builder('xydb.sdk_dk')
            ->select(['alipay_email'])
            ->where(['uid'=>$uid])
            ->first();
        return empty($result) ? '0':'1';
    }
    /**
     * 获取用户签约信息
     */
    public static function signedInfo($uid){
        DB::builder('xydb.sdk_dk')
            ->select(['*'])
            ->where(['uid'=>$uid])
            ->first();
    }

    /**
     * 无线代扣直接扣款
     *
     */
    public static function alipay_dk_pay($orderId,$price,$subject){
        include_once(SYS_PATH . "Config/Sdk/alipay_dk.config.php");
        include_once(SYS_PATH . "Libraries/Sdk/alipay_dk/alipay_submit.class.php");


        //动态ID类型
        $dynamic_id_type = $_POST['WIDdynamic_id_type'];
        //wave_code：声波，qr_code：二维码，bar_code：条码
        //动态ID
        $dynamic_id = $_POST['WIDdynamic_id'];
        //例如3856957008a73b7d
        //协议支付信息
        $agreement_info = $_POST['WIDagreement_info'];
        //商户代扣不可空，json格式


        /************************************************************/

//构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "alipay.acquire.createandpay",
            "partner" => trim($alipay_config['partner']),
            "seller_email"	=> $alipay_config['seller_email'],
            "out_trade_no"	=> $orderId,
            "subject"	=> $subject,
            "total_fee"	=> $price,
            "product_code"	=> 'GENERAL_WITHHOLDING_P',
            "dynamic_id_type"	=> '',
            "dynamic_id"	=> '',
            "agreement_info"	=> $agreement_info,
            "_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
        );

//建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestHttp($parameter);
//解析XML
//注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
        $doc = new DOMDocument();
        $doc->loadXML($html_text);

//请在这里加上商户的业务逻辑程序代码

//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

//获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

//解析XML
        if( ! empty($doc->getElementsByTagName( "alipay" )->item(0)->nodeValue) ) {
            $alipay = $doc->getElementsByTagName( "alipay" )->item(0)->nodeValue;
            return $alipay;
        }
        return false;
    }

    /**
     * 查询用户是否签约
     * @param $alipay_logon_id 用户的支付宝登陆账号
     * @return bool
     */
    public static function   alipay_dk($alipay_logon_id){
        include_once(SYS_PATH . "Config/Sdk/alipay_dk.config.php");
        include_once(SYS_PATH . "Libraries/Sdk/alipay_dk/alipay_submit.class.php");

        //产品码
        $product_code = 'GENERAL_WITHHOLDING_P';
        //必填，个人页面代扣 GENERAL_WITHHOLDING_P
        //场景
        $scene = 'INDUSTRY|APPSTORE';
        //必填，医疗请使用 INDUSTRY|MEDICAL
        //支付宝用户id
        $alipay_user_id = '';
        //服务窗appId
        $app_id = '';
        //如果alipay_user_id是openid，则appid不可空
        //外部签约号
        $external_sign_no = '';
        //可空
        //备注
        $memo = '';
        //可空
        /************************************************************/

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "alipay.dut.customer.agreement.query",
            "partner" => trim($alipay_config['partner']),
            "product_code"	=> $product_code,
            "scene"	=> $scene,
            "alipay_user_id"	=> $alipay_user_id,
            "alipay_logon_id"	=> $alipay_logon_id,
            "app_id"	=> $app_id,
            "external_sign_no"	=> $external_sign_no,
            "memo"	=> $memo,
            "_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
        );

        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestHttp($parameter);
        $doc = new \DOMDocument();
        $doc->loadXML($html_text);
        return empty($doc->getElementsByTagName( "userAgreementInfo" )->item(0)->nodeValue);

    }

    /***********************支付宝wap版***************************/


    /**
     * 支付宝wap版
     */
    public static function alipay($orderid, $price, $subject)
    {
        include_once(APP_PATH . "Config/Sdk/alipay.config.php");
        include_once(SYS_PATH . "Libraries/Sdk/alipay/alipay_submit.class.php");
        //返回格式
        $format = "xml";
        $v      = "2.0";

        //商户订单号
        $out_trade_no = $orderid;
        //商户网站订单系统中唯一订单号，必填
        //请求业务参数详细
        $req_data = '<direct_trade_create_req><notify_url>' . $alipay_config['notify_url'] . '</notify_url><call_back_url>' . $alipay_config['call_back_url'] . '</call_back_url><seller_account_name>' . $alipay_config['seller_email'] . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $price . '</total_fee><merchant_url>' . $alipay_config['merchant_url'] . '</merchant_url></direct_trade_create_req>';
        //构造要请求的参数数组，无需改动
        $para_token = array(
            "service"        => "alipay.wap.trade.create.direct",
            "partner"        => trim($alipay_config['partner']),
            "sec_id"         => trim($alipay_config['sign_type']),
            "format"         => $format,
            "v"              => $v,
            "req_id"         => $orderid,
            "req_data"       => $req_data,
            "_input_charset" => trim(strtolower($alipay_config['input_charset']))
        );
        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text    = $alipaySubmit->buildRequestHttp($para_token);

        //URLDECODE返回的信息
        $html_text = urldecode($html_text);
        //解析远程模拟提交后返回的信息
        $para_html_text = $alipaySubmit->parseResponse($html_text);

        //获取request_token
        $request_token = $para_html_text['request_token'];
        if (!$request_token) {
            return false;
        }


        /**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************/

        //业务详细
        $req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
        //必填

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service"        => "alipay.wap.auth.authAndExecute",
            "partner"        => trim($alipay_config['partner']),
            "sec_id"         => trim($alipay_config['sign_type']),
            "format"         => $format,
            "v"              => $v,
            "req_id"         => $orderid,
            "req_data"       => $req_data,
            "_input_charset" => trim(strtolower($alipay_config['input_charset']))
        );
        //建立请求
        $query_string = $alipaySubmit->buildRequestUrl($parameter);
        //echo "query_string ==> ";var_dump($query_string);echo "<br><br>";
        /*
                $html_text = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');
                echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html><head>	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">	<title>支付宝即时到账交易接口接口</title></head>';
                echo $html_text;
                echo "</body></html>";
                exit;
        */

        return $query_string;
    }

    /**
     * 支付宝pc版
     */
    public static function alipayQR($orderid, $price, $subject)
    {
        include_once(APP_PATH . "Config/Sdk/alipay.config.php");
        include_once(SYS_PATH . "Libraries/Sdk/alipay_qr/alipay_submit.class.php");

        $parameter = array(
            "service"       => $alipay_config['service'],
            "partner"       => $alipay_config['partner'],
            "seller_id"  => $alipay_config['seller_id'],
            "payment_type"  => $alipay_config['payment_type'],
            "notify_url"    => $alipay_config['notify_url'],
            "return_url"    => $alipay_config['return_url'],

            "anti_phishing_key"=>$alipay_config['anti_phishing_key'],
            "exter_invoke_ip"=>$alipay_config['exter_invoke_ip'],
            "out_trade_no"  => $orderid,
            "subject"   => $subject,
            "total_fee" => $price,
            "body"  => '',
            "_input_charset"    => trim(strtolower($alipay_config['input_charset']))
            //其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.kiX33I&treeId=62&articleId=103740&docType=1
            //如"参数名"=>"参数值"
        );

        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");

        return $html_text;
    }


    /**
     * 汇付宝支付
     */
    public static function heepay($orderid, $price, $subject)
    {
        include_once(SYS_PATH . "Config/Sdk/heepay.config.php");
        include_once(SYS_PATH . "Libraries/Sdk/heepay/heepay_submit.class.php");

        $ar_data = array(
            'orderid'  => $orderid,
            'amount'   => $price,
            'title'    => iconv('utf-8', 'gbk', $subject),
            'clientip' => String::getClientIp(),
        );

        $ret = \HeePaySubmit::getPerPay($ar_data);

        if (!$ret) {
            return false;
        }

        $ar_result = array('accesstoken' => $ret);

        return $ar_result;
    }


    /**
     * 网银支付
     */
    public static function unionpay($orderid, $price, $subject)
    {
        include_once(SYS_PATH . "Libraries/Sdk/unionpay/upmp_service.php");
        include_once(SYS_PATH . "Config/Sdk/upmp.config.php");
        //        if(!strpos(\upmp_config::$mer_back_end_url,_PAY_DOMAIN_CONFIG_)) {
        //            \upmp_config::$mer_back_end_url = str_replace('pay.xyzs.com',_PAY_DOMAIN_CONFIG_,\upmp_config::$mer_back_end_url);
        //        }
        //
        //        if(!strpos(\upmp_config::$mer_front_end_url,_PAY_DOMAIN_CONFIG_)) {
        //            \upmp_config::$mer_front_end_url = str_replace('pay.xyzs.com',_PAY_DOMAIN_CONFIG_,\upmp_config::$mer_front_end_url);
        //        }

        //需要填入的部分
        $req['version']          = \upmp_config::$version; // 版本号
        $req['charset']          = \upmp_config::$charset; // 字符编码
        $req['transType']        = "01"; // 交易类型
        $req['merId']            = \upmp_config::$mer_id; // 商户代码
        $req['backEndUrl']       = \upmp_config::$mer_back_end_url; // 通知URL
        $req['frontEndUrl']      = \upmp_config::$mer_front_end_url; // 前台通知URL(可选)
        $req['orderDescription'] = $subject;// 订单描述(可选)
        $req['orderTime']        = date("YmdHis"); // 交易开始日期时间yyyyMMddHHmmss
        $req['orderTimeout']     = ""; // 订单超时时间yyyyMMddHHmmss(可选)
        $req['orderNumber']      = $orderid ? str_replace('-', 'Y', $orderid) : date("YmdHiss"); //订单号(商户根据自己需要生成订单号)
        $req['orderAmount']      = $price * 100; // 订单金额
        $req['orderCurrency']    = "156"; // 交易币种(可选)
        $req['reqReserved']      = "透传信息"; // 请求方保留域(可选，用于透传商户信息)

        // 保留域填充方法
        $merReserved['test'] = "test";
        $req['merReserved']  = \UpmpService::buildReserved($merReserved); // 商户保留域(可选)

        $resp      = array();
        $validResp = \UpmpService::trade($req, $resp);

        // 商户的业务逻辑
        if ($validResp) {
            // 服务器应答签名验证成功
            return $resp;
        }

        return false;
    }

    public static function alipayNew($orderid, $price, $subject)
    {
        include_once(APP_PATH . "Config/Sdk/alipay_new/alipay.config.php");
        include_once(SYS_PATH. "Libraries/Sdk/alipay/alipay_submit.class.php");
        //返回格式
        $format = "xml";
        $v      = "2.0";

        //商户订单号
        $out_trade_no = $orderid;
        //商户网站订单系统中唯一订单号，必填
        //请求业务参数详细
        $req_data = '<direct_trade_create_req><notify_url>' . $alipay_config['notify_url'] . '</notify_url><call_back_url>' . $alipay_config['call_back_url'] . '</call_back_url><seller_account_name>' . $alipay_config['seller_email'] . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $price . '</total_fee><merchant_url>' . $alipay_config['merchant_url'] . '</merchant_url></direct_trade_create_req>';
        //构造要请求的参数数组，无需改动
        $para_token = array(
            "service"        => "alipay.wap.trade.create.direct",
            "partner"        => trim($alipay_config['partner']),
            "sec_id"         => trim($alipay_config['sign_type']),
            "format"         => $format,
            "v"              => $v,
            "req_id"         => $orderid,
            "req_data"       => $req_data,
            "_input_charset" => trim(strtolower($alipay_config['input_charset']))
        );
        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text    = $alipaySubmit->buildRequestHttp($para_token);


        //URLDECODE返回的信息
        $html_text = urldecode($html_text);
        //解析远程模拟提交后返回的信息
        $para_html_text = $alipaySubmit->parseResponse($html_text);


        //获取request_token
        $request_token = $para_html_text['request_token'];
        if (!$request_token) {
            return false;
        }


        /**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************/

        //业务详细
        $req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
        //必填

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service"        => "alipay.wap.auth.authAndExecute",
            "partner"        => trim($alipay_config['partner']),
            "sec_id"         => trim($alipay_config['sign_type']),
            "format"         => $format,
            "v"              => $v,
            "req_id"         => $orderid,
            "req_data"       => $req_data,
            "_input_charset" => trim(strtolower($alipay_config['input_charset']))
        );
        //建立请求
        $query_string = $alipaySubmit->buildRequestUrl($parameter);
        //echo "query_string ==> ";var_dump($query_string);echo "<br><br>";
        /*
                $html_text = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');
                echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html><head>	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">	<title>支付宝即时到账交易接口接口</title></head>';
                echo $html_text;
                echo "</body></html>";
                exit;
        */

        return $query_string;
    }
    /**
     * 爱贝支付
     */
  public static function iapppay($orderId, $price,$subject,$uid){
        include_once(APP_PATH . "Config/Sdk/iappay_config.php");
        include_once(SYS_PATH . "Libraries/Sdk/iappay/base.php");
        //下单接口
        $orderReq["appid"] = "3003627999";
        $orderReq["waresid"] = 1;
        $orderReq['waresname'] = $subject;
        $orderReq["cporderid"] = $orderId;
        $orderReq["price"] = floatval($price);   //单位：元
        $orderReq["currency"] = "RMB";
        $orderReq["appuserid"] = $uid;
        $orderReq["cpprivateinfo"] = "11qwer23q232111";//透传参数
        $orderReq["notifyurl"] = "http://".XYDB_URL."/sdk/pay/iappayNotify";
        //组装请求报文
        $reqData = composeReq($orderReq, $cpvkey);
        //发送到爱贝服务后台请求下单
        $respData = request_by_curl($orderUrl, $reqData, "xyzs");

        //验签数据并且解析返回报文
        if(!parseResp($respData, $platpkey, $respJson)) {
            return array('status'=>'fail','message'=>'报文解析失败');
        }
        if(!isset($respJson->transid)){
            return array('status'=>'fail','message'=>$respJson);
        }
        return array('status'=>'success','orderId'=>$respJson->transid);

  }

  /**
   * 爱贝h5 支付
   */
  public static function iapppayH5($orderId ){
        include(APP_PATH . "Config/Sdk/iappay_config.php");
        $orderReq["transid"] = "$orderId";
        $orderReq["redirecturl"] = "http://m.ixydb.com/pay/iappayCallback";
        $orderReq["cpurl"] = "http://www.xyzs.com";
        $reqData = composeReq($orderReq, $cpvkey);
        return $h5url.'?'.$reqData;
  }

  /**
   * 爱贝pc 支付
   */
  public static function iapppayPC($orderId ){
        include(APP_PATH . "Config/Sdk/iappay_config.php");
        $orderReq["transid"] = "$orderId";
        $orderReq["redirecturl"] = $call_back_url;
        $orderReq["cpurl"] = "http://www.xyzs.com";
        $reqData = composeReq($orderReq, $cpvkey);
        return $pcurl.'?'.$reqData;
  }

    public static function heepayQR($orderid, $price, $subject) {
        include(APP_PATH . "Config/Sdk/heepay_config.php");
        include(SYS_PATH. "Libraries/Sdk/heepay_qr/heepay_submit.class.php");

        $param = [
            'version'           => 1,
            'agent_id'          => HEEPAY_AGENT_ID,
            'agent_bill_id'     => $orderid,
            'agent_bill_time'   => date('YmdHis', time()),
            'pay_type'          => 30,
            'pay_code'          => '',
            'pay_amt'           => $price,
            'notify_url'        => HEEPAY_NOTIFY_URL,
            'return_url'        => HEEPAY_RETURN_URL,
            'user_ip'           => String::getClientIp(),
            'goods_name'        => urlencode($subject),
            'goods_num'         => 1,
            'goods_note'        => '',
            'remark'            => $orderid,
            'is_phone'          => 0,
            'is_frame'          => 0,
        ];
        $heepay_submit = new \HeePaySubmit();
        return $heepay_submit->buildRequestForm($param);
    }

}

?>