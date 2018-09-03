<?php
/**
 *  自动领取优惠券组件创建器
 * @access public (访问修饰符)
 * @author limin <limin@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;

use Mockery\Exception;

class ReceiveCouponComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;

    private $only_id = "383c8e805a3410e9ee03481d29a7f76f";

    private $coupon;


    public function __construct(OrderCreater $componnet, array $coupon=[],int $userId)
    {
        $this->componnet = $componnet;
        $schema =$this->componnet->getDataSchema();
        //有优惠券则直接返回
        if( $coupon ){
            $this->coupon = $coupon;
        }else{
            //自动领取优惠券
            $drawCouponArr = [
                'only_id'=> $this->only_id,
                'user_id'=>$userId,
            ];
            \App\Lib\Coupon\Coupon::drawCoupon($drawCouponArr);
            $queryCouponArr = [
                'spu_id'=>$schema['sku'][0]['spu_id'],
                'sku_id'=>$schema['sku'][0]['sku_id'],
                'user_id'=>$userId,
                'payment'=>$schema['sku'][0]['zujin']*$schema['sku'][0]['zuqi'],
            ];
            $queryCoupon = \App\Lib\Coupon\Coupon::queryCoupon($queryCouponArr);
            if( isset($queryCoupon[0]['coupon_no']) ){//查询优惠券是否存在
                $this->coupon = [
                    $queryCoupon[0]['coupon_no']
                ];
            }
        }

    }
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderComponnet
    {
        return $this->componnet->getOrderCreater();
    }
    /**
     * 过滤
     * <p>注意：</p>
     * <p>在过滤过程中，可以修改下单需要的元数据</p>
     * <p>组件之间的过滤操作互不影响</p>
     * <p>先执行内部组件的filter()，然后再执行组件本身的过滤</p>
     * @return bool
     */
    public function filter(): bool
    {
        $filter =  $this->componnet->filter();
        return $this->flag && $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        $schema =$this->componnet->getDataSchema();
        $coupon['receive_coupon'] = $this->coupon;
        return array_merge($schema,$coupon);
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {

        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        return true;
    }
}