<?php
/**
 *
 * 支付阶段--签约代扣处理
 */
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderPayModel;
use App\Order\Models\OrderPayPaymentModel;
use App\Order\Models\OrderPayWithhold;
use App\Order\Models\OrderPayWithholdModel;
use App\Order\Modules\Inc\OrderPayWithholdStatus;
use App\Order\Modules\Inc\OrderStatus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Profiler;

class OrderPayPaymentRepository
{

    /*
     * 查看订单的支付信息
     * @param $param $payment_no 支付编号
     * @return array
     */
    public static function find($payment_no){
        if(!$payment_no){
            return [];
        }

        $paymentInfo = OrderPayPaymentModel::query()
            ->where(['payment_no'=>$payment_no])
            ->first();
        if(!$paymentInfo){
            return [];
        }

        return $paymentInfo->toArray();
    }


}