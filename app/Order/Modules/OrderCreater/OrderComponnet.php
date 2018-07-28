<?php
/**
 * 订单创建组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Order\Models\Order;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\OrderRepository;
use Mockery\Exception;

class OrderComponnet implements OrderCreater
{
    //订单ID
    private $orderId = null;
    //订单编号
    private $orderNo = null;
    //订单类型
    private $orderType;
    //用户ID
    private $userId=0;
    //支付方式
    private $payType;
    //租期类型
    private $zuqiType;
    //用户组件
    private $userComponnet =null;
    //sku组件
    private $skuComponnet =null;
   //错误提示
    private $error = '';
   //错误码
    private $errno = 0;
    //免押金状态 0：不免押金；1：全免押金
    //appid
    private $appid;
    private $mianyaStatus = 0;

    public function __construct( $orderNo='' ,int $userId,int $appid,int $orderType) {
        $this->orderNo = $orderNo;
        $this->userId =$userId;
        $this->appid=$appid;
        $this->orderType =$orderType;
    }

    /**
     *
     * 设置 User组件
     * @param UserComponnet $user_componnet
     * @return OrderCreater
     */
    public function setUserComponnet(UserComponnet $userComponnet){
        $this->userComponnet = $userComponnet;
        return $this;
    }
    /**
     * 获取 User组件
     * @return UserComponnet
     */
    public function getUserComponnet(){
        return $this->userComponnet;
    }

    /**
     * 设置 Sku组件
     * @param SkuComponnet $sku_componnet
     * @return OrderCreater
     */
    public function setSkuComponnet(SkuComponnet $skuComponnet){
        $this->skuComponnet = $skuComponnet;
        return $this;
    }
    /**
     * 获取 Sku组件
     * @return SkuComponnet
     */
    public function getSkuComponnet(): SkuComponnet{
        return $this->skuComponnet;
    }
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderComponnet
    {
        return $this;
    }
    /**
     * 设置 错误提示
     * @param string $error  错误提示信息
     * @return OrderComponnet
     */
    public function setError( string $error ): OrderComponnet
    {
        $this->error = $error;
        return $this;
    }
    /**
     * 获取 错误提示
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * 设置 错误码
     * @param int $errno	错误码
     * @return OrderComponnet
     */
    public function setErrno( $errno ): OrderComponnet
    {
        $this->errno = $errno;
        return $this;
    }
    /**
     * 获取 错误码
     * @return int
     */
    public function getErrno(): int
    {
        return $this->errno;
    }

    /**
     * 设置免押状态
     * @param int $status
     * @return OrderComponnet
     */
    public function setMianyaStatus( int $status ): OrderComponnet
    {
        if( !in_array($status, [0,1]) ){
            throw new Exception('免押状态值设置异常');
        }
        $this->mianyaStatus = $status;
        return $this;
    }
    /**
     * 获取免押状态
     * @return int
     */
    public function getMianyaStatus(): int
    {
        return $this->mianyaStatus;
    }

    /**
     * 获取 订单编号
     * @return string
     */
    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    /**
     * 获取订单ID
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }


    /**
     * 过滤
     * <p>注意：</p>
     * <p>在过滤过程中，可以修改下单需要的元数据</p>
     * <p>组件之间的过滤操作互不影响</p>
     * <p>先执行内部组件的filter()，然后再执行组件本身的过滤</p>
     * @return bool
     */
    public function filter(): bool
    {
        //判断是否有其他活跃 未完成订单(小程序不限制)
        $this->payType =$this->getOrderCreater()->getSkuComponnet()->getPayType();
        if( $this->payType !=  PayInc::MiniAlipay){
            $b =OrderRepository::unCompledOrder($this->userId);
            if($b) {
                $this->getOrderCreater()->setError('有未完成订单');
                return false;
            }
        }

        $b = $this->userComponnet->filter();
        if( !$b ){
            return false;
        }
        $b = $this->skuComponnet->filter();
        if( !$b ){
            return false;
        }
        return true;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        $userSchema = $this->userComponnet->getDataSchema();
        $skuSchema =$this->skuComponnet->getDataSchema();
        $this->zuqiType =$this->skuComponnet->getZuqiType();
        $zuqiTypeName =$this->skuComponnet->getZuqiTypeName();
        return array_merge(['order'=>[
            'order_no'=>$this->orderNo,
            'zuqi_type'=>$this->zuqiType,
            'zuqi_type_name'=>$zuqiTypeName,
            'pay_type'=>$this->payType,
            'order_type'=>$this->orderType,
            'app_id'=>$this->appid,
        ]],$userSchema,$skuSchema);

    }


    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        $data = $this->getOrderCreater()->getDataSchema();

        // 执行 User组件
        $b = $this->userComponnet->create();
        if (!$b) {
            return false;
        }
        // 执行 Sku组件
        $b = $this->skuComponnet->create();
        if (!$b) {
            return false;
        }
        $order_amount = 0;
        $goods_yajin = 0;
        $order_yajin = 0;
        $order_insurance = 0;
        $coupon_amount = 0;
        $discount_amount = 0;

        foreach ($data['sku'] as $k => $v) {
            for ($i = 0; $i < $v['sku_num']; $i++) {
                $order_amount += $v['amount_after_discount'];
                $goods_yajin += $v['yajin'];
                $order_yajin += $v['deposit_yajin'];
                $order_insurance += $v['insurance'];
                $coupon_amount += ($v['first_coupon_amount']+$v['order_coupon_amount']);
                $discount_amount += $v['discount_amount'];
            }
        }
        $orderData = [
            'order_status' => OrderStatus::OrderWaitPaying,
            'order_no' => $this->orderNo,  // 编号
            'user_id' => $this->userId,
            'pay_type' => $this->payType,
            'zuqi_type'=>$this->zuqiType,
            'order_amount' => 0,
            'goods_yajin' => 0,
            'order_yajin' => 0,
            'order_insurance' => $order_insurance,
            'coupon_amount' => $coupon_amount,
            'discount_amount' => 0,
            'appid' =>$this->appid,
            'create_time'=>time(),
            'order_type'=>$this->orderType,
            'mobile'=>$data['user']['user_mobile'],
        ];
        $orderRepository = new OrderRepository();
        $orderId = $orderRepository->add($orderData);
        if (!$orderId) {
            $this->getOrderCreater()->setError('保存订单数据失败');
            return false;
        }

        return true;
    }

}
