<?php
namespace App\Tools\Modules\Repository\Coupon;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Models\CouponModel;

class CouponModelRepository
{
    protected $couponModel = [];
    
    public function __construct(CouponModel $couponModel)
    {
        $this->couponModel = $couponModel;
    }
    
    public function getDetailByModelNo(string $modelNo) : CouponModel
    {
        return CouponModel::query()->findOrNew($modelNo);
    }
    
    public function saveCoupon()
    {
        return $this->couponModel->save();
    }
    
    public function toArray()
    {
        return $this->couponModel->toArray();
    }
    
    /**
     * coupon_model 列表
     * @param array $whereArray
     * @param int $offset
     * @param number $limit
     * @return array
     */
    public static function list(array $whereArray = ['status'=>1] , int $limit = 20 , int $page = 1 , array $columns = ['*'])
    {
        $result = CouponModel::query()->where($whereArray)->orderBy('create_time' , 'desc')->paginate($limit,$columns,'',$page);
        return $result;
    }
    
    /**
     * 支持一次添加多个 (一个[],多个[[],[]])
     * @param array $data
     * @return bool
     */
    public static function add(array $data) : bool
    {
        $r = CouponModel::query()->insert($data);
        return $r;
    }
    
    public function getColumnsNames() {
        return (array) $this->couponModel->getConnection()->getSchemaBuilder()->getColumnListing($this->couponModel->getTable());
    }

}