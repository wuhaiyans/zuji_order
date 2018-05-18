<?php
/**
 *  优惠券组件创建器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


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
                    'coupon_no'=>"1111111111",
                    'coupon_type'=>1,// 1,现金券 2,首月0租金
                    'discount_amount'=>200,
                    'is_use'=>0,//是否使用 0未使用
                ],
                1=>[
                    'coupon_no'=>"22222222",
                    'coupon_type'=>2,// 1,现金券 2,首月0租金
                    'discount_amount'=>200,
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
        //计算总租金
        $totalAmount =0;
        foreach ($sku as $k=>$v){
            $totalAmount +=($v['zuqi']*$v['zujin']-$v['discount_amount'])*$v['sku_num'];
        }
        $zongyouhui=0;
        foreach ($sku as $k => $v) {
            for ($i =0;$i<$v['sku_num'];$i++){
                $youhui =0;
                foreach ($coupon as $key=>$val) {
                    //首月0租金
                    if ($val['coupon_type'] == 2 && $v['zuqi_type'] == 2) {
                        $zongzujin = ($v['zuqi'] - 1) * $v['zujin'];
                        $youhui+= $v['zujin'];
                        $sku[$k]['coupon_amount'] =$youhui;
                        $coupon[$key]['is_use'] = 1;
                    }
                    //现金券
                    if ($val['coupon_type'] == 1) {
                        $zongzujin = $v['zuqi'] * $v['zujin'] - $v['discount_amount'];
                        $sku[$k]['coupon_amount'] = round($val['discount_amount'] / $totalAmount * $zongzujin, 2);

                        if ($v['zuqi_type'] == 2) {
                            $sku[$k]['coupon_amount'] = $sku[$k]['coupon_amount']+$youhui;
                        } else {
                            if ($k == count($sku) - 1 && $i ==$v['sku_num']-1) {
                                $sku[$k]['coupon_amount'] = $val['discount_amount'] - $zongyouhui;
                            }else{
                                $zongyouhui += $sku[$k]['coupon_amount'];
                            }
                        }
                        $coupon[$key]['is_use'] = 1;
                    }
                }
            }
      }
        $this->couponInfo = $coupon;
        $this->sku =$sku;

        return $this->flag && $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        $schema =$this->componnet->getDataSchema();
        $schema['sku'] =$this->sku;
        $schema['coupon'] =$this->couponInfo;
        return $schema;
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        $this->componnet->create();
        var_dump("Coupon组件 -create");
        return true;
//        if( !$this->flag ){
//            return false;
//        }
//        $b = $this->componnet->create();
//        if( !$b ){
//            return false;
//        }
//        //无优惠券
//        if(!$this->coupon_no){
//            return true;
//        }
//        // 订单ID
//        $Creater = $this->get_order_creater();
//        $order_id = $Creater->get_order_id();
//
//        $order2_coupon_table = \hd_load::getInstance()->table('order2/order2_coupon');
//
//        $data =[
//            'order_id'=>$order_id,
//            'coupon_no'=>$this->coupon_no,
//            'coupon_id'=>$this->coupon_id,
//            'discount_amount'=>$this->discount_amount,
//            'coupon_type'=>$this->coupon_type,
//            'coupon_name'=>$this->coupon_name,
//        ];
//        $coupon_id = $order2_coupon_table->add($data);
//        if(!$coupon_id){
//            $this->get_order_creater()->set_error('保存订单优惠券信息失败');
//            return false;
//        }
//        $b = Coupon::set_coupon_status($this->coupon_id);
//        if(!$b){
//            $this->get_order_creater()->set_error('更新优惠券状态失败');
//            return false;
//        }
//        return true;
    }
}