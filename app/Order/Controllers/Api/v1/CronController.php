<?php

namespace App\Order\Controllers\Api\v1;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use App\Order\Modules\Service;
use App\Order\Models\OrderGoodExtend;




class CronController extends Controller
{

    /**
     * 定时任务取消订单
     * @return bool
     */
    public function cronCancelOrder(){

      Service\CronOperate::cronCancelOrder();
      echo "complete";die;
    }
    /**
     * 定时任务确认收货
     * @return bool
     */
    public function cronDeliveryReceive(){
        Service\CronOperate::cronDeliveryReceive();
        echo "complete";die;
    }

}
