<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserInfo;

class OrderUserInfoRepository
{

    private $orderUserInfo;

    public function __construct()
    {
        $this->orderUserInfo = new OrderUserInfo();
    }
    public function add($data){

        $id =$this->orderUserInfo->insertGetId($data);
        return $id;

    }
    //更新物流单号
    public function update($params){
        $order_no=$params['order_no'];
        $delivery=array(
            'delivery_sn'=>$params['delivery_sn'],
            'delivery_type'=>$params['delivery_type']
        );
        if($this->orderUserInfo::where('order_no','=',$order_no)->update($delivery)){
            return $order_no;
        }else{
            return false;
        }
    }

}