<?php
/**
 * Created by PhpStorm.
 * User: 杨洋<yangy1@kingnet.com>
 * Date: 2016/3/15
 * Time: 9:52
 */
namespace DongPHP\System\Libraries\ConfigHash;

class PublicHash{

    public static function hash($database, $table, $hash = null)
    {
        $table_alias = '';
        if ($hash) {
            switch ($table) {
                case "t_xyapplist":
                    $db_suffix    = substr(md5($hash), 0, 1);
                    $table_suffix = substr(md5($hash), 0, 2);

                    $database = 'app_xyapplist_' . $db_suffix;
                    $table_alias = 't_xyapplist_' . $table_suffix;
                    break;
                default:
                    break;
            }
        }
        return ['database' => $database, 'table_alias' => $table_alias, 'table' => $table];
    }
}