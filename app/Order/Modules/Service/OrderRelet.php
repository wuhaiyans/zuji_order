<?php
/**
 * Created by PhpStorm.
 * User: wangjinlin
 * Date: 2018/5/21
 * Time: 下午4:50
 */

namespace App\Order\Modules\Service;



use App\Lib\Common\LogApi;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderGoodsUnit;
use App\Order\Models\OrderRelet;
use App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Inc\OrderGoodStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Inc\publicInc;
use App\Order\Modules\Inc\ReletStatus;
use App\Order\Modules\Repository\Order\Goods;
use App\Order\Modules\Repository\Order\Order;
use App\Order\Modules\Repository\Order\ServicePeriod;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderPayWithholdRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\PayCreater;
use App\Order\Modules\Repository\Relet\Relet;
use App\Order\Modules\Repository\ReletRepository;
use Illuminate\Support\Facades\DB;

class OrderRelet
{
    protected $reletRepository;

    public function __construct(ReletRepository $reletRepository)
    {
        $this->reletRepository = $reletRepository;
    }

    /**
     * 获取续租列表(后台)
     *      带分页
     *
     * @param $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList($params){
        return $this->reletRepository->getList($params);

    }

    /**
     * 获取用户未完成续租列表(前段)
     *
     * @param $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserList($params){
        return $this->reletRepository->getUserList($params);

    }

    /**
     * 通过ID获取一条记录
     *
     * @param $params
     * @return array
     */
    public function getRowId($params){
        return $this->reletRepository->getRowId($params);

    }

    /**
     * 设置status状态
     *
     * @param $params
     * @return bool
     */
    public function setStatus($params){
        $row = $this->reletRepository->getRowId($params['id']);
        if($row['status'] == ReletStatus::STATUS1){
            return $this->reletRepository->setStatus($params);
        }else{
            set_msg('只允许创建续租取消');
            return false;
        }
    }

