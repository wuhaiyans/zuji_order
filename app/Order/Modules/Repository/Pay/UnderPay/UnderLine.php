<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;


interface UnderLine {



    /**
     * 获取需支付金额
     */
    public function getPayAmount();

    /**
     * 具体实现 业务
     */
    public function execute();


}
