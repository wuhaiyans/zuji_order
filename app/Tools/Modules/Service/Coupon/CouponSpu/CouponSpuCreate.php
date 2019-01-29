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

/**
 * 创建商品SPU与优惠券关联信息
 * @author Gaobo
 * 不暴露,移至repository中
 */
class CouponSpuCreate
{
    protected $CouponSpuRepository = [];
    
    /**
     * 注入repository依赖
     * @param CouponSpuRepository $CouponSpuRepository
     */
    public function __construct(CouponSpuRepository $CouponSpuRepository)
    {
        $this->CouponSpuRepository  = $CouponSpuRepository;
    }
    
    /**
     * 动作执行器     一次插入一个,格式为一维数组;一次插入多个,格式为二维数组
     * @param array $params
     * @return bool
     */
    public function execute(array $params) : bool
    {
        if($params){
            return $this->CouponSpuRepository->add($params);
        }
        return false;
    }
}