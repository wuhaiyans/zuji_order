<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

use App\Lib\Common\LogApi;
use App\Lib\Curl;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\ReletStatus;
use App\Order\Modules\Repository\Order\Goods;
use App\Order\Modules\Repository\Order\ServicePeriod;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ReletRepository;

class OrderRelet implements UnderLine {


    /**
     * 订单编号
     */
    protected $order_no = '';

    /**
     * 请求参数
     * @params
     * [''=>'']
     */
    private $componnet;


    public function __construct( $params ) {
        $this->order_no = $params['order_no'];

        $this->componnet = $params;
    }



    /**
     * 计算该付款金额
     * @param [
     * ''=>'',
     * ''=>'',
     * ''=>''
     * ]
     *
     * return string
     */
    public function getPayAmount(){
        $order_no = $this->order_no;

        $goods_obj = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow(['order_no'=>$order_no]);
        $goodsInfo = objectToArray($goods_obj);
        if(!$goodsInfo){
            LogApi::debug('[underLinePay]获取商品信息错误：'.$this->order_no);
            throw new \Exception("获取订单信息错误");
        }
        $spu_id = $goodsInfo['prod_id'];

        $begin_time = $this->componnet['extend']['begin_time'];
        $end_time   = $this->componnet['extend']['end_time'];

        $begin_time = strtotime($begin_time) > 0 ? strtotime($begin_time) : 0 ;
        $end_time   = strtotime($end_time) > 0 ? strtotime($end_time) : 0 ;

        if($begin_time >= $end_time){
            LogApi::debug('[underLinePay]续租时间错误：'.$this->order_no);
            throw new \Exception("续租时间错误");
        }

        $end_time = $end_time + (3600 * 24) - 1;
        $day = ceil( ($end_time - $begin_time) / 86400 );

        /**
         * 请求接口 计算商品总租金
         */
        $url  = config('ordersystem.OLD_ORDER_API');
        $data = [
            'version'		=> '1.0',
            'sign_type'		=> 'MD5',
            'sign'			=> '',
            'appid'			=> '1',
            'method'		=> 'zuji.short.rent.price.get',
            'timestamp' 	=> date("Y-m-d H:i:s"),
            'params'		=> [
                'zuqi'	    => $day,
                'spu_id'	=> $spu_id,
            ],
        ];

        $info = Curl::post($url, json_encode($data));
        $info = json_decode($info,true);

        if($info['code'] !== '0'){
            LogApi::debug('[underLinePay]获取续租商品金额错误：'.$this->order_no);
            throw new \Exception("获取续租商品金额错误：".$this->order_no);
        }

        $price  = $info['data'][0]['price'];

        return $price;
    }

