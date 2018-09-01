<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;



class ActivityDestineController extends Controller
{
    /***
     * 活动预定支付接口
     */
   public function destine(){

       $data = [
           'mobile'		=> "13654565804",
           'goods_name'	=> "火箭",
       ];
       $a = \App\Activity\Modules\Service\SendMessage::AdvanceSuccess($data);
       v($a);

       echo 'destine';die;
   }

}