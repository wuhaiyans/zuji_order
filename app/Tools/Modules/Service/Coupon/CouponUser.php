<?php
namespace App\Tool\Modules\Service\Coupon;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tool\Modules\Repository\Coupon\Coupon;
use App\Tool\Modules\Service\Coupon\CouponServiceInterface;
use App\Tool\Models\CouponModel;
use App\Tool\Modules\Repository\Coupon\CouponUser as CouponUserRepository;
use App\Tool\Modules\Repository\GreyTest\GreyTest;
use App\Tool\Modules\Func\Func;
use App\Tool\Modules\Inc\CouponStatus;
use App\Tool\Modules\Repository\Coupon\CouponSpu;
use App\Lib\Tool\Tool;

class CouponUser
{
    protected $couponUser = [];
    
    protected static $couponModelFields = [
        'tool_coupon_model.model_no','tool_coupon_model.start_time'
        ,'tool_coupon_model.end_time','tool_coupon_model.coupon_name'
        ,'tool_coupon_model.coupon_type','tool_coupon_model.coupon_value'
        ,'tool_coupon_model.use_restrictions','tool_coupon_model.issue_num'
        ,'tool_coupon_model.user_start_time','tool_coupon_model.user_end_time'
        ,'tool_coupon_model.user_day','tool_coupon_model.describe','tool_coupon_model.status'
    ];
    
    protected static $couponUserFields = ['tool_user.mobile','tool_user.status','tool_user.start_time','tool_user.end_time','tool_user.use_time'];

    public function __construct(CouponUserRepository $couponUser)
    {
        $this->couponUser = $couponUser;
    }
    
    /**
     * 获取couponModel元数据
     * @return array
     */
    public function getData() : array
    {
        return $this->couponUser->toArray();
    }
    
    public static function getUserCouponById(int $id) : CouponUser
    {
        $coupon = CouponUserRepository::getOne($id);
        return new self($coupon);
    }
    
    /**
     * 核销
     * @return boolean
     */
    public function writeOff()
    {
        if($this->couponUser->getAttribute('status')==CouponStatus::CouponStatusNotUsed){
            $this->couponUser->setAttribute('status', CouponStatus::CouponStatusAlreadyUsed);
            return $this->couponUser->save();
        }
        return false;
    }
    
    /**
     * 撤销
     * @return boolean
     */
    public function cancel()
    {
        if($this->couponUser->getAttribute('status')==CouponStatus::CouponStatusAlreadyUsed){
            $this->couponUser->setAttribute('status', CouponStatus::CouponStatusNotUsed);
            return $this->couponUser->save();
        }
        return false;
    }
    
    /**
     * 领取
     * @return boolean
     */
    public function receive(string $mobile , string $modelNo)
    {
        $couponModel = Coupon::getDetailByNo($modelNo);
        $where = ['model_no'=>$modelNo,'status'=>0,'mobile'=>'0','is_lock'=>CouponStatus::CouponLockWei];
        $coupon = CouponUserRepository::getOneOnWhere($where);
        if(!$coupon){
            return false;
        }
        $coupon->setAttribute('is_lock', CouponStatus::CouponLockSuo);
        $coupon->setAttribute('mobile', $mobile);
        $coupon->setLock();
        
        //计算使用时间
        //保存
        
        
        $load = \hd_load::getInstance();
        $coupon_table= $load->table('coupon/coupon2');
        $coupon_type_table = $load->table('coupon/coupon_type2');
        
        $time = time();
        
        $where = ['id'=>$coupon_id];
        $coupon_type = $coupon_type_table->where($where)->find();
        
        if(!$coupon_type){
            return ['code'=>0,'data'=>'无此优惠券'];
        }
        if($coupon_type['status'] != $status){
            return ['code'=>0,'data'=>'无效优惠券'];
        }
        if($coupon_type['end_time'] < $time){
            return ['code'=>0,'data'=>'优惠券已过期'];
        }
        if($coupon_type['start_time'] > $time){
            return ['code'=>0,'data'=>'优惠券未发布'];
        }
        
        $user_had = $coupon_table->where(['coupon_type_id'=>$coupon_type['id'],'user_id'=>$user_id])->find();
        if($user_had){
            return ['code'=>0,'data'=>'领取失败,已领取过此优惠券'];
        }
        
        //获取一个code
        $code_table = $load->table('coupon/coupon_code_item');
        $code = $code_table->where("opid=".$coupon_type['id']." AND start_time<=".$time." AND end_time>=".$time." AND is_lock=0 AND status=0")->find();
        
        if(!$code){
            return ['code'=>0,'data'=>'领取失败,优惠券数量不足！'];
        }
        
        $model = new \model();
        $tran = $model->startTrans();
        if (!$tran) {
            Debug::error(Location::L_Member, '系统繁忙', '调用锁定兑换码,开启事物失败');
            return ['code'=>0,'data'=>'系统繁忙'];
        }
        
        $ret = $code_table->where(['id'=>$code['id']])->save(['is_lock'=>1]);
        if($ret){
            $use_status =  self::useExchangeCode($user_id, $code['code'] , $status);
            if($use_status['code'] == 0){
                $model->rollback();
                return ['code'=>0,'data'=>$use_status['data']];
            }elseif($use_status['code'] == 1){
                $model->commit();
                return ['code'=>1,'data'=>$use_status['data']];
            }
        }else{
            $model->rollback();
            return ['code'=>0,'data'=>'兑换码锁定失败'];
        }
        
    }
    
}