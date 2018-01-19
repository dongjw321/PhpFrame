<?php


namespace DongPHP\System\Model\User;

use DongPHP\System\Data;
use DongPHP\System\Libraries\DB;
use DongPHP\System\Libraries\TcpLog;

class UserGoldModelException extends \Exception
{
}
class UserGoldModel extends AbstractUserModel
{
    const R_USER_GOLD_STR = 'user:gold:str';
    const R_USER_GOLD_HASH = 'user:gold:hash';

    protected $redis;

    protected $logModel;

    public function __construct($uid)
    {
        parent::__construct($uid);
        $this->redis            = Data::redis('xydb.xydb_user');
        $this->userGoldlogModel = new UserGoldLogModel($this->uid);
        $this->builder = DB::builder('xydb.user_gold_info', $this->uid);
    }


    public function add($num, $event=1, $params=[])
    {
        $remain = $this->redis->incrBy($this->getUserKey(), $num);

        //初始化用户金币hash数据
        $this->initGoldHash();

        $addGoldType = $event == 1 ? 'recharge' : 'reward'; //充值 或者 奖励

        //增加对应类型金币
        $this->redis->hIncrBy($this->getUserKeyHash(), $addGoldType , $num);

        //获取当前金币状态
        $nowGold = $this->redis->hGetAll($this->getUserKeyHash());

        $this->userGoldlogModel->add($num, $remain, $event, $params , $nowGold);

        $update = [];

        $update['total'] = $this->getUserGold();
        $update['detail'] = $nowGold;

        //更新
        $this->upUserGoldInfo($update);

        return true;
    }


    //初始化金币日志
    public function initGoldHash()
    {
        // recharge 充值
        // exchange 兑换
        // reward   奖励
    
        if(! $this->redis->exists($this->getUserKeyHash())) {
            $userGold = $this->getUserGold();
            $this->redis->hMset($this->getUserKeyHash() , ['recharge' => $userGold , 'exchange' => 0 , 'reward' => 0]);
        }
    }
  


    public function sub($num, $g_id, $period, $order_id)
    {
        if ($this->redis->get($this->getUserKey()) < $num) {
            throw new UserGoldModelException('金币不足');
        }

        $nowGold = $this->redis->hGetAll($this->getUserKeyHash());

        //$this->uid;

        /*

        if($nowGold['recharge'] >= $num) {
        
            //
        
        }elseif($nowGold['recharge'] + $nowGold['exchange'] >= $num) {
        
            //
        
        }elseif($nowGold['recharge'] + $nowGold['exchange'] + $nowGold['reward']) {
        
            //
        
        }else{
        
            //
        
        }


        */
        //$this->uid;

        $remain = $this->redis->decrBy($this->getUserKey(), $num);
        $this->userGoldlogModel->add($num, $remain, 2, ['g_id'=>$g_id, 'period'=>$period,'order_id'=>$order_id]);
        return true;
    }


    private function getUserKey()
    {
        return self::R_USER_GOLD_STR.':'.$this->uid;
    }

    private function getUserKeyHash()
    {
        return self::R_USER_GOLD_HASH . ':' . $this->uid;
    }
    
    public function getUserGold(){
        return $this->redis->get($this->getUserKey());
    }
    
    public function  gettestgold($uid){
       return $this->redis->get(self::R_USER_GOLD_STR.':'.$uid);
    }

    public function upUserGoldInfo($data) {
        if(!isset($data['utime'])) {
            $data['utime'] = time();
        }
        if(isset($data['detail']) && is_array($data['detail'])) {
            $data['detail'] = json_encode($data['detail']);
        }
        if($this->builder->where(['uid'=>$this->uid])->exists()) {
            return $this->builder->where(['uid'=>$this->uid])->update($data);
        }else {
            if(!isset($data['uid'])) {
                $data['uid'] = $this->uid;
            }
            return $this->builder->insert($data);
        }
    }
}
