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

class CouponSpuList
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
     * @param array $params ['spu_id']
     * @param int $status
     * @param int $toolType
     * @return array
     */
    public function execute(array $params , int $status = CouponStatus::CouponTypeStatusIssue , int $toolType = 1) : array
    {
        //1.检查参数
        if(!isset($params['spu_id'])){
            set_apistatus(\App\Lib\ApiStatus::CODE_20001,'参数错误');
            return [];
        }
        $time = time();
        //获取spu关联的优惠券
        $leftJoin['leftJoin'] = [['tool_coupon_model','tool_coupon_spu.model_no','=','tool_coupon_model.model_no']];
        $where = [
            ['tool_coupon_spu.spu_id','=',$params['spu_id']]
            ,['tool_coupon_model.start_time','<=',$time]
            ,['tool_coupon_model.end_time','>=',$time]
            ,['tool_coupon_model.status','=',$status]
            ,['tool_coupon_model.range_user','<',CouponStatus::DesignatedUser]
        ];
        
        if(isset($params['range_user'])){
            switch ($params['range_user']){
                case CouponStatus::RangeUserNew :
                    $where[] = ['tool_coupon_model.range_user','=',CouponStatus::RangeUserNew];
                    $where[] = ['tool_coupon_model.range_user_scope','=',CouponStatus::RangeUserScope];
                    break;
                case CouponStatus::RangeUserOld :
                    $where[] = ['tool_coupon_model.range_user','=',CouponStatus::RangeUserOld];
                    $where[] = ['tool_coupon_model.range_user_scope','=',CouponStatus::RangeUserScope];
                    break;
                case CouponStatus::RangeUserVisitor :
                    $where[] = ['tool_coupon_model.range_user','<',CouponStatus::DesignatedUser];
                    $where[] = ['tool_coupon_model.range_user_scope','=',CouponStatus::RangeUserScope];
                    break;
                default:
                    break;
            }
        }
        
        $orderBy = [['end_time'=>'DESC']];
        $fields = self::$couponModelFields;
        $spuCoupons = $this->CouponSpuRepository->getSpuCoupons($where,$leftJoin,$orderBy,$fields)->toArray();
        return $spuCoupons;
    }
}