<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\OrderReturn;
class OrderReturnRepository
{

    private $orderReturn;

    public function __construct(orderReturn $orderReturn)
    {
        $this->orderReturn = $orderReturn;
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
}