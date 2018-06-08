<?php
/**
 * 分期组件创建构造器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Order\Controllers\Api\v1\InstalmentController;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\Order\Instalment;
use App\Order\Modules\Service\OrderInstalment;

class InstalmentComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;
    private $payType;


    public function __construct(OrderCreater $componnet,int $payType)
    {
        $this->componnet = $componnet;
        $this->payType =$payType;
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
        //统一过滤
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
//        $instalment = new Instalment();
//        $instalmentData = $instalment->instalmentData($schema);
//        var_dump($instalmentData);die;
//        $instalmentData=[
//            'order'=>[
//                'order_no'=>'',
//            ],
//            'sku'=>[
//	           'zuqi'              => 1,//租期
//	           'zuqi_type'         => 1,//租期类型
//	           'all_amount'        => 1,//总金额
//	           'amount'            => 1,//实际支付金额
//	           'yiwaixian'         => 1,//意外险
//	           'zujin'             => 1,//租金
//	           'pay_type'          => 1,//支付类型
//	       ],
//            'coupon'=>[ 			  // 非必须
//	           'discount_amount'   => 1,//优惠金额
//	           'coupon_type'       => 1,//优惠券类型
//	       ],
//	      'user'=>[
//	           'user_id'           => 1,//用户ID
//	        ],
//        ];
//

        return array_merge($schema,['instalment'=>""]);
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        $schema =$this->componnet->getDataSchema();
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
//        $instalment = OrderInstalment::create($schema);
//        if (!$instalment) {
//            return false;
//        }
        return true;
    }
}