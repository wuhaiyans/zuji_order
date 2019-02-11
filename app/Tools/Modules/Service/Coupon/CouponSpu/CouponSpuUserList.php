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

class CouponSpuUserList
{
    protected $CouponSpuRepository = [];
    protected $CouponModelDetail = [];
    protected static $couponModelFields = [
        'tool_coupon_model.model_no','tool_coupon_model.start_time'
        ,'tool_coupon_model.end_time','tool_coupon_model.coupon_name'
        ,'tool_coupon_model.coupon_type','tool_coupon_model.coupon_value'
        ,'tool_coupon_model.use_restrictions','tool_coupon_model.issue_num'
        ,'tool_coupon_model.user_start_time','tool_coupon_model.user_end_time'
        ,'tool_coupon_model.user_day','tool_coupon_model.describe','tool_coupon_model.status as modelstatus'
    ];
    
    public function __construct(CouponSpuRepository $CouponSpuRepository , CouponModelDetail $CouponModelDetail)
    {
        $this->CouponSpuRepository  = $CouponSpuRepository;
        $this->CouponModelDetail  = $CouponModelDetail;
    }
    
    /**
     * @param array $params ['mobile','spu_id']
     * @param int $status
     * @return array
     */
    public function execute(array $params , int $status = CouponStatus::CouponTypeStatusIssue) : array
    {
        if(!$params['spu_id'] || !$params['mobile']){
            return false;
        }
        $time = time();
        $join['innerJoin'] = [['tool_user','tool_coupon_spu.model_no','=','tool_user.model_no']];
        $join['leftJoin'] = [['tool_coupon_model','tool_coupon_spu.model_no','=','tool_coupon_model.model_no']];
        $where = [
             ['tool_coupon_spu.spu_id','=',$params['spu_id']]
            ,['tool_coupon_model.status','=',$status]
            ,['tool_user.mobile','=',$params['mobile']]
            ,['tool_user.start_time','<=',$time]
            ,['tool_user.end_time','>=',$time]
        ];
        
        $fields = array_merge(self::$couponModelFields,[
            'tool_user.status'
            ,'tool_user.start_time as use_start_time','tool_user.end_time as use_end_time'
        ]);
        $spuUserCoupons = $this->CouponSpuRepository->getSpuCoupons($where,$join,[],$fields)->toArray();
        return $spuUserCoupons;
    }
}