    /**
     * 创建续租单
     *
     * @param $params
     * @return bool|array			返回参数
     * [
     *		'url'		=> '',	// 跳转地址
     *		'params'	=> '',	// 跳转附件参数
     * ]
     */
    public function createRelet($params){
        DB::beginTransaction();
        try{
            //获取订单对象
            $orderObj = Order::getByNo($params['order_no']);
            //判断是否冻结
            if( $orderObj->nonFreeze() ){
                DB::rollBack();
                set_msg('订单冻结中');
                return false;
            }
            //获取商品对象
            $goodsObj = Goods::getByGoodsId($params['goods_id']);
            if( $goodsObj ){
                $goods = $goodsObj->getData();
                if( $goods['zuqi_type']==OrderStatus::ZUQI_TYPE1 ){
                    if( $params['zuqi']<3 || $params['zuqi']>30 ){
                        DB::rollBack();
                        set_msg('租期错误');
                        return false;
                    }
                }else{
                    if( !publicInc::getCangzuRow($params['zuqi']) && $params['zuqi']!=0 ){
                        DB::rollBack();
                        set_msg('租期错误');
                        return false;
                    }
                }
                $amount = $goods['zujin']*$params['zuqi'];

                if($amount == $params['relet_amount']){
                    $data = [
                        'user_id'=>$params['user_id'],
                        'zuqi_type'=>$goods['zuqi_type'],
                        'zuqi'=>$goods['zuqi'],
                        'order_no'=>$params['order_no'],
                        'relet_no'=>createNo(9),
                        'create_time'=>time(),
                        'pay_type'=>$params['pay_type'],
                        'user_name'=>$params['user_name'],
                        'goods_id'=>$params['goods_id'],
                        'relet_amount'=>$params['relet_amount'],
                        'status'=>ReletStatus::STATUS1,
                    ];

                    if(ReletRepository::createRelet($data)){
                        //修改设备状态 续租中
                        if( !$goodsObj->setGoodsStatusReletOn() ){
                            DB::rollBack();
                            set_msg('修改设备状态续租中失败');
                            return false;
                        }
                        //修改订单冻结类型 续租
                        if( !$orderObj->reletFreeze() ){
                            DB::rollBack();
                            set_msg('修改订单冻结状态续租失败');
                            return false;
                        }

                        //创建支付
                        if($params['pay_type'] == PayInc::FlowerStagePay){
                            // 创建支付 一次性结清
                            $pay = PayCreater::createPayment([
                                'user_id'		=> $data['user_id'],
                                'businessType'	=> OrderStatus::BUSINESS_RELET,
                                'businessNo'	=> $data['relet_no'],

                                'paymentAmount' => $data['relet_amount'],
                                'paymentFenqi'	=> $params['zuqi'],
                            ]);
                            $step = $pay->getCurrentStep();
                            //echo '当前阶段：'.$step."\n";

                            $_params = [
                                'name'			=> '订单设备续租',				//【必选】string 交易名称
                                'front_url'		=> $params['return_url'],	    //【必选】string 前端回跳地址
                            ];
                            $urlInfo = $pay->getCurrentUrl(\App\Order\Modules\Repository\Pay\Channel::Alipay, $_params );
                            DB::commit();
                            return $urlInfo;

                        }else{
                            //代扣
                            // 创建分期
                            $fenqiData = [
                                'order'=>[
                                    'order_no'=>$data['order_no'],//订单编号
                                ],
                                'sku'=>[
                                    [
                                        'zuqi'              =>  $goods['zuqi'],//租期
                                        'zuqi_type'         =>  $goods['zuqi_type'],//租期类型
                                        'all_amount'        =>  $amount,//总金额
                                        'amount'            =>  $amount,//实际支付金额
                                        'yiwaixian'         =>  0,//意外险
                                        'zujin'             =>  $goods['zujin'],//租金
                                        'pay_type'          =>  PayInc::WithhodingPay,//支付类型
                                        'goods_no'          =>  $goods['goods_no'],//商品编号
                                    ]
                                ],
                                'user'=>[
                                    'user_id'=>$params['user_id'],//用户代扣协议号
                                ],
                            ];
                            if( OrderInstalment::create($fenqiData) ){
                                //续租完成
                                // 修改设备表状态续租完成,新建设备周期数据,解锁订单
                                $reletObj = Relet::getByReletNo($data['relet_no']);
                                if( !$reletObj->setStatusOn() ){
                                    DB::rollBack();
                                    set_msg('修改续租状态完成失败');
                                    return false;
                                }
                                //查询
                                // 续租数据
                                $relet = $reletObj->getData();
                                // 设备表
                                $goodsUnitObj = ServicePeriod::getByGoodsUnitNo($params['order_no'],$goods['goods_no']);
                                if($goodsUnitObj){
                                    $goodsUnit = $goodsUnitObj->getData();
                                }else{
                                    DB::rollBack();
                                    set_msg('设备周期未找到');
                                    return false;
                                }
                                //判断租期类型
                                if($relet['zuqi_type']==OrderStatus::ZUQI_TYPE1){
                                    $t = $relet['zuqi']*(60*60*24);
                                }else{
                                    $t = $relet['zuqi']*30*(60*60*24);
                                }
                                $data = [
                                    'order_no'=>$goodsObj->order_no,
                                    'goods_no'=>$goodsObj->goods_no,
                                    'user_id'=>$goodsObj->user_id,
                                    'unit'=>$relet['zuqi_type'],
                                    'unit_value'=>$relet['zuqi'],
                                    'begin_time'=>$goodsUnit['begin_time'],
                                    'end_time'=>$goodsUnit['begin_time']+$t,
                                ];
                                //修改设备状态 续租完成
                                if( !$goodsObj->setGoodsStatusReletOff() ){
                                    //LogApi::notify("续租修改设备状态失败", $data['relet_no']);
                                    DB::rollBack();
                                    set_msg('修改设备状态续租完成失败');
                                    return false;
                                }
                                //添加设备周期表
                                if( !ServicePeriod::createService($data) ){
                                    //LogApi::notify("续租添加设备周期表失败", $data['relet_no']);
                                    DB::rollBack();
                                    set_msg('添加设备周期表失败');
                                    return false;
                                }
                                //订单解锁
                                if( !$orderObj->relieveFreeze() ){
                                    DB::rollBack();
                                    set_msg('订单解冻失败');
                                    return false;
                                }
                                //提交
                                DB::commit();
                                //LogApi::notify("续租支付成功", $data['relet_no']);
                                return true;

                            }else{
                                DB::rollBack();
                                set_msg('创建分期失败');
                                return false;
                            }

                        }

                    }else{
                        DB::rollBack();
                        set_msg('创建续租失败');
                        return false;
                    }
                }else{
                    DB::rollBack();
                    set_msg('金额错误');
                    return false;
                }

            }else{
                DB::rollBack();
                set_msg('未获取到订单商品信息');
                return false;
            }
        }catch(\Exception $e){
            DB::rollBack();
            set_msg($e->getMessage());
            return false;
        }

    }

