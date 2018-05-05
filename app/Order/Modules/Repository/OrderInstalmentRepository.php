<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\OrderInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;

class OrderInstalmentRepository
{



    public function __construct(){

    }

    public function create(){



    }

    /**
     *
     * 根据id查询信息
     *
     */
    public static function getInfoById($id){
        if (empty($id)) return false;
        $result =  OrderInstalment::query()->where([
            ['id', '=', $id],
        ])->first();
        if (!$result) return false;
        return $result->toArray();
    }

    /**
     *
     * 根据goods_no查询分期信息
     *
     */
    public static function getBygoodsNo($goods_no){
        if (empty($goods_no)) return false;
        $result =  OrderInstalment::query()->where([
            ['goods_no', '=', $goods_no],
        ])->get();
        if (!$result) return false;
        return $result->toArray();
    }

    /**
     * 关闭分期
     */
    public static function closeInstalment($data){

        if (!is_array($data) || $data == [] ) {
            return false;
        }
        $where = [];
        if(isset($data['order_no'])){
            $where .= ['order_no', '=', $data['order_no']];
        }
        if(isset($data['id'])){
            $where .= ['id', '=', $data['id']];
        }

        $status = ['status'=>OrderInstalmentStatus::CANCEL];
        $result =  Order::where($where)->save($status);
        if (!$result) return false;

        return true;

    }

}