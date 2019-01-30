<?php
namespace App\Tools\Modules\Service\Coupon\CouponModel;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Modules\Repository\Coupon\Coupon;
use App\Tools\Modules\Service\Coupon\CouponServiceInterface;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Repository\Coupon\CouponUser;
use App\Tools\Modules\Repository\GreyTest\GreyTest;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Repository\Coupon\CouponModelRepository;
use App\Lib\Tool\Tool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Tools\Modules\Service\Coupon\CouponUser\CouponUserCreate;
use App\Tool\Models\GreyTestModel;

/**
 * 搜索条件列表
 * @author Gaobo
 */
class CouponModelSearchCondition
{
    public function __construct(){}
    
    public function execute() : array 
    {
        //卡卷类型,卡卷领取方式,卡卷发放状态,渠道
        $data = [
            'type_name'   => CouponStatus::get_coupon_type_name(),
            'type_site'   => CouponStatus::get_coupon_type_site(),
            'type_status' => CouponStatus::get_coupon_type_status(),
            'range_name'  => CouponStatus::get_coupon_range_name()
        ];
        $channel   = Tool::getChannel(['status'=>1] , 'id,name');
        $channel[] = ['id'=>0,'name'=>'全渠道'];
        $data['channel'] = $channel;
        set_apistatus(ApiStatus::CODE_0, '');
        return $data;
    }
}