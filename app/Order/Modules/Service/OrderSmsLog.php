<?php
namespace App\Order\Modules\Service;


use App\Order\Models\OrderSmsLog AS OSL;
class OrderSmsLog
{
	/**
	 * 订单短信发送记录处理model
	 * @var obj
	 */
	protected $order_sms_log_model;
	public function __construct(  ) {
		$this->order_sms_log_model = new OSL();
	}
    /**
     * 保存还机单数据
     * @param $data
     * @return id
     */
    public static function create($data){
		$order_sms_log_model = new OSL();
		$data = filter_array($data, [
			'mobile' => 'required',//手机号
			'template' => 'required',//短信模板
			'success' => 'required',//短信发送结果（0：成功，1：失败）
			'params' => 'required',//短信发送的参数json串
			'result' => 'required',//短信返回的结果json串
		]);
		$data['send_time'] = date('Y-m-d H:i:s');
        $result = $order_sms_log_model->create( $data );
		if( !$result ) {
			return false;
		}
		return true;
    }
}
