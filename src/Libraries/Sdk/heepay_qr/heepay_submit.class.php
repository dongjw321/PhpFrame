<?php
/**
 * 汇付宝微信二维码支付
 */
class HeePaySubmit {
    function buildRequestForm($data) {
        $sign = $this->buildRequestSign($data);

        $sHtml = "<form id='frmSubmit' name='frmSubmit' action='".HEEPAY_PAY_URL."' method='post'>";
        while (list ($key, $val) = each ($data)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
        $sHtml = $sHtml."<input type='hidden' name='sign' value='".$sign."'/>";

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit'  value='ok' style='display:none;'></form>";

        $sHtml = $sHtml."<script>document.forms['frmSubmit'].submit();</script>";

        return $sHtml;
    }

    function buildRequestSign($data) {
        $sign_str = '';
        $sign_str  = $sign_str . 'version=' . $data['version'];
        $sign_str  = $sign_str . '&agent_id=' . HEEPAY_AGENT_ID;
        $sign_str  = $sign_str . '&agent_bill_id=' . $data['agent_bill_id'];
        $sign_str  = $sign_str . '&agent_bill_time=' . $data['agent_bill_time'];
        $sign_str  = $sign_str . '&pay_type=' . $data['pay_type'];
        $sign_str  = $sign_str . '&pay_amt=' . $data['pay_amt'];
        $sign_str  = $sign_str .  '&notify_url=' . HEEPAY_NOTIFY_URL;
        $sign_str  = $sign_str . '&return_url=' . HEEPAY_RETURN_URL;
        $sign_str  = $sign_str . '&user_ip=' . $data['user_ip'];
        $sign_str = $sign_str . '&key=' . HEEPAY_SIGN_KEY;

        return md5($sign_str);
    }

    function verifySignature($data) {
        $signStr='';
        $signStr  = $signStr . 'result=' . $data['result'];
        $signStr  = $signStr . '&agent_id=' . $data['agent_id'];
        $signStr  = $signStr . '&jnet_bill_no=' . $data['jnet_bill_no'];
        $signStr  = $signStr . '&agent_bill_id=' . $data['agent_bill_id'];
        $signStr  = $signStr . '&pay_type=' . $data['pay_type'];
        $signStr  = $signStr . '&pay_amt=' . $data['pay_amt'];
        $signStr  = $signStr .  '&remark=' . $data['remark'];

        $signStr = $signStr . '&key=' . HEEPAY_SIGN_KEY;

        return $data['sign'] == md5($signStr);
    }
}