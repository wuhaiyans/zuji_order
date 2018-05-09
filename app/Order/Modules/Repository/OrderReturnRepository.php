<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\OrderReturn;
use App\Order\Models\OrderGoods;
use App\Order\Models\Order;
use App\Order\Modules\Inc\ReturnStatus;
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
        $status='1';
        $return_info=orderReturn::where(['order_no'=>$order_no,'goods_no'=>$goods_no,'status'=>$status])->get()->toArray();
        if(empty($return_info)){

            return true;
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
    //更新商品状态
    public static function goods_update($order_no){
        if(ordergoods::where('order_no','=',$order_no)->update(['goods_status'=>'1'])){
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
}