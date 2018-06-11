<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderGoodsInstalmentRecord;

class OrderGoodsInstalmentRecordRepository
{


    public function __construct() {

    }

     /**
     * 创建记录
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

    public static function create($data){

        $ret = OrderGoodsInstalmentRecord::create($data);
        if(!$ret){
            return false;
        }


    }


    /**
     * 修改方法
     * @param  array $params
     * @param  array $data
     * @return bool|mixed
     */

    public static function save($params, $data){
        if ( empty($params )) {
            return false;
        }
        if ( empty($data )) {
            return false;
        }

        $result =  OrderGoodsInstalmentRecord::where($params)->update($data);
        if (!$result) return false;
        return true;
    }


}