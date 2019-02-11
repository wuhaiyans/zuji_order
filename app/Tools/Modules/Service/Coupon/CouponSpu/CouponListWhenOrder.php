<?php
namespace App\Tools\Modules\Service\Coupon\CouponSpu;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Repository\GreyTest\GreyTestRepository;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Repository\Coupon\CouponModelRepository;
use App\Lib\Tool\Tool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Tools\Modules\Repository\Coupon\CouponSpuRepository;
use App\Tools\Modules\Service\Coupon\CouponModel\CouponModelDetail;
use App\Tools\Modules\Service\Coupon\CouponModel\CouponModelCalculation;

/**
 * 下单优惠券列表
 * @author Gaobo
 *
 */
class CouponListWhenOrder
{
    protected $CouponSpuList          = [];
    protected $CouponModelDetail      = [];
    protected $CouponSpuUserList      = [];
    protected $CouponModelCalculation = [];
    
    /**
     * 依赖服务注入
     * @param CouponSpuList $CouponSpuList
     * @param CouponModelDetail $CouponModelDetail
     * @param CouponSpuUserList $CouponSpuUserList
     * @param CouponModelCalculation $CouponModelCalculation
     */
    public function __construct(CouponSpuList $CouponSpuList , CouponModelDetail $CouponModelDetail , CouponSpuUserList $CouponSpuUserList , CouponModelCalculation  $CouponModelCalculation)
    {
        $this->CouponSpuList          = $CouponSpuList;
        $this->CouponModelDetail      = $CouponModelDetail;
        $this->CouponSpuUserList      = $CouponSpuUserList;
        $this->CouponModelCalculation = $CouponModelCalculation;
    }
    
    /**
     * 动作执行器
     * @param array $params ['spu_id' , 'mobile']
     * @param int $status
     * @param int $toolType
     * @return bool
     */
    public function execute(array $params , int $status = CouponStatus::CouponTypeStatusIssue , int $toolType = 1)
    {
        //获取用户与此SPU关联的优惠券
        if($params['mobile']){
            $time = time();
            $spuUserCoupons = $this->CouponSpuUserList->execute($params,$status);
            foreach ($spuUserCoupons as $key => $coupon){
                //用户未使用的正常的优惠券不在$spuCouponsModelNos中的，需查询出来并merge到$spuCoupons中
                if($coupon['status'] == 1 || $coupon['coupon_type'] == CouponStatus::CouponTypeVoucher){
                    unset($spuUserCoupons[$key]);
                }
            }
            $spuCoupons = array_merge($spuUserCoupons,[]);
        
            //查询商品SPU
            $spu = Tool::getSpu($params['spu_id']);
            //计算优惠信息
            if($spu){
                $amount = ($spu['min_price'] * $spu['min_month']) * 100; //月租金*租期;
                $spuCoupons = $this->CouponModelCalculation->execute($amount , $spu['min_price'] , $spuCoupons);
                set_apistatus(ApiStatus::CODE_0, '');
                return $spuCoupons;
            }
        }
        set_apistatus(ApiStatus::CODE_50000, 'mobile不能为空');
        return [];
    }
}