<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\MiniOrder;

/**
 * 小程序临时订单表
 * Class MiniOrderRepository
 * Author zhangjinhui
 * @package App\Order\Modules\Repository
 */
class MiniOrderRepository
{
    public function __construct(){}

    /**
     * 添加芝麻订单信息
     * @param $data
     * @return $last_id
     */
    public static function add($data){
        $info =MiniOrder::create($data);
        return $info->getQueueableId();
    }

    /**
     * 根据商品编号获取单条商品信息
     * @param string $goodsNo 商品编号
     * @return array $goodsInfo 商品基础信息|空<br/>
     * $goodsInfo = [<br/>
     *		'id' => '',//订单商品自增id<br/>
     *		'order_no' => '',//业务平台订单号<br/>
     *		'zm_order_no' => '',//芝麻订单号<br/>
     *		'transaction_id' => '',//芝麻请求流水号<br/>
     *		'cert_no' => '',//证件号<br/>
     *		'mobile' => '',//手机号<br/>
     *		'house' => '',//住宅地址<br/>
     *		'zm_grade' => '',//级别<br/>
     *		'credit_amount' => '',//信用权益金额<br/>
     *		'zm_score' => '',//
     *		'zm_risk' => '',//芝麻风控产品集联合结果<br/>
     *		'zm_face' => '',//人脸核身结果<br/>
     *		'user_id' => '',//支付宝 userid<br/>
     *		'channel_id' => '',//渠道来源<br/>
     *		'create_time' => '',//创建时间<br/>
     * ]
     */
    public function getMiniOrderInfo( $orderNo ) {
        $MiniOrder = new MiniOrder();
        $result =  $MiniOrder->where(['order_no'=> $orderNo])->first();
        if (!$result) {
            get_instance()->setCode(\App\Lib\ApiStatus::CODE_35002)->setMsg('芝麻小程序订单信息获取失败');
            return [];
        }
        $miniOrderInfo = $result->toArray();
//		var_dump($miniOrderInfo);exit;
//		$miniOrderInfo['create_time'] = date('Y-m-d H:i:s',$miniOrderInfo['create_time']);
        return $miniOrderInfo;
    }
}