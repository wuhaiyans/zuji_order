<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;

use App\Lib\ApiStatus;
use App\Lib\Risk\Risk;
use App\Order\Modules\Repository\OrderRiskRepository;
use App\Order\Modules\Repository\OrderYidunRepository;
use Mockery\Exception;

class RiskComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $userInfo;
    private $flag = true;

    //白骑士
    private $knight;

    public function __construct(OrderCreater $componnet,int $appId,int $userId)
    {
        $this->componnet = $componnet;
        //获取白骑士信息
        $knight =Risk::getKnight(['user_id'=>$userId]);
        $this->knight =[];
        if(is_array($knight)){
            $this->knight =$knight;
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
        $filter =$this->componnet->filter();
        return $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
       $schema = $this->componnet->getDataSchema();
       $risk['risk'] =$this->knight;
       return array_merge($schema,$risk);
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
        $orderNo =$this->componnet->getOrderCreater()->getOrderNo();
        if(empty($this->knight)){
            return true;
        }
        foreach ($this->knight as $k=>$v){
            $score =0;
            $strategies= '';
            if($k=="zhima_score"){
                $score =$v['score'];
                $v =$v['grade'];
            }
            if($k=="yidun"){
                $score =$v['score'];
                $strategies =$v['strategies'];
                $v =$v['decision'];
            }
            if($v===false){
                $v ="false";
            }
            if($v===true){
                $v ="true";
            }
            $riskData =[
                'decision' => $v,
                'order_no'=>$orderNo,  // 编号
                'score' => $score,
                'strategies' =>$strategies,
                'type'=>$k,
            ];
             $id =OrderRiskRepository::add($riskData);
            if(!$id){
                $this->getOrderCreater()->setError('保存风控数据失败');
                return false;
            }

            
        }
        return true;

    }
}