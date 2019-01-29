<?php
namespace App\Tools\Modules\Repository\Coupon;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Models\CouponModel;
use App\Tools\Models\CouponSpuModel;

class CouponSpuRepository
{
    protected $couponSpu = [];
    
    public function __construct(CouponSpuModel $couponSpu)
    {
        $this->couponSpu = $couponSpu;
    }
    
    public function toArray()
    {
        return $this->couponSpu->toArray();
    }
    
    /**
     * 支持一次添加多个 (一个[],多个[[],[]])
     * @param array $data
     * @return bool
     */
    public function add(array $data)
    {
        $r = CouponSpuModel::query()->insert($data);
        return $r;
    }
    
    public function getListByWhere(array $where , $columns = ['*'])
    {
        return CouponSpuModel::query()->where($where)->get($columns);
    }
    
    /**
     * 
     * @param array $where
     * @param array $join
     * @param array $orderBy
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\static[]|unknown
     */
    public function getSpuCoupons(array $where = [] , array $join = ['leftJoin'=>[],'innerJoin'=>[]] , array $orderBy = [] , array $fields = ['*'])
    {
        $query = CouponSpuModel::query();
        
        if(isset($join['leftJoin'])){
            foreach ($join['leftJoin'] as $key=>$val){
                $query->leftJoin($val[0],$val[1],$val[2],$val[3]);
            }
        }
        if(isset($join['innerJoin'])){
            foreach ($join['innerJoin'] as $key=>$val){
                $query->join($val[0],$val[1],$val[2],$val[3]);
            }
        }
            
        if($where){
            $query->where($where);
        }
        if($orderBy){
            foreach ($orderBy as $key=>$val){
                foreach ($val as $k=>$v){
                    $query->orderBy($k,$v);
                }
            }
        }
        return $query->get($fields);
        
        return CouponSpuModel::query()
        ->leftJoin('tool_coupon_model','tool_user.model_no','=','tool_coupon_model.model_no')
        ->where([['tool_user.mobile','=','18612709162'],['tool_user.status','=',1]])
        ->get($fields);
    }

}