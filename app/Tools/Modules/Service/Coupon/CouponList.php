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
use App\Tool\Modules\Repository\Coupon\CouponUser;
use App\Tool\Modules\Repository\GreyTest\GreyTest;
use App\Tool\Modules\Func\Func;
use App\Tool\Modules\Inc\CouponStatus;
use App\Tool\Modules\Repository\Coupon\CouponSpu;
use App\Lib\Tool\Tool;

class CouponList
{
    protected $couponList = [];
    
    protected static $couponModelFields = [
        'tool_coupon_model.model_no','tool_coupon_model.start_time'
        ,'tool_coupon_model.end_time','tool_coupon_model.coupon_name'
        ,'tool_coupon_model.coupon_type','tool_coupon_model.coupon_value'
        ,'tool_coupon_model.use_restrictions','tool_coupon_model.issue_num'
        ,'tool_coupon_model.user_start_time','tool_coupon_model.user_end_time'
        ,'tool_coupon_model.user_day','tool_coupon_model.describe','tool_coupon_model.status'
    ];
    
    protected static $couponUserFields = ['tool_user.mobile','tool_user.status','tool_user.start_time','tool_user.end_time','tool_user.use_time'];

    public function __construct(coupon $couponModel)
    {
        $this->couponList = $couponModel;
    }
    
    /**
     * 获取couponModel元数据
     * @return array
     */
    public function getData() : array
    {
        return $this->couponList->toArray();
    }
    
