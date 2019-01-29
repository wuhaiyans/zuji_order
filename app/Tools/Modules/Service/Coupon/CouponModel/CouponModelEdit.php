<?php
namespace App\Tools\Modules\Service\Coupon\CouponModel;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Modules\Inc\CouponStatus;
use App\Lib\Tool\Tool;

/**
 * 修改优惠券FIXME
 * @author Gaobo
 *
 */
class CouponModelEdit
{
    protected $CouponModelDetail = [];

    /**
     * 解析注入依赖
     * @param CouponModelDetail $CouponModelDetail
     */
    public function __construct(CouponModelDetail $CouponModelDetail)
    {
        $this->CouponModelDetail = $CouponModelDetail;
    }
    
    /**
     * 动作执行器
     * @param array $params
     * @return array
     */
    public function execute(array $params) : array 
    {
        if(!isset($params['model_no']) || !$params['model_no']){
            set_apistatus(ApiStatus::CODE_20001, '参数错误');
            return [];
        }
        $couponModel = $this->CouponModelDetail->execute($params['model_no']);
        if(!$couponModel){
            return [];
        }
        //修改草稿状态下的优惠券任意字段  或 修改其他状态下的特定字段
        if($couponModel->status == CouponStatus::CouponTypeStatusRough){
            $couponModel->status  = \App\Tool\Modules\Inc\CouponStatus::CouponTypeStatusIssue;
            if($couponModel->save()){
                $data = $couponModel->toArray();
                $this->CouponUserCreate->execute($data,$data['issue_num']);
                set_apistatus(ApiStatus::CODE_0, '');
            }else{
                set_apistatus(ApiStatus::CODE_50000, '保存失败');
            }
            return [];
        }else{
            
        }
        set_apistatus(ApiStatus::CODE_50000, '优惠券模型状态错误');
        return [];
    }
}