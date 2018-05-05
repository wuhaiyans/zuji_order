<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderInstalmentRepository;
use Illuminate\Support\Facades\DB;

class OrderInstalment
{
    /**
     * 创建订单分期
     * @return boolean
     */
    public function create($data){


    }

    /**
     * 根据goods_no查询分期数据
     * @return array
     */
    public function queryByInstalmentId($id){
        if (empty($id)) {
            return false;
        }

        $result =  OrderInstalmentRepository::getInfoById($id);
        return $result;
    }


    /**
     * 根据goods_no查询分期数据
     * @return array
     */
    public function queryByGoodsNo($goods_no){
        if (empty($goods_no)) {
            return false;
        }

        $result =  OrderInstalmentRepository::getBygoodsNo($goods_no);
        return $result;
    }


    /**
     * 根据用户id和订单号，关闭用户的分期
     * @return array
     */
    public function close($data){
        if (!is_array($data) || $data == [] ) {
            return false;
        }

        $result =  OrderInstalmentRepository::closeInstalment($data);
        return $result;
    }

}