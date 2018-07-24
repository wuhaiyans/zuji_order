<?php
namespace App\Order\Controllers\Api\v1;

use App\Order\Modules\Service\OrderNotice;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;

class TestController extends Controller
{
	public function sendSms() {

		$paramsArr = [
	 		'goods_no' => 'GA71199188045622',//商品编号	【必须】<br/>
	 		'evaluation_status' => '1',//检测结果 【必须】<br/>
	 		'evaluation_time' => time(),//检测时间 【必须】<br/>
	 		'evaluation_remark' => '测试',//检测备注 【可选】【检测不合格时必须】<br/>
	 		'compensate_amount' => '0.01',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
	 		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
	 		'order_no' => 'A711199188021012',//订单编号 【必须】<br/>
	 		'user_id' => '28',//用户id 【必须】<br/>
	 		'giveback_no' => '11111',//还机单编号 【必须】<br/>
	 		'instalment_num' => '0',//剩余分期期数 【必须】【可为0】<br/>
	 		'instalment_amount' => '0',	//剩余分期总金额 【必须】【可为0】<br/>
	 		'yajin' => '0.01',				//押金金额 【必须】【可为0】<br/>
		];

		$a = new \App\Order\Controllers\Api\v1\GivebackController();
		$a->__orderClean($paramsArr);
		v($a);



		die;

		//发送短信
		$notice = new \App\Order\Modules\Service\OrderNotice(
			\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
			'GA71199188045622',
			"GivebackReturnDeposit");
		$notice->notify();


//		//发送短信
//		$notice = new \App\Order\Modules\Service\OrderNotice(
//			\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
//			'GA71199188045622',
//			'GivebackEvaNoWitYesEnoNo',
//			['amount'=>1]);
//		$notice->notify();



//		$id = 1;
//		$a  = \App\Order\Modules\Service\OrderWithhold::instalment_withhold($id);
//		v($a);

//		//发送短信通知 支付宝内部通知
//		$notice = new \App\Order\Modules\Service\OrderNotice(
//			\App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
//			'FA70200698030574',
//			"InstalmentWithhold");
//
//		$notice->notify();


//		$a = new \App\Order\Controllers\Api\v1\WithholdController();
//		$b = $a->crontab_createpay();
//		v($b);

//		$data = [
//			'payment_no'    => 'required',
//			'out_no'        => 'FA62602377858037',
//			'status'        => 'success',
//			'reason'        => '',
//		];
//
//		$a = \App\Order\Modules\Service\OrderWithhold::repaymentNotify($data);
//		v($a);
//		die;
//		$a = new OrderNotice(1, 1, SceneConfig::ORDER_CREATE);
//		$a->notify();
	}
}
?>
