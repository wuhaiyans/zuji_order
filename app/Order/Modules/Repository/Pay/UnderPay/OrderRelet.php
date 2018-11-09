<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

use App\Lib\Common\LogApi;
use App\Lib\Curl;

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
            return false;
        }
        $spu_id = $goodsInfo['prod_id'];

        $begin_time = $this->componnet['extend']['begin_time'];
        $end_time   = $this->componnet['extend']['end_time'];

        $begin_time = strtotime($begin_time) > 0 ? strtotime($begin_time) : 0 ;
        $end_time   = strtotime($end_time) > 0 ? strtotime($end_time) : 0 ;

        if($begin_time >= $end_time){
            LogApi::debug('[underLinePay]续租时间错误：'.$this->order_no);
            return false;
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
            return false;
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
     * 'order_no'=>'',
     * 'business_type'=>'',
     * 'goods_no'=>'',
     * 'under_channel'=>'',
     * 'amount'=>'',
     * 'create_time'=>'',
     * 'remark'=>'',
     * 'begin_time'=>'',
     * 'end_time'=>'',
     * ]
     * @return bool true  false
     */
    public function execute( ){




        return true;

    }

}
