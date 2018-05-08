<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderInstalmentRepository;
use App\Lib\ApiStatus;
class OrderInstalment
{

    /**
     * 创建订单分期
     * @return array
     *  $array = [
    'order'=>[
    'order_no'=>1,
    ],
    'sku'=>[
    'zuqi'=>1,
    'zuqi_type'=>1,
    'all_amount'=>1,
    'amount'=>1,
    'yiwaixian'=>1,
    'zujin'=>1,
    'yiwaixian'=>1,
    'payment_type_id'=>1,
    ],
    'coupon'=>[
    'discount_amount'=>1,
    'coupon_type'=>1,
    ],
    'user'=>[
    'withholding_no'=>1,
    ],
    ];
     */
    public static function create($params){

        $order    = $params['order'];
        $sku      = $params['sku'];
        $coupon   = !empty($params['coupon']) ? $params['coupon'] : "";
        $user     = $params['user'];

        $order = filter_array($order, [
            'order_no' => 'required',
        ]);

        if(!$order['order_no']){
            return false;
        }

        //获取sku
        $sku = filter_array($sku, [
            'goods_no'=>'required',
            'zuqi'=>'required',
            'zuqi_type'=>'required',
            'all_amount'=>'required',
            'amount'=>'required',
            'yiwaixian'=>'required',
            'zujin'=>'required',
            'pay_type'=>'required',
        ]);
        if(count($sku) < 8){

            return false;
        }

        filter_array($coupon, [
            'discount_amount' => 'required',
            'coupon_type' => 'required',
        ]);


        $user = filter_array($user, [
            'withholding_no' => 'required',
        ]);
        if(count($user) < 1){
            return false;
        }

        $res = new OrderInstalmentRepository($params);
        return $res->create();

    }


    /**
     * 获取分期数据
     * @return array
     *  $array = [
    'order'=>[
    'order_no'=>1,
    ],
    'sku'=>[
    'zuqi'=>1,
    'zuqi_type'=>1,
    'all_amount'=>1,
    'amount'=>1,
    'yiwaixian'=>1,
    'zujin'=>1,
    'yiwaixian'=>1,
    'payment_type_id'=>1,
    ],
    'coupon'=>[
    'discount_amount'=>1,
    'coupon_type'=>1,
    ],
    'user'=>[
    'withholding_no'=>1,
    ],
    ];
     */
    public static function get_data_schema($params){
        $sku      = $params['sku'];
        $coupon   = !empty($params['coupon']) ? $params['coupon'] : "";
        $user     = $params['user'];


        $sku = filter_array($sku, [
            'zuqi'=>'required',
            'zuqi_type'=>'required',
            'all_amount'=>'required',
            'amount'=>'required',
            'yiwaixian'=>'required',
            'zujin'=>'required',
            'pay_type'=>'required',
        ]);
        if(count($sku) < 7){
            return false;
        }

        filter_array($coupon, [
            'discount_amount'=>'required',
            'coupon_type'=>'required',
        ]);

        $user = filter_array($user, [
            'withholding_no' => 'required',
        ]);
        if(count($user) < 1){
            return false;
        }

        $res = new OrderInstalmentRepository($params);
        return $res->get_data_schema();


    }

    /**
     * 根据goods_no查询分期数据
     * @return array
     */
    public static function queryByInstalmentId($id){
        if (empty($id)) {
            return false;
        }

        $result =  OrderInstalmentRepository::getInfoById($id);
        return $result;
    }


    /**
     * 查询分期数据
     * @return array
     */
    public static function queryList($params = []){
        if (!is_array($params)) {
            return ApiStatus::CODE_20001;
        }
        $params = filter_array($params, [
            'goods_no'=>'required',
            'order_no'=>'required',
        ]);

        $result =  OrderInstalmentRepository::queryList($params);
        $result = array_group_by($result,'goods_no');

        return $result;
    }


    /**
     * 根据用户id和订单号，关闭用户的分期
     * @return array
     */
    public static function close($data){
        if (!is_array($data) || $data == [] ) {
            return false;
        }

        $result =  OrderInstalmentRepository::closeInstalment($data);
        return $result;
    }

    /**
     * 是否允许扣款
     * @param  int  $instalment_id 订单分期付款id
     * @return bool true false
     */
    public function allow_withhold($instalment_id){
        if(empty($instalment_id)){
            return false;
        }
        $alllow = 0;
        $instalment_info = OrderInstalmentRepository::getInfoById($instalment_id);
        p($instalment_info);
        $status = $instalment_info['status'];

        $year   = date("Y");
        $month  = intval(date("m"));
        if($month < 10 ){
            $month = "0".$month;
        }
        $term 	= $year.$month;
        $day 	= intval(date("d"));

        // 是否有扣款记录
        $fund_auth_record = $this->fund_auth_record_table->where(['instalment_id'=>$instalment_id,'status'=>1])->find();
        //查询订单记录
        $order_info = $this->order_service->get_order_info(['order_id'=>$instalment_info['order_id']]);

        if(!$fund_auth_record && ($status == Instalment::UNPAID || $status == Instalment::FAIL)){
            // 本月15后以后 可扣当月 之前没有扣款的可扣款
            if(($term == $instalment_info['term'] && $day >= 15) || $term > $instalment_info['term']){
                //判断订单状态 必须是租用中 或者完成关闭的状态 才允许扣款
                if($order_info['status']== \oms\state\State::OrderInService || $order_info['status'] == oms\state\State::OrderClosed){
                    $alllow = 1;
                }
            }
        }


        return $alllow;
    }

}