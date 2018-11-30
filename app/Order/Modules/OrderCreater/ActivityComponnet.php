<?php
/**
 * 活动组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Activity\Modules\Inc\DestineStatus;
use App\Activity\Modules\Repository\Activity\ExperienceDestine;
use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\StoreAddress;
use App\Order\Modules\Repository\OrderUserAddressRepository;

class ActivityComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;

    //活动预定编号
    private $destineNo;

    //设备租用开始时间
    private $beginTime;
    //设备租用结束时间
    private $endTime;
    //活动ID
    private $activityId;

    public function __construct(OrderCreater $componnet,$destineNo='')
    {
        $this->componnet = $componnet;
        $this->destineNo = $destineNo;
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
        if($this->destineNo !=''){
            //判断用户是否 已经参与活动
            $destine = ExperienceDestine::getByNo($this->destineNo);
            $userId = $this->componnet->getOrderCreater()->getUserComponnet()->getUserId();
            if($destine) {
                $destineData = $destine->getData();
                //判断用户信息 与预定信息
                if($destineData['user_id']!=$userId){
                    $this->getOrderCreater()->setError('预订信息与用户不匹配');
                    $this->flag = false;
                }
                if ($destineData['destine_status'] != DestineStatus::DestinePayed) {
                    $this->getOrderCreater()->setError('该活动不能领取');
                    $this->flag = false;
                }

                //重新覆盖 商品日期
                //租用时间 为领取的第二天时间开始算
                $this->beginTime = date("Y-m-d",strtotime("+1 day"));
                //结束时间为 租用时间开始+租期
                $this->endTime = date("Y-m-d",strtotime("+".$destineData['zuqi']." day"));
                $this->componnet->getOrderCreater()->getSkuComponnet()->unitTime($this->beginTime, $this->endTime);
                $this->activityId = $destineData['activity_id'];
            }else{
                $this->getOrderCreater()->setError('该活动未预约');
                $this->flag = false;
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
        $schema = $this->componnet->getDataSchema();
        $activity['activity'] =[
            'activity_id'=>$this->activityId,
        ];
        return array_merge($schema,$activity);

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
        if($this->destineNo != ''){
            $data =$this->getDataSchema();
            //判断用户是否 已经参与活动
            $destine = ExperienceDestine::getByNo($this->destineNo);
            $isStudent = isset($data['risk']['is_chsi'])&&$data['risk']['is_chsi']?1:0;  //判断是否是学生
            //更新 预约单状态
            $b = $destine->updateDestineForOrder(strtotime($data['sku'][0]['end_time']),$isStudent);
            if(!$b){
                LogApi::error(config('app.env')."OrderCreate-UpdateDestine-error-".$this->destineNo);
                $this->getOrderCreater()->setError("OrderCreate-UpdateDestine-error");
                return false;
            }
        }
        return true;
    }
}