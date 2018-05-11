<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\OrderReturn;
use App\Order\Models\OrderGoods;
use App\Order\Models\Order;
use App\Order\Modules\Inc\ReturnStatus;
use Illuminate\Support\Facades\DB;
class OrderReturnRepository
{

    private $orderReturn;
    private $order;
    private $ordergoods;
    public function __construct(orderReturn $orderReturn,order $order,ordergoods $ordergoods)
    {
        $this->orderReturn = $orderReturn;
        $this->ordergoods = $ordergoods;
        $this->order = $order;
    }
    public static function get_return_info($data){
        $order_no=$data['order_no'];
        $goods_no=$data['goods_no'];
        $return_info=orderReturn::where(['order_no'=>$order_no,'goods_no'=>$goods_no])->get()->toArray();
        if(is_array($return_info)){
            return $return_info;
        }else{
            return false;
        }
    }
    //添加退货申请
    public static function add($data){
        $ret =OrderReturn::query()->insert($data);
        if(!$ret){
            return false;
        }
        return $data;
    }
    //查询退货列表
    public static function get_list($where,$additional){
        $additional['page'] = ($additional['page'] - 1) * $additional['limit'];
        $parcels=DB::table('order_return')
            ->leftJoin('order_goods', function ($join) {
                $join->on('order_return.order_no', '=', 'order_goods.order_no');
            })
            ->where($where)
            ->offset($additional['page'])->limit($additional['limit'])
            ->select('order_return.*','order_goods.*')
            ->get();
        if($parcels){
            return $parcels;
        }
        return [];
    }

    //更新退换货审核状态 同意
    public static function update_return($params){
        $id=$params['id'];
        $data['remark']=$params['remark'];
        $data['status']=$params['status'];
        $data['check_time']=time();
        $data['update_time']=time();
        if(OrderReturn::where('id','=',$id)->update($data)){
            return true;
        }else{
            return false;
        }

    }
    //更新商品状态-
    public static function goods_update($order_no){
        if(ordergoods::where('order_no','=',$order_no)->update(['goods_status'=>'1'])){
            return true;
        }else{
            return false;
        }
    }
    //用户取消退货更新商品状态
    public static function cancel_goods_update($order_no){
        if(ordergoods::where('order_no','=',$order_no)->update(['goods_status'=>'0'])){
            return true;
        }else{
            return false;
        }
    }
    //更新退换货审核状态->拒绝
    public static function deny_return($params){
        $id=$params['id'];
        $order_no=$params['order_no'];
        $data['remark']=$params['remark'];
        $data['status']="3";
        $data['update_time']=time();
        if(OrderReturn::where('id','=',$id)->update($data)){
            return true;
        }else{
            return false;
        }

    }
    //取消退货申请
    public static function cancel_apply($params){
        if(empty($params['order_no'])){
            return false;
        }
        $data['status']='4';
        $order_no=$params['order_no'];
        if(OrderReturn::where('order_no','=',$order_no)->update($data)){
            return true;
        }else{
            return false;
        }
    }
    //获取退货单信息
    public static function get_info_by_order_no($order_no){
        $return_info=orderReturn::where('order_no','=',$order_no)->get()->toArray();
        if($return_info){
            return $return_info;
        }else{
            return false;
        }
    }
    //获取商品信息
    public static function get_goods_info($order_no){
        if(empty($order_no)){
            return false;
        }
        $return_info=ordergoods::where('order_no','=',$order_no)->get()->toArray();
        if($return_info){
            return $return_info;
        }else{
            return false;
        }
    }
    //上传退货物流单号
    public static function upload_wuliu($data){
        if(empty($data['order_no'])){
            return false;
        }
        if(empty($data['logistics_no'])){
            return false;
        }
        if(empty($data['wuliu_channel_id'])){
            return false;
        }
        $params['wuliu_channel_id']=$data['wuliu_channel_id'];
        $params['logistics_no']=$data['logistics_no'];
        if(OrderReturn::where('order_no','=',$data['order_no'])->update($params)){
            $res=OrderReturn::where('order_no','=',$data['order_no'])->first()->toArray();
            return $res;
        }else{
            return false;
        }

    }

    //获取退换货订单信息
  /*  public static function getOrderList($param = array())
    {
        if (empty($param)) {
            return false;
        }
        if (isset($param['user_id']) && !empty($param['user_id']))
        {

            $orderData = DB::table('order_return')
                ->leftJoin('order_goods', function ($join) {
                    $join->on('order_return.order_no', '=', 'order_goods.order_no');
                })
                ->where('order_return.user_id', '=', $param['user_id'])
                ->select('order_return.*','order_goods.*')
                ->get();
            return $orderData->toArray();
        }

    }*/
    //退货结果查看
    public static function returnResult($params){
        if(empty($params['order_no'])){
            return false;
        }
        if(empty($params['goods_no'])){
            return false;
        }
         $result=OrderReturn::where($params)->get();
         if($result){
             return $result->toArray();
         }else{
             return false;
         }
    }
    //检测合格-修改退货状态
    public static function is_qualified($params){
        if(empty($params['order_no'])){
            return false;
        }
        if(empty($params['goods_no'])){
            return false;
        }
        if(empty($params['status'])){
            return false;
        }
        $result=OrderReturn::where(['order_no'=>$params['order_no'],'goods_no'=>$params['goods_no']])->update(['status'=>$params['status']]);
        if($result){
            $orderData=OrderGoods::where('goods_no','=',$params['goods_no'])->first();
            return $orderData->toArray();
        }else{
            return false;
        }
    }

}