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

    //是否满足押金减免条件
    private $deposit = true;

    private $certifiedFlag =true;

    private $flag = true;

    public function __construct(OrderCreater $componnet,int $payType,$certifiedFlag=true)
    {
        $this->componnet = $componnet;
        $this->payType =$payType;
        $schema = $this->componnet->getDataSchema();
        //var_dump($schema);die;
        $this->certifiedFlag =$certifiedFlag;
        $this->schema =$schema;

    }

    /**
     *  计算押金
     * @param int $amount
     */
    public function discrease_yajin(int $jianmian,$yajin,$mianyajin): array{
        $arr=['yajin'=>$yajin,'mianyajin'=>$mianyajin,'jianmian'=>$jianmian];
        if( $jianmian<0 ){
            return [];
        }
        // 优惠金额 大于 总金额 时，总金额设置为0.01
        if( $jianmian >= $yajin ){
            $jianmian = $yajin;
        }

        $arr['yajin'] -= $jianmian;// 更新押金
        $arr['mianyajin'] += $jianmian;// 更新免押金额
        $arr['jianmian'] = $jianmian;

        return $arr;
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
        /*
         * 2018-02-22 liuhongxing 暂时去掉 人脸识别 限制条件（为 芝麻活动 提高订单量）
         * 2018-03-05 liuhongxing 恢复人脸识别 限制条件
        */
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
        //未通过信用分
        if($this->schema['user']['score'] <env("ORDER_SCORE")){
            $this->deposit = false;
        }


        //未通过蚁盾验证
        if($this->schema['yidun']['decision'] ==YidunComponnet::RISK_REJECT){
            $this->deposit = false;
        }
        //京东小白信用押金全免设置（配合银联支付）
        if( $this->schema['user']['certified_platform']== Certification::JdXiaoBai) {
            $this->deposit = true;
        }
        if($this->deposit && $this->payType >0){
            //支付押金规则
            foreach ($this->schema['sku'] as $k=>$v)
            {
                $deposit =Goods\Deposit::getDeposit(config('tripartite.Interior_Goods_Request_data'),[
                    'spu_id'=>$v['spu_id'],
                    'pay_type'=>$this->payType,
                    'credit'=>$this->schema['user']['credit']?$this->schema['user']['credit']:0,
                    'age'=>$this->schema['user']['age']?$this->schema['user']['age']:0,
                    'yajin'=>$v['yajin']*100,

                ]);
                if(!is_array($deposit)){
                    $this->getOrderCreater()->setError('商品押金接口错误');
                    $this->flag = false;
                }
                $jianmian = priceFormat($deposit['jianmian']/100);
                $arr =$this->discrease_yajin($jianmian,$v['yajin'],$v['mianyajin']);
                $this->schema['sku'][$k]['jianmian'] =$arr['jianmian'];
                $this->schema['sku'][$k]['yajin'] =$arr['yajin'];
                $this->schema['sku'][$k]['mianyajin'] =$arr['mianyajin'];
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
        return $this->schema;
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        $b = $this->componnet->create();
        var_dump("押金组件 -create");
        return true;
        if( !$this->flag ){
            return false;
        }
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        return true;
    }

}