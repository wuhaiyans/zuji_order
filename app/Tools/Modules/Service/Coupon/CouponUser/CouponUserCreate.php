<?php
namespace App\Tools\Modules\Service\Coupon\CouponUser;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Repository\Coupon\CouponUserRepository;

class CouponUserCreate
{
    protected $CouponUserRepository = [];
    

    public function __construct(CouponUserRepository $CouponUserRepository)
    {
        $this->CouponUserRepository = $CouponUserRepository;
    }
    
    public function execute(array $modelData , int $num = 1)
    {
        $time = time();
        //count 已发行的兑换码数量
        $count_coupon_code_item = $this->CouponUserRepository->getCount(['model_no'=>$modelData['model_no']]);
        
        if($count_coupon_code_item < $num){
            $num = $num - $count_coupon_code_item;//兼容补发
            $couponCodeArr = [];
            $whileNum = 0;
            while($whileNum < $num){
                $couponCodeArr[] = Func::md5_16();
                $couponCodeArr = array_keys(array_flip($couponCodeArr));//去重
                $whileNum = count($couponCodeArr);
            }
            $couponCodeArr = array_merge($couponCodeArr,[]);
            if($couponCodeArr){
                $user_day        = $modelData['user_day'];
                if($user_day){//天换秒
                    $start_time = $time;
                    $end_time = $time + $user_day * 86400;
                }else{
                    $start_time = $modelData['user_start_time'];
                    $end_time = $modelData['user_end_time'];
                }
                $mod = 500;
                foreach($couponCodeArr as $key => $code){
                    //组合values一次性插入多条数据
                    $data[] = [
                        'model_no'   => $modelData['model_no'],
                        'coupon_no'  => $code,
                        'start_time' => $start_time,
                        'end_time'   => $end_time,
                    ];
                    //取模，避免一次性大量插入
                    if(($key-1) >= $mod){
                        if($key%$mod == 0){
                            $this->CouponUserRepository->addAll($data);
                            usleep(50000);
                            $data = [];
                        }
                    }
                }
                if($data){
                    $this->CouponUserRepository->addAll($data);
                }
            }
            return true;
        }
        return false;
    }
}