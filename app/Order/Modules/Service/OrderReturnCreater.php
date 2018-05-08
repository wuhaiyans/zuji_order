<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderReturnRepository;

class OrderReturnCreater
{

    protected $orderReturnRepository;
   
    public function __construct(orderReturnRepository $orderReturnRepository)
    {
        
        $this->orderReturnRepository = $orderReturnRepository;
        
    }
    public function get_return_info($data){
        return $this->orderReturnRepository->get_return_info($data);
    }
    //添加退换货数据
    public function add($data){
        $to_data['order_no']=$data['order_no'];
        $to_data['goods_no']=$data['goods_no'];
        $to_data['user_id']=$data['user_id'];
        $to_data['business_key']=$data['business_key'];
        $to_data['loss_type']=$data['loss_type'];
        $to_data['reason_id']=$data['reason_id'];
        $to_data['reason_text']=$data['reason_text'];
        $to_data['status']='1';
        $to_data['remark']=$data['remark'];
        $to_data['create_time']=time();
        return $this->orderReturnRepository->add($to_data);

    }
}