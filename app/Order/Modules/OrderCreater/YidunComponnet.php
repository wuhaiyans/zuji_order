<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Fengkong\Fengkong;

class YidunComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $userInfo;

    //蚁盾数据
    private $yidun;
    public function __construct(OrderCreater $componnet)
    {
        $this->componnet = $componnet;
        $schema =$componnet->getDataSchema();
        $this->userInfo =$schema['user'];

        //获取蚁盾信息
//        $yidun =Fengkong::getYidun(config('tripartite.Interior_Fengkong_Request_data'),[
//            'user_id'=>$schema['user']['user_id'],
//            'user_name'=>$schema['user']['realname'],
//            'cert_no'=>$schema['user']['cert_no'],
//            'mobile'=>$schema['user']['user_mobile'],
//        ]);

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
        $this->componnet->create();
        var_dump("yidun组件 -create");
        return true;
    }
}