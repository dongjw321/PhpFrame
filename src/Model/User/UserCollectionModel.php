<?php

namespace DongPHP\System\Model\User;

use DongPHP\System\Data;

class UserCollectionModel extends AbstractUserModel
{
    const R_USER_COLLECTION_STR = 'user:collection:str';

    protected $redis;

    public function __construct($uid)
    {
        parent::__construct($uid);
        $this->redis            = Data::redis('xydb.xydb_user');
        $this->userGoldlogModel = new UserGoldLogModel($this->uid);
    }

    private function getUserCollectionKey()
    {
        return self::R_USER_COLLECTION_STR.':'.$this->uid;
    }
    
    public function getUserCollection(){
        $collection = $this->redis->get($this->getUserCollectionKey());

        return $collection ? json_decode($collection, true) : array();
    }
    
    public function setUserCollection($collection){
        return $this->redis->set($this->getUserCollectionKey(), json_encode($collection));
    }
}
