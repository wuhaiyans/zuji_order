<?php
/**
 * App\Order\Modules\Repository\Pay\PayCreater.php
 * @access public
 * @author gaobo
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\Repository\Pay\BusinessPay;
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayInterface;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;

/**
 * 业务工厂
 * @access public
 * @author gaobo
 */
class BusinessPayFactory {
    
     /**
      * 获取业务
      * @access public
      * @param int $bid
      * @param int $did
      * @return BusinessPayInterface
      */
    public static function getBusinessPay( int $business_type, string $business_no ) : BusinessPayInterface{
        $business = config('businesspay.business');
        if(!array_key_exists($business_type,$business)){
            LogApi::error('没有此项业务');
            return apiResponse([],ApiStatus::CODE_20002,"没有此项业务");//应增加新的apistatus_code
        }
        return new $business[$business_type]($business_no);
    }
}
