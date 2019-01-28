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
        $spuCoupons = $this->CouponSpuList->execute($params , $toolType , $status);
        $spuCouponsModelNos = [];
        if($spuCoupons){
            $spuCouponsModelNos = array_column($spuCoupons, 'model_no');
        }
        //获取用户与此SPU关联的优惠券
        if($params['mobile']){
            $time = time();
            $spuUserCoupons = $this->CouponSpuUserList->execute($params,$status);
            foreach ($spuUserCoupons as $key => $coupon){
                //用户未使用的正常的优惠券不在$spuCouponsModelNos中的，需查询出来并merge到$spuCoupons中
                if($coupon['status'] == 0 && $coupon['end_time'] >= $time){
                    if(!in_array($coupon['model_no'] , $spuCouponsModelNos)){
                        //查询model 设置已领取 并 push $spuCoupons
                        $couponModel = $this->CouponModelDetail->execute($coupon['model_no'])->toArray();
                        $couponModel['user_had'] = 1;
                        array_push($spuCoupons, $couponModel);
                    }else{
                        //设置已领取
                        $spuCouponsKey = array_keys($spuCouponsModelNos,$coupon['model_no'])[0];
                        $spuCoupons[$spuCouponsKey]['user_had'] = 1;
                    }
                }else{//用户已使用的和失效的要从$spuCoupons中删除
                    $spuCouponsKey = array_keys($spuCouponsModelNos,$coupon['model_no']);
                    if( isset($spuCouponsKey[0]) && isset($spuCoupons[$spuCouponsKey[0]]) ){
                        unset($spuCoupons[$spuCouponsKey[0]]);
                    }
                }
            }
            $spuCoupons = array_merge($spuCoupons,[]);
        }
        
        //查询商品SPU
        $spu = Tool::getSpu($params['spu_id']);
        //计算优惠信息
        if($spu){
            $amount = ($spu['min_price'] * $spu['min_month']) * 100; //月租金*租期;
            $spuCoupons = $this->CouponModelCalculation->execute($amount , $spu['min_price'] , $spuCoupons);
            set_apistatus(ApiStatus::CODE_0, '');
            return $spuCoupons;
        }
        
        return [];
    }
}