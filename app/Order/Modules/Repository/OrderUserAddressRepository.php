<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderRisk;
use App\Order\Models\OrderUserAddress;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderUserRisk;

class OrderUserAddressRepository
{

    public static function add($data){
        $data =OrderUserAddress::create($data);
        return $data->getQueueableId();
    }


    /**
     *
     * 修改地址信息
     * Author: heaven
     * @param $params
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


        if(OrderUserAddress::where('order_no','=', $params['order_no'])->update($data)){
            return true;
        }else{
            return false;
        }
    }

}