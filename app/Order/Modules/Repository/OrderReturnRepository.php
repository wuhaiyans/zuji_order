<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\OrderReturn;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderGoodExtend;
use App\Order\Models\Order;
use App\Order\Models\OrderUserInfo;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\ReturnStatus;
use Illuminate\Support\Facades\DB;

class OrderReturnRepository
{

    private $orderReturn;
    private $order;
    private $ordergoods;
    private $ordergoodextend;
    private $OrderUserInfo;
    public function __construct(orderReturn $orderReturn,order $order,ordergoods $ordergoods,ordergoodextend $ordergoodextend,OrderUserInfo $OrderUserInfo)
    {
        $this->orderReturn = $orderReturn;
        $this->ordergoods = $ordergoods;
        $this->ordergoodextend = $ordergoodextend;
        $this->OrderUserInfo = $OrderUserInfo;
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
        $goods_no=explode(',',$params['goods_no']);
        foreach($goods_no as $k=>$v){
            $where[$k][]=['goods_no','=',$v];
            $where[$k][]=['order_no','=',$params['order_no']];
            $where[$k][]=['user_id','=',$params['user_id']];
            $return_info[]=orderReturn::where($where[$k])->first();
        }
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
        $parcels = DB::table('order_return')
            ->leftJoin('order_userinfo', 'order_return.order_no', '=', 'order_userinfo.order_no')
            ->leftJoin('order_info','order_return.order_no', '=', 'order_info.order_no')
            ->leftJoin('order_goods',[['order_return.order_no', '=', 'order_goods.order_no'],['order_return.goods_no', '=', 'order_goods.goods_no']])
            ->where($where)
            ->select('order_return.create_time as c_time','order_return.*','order_userinfo.*','order_info.*','order_goods.goods_name','order_goods.zuqi')
            ->paginate($additional['limit'],$columns = ['*'], $pageName = '', $additional['page']);
        if($parcels){
            return $parcels->toArray();
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
           return  OrderReturn::where($where)->first()->toArray();
        }else{
            return false;
        }

    }
    //更新商品状态-申请退货|申请退款
    public static function goods_update_status($params){
        if(empty($params['order_no'])){
            return false;
        }
        if(isset($params['goods_no'])){
            $goods_no=explode(',',$params['goods_no']);
            foreach($goods_no as $k=>$v){
                $where[$k][]=['goods_no','=',$v];
                $where[$k][]=['order_no','=',$params['order_no']];
                $data['goods_status']=ReturnStatus::ReturnCreated;
                $update_result=ordergoods::where($where[$k])->update($data);
            }

        }else{
            $where[]=['order_no','=',$params['order_no']];
            $data['goods_status']=ReturnStatus::ReturnCreated;
            $update_result=ordergoods::where($where)->update($data);
        }
        if($update_result){
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
    //更新商品状态-退款-审核同意
    public static function goodsupdate($params){
        if(isset($params['goods_no'])){
            $where[]=['goods_no','=',$params['goods_no']];
        }
        if(empty($params['order_no'])){
            return false;
        }

        $where[]=['order_no','=',$params['order_no']];
        $data['goods_status']=ReturnStatus::ReturnTui;
        if(ordergoods::where($where)->update($data)){
            return true;
        }else{
            return false;
        }
    }
    //更新商品状态-退货-审核拒绝
    public static function deny_goods_update($params){
        if(isset($params['goods_no'])){
            $where[]=['goods_no','=',$params['goods_no']];
        }
        if(empty($params['order_no'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        $data['goods_status']=ReturnStatus::ReturnDenied;
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
            ->leftJoin('order_good_extend', function ($join){
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
    //检测合格与否-修改退货状态
    public static function is_qualified($where,$data){
        if(OrderReturn::where($where)->update($data)){
          return true;
        }else{
            return false;
        }
    }

    public static function is_tui_qualified($where,$data){
        if(OrderReturn::where($where)->update($data)){
            return true;
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
      /*  if(empty($params['user_id'])){
            return false;
        }*/
        $where[]=['order_no','=',$params['order_no']];
     //   $where[]=['user_id','=',$params['user_id']];
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
    //检测合格与不合格-》修改商品状态
    public static function updategoods($where,$params){
       $goods_res= OrderGoods::where($where)->update($params);
        if(!$goods_res){
            return false;
        }

        /*$goods_extend_res= ordergoodextend::where($extend_where)->update(['status'=>'1']);//修改商品扩展表商品状态为无效
        if(!$goods_extend_res){
            return false;
        }*/
        return true;
    }
    //查询退货单类型
   /* public static function get_type($where){
        $res=OrderReturn::where($where)->first()->toArray();
        if($res){
            return $res;
        }else{
            return false;
        }
    }*/
    //获取订单信息
    public static function order_info($order_no){
        if(empty($order_no)){
            return false;
        }
        $orderData=Order::where('order_no','=',$order_no)->first()->toArray();
        if($orderData){
            return $orderData;
        }else{
            return false;
        }
    }
    //创建换货单记录
    public static function createchange($params){
        if (isset($param['order_no']) && isset($param['good_id']) &&  isset($param['good_no']) &&  isset($param['serial_number'])){
            return false;//参数错误
        }
        $create_result=ordergoodextend::query()->insert($params);
        if($create_result){
            return true;
        }else{
            return false;
        }
    }
    //退款成功更新退款状态
    public static function updateStatus($params){
        if(empty($params['order_no'])){
            return false;
        }
        if(isset($params['goods_no'])){
            $where[]=['goods_no','=',$params['goods_no']];
        }
        $where[]=['order_no','=',$params['order_no']];
        //更新退款单状态
        $data['status']=ReturnStatus::ReturnTuiKuan;
        $return_result= OrderReturn::where($where)->update($data);
        if(!$return_result){
            return false;
        }
        return true;
    }
    //退款成功更新商品状态
    public static function updategoodsStatus($params){
        if(empty($params['order_no'])){
            return false;
        }
        if(isset($params['goods_no'])){
            $where[]=['goods_no','=',$params['goods_no']];
        }
        $where[]=['order_no','=',$params['order_no']];
        $data['goods_status']=ReturnStatus::ReturnTuiKuan;
        $goods_result= OrderGoods::where($where)->update($data);
        if(!$goods_result){
            return false;
        }
        return true;
    }
    //退款成功更新订单状态
    public static function updateorderStatus($params){
        if(empty($params['order_no'])){
            return false;
        }
        $where[]=['order_no','=',$params['order_no']];
        //更新退款单状态
        $data['order_status']=OrderStatus::OrderRefunded;
        $order_result= Order::where($where)->update($data);//修改商品扩展表商品状态为无效
        if(!$order_result){
            return false;
        }
        return true;
    }
    //获取退货单信息
    public static function get_type($where){
        if(empty($where)){
            return false;
        }
        $orderData=OrderReturn::where($where)->first()->toArray();
        if($orderData){
            return $orderData;
        }else{
            return false;
        }
    }
    //获取下单用户的user_id
    public static function get_user_info($mobile){
        if(empty($mobile)){
            return false;
        }
        $userData=OrderUserInfo::where('user_mobile','=',$mobile)->first()->toArray();
        if($userData){
            return $userData;
        }else{
            return false;
        }
    }
}