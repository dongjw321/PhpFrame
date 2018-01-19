<?php
/**
 * 汇付宝各接口请求提交类
 */
use DongPHP\System\Libraries\Http\Curl;
require_once("heepay_core.function.php");

class HeePaySubmit {

    /**
     * 支付初始化，获取AccessToken
     * @return string
     */
    public static function getPerPay($ar_para) {
        $ar_params = array(
            'version'      => 1,
            'agent_id'     => HEEPAY_PARTNER,
            'agent_bill_id'=> $ar_para['orderid'],
            'agent_bill_time' => date('YmdHis'),
            'pay_type'     => 30,
            'pay_amt'      => $ar_para['amount'],
            'notify_url'   => HEEPAY_NOTIFY_URL,
            'user_ip'      => $ar_para['clientip'],
        );

        $sign = md5(createLinkstring($ar_params).'&key='.HEEPAY_PARTNER_KEY);

        $ar_params = array_merge($ar_params, array(
            'return_url'      => HEEPAY_RETURN_URL,
            'goods_name'      => $ar_para['title'],
            'goods_num'       => 1,
            'remark'          => 'xyzs',
            'goods_note'      => '',
            'sign'            => $sign
        ));
        $result = Curl::post(HEEPAY_TOKEN_URL, $ar_params);
        if(!$result) {
            return '';
        }

        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $result, $values, $index);
        xml_parser_free($parser);

        if(!isset($values[0]['tag']) || $values[0]['tag'] == 'ERROR' || !$values[0]['value']) {
            return '';
        }
 

        return $values[0]['value'];
    }

    /**
     * 签名校验
     * @param $ar_paras
     * @return bool
     */
    public static function verifySignature($ar_paras) {
        $respSignature = strtolower($ar_paras['sign']);

        $ar_signArray = array(
            'result'      => '',
            'agent_id'    => '',
            'jnet_bill_no'=> '',
            'agent_bill_id'=> '',
            'pay_type'     => '',
            'pay_amt'      => '',
            'remark'       => '',
        );
        foreach($ar_signArray as $key=>$val) {
            if(isset($ar_paras[$key]) && $ar_paras[$key]) {
                $ar_signArray[$key] = $ar_paras[$key];
            }
        }

        $reqstr        = createLinkstring($ar_signArray);
        $signature     = md5($reqstr.'&key='.HEEPAY_PARTNER_KEY);

        if ("" != $respSignature && $respSignature == $signature) {
            return true;
        } else {
            return false;
        }
    }
}
?>