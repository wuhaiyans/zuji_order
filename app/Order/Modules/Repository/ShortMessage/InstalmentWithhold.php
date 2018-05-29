<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Order\Modules\Repository\OrderRepository;

/**
 * InstalmentWithhold
 *
 * @author maxiaoyu
 */
class InstalmentWithhold implements ShortMessage {

    private $business_type;
    private $business_no;

    public function setBusinessType( int $business_type ){
        $this->business_type = $business_type;
    }

    public function setBusinessNo( string $business_no ){
        $this->business_no = $business_no;
    }

    public function getCode(){
        return Config::getCode($this->order_info['appid'], __CLASS__);
    }

    public function notify($data = ""){
        // 根据业务，获取短息需要的数据

        // 短息模板
        $code = Config::getCode($this->business_type, __CLASS__);
        if( !$code ){
            return false;
        }

        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($data['mobile'], $code, $data);
    }

}
