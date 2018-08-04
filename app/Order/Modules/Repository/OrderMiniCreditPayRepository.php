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
        $miniOrderCreditPayInfo = self::getMiniCreditPayInfo($data['out_order_no'] , $data['order_operate_type']);
        if(empty($miniOrderCreditPayInfo)){
            $info =OrderMiniCreditPay::create($data);
            return $info->getQueueableId();
        }else{
            $b =self::update( [
                'out_order_no'=>$data['out_order_no']
            ], $data);
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
        $b = $OrderMiniCreditPay->update($where,$arr);
        return $b;
    }

    /**
     * 根据订单号获取芝麻支付信息
     * @param string $orderNo 订单编号
     * @param string $orderOperateType 订单完结类型
     * @return array $zmOrderInfo 订单基础信息|空<br/>
     * $zmOrderInfo = [<br/>
     *		'id' => '',//自增id<br/>
     *		'order_operate_type' => '',//订单完结类型，目前包括取消(CANCEL)、完结(FINISH) 、分期扣款(INSTALLMENT)<br/>
     *		'out_order_no' => '',//商户订单号<br/>
     *		'zm_order_no' => '',//芝麻订单号<br/>
     *		'out_trans_no' => '',//资商户资金交易号<br/>
     *		'remark' => '',//报错取消原因或完结补充说明<br/>
     *		'pay_amount' => '',//该次支付总金额<br/>
     *		'create_time' => '',//创建时间<br/>
     * ]
     */
    public static function getMiniCreditPayInfo( $orderNo,$orderOperateType ,$remark = false ) {
        $MiniOrder = new OrderMiniCreditPay();
        $where['out_order_no'] = $orderNo;
        $where['order_operate_type'] = $orderOperateType;
        if($remark){
            $where['remark'] = $remark;
        }
        $result =  $MiniOrder->where($where)->first();
        if (!$result) {
            get_instance()->setCode(\App\Lib\ApiStatus::CODE_35002)->setMsg('芝麻小程序订单信息获取失败');
            return [];
        }
        $miniOrderInfo = $result->toArray();
		$miniOrderInfo['create_time'] = date('Y-m-d H:i:s',$miniOrderInfo['create_time']);
        return $miniOrderInfo;
    }
}