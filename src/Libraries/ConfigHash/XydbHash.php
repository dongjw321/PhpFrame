<?php
namespace DongPHP\System\Libraries\ConfigHash;

/**
 * this is part of xyfree
 *
 * @file XydbHash.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-22 11:22
 *
 */
class XydbHash
{
    public static function hash($database, $table, $hash = null)
    {
        $table_alias = '';
        if ($hash) {
            switch ($table) {
                case "goods_buy_record":
                    list($g_id, $period) = explode(":", $hash);
                    $database    = 'xydb_goods_buy_record_' . substr($period, 0, 1) . ($g_id % 10);
                    $table_alias = 'goods_buy_record_' . substr($period, 1, 4);
                    break;
                case "user_base_info":
                    $database    = 'xydb_user_base_info';
                    $table_alias = 'user_base_info_' . ($hash % 100);
                    break;
                case "user_address":
                    $database    = 'xydb_user_address';
                    $table_alias = 'user_address_' . ($hash % 100);
                    break;
                case "user_login":
                    $mdKey       = md5($hash);
                    $database    = 'xydb_user_login';
                    $table_alias = 'user_login_' . substr($mdKey, 0, 2);
                    break;
                case "user_gold_log":
                    $database    = 'xydb_user_gold_log';
                    $table_alias = 'user_gold_log_' . substr($hash ,-2);
                    break;
                case "user_join_record":
                    $database    = 'xydb_user_join_record';
                    $table_alias = 'user_join_record_' . substr($hash ,-2);
                    break;
                case "user_win_record":
                    $database    = 'xydb_user_win_record';
                    $table_alias = 'user_win_record_' . substr($hash ,-2);
                    break;
                case "user_credits_record":
                    $database    = 'xydb_credits_record';
                    $table_alias = 'user_credits_record_' . ($hash % 100);
                    break;
                case "sdk_order":
                    $tmpArr      = explode('-', $hash);//获取订单内的时间戳
                    $year        = date('Y', $tmpArr[3]);
                    $m_d         = date('m_d', $tmpArr[3]);
                    $database    = 'xydb_sdk_' . $year;
                    $table_alias = 'sdk_order_' . $m_d;
                    break;
                default:
                    break;
            }
        }
        return ['database' => $database, 'table_alias' => $table_alias, 'table' => $table];
    }
}
