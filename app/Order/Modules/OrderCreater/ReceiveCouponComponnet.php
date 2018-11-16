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

    private $only_id = "f3eac8c72f693f962b1cdf5543d8e2fb";

    private $coupon;
    private $status;
    private $num;
    private $recceive;


    public function __construct(OrderCreater $componnet, array $coupon=[],int $userId)
    {
        $this->componnet = $componnet;
        $schema =$this->componnet->getDataSchema();
        //有优惠券则直接返回
        if( !empty($coupon) ){
            $this->coupon = $coupon;
        }else{
            $appid =$this->componnet->getOrderCreater()->getAppid();
            //自动领取优惠券
            $drawCouponArr = [
                'only_id'=> $this->only_id,
                'user_id'=>$userId,
                'appid'  =>$appid,
            ];
            $this->recceive = $drawCouponArr;
            $this->status = \App\Lib\Coupon\Coupon::drawCoupon($drawCouponArr);
            $queryCouponArr = [
                'spu_id'=>$schema['sku'][0]['spu_id'],
                'sku_id'=>$schema['sku'][0]['sku_id'],
                'user_id'=>$userId,
                'payment'=>$schema['sku'][0]['zujin']*$schema['sku'][0]['zuqi'],
            ];
            $this->num = $queryCouponArr;
            $queryCoupon = \App\Lib\Coupon\Coupon::queryCoupon($queryCouponArr);
            if( isset($queryCoupon[0]['coupon_no']) ){//查询优惠券是否存在
                $coupon = [
                    $queryCoupon[0]['coupon_no']
                ];
            }
            $this->coupon = $coupon;
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
        $coupon['receive_coupon'] = [
            'coupon'=>$this->coupon,
            'receive'=>$this->recceive,
            'status'=>$this->status,
            'num'=>$this->num
        ];
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