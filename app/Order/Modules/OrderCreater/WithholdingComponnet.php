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
//        // 代扣支付方式时，进行判断
//        if($this->payType == PayInc::WithhodingPay){
//
//            //查询该用户代扣数据
//            try{
//                $withhold=WithholdQuery::getByUserChannel($this->userId,$this->payChannelId);
//                $this->needWithholding ="N";
//            }catch(\Exception $e){
//                $this->needWithholding ="Y";
//                $this->getOrderCreater()->setError('未签约代扣协议');
//                $this->flag = false;
//            }
//
//        }

        return $this->flag && $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        return $this->componnet->getDataSchema();
//        return array_merge($schema,[
//            'withholding' => [
//                'needWithholding'=>$this->needWithholding,
//            ]
//        ]);
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
//        $orderNo =$this->componnet->getOrderCreater()->getOrderNo();
//
//        //判断如果是代扣预授权 并且已经签订代扣协议 把代扣协议绑定到订单中
//        if($this->payType == PayInc::WithhodingPay && $this->needWithholding=="N"){
//
//            //判断如果已经签约代扣 并且预授权金额为 0 订单状态改为已支付
//            $orderYajin =$this->componnet->getOrderCreater()->getSkuComponnet()->getOrderYajin();
//            if($orderYajin=="0" && $this->needWithholding=="N"){
//
//                $data['order_status']=OrderStatus::OrderPayed;
//                $b =Order::where('order_no', '=', $orderNo)->update($data);
//                if(!$b){
//                    $this->getOrderCreater()->setError('更新订单支付状态失败');
//                    return false;
//                }
//            }
//        }



        return true;
    }
}