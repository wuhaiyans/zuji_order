<?php
/**
 * 支付 服务
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace App\Order\Modules\Service;

/**
 * 支付 类
 * 定义 订单系统 支付阶段 标准业务接口
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class OrderPay
{
	
    public function __construct( )
    {

    }

    /**
     * 创建支付
     */
    public static function create( array $data )
    {
    }

    /**
     * 取消支付
     */
    public static function cancel( array $data )
    {
    }
	
}