    /**
     * 获取用户tools
     * @param array $params ['mobile'=>'18612709166','type'=>1]
     * @param int $toolType
     * @param int $status
     * @return Coupon
     */
    public static function getUserCoupons(array $params , int $toolType = 1 , int $status = CouponStatus::CouponTypeStatusIssue)
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
        $fields = self::$couponUserFields;
        //3.获取数据
        return CouponUser::getUserCoupons($where,$leftJoin,$orderBy,$fields)->toArray();
    }
    
    /**
     * 获取SPU的优惠券列表
     * @param array $params
     * @param int $toolType
     * @param int $status
     * @return array|array
     */
    public static function getSpuCoupons(array $params , int $toolType = 1 , int $status = CouponStatus::CouponTypeStatusIssue)
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
        $orderBy = [['end_time'=>'DESC']];
        $fields = self::$couponModelFields;
        $spuCoupons = CouponSpu::getSpuCoupons($where,$leftJoin,$orderBy,$fields)->toArray();
        $spuCouponsModelNos = [];
        if($spuCoupons){
            $spuCouponsModelNos = array_column($spuCoupons, 'model_no');
        }
        
        //获取用户与此SPU关联的优惠券
        if($params['mobile']){
            $spuUserCoupons = self::getUserSpuCoupons($params , $status);
            foreach ($spuUserCoupons as $key => $coupon){
                //用户未使用的正常的优惠券不在$spuCouponsModelNos中的，需查询出来并merge到$spuCoupons中
                if($coupon['status'] == 0 && $coupon['end_time'] >= $time){
                    if(!in_array($coupon['model_no'] , $spuCouponsModelNos)){
                        //查询model 设置已领取 并 push $spuCoupons
                        $couponModel = CouponService::getCouponModelDetailByNo($coupon['model_no'])->getData();
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
        $amount = ($spu['min_price'] * $spu['min_month']) * 100; //月租金*租期;
        $spuCoupons = self::Calculation($amount , $spu['min_price'] , $spuCoupons);
        return $spuCoupons;
    }
    
    /**
     * 下单券int $user_id , int $sku_id , int $appid , $zuqi = 0 , int $status=1
     * @param array $params
     * @param int $toolType
     * @param int $status
     * @return array
     */
    public static function getUserSpuCouponsWhenOrder(array $params , int $toolType = 1 , int $status = CouponStatus::CouponTypeStatusIssue)
    {
        //1.检查参数
        if(!isset($params['sku_id']) || !isset($params['mobile'])){
            set_apistatus(\App\Lib\ApiStatus::CODE_20001,'参数错误');
            return [];
        }
        
        $time = time();
        $sku = Tool::getSku($params['sku_id']);
        $spu = Tool::getSpu($sku['sku_id']);
        
        //获取用户与此SPU关联的优惠券
        $params['spu_id'] = $spu['id'];
        $spuUserCoupons = self::getUserSpuCoupons($params , $status);
        
        if($params['zuqi']){
            $amount = ($sku['shop_price'] * $params['zuqi'] ) * 100; //月租金*租期;
        }else{
            $amount = ($sku['shop_price'] * $sku['zuqi'] ) * 100; //月租金*租期;
        }
        
        foreach($spuUserCoupons as $key => $coupon){
            //去掉租金抵用券
            if($coupon['coupon_type'] == CouponStatus::CouponTypeVoucher){
                unset($spuUserCoupons[$key]);
                continue;
            }
            //去掉不满足的满减券
            if( $coupon['use_restrictions'] > $amount ){
                unset($spuUserCoupons[$key]);
                continue;
            }
        }
        
        $spuUserCoupons = self::Calculation($amount , $sku['shop_price'] , $spuUserCoupons);
        return $spuUserCoupons;
    }
    
    /**
     * 支付券
     * @param array $params
     * @param int $toolType
     * @param int $status
     * @return array
     */
    public static function getUserSpuCouponsWhenPay(array $params , int $toolType = 1 , int $status = CouponStatus::CouponTypeStatusIssue)
    {
        //检查参数
        if(!isset($params['spu_id']) || !isset($params['mobile'])){
            set_apistatus(\App\Lib\ApiStatus::CODE_20001,'参数错误');
            return [];
        }
        $time = time();
        
        $leftJoin['leftJoin'] = [['tool_coupon_model','tool_user.model_no','=','tool_coupon_model.model_no']];
        $where = [
            ['tool_user.mobile','=',$params['mobile']]
            ,['tool_user.status','=',CouponStatus::CouponStatusNotUsed]
            ,['tool_user.start_time','<=',$time]
            ,['tool_user.end_time','>=',$time]
            ,['tool_coupon_model.coupon_type','=',CouponStatus::CouponTypeVoucher]
            ,['tool_coupon_model.status','=',$status]
        ];
        $orderBy = [];
        $fields = array_merge(self::$couponModelFields,[
            'tool_user.status as user_status'
            ,'tool_user.start_time as use_start_time','tool_user.end_time as use_end_time'
        ]);
        //获取数据
        $coupons = CouponUser::getUserCoupons($where,$leftJoin,$orderBy,$fields)->toArray();
        //获取最优券
        $coupons = self::Calculation(0,0,$coupons);
        return $coupons[0];
    }
    
    
    public static function Calculation(int $amount = 0 , int $min_price = 0 , array $coupons)
    {
//         if(!$amount && !$min_price){
//             return [];
//         }
        //初始化商品总金额及月租
        $couponTypeIg = new CouponCalculation($amount , $min_price);
        $tempk = [];
        $coupons_spu_had = array_merge($coupons_spu_had,[]);
        foreach($coupons as $key=>$coupon){
            $couponTypeIg->setLimit($coupon['use_restrictions']);
            $couponTypeIg->setCouponValue($coupon['coupon_value']);
            $coupons[$key]['ret_amount'] = $couponTypeIg->{algorithm.$coupon['coupon_type']}();
            $coupons[$key]['num'] = CouponUser::getCount(['model_no'=>$coupon['model_no'],'is_lock'=>0]);
            $tempk[] = $coupons[$key]['ret_amount'];
        }
        
        //最优优惠券算法
        asort($tempk);
        $coupons = array_merge(array_replace($tempk,$coupons),[]);
        return $coupons;
    }
    
    private static function getUserSpuCoupons(array $params , int $status = 1) : array 
    {
        $join['innerJoin'] = [['tool_user','tool_coupon_spu.model_no','=','tool_user.model_no']];
        $join['leftJoin'] = [['tool_coupon_model','tool_coupon_spu.model_no','=','tool_coupon_model.model_no']];
        $where = [
            ['tool_coupon_spu.spu_id','=',$params['spu_id']]
            ,['tool_coupon_model.status','=',$status]
            ,['tool_user.mobile','=',$params['mobile']]
        ];
        $fields = array_merge(self::$couponModelFields,[
            'tool_user.status as user_status'
            ,'tool_user.start_time as use_start_time','tool_user.end_time as use_end_time'
        ]);
        $spuUserCoupons = CouponSpu::getSpuCoupons($where,$join,[],$fields)->toArray();
        return $spuUserCoupons;
    }
}