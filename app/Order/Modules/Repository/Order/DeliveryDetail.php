<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;
use App\Lib\Common\LogApi;
use App\Lib\Contract\Contract;
use App\Order\Models\OrderDelivery;
use App\Order\Models\OrderGoodsDelivery;
use App\Order\Modules\Inc\OrderGoodStatus;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderRepository;

/**
 * 发货明细
 * （IMEI，合同）
 */
class DeliveryDetail {
    /**
     *
     * @var OrderGoodsdelivery
     */
    private $model = [];

    /**
     * 构造函数
     * @param array $data 商品扩展原始数据
     */
    public function __construct( OrderGoodsDelivery $OrderGoodsDelivery ) {
        $this->model = $OrderGoodsDelivery;
    }

    /**
     * 读取商品原始数据
     * @return array
     */
    public function getData():array{
        return $this->model->toArray();
    }

    /**
	 * 发货明细
	 * @param string $goods_no
	 * @return array|bool	array：发货明细（一维数组）；false：不存在
	 * [
	 * ]
	 */
	public static function getDetail( string $goods_no ){
		return false;
	}
	
	/**
	 * 发货明细列表
	 * @param string $order_no
	 * @return array	发货明细列表（二维数组）
	 * [
	 * ]
	 */
	public static function getDetailList( string $order_no ){
		return [];
	}


    /**
     * 换货更新原设备为无效
     * @return bool
     */
    public function barterDelivery():bool{
        //必须是有效状态
	    /*if($this->model->status == 1){
	        return false;
        }*/
        $this->model->status=1;   //无效   0：有效   1：无效
	    return $this->model->save();

    }

    /**
     * 增加发货时 生成合同
     * @param $orderNo 订单编号
     * @param $goodsInfo 发货时商品信息
     * @return bool
     */

    public static function addDeliveryContract(string $orderNo,array $goodsInfo=[]):bool{
        $orderInfo = OrderRepository::getOrderInfo(['order_no'=>$orderNo]);
        $instalment = OrderGoodsInstalmentRepository::queryList(['order_no'=>$orderNo,'times'=>1],['limit'=>1]);
        $payment_day ="";
        if($instalment){
            $payment_day =$instalment[0]['day'];
        }

        $data =[
            'order_no'=>$orderNo,
            'user_id'=>$orderInfo['user_id'],
            'email'=>'',
            'name'=>$orderInfo['realname'],
            'id_cards'=>$orderInfo['cret_no'],
            'mobile'=>$orderInfo['mobile'],
            'address'=>$orderInfo['address_info'],
            'payment_day'=>$payment_day,
            'delivery_time'=>time(),
        ];

        $goods = OrderRepository::getGoodsListByOrderId($orderNo);

        foreach ($goods as $k=>$v){
            $goodsDeliveryInfo = self::getGoodsDeliveryInfo($orderNo,$v['goods_no']);
            $value = $goodsDeliveryInfo->getData();
            $imei= $value['imei1']." ".$value['imei2']." ".$value['imei3']." ".$value['serial_number'];
//            foreach ($goodsInfo as $key=>$value){
//                $imei ="";
//                if(in_array($v['goods_no'],$value)){
//                    $value['imei2'] =isset($value['imei2'])?$value['imei2']:'';
//                    $value['imei3'] =isset($value['imei3'])?$value['imei3']:'';
//                    $value['serial_number'] =isset($value['serial_number'])?$value['serial_number']:'';
//
//                    $imei = $value['imei1']." ".$value['imei2']." ".$value['imei3']." ".$value['serial_number'];
//                }
//            }
            $v['chengse'] = OrderGoodStatus::spec_chengse_value($v['chengse']);
            $goodsData=[
                'spu_id'=>$v['prod_id'],
                'goods_no'=>$v['goods_no'],
                'chengse'=>$v['chengse'],
                'machine_no'=>$v['machine_value'],
                'imei'=>$imei,
                'zuqi'=>$v['zuqi'],
                'zujin'=>$v['amount_after_discount'],
                'mianyajin'=>$v['yajin'],
                'yiwaixian'=>$v['insurance'],
                'market_price'=>$v['market_price'],
                'goods_yajin'=>$v['goods_yajin'],
            ];
            $contractData =array_merge($data,$goodsData);
            $b =Contract::createContract($contractData);
            if(!$b){
                return false;
            }
        }
        return true;
    }


