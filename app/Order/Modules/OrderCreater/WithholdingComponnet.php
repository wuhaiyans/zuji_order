<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Order\Models\Order;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\OrderPayWithholdRepository;
use App\Order\Modules\Repository\Pay\WithholdQuery;

class WithholdingComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;
    private $payType;
    private $userId;
    private $payChannelId;

    /**
     * 代扣签约信息
     * @var int
     */
    private $withholdingInfo =[];
    private $withhodldingNo="";
    private $needWithholding ="";

    public function __construct(OrderCreater $componnet,int $payType,int $userId,int $payChannelId)
    {
        $this->componnet = $componnet;
        $this->payType=$payType;
        $this->userId =$userId;
        $this->payChannelId=$payChannelId;
    }
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderComponnet
    {
        return $this->componnet->getOrderCreater();
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

        $filter =  $this->componnet->filter();
        return $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        return $this->componnet->getDataSchema();
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        return true;
    }
}