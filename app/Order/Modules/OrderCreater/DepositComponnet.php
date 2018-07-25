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
use App\Lib\Risk\Yajin;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;
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

    private $deposit_detail='';

    private $orderNo='';

    public function __construct(OrderCreater $componnet,$certifiedFlag=true,$miniCreditAmount = 0)
    {
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
        $this->orderNo =$schema['order']['order_no'];
        $this->payType =$this->getOrderCreater()->getSkuComponnet()->getPayType();

        if($this->deposit && $this->payType >0){
            //支付押金规则
            foreach ($this->schema['sku'] as $k=>$v)
            {
                if( $this->payType == \App\Order\Modules\Inc\PayInc::MiniAlipay) {//小程序入口
                    $this->componnet->getOrderCreater()->getSkuComponnet()->discrease_yajin($this->miniCreditAmount, $v['yajin'], $v['mianyajin'], $v['sku_id']);
                }else{//其他入口
                    $arr =[
                        'user_id'=>$this->schema['user']['user_id'],
                        'yajin'=>$v['yajin'] * 100,
                        'market_price'=>$v['market_price']*100,
                    ];
                    try{
                        $deposit = Yajin::calculate($arr);
                    }catch (\Exception $e){
                        $this->getOrderCreater()->setError('商品押金接口错误');
                        $this->flag = false;
                    }

                    $jianmian = priceFormat($deposit['jianmian'] / 100);
                    $this->deposit_detail = $deposit['_msg'];

                    $this->componnet->getOrderCreater()->getSkuComponnet()->discrease_yajin($jianmian, $v['yajin'], $v['mianyajin'], $v['sku_id']);
                }
            }
        }
        return $this->flag && $filter;
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
        //保存减免押金详情信息
        $b= OrderUserCertifiedRepository::updateDepoistDetail($this->orderNo,$this->deposit_detail);
        if(!$b){
            $this->getOrderCreater()->setError('保存用户减免押金详情信息失败');
            return false;
        }
        return true;
    }

}