<?php
/**
 * 获取信用分组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Fengkong\Fengkong;

class CreditComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;
    private $appid;
    private $userInfo;
    //信用分
    private $score;


    public function __construct(OrderCreater $componnet)
    {
        $this->componnet = $componnet;
        $schema =$componnet->getDataSchema();
        $this->userInfo =$schema['user'];
        //获取信用分
//        $score = Fengkong::getCredit(config('tripartite.Interior_Fengkong_Request_data'),['user_id'=>$schema['user']['user_id']]);
//        if(!is_array($score)){
//            $this->score =0;
//        }
        $this->score=99;//$score['score'];
        $this->componnet->getOrderCreater()->getUserComponnet()->setScore($this->score);
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
        $filter =$this->componnet->filter();
        $userInfo=$this->userInfo;

        // 信用认证结果有效期
        if(time()-$userInfo['credit_time'] > 60*60 ){
            $this->getOrderCreater()->setError('信用认证过期');
            $this->flag = false;
        }
        if( $userInfo['certified'] == 0 ){
            $this->getOrderCreater()->setError('账户尚未信用认证');
            $this->flag = false;
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
        return true;

    }
}