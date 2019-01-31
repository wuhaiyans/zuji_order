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

class CouponUserList
{
    protected $CouponUserRepository = [];
    protected static $couponModelFields = [
        'tool_coupon_model.model_no','tool_coupon_model.start_time as model_start_time'
        ,'tool_coupon_model.end_time as model_end_time','tool_coupon_model.coupon_name'
        ,'tool_coupon_model.coupon_type','tool_coupon_model.coupon_value'
        ,'tool_coupon_model.use_restrictions','tool_coupon_model.issue_num'
        ,'tool_coupon_model.user_start_time','tool_coupon_model.user_end_time'
        ,'tool_coupon_model.user_day','tool_coupon_model.describe','tool_coupon_model.status as modelstatus'
    ];
    protected static $couponUserFields = [
                                             'tool_user.mobile','tool_user.status'
                                            ,'tool_user.start_time','tool_user.end_time'
                                            ,'tool_user.use_time'
    ];

    public function __construct(CouponUserRepository $CouponUserRepository)
    {
        $this->CouponUserRepository = $CouponUserRepository;
    }
    
    /**
     * 
     * @param array $params $params['mobile'],$params['type'];
     * @param int $status
     * @param int $toolType
     * @return array|array
     */
    public function execute(array $params , int $status = CouponStatus::CouponTypeStatusIssue , int $toolType = 1 )
    {
        //1.检查参数
        if(!isset($params['mobile']) || !isset($params['type'])){
            set_apistatus(\App\Lib\ApiStatus::CODE_20001,'参数错误');
            return [];
        }
        //2.组织条件
        $time = time();
        if($params['type'] == CouponStatus::CouponStatusAlreadyUsed){//已使用
            $leftJoin['leftJoin'] = [['tool_coupon_model','tool_user.model_no','=','tool_coupon_model.model_no']];
            $where = [
                ['tool_user.mobile','=',$params['mobile']]
                ,['tool_user.status','=',CouponStatus::CouponStatusAlreadyUsed]
            ];
            $orderBy = [['use_time'=>'DESC']];
        }
        if($params['type'] == CouponStatus::CouponStatusNotUsed){//未使用
            $leftJoin['leftJoin'] = [['tool_coupon_model','tool_user.model_no','=','tool_coupon_model.model_no']];
            $where = [
                ['tool_user.mobile','=',$params['mobile']]
                ,['tool_user.status','=',CouponStatus::CouponStatusNotUsed]
                ,['tool_user.end_time','>=',$time]
                ,['tool_coupon_model.status','=',$status]
            ];
            $orderBy = [['end_time'=>'DESC']];
        }
        if($params['type'] == CouponStatus::CouponStatusExpire){//已失效
            $leftJoin['leftJoin'] = [['tool_coupon_model','tool_user.model_no','=','tool_coupon_model.model_no']];
            $where = [
                ['tool_user.mobile','=',$params['mobile']]
                ,['tool_user.status','=',CouponStatus::CouponStatusNotUsed]
                ,['tool_user.end_time','<',$time]
                ,['tool_coupon_model.status','=',$status]
            ];
            $orderBy = [['end_time'=>'DESC']];
        }
        $fields = array_merge(self::$couponModelFields,self::$couponUserFields);
        //3.获取数据
        set_apistatus(ApiStatus::CODE_0, '');
        return $this->CouponUserRepository->getUserCoupons($where,$leftJoin,$orderBy,$fields)->toArray();
    }
}