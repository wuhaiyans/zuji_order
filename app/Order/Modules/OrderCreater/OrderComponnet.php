<?php
/**
 * 订单创建组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Order\Modules\Repository\OrderRepository;
use Mockery\Exception;

class OrderComponnet implements OrderCreater
{
    //订单ID
    private $orderId = null;
    //订单编号
    private $orderNo = null;
    //用户ID
    private $userId=0;
    //用户组件
    private $userComponnet =null;
    //sku组件
    private $skuComponnet =null;
   //错误提示
    private $error = '';
   //错误码
    private $errno = 0;
    //免押金状态 0：不免押金；1：全免押金

    private $mianyaStatus = 0;

    public function __construct( $orderNo=null ,int $userId) {
        $this->orderNo = $orderNo;
        $this->userId =$userId;
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
        //判断是否有其他活跃 未完成订单
        $b =OrderRepository::unCompledOrder($this->userId);
        if($b) {
            $this->getOrderCreater()->setError('有未完成订单');
            return false;
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
        $zuqiType =$this->skuComponnet->getZuqiType();
        $zuqiTypeName =$this->skuComponnet->getZuqiTypeName();
        return array_merge(['order'=>[
            'order_no'=>$this->orderNo,
            'zuqi_type'=>$zuqiType,
            'zuqi_type_name'=>$zuqiTypeName,
        ]],$userSchema,$skuSchema);

    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        var_dump("订单组件 -create");
        //var_dump('创建订单...');
        // 创建订单
/*        $order_data = [
            'order_status' => \zuji\order\OrderStatus::OrderCreated, // 订单已创建
            'business_key' => $this->business_key,        // 业务类型值
            'order_no' => $this->order_no,  // 编号
            'status' => \oms\state\State::OrderCreated,  // 状态
            'create_time' => time(),
        ];
        $order2_table = \hd_load::getInstance()->table('order2/order2');
        $order_id = $order2_table->add($order_data);
        if( !$order_id ){
            $this->set_error('保存订单失败');
            return false;
        }
        $this->order_id = $order_id;

        $follow_table = \hd_load::getInstance()->table('order2/order2_follow');
        $follow_data =[
            'order_id'=>$order_id,
            'old_status'=>0,
            'new_status'=>1,
            'create_time'=>time(),
        ];
        $follow_table->add($follow_data);
*/

        // 执行 User组件
        $b = $this->userComponnet->create();
        if( !$b ){
            return false;
        }
        // 执行 Sku组件
        $b = $this->skuComponnet->create();
        if( !$b ){
            return false;
        }

        return true;
    }
}