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

        $day = $this->componnet['extend']['zuqi'];

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
        $zuqi   = $params['extend']['zuqi'];
        //获取商品对象
        $goodsObj = Goods::getByGoodsNo($params['goods_no']);
        if( $goodsObj ){
            $goods = $goodsObj->getData();
            if( $goods['zuqi_type'] == OrderStatus::ZUQI_TYPE1 ){
                if( $zuqi < 1 || $zuqi > 30 ){
                    LogApi::info("[OrderRelet]租期错误", [$this->order_no]);
                    throw new \Exception("租期错误,租期天数错误：".$this->order_no);
                }
            }else{
                LogApi::info("[OrderRelet]租期错误,当前只支持短租续租", [$this->order_no]);
                throw new \Exception("租期错误,当前只支持短租续租：".$this->order_no);
            }

            //获取订单信息
            $orderInfo = OrderRepository::getInfoById($this->order_no);
            //验证是否小程序订单
            if($orderInfo['order_type']==OrderStatus::orderMiniService){
                if($goods['relet_day'] < $zuqi){
                    LogApi::info("[OrderRelet]小程序订单续租的租期必须小于剩余续租天数", json_encode($params));
                    throw new \Exception("小程序订单续租的租期必须小于剩余续租天数：".$this->order_no);
                }
            }

            //创建续租完成
            if($params['amount']){
                $certified  = \App\Order\Models\OrderUserCertified::where('order_no','=',$this->order_no)->first();
                $userInfo   = objectToArray($certified);

                $user_name  = $userInfo['realname'] ? $userInfo['realname'] : "--";

                $data = [
                    'user_id'       => $orderInfo['user_id'],
                    'zuqi_type'     => $goods['zuqi_type'],
                    'zuqi'          => $zuqi,
                    'order_no'      => $goods['order_no'],
                    'relet_no'      => createNo(9),
                    'create_time'   => time(),
                    'pay_type'      => \App\Order\Modules\Inc\PayInc::UnderLinePay, // 线下支付
                    'user_name'     => $user_name,
                    'goods_id'      => $goods['id'],
                    'relet_amount'  => $params['amount'],
                    'status'        => ReletStatus::STATUS2,
                ];

                if(!ReletRepository::createRelet($data)){
                    LogApi::info("[OrderRelet]创建续租失败", json_encode($data));
                    throw new \Exception("创建续租失败：".$this->order_no);
                }
            }else{
                LogApi::info("[OrderRelet]续租金额错误", $params['amount']);
                throw new \Exception("续租金额错误：".$params['amount']);
            }

            //修改小程序续租剩余时间
            if($orderInfo['order_type'] == OrderStatus::orderMiniService){
                if(!$goodsObj->setReletTime($zuqi)){
                    LogApi::info("[OrderRelet]修改小程序短租商品续租剩余天数失败", $params['goods_id']);
                    throw new \Exception("修改小程序短租商品续租剩余天数失败：".$goods['id']);
                }
            }

            // 获取周期最新一条对象
            $goodsUnitObj = ServicePeriod::getByGoodsUnitNo($this->order_no,$goods['goods_no']);
            if($goodsUnitObj){
                $goodsUnit = $goodsUnitObj->getData();
            }else{
                LogApi::info("[OrderRelet]设备周期未找到", $params['goods_id']);
                throw new \Exception("设备周期未找到：".$goods['id']);
            }
            //判断租期类型
            if($data['zuqi_type'] == OrderStatus::ZUQI_TYPE1){
                $t = strtotime("+" . $zuqi . " day", $goodsUnit['end_time']);
            }else{
                $t = strtotime("+" . $zuqi . " month", $goodsUnit['end_time']);
            }

            $data_goods = [
                'order_no'      => $this->order_no,
                'goods_no'      => $goods['goods_no'],
                'user_id'       => $orderInfo['user_id'],
                'unit'          => $goods['zuqi_type'],
                'unit_value'    => $zuqi,
                'begin_time'    => $goodsUnit['begin_time'],
                'end_time'      => $t,
            ];

            //添加设备周期表
            if( !ServicePeriod::createService($data_goods) ){
                LogApi::info("[OrderRelet]续租添加设备周期表失败", $params['goods_id']);
                throw new \Exception("续租添加设备周期表失败：".$goods['id']);
            }
            //修改订单商品服务结束时间和租期
            if( !ServicePeriod::updateGoods($data_goods) ){
                LogApi::info("[OrderRelet]续租修改订单商品服务结束时间失败", $params['goods_id']);
                throw new \Exception("续租修改订单商品服务结束时间失败：".$goods['id']);
            }
            LogApi::info("[OrderRelet]续租支付成功", $data['relet_no']);
            return true;

        }else{
            LogApi::info("[OrderRelet]未获取到订单商品信息", $params['goods_no']);
            throw new \Exception("未获取到订单商品信息：");
        }

    }

}
