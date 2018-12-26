<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use App\Order\Modules\Service;
use App\Order\Models\OrderGoodExtend;
use Illuminate\Http\Request;

class CronController extends Controller
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
    public static function cronWithholdMessage($day = 1){
		self::addLog('[cronWithholdMessage]提前一天 三天 七天 发送扣款短信-start-', [$day]);

        // 超时时间
        ini_set('max_execution_time', '0');
        Service\CronOperate::cronPrepaymentMessage($day);
		self::addLog('[cronWithholdMessage]提前一天 三天 七天 发送扣款短信-end-', [$day]);
        echo "complete";die;
    }
	
	private static function addLog($name, $data = []){
		\App\Lib\Common\LogApi::error($name.date('Y-m-d H:i:s'),$data);
	}

    /**
     * 定时任务 用户扣款逾期 一天、三天
     */
    public static function cronOverdueMessage($day = 1){
        self::addLog('[cronOverdueMessage]逾期一天 三天 发送短信-start-', [$day]);
        // 超时时间
        ini_set('max_execution_time', '0');
        Service\CronOperate::cronOverdueMessage($day);
        self::addLog('[cronOverdueMessage]逾期一天 三天发送短信-end-', [$day]);
        echo "complete";die;
    }

    /**
     * 定时任务  获取连续两个月，总共三个月未缴租金的逾期数据
     */
    public static function cronOverdueDeductionMessage(){
        self::addLog('[cronOverdueDeductionMessage]获取连续两个月，总共三个月的逾期数据-start-');
        // 超时时间
        ini_set('max_execution_time', '0');
        Service\CronOperate::cronOverdueDeductionMessage();
        self::addLog('[cronOverdueDeductionMessage]获取连续两个月，总共三个月的逾期数据-end-');
        echo "complete";die;
    }
    public function test(){
        $a = Service\CronOperate::cronOverdueDeductionMessage();
        return apiResponse($a, ApiStatus::CODE_34007);
   }

}
