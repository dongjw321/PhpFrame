<?php
namespace DongPHP\Model;

use DongPHP\System\Libraries\DB;
use DongPHP\System\Libraries\Input;

class FeedbackModel extends AbstractModel
{
    public function __construct()
    {

    }

    public function insert($data)
    {
        return DB::builder("xydb.db_feedback")->insert($data);
    }
}