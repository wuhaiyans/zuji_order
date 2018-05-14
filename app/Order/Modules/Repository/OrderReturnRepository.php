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
    //商品状态
    private $goods_reply = 1;
    private $goods_agree = 2;
    private $goods_Denied = 3;
    public function __construct(orderReturn $orderReturn,order $order,ordergoods $ordergoods)
    {
        $this->orderReturn = $orderReturn;
        $this->ordergoods = $ordergoods;
        $this->order = $order;
    }
    public static function get_return_info($params){
        if(empty($params['order_no'])){
            return false;
        }
        if(empty($params['user_id'])){
            return false;
        }
        if(empty($params['goods_no'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        $where[]=['user_id','=',$params['user_id']];
        $where[]=['goods_no','=',$params['goods_no']];
        $return_info=orderReturn::where($where)->first();
        if($return_info){
            return $return_info;
        }else{
            return false;
        }
    }
    //添加退货申请
    public static function add($data){
        if(OrderReturn::query()->insert($data)){
            return true;
        }else{
            return false;
        }

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
            ->paginate($additional['limit'],$columns = ['*'], $pageName = '', $additional['page']);
        if($parcels){
            return $parcels;
        }
        return [];
    }

    //更新退换货审核状态 同意
    public static function update_return($params){
        $where[]=['order_no','=',$params['order_no']];
        if(isset($params['goods_no'])){
           $where[]=['goods_no','=',$params['goods_no']];
        }
        $data['remark']=$params['remark'];
        $data['status']=ReturnStatus::ReturnAgreed;
        $data['check_time']=time();
        $data['update_time']=time();
        if(OrderReturn::where($where)->update($data)){
            return true;
        }else{
            return false;
        }

    }
    //更新商品状态-申请退货|申请退款
    public static function goods_update_status($params){
        if(isset($params['goods_no'])){
            $where[]=['goods_no','=',$params['goods_no']];
        }
        if(empty($params['order_no'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        $data['goods_status']=ReturnStatus::ReturnCreated;
        if(ordergoods::where($where)->update($data)){
            return true;
        }else{
            return false;
        }

    }
    //更新商品状态-退货-审核同意
    public static function goods_update($params){
        if(isset($params['goods_no'])){
            $where[]=['goods_no','=',$params['goods_no']];
        }
        if(empty($params['order_no'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        $data['goods_status']=ReturnStatus::ReturnAgreed;
        if(ordergoods::where($where)->update($data)){
            return true;
        }else{
            return false;
        }
    }
    //用户取消退货更新商品状态
    public static function cancel_goods_update($params){
        if(isset($params['goods_no'])){
            $where[]=['goods_no','=',$params['goods_no']];
        }
        if(empty($params['order_no'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        $data['goods_status']=ReturnStatus::ReturnCanceled;
        if(ordergoods::where($where)->update($data)){
            return true;
        }else{
            return false;
        }
    }
    //更新退换货审核状态->拒绝
    public static function deny_return($params){
        if(isset($params['goods_no'])){
            $where[]=['goods_no','=',$params['goods_no']];
        }
        if(empty($params['order_no'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        $data['remark']=$params['remark'];
        $data['status']=ReturnStatus::ReturnDenied;
        $data['update_time']=time();
        if(OrderReturn::where($where)->update($data)){
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
        $data['status']=ReturnStatus::ReturnCanceled;
        $order_no=$params['order_no'];
        if(OrderReturn::where('order_no','=',$order_no)->update($data)){
            return true;
        }else{
            return false;
        }
    }
    //获取退货单信息
    public static function get_info_by_order_no($params){
        if(empty($params['order_no'])){
            return false;
        }
        if(empty($params['user_id'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        $where[]=['user_id','=',$params['user_id']];
        $return_info=orderReturn::where($where)->first()->toArray();
        if($return_info){
            return $return_info;
        }else{
            return false;
        }
    }
    //获取商品信息
    public static function get_goods_info($params){

        if(isset($params['goods_no'])){
            $where[]=['order_goods.goods_no','=',$params['goods_no']];
        }
        if(empty($params['order_no'])){
            return false;
        }
        $where[]=['order_goods.order_no','=',$params['order_no']];
        $return_info=DB::table('order_goods')
            ->leftJoin('order_good_extend', function ($join) {
                $join->on([['order_goods.order_no','=','order_good_extend.order_no'],['order_goods.goods_no','=','order_good_extend.good_no']]);
            })
            ->where($where)
            ->select('order_good_extend.*','order_goods.*')
            ->get();;
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

    public static function update_return_info($order_no){
        if(OrderGoods::where('order_no','=',$order_no)->update(['goods_status'=>'1'])){
           return true;
        }else{
            return false;
        }
    }
    //申请退款->获取订单信息
    public static function get_order_info($params){
        if(empty($params['order_no'])){
            return false;
        }
        if(empty($params['user_id'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        $where[]=['user_id','=',$params['user_id']];
        $orderData=Order::where($where)->first()->toArray();
        if($orderData){
            return $orderData;
        }else{
            return false;
        }
    }
    //修改冻结类型
    public static function update_freeze($params,$freeze_type){
        if(empty($params['order_no'])){
            return false;
        }
        if(empty($params['user_id'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        $where[]=['user_id','=',$params['user_id']];
        $orderData=Order::where($where)->update(['freeze_type'=>$freeze_type]);
        if($orderData){
            return true;
        }else{
            return false;
        }
    }
    //申请退货-》获取订单信息
    public static function get_return($params){
        if(empty($params['order_no'])){
            return false;
        }
        if(empty($params['user_id'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        $where[]=['user_id','=',$params['user_id']];
        $orderData=Order::where($where)->first()->toArray();
        if($orderData){
            return $orderData;
        }else{
            return false;
        }
    }

}