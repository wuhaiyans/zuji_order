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


	/**
	 * 
	 * @param \App\Order\Modules\OrderCreater\OrderCreater $componnet
	 * @param array $sku
	 * [
	 *		'sku_id' => '',		//【必选】SKU ID
	 *		'sku_num' => '',	//【必选】SKU 数量
	 * ]
	 * @param int $payType
	 * @throws Exception
	 */
    public function __construct(OrderCreater $componnet, array $sku,int $payType)
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
            $goodsArr[$skuId]['sku_info']['begin_time'] =isset($sku[$i]['begin_time'])?$sku[$i]['begin_time']:"";
            $goodsArr[$skuId]['sku_info']['end_time'] =isset($sku[$i]['end_time'])?$sku[$i]['end_time']:"";
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

            //计算短租租期
            if($this->zuqiType ==1){
                if(strtotime($skuInfo['begin_time'])-strtotime($skuInfo['begin_time'])<86400*2){
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
            $coupon_amount =!empty($this->sku[$skuInfo['sku_id']]['coupon_amount'])?$this->sku[$skuInfo['sku_id']]['coupon_amount']:0.00;
            $arr['sku'][] = [
                    'sku_id' => intval($skuInfo['sku_id']),
                    'spu_id' => intval($skuInfo['spu_id']),
                    'sku_name' => $skuInfo['sku_name'],
                    'spu_name' => $spuInfo['name'],
                    'sku_no' => $skuInfo['sn'],
                    'spu_no' => $spuInfo['sn'],
                    'goods_no'=>createNo(6),
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
                    'coupon_amount' => $coupon_amount,
                    'mianyajin' => !empty($this->deposit[$skuInfo['sku_id']]['mianyajin'])?$this->deposit[$skuInfo['sku_id']]['mianyajin']:0.00,
                    'jianmian' => !empty($this->deposit[$skuInfo['sku_id']]['jianmian'])?$this->deposit[$skuInfo['sku_id']]['jianmian']:0.00,
                    'deposit_yajin' => !empty($this->deposit[$skuInfo['sku_id']]['deposit_yajin'])?$this->deposit[$skuInfo['sku_id']]['deposit_yajin']:0.00,
                    'amount_after_discount'=>$skuInfo['shop_price']*$skuInfo['zuqi']-$skuInfo['buyout_price']-$coupon_amount,
                    'begin_time'=>$skuInfo['begin_time'],
                    'end_time'=>$skuInfo['end_time'],
            ];
        }
        $arr['coupon'] =$this->couponInfo;
        return $arr;
    }
    /**
     *  计算押金
     * @param int $amount
     */
    public function discrease_yajin(int $jianmian,$yajin,$mianyajin,$sku_id): array{
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
    public function discrease_coupon($sku,$coupon){
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
                        $skuyouhui[$v['sku_id']]['coupon_amount'] =$youhui;
                        $coupon[$key]['is_use'] = 1;
                    }
                    //现金券
                    if ($val['coupon_type'] == 1) {
                        $zongzujin = $v['zuqi'] * $v['zujin'] - $v['discount_amount'];
                        $skuyouhui[$v['sku_id']]['coupon_amount'] = round($val['discount_amount'] / $totalAmount * $zongzujin, 2);

                        if ($v['zuqi_type'] == 2) {
                            $skuyouhui[$v['sku_id']]['coupon_amount'] = $skuyouhui[$v['sku_id']]['coupon_amount']+$youhui;
                        } else {
                            if ($k == count($sku) - 1 && $i ==$v['sku_num']-1) {
                                $skuyouhui[$v['sku_id']]['coupon_amount'] = $val['discount_amount'] - $zongyouhui;
                            }else{
                                $zongyouhui += $skuyouhui[$v['sku_id']]['coupon_amount'];
                            }
                        }
                        $coupon[$key]['is_use'] = 1;
                    }
                }
            }
        }
        $this->couponInfo = $coupon;
        $this->sku =$skuyouhui;
    }
    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {

        $data = $this->componnet->getDataSchema();
        //var_dump($data);die;
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
                    'user_id'=>$userId,
                    'quantity'=>1,
                    'goods_yajin'=>$v['yajin'],
                    'yajin'=>$v['deposit_yajin'],
                    'zuqi'=>$v['zuqi'],
                    'zuqi_type'=>$v['zuqi_type'],
                    'zujin'=>$v['zujin'],
                    'order_no'=>$orderNo,
                    'chengse'=>$v['chengse'],
                    'discount_amount'=>$v['discount_amount'],
                    'coupon_amount'=>$v['coupon_amount'],
                    'amount_after_discount'=>$v['amount_after_discount'],
                    'edition'=>$v['edition'],
                    'market_price'=>$v['market_price'],
                    'price'=>$v['amount_after_discount'] + $v['yiwaixian'],
                    'specs'=>$v['specs'],
                    'insurance'=>$v['yiwaixian'],
                    'buyout_price'=>$v['buyout_price'],
                    'weight'=>$v['weight'],
                    'create_time'=>time(),
                ];
                //如果是短租 把租期时间写到goods 和goods_unit 中
                if($this->zuqiType ==1){
                    $goodsData['begin_time'] = strtotime($v['begin_time']);
                    $goodsData['end_time'] =strtotime($v['end_time']." 23:59:59");
                    $goodsData['zuqi'] = ($goodsData['end_time']-$goodsData['begin_time'])/86400;
                    $unitData['unit_value'] =($goodsData['end_time']-$goodsData['begin_time'])/86400;
                    $unitData['unit'] =1;
                    $unitData['goods_no'] =$goodsData['goods_no'];
                    $unitData['order_no'] =$orderNo;
                    $unitData['user_id'] =$userId;
                    $unitData['begin_time'] =$goodsData['begin_time'];
                    $unitData['end_time'] =$goodsData['end_time'];

                    $unitId =OrderGoodsUnitRepository::add($unitData);
                    if(!$unitId){
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
        $b =Goods::reduceStock(config('tripartite.Interior_Goods_Request_data'),$goodsArr);
        if(!$b){
            $this->getOrderCreater()->setError("减少库存失败");
            return false;
        }

        return true;
    }

}