    /**
     * 支付阶段完成时业务的回调
     *      业务类型为【续租】6的支付回调通知
     *
     * @param $params
     * @return bool
     */
    public static function callback($params)
    {
        //开启事物
        DB::beginTransaction();
        try{
            $businessType = $params['business_type'];
            $reletNo = $params['business_no']; //续租支付也就是续租编号
            $status = $params['status'];

            if ($status == "processing") {
                //续租创建的同时就会去支付,所以不需要支付中状态处理(创建=支付中)
                LogApi::notify("续租支付处理中", $reletNo);
                return true;
            } else {
                //获取续租对象
                $reletObj = Relet::getByReletNo($reletNo);
                //修改续租状态完成
                if (!$reletObj->setStatusOn()) {
                    DB::rollBack();
                    LogApi::notify("修改续租状态完成失败", $reletNo);
                    return false;
                }
                //查询
                // 续租数据
                $relet = $reletObj->getData();
                // 获取商品对象
                $goodsObj = Goods::getByGoodsId($relet['goods_id']);
                // 获取周期最新一条对象
                $goodsUnitObj = ServicePeriod::getByGoodsUnitNo($goodsObj->order_no,$goodsObj->goods_no);
                if($goodsUnitObj){
                    $goodsUnit = $goodsUnitObj->getData();
                }else{
                    DB::rollBack();
                    LogApi::notify("设备周期未找到", $reletNo);
                    return false;
                }
                //判断租期类型
                if($relet['zuqi_type']==OrderStatus::ZUQI_TYPE1){
                    $t = $relet['zuqi']*(60*60*24);
                }else{
                    $t = $relet['zuqi']*30*(60*60*24);
                }
                $data = [
                    'order_no'=>$goodsObj->order_no,
                    'goods_no'=>$goodsObj->goods_no,
                    'user_id'=>$goodsObj->user_id,
                    'unit'=>$relet['zuqi_type'],
                    'unit_value'=>$relet['zuqi'],
                    'begin_time'=>$goodsUnit['begin_time'],
                    'end_time'=>$goodsUnit['begin_time']+$t,
                ];
                //修改设备状态 续租完成
                if( !$goodsObj->setGoodsStatusReletOff() ){
                    DB::rollBack();
                    LogApi::notify("修改设备状态续租完成失败", $reletNo);
                    return false;
                }
                //添加设备周期表
                if( !ServicePeriod::createService($data) ){
                    DB::rollBack();
                    LogApi::notify("续租添加设备周期表失败", $reletNo);
                    return false;
                }
                //获取订单对象
                $orderObj = Order::getByNo($goodsObj->order_no);
                //订单解锁
                if( !$orderObj->relieveFreeze() ){
                    DB::rollBack();
                    LogApi::notify("订单解冻失败", $reletNo);
                    return false;
                }
                //提交
                DB::commit();
                LogApi::notify("续租支付成功", $reletNo);
                return true;

            }
        }catch(\Exception $e){
            DB::rollBack();
            set_msg($e->getMessage());
            return false;
        }

    }



    /**
     * 拼接去续租页数据
     *
     * @param $params
     * @return array|bool
     */
    public function getGoodsZuqi($params){
        $where = [
            ['id', '=', $params['goods_id']],
            ['user_id', '=', $params['user_id']],
            ['order_no', '=', $params['order_no']]
        ];
        $row = OrderGoodsRepository::getGoodsRow($where);
        if($row){
            if($row['zuqi_type']==OrderStatus::ZUQI_TYPE1){
                $list = publicInc::getDuanzuList();
                foreach ($list as $item){
                    $list[$item] = ['zuqi'=>$item,'zujin'=>$item*$row['zujin']];
                }
                $row['pay'][] = ['pay_type'=>PayInc::FlowerStagePay,'pay_name'=>PayInc::getPayName(PayInc::FlowerStagePay)];
            }else{
                $list = publicInc::getCangzulist();
                foreach ($list as $item){
                    $list[$item] = ['zuqi'=>$item,'zujin'=>$item*$row['zujin']];
                }
                $row['pay'][] = ['pay_type'=>PayInc::WithhodingPay,'pay_name'=>PayInc::getPayName(PayInc::WithhodingPay)];
                $row['pay'][] = ['pay_type'=>PayInc::FlowerStagePay,'pay_name'=>PayInc::getPayName(PayInc::FlowerStagePay)];
            }
            $row['list'] = $list;
            $orderInfo = OrderRepository::getInfoById($params['order_no']);
            $row['pay_type'] = $orderInfo['pay_type'];
            $row['pay_name'] = PayInc::getPayName($orderInfo['pay_type']);
            return $row;
        }else{
            set_msg('数据未查到');
            return false;
        }
    }

}