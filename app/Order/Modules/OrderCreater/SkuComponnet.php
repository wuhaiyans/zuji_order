<?php
/**
 * 商品创建组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Goods\Goods;
use App\Order\Modules\Inc\PayInc;
use Mockery\Exception;

class SkuComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;
    //租期类型
    private $zuqiType=1;

    private $goodsArr;
    //支付方式
    private $payType;

    public function __construct(OrderCreater $componnet, array $sku,int $payType)
    {
        $this->componnet = $componnet;
        $goodsArr = Goods::getSku(config('tripartite.Interior_Goods_Request_data'),$sku);
        if (!is_array($goodsArr)) {
            throw new Exception("获取商品接口失败");
        }
        //商品数量付值到商品信息中
        for($i=0;$i<count($sku);$i++){
            $skuNum =$sku[$i]['sku_num'];
            $skuId =$sku[$i]['sku_id'];
            $goodsArr[$skuId]['sku_info']['sku_num'] = $skuNum;
        }
        $this->goodsArr =$goodsArr;
        $this->payType=$payType;
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
        //判断租期类型
        $skuInfo = array_column($this->goodsArr,'sku_info');
        //var_dump($skuInfo);die;
        for ($i=0;$i<count($skuInfo);$i++){
            if($skuInfo[$i]['zuqi_type'] ==2 && (count($skuInfo) >1 || $skuInfo[$i]['sku_num'] >1)){
                $this->getOrderCreater()->setError('不支持多商品添加');
                $this->flag = false;
            }
            $this->zuqiType = $skuInfo[$i]['zuqi_type'];
        }
        $arr =[];
        foreach ($this->goodsArr as $k=>$v){
            $skuInfo =$v['sku_info'];
            $spuInfo =$v['spu_info'];

            // 计算金额
            $amount = $skuInfo['zuqi']*$skuInfo['shop_price']+$spuInfo['yiwaixian'];
            if($amount <0){
                $this->getOrderCreater()->setError('商品金额错误');
                $this->flag = false;
            }
            // 库存量
            if($skuInfo['number']<$skuInfo['sku_num']){
                $this->getOrderCreater()->setError('商品库存不足');
                $this->flag = false;
            }
            // 商品上下架状态、
            if($skuInfo['status'] !=1){
                $this->getOrderCreater()->setError('商品已下架');
                $this->flag = false;
            }
            // 成色 100,99,90,80,70,60
            if( $skuInfo['chengse']<1 || $skuInfo['chengse']>100 ){
                $this->getOrderCreater()->setError('商品成色错误');
                $this->flag = false;
            }
            if( $this->zuqiType == 1 ){ // 天
                // 租期[1,12]之间的正整数
                if( $skuInfo['zuqi']<1 || $skuInfo['zuqi']>31 ){
                    $this->getOrderCreater()->setError('商品租期错误');
                    $this->flag = false;
                }
            }else{
                // 租期[1,12]之间的正整数
                if( $skuInfo['zuqi']<1 || $skuInfo['zuqi']>12 ){
                    $this->getOrderCreater()->setError('商品租期错误');
                    $this->flag = false;
                }
            }
            // 押金必须
            if( $skuInfo['yajin'] < 1 && $this->payType != PayInc::MiniAlipay){
                $this->getOrderCreater()->setError('商品押金错误');
                $this->flag = false;
            }
            // 格式化 规格
            $specs = [];
            foreach(json_decode($skuInfo['spec'],true) as $it){
                $specs[] = filter_array($it, [
                    'id' => 'required',
                    'name' => 'required',
                    'value' => 'required',
                ]);
            }
            $mustSpec = [1,4];
            $specId = array_column($specs, 'id');
            $specDiff = array_diff($mustSpec, $specId);
            if( count($specDiff)>0 ){
                $this->getOrderCreater()->setError('商品规格错误');
                $this->flag = false;
            }
        }

        return $this->flag;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        foreach ($this->goodsArr as $k=>$v) {
            $skuInfo = $v['sku_info'];
            $spuInfo = $v['spu_info'];
            if ($this->zuqiType == 1) {
                $zuqiTypeName = "day";
            } elseif ($this->zuqiType == 2) {
                $zuqiTypeName = "month";
            }
            $arr[] = [
                'sku' => [
                    'sku_id' => intval($skuInfo['sku_id']),
                    'spu_id' => intval($skuInfo['spu_id']),
                    'sku_name' => $skuInfo['sku_name'],
                    'spu_name' => $spuInfo['name'],
                    'sku_no' => $skuInfo['sn'],
                    'spu_no' => $spuInfo['sn'],
                    'weight' => $skuInfo['weight'],
                    'edition' => $skuInfo['edition'],
                    'sku_num' => intval($skuInfo['sku_num']),
                    'brand_id' => intval($spuInfo['brand_id']),
                    'category_id' => intval($spuInfo['catid']),
                    'specs' => $spuInfo['specs'],
                    'thumb' => $spuInfo['thumb'],
                    'yiwaixian' =>$spuInfo['yiwaixian'],
                    'yiwaixian_cost' => $spuInfo['yiwaixian_cost'],
                    'zujin' => $skuInfo['shop_price'],
                    'yajin' => $skuInfo['yajin'],
                    'zuqi' => intval($skuInfo['zuqi']),
                    'zuqi_type' => intval($skuInfo['zuqi_type']),
                    'zuqi_type_name' => $zuqiTypeName,
                    'buyout_price' => $skuInfo['market_price'] * 1.2-$skuInfo['shop_price'] * $skuInfo['zuqi'],
                    'market_price' => $skuInfo['market_price'],
                    'chengse' => intval($skuInfo['chengse']),
                    'contract_id' => $spuInfo['contract_id'],
                    'stock' => intval($skuInfo['number']),
                    'pay_type' => $this->payType,
                    'channel_id'=>intval($spuInfo['channel_id']),
                    'discount_amount' => 0.00,
                    'coupon_amount' => 0.00,
                    'mianyajin' => 0.00,
                ]
            ];
        }
        return $arr;
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        var_dump("sku组件 -create");
//        if( !$this->flag ){
//            return false;
//        }
//
//        // 订单ID
//        $order_id = $this->componnet->get_order_creater()->get_order_id();
//        //业务类型
//        $business_key = $this->componnet->get_order_creater()->get_business_key();
//
//        // 保存 商品信息
//        $goods_data = [
//            'order_id' => $order_id,
//            'sku_id' => $this->sku_id,
//            'spu_id' => $this->spu_id,
//            'sku_name' => $this->sku_name,
//            'brand_id' => $this->brand_id,
//            'category_id' => $this->category_id,
//            'specs' => \zuji\order\goods\Specifications::input_format($this->specs),
//            'zujin' => $this->zujin,
//            'yajin' => $this->yajin,
//            'mianyajin' => $this->mianyajin,
//            'yiwaixian' => $this->yiwaixian,
//            'yiwaixian_cost' => $this->yiwaixian_cost,
//            'zuqi' => $this->zuqi,
//            'zuqi_type' => $this->zuqi_type,
//            'chengse' => $this->chengse,
//            'create_time' => time(),
//        ];
//        $order2_goods = \hd_load::getInstance()->table('order2/order2_goods');
//        $goods_id = $order2_goods->add($goods_data);
//        if( !$goods_id ){
//            $this->get_order_creater()->set_error('[创建订单]商品保存失败');
//            return false;
//        }
//        $this->goods_id =$goods_id;
//        // 租机业务下单减少库存
//        if($business_key == Business::BUSINESS_ZUJI){
//            //sku库存 -1
//            $sku_table =\hd_load::getInstance()->table('goods2/goods_sku');
//            $spu_table=\hd_load::getInstance()->table('goods2/goods_spu');
//
//            $sku_data['sku_id'] =$this->sku_id;
//            $sku_data['number'] = ['exp','number-1'];
//            $add_sku =$sku_table->save($sku_data);
//            if(!$add_sku){
//                $this->get_order_creater()->set_error('[创建订单]商品库存减少失败');
//                return false;
//            }
//            $spu_data['id'] =$this->spu_id;
//            $spu_data['sku_total'] = ['exp','sku_total-1'];
//            $add_spu =$spu_table->save($spu_data);
//            if(!$add_spu){
//                $this->get_order_creater()->set_error('[创建订单]商品库存减少失败');
//                return false;
//            }
//        }
//
////		// 测试环境;
////		if( $_SERVER['ENVIRONMENT'] == 'test' ){
////			// 测试优惠价格 = 租期数
////			if( $this->discount_amount>0 ){
////				$this->discount_amount = $this->zuqi;
////			}else{
////				$this->discount_amount = 0;
////			}
////
////			// 测试意外险 1
////			$this->yiwaixian = 1;
////
////			// 测试 月租金 2分（支持每月优惠1分）
////			$this->zujin = 2;
////
////			// 测试 总金额
////			$this->all_amount = $this->zuqi*$this->zujin + $this->yiwaixian;
////
////			// 测试 待支付金额
////			$this->amount = $this->all_amount - $this->discount_amount;
////		}
//
//        // 保存订单商品信息
//        $data = [
//            'goods_id' => $goods_id,
//            'goods_name' => $this->spu_name,
//            'chengse' => $this->chengse,
//            'zuqi' => $this->zuqi,
//            'zuqi_type' => $this->zuqi_type,
//            'zujin' => $this->zujin,
//            'yajin' => $this->yajin,
//            'mianyajin' => $this->mianyajin,
//            'yiwaixian' => $this->yiwaixian,
//            'amount' => $this->amount,
//            'buyout_price' => $this->buyout_price,
//            'discount_amount' => $this->discount_amount,
//            'all_amount' => $this->all_amount,
//            'payment_type_id'=>$this->payment_type_id
//        ];
//        $order_table = \hd_load::getInstance()->table('order2/order2');
//        $b = $order_table->where(['order_id'=>$order_id])->save($data);
//        if( !$b ){
//            $this->get_order_creater()->set_error('[创建订单]更新订单商品信息失败');
//            return false;
//        }
        return true;
    }

    /**
     *
     * 增加订单金额
     * @param int $amount
     * @return \oms\order_creater\SkuComponnet
     */
    public function increase_amount(int $amount): SkuComponnet{
        if( $amount<0 ){
            return $this;
        }

        $this->amount += $amount;
        // 最终金额>=0
        if( $this->amount<0 ){
            $this->amount = 0;
        }
        return $this;
    }

    /**
     * 优惠金额
     * <p>如果优惠金额 大于 订单金额时，优惠金额值取总订单额进行优惠</p>
     * @param int $amount  金额值，单位：分；必须>=0
     * @return \oms\order_creater\SkuComponnet
     */
    public function discount(int $amount): SkuComponnet{
        if( $amount<0 ){
            return $this;
        }
        $price = $this->amount-$this->yiwaixian;
        // 优惠金额最多等于总金额
        if( $amount >= $price ){
            $amount = $price;
        }
        $this->amount -= $amount;// 更新总金额
        $this->discount_amount += $amount;// 更新优惠金额
        return $this;
    }

    /**
     * 免押
     * @param int $amount
     * @return \oms\order_creater\SkuComponnet
     */
    public function discrease_yajin(int $amount): SkuComponnet{
        if( $amount<0 ){
            return $this;
        }
        // 优惠金额 大于 总金额 时，总金额设置为0.01
        if( $amount >= $this->yajin ){
            $amount = $this->yajin;
        }
        $this->yajin -= $amount;// 更新押金
        $this->mianyajin += $amount;// 更新免押金额
        return $this;
    }
    /**
     * 全部免押
     * @return \oms\order_creater\SkuComponnet
     */
    public function mianyajin(): SkuComponnet{
        $this->mianyajin += $this->yajin;// 更新免押金额(一定要兼顾已经免押的金额，所以是 +=)
        $this->yajin = 0;// 更新押金
        return $this;
    }
}