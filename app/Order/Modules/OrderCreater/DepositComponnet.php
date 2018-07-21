<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Certification;
use App\Lib\Goods;
use Mockery\Exception;

class DepositComponnet implements OrderCreater
{
    //组件
    private $componnet;
    //支付方式
    private $payType;

    private $schema;

    private $miniCreditAmount;

    //是否满足押金减免条件
    private $deposit = true;

    private $certifiedFlag =true;

    private $flag = true;

    public function __construct(OrderCreater $componnet,$certifiedFlag=true,$miniCreditAmount = 0)
    {
        echo 11;die;
        $this->componnet = $componnet;
        $this->certifiedFlag =$certifiedFlag;
        $this->miniCreditAmount =$miniCreditAmount;

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
        $schema = $this->componnet->getDataSchema();
        $this->schema =$schema;
        //根据用户实名认证信息是否一致初始化订单是否满足押金键名条件
        $this->deposit = !!$this->certifiedFlag;

        //未通过认证人脸识别
        if($this->schema['user']['face']==0){
            $this->deposit = false;
        }
        //未通过风控验证
        if($this->schema['user']['risk']==0){
            $this->deposit = false;
        }
        //风控整体策略
        if(empty($this->schema['risk']['risk_grade']) || $this->schema['risk']['risk_grade'] =='REJECT'){
            $this->deposit = false;
        }
        $this->payType =$this->getOrderCreater()->getSkuComponnet()->getPayType();

        if($this->deposit && $this->payType >0){
            //支付押金规则
            foreach ($this->schema['sku'] as $k=>$v)
            {
                if( $this->payType == \App\Order\Modules\Inc\PayInc::MiniAlipay) {//小程序入口
                    $this->componnet->getOrderCreater()->getSkuComponnet()->discrease_yajin($this->miniCreditAmount, $v['yajin'], $v['mianyajin'], $v['sku_id']);
                }else{//其他入口
                    $deposit = Goods\Deposit::getDeposit([
                        'spu_id' => $v['spu_id'],
                        'pay_type' => $this->payType,
                        'credit' => $this->schema['user']['credit'] ? $this->schema['user']['credit'] : 0,
                        'age' => $this->schema['user']['age'] ? $this->schema['user']['age'] : 0,
                        'yajin' => $v['yajin'] * 100,

                    ]);
                    if (!is_array($deposit)) {
                        $this->getOrderCreater()->setError('商品押金接口错误');
                        $this->flag = false;
                    }
                    $jianmian = priceFormat($deposit['jianmian'] / 100);
                    $this->componnet->getOrderCreater()->getSkuComponnet()->discrease_yajin($jianmian, $v['yajin'], $v['mianyajin'], $v['sku_id']);
                }
            }
        }return $this->flag && $filter;
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