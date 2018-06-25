<?php
/**
 *  优惠券组件创建器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\ApiStatus;
use App\Lib\Coupon\Coupon;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\OrderCouponRepository;
use Mockery\Exception;

class CouponComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;


    private $couponInfo=[];
    private $coupon;


    public function __construct(OrderCreater $componnet, array $coupon=[],int $userId)
    {
        $this->componnet = $componnet;
        if(!empty($coupon)){
            //获取优惠券类型接口
            for ($i=0;$i<count($coupon);$i++){
                if($coupon[$i]!=""){
                    $couponData[]=[
                        'user_id'=>$userId,
                        'coupon_no'=>$coupon[$i],
                    ];
                }
            }
           // var_dump($couponData);die;
            $coupon = Coupon::getCoupon($couponData);
            if(!is_array($coupon)){
                throw new Exception("优惠券信息错误");
            }
            if(empty($coupon)){
                throw new Exception("该优惠券已使用");
            }
            $couponInfo =[];
            foreach ($coupon as $key=>$value){
                foreach ($value as $k=>$v){
                    $couponInfo[]=[
                        'coupon_id'=>$v['coupon_id'],
                        'coupon_no'=>$v['coupon_no'],
                        'coupon_type'=>$v['coupon_type'],// 1,现金券 3,首月0租金
                        'discount_amount'=>$v['coupon_value']/100,
                        'coupon_name'=>$v['coupon_name'],
                        'is_use'=>0,//是否使用 0未使用
                    ];

                }
            }
            $this->couponInfo = $couponInfo;
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
        $coupon =$this->couponInfo;
        //无优惠券
        if(empty($coupon)){
            return $this->flag && $filter;
        }

        //计算优惠券信息
        $this->coupon =$this->componnet->getOrderCreater()->getSkuComponnet()->discrease_coupon($coupon);
        return $this->flag && $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        $schema =$this->componnet->getDataSchema();
        $coupon['coupon']=$this->coupon;
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
        $data =$this->getDataSchema();
        //无优惠券
        if(empty($data['coupon'])){
            return true;
        }
        $orderNo =$this->getOrderCreater()->getOrderNo();
        $coupon =[];
        foreach ($data['coupon'] as $k=>$v){
            if($v['is_use'] ==1){
                $couponData =[
                    'business_type'=>OrderStatus::BUSINESS_ZUJI,
                    'business_no'=>$orderNo,
                    'order_no'=>$orderNo,
                    'coupon_no'=>$v['coupon_no'],
                    'coupon_id'=>$v['coupon_id'],
                    'discount_amount'=>$v['discount_amount'],
                    'coupon_type'=>$v['coupon_type'],
                    'coupon_name'=>$v['coupon_name'],
                ];
                $couponId = OrderCouponRepository::add($couponData);
                if(!$couponId){
                    $this->getOrderCreater()->setError("保存订单优惠券信息失败");
                    return false;
                }
                $coupon[] =intval($v['coupon_id']);
            }

        }

        /**
         * 调用优惠券使用接口
         */
        $coupon = Coupon::useCoupon($coupon);
        if($coupon !=ApiStatus::CODE_0){
            $this->getOrderCreater()->setError("调用使用优惠券接口失败");
            return false;
        }

        return true;

    }
}