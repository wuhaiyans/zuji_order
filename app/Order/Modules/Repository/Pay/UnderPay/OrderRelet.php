<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

use App\Order\Modules\Repository\Instalment\Discounter\SimpleDiscounter;
use App\Order\Modules\Repository\Instalment\Discounter\Discounter;

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
        $begin_time = $this->componnet['extend'][''];
        $end_time = $this->componnet['extend'][''];

        $begin_time = strtotime($begin_time) > 0 ? strtotime($begin_time) : 0 ;
        $end_time   = strtotime($end_time) > 0 ? strtotime($end_time) : 0 ;

        if($begin_time >= $end_time){
            return apiResponse([], ApiStatus::CODE_50000, "时间错误");
        }


        $end_time = $end_time + (3600 * 24) - 1;
        $day = ceil( ($end_time - $begin_time) / 86400 );

       




    }

    /**
     * 实现具体业务
     * @return bool true  false
     */
    public function execute( ){

        return true;

    }

}
