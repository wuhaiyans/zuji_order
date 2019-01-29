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
use App\Tools\Modules\Repository\Coupon\CouponUserRepository;

/**
 * 支付时的优惠券
 * @author Administrator
 *
 */
class CouponListWhenPay
{
    protected $CouponModelRepository = [];
    protected $CouponUserRepository  = [];
    protected static $couponModelFields = [
        'tool_coupon_model.model_no','tool_coupon_model.start_time'
        ,'tool_coupon_model.end_time','tool_coupon_model.coupon_name'
        ,'tool_coupon_model.coupon_type','tool_coupon_model.coupon_value'
        ,'tool_coupon_model.use_restrictions','tool_coupon_model.issue_num'
        ,'tool_coupon_model.user_start_time','tool_coupon_model.user_end_time'
        ,'tool_coupon_model.user_day','tool_coupon_model.describe','tool_coupon_model.status as modelstatus'
    ];
    
    public function __construct(CouponUserRepository $CouponUserRepository , CouponModelRepository $CouponModelRepository)
    {
        $this->CouponModelRepository = $CouponModelRepository;
        $this->CouponUserRepository  = $CouponUserRepository;
    }
    
    /**
     * 动作执行器
     * @param array $params ['mobile','spu_id']
     * @param int $status
     * @param int $toolType
     * @return array
     */
    public function execute(array $params , int $status = CouponStatus::CouponTypeStatusIssue , int $toolType = 1) : array
    {
        if(!$params['spu_id'] || !$params['mobile']){
            return false;
        }
        $time = time();
        $join['leftJoin'] = [['tool_coupon_model','tool_user.model_no','=','tool_coupon_model.model_no']];
        $where = [
             ['tool_user.mobile','=',$params['mobile']]
            ,['tool_coupon_model.coupon_type','=',CouponStatus::CouponTypeVoucher]
            ,['tool_coupon_model.status','=',$status]
            ,['tool_user.start_time','<=',$time]
            ,['tool_user.end_time','>=',$time]
        ];
        $fields = array_merge(self::$couponModelFields,[
            'tool_user.status' , 'tool_user.id','tool_user.coupon_no'
            ,'tool_user.start_time as use_start_time','tool_user.end_time as use_end_time'
        ]);
        $spuUserCoupons = $this->CouponUserRepository->getUserCoupons($where,$join,[],$fields)->toArray();
        //FIXME 需要后期优化
        foreach($spuUserCoupons as $key => $coupon){
            $arr[$coupon['id']] = $coupon;
        }
        if($arr){
            $one = [];
            foreach ($arr as $k=>$item){
                if($one){
                    if($one['coupon_value']<$item['coupon_value']){
                        $one = $item;
                        $one['coupon_id'] = $k;
                    }
                }else{
                    $one = $item;
                    $one['coupon_id'] = $k;
                }
            }
            set_apistatus(ApiStatus::CODE_0, '');
            //处理完成 FIXME 需要后期优化
            return [
                'youhui'=>$one['coupon_value'],
                'coupon_id'=>$one['coupon_id'],
                'coupon_name'=>$one['coupon_name'],
                'coupon_no'=>$one['coupon_no']
            ];
        }else{
            return ['code'=>50010,'data'=>'未找到优惠券'];
        }
    }
}