<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

use App\Lib\Common\LogApi;
use App\Lib\Curl;

class OrderRelet implements UnderLine {


    /**
     * 商品编号
     */
    protected $order_no = '';

    private $componnet;


    public function __construct( $params ) {
        $this->order_no = $params['order_no'];

        $this->componnet = $params;
    }



    /**
     * 计算该付款金额
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
        $url = config('ordersystem.OLD_ORDER_API');
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
        p($info);




    }

    /**
     * 实现具体业务
     * @return bool true  false
     */
    public function execute( ){

        return true;

    }

}
