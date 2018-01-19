<?php

namespace DongPHP\System\Model\Advert;

use DongPHP\System\Data;
use DongPHP\System\Libraries\DB;
use DongPHP\System\Model\AbstractModel;



class LauncherAdvertModel extends AbstractModel
{

    const M_LAUNCHER_ADVERT_KEY = 'xydb:launcher:advert:string';
    public function __construct()
    {
        $this->builder = DB::builder('xydb.xydb_launcher_advert');
        $this->memcache = Data::Memcache('xydb.advert');
    }

    public function getOneAdvertInfo() {
        $cacheData = $this->memcache->get(self::M_LAUNCHER_ADVERT_KEY);
        if(!$cacheData) {
            $result = [];
            $data =  $this->builder->where(['status'=>2])->orderBy('id', 'desc')->first();
            if($data) {
                $result = $data;
                $this->memcache->set(self::M_LAUNCHER_ADVERT_KEY, json_encode($data));
            }
        }else {
            $result = json_decode($cacheData, true);
        }

        return $result;
    }
}
