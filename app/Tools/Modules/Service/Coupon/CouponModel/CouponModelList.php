<?php
namespace App\Tools\Modules\Service\Coupon\CouponModel;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Repository\Coupon\CouponModelRepository;
use App\Lib\Tool\Tool;
use App\Tools\Modules\Repository\Coupon\CouponSpuRepository;
use App\Tools\Modules\Repository\Coupon\CouponUserRepository;
use App\Tools\Models\CouponSpuModel;

class CouponModelList
{
    protected $CouponModelRepository = [];
    protected $CouponSpuRepository   = [];
    protected $CouponUserRepository   = [];
    

    public function __construct(CouponModelRepository $CouponModelRepository , CouponSpuRepository $CouponSpuRepository , CouponUserRepository $CouponUserRepository)
    {
        $this->CouponModelRepository = $CouponModelRepository;
        $this->CouponSpuRepository   = $CouponSpuRepository;
        $this->CouponUserRepository  = $CouponUserRepository;
    }
    
    public function execute(array $params)
    {
        $where  = self::setWhere($params);
        
        $limit = (isset($params['size']) && is_numeric($params['size'])) ? $params['size'] : 20;
        $page = (isset($params['page']) && is_numeric($params['page'])) ? $params['page'] : 1;
        
        $couponModelList = $this->CouponModelRepository->list($where , $limit , $page)->toArray();
        $couponModelList['page'] = $couponModelList['current_page'];
        $couponModelList['pagesize'] = $limit;
        foreach ($couponModelList['data'] as $key=>$value){
            if($value['status'] == CouponStatus::CouponTypeStatusIssue){
                $couponModelList['data'][$key]['fafang_stop'] = true;
                $couponModelList['data'][$key]['reissue']     = true;
                $couponModelList['data'][$key]['export']      = true;
                $couponModelList['data'][$key]['fafang']      = false;
                $couponModelList['data'][$key]['huidu']       = false;
                $couponModelList['data'][$key]['del']         = false;
                $couponModelList['data'][$key]['huidu']       = false;
                $couponModelList['data'][$key]['stoptest']    = false;
            }elseif ($value['status'] == CouponStatus::CouponTypeStatusRough){
                $couponModelList['data'][$key]['fafang']      = true;
                $couponModelList['data'][$key]['huidu']       = true;
                $couponModelList['data'][$key]['del']         = true;
                $couponModelList['data'][$key]['fafang_stop'] = false;
                $couponModelList['data'][$key]['export']      = false;
                $couponModelList['data'][$key]['reissue']     = false;
                $couponModelList['data'][$key]['stoptest']    = false;
            }elseif($value['status'] == CouponStatus::CouponTypeStatusTest){
                $couponModelList['data'][$key]['fafang']      = true;
                $couponModelList['data'][$key]['stoptest']    = true;
                $couponModelList['data'][$key]['fafang_stop'] = false;
                $couponModelList['data'][$key]['huidu']       = false;
            }else{
                $couponModelList['data'][$key]['fafang_stop'] = false;
                $couponModelList['data'][$key]['fafang']      = false;
                $couponModelList['data'][$key]['huidu']       = false;
                $couponModelList['data'][$key]['reissue']     = false;
                $couponModelList['data'][$key]['stoptest']    = false;
            }
            
            $couponModelList['data'][$key]['create_time'] = date('Y-m-d h:i:s',$value['create_time']);
            $couponModelList['data'][$key]['coupon_type'] = CouponStatus::get_coupon_type_name($value['coupon_type']);
            $couponModelList['data'][$key]['range_user']  = CouponStatus::get_coupon_range_name($value['range_user']);
            $couponModelList['data'][$key]['site']        = CouponStatus::get_coupon_type_site($value['site']);
            $couponModelList['data'][$key]['status']      = CouponStatus::get_coupon_type_status($value['status']);
            $spu_name_all = '';
            $channel_name_all = '';
            if($value['scope'] > 0){//全渠道
                //接口获取
                $channel_name_all = Tool::getChannel(['id'=>$value['scope']]);
            }elseif($value['scope'] == 0){//指定商品
                $channel_id = CouponSpuModel::query()->where(['model_no'=>$value['model_no']])->get(['channel_id'])->groupBy('channel_id');
                $channel_id = array_keys($channel_id->toArray());
                $channel_name_all = Tool::getChannel(['id'=>['in',join($channel_id,',')]]);
                $spuids = $this->CouponSpuRepository->getListByWhere(['model_no'=>$value['model_no']] , ['spu_id'])->toArray();
                $spuids = array_column($spuids, 'spu_id');
                $spu_name_all = Tool::getSpuNames(['id'=>['in',implode($spuids, ',')]]);
            }elseif($value['scope'] == -1){//全场
                $channel_name_all = ['全场'];
            }
            
            if($spu_name_all){
                $couponModelList['data'][$key]['range_spu']=implode(',',$spu_name_all);
            }else{
                $couponModelList['data'][$key]['range_spu']=$spu_name_all;
            }
            $couponModelList['data'][$key]['remainder'] = $this->CouponUserRepository->getCount(['model_no'=>$value['model_no'],'is_lock'=>0]);
        }
        set_apistatus(ApiStatus::CODE_0, '');
        return $couponModelList;
    }
    
    private static function setWhere(array $params) : array
    {
        $where = [];
        if(isset($params['coupon_type'])){
            $where = ['coupon_type' => $params['coupon_type']];
        }
        //领取方式
        if(isset($params['site'])){
            $where = array_merge($where,['site' => $params['site']]);
        }
        //发放状态
        if( isset($params['status']) && $params['status'] !== ''){
            $where = array_merge($where,['status' => $params['status']]);
        }
        //关键字
        if(isset($params['keywords'])){
            $where = array_merge($where,['keywords' => [ 'like' => '%'.$params['keywords'].'%']]);
        }
        
        //以下可优化为between and
        //创建优惠券的查询开始时间
        if(isset($params['start_time'])){
            $where = array_merge($where,['create_time' => ["gt"=>strtotime($params['start_time'])]]);
        }
        //创建优惠券的查询结束时间
        if(isset($params['end_time'])){
            $where = array_merge($where,['create_time' => ["lt"=>strtotime($params['end_time'])]]);
        }
        
        return $where;
    }
}