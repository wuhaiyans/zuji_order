<?php
namespace App\Tools\Modules\Service\Coupon\CouponUser;
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
use App\Tools\Modules\Repository\Coupon\CouponUserRepository;
use App\Lib\Tool\Tool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Dotenv\Exception\ValidationException;

class CouponUserDetail
{
    protected $CouponUserRepository = [];
    
    //注入checker类 
    public function __construct(CouponUserRepository $CouponUserRepository)
    {
        $this->CouponUserRepository = $CouponUserRepository;
    }
    
    public function execute(int $id = 0)
    {
        if($id){
            $couponUser =  $this->CouponUserRepository->getOne($id);
            set_apistatus(ApiStatus::CODE_0, '');
            if(!$couponUser->toArray()){
                set_apistatus(ApiStatus::CODE_50000, '无此数据');
            }
            return $couponUser;
        }
        set_apistatus(ApiStatus::CODE_50000, '参数错误');
        return [];
    }
}