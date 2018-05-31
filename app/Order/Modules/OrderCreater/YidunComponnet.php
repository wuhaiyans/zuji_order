<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Fengkong\Fengkong;
use App\Order\Modules\Repository\OrderYidunRepository;

class YidunComponnet implements OrderCreater
{
    /**
     * 风险类型：可接受的风险，无风险
     */
    const RISK_ACCEPT = 'accept';
    /**
     * 风险类型：不可接受的风险，高风险
     */
    const RISK_REJECT = 'reject';
    /**
     * 风险类型：用户根据自己业务模型进行验证，中风险
     */
    const RISK_VALIDATE = 'validate';
    //组件
    private $componnet;
    private $userInfo;

    //蚁盾数据
    private $yidun;
    public function __construct(OrderCreater $componnet,int $appId)
    {
        $this->componnet = $componnet;
        $schema =$componnet->getDataSchema();
        $this->userInfo =$schema['user'];

        //获取蚁盾信息
//        $yidun =Fengkong::getYidun([
//            'user_id'=>$schema['user']['user_id'],
//            'user_name'=>$schema['user']['realname'],
//            'cert_no'=>$schema['user']['cert_no'],
//            'mobile'=>$schema['user']['user_mobile'],
//            'channel_appid'=>$appId,
//        ]);
//        var_dump($yidun);die;

        $yidun_data =[
            'yidun'=>[
                'decision' => "0",
                'score' => "0",
                'strategies' =>"111",
            ]
        ];
        $this->yidun =$yidun_data;
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
        return $this->componnet->filter();
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
       $schema = $this->componnet->getDataSchema();
       return array_merge($schema,$this->yidun);
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
        $data =$this->getDataSchema();
        //存储蚁盾信息
        $yidunData =[
            'decision' => $data['yidun']['decision'],
            'order_no'=>$data['order']['order_no'],  // 编号
            'score' => $data['yidun']['score'],
            'strategies' =>$data['yidun']['strategies'],
        ];
        $yidunId =OrderYidunRepository::add($yidunData);
        if(!$yidunId){
            $this->getOrderCreater()->setError('保存蚁盾数据失败');
            return false;
        }
        return true;
    }
}