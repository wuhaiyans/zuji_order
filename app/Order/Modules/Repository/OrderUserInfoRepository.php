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
        return $this->orderUserInfo->insertGetId($data);
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

    /**
     * 获取用户信息
     * @param array $params
     * [
     *      'user_id'=>''//用戶ID
     *      'order_no'=>''//订单编号
     * ]
     * @return bool
     */

    public static function getUserInfo($params){
        //根据用户id
        if (isset($param['user_id']) && !empty($param['user_id'])) {

            $whereArray[] = ['user_id', '=', $param['user_id']];
        }
        //根据订单编号
        if (isset($param['order_no']) && !empty($param['order_no'])) {

            $whereArray[] = ['order_no', '=', $param['order_no']];
        }

        $data = OrderUserInfo::query()->where($whereArray)->get()->toArray();
        return !empty($orderData) ?? false;
    }


    /**
     * 获取用户信息
     * @param array $params
     * [
     *      'user_id'=>''//用戶ID
     *      'order_no'=>''//订单编号
     * ]
     * @return bool
     */

    public static function modifyAddress($params){
        if (isset($params['mobile'])) {
            $data['mobile']    =   $params['mobile'];
        }

        if (isset($params['name'])) {
            $data['name']    =   $params['name'];
        }

        if (isset($params['address_info'])) {
            $data['address_info']    =   $params['address_info'];
        }


        if(OrderUserInfo::where(['order_no', '=', $params['order_no']],['id', '=', $params['order_address_id']])->update($data)){
            return true;
        }else{
            return false;
        }
    }

}