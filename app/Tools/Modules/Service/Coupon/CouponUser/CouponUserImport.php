<?php
namespace App\Tools\Modules\Service\Coupon\CouponUser;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Repository\Coupon\CouponUserRepository;
use App\Lib\Tool\Tool;
use App\Tools\Modules\Service\Coupon\CouponModel\CouponModelDetail;

class CouponUserImport
{
    protected $CouponUserRepository = [];
    protected $CouponModelDetail = [];

    public function __construct(CouponModelDetail $CouponModelDetail , CouponUserRepository $CouponUserRepository)
    {
        $this->CouponUserRepository = $CouponUserRepository;
        $this->CouponModelDetail = $CouponModelDetail;
    }
    
    public function execute($model_no , $tmp_file)
    {
//         /$tmp_file = 'C:\Users\Administrator\Desktop\优惠券导入用户模板v1.csv'; //临时文件
        if(!$tmp_file){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '文件不存在');
            return [];
        }
        
        $couponModel = $this->CouponModelDetail->execute($model_no);
        
        $time = time();
        $user_day = $couponModel->getAttribute('user_day');
        if($user_day){//天换秒
            $start_time = $time;
            $end_time = $time + $user_day * 86400;
        }else{
            $start_time = $couponModel->getAttribute('user_start_time');
            $end_time = $couponModel->getAttribute('user_end_time');
        }
        
        $path = $tmp_file;
        //$path = "C:\Users\Administrator\Desktop\aaa.csv";
        $file = fopen($path, 'r');
        $data = [];
        while ($one = fgetcsv($file)) {
            $data = array_merge($data,$one);
        };
        fclose($file);
        
        $num = count($data);
        if($num <= 0){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '文件内容错误');
            return [];
        }
        
        $where = ['model_no'=>$model_no,'status'=>0,'mobile'=>'0','is_lock'=>CouponStatus::CouponLockWei];
        $coupon_code_data = $this->CouponUserRepository->getCoupon($where,$num)->toArray();
        
        //优惠券个数是否等于用户个数
        if(count($coupon_code_data) < $num){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券数量不足');
            return [];
        }
        
        $retData['new_user'] = [];
        $retData['mobile_error'] = [];
        foreach($coupon_code_data as $key => $coupon_code){
            //手机号格式错误
            if(!Func::checkMobileValidity($data[$key])){
                $retData['mobile_error'][] = strval($data[$key]);
                continue;
            }
            // 查询用户信息
            $userInfo  = Tool::getIsMember($data[$key]);//调用商品系统接口查询此手机是否注册,返回用户信息
            if(!$userInfo){
                $retData['new_user'][] = strval($data[$key]);
                continue;
            }
            //查询该用户是否已拥有此优惠券
            $userCoupon = $this->CouponUserRepository->getCount(['mobile'=>$userInfo['mobile'] , 'model_no'=>$model_no]);
            if($userCoupon){
                continue;
            }
            
            //锁码
            $couponUser = $this->CouponUserRepository->getOne($coupon_code['id']);
            $couponUser->setAttribute('is_lock', 1);
            $couponUser->setAttribute('lock_time', time());
            $status = $couponUser->setLock();
            if(!$status){
                continue;
            }
            
            //更新user_coupon
            $couponUser->setAttribute('mobile', $userInfo['mobile']);
            $couponUser->setAttribute('user_id',$userInfo['id']);
            $couponUser->setAttribute('create_time',time());
            $couponUser->setAttribute('import',1);
            $status = $couponUser->setUserCoupon();
            if(!$status){
                sleep(1);
                $couponUser->setUserCoupon();
            }
        }
        
        set_apistatus(ApiStatus::CODE_0,'');
        return $retData;
    }
}