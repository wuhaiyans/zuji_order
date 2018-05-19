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
use App\Order\Modules\Repository\OrderGoodsRepository;
use Mockery\Exception;

class SkuComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;
    //租期类型
    private $zuqiType=1;
    private $zuqiTypeName;

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
            $this->zuqiType = $goodsArr[$skuId]['sku_info']['zuqi_type'];
            if ($this->zuqiType == 1) {
                $this->zuqiTypeName = "day";
            } elseif ($this->zuqiType == 2) {
                $this->zuqiTypeName = "month";
            }
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
        for ($i=0;$i<count($skuInfo);$i++){
            if($this->zuqiType ==2 && (count($skuInfo) >1 || $skuInfo[$i]['sku_num'] >1)){
                $this->getOrderCreater()->setError('不支持多商品添加');
                $this->flag = false;
            }
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

    public function getZuqiType(){
        return $this->zuqiType;
    }
    public function getZuqiTypeName(){
        return $this->zuqiTypeName;
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
            $arr['sku'][] = [
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
                    'zuqi_type_name' => $this->zuqiTypeName,
                    'buyout_price' => $skuInfo['market_price'] * 1.2-$skuInfo['shop_price'] * $skuInfo['zuqi'],
                    'market_price' => $skuInfo['market_price'],
                    'chengse' => intval($skuInfo['chengse']),
                    'contract_id' => $spuInfo['contract_id'],
                    'stock' => intval($skuInfo['number']),
                    'pay_type' => $this->payType,
                    'channel_id'=>intval($spuInfo['channel_id']),
                    'discount_amount' => $skuInfo['buyout_price'],
                    'amount'=>$skuInfo['shop_price']*intval($skuInfo['zuqi'])+$spuInfo['yiwaixian'],
                    'all_amount'=>$skuInfo['shop_price']*intval($skuInfo['zuqi'])+$spuInfo['yiwaixian'],
                    'coupon_amount' => 0.00,
                    'mianyajin' => 0.00,
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
        if (!$this->flag) {
            return false;
        }
        $data = $this->getDataSchema();
        var_dump($data);
        die;
        $goodsRepository = new OrderGoodsRepository();
        foreach ($data['sku'] as $k=>$v){
            for($i=0;$i<$v['sku_num'];$i++){
                $goodsData =[
                    'goods_name'=>$v['sku']['spu_name'],
                    'goods_id'=>$v['sku']['sku_id'],
                    'goods_no'=>$v['sku']['sku_no']."-".($i+1),
                    'prod_id'=>$v['sku']['spu_id'],
                    'prod_no'=>$v['sku']['spu_no'],
                    'brand_id'=>$v['sku']['brand_id'],
                    'category_id'=>$v['sku']['category_id'],
                    'user_id'=>$user_info['address']['user_id'],
                    'quantity'=>1,
                    'goods_yajin'=>$v['sku']['yajin'],
                    'yajin'=>$v['deposit']['yajin'],
                    'zuqi'=>$v['sku']['zuqi'],
                    'zuqi_type'=>$v['sku']['zuqi_type'],
                    'zujin'=>$v['sku']['zujin'],
                    'order_no'=>$data['order_no'],
                    'chengse'=>$v['sku']['chengse'],
                    'discount_amount'=>$v['sku']['discount_amount'],
                    'coupon_amount'=>$v['sku']['coupon_amount'],
                    'amount_after_discount'=>$v['sku']['zujin']*$v['sku']['zuqi']-$v['sku']['discount_amount']-$v['sku']['coupon_amount'],
                    'edition'=>$v['sku']['edition'],
                    'market_price'=>$v['sku']['market_price'],
                    'price'=>$v['sku']['amount'] + $v['deposit']['yajin'],
                    'specs'=>json_encode($v['sku']['specs']),
                    'insurance'=>$v['sku']['yiwaixian'],
                    'buyout_price'=>$v['sku']['buyout_price'],
                    'weight'=>$v['sku']['weight'],
                ];
                $goodsId =$goodsRepository->add($goodsData);
                if(!$goodsId){
                    $this->getOrderCreater()->setError("保存商品信息失败");
                    return false;
                }
            }
        }
        return true;

        foreach ($sku_info as $k =>$v){

                $goods_name .=$v['sku']['spu_name']." ";
                // 保存 商品信息
                $goods_data = [
                    'goods_name'=>$v['sku']['spu_name'],
                    'goods_id'=>$v['sku']['sku_id'],
                    'goods_no'=>$v['sku']['sku_no']."-".($i+1),
                    'prod_id'=>$v['sku']['spu_id'],
                    'prod_no'=>$v['sku']['spu_no'],
                    'brand_id'=>$v['sku']['brand_id'],
                    'category_id'=>$v['sku']['category_id'],
                    'user_id'=>$user_info['address']['user_id'],
                    'quantity'=>1,
                    'goods_yajin'=>$v['sku']['yajin'],
                    'yajin'=>$v['deposit']['yajin'],
                    'zuqi'=>$v['sku']['zuqi'],
                    'zuqi_type'=>$v['sku']['zuqi_type'],
                    'zujin'=>$v['sku']['zujin'],
                    'order_no'=>$data['order_no'],
                    'chengse'=>$v['sku']['chengse'],
                    'discount_amount'=>$v['sku']['discount_amount'],
                    'coupon_amount'=>$v['sku']['coupon_amount'],
                    'amount_after_discount'=>$v['sku']['zujin']*$v['sku']['zuqi']-$v['sku']['discount_amount']-$v['sku']['coupon_amount'],
                    'edition'=>$v['sku']['edition'],
                    'market_price'=>$v['sku']['market_price'],
                    'price'=>$v['sku']['amount'] + $v['deposit']['yajin'],
                    'specs'=>json_encode($v['sku']['specs']),
                    'insurance'=>$v['sku']['yiwaixian'],
                    'buyout_price'=>$v['sku']['buyout_price'],
                    'weight'=>$v['sku']['weight'],
                ];
                $order_amount +=$goods_data['amount_after_discount'];
                $goods_yajin  +=$goods_data['goods_yajin'];
                $order_yajin  +=$goods_data['yajin'];
                $order_insurance+=$goods_data['insurance'];
                $coupon_amount+=$goods_data['coupon_amount'];
                $discount_amount+=$goods_data['discount_amount'];


                $goods_id = $this->goods->insertGetId($goods_data);
                if(!$goods_id){
                    return ApiStatus::CODE_30005;
                }
//                $v['sku']['goods_no']=$v['sku']['sku_no']."-".++$i;
                // 生成分期
                $instalment_data =array_merge($v,['order'=>$data],$user_info);
                //var_dump($instalment_data);die;
//                $instalment = $this->instalment->create($instalment_data);
//                if(!$instalment){
//                    return ApiStatus::CODE_30005;
//                }

            }

        

        return true;
    }

}