    /**
     * 获取订单最大续租时间接口（小程序有限制，H5续租为无限制）
     */
    public function getReletTime(){
        $order_no = $this->order_no;
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $order_no );
        if( $orderInfo == false ){
            LogApi::debug('[underLinePay]获取订单信息错误：'.$this->order_no);
            return false;
        }
        //（小程序有限制，H5续租为无限制）
        if($orderInfo['order_type'] == \App\Order\Modules\Inc\OrderStatus::orderMiniService){
            $goods_info= \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow(['order_no'=>$order_no]);
            if(!$goods_info){
                LogApi::debug('[underLinePay]获取商品信息错误：'.$this->order_no);
                return false;
            }
            return ['relet_day'=>$goods_info['relet_day']];
        }else{
            return ['relet_day'=>''];
        }
    }
    /**
     * 实现具体业务（H5 + 小程序 线下续租操作）
     * 进行入账，数据操作
     * @params[
     *  'user_id'       => 'required', //用户ID
     *  'zuqi'          => 'required', //租期
     *  'order_no'      => 'required', //订单编号
     *  'pay_type'      => 'required', //支付方式
     *  'user_name'     => 'required',//用户名(手机号)
     *  'goods_id'      => 'required', //设备ID
     *  'relet_amount'  => 'required',//续租金额
     * ]
     * @return bool true  false
     */
    public function execute(){
        $params = $this->componnet;
        //获取商品对象
        $goodsObj = Goods::getByGoodsId($params['goods_id']);
        if( $goodsObj ){
            $goods = $goodsObj->getData();
            if( $goods['zuqi_type']==OrderStatus::ZUQI_TYPE1 ){
                if( $params['zuqi']<1 || $params['zuqi']>30 ){
                    set_msg('租期错误'.$params['goods_id']);
                    LogApi::info("[OrderRelet]租期错误", $params['goods_id']);
                    return false;
                }
            }else{
                set_msg('租期错误,当前只支持短租续租'.$params['goods_id']);
                LogApi::info("[OrderRelet]租期错误,当前只支持短租续租", $params['goods_id']);
                return false;
            }

            //获取订单信息
            $orderInfo = OrderRepository::getInfoById($params['order_no']);
            //验证是否小程序订单
            if($orderInfo['order_type']==OrderStatus::orderMiniService){
                if($goods['relet_day']<$params['zuqi']){
                    set_msg('小程序订单续租的租期必须小于剩余续租天数'.json_encode($params));
                    LogApi::info("[OrderRelet]小程序订单续租的租期必须小于剩余续租天数", json_encode($params));
                    return false;
                }
            }

            //创建续租完成
            if($params['relet_amount']){
                $data = [
                    'user_id'=>$params['user_id'],
                    'zuqi_type'=>$goods['zuqi_type'],
                    'zuqi'=>$params['zuqi'],
                    'order_no'=>$params['order_no'],
                    'relet_no'=>createNo(9),
                    'create_time'=>time(),
                    'pay_type'=>$params['pay_type'],
                    'user_name'=>$params['user_name'],
                    'goods_id'=>$params['goods_id'],
                    'relet_amount'=>$params['relet_amount'],
                    'status'=>ReletStatus::STATUS2,
                ];

                if(!ReletRepository::createRelet($data)){
                    set_msg('创建续租失败'.json_encode($data));
                    LogApi::info("[OrderRelet]创建续租失败", json_encode($data));
                    return false;
                }
            }else{
                set_msg('续租金额错误'.$params['relet_amount']);
                LogApi::info("[OrderRelet]续租金额错误", $params['relet_amount']);
                return false;
            }

            //获取订单信息
            $orderInfo = OrderRepository::getInfoById($params['order_no']);

            //修改小程序续租剩余时间
            if($orderInfo['order_type']==OrderStatus::orderMiniService){
                if(!$goodsObj->setReletTime($data['zuqi'])){
                    set_msg('修改小程序短租商品续租剩余天数失败'.$params['goods_id']);
                    LogApi::info("[OrderRelet]修改小程序短租商品续租剩余天数失败", $params['goods_id']);
                    return false;
                }
            }

            // 获取周期最新一条对象
            $goodsUnitObj = ServicePeriod::getByGoodsUnitNo($goods['order_no'],$goods['goods_no']);
            if($goodsUnitObj){
                $goodsUnit = $goodsUnitObj->getData();
            }else{
                set_msg('设备周期未找到'.$params['goods_id']);
                LogApi::info("[OrderRelet]设备周期未找到", $params['goods_id']);
                return false;
            }
            //判断租期类型
            if($data['zuqi_type']==OrderStatus::ZUQI_TYPE1){
                $t = strtotime("+".$data['zuqi']." day",$goodsUnit['end_time']);
            }else{
                $t = strtotime("+".$data['zuqi']." month",$goodsUnit['end_time']);
            }
            $data_goods = [
                'order_no'=>$goods['order_no'],
                'goods_no'=>$goods['goods_no'],
                'user_id'=>$goods['user_id'],
                'unit'=>$data['zuqi_type'],
                'unit_value'=>$data['zuqi'],
                'begin_time'=>$goodsUnit['begin_time'],
                'end_time'=>$t,
            ];
            //添加设备周期表
            if( !ServicePeriod::createService($data_goods) ){
                set_msg('续租添加设备周期表失败'.$params['goods_id']);
                LogApi::info("[OrderRelet]续租添加设备周期表失败", $params['goods_id']);
                return false;
            }
            //修改订单商品服务结束时间和租期
            if( !ServicePeriod::updateGoods($data_goods) ){
                set_msg('续租修改订单商品服务结束时间失败'.$params['goods_id']);
                LogApi::info("[OrderRelet]续租修改订单商品服务结束时间失败", $params['goods_id']);
                return false;
            }
            LogApi::info("[OrderRelet]续租支付成功", $data['relet_no']);
            return true;

        }else{
            set_msg('未获取到订单商品信息');
            LogApi::info("[OrderRelet]未获取到订单商品信息", $params['goods_id']);
            return false;
        }

    }

}
