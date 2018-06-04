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
use App\Order\Modules\Inc\OrderGoodStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Inc\publicInc;
use App\Order\Modules\Inc\ReletStatus;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderPayWithholdRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\PayCreater;
use App\Order\Modules\Repository\ReletRepository;
use Illuminate\Support\Facades\DB;

class Relet
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
        $where = [
            ['id', '=', $params['goods_id']],
            ['user_id', '=', $params['user_id']],
            ['order_no', '=', $params['order_no']]
        ];
        $row = OrderGoodsRepository::getGoodsRow($where);
        if( $row ){
            if( $row['zuqi_type']==OrderStatus::ZUQI_TYPE1 ){
                if( !publicInc::getDuanzuRow($params['zuqi']) ){
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
            $amount = $row['zujin']*$params['zuqi'];

            if($amount == $params['relet_amount']){
                $data = [
                    'user_id'=>$params['user_id'],
                    'zuqi_type'=>$row['zuqi_type'],
                    'zuqi'=>$row['zuqi'],
                    'order_no'=>$params['order_no'],
                    'relet_no'=>createNo(9),
                    'create_time'=>time(),
                    'pay_type'=>$params['pay_type'],
                    'user_name'=>$params['user_name'],
                    'goods_id'=>$params['goods_id'],
                    'relet_amount'=>$params['relet_amount'],
                    'status'=>ReletStatus::STATUS1,
                ];

                if($this->reletRepository->createRelet($data)){
                    //修改设备状态 续租中
                    $rse = OrderGoods::where('id',$data['goods_id'])->update(['goods_status'=>OrderGoodStatus::RELET,'update_time'=>time()]);
                    if( !$rse ){
                        DB::rollBack();
                        set_msg('修改设备状态续租中失败');
                        return false;
                    }

                    //创建支付
                    if($params['pay_type'] == PayInc::FlowerStagePay){
                        // 创建支付 一次性结清
                        $pay = PayCreater::createPayment([
                            'user_id'		=> $data['user_id'],
                            'businessType'	=> OrderStatus::BUSINESS_RELET,
                            'businessNo'	=> $data['relet_no'],

//                        'paymentNo' => $orderInfo['trade_no'],
                            'paymentAmount' => $data['relet_amount'],
//                        'paymentChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
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
                        $withholdRow = OrderPayWithholdRepository::find($params['user_id']);

                        $fenqiData = [
                            'order'=>[
                                'order_no'=>$data['order_no'],//订单编号
                            ],
                            'sku'=>[
                                'zuqi'              =>  $row['zuqi'],//租期
                                'zuqi_type'         =>  $row['zuqi_type'],//租期类型
                                'all_amount'        =>  $amount,//总金额
                                'amount'            =>  $amount,//实际支付金额
                                'yiwaixian'         =>  0,//意外险
                                'zujin'             =>  $row['zujin'],//租金
                                'payment_type_id'   =>  PayInc::WithhodingPay,//支付类型
                            ],
                            'user'=>[
                                'withholding_no'=>$withholdRow['withhold_no'],//用户代扣协议号
                            ],
                        ];
                        if( OrderInstalment::create($fenqiData) ){
                            dd(444);
                            //修改设备表状态续租完成,新建设备周期数据
                            if( $this->reletRepository->setGoods($data['relet_no']) ){
                                DB::commit();
                                return [];
                            }else{
                                DB::rollBack();
                                set_msg('修改设备表状态,新建设备周期数据失败');
                                return false;
                            }

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
        $businessType = $params['business_type'];
        $reletNo = $params['business_no']; //续租支付也就是续租编号
        $status = $params['status'];

        if ($status == "processing") {
            //续租创建的同时就会去支付,所以不需要支付中状态处理(创建=支付中)
        } else {

            DB::beginTransaction();
            $b = ReletRepository::reletPayStatus($reletNo, ReletStatus::STATUS2);
            if (!$b) {
                DB::rollBack();
                LogApi::notify("续租修改支付状态失败", $reletNo);
                return false;
            }
            //查询
            // 续租表
            $reletRow = OrderRelet::where(['relet_no','=',$reletNo])->get(['goods_id'])->toArray();
            // 设备表
            $goodsObj = OrderGoods::where(['id'],'=',$reletRow['goods_id'])->first();
            // 设备周期表
            $goodsUnitRow = OrderGoodsUnit::where(
                ['order_no','=',$goodsObj->order_no],
                ['goods_no','=',$goodsObj->goods_no]
            )->orderBy('id','desc')->fresh()->toArray();
            //判断租期类型
            if($reletRow['zuqi_type']==OrderStatus::ZUQI_TYPE1){
                $t = $reletRow['zuqi']*(60*60*24);
            }else{
                $t = $reletRow['zuqi']*30*(60*60*24);
            }
            $data = [
                'order_no'=>$goodsObj->order_no,
                'goods_no'=>$goodsObj->goods_no,
                'user_id'=>$goodsObj->user_id,
                'unit'=>$reletRow['zuqi_type'],
                'unit_value'=>$reletRow['zuqi'],
                'begin_time'=>$goodsUnitRow['begin_time'],
                'end_time'=>$goodsUnitRow['begin_time']+$t,
            ];

            //修改订单商品状态
            $goodsObj->goods_status=OrderGoodStatus::RENEWAL_OF_RENT;
            $goodsObj->update_time=time();
            if( !$goodsObj->save() ){
                DB::rollBack();
                LogApi::notify("续租修改设备状态失败", $reletNo);
                return false;
            }
//            OrderGoods::where(['id'],'=',$reletRow['goods_id'])->update(['goods_status'=>OrderGoodStatus::RENEWAL_OF_RENT,'update_time'=>time()]);
            //添加设备周期表
            if( !OrderGoodsUnit::insert($data) ){
                DB::rollBack();
                LogApi::notify("续租添加设备周期表失败", $reletNo);
                return false;
            }
            DB::commit();

        }
        LogApi::notify("续租支付成功", $reletNo);
        return true;

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