<?php

namespace DongPHP\System\Model\Fzs;

use DongPHP\System\Model\AbstractModel;
use DongPHP\System\Libraries\Http\Curl;
use DongPHP\System\Data;

class AbstractFzsModel extends AbstractModel
{


    public function generateOrderId()
    {
        return sprintf('%s-%s-%s' , mt_rand(1000000 , 9999999) , time() , mt_rand(1000000 , 9999999));
    }
   
}
