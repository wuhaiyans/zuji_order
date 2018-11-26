<?php
/**
 * 商品创建组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Common\LogApi;
use App\Lib\Goods\Goods;
use App\Order\Modules\Inc\CouponStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Inc\Specifications;
use App\Order\Modules\Repository\Order\DeliveryDetail;
use App\Order\Modules\Repository\Order\ServicePeriod;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderRepository;
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
    private $orderType=0;//订单类型

    private $goodsArr;
    //支付方式
    private $payType;
    private $deposit=[];
    private $couponInfo=[];
    private $sku=[];

    //规格
    private $specs;

    private $orderYajin=0;   //订单押金
    private $orderZujin=0;  //订单租金+意外险
    private $orderFenqi=0; //订单分期数
    private $orderInsurance=0;//订单 意外险


    //短租租用时间
    private $beginTime=0;
    private $endTime=0;



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
        $mobile = $this->componnet->getOrderCreater()->getUserComponnet()->getMobile();
        try{
            $goodsArr = Goods::getSkuList( array_column($sku, 'sku_id') ,$mobile);
        }catch (\Exception $e){
            LogApi::error(config('app.env')."OrderCreate-GetSkuList-error:".$e->getMessage());
            throw new Exception("GetSkuList:".$e->getMessage());
        }


        //商品数量付值到商品信息中
        for($i=0;$i<count($sku);$i++){
            $skuNum =$sku[$i]['sku_num'];
            $skuId =$sku[$i]['sku_id'];
            if(empty($goodsArr[$skuId]['spu_info']['payment_list'][0]['id']) || !isset($goodsArr[$skuId]['spu_info']['payment_list'][0]['id'])){
                LogApi::error(config('app.env')."OrderCreate-PayType-error:".$skuId);
                throw new Exception("商品支付方式错误");
            }
            //默认 获取 商品列表的第一个支付方式
            $this->payType =$payType?$payType:$goodsArr[$skuId]['spu_info']['payment_list'][0]['id'];
            //租期类型
            $this->zuqiType = $goodsArr[$skuId]['sku_info']['zuqi_type'];
            //如果为短租 商品租期为前端传递过来
            $goodsArr[$skuId]['sku_info']['begin_time'] =isset($sku[$i]['begin_time'])&&$this->zuqiType == 1?$sku[$i]['begin_time']:"";
            $goodsArr[$skuId]['sku_info']['end_time'] =isset($sku[$i]['end_time'])&&$this->zuqiType == 1?$sku[$i]['end_time']:"";
            $goodsArr[$skuId]['sku_info']['sku_num'] = $skuNum;
            $goodsArr[$skuId]['sku_info']['goods_no'] = createNo(6);

            if ($this->zuqiType == OrderStatus::ZUQI_TYPE_DAY) {
                $this->zuqiTypeName = "day";
                //计算短租租期
                $goodsArr[$skuId]['sku_info']['zuqi'] = ((strtotime($goodsArr[$skuId]['sku_info']['end_time']) -strtotime($goodsArr[$skuId]['sku_info']['begin_time']))/86400)+1;
            } elseif ($this->zuqiType == OrderStatus::ZUQI_TYPE_MONTH) {
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
        $this->orderType =$this->componnet->getOrderCreater()->getOrderType();
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
        //判断租期类型 长租只能租一个商品
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
//            if($skuInfo['number']<$skuInfo['sku_num']){
//                $this->getOrderCreater()->setError('商品库存不足');
//                $this->flag = false;
//            }
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
            if( $this->zuqiType == OrderStatus::ZUQI_TYPE_DAY ){ // 天
                // 租期[3,31]之间的正整数
                if( $skuInfo['zuqi']<1){
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
            if( $skuInfo['yajin'] < 0){
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
     * 获取订单意外险
     * @return string
     */
    public function getOrderInsurance(){
        return $this->orderInsurance;
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
            //首月0租金优惠金额
            $first_coupon_amount =isset($this->sku[$skuInfo['sku_id']]['first_coupon_amount'])?normalizeNum($this->sku[$skuInfo['sku_id']]['first_coupon_amount']):0.00;
            //订单固定金额优惠券
            $order_coupon_amount =isset($this->sku[$skuInfo['sku_id']]['order_coupon_amount'])?normalizeNum($this->sku[$skuInfo['sku_id']]['order_coupon_amount']):0.00;
            //计算后的押金金额 - 应缴押金金额
            $deposit_yajin =isset($this->deposit[$skuInfo['sku_id']]['deposit_yajin'])?normalizeNum($this->deposit[$skuInfo['sku_id']]['deposit_yajin']):$skuInfo['yajin'];
            //计算减免金额
            $mianyajin = isset($this->deposit[$skuInfo['sku_id']]['mianyajin'])?normalizeNum($this->deposit[$skuInfo['sku_id']]['mianyajin']):0.00;
            //计算免押金金额
            $jianmian =isset($this->deposit[$skuInfo['sku_id']]['jianmian'])?normalizeNum($this->deposit[$skuInfo['sku_id']]['jianmian']):0.00;
            //计算原始押金
            $yajin =isset($this->deposit[$skuInfo['sku_id']]['yajin'])?normalizeNum($this->deposit[$skuInfo['sku_id']]['yajin']):$skuInfo['yajin'];

            //计算买断金额
            $buyout_amount =normalizeNum( max(0,normalizeNum($skuInfo['market_price'] * 1.2-$skuInfo['shop_price'] * $skuInfo['zuqi']))  );

            //计算优惠后的总租金
            $amount_after_discount =normalizeNum($skuInfo['shop_price']*$skuInfo['zuqi']-$first_coupon_amount-$order_coupon_amount);
            if($amount_after_discount <0){
                $amount_after_discount =0.00;
            }
            //设置订单金额的赋值 （目前一个商品 就暂时写死 多个商品后 根据文案 进行修改）
            $this->orderZujin =$amount_after_discount+$spuInfo['yiwaixian'];
            $this->orderFenqi =intval($skuInfo['zuqi_type']) ==1?1:intval($skuInfo['zuqi']);
            $this->orderYajin =$deposit_yajin;
            $this->orderInsurance =$spuInfo['yiwaixian'];

            //如果是活动领取接口  押金 意外险 租金都 为0
            if($this->orderType == OrderStatus::orderActivityService){
                $jianmian = $this->orderYajin;
                $mianyajin = $this->orderYajin;
                $this->orderZujin = 0.00;
                $this->orderYajin = 0.00;
                $deposit_yajin = 0.00;
                $spuInfo['yiwaixian'] =0.00; //意外险
                $amount_after_discount =0.00;
                $buyout_amount =normalizeNum( max(0,normalizeNum($skuInfo['market_price'] * 1.2)));

            }


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
                    'kucun' => intval($skuInfo['number']),
                    'brand_id' => intval($spuInfo['brand_id']),
                    'category_id' => intval($spuInfo['catid']),
                    'machine_id' => intval($spuInfo['machine_id']),//机型ID
                    'specs' => $this->specs, //规格
                    'thumb' => $spuInfo['thumb'], //商品缩略图
                    'insurance' =>$spuInfo['yiwaixian'], //意外险
                    'insurance_cost' => $spuInfo['yiwaixian_cost'], //意外险成本价
                    'zujin' => $skuInfo['shop_price'], //租金
                    'yajin' => $yajin, //商品押金
                    'zuqi' => intval($skuInfo['zuqi']),
                    'zuqi_type' => intval($skuInfo['zuqi_type']),
                    'zuqi_type_name' => $this->zuqiTypeName,
                    'buyout_price' => $buyout_amount,
                    'market_price' => $skuInfo['market_price'],
                    'machine_value' => isset($spuInfo['machine_name'])?$spuInfo['machine_name']:"",//机型名称
                    'chengse' => $skuInfo['chengse'],//商品成色
                    'stock' => intval($skuInfo['number']),
                    'pay_type' => $this->payType,
                    'channel_id'=>intval($spuInfo['channel_id']),
                    'discount_amount' => 0,//$skuInfo['buyout_price'], //商品优惠金额 （商品系统为buyout_price字段）
                    'amount'=>normalizeNum($skuInfo['shop_price']*intval($skuInfo['zuqi'])+$spuInfo['yiwaixian']),
                    'all_amount'=>normalizeNum($skuInfo['shop_price']*intval($skuInfo['zuqi'])+$spuInfo['yiwaixian']),
                    'yajin_limit'=>normalizeNum($spuInfo['yajin_limit']), //最小押金值
                    'first_coupon_amount' => $first_coupon_amount,
                    'order_coupon_amount' => $order_coupon_amount,
                    'mianyajin' => $mianyajin,
                    'jianmian' => $jianmian,
                    'deposit_yajin' => $deposit_yajin,//应缴押金
                    'amount_after_discount'=>$amount_after_discount,
                    'begin_time'=>$this->beginTime?:$skuInfo['begin_time'],
                    'end_time'=>$this->endTime?:$skuInfo['end_time'],
            ];
        }
        return $arr;
    }
    /**
     *  小程序计算押金
     * @param int $amount
     */
    public function mini_discrease_yajin($jianmian,$yajin,$mianyajin,$sku_id): array{
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
     *  计算押金
     * @param int $amount
     */
    public function discrease_yajin($jianmian,$yajin,$yajinLimit,$sku_id): array{
        if( $jianmian<0 ){
            return [];
        }
        //判断如果押金限额 大于 风控押金值 取押金限额
        if($yajinLimit > $yajin){
            $yajin =$yajinLimit;
        }

        // 优惠金额 大于 总金额 时，总金额设置为0.01
        $arr[$sku_id]['deposit_yajin'] = $yajin;// 更新押金
        $arr[$sku_id]['mianyajin'] = $jianmian;// 更新免押金额
        $arr[$sku_id]['jianmian'] = $jianmian;
        $arr[$sku_id]['yajin'] = $yajin+$jianmian;
        $this->deposit =$arr;
        return $arr;
    }
    /**
     *  覆盖 租用时间
     * @param $beginTime
     * @param $endTime
     * @return true
     */
    public function unitTime($beginTime,$endTime): bool {

        $this->beginTime =$beginTime;
        $this->endTime = $endTime;
        return true;
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
                $zongzujin = $v['zuqi'] * $v['zujin'];
                foreach ($coupon as $key=>$val) {

                    $skuyouhui[$v['sku_id']]['order_coupon_amount'] =0;
                    //首月0租金 coupon_type =3
                    if ($val['coupon_type'] == CouponStatus::CouponTypeFirstMonthRentFree && $v['zuqi_type'] == OrderStatus::ZUQI_TYPE_MONTH) {
                        $skuyouhui[$v['sku_id']]['first_coupon_amount'] = $v['zujin'];
                        $coupon[$key]['is_use'] = 1;
                        $coupon[$key]['discount_amount'] = $v['zujin'];
                    }
                    //现金券 coupon_type =1  分期递减 coupon_type =4  总金额和现金券计算同等
                    if ($val['coupon_type'] == CouponStatus::CouponTypeFixed || $val['coupon_type'] == CouponStatus::CouponTypeDecline ) {

                        if ($v['zuqi_type'] == OrderStatus::ZUQI_TYPE_MONTH) {
                            $skuyouhui[$v['sku_id']]['order_coupon_amount'] = $val['discount_amount'];
                        } else {

                            $skuyouhui[$v['sku_id']]['order_coupon_amount'] = round($val['discount_amount'] / $totalAmount * $zongzujin, 2);
                            if ($k == count($sku) - 1 && $i ==$v['sku_num']-1) {
                                $skuyouhui[$v['sku_id']]['order_coupon_amount'] = $val['discount_amount'] - $zongyouhui;
                            }else{
                                $zongyouhui += $skuyouhui[$v['sku_id']]['order_coupon_amount'];
                            }
                        }
                        $coupon[$key]['is_use'] = 1;
                        $coupon[$key]['discount_amount'] = $val['discount_amount'];
                    }
                    //租金折扣券 coupon_type =2  四舍五入 保留一位小数
                    if ($val['coupon_type'] == CouponStatus::CouponTypePercentage) {

                        $skuyouhui[$v['sku_id']]['order_coupon_amount'] =round($zongzujin-$zongzujin*$val['discount_amount'],1);
                        $coupon[$key]['is_use'] = 1;
                        $coupon[$key]['discount_amount'] = round($zongzujin-$zongzujin*$val['discount_amount'],1);
                    }
                    //满减券 coupon_type =5
                    if ($val['coupon_type'] == CouponStatus::CouponFullSubtraction) {
                        if($zongzujin>= $val['use_restrictions']){
                            $skuyouhui[$v['sku_id']]['order_coupon_amount'] =$val['discount_amount'];
                            $coupon[$key]['is_use'] = 1;
                            $coupon[$key]['discount_amount'] = $val['discount_amount'];
                        }
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
        $goodsArr=[];
        foreach ($data['sku'] as $k=>$v){
            if($v['kucun']>=$v['sku_num']){
                $goodsArr[] = [
                    'sku_id'=>$v['sku_id'],
                    'spu_id'=>$v['spu_id'],
                    'num'=>$v['sku_num']
                ];
            }
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
                    'insurance_cost'=>$v['insurance_cost'],
                    'buyout_price'=>$v['buyout_price'],
                    'weight'=>$v['weight'],
                    'create_time'=>time(),
                ];
                //如果是短租 把租期时间写到goods 和goods_unit 中(小程序续租时间为75天)
                if($this->zuqiType ==1){
                    $goodsData['begin_time'] =strtotime($v['begin_time']);
                    $goodsData['end_time'] =strtotime($v['end_time']." 23:59:59");

                    $zuqi =ceil((strtotime($v['end_time'])-strtotime($v['begin_time']))/86400+1);
                    $goodsData['zuqi'] = $zuqi;
                    if( $this->orderType == OrderStatus::orderMiniService ){//小程序
                        $goodsData['relet_day'] = 75;
                    }else{//非小程序
                        $goodsData['relet_day'] = 0;
                    }
                    $unitData['unit_value'] =$zuqi;
                    $unitData['unit'] =1;
                    $unitData['goods_no'] =$goodsData['goods_no'];
                    $unitData['order_no'] =$orderNo;
                    $unitData['user_id'] =$userId;
                    $unitData['begin_time'] =$goodsData['begin_time'];
                    $unitData['end_time'] =$goodsData['end_time'];

                    $b =ServicePeriod::createService($unitData);
                    if(!$b){
                        LogApi::error(config('app.env')."OrderCreate-Add-Unit-error",$unitData);
                        $this->getOrderCreater()->setError("OrderCreate-Add-Unit-error");
                        return false;
                    }
                }
                $goodsId =$goodsRepository->add($goodsData);
                if(!$goodsId){
                    LogApi::error(config('app.env')."OrderCreate-AddGoods-error",$goodsData);
                    $this->getOrderCreater()->setError("OrderCreate-AddGoods-error");
                    return false;
                }

            }
        }

        /**
         * 在这里要调用减少库存方法
         */
        if(!empty($goodsArr)){
            $b =Goods::reduceStock($goodsArr);
            if(!$b){
                LogApi::error(config('app.env')."OrderCreate-reduceStock-error",$goodsArr);
                $this->getOrderCreater()->setError("OrderCreate-reduceStock-error");
                return false;
            }
        }

        return true;
    }

}