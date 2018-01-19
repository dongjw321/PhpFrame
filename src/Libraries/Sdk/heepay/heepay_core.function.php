<?php
/**
 * 汇付宝接口公用函数
 * 详细：该类是请求、通知返回两个文件所调用的公用函数核心处理文件
 */

/**
 * 生成32位随机字符串
 * @return string
 */
function createNonceStr() {
    $orgstr = strval(mt_rand(0,10000));
    $orgstr = unpack("c*", md5($orgstr,true));

    $srcstr = '';
    $ar_hex = array("0", "1", "2", "3", "4", "5","6", "7", "8", "9", "a", "b", "c", "d", "e", "f" );

    for($i = 1; $i <= count($orgstr); $i++) {
        ($orgstr[$i] < 0) && $orgstr[$i] += 256;

        $hex1 = intval($orgstr[$i] / 16);
        $hex2 = intval($orgstr[$i] % 16);

        $srcstr .= $ar_hex[$hex1] . $ar_hex[$hex2];
    }

    return $srcstr;
}

/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstring($para) {
    $arg  = "";
    foreach($para as $key=>$val) {
        $arg.=$key."=".$val."&";
    }
    //去掉最后一个&字符
    $arg = substr($arg,0,count($arg)-2);

    //如果存在转义字符，那么去掉转义
    if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

    return $arg;
}
/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstringUrlencode($para) {
    $arg  = "";
    while (list ($key, $val) = each ($para)) {
        $arg.=$key."=".urlencode($val)."&";
    }
    //去掉最后一个&字符
    $arg = substr($arg,0,count($arg)-2);

    //如果存在转义字符，那么去掉转义
    if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

    return $arg;
}
/**
 * 除去数组中的空值和签名参数
 * @param $para 签名参数组
 * return 去掉空值与签名参数后的新签名参数组
 */
function paraFilter($para) {
    $para_filter = array();
    while (list ($key, $val) = each ($para)) {
        if($key == "sign" || $val == "")continue;
        else	$para_filter[$key] = $para[$key];
    }
    return $para_filter;
}
/**
 * 对数组排序
 * @param $para 排序前的数组
 * return 排序后的数组
 */
function argSort($para) {
    ksort($para);
    reset($para);
    return $para;
}
?>