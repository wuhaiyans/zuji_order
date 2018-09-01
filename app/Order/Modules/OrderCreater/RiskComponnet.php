<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;

use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Risk\Risk;
use App\Order\Models\OrderUserCertified;
use App\Order\Modules\Repository\OrderRiskRepository;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;
use App\Order\Modules\Repository\OrderYidunRepository;
use Mockery\Exception;

class RiskComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $userInfo;
    private $flag = true;

    //风控信息
    private $knight;

    public function __construct(OrderCreater $componnet)
    {
        $this->componnet = $componnet;
        $data =$this->componnet->getDataSchema();

        //获取风控信息信息
        try{
            $knight =Risk::getKnight(['user_id'=>$data['user']['user_id']]);
            $this->knight =$knight;
        }catch (\Exception $e){
            LogApi::error(config('app.env')."[下单/确认订单] 获取风控接口失败",$data['user']['user_id']);
            $this->knight =[];
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
        //保存用户身份证信息
        if(isset($this->knight['user_info']['card_img']) && !empty($this->knight['user_info']['card_img'])){
            $b= OrderUserCertifiedRepository::updateCardImg($orderNo,$this->knight['user_info']['card_img']);
            if(!$b){
                $this->getOrderCreater()->setError('保存身份证信息失败');
                return false;
            }
        }

        //获取风控信息详情 保存到数据表

        $riskDetail =$this->knight['risk_detail']?? true;
        if (is_array($riskDetail) && !empty($riskDetail)) {
            foreach ($riskDetail as $k=>$v){
                $riskData =[
                    'order_no'=>$orderNo,  // 订单编号
                    'data' => json_encode($riskDetail[$k]),
                    'type'=>$k,
                ];
                $id =OrderRiskRepository::add($riskData);
                if(!$id){
                    LogApi::error(config('app.env')."[下单]保存风控数据失败",$riskData);
                    $this->getOrderCreater()->setError('保存风控数据失败');
                    return false;
                }
            }
        }

        //保存是否是学生
        $isStudent = $this->knight['is_chsi']?1:0;  //判断是否是学生
        $certified = OrderUserCertified::where('order_no','=',$orderNo)->first();
        if (!$certified) return false;
        $certified->is_student = $isStudent;
        if (!$certified->save()) {
            LogApi::error(config('app.env')."[下单]保存用户身份类型失败",$riskData);
            $this->getOrderCreater()->setError('保存用户身份类型失败');
            return false;
        }


        return true;

    }
}