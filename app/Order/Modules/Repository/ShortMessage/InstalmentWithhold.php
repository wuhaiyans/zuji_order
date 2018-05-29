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

    public function getCode($channel_id){
        $class =basename(str_replace('\\', '/', __CLASS__));
        return Config::getCode($channel_id, $class);
    }

    public function notify($data = ""){
        // 根据业务，获取短息需要的数据

        // 短息模板
        $code = $this->getCode($this->business_type);
        if( !$code ){
            return false;
        }

        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($data['mobile'], $code, $data);
    }

}
