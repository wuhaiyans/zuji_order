<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 10:50
 */

namespace App\Lib\Order;

/**
 * Class Delivery
 * 与收发货相关
 */
class Receive
{

    /**
     * @param $order_no
     * @param $data
     *
     * 收货系统 检测结果反馈
     */
    public static function checkResult($order_no, $data)
    {

        dd($data);

        $data = [
            [
                'sku_no' => '123',
                'imei' => 'abcde',
                'check_result' => 'success',//是否合格 fasle/success
                'check_description' => '原因',
                'check_price' => 200//金额
            ],
            [
                'sku_no' => '123',
                'imei' => 'abcde',
                'check_result' => 'success',//是否合格 fasle/success
                'check_description' => '原因',
                'check_price' => 200//金额
            ]
        ];

    }




}