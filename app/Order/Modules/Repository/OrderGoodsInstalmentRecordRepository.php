<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderGoodsInstalmentRecord;

class OrderGoodsInstalmentRepository
{


    public function __construct() {

    }

     /**
     * @param $data
     * [
     *     'instalment_id'              => 'required', //分期ID
     *      'type'                      => 'required', //类型；1：代扣；2：主动还款
     *      'payment_amount'            => 'required', //实际支付金额
     *      'payment_discount_amount'   => 'required', //支付优惠金额
     *      'discount_type'             => 'required', //优惠券类型
     *      'discount_value'            => 'required', //优惠券编号
     *      'discount_name'             => 'required', //优惠券编号
     *      'status'                    => 'required', //状态： 0：无效；1：未支付；2：扣款成功；3：扣款失败；4：取消；5：扣款中
     * ]
     * @return bool|mixed
     */

    public function create($data){

        $data =filter_array($data,[
            'instalment_id'             => 'required',
            'type'                      => 'required',
            'payment_amount'            => 'required',
            'payment_discount_amount'   => 'required',
            'discount_type'             => 'required',
            'discount_value'            => 'required',
            'discount_name'             => 'required',
            'status'                    => 'required',
        ]);
        if(count($data) != 8){
            return false;
        }

        $info = OrderGoodsInstalmentRecord::create($data);
        return $info->getQueueableId();
    }


}