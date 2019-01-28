<?php
namespace App\Tools\Modules\Service\GreyTest;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Modules\Repository\Coupon\Coupon;
use App\Tools\Modules\Service\Coupon\CouponServiceInterface;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Repository\Coupon\CouponUser;
use App\Tools\Modules\Repository\GreyTest\GreyTestRepository;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Repository\Coupon\CouponModelRepository;
use App\Lib\Tool\Tool;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GreyTestGetByMobile
{
    protected $GreyTestRepository = [];
    
    //将灰度作为公共业务，此处应使用容器，注入interface接口，其他各业务实现此接口，并注册服务
    public function __construct(GreyTestRepository $GreyTestRepository)
    {
        $this->GreyTestRepository  = $GreyTestRepository;
    }
    
    public function execute(string $mobile)
    {
        if($mobile){
            $greyTest = $this->GreyTestRepository->getOne(['mobile'=>$mobile,'status'=>1]);
            set_apistatus(ApiStatus::CODE_0, '');
            if(!$greyTest->toArray()){
                set_apistatus(ApiStatus::CODE_50000, '无此数据');
                return [];
            }
            return $greyTest;
        }else{
            set_apistatus(ApiStatus::CODE_20001, '参数错误,mobile必传');
            return [];
        }
    }
}