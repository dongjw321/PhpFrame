<?php
/**
 * 微信各接口请求提交类
 */

require_once("weixin_core.function.php");

class WeiXinSubmit {

    /**
     * 获取AccessToken
     * @return string
     */
    public static function getAccessToken() {
        $result = httpRequest(WEIXIN_TOKEN_URL,'grant_type='.WEIXIN_GRANT_TYPE.'&appid='.WEIXIN_APP_ID.'&secret='.WEIXIN_APP_SECRET,'get');

        if(!$result) {
            return '';
        }

        $result = json_decode($result,true);

        if(!isset($result['access_token'])) {
            return '';
        }

        return $result['access_token'];
    }

    /**
     * 获取预支付ID
     * @param $ar_para
     * @return bool
     */
    public static function getPerPay($ar_para) {
        if(empty($ar_para)) {
            return false;
        }

        $ar_data = array(
            'bank_type'        => 'WX',
            'body'             => $ar_para['title'],
            'notify_url'       => WEIXIN_NOTIFY_URL,
            'partner'          => WEIXIN_PARTNER,
            'out_trade_no'     => $ar_para['orderid'],
            'total_fee'        => $ar_para['amount'],
            'spbill_create_ip' => \DongPHP\System\Libraries\String::getClientIp(),
            'fee_type'         => 1,
            'input_charset'    => 'GBK',
            'attach'           => $ar_para['attach']
        );

        $ar_data = argSort($ar_data);
        $ar_data['sign'] = strtoupper(md5(createLinkstring($ar_data).'&key='.WEIXIN_PARTNER_KEY));

        $ar_data = argSort($ar_data);
        $package_value = createLinkstringUrlencode($ar_data);

        $ar_data = array(
            'appid'      => WEIXIN_APP_ID,
            'appkey'     => WEIXIN_APP_KEY,
            'noncestr'   => $ar_para['noncestr'],
            'package'    => $package_value,
            'timestamp'  => $ar_para['timestamp'],
            'traceid'    => ''
        );

        $ar_data = argSort($ar_data);

        $ar_data['app_signature'] = sha1(createLinkstring($ar_data));
        $ar_data['sign_method']   = WEIXIN_SIGN_METHOD;

        unset($ar_data['appkey'],$ar_data['traceid']);

        $ar_data = argSort($ar_data);

        $result = httpRequest(WEIXIN_GATE_URL.$ar_para['accessToken'],json_encode($ar_data));

        if(!$result) {
            return false;
        }

        $result = json_decode($result,true);

        if(!isset($result['prepayid'])) {
            return false;
        }

        return $result['prepayid'];
    }

    /**
     * 获取支付Body
     * @param $prepayid
     * @return array
     */
    public static function getPayBody($prepayid,$timestamp) {
        $createNonceStr = createNonceStr();
        $ar_data = array(
            'appid'      => WEIXIN_APP_ID,
            'appkey'     => WEIXIN_APP_KEY,
            'noncestr'   => $createNonceStr,
            'package'    => 'Sign=WXPay',
            'partnerid'  => WEIXIN_PARTNER,
            'prepayid'   => $prepayid,
            'timestamp'  => $timestamp,
        );

        $sign = sha1(createLinkstring($ar_data));

        $ar_data['sign'] = $sign;

        return $ar_data;
    }

    /**
     * 签名校验
     * @param $ar_paras
     * @return bool
     */
    public static function verifySignature($ar_paras) {
        $respSignature = strtolower($ar_paras['sign']);

        $reqstr        = createLinkstring(paraFilter($ar_paras));
        $signature     = md5($reqstr.'&key='.WEIXIN_PARTNER_KEY);

        if ("" != $respSignature && $respSignature == $signature) {
            return true;
        } else {
            return false;
        }
    }
}
?>