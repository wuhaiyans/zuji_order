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
     * [
     *   'goods_no'      => '', string  商品编号
     *   'order_no'      => '', string  订单编号
     *   'business_key'  => '', int     业务类型
     *   'reason_id'     => '', int     退换货id
     *   'reason_text'   => '', string  退货说明
     *   'user_id'       => '', int     用户id
     *   'status'        => '', int     退换货单状态
     *   'refund_no'     => '', string  退换货单号
     *   'pay_amount'    =>'' ,         实付金额
     *   'auth_unfreeze_amount'  => '', 应退押金
     *   'refund_amount'  => '' ,       应退金额
     *   'create_time'   => '', int    创建时间
     *
     *
     * ]
     * @return  bool
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
     * [
     *   'order_no'      => '', string  订单编号
     *   'business_key'  => '', int     业务类型
     *   'user_id'       => '', int     用户id
     *   'status'        => '', int     退换货单状态
     *   'refund_no'     => '', string  退款单号
     *   'pay_amount'    =>'' ,         实付金额
     *   'auth_unfreeze_amount'  => '', 应退押金
     *   'refund_amount'  => '' ,       应退金额
     *   'create_time'   => '', int    创建时间
     * ]
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
    public static function get_list(array $where,array $additional){
        $parcels = DB::table('order_return')
            ->leftJoin('order_info','order_return.order_no', '=', 'order_info.order_no')
            ->leftJoin('order_goods',[['order_return.order_no', '=', 'order_goods.order_no'],['order_return.goods_no', '=', 'order_goods.goods_no']])
            ->where($where)
            ->select('order_return.create_time as c_time','order_return.*','order_info.*','order_goods.goods_name','order_goods.zuqi')
            ->orderBy('order_return.create_time', 'DESC')
            ->paginate($additional['size'],$columns = ['*'], $pageName = 'page', $additional['page']);
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
    public static function getReturnList(array $where){
        $parcels = DB::table('order_return')
            ->leftJoin('order_info','order_return.order_no', '=', 'order_info.order_no')
            ->leftJoin('order_goods',[['order_return.order_no', '=', 'order_goods.order_no'],['order_return.goods_no', '=', 'order_goods.goods_no']])
            ->where($where)
            ->select('order_return.create_time as c_time','order_return.*','order_info.*','order_goods.goods_name','order_goods.zuqi')
            ->orderBy('order_return.create_time', 'DESC')
            ->get();
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
    public static function  getPayNo(int $business_type,string $business_no){
        $Data=OrderPayModel::where([['business_type','=',$business_type],['business_no','=',$business_no]])->first();
        if(!$Data){
            return false;
        }
        return $Data->toArray();
    }
    /**
     * 获取退换货单数据
     * @param $where  条件参数
     **[
     *   'order_no'    =>'' ,订单编号   string   【必传】
     *   'business_key'=>'',业务类型    int      【必传】
     *   'status'      =>'',退货单状态  int
     *   'evaluation_status' =>'', 检测状态   int    注：退货单状态和检测状态二传一即可
     * ]
     * @return array
     */
    public static function returnApplyList(array $where){
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

    /**
     * 获取订单的商品信息
     * @param $order_no
     * @return bool|\Illuminate\Support\Collection
     */
    public static function getGoodsInfo(string $order_no){
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
    public static function returnCheckingCount(){
        $where[]=["status","=",ReturnStatus::ReturnCreated];
        $where[]=["business_key","=",OrderStatus::BUSINESS_RETURN];
        $getReturn=orderReturn::where($where)->count();
        return $getReturn;
    }

    /**
     * 获取已取消除外的退货单信息
     * @param $order_no
     * @param $gods_no
     */
    public static function returnList(string $order_no,string $goods_no){
        $where[]=['goods_no','=',$goods_no];
        $where[]=['order_no','=',$order_no];
        $where[]=['status','!=',ReturnStatus::ReturnCanceled];  //状态不为已取消
        $getReturn=orderReturn::where($where)->first();
        if(!$getReturn){
            return false;
        }
        return $getReturn->toArray($getReturn);

    }



}