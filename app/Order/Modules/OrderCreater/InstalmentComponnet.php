<?php
/**
 * 分期组件创建构造器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Order\Controllers\Api\v1\InstalmentController;
use App\Order\Models\OrderGoodsInstalment;
use App\Order\Modules\Inc\CouponStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\Order\Instalment;
use App\Order\Modules\Service\OrderInstalment;
use Mockery\Exception;

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
        return $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        $schema =$this->componnet->getDataSchema();
        foreach ($schema['sku'] as $k=>$sku){
            $skuInfo['zuqi_type'] = $sku['zuqi_type'];
            $skuInfo['discount_info'] = [
                [
                    'discount_amount' =>$sku['first_coupon_amount'],
                    'zuqi_policy' =>CouponStatus::CouponTypeFirst,// first：首月0租金；avg：优惠券优惠(订单优惠券固定金额) serialize:商品券
                ],
                [
                    'discount_amount' =>$sku['order_coupon_amount'],
                    'zuqi_policy' =>CouponStatus::CouponTypeAvg,
                ],
                [
                    'discount_amount' =>$sku['discount_amount'],
                    'zuqi_policy' =>CouponStatus::CouponTypeSerialize,
                ]
            ];

            $_data = [
         		'zujin'		    => $sku['zujin'],	    //【必选】price 每期租金
         		'zuqi'		    => $sku['zuqi'],	    //【必选】int 租期（必选保证大于0）
         		'insurance'    => $sku['insurance'],	//【必选】price 保险金额
            ];
            $instalment =$this->discountInstalment($_data,$skuInfo);
            $schema['sku'][$k]['instalment'] = $instalment;
        }
        return $schema;
    }

    public function discountInstalment($_data,$sku){
        try{
            // 月租，分期计算器
            if($sku['zuqi_type'] == 2){
                $computer = new \App\Order\Modules\Repository\Instalment\MonthComputer( $_data );
            }
            // 日租，分期计算器
            elseif($sku['zuqi_type'] == 1){
                $computer = new \App\Order\Modules\Repository\Instalment\DayComputer( $_data );
            }
            // 优惠策略
            foreach( $sku['discount_info'] as $dis_info ){
                // 分期策略：平均优惠
                if( $dis_info['zuqi_policy'] == 'avg' ){
                    $discounter_simple = new \App\Order\Modules\Repository\Instalment\Discounter\SimpleDiscounter( $dis_info['discount_amount'] );
                    $computer->addDiscounter( $discounter_simple );
                }

                // 分期策略：首月优惠（优惠金额为每期租金时，就是 首月0租金）
                elseif( $dis_info['zuqi_policy'] == 'first' ){
                    $discounter_first = new \App\Order\Modules\Repository\Instalment\Discounter\FirstDiscounter( $dis_info['discount_amount'] );
                    $computer->addDiscounter( $discounter_first );
                }
                // 分期策略：分期顺序优惠
                elseif( $dis_info['zuqi_policy'] == 'serialize' ){
                    $discounter_serialize = new \App\Order\Modules\Repository\Instalment\Discounter\SerializeDiscounter( $dis_info['discount_amount'] );
                    $computer->addDiscounter( $discounter_serialize );

                }
            }
            return $computer->compute();

        }catch( \Exception $exc ){
            throw new Exception("获取分期信息错误");
        }
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        $schema =$this->getDataSchema();
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        //支持分期支付方式
        $payType = [
            PayInc::WithhodingPay,
            PayInc::MiniAlipay,
        ];
        if(!in_array($this->payType,$payType)){
            return true;
        }
        foreach ($schema['sku'] as $key=>$sku){
            //循环插入到分期表
            for($i=0;$i<$sku['sku_num'];$i++) {
                foreach ($sku['instalment'] as $k => $v) {
                    $amount =$v['amount'];
                    if($v['term'] ==1){
                        $amount =$v['amount']+$sku['insurance'];
                    }

                    $instalmentData = [
                        'order_no' => $schema['order']['order_no'],
                        'goods_no' => $sku['goods_no'],
                        'user_id' => $schema['user']['user_id'],
                        'term' => $v['term'],
                        'day' => $v['day'],
                        'times' => $v['times'],
                        'original_amount' => $v['original_amount'],
                        'discount_amount' => $v['discount_amount'],
                        'amount' => $amount,
                        'status' => 1,
                    ];
                    $res = OrderGoodsInstalment::create($instalmentData);
                    $id = $res->getQueueableId();
                    if (!$id) {
                        $this->getOrderCreater()->setError('保存分期数据失败');
                        return false;
                    }
                }
            }
        }
        return true;
    }
}