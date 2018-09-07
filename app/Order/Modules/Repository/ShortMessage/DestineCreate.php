<?php

namespace App\Order\Modules\Repository\ShortMessage;
use App\Lib\Common\LogApi;


/**
 * DestineCreate
 *
 * @author Administrator
 */
class DestineCreate implements ShortMessage {

	private $business_type;
	private $business_no;
    private $data;

	public function setBusinessType( int $business_type ){
		$this->business_type = $business_type;
	}

	public function setBusinessNo( string $business_no ){
		$this->business_no = $business_no;
	}

    public function setData( array $data ){
        $this->data = $data;
    }

	public function getCode($channel_id){
	    $class =basename(str_replace('\\', '/', __CLASS__));
		return Config::getCode($channel_id, $class);
	}

	public function notify(){
	    //获取预定单信息
         $destine = \App\Activity\Modules\Repository\Activity\ActivityDestine::getByNo($this->business_no);
        if( !$destine ){
            return false;
        }
        $destineInfo = $destine->getData();
        LogApi::debug("短信获取预定单信息",$destineInfo);

		// 短息模板
		$code = $this->getCode($destineInfo['channel_id']);
        LogApi::debug("短息模板",$code);
		if( !$code ){
			return false;
		}
        LogApi::debug("[create]短信内容",$destineInfo);
        // 发送短息
        $res=\App\Lib\Common\SmsApi::sendMessage($destineInfo['mobile'], $code, [
            'realName' => $destineInfo['mobile'],
            'orderNo' => $destineInfo['destine_no'],
            'goodsName' => $destineInfo['activity_name'],
        ],$destineInfo['destine_no']);
        return $res;
	}

	// 支付宝 短信通知
	public function alipay_notify(){
		return true;
	}

}
