<?php

namespace App\Order\Controllers\Api\v1;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use App\Order\Modules\Service;
use App\Order\Models\OrderGoodExt-end-;
use Illuminate\Http\Request;

class CronController ext-end-s Controller
{

    /**
     * 定时任务取消订单
     */
    public function cronCancelOrder(){
		self::addLog('[cronCancelOrder]定时任务取消订单-start-');
      Service\CronOperate::cronCancelOrder();
		self::addLog('[cronCancelOrder]定时任务取消订单-end-');
      echo "complete";die;
    }
    /**
     * 定时任务确认收货
     */
    public function cronDeliveryReceive(){
		self::addLog('[cronDeliveryReceive]定时任务确认收货-start-');
        Service\CronOperate::cronDeliveryReceive();
		self::addLog('[cronDeliveryReceive]定时任务确认收货-end-');
        echo "complete";die;
    }
    /**
     * 定时任务 长租订单到期前一个月发送信息
     */
    public function cronOneMonthEndByLong(){
		self::addLog('[cronOneMonthEndByLong]长租订单到期前一个月发送信息-start-');
        Service\CronOperate::cronOneMonthEndByLong();
		self::addLog('[cronOneMonthEndByLong]长租订单到期前一个月发送信息-end-');
        echo "complete";die;
    }
    /**
     * 定时任务 长租订单到期前一周发送信息
     */
    public function cronOneWeekEndByLong(){
		self::addLog('[cronOneWeekEndByLong]长租订单到期前一周发送信息-start-');
        Service\CronOperate::cronOneWeekEndByLong();
		self::addLog('[cronOneWeekEndByLong]长租订单到期前一周发送信息-end-');
        echo "complete";die;
    }
    /**
     * 定时任务 长租订单逾期一个月发送信息
     */
    public function cronOverOneMonthEndByLong(){
		self::addLog('[cronOverOneMonthEndByLong]长租订单逾期一个月发送信息-start-');
        Service\CronOperate::cronOverOneMonthEndByLong();
		self::addLog('[cronOverOneMonthEndByLong]长租订单逾期一个月发送信息-end-');
        echo "complete";die;
    }

    /**
     * 定时任务取消买断单
     * @return bool
     */
    public function cronCancelOrderBuyout(){
		self::addLog('[cronCancelOrderBuyout]定时任务取消买断单-start-');
        Service\CronOperate::cronCancelOrderBuyout();
		self::addLog('[cronCancelOrderBuyout]定时任务取消买断单-end-');
        echo "complete";die;
    }
    /**
     * 定时任务还机单更新状态-逾期违约
     * @return bool
     */
    public function cronGivebackAgedFail(){
		self::addLog('[cronGivebackAgedFail]还机单更新状态-逾期违约-start-');
        Service\CronOperate::cronGivebackAgedFail();
		self::addLog('[cronGivebackAgedFail]还机单更新状态-逾期违约-end-');
        echo "complete";die;
    }

    /**
     * 定时任务  换货确认收货
     */
    public function cronBarterDelivey(){
		self::addLog('[cronBarterDelivey]换货确认收货-start-');
        Service\CronOperate::cronBarterDelivey();
		self::addLog('[cronBarterDelivey]换货确认收货-end-');
        echo "complete";die;
    }

    /**
     * 定时任务  月初发送提前还款短信
     */
    public function cronPrepayment(){
		self::addLog('[cronPrepayment]月初发送提前还款短信-start-');
        // 超时时间
        ini_set('max_execution_time', '0');

        Service\CronOperate::cronPrepayment();
		self::addLog('[cronPrepayment]月初发送提前还款短信-end-');
        echo "complete";die;
    }

    /**
     * 定时任务  提前一天 三天 七天 发送扣款短信
     */
    public function cronWithholdMessage(Request $request){
		self::addLog('[cronWithholdMessage]提前一天 三天 七天 发送扣款短信-start-');
        $day = $request->get('day', 1);
        // 超时时间
        ini_set('max_execution_time', '0');
        Service\CronOperate::cronPrepaymentMessage($day);
		self::addLog('[cronWithholdMessage]提前一天 三天 七天 发送扣款短信-end-');
        echo "complete";die;
    }
	
	private static function addLog($name){
		\App\Lib\Common\LogApi::error($name.date('Y-m-d H:i:s'));
	}

    /**
     * 定时任务 用户扣款逾期 一天、三天
     */
    public function cronOverdueMessage(Request $request){
        $day = $request->get('day', 1);
        // 超时时间
        ini_set('max_execution_time', '0');
        Service\CronOperate::cronOverdueMessage($day);
        echo "complete";die;
    }

}
