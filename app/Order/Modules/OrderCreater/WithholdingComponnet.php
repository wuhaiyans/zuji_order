<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\OrderPayWithholdRepository;

class WithholdingComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;
    private $payType;

    /**
     * 代扣签约信息
     * @var int
     */
    private $withholdingInfo =[];
    private $withhodldingNo="";

    public function __construct(OrderCreater $componnet,int $payType,int $userId)
    {
        $this->componnet = $componnet;
        $this->payType=$payType;
        if($payType == PayInc::WithhodingPay){
            //查询该用户代扣数据
            $withhodingInfo = OrderPayWithholdRepository::find($userId);
            $this->withholdingInfo =$withhodingInfo;
        }
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
        // 代扣支付方式时，进行判断
        if($this->payType == PayInc::WithhodingPay){
            //查询该用户代扣数据
            if(empty($this->withholdingInfo['withhold_no']) ||$this->withholdingInfo['withhold_no']==""){
                $this->getOrderCreater()->setError('未签约代扣协议');
                $this->flag = false;
            }
            if(empty($this->withholdingInfo['withhold_status']) ||$this->withholdingInfo['withhold_status']==2){
                $this->getOrderCreater()->setError('用户已经解约代扣协议');
                $this->flag = false;
            }
            $this->withhodldingNo =!empty($this->withholdingInfo['withhold_no'])?$this->withholdingInfo['withhold_no']:"";

        }

        return $this->flag && $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        $schema = $this->componnet->getDataSchema();
        return array_merge($schema,[
            'withholding' => [
                'withholding_no' => strlen($this->withhodldingNo)?$this->withhodldingNo:"",
            ]
        ]);
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