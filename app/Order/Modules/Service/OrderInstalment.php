<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderInstalmentRepository;

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
    public function create($params){
        $order      = $params['order'];
        $sku        = $params['sku'];
        $coupon    = $params['coupon'];
        $user     = $params['coupon'];

        $order = filter_array($order, [
            'order_no' => 'required',    //【必须】int；订单号
        ]);
        if(!$order['order_no']){
            return false;
        }
        $sku = filter_array($sku, [
            'order_no' => 'required',    //【必须】int；订单号
        ]);

        if(count($sku) < 8){
            return false;
        }

        $coupon = filter_array($coupon, [
            'order_no' => 'required',    //【必须】int；订单号
        ]);
        if(count($coupon) < 2){
            return false;
        }

        $user = filter_array($user, [
            'order_no' => 'required',    //【必须】int；订单号
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
    public function get_data_schema($params){
        $sku        = $params['sku'];
        $coupon    = $params['coupon'];
        $user     = $params['coupon'];


        $sku = filter_array($sku, [
            'zuqi'=>'required',
            'zuqi_type'=>'required',
            'all_amount'=>'required',
            'amount'=>'required',
            'yiwaixian'=>'required',
            'zujin'=>'required',
            'yiwaixian'=>'required',
            'payment_type_id'=>'required',
        ]);
        if(count($sku) < 8){
            return false;
        }

        $coupon = filter_array($coupon, [
            'discount_amount'=>'required',
            'coupon_type'=>'required',
        ]);
        if(count($coupon) < 2){
            return false;
        }

        $user = filter_array($user, [
            'withholding_no' => 'required',    //【必须】int；订单号
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