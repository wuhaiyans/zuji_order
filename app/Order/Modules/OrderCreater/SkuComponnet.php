<?php
/**
 * 商品创建组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Goods\Goods;
use App\Order\Modules\Inc\CouponStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Inc\Specifications;
use App\Order\Modules\Repository\Order\DeliveryDetail;
use App\Order\Modules\Repository\Order\ServicePeriod;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use Mockery\Exception;

/**
 * SKU 组件
 * 处理订单中商品的基本信息
 */
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
    private $deposit=[];
    private $couponInfo=[];
    private $sku=[];

    //规格
    private $specs;

    //总押金
    private $orderYajin=0;
    private $orderZujin=0;
    private $orderFenqi=0;


	/**
	 * 
	 * @param \App\Order\Modules\OrderCreater\OrderCreater $componnet
	 * @param array $sku
	 * [
	 *		'sku_id' => '',		//【必选】SKU ID
	 *		'sku_num' => '',	//【必选】SKU 数量
	 * ]
	 * @param int $payType  //创建订单才有支付方式
	 * @throws Exception
	 */
    public function __construct(OrderCreater $componnet, array $sku,int $payType =0)
    {
        $this->componnet = $componnet;
        $goodsArr = Goods::getSkuList( array_column($sku, 'sku_id') );
        if (!is_array($goodsArr)) {
            throw new Exception("获取商品接口失败");
        }

        //商品数量付值到商品信息中
        for($i=0;$i<count($sku);$i++){
            $skuNum =$sku[$i]['sku_num'];
            $skuId =$sku[$i]['sku_id'];
            if(empty($goodsArr[$skuId]['spu_info']['payment_list'])){
                throw new Exception("商品支付方式错误");
            }
            $this->payType =$payType?$payType:$goodsArr[$skuId]['spu_info']['payment_list'][0]['id'];
            $this->zuqiType = $goodsArr[$skuId]['sku_info']['zuqi_type'];
            $goodsArr[$skuId]['sku_info']['begin_time'] =isset($sku[$i]['begin_time'])&&$this->zuqiType == 1?$sku[$i]['begin_time']:"";
            $goodsArr[$skuId]['sku_info']['end_time'] =isset($sku[$i]['end_time'])&&$this->zuqiType == 1?$sku[$i]['end_time']:"";
            $goodsArr[$skuId]['sku_info']['sku_num'] = $skuNum;
            $goodsArr[$skuId]['sku_info']['goods_no'] = createNo(6);

            if ($this->zuqiType == 1) {
                $this->zuqiTypeName = "day";
                $goodsArr[$skuId]['sku_info']['zuqi'] = ((strtotime($goodsArr[$skuId]['sku_info']['end_time']) -strtotime($goodsArr[$skuId]['sku_info']['begin_time']))/86400)+1;
            } elseif ($this->zuqiType == 2) {
                $this->zuqiTypeName = "month";
            }
            $spec = json_decode($goodsArr[$skuId]['sku_info']['spec'],true);
            // 格式化 规格
            $_specs = [];
            foreach($spec as $it){
                //不存储租期
                if($it['id'] !=4){
                    $_specs[] = filter_array($it, [
                        'id' => 'required',
                        'name' => 'required',
                        'value' => 'required',
                    ]);
                }
            }
            $this->specs = $_specs;

        }
        $this->goodsArr =$goodsArr;
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

            //计算短租租期
            if($this->zuqiType ==1){
                if(strtotime($skuInfo['end_time'])-strtotime($skuInfo['begin_time'])<86400*2){
                    $this->getOrderCreater()->setError('短租时间错误');
                    $this->flag = false;
                }
            }

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
            if( $skuInfo['yajin'] < 0.01 && $this->payType != PayInc::MiniAlipay){
                $this->getOrderCreater()->setError('商品押金错误');
                $this->flag = false;
            }

        }

        return $this->flag;
    }

    /**
     * 获取支付方式
     * @return int
     */
    public function getPayType(){
        return $this->payType;
    }
    /**
     * 获取租期类型
     * @return int
     */
    public function getZuqiType(){
        return $this->zuqiType;
    }
    public function getZuqiTypeName(){
        return $this->zuqiTypeName;
    }
    public function getOrderYajin(){
        return $this->orderYajin;
    }
    public function getOrderZujin(){
        return $this->orderZujin;
    }
    public function getOrderFenqi(){
        return $this->orderFenqi;
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
            $first_coupon_amount =!empty($this->sku[$skuInfo['sku_id']]['first_coupon_amount'])?normalizeNum($this->sku[$skuInfo['sku_id']]['first_coupon_amount']):0.00;
            $order_coupon_amount =!empty($this->sku[$skuInfo['sku_id']]['order_coupon_amount'])?normalizeNum($this->sku[$skuInfo['sku_id']]['order_coupon_amount']):0.00;
            $specs =json_decode($spuInfo['specs'],true);
            $deposit_yajin =!empty($this->deposit[$skuInfo['sku_id']]['deposit_yajin'])?$this->deposit[$skuInfo['sku_id']]['deposit_yajin']:$skuInfo['yajin'];
            $this->orderYajin =normalizeNum($deposit_yajin);
            $amount_after_discount =normalizeNum($skuInfo['shop_price']*$skuInfo['zuqi']-$skuInfo['buyout_price']-$first_coupon_amount-$order_coupon_amount);
            if($amount_after_discount <0){
                $amount_after_discount =0.00;
            }

            $this->orderZujin =normalizeNum($amount_after_discount+$spuInfo['yiwaixian']);
            $this->orderFenqi =intval($skuInfo['zuqi_type']) ==1?1:intval($skuInfo['zuqi']);

            $arr['sku'][] = [
                    'sku_id' => intval($skuInfo['sku_id']),
                    'spu_id' => intval($skuInfo['spu_id']),
                    'sku_name' => $skuInfo['sku_name'],
                    'spu_name' => $spuInfo['name'],
                    'sku_no' => $skuInfo['sn'],
                    'spu_no' => $spuInfo['sn'],
                    'goods_no'=>$skuInfo['goods_no'],
                    'weight' => $skuInfo['weight'],
                    'edition' => $skuInfo['edition'],
                    'sku_num' => intval($skuInfo['sku_num']),
                    'brand_id' => intval($spuInfo['brand_id']),
                    'category_id' => intval($spuInfo['catid']),
                    'machine_id' => intval($spuInfo['machine_id']),
                    'specs' => $this->specs,
                    'thumb' => $spuInfo['thumb'],
                    'insurance' =>$spuInfo['yiwaixian'],
                    'insurance_cost' => $spuInfo['yiwaixian_cost'],
                    'zujin' => $skuInfo['shop_price'],
                    'yajin' => normalizeNum($skuInfo['yajin']),
                    'zuqi' => intval($skuInfo['zuqi']),
                    'zuqi_type' => intval($skuInfo['zuqi_type']),
                    'zuqi_type_name' => $this->zuqiTypeName,
                    'buyout_price' => normalizeNum($skuInfo['market_price'] * 1.2-$skuInfo['shop_price'] * $skuInfo['zuqi']),
                    'market_price' => normalizeNum($skuInfo['market_price']),
                    'machine_value' => isset($spuInfo['machine_name'])?$spuInfo['machine_name']:"",
                    'chengse' => $skuInfo['chengse'],
                    'stock' => intval($skuInfo['number']),
                    'pay_type' => $this->payType,
                    'channel_id'=>intval($spuInfo['channel_id']),
                    'discount_amount' => normalizeNum($skuInfo['buyout_price']),
                    'amount'=>normalizeNum($skuInfo['shop_price']*intval($skuInfo['zuqi'])+$spuInfo['yiwaixian']),
                    'all_amount'=>normalizeNum($skuInfo['shop_price']*intval($skuInfo['zuqi'])+$spuInfo['yiwaixian']),
                    'first_coupon_amount' => $first_coupon_amount,
                    'order_coupon_amount' => $order_coupon_amount,
                    'mianyajin' => !empty($this->deposit[$skuInfo['sku_id']]['mianyajin'])?normalizeNum($this->deposit[$skuInfo['sku_id']]['mianyajin']):0.00,
                    'jianmian' => !empty($this->deposit[$skuInfo['sku_id']]['jianmian'])?normalizeNum($this->deposit[$skuInfo['sku_id']]['jianmian']):0.00,
                    'deposit_yajin' => normalizeNum($deposit_yajin),
                    'amount_after_discount'=>normalizeNum($amount_after_discount),
                    'begin_time'=>$skuInfo['begin_time'],
                    'end_time'=>$skuInfo['end_time'],
            ];
        }
        return $arr;
    }
    /**
     *  计算押金
     * @param int $amount
     */
    public function discrease_yajin(int $jianmian,$yajin,$mianyajin,$sku_id): array{
        print_r($jianmian);
        print_r($yajin);
        print_r($mianyajin);
        print_r($sku_id);die;
        if( $jianmian<0 ){
            return [];
        }
        // 优惠金额 大于 总金额 时，总金额设置为0.01
        if( $jianmian >= $yajin ){
            $jianmian = $yajin;
        }
        $arr[$sku_id]['deposit_yajin'] = $yajin -$jianmian;// 更新押金
        $arr[$sku_id]['mianyajin'] = $mianyajin +$jianmian;// 更新免押金额
        $arr[$sku_id]['jianmian'] = $jianmian;
        $this->deposit =$arr;
        return $arr;
    }
    /**
     * 计算优惠券信息
     *
     */
    public function discrease_coupon($coupon){
        $schema =$this->getDataSchema();
        $sku =$schema['sku'];
        //计算总租金
        $totalAmount =0;
        foreach ($sku as $k=>$v){
            $totalAmount +=($v['zuqi']*$v['zujin'])*$v['sku_num'];
        }
        $zongyouhui=0;
        foreach ($sku as $k => $v) {
            for ($i =0;$i<$v['sku_num'];$i++){
                $youhui =0;
                foreach ($coupon as $key=>$val) {
                    //首月0租金
                    if ($val['coupon_type'] == CouponStatus::CouponTypeFirstMonthRentFree && $v['zuqi_type'] == 2) {
                        $skuyouhui[$v['sku_id']]['first_coupon_amount'] = $v['zujin'];
                        $coupon[$key]['is_use'] = 1;
                    }
                    //现金券
                    if ($val['coupon_type'] == CouponStatus::CouponTypeFixed) {

                        if ($v['zuqi_type'] == 2) {
                            $skuyouhui[$v['sku_id']]['order_coupon_amount'] = $val['discount_amount'];
                        } else {
                            $zongzujin = $v['zuqi'] * $v['zujin'];
                            $skuyouhui[$v['sku_id']]['order_coupon_amount'] = round($val['discount_amount'] / $totalAmount * $zongzujin, 2);
                            if ($k == count($sku) - 1 && $i ==$v['sku_num']-1) {
                                $skuyouhui[$v['sku_id']]['order_coupon_amount'] = $val['discount_amount'] - $zongyouhui;
                            }else{
                                $zongyouhui += $skuyouhui[$v['sku_id']]['order_coupon_amount'];
                            }
                        }
                        $coupon[$key]['is_use'] = 1;
                    }
                }
            }
        }
        $this->sku =$skuyouhui;
        return $coupon;
    }
    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        $data = $this->componnet->getDataSchema();
        $userId =$this->componnet->getOrderCreater()->getUserComponnet()->getUserId();
        $orderNo=$this->componnet->getOrderCreater()->getOrderNo();
        $goodsRepository = new OrderGoodsRepository();
        foreach ($data['sku'] as $k=>$v){
            $goodsArr[] = [
                'sku_id'=>$v['sku_id'],
                'spu_id'=>$v['spu_id'],
                'num'=>$v['sku_num']
            ];
            for($i=0;$i<$v['sku_num'];$i++){
                $goodsData =[
                    'goods_name'=>$v['spu_name'],
                    'zuji_goods_id'=>$v['sku_id'],
                    'zuji_goods_sn'=>$v['sku_no'],
                    'goods_thumb'=>$v['thumb'],
                    'goods_no'=>$v['goods_no'],
                    'prod_id'=>$v['spu_id'],
                    'prod_no'=>$v['spu_no'],
                    'brand_id'=>$v['brand_id'],
                    'category_id'=>$v['category_id'],
                    'machine_id'=>$v['machine_id'],
                    'user_id'=>$userId,
                    'quantity'=>1,
                    'goods_yajin'=>$v['yajin'],
                    'yajin'=>$v['deposit_yajin'],
                    'zuqi'=>$v['zuqi'],
                    'zuqi_type'=>$v['zuqi_type'],
                    'zujin'=>$v['zujin'],
                    'order_no'=>$orderNo,
                    'machine_value'=>$v['machine_value'],
                    'chengse'=>$v['chengse'],
                    'discount_amount'=>$v['discount_amount'],
                    'coupon_amount'=>$v['first_coupon_amount']+$v['order_coupon_amount'],
                    'amount_after_discount'=>$v['amount_after_discount'],
                    'edition'=>$v['edition'],
                    'market_price'=>$v['market_price'],
                    'price'=>$v['amount_after_discount'] + $v['insurance'],
                    'specs'=>Specifications::input_format($v['specs']),
                    'insurance'=>$v['insurance'],
                    'buyout_price'=>$v['buyout_price'],
                    'weight'=>$v['weight'],
                    'create_time'=>time(),
                ];
                //如果是短租 把租期时间写到goods 和goods_unit 中
                if($this->zuqiType ==1){
                    $goodsData['begin_time'] =strtotime($v['begin_time']);
                    $goodsData['end_time'] =strtotime($v['end_time']." 23:59:59");
                    $goodsData['zuqi'] = $v['zuqi'];
                    $unitData['unit_value'] =$v['zuqi'];
                    $unitData['unit'] =1;
                    $unitData['goods_no'] =$goodsData['goods_no'];
                    $unitData['order_no'] =$orderNo;
                    $unitData['user_id'] =$userId;
                    $unitData['begin_time'] =$goodsData['begin_time'];
                    $unitData['end_time'] =$goodsData['end_time'];

                    $b =ServicePeriod::createService($unitData);
                    if(!$b){
                        $this->getOrderCreater()->setError("保存设备周期表信息失败");
                        return false;
                    }
                }
                $goodsId =$goodsRepository->add($goodsData);
                if(!$goodsId){
                    $this->getOrderCreater()->setError("保存商品信息失败");
                    return false;
                }
            }
        }

        /**
         * 在这里要调用减少库存方法
         *
         */
        $b =Goods::reduceStock($goodsArr);
        if(!$b){
            $this->getOrderCreater()->setError("减少库存失败");
            return false;
        }

        return true;
    }

}