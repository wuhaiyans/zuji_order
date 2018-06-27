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

    //风控数据
    private $risk;
    //信用分
    private $score;
    //白骑士
    private $knight;

    public function __construct(OrderCreater $componnet,int $appId)
    {
        $this->componnet = $componnet;
        $schema =$componnet->getDataSchema();
        $this->userInfo =$schema['user'];
        //获取信用分
        $score = Risk::getCredit(['user_id'=>$schema['user']['user_id']]);
        $this->score =0;
        if(is_array($score)){
            $this->score =$score['score'];
        }

        //获取白骑士信息
        $knight =Risk::getKnight(['user_id'=>$schema['user']['user_id']]);
        if(!is_array($knight)){
            throw new \Exception("获取白骑士信息失败:".$knight);
        }
        $this->knight =$knight;

        //获取蚁盾信息
//        $risk =Risk::getRisk([
//            'user_id'=>$schema['user']['user_id'],
//            'user_name'=>$schema['user']['realname'],
//            'cert_no'=>$schema['user']['cert_no'],
//            'mobile'=>$schema['user']['user_mobile'],
//            'channel_appid'=>$appId,
//        ]);
//        if(!is_array($risk)){
//            throw new Exception($risk);
//        }
//        $riskData =[
//            'risk'=>[
//                'decision' => $risk['decision'],
//                'score' => $risk['score'],
//                'strategies' =>$risk['strategies'],
//                'type'=>Risk::RiskYidun,
//            ],
//            'score'=>$this->score,
//        ];
          $riskData =[
            'risk'=>[
                'decision' => "假数据",
                'score' => 0,
                'strategies' =>'',
                'type'=>Risk::RiskYidun,
            ],
            'score'=>$this->score,
        ];

        $this->risk =$riskData;
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
       $schema = $this->componnet->getDataSchema();
       return array_merge($schema,$this->risk);
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
        $data =$this->knight;
        foreach ($this->knight as $k=>$v){
            if($k=="zhima_score"){
                continue;
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
                'score' => 0,
                'strategies' =>"",
                'type'=>$k,
            ];
             $id =OrderRiskRepository::add($riskData);
            if(!$id){
                $this->getOrderCreater()->setError('保存风控数据失败');
                return false;
            }

        }
        return true;
//        $data =$this->risk;
//        $riskData =[
//            'decision' => $data['risk']['decision'],
//            'order_no'=>$orderNo,  // 编号
//            'score' => $data['risk']['score'],
//            'strategies' =>$data['risk']['strategies'],
//            'type'=>$data['risk']['type'],
//        ];
//        $Id =OrderRiskRepository::add($riskData);
//        if(!$Id){
//            $this->getOrderCreater()->setError('保存风控数据失败');
//            return false;
//        }
    }
}