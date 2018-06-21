<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\OrderReturn;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderGoodsExtend;
use App\Order\Models\Order;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderPayModel;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\ReturnStatus;
use App\Order\Modules\Inc\OrderGoodStatus;
use Illuminate\Support\Facades\DB;

class OrderReturnRepository
{
    private $orderReturn;
    public function __construct(orderReturn $orderReturn)
    {
        $this->orderReturn = $orderReturn;
    }

    /**
     * 获取退货单信息,根据退货单id
     * @param $params
     *
     */
    public static function getReturnInfo($id){
        $where[]=['id','=',$id];
        $res=orderReturn::where($where)->first();
        if(!$res){
           return false;
        }
        return $res->toArray();
    }

    /**
     * 创建退换货单
     * @param array $data
     *
     */
   public static function createReturn(array $data){
       $createReturn=orderReturn::query()->insert($data);
       if(!$createReturn){
           return false;
       }
       return true;
   }

    /**
     * 创建退款单
     * @param array $data
     * @return bool
     */
   public static function createRefund(array $data){
       $createRefund=orderReturn::query()->insert($data);
       if(!$createRefund){
           return false;
       }
       return true;
   }

    /**
     * 查询退货、退款列表
     * @param $where
     * @param $additional
     * @return array
     *
     */
    public static function get_list($where,$additional){
        $additional['page'] = ($additional['page'] - 1) * $additional['limit'];
        $parcels = DB::table('order_return')
            ->leftJoin('order_info','order_return.order_no', '=', 'order_info.order_no')
            ->leftJoin('order_goods',[['order_return.order_no', '=', 'order_goods.order_no'],['order_return.goods_no', '=', 'order_goods.goods_no']])
            ->where($where)
            ->select('order_return.create_time as c_time','order_return.*','order_info.*','order_goods.goods_name','order_goods.zuqi')
            ->paginate($additional['limit'],$columns = ['*'], $pageName = '', $additional['page']);
        if($parcels){
            return $parcels->toArray();
        }
        return [];
    }
    /**
     * 导出 查询退货、退款列表
     * @param $where
     * @param $additional
     * @return array
     *
     */
    public static function getReturnList($where){
        $parcels = DB::table('order_return')
            ->leftJoin('order_info','order_return.order_no', '=', 'order_info.order_no')
            ->leftJoin('order_goods',[['order_return.order_no', '=', 'order_goods.order_no'],['order_return.goods_no', '=', 'order_goods.goods_no']])
            ->where($where)
            ->select('order_return.create_time as c_time','order_return.*','order_info.*','order_goods.goods_name','order_goods.zuqi')->get();
        if($parcels){
            return $parcels->toArray();
        }
        return [];
    }
    /**
     * 获取订单支付编号信息
     * @param $business_type
     * @param $business_no
     */
    public static function  getPayNo($business_type,$business_no){
        $Data=OrderPayModel::where([['business_type','=',$business_type],['business_no','=',$business_no]])->first();
        if(!$Data){
            return false;
        }
        return $Data->toArray();
    }
    /**
     * 获取退换货单数据
     * @param $where
     *
     */
    public static function returnApplyList($where){
        $return_result= DB::table('order_return')
            ->leftJoin('order_goods', [['order_return.order_no', '=', 'order_goods.order_no'],['order_return.goods_no', '=', 'order_goods.goods_no']])
            ->where($where)
            ->select('order_goods.*','order_return.*')
            ->get()->toArray();
        if(!$return_result){
            return [];
        }
        return $return_result;
    }
    public static function getGoodsInfo($order_no){
        $where[]=['order_no','=',$order_no];
        $getGoods=OrderGoods::where($where)->get();
        if(!$getGoods){
            return false;
        }
        return $getGoods;
    }

    /**
     * 获取退货待审核的数量
     * @param $where
     */
    public static function returnCount($where){
        $getReturn=orderReturn::where($where)->count();
        return $getReturn;
    }



}