    /**
     * 生成订单发货单
     * @param array $deliveryData
     * [
     *  'order_no'=>'',//订单编号
     *  'logistics_id'=>''//物流渠道ID
     *  'logistics_no'=>''//物流单号
     * ]
     * @return bool
     */

	public static function addOrderDelivery(array $deliveryData):bool {
        $res = OrderDelivery::create($deliveryData);
        $id =$res->getQueueableId();
        if(!$id){
            return false;
        }
        return true;
    }

    /**
     * 生成发货单明细
     * @param $orderNo
     * @param $goods_info array 商品信息 【必须】 参数内容如下
     * [
     *   [
     *      'goods_no'=>'abcd',  商品编号   string  【必传】
     *      'imei1'   =>'imei1', 商品imei1  string  【必传】
     *      'imei2'   =>'imei2', 商品imei2  string  【必传】
     *      'imei3'   =>'imei3', 商品imei3  string  【必传】
     *      'serial_number'=>'abcd' 商品序列号  string  【必传】
     *   ]
     *   [
     *      'goods_no'=>'abcd',  商品编号   string  【必传】
     *      'imei1'   =>'imei1', 商品imei1  string  【必传】
     *      'imei2'   =>'imei2', 商品imei2  string  【必传】
     *      'imei3'   =>'imei3', 商品imei3  string  【必传】
     *      'serial_number'=>'abcd' 商品序列号  string  【必传】
     *   ]
     * ]
     * @return bool
     */

	public static function addGoodsDeliveryDetail(string $orderNo,array $goodsInfo):bool {
        foreach ($goodsInfo as $k=>$v){
            $data =[
                'order_no'=>$orderNo,
                'goods_no'=>$v['goods_no'],
                'imei1'=>isset($goodsInfo[$k]['imei1'])?$goodsInfo[$k]['imei1']:"",
                'imei2'=>isset($goodsInfo[$k]['imei2'])?$goodsInfo[$k]['imei2']:"",
                'imei3'=>isset($goodsInfo[$k]['imei3'])?$goodsInfo[$k]['imei3']:"",
                'serial_number'=>isset($goodsInfo[$k]['serial_number']) ? $goodsInfo[$k]['serial_number'] : '',
                'status'=>0,  //有效状态   0：有效   1：无效
            ];
            $res =OrderGoodsDelivery::create($data);//创建商品扩展信息
            $id =$res->getQueueableId();
            if(!$id){
                return false;
            }
        }
        return true;
    }

    /**
     * 获取商品扩展表信息
     * @param string $order_no
     * @param array $goods_info
     * @return DeliveryDetail|bool
     */
    public static function getGoodsDelivery(string $order_no,array $goods_info){
        foreach ($goods_info as $k=>$v){
            $builder=OrderGoodsDelivery::where([['order_no','=',$order_no],['goods_no','=',$goods_info[$k]['goods_no']]])->limit(1);
            $goods_delivery_info = $builder->first();
            if( !$goods_delivery_info ){
                return false;
            }
            return new self( $goods_delivery_info );
        }
    }

    /**
     * 获取商品扩展信息
     * @param string $order_no
     * @param string $goods_no
     * @return DeliveryDetail|bool
     */
    public static function getGoodsDeliveryInfo(string $order_no,string $goods_no){
        $builder=OrderGoodsDelivery::where([['order_no','=',$order_no],['goods_no','=',$goods_no]])->limit(1);
        $goods_delivery_info = $builder->first();
        if( !$goods_delivery_info ){
            return false;
        }
        return new self( $goods_delivery_info );
    }



	
}
