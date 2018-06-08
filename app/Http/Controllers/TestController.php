<?php

namespace App\Http\Controllers;

use App\Lib\Common\EmailApi;
use App\Lib\Common\SmsApi;
use App\Lib\Common\JobQueueApi;

use App\Lib\Warehouse\Delivery;
use App\Lib\Warehouse\Receive;

use App\Warehouse\Modules\Repository\DeliveryRepository;
use App\Warehouse\Modules\Repository\ImeiRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use App\Lib\Curl;

class TestController extends Controller
{

    public function test()
    {


        $a = Receive::updateLogistics(['receive_no'=>'201806081435393613','logistics_id'=>'1','logistics_no'=>'12323sdf']);

//        $a = Receive::create(1,2,[['goods_no'=>'abcde']]);
//        ImeiRepository::search('b');


//        DeliveryRepository::cancelMatchGoodsStatus('201805291931525799');

//        $this->testCreateReceive();

//        $this->testApply('A528100728283349');

//        $this->testSend('201805281944493333');
//        $this->testReceive();
//        $a = Delivery::receive('201805281926104525', \App\Warehouse\Models\Delivery::RECEIVE_TYPE_USER);
//
//        var_dump($a);


        //换货 发货测试
//        Delivery::createDelivery([
//            'order_no'=>123333,
//            'realname' => '张三',
//            'mobile' => '手机号',
//            'address_info' => '收货地址',
//            'goods'=> [
//                ['goods_no'=> 123],
//                ['goods_no'=> 456]
//        ]]);


        //新订单发货
//        $a = Delivery::apply('A511125156960043');



    }


//	public function testEmail(){
//		$b = EmailApi::send(['liuhongxing@huishoubao.com.cn'], 'Test...', 'test test');// ok
//		var_dump($b);exit;
//	}
//	public function testSms(){
//		$mobile = '15311371612';
//		$b = SmsApi::sendCode($mobile);
//		var_dump($b);exit;
//	}
	
//	public function testJob(){
//		
//		$callback = 'https://dev-api-zuji.huishoubao.com/test.php';
//
//		$b = false;
//
//		
//		$b = JobQueueApi::addRealTime('test-realtime','https://dev-api-zuji.huishoubao.com/test.php', [
//			'key' => 'test-realtime',
//			'time' => date('Y-m-d H:i:s'),
//		],$callback);
//		var_dump( 'test-realtime:'. ($b?'ok':'no' ) );
//		
//		
//		$b = JobQueueApi::addScheduleOnce('test-schedule-once','https://dev-api-zuji.huishoubao.com/test.php', [
//			'key' => 'test-schedule-once',
//			'time' => date('Y-m-d H:i:s'),
//		],time(),$callback);
//		var_dump( 'test-schedule-once:'. ($b?'ok':'no' ) );
//		
//		$b = JobQueueApi::addScheduleEvery('test-schedule-every','https://dev-api-zuji.huishoubao.com/test.php', [
//			'key' => 'test-schedule-every',
//			'time' => date('Y-m-d-H:i:s'),
//		],'1s',$callback);
//		var_dump( 'test-schedule-every:'. ($b?'ok':'no' ) );
//		
//		$b = JobQueueApi::addScheduleCron('test-schedule-cron','https://dev-api-zuji.huishoubao.com/test.php', [
//			'key' => 'test-schedule-cron',
//			'time' => date('Y-m-d H:i:s'),
//		],'*/1 * * * *',$callback);
//		var_dump( 'test-schedule-cron:'. ($b?'ok':'no' ) );
//		
//		sleep(4);
//		$b = JobQueueApi::disable('test-realtime');
//		var_dump( 'test-realtime disable:'. ($b?'ok':'no' ) );
//		
//		$b = JobQueueApi::disable('test-schedule-once');
//		var_dump( 'test-schedule-once disable:'. ($b?'ok':'no' ) );
//		
//		$b = JobQueueApi::disable('test-schedule-every');
//		var_dump( 'test-schedule-every disable:'. ($b?'ok':'no' ) );
//		
//		$b = JobQueueApi::disable('test-schedule-cron');
//		var_dump( 'test-schedule-cron disable:'. ($b?'ok':'no' ) );
//		
//		
//	}


    /**
     * 收货测试
     */
    public function testReceive()
    {
        $base_api = config('api.warehouse_api_uri');

        $res = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.delivery.receive',//模拟
            'params' => ['delivery_no'=>'201805281926104525', 'receive_type'=>2]
        ]);
        p($res);

        $res = json_decode($res, true);

    }


    public function testCreateReceive()
    {
        Receive::create(123456, 1, [['goods_no'=>123444]]);
    }


    /**
     * 发货申请
     */
    public function testApply($order_no)
    {
        Delivery::apply($order_no);
    }


    /**
     * 发货通知订单测试
     */
    public function testSend($delivery_no)
    {
        $base_api = config('api.warehouse_api_uri');

        $res = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.delivery.send',//模拟
            'params' => json_encode(['delivery_no'=>$delivery_no])
        ]);

        p($res);

    }




	public function test_alipay_url(){
		
		
		$params = [
			'app_id' => '1',	// 业务应用ID
			'out_no' => time(),	// 业务系统支付编号
			'amount' => '1',	// 金额，单位：分
			'name' => '测试商品支付',// 支付名称
			'back_url' => 'https://alipay/Test/notify',
			'front_url' => 'https://alipay/Test/front',
			'fenqi' => 0,	// 分期数
			'user_id' => 5,// 用户ID
		];
		$data = \App\Lib\Payment\AlipayApi::getUrl($params);
		var_dump( $data );exit;
	}
	
}
