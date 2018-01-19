<?php
/**
 * this is part of xyfree
 *
 * @file UserWinModel.php
 * @use 用户中奖记录
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2016-01-07 17:40
 *
 */

namespace DongPHP\System\Model\User;

use DongPHP\System\Data;
use DongPHP\System\Libraries\DB;
use DongPHP\System\Libraries\Sms;
use DongPHP\System\Model\Goods\GoodsModel;

class UserWinModel extends AbstractUserModel
{
    protected $db;

    const M_USER_WIN_NOTICE = 'user:win:notice:';

    public function __construct($uid)
    {
        parent::__construct($uid);
        $this->builder = DB::builder('xydb.user_win_record', $this->uid);
    }

    public function add($g_id, $period)
    {
        $goodsInfo = (new GoodsModel())->getGoodsInfoByGid($g_id);
        $noticeMessage = [
            'g_id' => $g_id ,
            'period' => $period ,
            'g_name' => $goodsInfo['g_name'],
            'exchange_price' => $goodsInfo['exchange_price'],
            'auto_recharge' => $goodsInfo['auto_recharge'],
            'goods_type' => $goodsInfo['goods_type']
        ];

        //添加用户中奖信息
        $this->addWinNotice($this->uid , $noticeMessage);
        //获取用户详细
        $userDetail = (new UserInfoModel($this->uid))->get();

        //@notice 通知中奖用户
        Sms::send($userDetail['phone'] , '恭喜您中奖了！您在“XY夺宝”中成功获得商品！请于7日内到XY夺宝个人中心确认收货地址，以便我们及时为您发货。', 'system');

        $record['uid']    = $this->uid;
        $record['g_id']   = $g_id;
        $record['period'] = $period;
        $record['atime']  = time();
        return $this->builder->insert($record);
    }


    public function addWinNotice($uid , $message)
    {

        $leaveMessage =  Data::Memcache('xydb.user_account')->get(self::M_USER_WIN_NOTICE . $uid);

        $leaveMessage = $leaveMessage ?: [];
        $leaveMessage[] = $message;
        
         Data::Memcache('xydb.user_account')->set(self::M_USER_WIN_NOTICE . $uid , $leaveMessage);
    
    }

    //@notice 获取用户中奖信息
    public function getWinNotice($uid) 
    {

        $leaveMessage =  Data::Memcache('xydb.user_account')->get(self::M_USER_WIN_NOTICE . $uid);
    
        if($leaveMessage) {
             Data::Memcache('xydb.user_account')->delete(self::M_USER_WIN_NOTICE . $uid);
        }

        return $leaveMessage;
    
    }



    public function getList($page=1, $size=30)
    {
        return $this->builder->where(['uid'=>$this->uid])->orderBy('id', 'desc')->pageInfo($page, $size);
    }

    //@notice 获取所有中奖纪录
    public function getListAll()
    {
        return $this->builder->where(['uid'=>$this->uid])->orderBy('atime', 'desc')->get();
    }
}
