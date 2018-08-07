<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderMiniCreditPay;

/**
 * 小程序发送 扣款 取消 完成请求信息记录表
 * Class OrderMiniCreditPayRepository
 * Author zhangjinhui
 * @package App\Order\Modules\Repository
 */
class OrderMiniCreditPayRepository
{
    public function __construct(){}

    /**
     * 添加芝麻订单信息
     * @param $data
     * @return $last_id
     */
    public static function add($data){
        //判断当前订单已经存在（已存在则修改）
        $where = [
          'out_order_no'=>$data['out_order_no'],
          'order_operate_type'=>$data['order_operate_type'],
        ];
        if(isset($data['out_trans_no'])){
            $where['out_trans_no'] = $data['out_trans_no'];
        }
        $miniOrderCreditPayInfo = self::getMiniCreditPayInfo($where);
        if(empty($miniOrderCreditPayInfo)){
            $info =OrderMiniCreditPay::create($data);
            return $info->getQueueableId();
        }else{
            $b =self::update( [
                'id'=>$miniOrderCreditPayInfo['id']
            ] , $data );
            if(!$b){
                return false;
            }
            return $miniOrderCreditPayInfo['id'];
        }
    }

    /**
     * 判断是否调用修改订单数据
     * @params $where //传入修改条件
     * @params $arr //传入修改数据
     */
    public static function update( $where , $arr ) {
        $OrderMiniCreditPay = new OrderMiniCreditPay();
        $MiniCreditPay = $OrderMiniCreditPay->where($where)->first();
        $MiniCreditPay->order_operate_type = $arr['order_operate_type'];
        $MiniCreditPay->out_order_no = $arr['out_order_no'];
        $MiniCreditPay->zm_order_no = $arr['zm_order_no'];
        $MiniCreditPay->out_trans_no = $arr['out_trans_no'];
        $MiniCreditPay->remark = $arr['remark'];
        $MiniCreditPay->pay_amount = $arr['pay_amount'];
        $b = $MiniCreditPay->update();
        return $b;
    }

    /**
     * 根据订单号获取芝麻支付信息
     * @param string $where  数据字段条件
     * @return array $zmOrderInfo 订单基础信息|空<br/>
     * $zmOrderInfo = [<br/>
     *		'id' => '',//自增id<br/>
     *		'order_operate_type' => '',//订单完结类型，目前包括取消(CANCEL)、完结(FINISH) 、分期扣款(INSTALLMENT)<br/>
     *		'out_order_no' => '',//商户订单号<br/>
     *		'zm_order_no' => '',//芝麻订单号<br/>
     *		'out_trans_no' => '',//资商户资金交易号<br/>
     *		'remark' => '',//报错取消原因或完结补充说明<br/>
     *		'pay_amount' => '',//该次支付总金额<br/>
     * ]
     */
    public static function getMiniCreditPayInfo( $where = [] ) {
        $MiniOrder = new OrderMiniCreditPay();
        $result =  $MiniOrder->where($where)->first();
        if (!$result) {
            get_instance()->setCode(\App\Lib\ApiStatus::CODE_35002)->setMsg('芝麻小程序订单信息获取失败');
            return [];
        }
        $miniOrderInfo = $result->toArray();
        return $miniOrderInfo;
    }
}