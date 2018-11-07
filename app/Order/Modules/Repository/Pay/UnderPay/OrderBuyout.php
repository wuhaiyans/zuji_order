<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

use App\Order\Modules\Repository\Instalment\Discounter\SimpleDiscounter;
use App\Order\Modules\Repository\Instalment\Discounter\Discounter;

class OrderBuyout implements UnderLine {


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

    }

    /**
     * 实现具体业务
     * @return bool true  false
     */
    public function execute( ){

        return true;

    }

}
