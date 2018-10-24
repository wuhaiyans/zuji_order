<?php
namespace App\Order\Modules\Repository\Instalment;

use App\Order\Modules\Repository\Instalment\Discounter\SimpleDiscounter;
use App\Order\Modules\Repository\Instalment\Discounter\Discounter;

/**
 * 分期计算（日租，n天作为一期）
 *
 */
class OrderWithhold implements UnderLine {


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
        return 100;
    }

    /**
     * 实现具体业务
     * @return bool true  false
     */
    public function execute( ){

        return true;

    }

}
