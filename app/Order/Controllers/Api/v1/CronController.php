<?php

namespace App\Order\Controllers\Api\v1;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use App\Order\Modules\Service;
use App\Order\Models\OrderGoodExtend;




class CronController extends Controller
{

    /**
     * 定时任务取消订单
     */
    public function cronCancelOrder(){

      Service\CronOperate::cronCancelOrder();
      echo "complete";die;
    }
    /**
     * 定时任务确认收货
     */
    public function cronDeliveryReceive(){
        Service\CronOperate::cronDeliveryReceive();
        echo "complete";die;
    }
    /**
     * 定时任务 长租订单到期前一个月发送信息
     */
    public function cronOneMonthEndByLong(){
        Service\CronOperate::cronOneMonthEndByLong();
        echo "complete";die;
    }
    /**
     * 定时任务 长租订单到期前一周发送信息
     */
    public function cronOneWeekEndByLong(){
        Service\CronOperate::cronOneWeekEndByLong();
        echo "complete";die;
    }
    /**
     * 定时任务 长租订单逾期一个月发送信息
     */
    public function cronOverOneMonthEndByLong(){
        Service\CronOperate::cronOverOneMonthEndByLong();
        echo "complete";die;
    }

    /**
     * 定时任务取消买断单
     * @return bool
     */
    public function cronCancelOrderBuyout(){

        Service\CronOperate::cronCancelOrderBuyout();
        echo "complete";die;
    }
    /**
     * 定时任务还机单更新状态-逾期违约
     * @return bool
     */
    public function cronGivebackAgedFail(){

        Service\CronOperate::cronGivebackAgedFail();
        echo "complete";die;
    }

    /**
     * 定时任务  换货确认收货
     */
    public function cronBarterDelivey(){
        Service\CronOperate::cronBarterDelivey();
        echo "complete";die;
    }

    /**
     * 定时任务  月初发送提前还款短信
     */
    public function cronPrepayment(){
        Service\CronOperate::cronPrepayment();
        echo "complete";die;
    }
}
