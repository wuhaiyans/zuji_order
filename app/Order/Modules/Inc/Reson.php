<?php

/**
 * 退换货问题
 * @access public 
 * 
 * 
 */

namespace App\Order\Modules\Inc;

class Reason {

    /***********退货/换货原因问题******************/
  
    public static function getReturnList(){
        return [
            'return'=> [
                '1'  => '额度不够',
                '2'  => '价格不划算',
                '3'  => '选错机型,重新下单',
                '4'  => '随便试试',
                '5'  => '不想租了',
                '6'  => '已经买了',
            ],
            'barter'=>[
                '1'  => '寄错手机型号',
                '2'  => '不想用了',
                '3'  => '收到时已经拆封',
                '4'  => '手机无法正常使用',
                '5'  => '未收到手机',
                '6'  => '换货',
            ]


        ] ;


        
    }

   
}
