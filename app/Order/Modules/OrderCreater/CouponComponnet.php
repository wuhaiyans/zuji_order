<?php
/**
 *  优惠券组件创建器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Order\Modules\Repository\OrderCouponRepository;

class CouponComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;


    private $couponInfo=[];
    private $sku;


    public function __construct(OrderCreater $componnet, array $coupon=[])
    {
        $this->componnet = $componnet;
        if(!empty($coupon)){
            //获取优惠券类型接口
            $couponInfo=[
                0=>[
                    'coupon_id'=>"1",
                    'coupon_no'=>"1111111111",
                    'coupon_type'=>1,// 1,现金券 2,首月0租金
                    'discount_amount'=>200,
                    'coupon_name'=>"现金优惠券",
                    'is_use'=>0,//是否使用 0未使用
                ],
                1=>[
                    'coupon_id'=>"2",
                    'coupon_no'=>"22222222",
                    'coupon_type'=>2,// 1,现金券 2,首月0租金
                    'discount_amount'=>200,
                    'coupon_name'=>"首月0租金",
                    'is_use'=>0,//是否使用 0未使用
                ]
            ];
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
        $schema =$this->componnet->getDataSchema();
        $sku =$schema['sku'];

        //计算优惠券信息
        $this->componnet->getOrderCreater()->getSkuComponnet()->discrease_coupon($sku,$coupon);

        return $this->flag && $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        return $this->componnet->getDataSchema();
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        var_dump("Coupon组件 -create");
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        $data =$this->getOrderCreater()->getDataSchema();
        //无优惠券
        if(empty($data['coupon'])){
            return true;
        }
        $orderNo =$this->getOrderCreater()->getOrderNo();
        foreach ($data['coupon'] as $k=>$v){
            if($v['is_use'] ==1){
                $couponData =[
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
            }

        }

        /**
         * 调用优惠券使用接口
         */
        var_dump("别忘了调用优惠券使用接口");

        return true;

    }
}