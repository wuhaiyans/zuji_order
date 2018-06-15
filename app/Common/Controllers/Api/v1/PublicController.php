<?php

namespace App\Common\Controllers\Api\v1;

use App\Http\Controllers\Controller;

/**
 * sql
 */
class PublicController extends Controller
{

    public function __construct()
    {

    }

    public function sql(){
        $sql = 'select * from zuji_order2_instalment limit 0,1000';
        $result = [

        ];






    }


}
