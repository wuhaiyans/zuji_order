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
use App\Tools\Modules\Service\Coupon\CouponModel\CouponModelDetail;
use Tymon\JWTAuth\Http\Middleware\Check;
use App\Tools\Modules\Checker\Checker;

class CouponUserExchange
{
    protected $CouponUserRepository = [];
    protected $CouponModelDetail    = [];
    
    //或注入checker类 
    public function __construct(CouponUserRepository $CouponUserRepository , CouponModelDetail $CouponModelDetail)
    {
        $this->CouponUserRepository = $CouponUserRepository;
        $this->CouponModelDetail    = $CouponModelDetail;
    }
    
    public function execute(string $mobile , string $couponNo , $status = CouponStatus::CouponTypeStatusIssue)
    {
        if(!$mobile || !$couponNo){
            return [ApiStatus::CODE_20001,'参数错误'];
        }
        //根据couponNo获取couponuser
        $time = time();
        $couponUser = $this->CouponUserRepository->getOneOnWhere(['coupon_no'=>$couponNo]);
        if(!$couponUser->toArray()){
            set_apistatus(ApiStatus::CODE_50000, '此码不合法');
            return [];
        }
        if($couponUser->getAttribute('is_lock') == CouponStatus::CouponLockSuo){
            set_apistatus(ApiStatus::CODE_50000, '此码已兑换');
            return [];
        }
        
        //根据modelno获取model
        $couponModel = $this->CouponModelDetail->execute($couponUser->getAttribute('model_no'));
        if(!$couponModel->toArray()){
            set_apistatus(ApiStatus::CODE_50000, '模型不存在');
            return [];
        }
        
        //check
        $checker = new Checker();
        $checker->setModelStatus($couponModel->status);
        $checker->setReceiveStartTime($couponModel->start_time);
        $checker->setReceiveEndTime($couponModel->end_time);
        $checker->setCurrentTime($time);
        $checker->checkingGet();
        
        if($checker->getCode() == ApiStatus::CODE_0){
                //锁码 领券
            $user_day = $couponModel->getAttribute('user_day');
            if($user_day){//天换秒
                $start_time = $time;
                $end_time   = $time + $user_day * 86400;
            }else{
                $start_time = $couponModel->getAttribute('user_start_time');
                $end_time   = $couponModel->getAttribute('user_end_time');
            }
            
            $state = $couponUser
                        ->setAttribute('is_lock', CouponStatus::CouponLockSuo)
                        ->setAttribute('mobile', $mobile)
                        ->setAttribute('create_time', $time)
                        ->setAttribute('start_time', $start_time)
                        ->setAttribute('end_time', $end_time)
                        ->save();
            if($state){
                set_apistatus(ApiStatus::CODE_0, '');
                return [];
            }else{
                set_apistatus(ApiStatus::CODE_50000, '保存失败');
                return [];
            }
        }
        set_apistatus(ApiStatus::CODE_50000, '领取条件不符.'.$checker->getMsg());
        return [];
    }
}