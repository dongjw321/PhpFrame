<?php
/**
 * Created by PhpStorm.
 * User: 杨洋<yangy1@kingnet.com>
 * Date: 2016/3/15
 * Time: 9:52
 */
namespace DongPHP\System\Libraries\ConfigHash;

class Hash{

    public static function hash($database, $table, $hash = null)
    {
        $table_alias = '';
        if ($hash) {
            switch ($table) {
                case "reply"://回复表
                    $table_suffix = substr(md5($hash), 0, 2);
                    $database     = 'e7gamer_reply';
                    $table_alias  = 'reply_' . $table_suffix;
                    break;
                case "user_block":    //用户关注版块列表
                case "user_message":  //用户消息列表
                case "user_relation": //用户关注表
                case "user_reply":    //用户回帖表
                case "user_topic":    //用户发贴表
                case "user_fans":     //用户粉丝表
                case "user_concerned"://用户关注表
                    $table_suffix = substr($hash, -2);
                    $database     = 'e7gamer_'.$table;
                    $table_alias  = $table . '_'. $table_suffix;
                    break;
                default:
                    break;
            }
        }
        return ['database' => $database, 'table_alias' => $table_alias, 'table' => $table];
    }
}