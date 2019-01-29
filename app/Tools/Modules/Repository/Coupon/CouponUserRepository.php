<?php
namespace App\Tools\Modules\Repository\Coupon;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Models\CouponModel;
use App\Tools\Models\CouponUserModel;

class CouponUserRepository
{
    protected $couponUser = [];
    
    public function __construct(CouponUserModel $couponUser)
    {
        $this->couponUser = $couponUser;
    }
    
    public function toArray()
    {
        return $this->couponUser->toArray();
    }
    
    public function getCount(array $where)
    {
        return CouponUserModel::query()->where($where)->get()->count();
    }
    
    public function getCoupon(array $where , $limit = 0)
    {
        if($limit){
            $c = CouponUserModel::query()->where($where)->limit($limit)->get();
        }else{
            $c = CouponUserModel::query()->where($where)->get();
        }
        
        return $c;
    }
    
    public static function getOneOnWhere(array $where , bool $is_lock = false)
    {
        $couponUserModel = CouponUserModel::query()->where($where);
        if($is_lock){
            $couponUserModel->lockForUpdate();
        }
        $couponUserModel = $couponUserModel->firstOrNew([]);
        return new self($couponUserModel);
    }
    
    public function getUserCoupons(array $where = [] , array $join = ['leftJoin'=>[],'innerJoin'=>[]] , array $orderBy = [] , array $fields = ['*'])
    {
        $query = CouponUserModel::query();
        
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
        
        return CouponUserModel::query()
                    ->leftJoin('tool_coupon_model','tool_user.model_no','=','tool_coupon_model.model_no')
                    ->where([['tool_user.mobile','=','18612709162'],['tool_user.status','=',1]])
                    ->get($fields);
    }
    
    public static function getOne(int $id) : self
    {
        return new self(CouponUserModel::query()->find($id));
    }
    
    public function setUserCoupon() : bool//可设置service实现更详细的功能
    {
        return $this->couponUser->save();
    }
    
    public function setLock()//可设置service实现更详细的功能
    {
        $this->couponUser->setAttribute('is_lock', 1);
        return $this->couponUser->save();
    }
    
    public function save()//可设置service实现更详细的功能
    {
        return $this->couponUser->save();
    }
    
    public static function addAll(array $data)
    {
        $r = CouponUserModel::query()->insert($data);
        return $r;
    }
    
    public function getAttribute($key)
    {
        return $this->couponUser->getAttribute($key);
    }
    
    public function setAttribute($key,$value)
    {
        return $this->couponUser->setAttribute($key,$value);
    }

    public function getColumnsNames() {
        return (array) $this->couponUser->getConnection()->getSchemaBuilder()->getColumnListing($this->couponUser->getTable());
    }
}