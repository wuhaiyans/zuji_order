<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderMini;

/**
 * 小程序订单表
 * Class OrderMiniRepository
 * Author zhangjinhui
 * @package App\Order\Modules\Repository
 */
class OrderMiniRepository
{
    public function __construct(){}

    /**
     * 添加芝麻订单信息
     * @param $data
     * @return $last_id
     */
    public static function add($data){
        $arr = [
            'order_no'=>$data['out_order_no'],
            'name'=>$data['name'],
            'zm_order_no'=>$data['order_no'],
            'transaction_id'=>$data['transaction_id'],
            'cert_no'=>$data['cert_no'],
            'mobile'=>$data['mobile'],
            'house'=>$data['house'],
            'zm_grade'=>$data['zm_grade'],
            'credit_amount'=>$data['credit_amount'],
            'zm_risk'=>$data['zm_risk'],
            'zm_face'=>$data['zm_face'],
            'app_id'=>$data['appid'],
            'channel_id'=>$data['channel_id'],
            'user_id'=>$data['user_id'],
            'overdue_time'=>$data['overdue_time'],
            'create_time'=>time(),
        ];
        //判断当前订单已经存在（已存在则修改）
        $miniOrderInfo = self::getMiniOrderInfo($data['out_order_no']);
        if(empty($miniOrderInfo)){
            $info =OrderMini::create($arr);
            return $info->getQueueableId();
        }else{
            $b =self::update( [
                'order_no'=>$data['out_order_no']
            ], $arr);
            if(!$b){
                return false;
            }
            return $miniOrderInfo['id'];
        }
    }

    /**
     * 判断是否调用修改订单数据
     * @params $where //传入修改条件
     * @params $arr //传入修改数据
     */
    public static function update( $where , $arr ) {
        $OrderMini = new OrderMini();
        $b = $OrderMini->update($where,$arr);
        return $b;
    }

    /**
     * 根据订单编号获取单条订单信息
     * @param string $orderNo 内部订单编号
     * @return array $miniOrderInfo 小程序订单基础信息|空<br/>
     * $orderInfo = [<br/>
     *		'id' => '',//订单自增id<br/>
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
     *		'app_id' => '',//app_id<br/>
     *		'create_time' => '',//创建时间<br/>
     * ]
     */
    public static function getMiniOrderInfo( $orderNo ) {
        $MiniOrder = new OrderMini();
        $result =  $MiniOrder->where(['order_no'=> $orderNo])->first();
        if (!$result) {
            get_instance()->setCode(\App\Lib\ApiStatus::CODE_35002)->setMsg('芝麻小程序订单信息获取失败');
            return [];
        }
        $miniOrderInfo = $result->toArray();
        return $miniOrderInfo;
    }
}