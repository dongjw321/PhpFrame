<?php

namespace DongPHP\Model;

use DongPHP\System\Libraries\DB;

class ToolbarModel extends AbstractModel
{
    public function __construct()
    {
        parent::__construct();
        $this->builder = DB::builder('xydb.xydb_toolbar');
    }

    public function getToolbarList()
    {
        $data = $this->builder->where(['status'=>2])->orderBy('rank', 'desc')->get();

        return $data ? $data : array();
    }
}
