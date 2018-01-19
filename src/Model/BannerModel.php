<?php
namespace DongPHP\System\Model;

use DongPHP\System\Libraries\DB;

class BannerModel extends AbstractModel
{
    public function __construct()
    {
        parent::__construct();
        $this->builder = DB::builder("xydb.xydb_banner");
    }
}