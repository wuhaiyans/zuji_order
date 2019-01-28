<?php
namespace App\Tools\Modules\Service\Coupon\CouponUser;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Checker\Checker;
use App\Tools\Modules\Service\Coupon\CouponModel\CouponModelDetail;
use App\Tools\Modules\Repository\Coupon\CouponUserRepository;
use Illuminate\Support\Facades\DB;

class CouponUserReceive
{
    protected $CouponUserDetail = [];
    protected $CouponModelDetail = [];
    protected $CouponUserRepository = [];
    
    public function __construct(CouponUserDetail $CouponUserDetail , CouponModelDetail $CouponModelDetail , CouponUserRepository $CouponUserRepository)
    {
        $this->CouponUserDetail = $CouponUserDetail;
        $this->CouponModelDetail = $CouponModelDetail;
        $this->CouponUserRepository = $CouponUserRepository;
    }
    
    public function execute(int $modelNo , string $mobile , $status = CouponStatus::CouponTypeStatusIssue)
    {
        $couponModel = $this->CouponModelDetail->execute($modelNo);
        if($couponModel){
            $time = time();
            $checker = new Checker();
            $checker->setCurrentTime($time);
            $checker->setReceiveStartTime($couponModel->start_time);
            $checker->setReceiveEndTime($couponModel->end_time);
            $checker->setModelStatus($couponModel->status);
            $checker->checkingGet();
            if($checker->getCode() == ApiStatus::CODE_0){
                $hasOne = $this->CouponUserRepository->getOneOnWhere(['model_no'=>$modelNo , 'mobile'=>$mobile])->toArray();
                if($hasOne){
                    set_apistatus(ApiStatus::CODE_50000, '已领过此优惠券');
                    return [];
                }
                $roundOne = $this->CouponUserRepository->getOneOnWhere(['model_no'=>$modelNo , 'is_lock'=>CouponStatus::CouponLockWei , 'status' =>CouponStatus::CouponStatusNotUsed] , true);
                if($roundOne){
                    //锁码 领券
                    $user_day = $couponModel->getAttribute('user_day');
                    if($user_day){//天换秒
                        $start_time = $time;
                        $end_time   = $time + $user_day * 86400;
                    }else{
                        $start_time = $couponModel->getAttribute('user_start_time');
                        $end_time   = $couponModel->getAttribute('user_end_time');
                    }
                    
                    $roundOne->setAttribute('is_lock', CouponStatus::CouponLockSuo)
                             ->setAttribute('mobile', $mobile)
                             ->setAttribute('create_time', $time)
                             ->setAttribute('lock_time', $time)
                             ->setAttribute('start_time', $start_time)
                             ->setAttribute('end_time', $end_time)
                            ;
                    if($roundOne->save()){
                        set_apistatus(ApiStatus::CODE_0, '');
                    }else{
                        set_apistatus(ApiStatus::CODE_50000, '保存失败');
                    }
                    return [];
                }else{
                    //已领完
                    set_apistatus(ApiStatus::CODE_50000, '已领完');
                    return [];
                }
            }
            set_apistatus($checker->getCode(), $checker->getMsg());
            return [];
        }else{
            //模板不存在
            set_apistatus(ApiStatus::CODE_50000, '模板不存在');
            return [];
        }
    }
}