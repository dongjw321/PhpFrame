<?php

namespace DongPHP\Model\Goods;

use DongPHP\System\Libraries\DB;
use DongPHP\System\Data;
use DongPHP\Model\AbstractModel;



class GoodsVertualCodeModel extends AbstractModel
{
    const R_VIRTUAL_CODE_KEY_STRING = 'xydb:virtual:code:string:';

    public function __construct()
    {
        $this->redis = Data::redis('xydb.goods');
        $this->builder = DB::builder('xydb.xydb_virtual_code');
    }

    public function getCodeInfoByGid($gid) {
        $data = $this->redis->rPop(self::R_VIRTUAL_CODE_KEY_STRING . $gid);
        return $data ? json_decode($data, true) : [];
    }

    public function upInfo($where, $info) {
        $this->builder->where($where)->update($info);
    }
}
