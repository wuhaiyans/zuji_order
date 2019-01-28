<?php
namespace App\Tools\Modules\Service\Coupon\CouponModel;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Repository\Coupon\CouponModelRepository;
use App\Lib\Tool\Tool;
use App\Tools\Modules\Service\Coupon\CouponSpu\CouponSpuCreate;

/**
 * 创建优惠券模型
 * @author Gaobo
 *
 */
class CouponModelCreate
{
    protected $CouponModelRepository = [];
    protected $CouponSpuCreate = [];
    
    /**
     * 解析注入依赖
     * @param CouponModelRepository $CouponModelRepository
     * @param CouponSpuCreate $CouponSpuCreate
     */
    public function __construct(CouponModelRepository $CouponModelRepository , CouponSpuCreate $CouponSpuCreate)
    {
        $this->CouponModelRepository = $CouponModelRepository;
        $this->CouponSpuCreate = $CouponSpuCreate;
    }
    
    /**
     * 动作执行器
     * @param array $params
     * @return array
     */
    public function execute(array $params)
    {
        //1.参数校验
        $checked = self::checkAddParams($params);
        if($checked){
            //2.设置参数
            $params = self::setAddParams($params);
            //3.组织模型数据
            $modelNo = func::md5_16();
            $modelData = [
                'coupon_name'      => $params['coupon_name'],
                'coupon_type'      => $params['coupon_type'],
                'coupon_value'     => $params['coupon_value'],
                'use_restrictions' => $params['use_restrictions'],
                'range_user'       => $params['range_user'],
                'start_time'       => $params['start_time'],
                'end_time'         => $params['end_time'],
                'user_start_time'  => $params['user_start_time'],
                'user_end_time'    => $params['user_end_time'],
                'user_day'         => $params['user_day'],
                'site'             => $params['site'],
                'describe'         => $params['describe'],
                'status'           => CouponStatus::CouponTypeStatusRough,
                'issue_num'        => $params['issue_num'],
                'create_time'      => time(),
            ];
            
            DB::beginTransaction();
            if(is_array($params['scope'])){ //如果指定渠道全场
                $addData = [];
                foreach($params['scope'] as $key=>$val){
                    $modelData['scope']    = $val;
                    $modelData['only_id']  = Func::md5_16();
                    $modelData['model_no'] = Func::md5_16();
                    $addData[] = $modelData;
                }
                $result = $this->CouponModelRepository->add($addData);
                if(!$result){
                    DB::rollBack();
                    set_apistatus(ApiStatus::CODE_50000, '模型保存失败');
                    return [];
                }
            }elseif($params['scope'] == 0){
                $modelData['scope']    = 0;
                $modelData['only_id']  = Func::md5_16();
                $modelData['model_no'] = $modelNo;
                if(isset($params['range_spu']) && $params['range_spu']){//如果指定商品
                    foreach(explode(',',$params['range_spu']) as $key=>$spu_id){
                        $couponSpuData[] = [
                            'model_no' => $modelNo,
                            'spu_id'   => $spu_id,
                            //'channel_id'=>$channel_id,
                        ];
                    }
                    $result = $this->CouponSpuCreate->execute($couponSpuData);
                    if(!$result){
                        DB::rollBack();
                        set_apistatus(ApiStatus::CODE_50000, '模型商品信息保存失败');
                        return [];
                    }
                    $result = $this->CouponModelRepository->add($modelData);
                }
            }elseif($params['scope'] == -1){
                $modelData['scope']    = -1;
                $modelData['only_id']  = Func::md5_16();
                $modelData['model_no'] = $modelNo;
                $result = $this->CouponModelRepository->add($modelData);
            }
            DB::commit();
            set_apistatus(ApiStatus::CODE_0, '');
            return [];
        }
        return [];
    }
    
    private static function checkAddParams(array $params)
    {
        if(count($params)<6){
            set_apistatus(\App\Lib\ApiStatus::CODE_20001, '参数错误');
            return [];
        }
        
        if(empty($params['start_time'])){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券领取时间不能为空');
            return [];
        }
        if(empty($params['end_time'])){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券领取时间不能为空');
            return [];
        }
        
        if(empty($params['user_day'])){
            if(empty($params['user_end_time'])){
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券核销时间不能为空');
                return [];
            }
            if(empty($params['user_start_time'])){
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券核销时间不能为空');
                return [];
            }
            
            if( $params['end_time'] > $params['user_end_time'] ){
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券领取时间大于核销时间');
                return [];
            }
            if($params['user_start_time'] >= $params['end_time']){
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '用户核销的开始时间不能大于等于优惠券领取的结束时间');
                return [];
            }
        }
        
        if(empty($params['issue_num'])){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券发放数量不能为空');
            return [];
        }
        return true;
    }
    
    private static function setAddParams(array $params)
    {
        if($params['coupon_type'] == CouponStatus::CouponTypePercentage || $params['coupon_type'] == CouponStatus::CouponTypeFirstMonthRentFree){
            $data['coupon_value'] = $params['coupon_value'];
        }elseif ($params['coupon_type'] == CouponStatus::CouponTypeFullReduction){
            $data['coupon_value']     = $params['coupon_value']*100;
            $data['use_restrictions'] = $params['use_restrictions']*100;
        }else{
            $data['coupon_value'] = $params['coupon_value']*100;
        }
        
        if(empty($params['user_day'])){
            if( $params['end_time'] > $params['user_end_time'] ){
                set_apistatus(ApiStatus::CODE_50000,'优惠券领取时间大于核销时间');
                return false;
            }
            if($params['user_start_time'] >= $params['end_time']){
                set_apistatus(ApiStatus::CODE_50000,'用户核销的开始时间不能大于等于优惠券领取的结束时间');
                return false;
            }
        }
        
        if($params['site'] == CouponStatus::SiteActive){
            $params['start_time']      = empty($params['start_time']) ? 0 : strtotime($params['start_time']);
            $params['end_time']        = empty($params['end_time']) ? 0 : strtotime($params['end_time']);
            $params['user_start_time'] = empty($params['user_start_time']) ? 0 : strtotime($params['user_start_time']);
            $params['user_end_time']   = empty($params['user_end_time']) ? 0 : strtotime($params['user_end_time']);
            $params['user_day']        = empty($params['user_day']) ? 0 : $params['user_day'];
            $params['issue_num']       = empty($params['issue_num']) ? 0 : $params['issue_num'];
        }elseif ($params['site'] == CouponStatus::SiteOut){
            $params['user_start_time'] = empty($params['user_start_time']) ? 0 : strtotime($params['user_start_time']);
            $params['user_end_time']   = empty($params['user_end_time']) ? 0 : strtotime($params['user_end_time']);
            $params['issue_num']       = empty($params['issue_num']) ? 0 : $params['issue_num'];
        }
        
        if($params['issue_num'] > 10000){
            $params['issue_num'] = 10000;
        }
        
        return $params;
    }
}