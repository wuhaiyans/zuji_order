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
use App\Tools\Modules\Repository\Coupon\CouponUserRepository;
use App\Tools\Modules\service\coupon\CouponCalculation;

class CouponModelCalculation
{
    protected $CouponUserRepository = [];
    

    public function __construct(CouponUserRepository $CouponUserRepository)
    {
        $this->CouponUserRepository = $CouponUserRepository;
    }
    
    public function execute(int $amount = 0 , int $min_price = 0 , array $coupons)
    {
        //         if(!$amount && !$min_price){
        //             return [];
        //         }
        //初始化商品总金额及月租.
        $couponTypeIg = new CouponCalculation($amount , $min_price);
        $tempk = [];
        $coupons = array_merge($coupons,[]);
        foreach($coupons as $key=>$coupon){
            $couponTypeIg->setLimit($coupon['use_restrictions']);
            $couponTypeIg->setCouponValue($coupon['coupon_value']);
            
            $coupons[$key]['ret_amount'] = $couponTypeIg->{'algorithm'.$coupon['coupon_type']}();
            $coupons[$key]['num'] = $this->CouponUserRepository->getCount(['model_no'=>$coupon['model_no'],'is_lock'=>0]);
            $tempk[] = $coupons[$key]['ret_amount'];
        }
        
        //最优优惠券算法
        asort($tempk);
        $coupons = array_merge(array_replace($tempk,$coupons),[]);
        return $coupons;
    }
}