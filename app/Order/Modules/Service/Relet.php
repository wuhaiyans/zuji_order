<?php
/**
 * Created by PhpStorm.
 * User: wangjinlin
 * Date: 2018/5/21
 * Time: 下午4:50
 */

namespace App\Order\Modules\Service;



use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Inc\publicInc;
use App\Order\Modules\Inc\ReletStatus;
use App\Order\Modules\Repository\OrderGoodsRepository;
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
                $fundauthNo = \createNo(3);
            }else{
                if( !publicInc::getCangzuRow($params['zuqi']) ){
                    DB::rollBack();
                    set_msg('租期错误');
                    return false;
                }
                $fundauthNo = \createNo();
            }
            $amount = $row['zujin']*$params['zuqi'];
            if($amount == $params['relet_amount']){
                $data = [
                    'user_id'=>$params['user_id'],
                    'zuqi_type'=>$row['zuqi_type'],
                    'zuqi'=>$row['zuqi'],
                    'order_no'=>$params['order_no'],
                    'create_time'=>time(),
                    'pay_type'=>$params['pay_type'],
                    'user_name'=>$params['user_name'],
                    'goods_id'=>$params['goods_id'],
                    'relet_amount'=>$params['relet_amount'],
                    'status'=>ReletStatus::STATUS1,
                ];

                if($this->reletRepository->createRelet($data)){

                    if(PayInc::FlowerStagePay){
                        // 创建支付 一次性结清
                        $pay = PayCreater::createPayment([
//                        'user_id'		=> $data['user_id'],
                            'businessType'	=> OrderStatus::BUSINESS_RELET,
                            'businessNo'	=> $data['order_no'],

//                        'paymentNo' => $orderInfo['trade_no'],
                            'paymentAmount' => $data['relet_amount'],
//                        'paymentChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
                            'paymentFenqi'	=> 0,
                        ]);
                    }else{
                        // 创建支付 代扣签约
                        $pay = PayCreater::createWithhold([
                            'businessType'	=> OrderStatus::BUSINESS_RELET,
                            'businessNo'	=> $data['order_no'],
                        ]);
                    }
                    $step = $pay->getCurrentStep();
                    //echo '当前阶段：'.$step."\n";

                    $_params = [
                        'name'			=> '订单续租',				//【必选】string 交易名称
                        'front_url'		=> $params['return_url'],	//【必选】string 前端回跳地址
                    ];
                    $urlInfo = $pay->getCurrentUrl(\App\Order\Modules\Repository\Pay\Channel::Alipay, $_params );
                    DB::commit();
                    return $urlInfo;

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
     * 创建支付
     *
     * @param $data
     * @return mixed
     */
    public function createPay($data){
        // 创建支付
        $pay = PayCreater::createFundauth([
            'user_id'		=> $data['user_id'],
            'businessType'	=> OrderStatus::BUSINESS_RELET,
            'businessNo'	=> $data['order_no'],

            'fundauthNo' => \createNo($data['noType']),
            'fundauthAmount' => $data['amount'],
            'fundauthChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
        ]);
        $step = $pay->getCurrentStep();
        //echo '当前阶段：'.$step."\n";

        $_params = [
            'name'			=> '订单续租',					//【必选】string 交易名称
            'front_url'		=> $data['return_url'],	//【必选】string 前端回跳地址
        ];
        $urlInfo = $pay->getCurrentUrl(\App\Order\Modules\Repository\Pay\Channel::Alipay, $_params );
        return $urlInfo;
    }

    /**
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
                $row['pay'][] = ['pay_type'=>PayInc::WithhodingPay,'pay_name'=>PayInc::getPayName(PayInc::WithhodingPay)];
                $row['pay'][] = ['pay_type'=>PayInc::FlowerStagePay,'pay_name'=>PayInc::getPayName(PayInc::FlowerStagePay)];
            }else{
                $list = publicInc::getCangzulist();
                foreach ($list as $item){
                    $list[$item] = ['zuqi'=>$item,'zujin'=>$item*$row['zujin']];
                }
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