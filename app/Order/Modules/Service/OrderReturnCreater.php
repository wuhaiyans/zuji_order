<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\NotFoundException;
use App\Lib\Warehouse\Receive;
use \App\Lib\Common\SmsApi;
use App\Order\Models\OrderReturn;
use App\Order\Modules\Inc\OrderGoodStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\ShortMessage\ReturnDeposit;
use Illuminate\Support\Facades\DB;
use \App\Order\Modules\Inc\ReturnStatus;
use \App\Order\Modules\Inc\OrderCleaningStatus;
use \App\Order\Modules\Inc\OrderStatus;
use \App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Repository\OrderReturnRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ReturnLogRepository;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderClearingRepository;
use App\Lib\Warehouse\Delivery;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;
use App\Lib\Warehouse\Logistics;
use Illuminate\Support\Facades\Log;
use App\Lib\Goods\Goods;
use App\Order\Modules\Repository\GoodsReturn\GoodsReturn;
use App\Order\Modules\Repository\OrderLogRepository;
use \App\Order\Modules\Inc\Reason;
use App\Lib\Curl;
use App\Order\Modules\Repository\Pay\WithholdQuery;
class OrderReturnCreater
{
    protected $orderReturnRepository;
    public function __construct(orderReturnRepository $orderReturnRepository)
    {
        $this->orderReturnRepository = $orderReturnRepository;
    }
    public function get_return_info($data){
        return $this->orderReturnRepository->get_return_info($data);
    }
    //添加退换货数据
    /**
     * 申请退货
     * @param array $params 业务参数
     * [
     *      'order_no'      => '',    商品编号   string   【必选】
     *      'goods_no'      => ['',''],    商品编号   string   【必选】
     *      'business_key'  => '',    业务类型   int      【必选】
     *      'loss_type'     => '',    商品损耗   int      【必选】
     *      'reason_id'     => '',    退货原因id int     【必选】
     *      'reason_text'   => '',   退货原因备注string  【可选】
     *      'user_id'       => '',   用户id      int     【必选】
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'    =>''     用户id      int      【必传】
     *      'username' =>''   用户名      string   【必传】
     *      'type'    =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return array
     * [
     *   'refund_no'   =>''  业务编号
     *   'goods_no’   =>''  商品编号
     * ]
     */
    public function add(array $params,array $userinfo){

        //开启事务
        DB::beginTransaction();
        try{

            $no_list = [];
            foreach( $params['goods_no'] as $k => $goods_no ){
                // 查商品
                $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($goods_no, true);
                if(!$goods){
                    DB::rollBack();
                    return false;
                }
                $goodsInfo=$goods->getData();//商品信息数组
                // 订单
                $order = $goods->getOrder();
                if(!$order){
                    return false;
                }
                $order_info = $order->getData();
                if( $order_info['user_id'] != $params['user_id'] ){
                    return false;
                }
                //乐百分支付的用户在14天内可申请退货
                if( $order_info['pay_type'] == PayInc::LebaifenPay){
                    if ($goodsInfo['begin_time'] > 0 &&  ($goodsInfo['begin_time'] + config('web.lebaifen_service_days')) < time()) {
                        return false;
                    }
                }else{
                    //拿趣用平台下单用户必须在收货后7天内才可以申请退换货
                    if ($goodsInfo['begin_time'] > 0 &&  ($goodsInfo['begin_time'] + config('web.month_service_days')) < time()) {
                        return false;
                    }
                }

                //获取商品数组
                $goods_info = $goods->getData();
                //代扣+预授权
                if($order_info['pay_type'] == PayInc::WithhodingPay){
                    if( $goods_info['yajin']>0 ){
                        $result['auth_unfreeze_amount'] = $goods_info['yajin'];//商品实际支付押金
                    }
                }
                //直接支付
                if( $order_info['pay_type'] == PayInc::FlowerStagePay
                    || $order_info['pay_type'] == PayInc::UnionPay
                    || $order_info['pay_type'] == PayInc::LebaifenPay) {
                    if ( $goods_info['yajin'] > 0 ) {
                        $result['refund_amount'] = $goods_info['amount_after_discount'];//应退退款金额：商品实际支付优惠后总租金
                        $result['pay_amount'] = $goods_info['amount_after_discount'];//实际支付金额=实付租金
                    }
                    if ( $goods_info['yajin'] > 0 ) {
                        $result['auth_unfreeze_amount'] = $goods_info['yajin'];//商品实际支付押金
                    }
                }

                // 创建退换货单参数
                $data = [
                    'goods_no'      => $goods_info['goods_no'],
                    'order_no'      => $goods_info['order_no'],
                    'business_key'  => $params['business_key'],
                    'reason_id'     => $params['reason_id'],
                    'reason_text'   => $params['reason_text'],
                    'user_id'       => $params['user_id'],
                    'status'        => ReturnStatus::ReturnCreated,
                    'refund_no'     => create_return_no(),
                    'pay_amount'    =>isset($result['pay_amount']) ?$result['pay_amount']: 0.00 ,            //实付金额
                    'auth_unfreeze_amount'  => isset($result['auth_unfreeze_amount']) ?$result['auth_unfreeze_amount']: 0.00,   //应退押金
                    'refund_amount'  => isset($result['refund_amount']) ?$result['refund_amount']: 0.00 ,           //应退金额
                    'create_time'   => time(),
                ];
                //创建退换货单
                $create = OrderReturnRepository::createReturn($data);
                if(!$create){
                    //事务回滚
                    DB::rollBack();
                    return false;//创建失败
                }
                $no_list[$k]['refund_no'] = $data['refund_no'];
                $no_list[$k]['goods_no'] = $goods_no;
               //退货申请
                if( $params['business_key'] == OrderStatus::BUSINESS_RETURN  ){
                    //修改冻结状态为退货中
                    $orderStatus = $order->returnOpen();
                    //修改商品信息
                    $goodsStatus = $goods->returnOpen($data['refund_no']);
                }
                //换货申请
                if( $params['business_key'] == OrderStatus::BUSINESS_BARTER ){
                    //修改冻结状态为换货中
                    $orderStatus = $order->barterOpen();
                    //修改商品信息
                    $goodsStatus = $goods->barterOpen($data['refund_no']);
                }
            }

            if( !$orderStatus ){
                //事务回滚
                DB::rollBack();
                return false;
            }
            if( !$goodsStatus ){
                //事务回滚
                DB::rollBack();
                return false;
            }
            DB::commit();
           foreach( $no_list as $no ){
                //退货短信及日志
                if( $params['business_key'] == OrderStatus::BUSINESS_RETURN ){
                    //插入操作日志
                    $goodsLog=\App\Order\Modules\Repository\GoodsLogRepository::add([
                        'order_no'     =>$params['order_no'],
                        'action'       =>'退货单生成',
                        'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN,
                        'business_no'  =>$no['refund_no'],
                        'goods_no'     =>$no['goods_no'],
                        'operator_id'  =>$userinfo['uid'],
                        'operator_name'=>$userinfo['username'],
                        'operator_type'=>$userinfo['type'],
                        'msg'           =>'用户申请退货',
                    ],$isCorntab=FALSE);
                    //退货申请成功发送短信
                    $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI, $no['refund_no'] ,SceneConfig::RETURN_APPLY);
                    $b=$orderNoticeObj->notify();
                    Log::debug($b?"Order :".$goods_info['order_no']." IS OK":"IS error");
                }
                //换货
               if( $params['business_key'] == OrderStatus::BUSINESS_BARTER ) {
                    //插入操作日志
                   $goodsLog=\App\Order\Modules\Repository\GoodsLogRepository::add([
                       'order_no'     =>$params['order_no'],
                       'action'       =>'换货单生成',
                       'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_BARTER,
                       'business_no'  =>$no['refund_no'],
                       'goods_no'     =>$no['goods_no'],
                       'operator_id'  =>$userinfo['uid'],
                       'operator_name'=>$userinfo['username'],
                       'operator_type'=>$userinfo['type'],
                       'msg'           =>'用户申请换货',
                   ],$isCorntab=FALSE);
               }
               if(!$goodsLog){
                   return false;
               }
           }
            return $no_list;
        }catch( \Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }

    }
    /**
     *申请退款
	 * 2018-07-28 注意：待退款金额为0时，不能直接关闭订单（订单关闭，需要做很多事情，不单单是更新订单状态），必须创建退款单
     * @param array $params 业务参数
     * [
     *       'order_no'      => '',   商品编号 string  【必选】
     *       'user_id'      => '',    用户id  int     【必选】
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return array ['refund_no'=>'']  //业务编号
     */
    public function createRefund(array $params,array $userinfo){
        //开启事务
        DB::beginTransaction();
        try {
            //获取订单信息
            $order = \App\Order\Modules\Repository\Order\Order::getByNo($params['order_no'], true);
            if ( !$order ){
                return false;
            }
            $order_info = $order->getData();
            //订单必须是已支付，未收货
            if( $order_info['order_status'] != OrderStatus::OrderPayed  && $order_info['order_status'] != OrderStatus::OrderPaying && $order_info['order_status'] != OrderStatus::OrderInStock){
                return false;
            }
            //如果订单是备货中状态，通知收发货系统取消发货
            if( $order_info['order_status'] == OrderStatus::OrderInStock ){
                $cancel = Delivery::cancel($params['order_no']);
                if( !$cancel ){
                    //事务回滚
                    DB::rollBack();
                    return false;//取消发货失败
                }
            }
            //订单必须是不冻结状态
            if($order_info['freeze_type'] != OrderFreezeStatus::Non){
                return false;//订单正在操作中
            }
            //代扣+预授权
            if($order_info['pay_type'] == PayInc::WithhodingPay ){
                $data['auth_unfreeze_amount'] = $order_info['order_yajin'];//应退押金=实付押金
            }
            //直接支付或小程序
            if($order_info['pay_type'] == PayInc::FlowerStagePay
                || $order_info['pay_type'] == PayInc::UnionPay
                || $order_info['pay_type'] == PayInc::MiniAlipay){
                $data['pay_amount'] = $order_info['order_amount']+$order_info['order_insurance'];//实际支付金额=实付租金+意外险
                $data['auth_unfreeze_amount'] = $order_info['order_yajin'];//应退押金=实付押金
                $data['refund_amount'] = $order_info['order_amount']+$order_info['order_insurance'];//应退金额

            }
            //获取订单的支付信息
            $pay_result = $this->orderReturnRepository->getPayNo(OrderStatus::BUSINESS_ZUJI,$params['order_no']);
            if(!$pay_result){
                return false;
            }
            //乐百分支付
            if($order_info['pay_type'] == PayInc::LebaifenPay){
                $data['pay_amount'] = $pay_result['payment_amount'];//实际支付金额=支付金额
            }
            //冻结订单
            $orderFreeze = $order->refundOpen();
            if( !$orderFreeze ){
                //事务回滚
                DB::rollBack();
                return false;//订单冻结失败
            }
            //获取商品信息
           $goods = \App\Order\Modules\Repository\Order\Goods::getOrderNo($params['order_no'],true);
            if( !$goods ){
                return false;
            }
            //更新商品状态为退款中
            $goodsRefund = $goods->orderRefund();
            if( !$goodsRefund ){
                //事务回滚
                DB::rollBack();
                return false;//商品状态修改为退款中失败
            }


            //创建退款单
            $data['business_key'] = OrderStatus::BUSINESS_REFUND;
            $data['order_no'] = $params['order_no'];
            $data['user_id'] = $params['user_id'];

            $data['status'] = ReturnStatus::ReturnCreated;
            $data['refund_no'] = create_return_no();
            $data['create_time'] = time();
            //创建申请退款记录
            $addresult = OrderReturnRepository::createRefund($data);
            if( !$addresult ){
                //事务回滚
                DB::rollBack();
                return false;//创建失败
            }
            $no_list['refund_no'] = $data['refund_no'];
            //操作日志
            OrderLogRepository::add($userinfo['uid'],$userinfo['username'],$userinfo['type'],$params['order_no'],"退款","申请退款");
            //事务提交
            DB::commit();
            return $no_list;

        }catch( \Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }
    }

    /**
     * 退换货审核同意
     * @param array $params  业务参数
     * [
     * 'business_key'    =>'',  业务类型  int【必选】
     *  'detail'=> [
     *     [
     *         'refund_no'  =>'',   业务编号     string   【必传】
     *         'remark'     =>'',   审核备注     string   【必传】
     *         'reason_key' =>''    审核原因id  int      【必传】
     *         'audit_state'=>''true 审核通过，false 审核不通过  【必传】
     *    ],
     *     [
     *         'refund_no'  =>'',     业务编号    string 【必传】
     *         'remark'     =>'',     审核备注    string 【必传】
     *         'reason_key' =>''     审核原因id  int    【必传】
     *         'audit_state'=>''true 审核通过，false 审核不通过  【必传】
     *    ],
     *     ]
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'    =>''     用户id      int      【必传】
     *      'username' =>''   用户名      string   【必传】
     *      'type'    =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return bool
     */
    public function returnOfGoods($params,$userinfo){
        //开启事务
        DB::beginTransaction();
        try {
            //审核同意的编号
            $yes_list = [];
            //审核拒绝的编号
            $no_list = [];
            foreach ( $params['detail'] as $k => $v ) {
                $param= filter_array($params['detail'][$k],[
                    'refund_no'  => 'required',    //业务编号
                    'remark'     => 'required',    //审核备注
                    'reason_key' => 'required',    //审核原因id
                    'audit_state'=> 'required',    //审核状态
                ]);
                if(count($param)<4){
                    return  false;
                }
                //获取退换货单的信息
                $return = GoodsReturn::getReturnByRefundNo($params['detail'][$k]['refund_no']);
                if (!$return) {
                    return false;
                }
                $returnInfo[$k] = $return->getData();
                //获取商品信息
                $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($returnInfo[$k]['goods_no']);
                if(!$goods){
                    return false;
                }
               $goods_info = $goods->getData();
                //审核同意
                if ($params['detail'][$k]['audit_state'] == 'true'){
                    //更新审核状态为同意
                    $accept = $return->accept($params['detail'][$k]);
                    if (!$accept) {
                        //事务回滚
                        DB::rollBack();
                        return false;
                    }
                    $order=$returnInfo[$k]['order_no'];
                    $data[$k]['goods_no']    = $returnInfo[$k]['goods_no'];
                    $refund[$k]['refund_no'] = $params['detail'][$k]['refund_no'];
                    //获取商品扩展信息
                    $goodsDelivery[$k]=\App\Order\Modules\Repository\Order\DeliveryDetail::getGoodsDelivery($order,$data);
                    if(!$goodsDelivery[$k]){
                        return false;
                    }
                    $goodsDeliveryInfo[$k] = $goodsDelivery[$k]->getData();
                    $goodsDeliveryInfo[$k]['quantity']  = $goods_info['quantity'];//商品数量
                    $goodsDeliveryInfo[$k]['refund_no'] = $params['detail'][$k]['refund_no'];//退换货单号
                    $goodsDeliveryInfo[$k]['goods_name'] = $goods_info['goods_name'];//商品名称

                    $yes_list[] = $params['detail'][$k]['refund_no'];
                    // 退货
                    if($params['business_key'] == OrderStatus::BUSINESS_RETURN ){
                        $type=2;
                        //插入操作日志
                        $goodsLog=\App\Order\Modules\Repository\GoodsLogRepository::add([
                            'order_no'     =>$order,
                            'action'       =>'退货审核',
                            'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN,
                            'business_no'  =>$params['detail'][$k]['refund_no'],
                            'goods_no'     =>$returnInfo[$k]['goods_no'],
                            'operator_id'  =>$userinfo['uid'],
                            'operator_name'=>$userinfo['username'],
                            'operator_type'=>$userinfo['type'],
                            'msg'           =>'退货审核同意',
                        ],$isCorntab=FALSE);
                    }
                    //换货
                    if( $params['business_key'] == OrderStatus::BUSINESS_BARTER ) {
                        $type=3;
                        //插入操作日志
                        $goodsLog=\App\Order\Modules\Repository\GoodsLogRepository::add([
                            'order_no'     =>$order,
                            'action'       =>'换货审核',
                            'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_BARTER,
                            'business_no'  =>$params['detail'][$k]['refund_no'],
                            'goods_no'     =>$returnInfo[$k]['goods_no'],
                            'operator_id'  =>$userinfo['uid'],
                            'operator_name'=>$userinfo['username'],
                            'operator_type'=>$userinfo['type'],
                            'msg'           =>'换货审核同意',
                        ],$isCorntab=FALSE);
                    }
                } else {
                    //更新审核状态为拒绝
                    $refuse = $return->refuse($params['detail'][$k]);
                    if (!$refuse){
                        //事务回滚
                        DB::rollBack();
                        return false;
                    }
                    $no_list[] = $params['detail'][$k]['refund_no'];
                    //更新商品状态
                    $returnClose = $goods->returnClose();
                    if (!$returnClose){
                        //事务回滚
                        DB::rollBack();
                        return false;
                    }

                    // 退货
                    if($params['business_key'] == OrderStatus::BUSINESS_RETURN ){
                        //插入操作日志
                        $goodsLog=\App\Order\Modules\Repository\GoodsLogRepository::add([
                            'order_no'     =>$returnInfo[$k]['order_no'],
                            'action'       =>'退货审核',
                            'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN,
                            'business_no'  =>$params['detail'][$k]['refund_no'],
                            'goods_no'     =>$returnInfo[$k]['goods_no'],
                            'operator_id'  =>$userinfo['uid'],
                            'operator_name'=>$userinfo['username'],
                            'operator_type'=>$userinfo['type'],
                            'msg'           =>'退货审核拒绝',
                        ],$isCorntab=FALSE);

                    }
                    //换货
                    if( $params['business_key'] == OrderStatus::BUSINESS_BARTER ) {
                        //插入操作日志
                        $goodsLog=\App\Order\Modules\Repository\GoodsLogRepository::add([
                            'order_no'     =>$returnInfo[$k]['order_no'],
                            'action'       =>'换货审核',
                            'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN,
                            'business_no'  =>$params['detail'][$k]['refund_no'],
                            'goods_no'     =>$returnInfo[$k]['goods_no'],
                            'operator_id'  =>$userinfo['uid'],
                            'operator_name'=>$userinfo['username'],
                            'operator_type'=>$userinfo['type'],
                            'msg'           =>'换货审核拒绝',
                        ],$isCorntab=FALSE);
                    }

                    if (!$goodsLog){
                        //事务回滚
                        DB::rollBack();
                        return false;
                    }
                }
                //获取商品信息
                $goodsStatus = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($returnInfo[$k]['goods_no']);
                if(!$goodsStatus){
                    return false;
                }
                $goodsInfo[$k] = $goodsStatus->getData();
            }
            //获取此订单的商品是否还有处理中的设备，没有则解冻
            $status=false;
            foreach($goodsInfo as $k=>$v){
                if($v['goods_status'] == OrderGoodStatus::RENTING_MACHINE){
                    $status = true;
                }else{
                    $status = false;
                    break;
                }
            }
            if( $status == true ){
                //解冻订单
                $orderInfo   = \App\Order\Modules\Repository\Order\Order::getByNo($returnInfo[0]['order_no']);
                $updateOrder = $orderInfo->returnClose();
                if(!$updateOrder){
                    DB::rollBack();
                    return false;
                }
            }
            //存在审核同意商品
            if(isset($goodsDeliveryInfo)){
                //获取用户下单信息
                $userAddress = \App\Order\Modules\Repository\Order\Address::getByOrderNo($order);
                if(!$userAddress){
                    return false;
                }
                $user_info = $userAddress->getData();

                $user_data['customer_mobile'] = $user_info['consignee_mobile'];//用户手机号
                $user_data['customer'] = $user_info['name'];             //用户名
                $user_data['customer_address'] = $user_info['address_info']; //用户地址
                $user_data['business_key'] = $params['business_key'];//业务类型
                foreach($goodsDeliveryInfo as $k=>$v){
                    $receive_data[$k] =[
                        'goods_no'  => $goodsDeliveryInfo[$k]['goods_no'],
                        'goods_name'  => $goodsDeliveryInfo[$k]['goods_name'],
                        'refund_no' =>$goodsDeliveryInfo[$k]['refund_no'],
                        'serial_no' => $goodsDeliveryInfo[$k]['serial_number'],
                        'quantity'  => $goodsDeliveryInfo[$k]['quantity'],
                        'imei'     =>$goodsDeliveryInfo[$k]['imei1'],
                        'business_no' =>$goodsDeliveryInfo[$k]['refund_no'],
                      //  'imei2'     =>$goodsDeliveryInfo[$k]['imei2'],
                       // 'imei3'     =>$goodsDeliveryInfo[$k]['imei3'],
                    ];
                }
                LogApi::debug("创建收货单参数",$user_data);
                $create_receive = Receive::create($order,$type,$receive_data,$user_data);//创建待收货单
                if(!$create_receive){
                    //事务回滚
                    DB::rollBack();
                    return false;//创建待收货单失败
                }
                //更新退换货单的收货编号
                 foreach($refund as $k=>$v){
                    $getReturn = \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($v['refund_no']);
                    $updateReceive = $getReturn->updateReceive($create_receive);
                    if(!$updateReceive){
                        //事务回滚
                        DB::rollBack();
                       return false;
                    }
                 }


            }
            //事务提交
            DB::commit();
            //审核发送短信
            if($params['business_key']==OrderStatus::BUSINESS_RETURN){
                if($yes_list){
                    foreach( $yes_list as $no ) {
                        //短信
                        $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$no,SceneConfig::RETURN_APPLY_AGREE);
                        $b=$orderNoticeObj->notify();
                        Log::debug($b?"Order :".$order." IS OK":"IS error");
                    }
                }
                if($no_list){
                    foreach( $no_list as $no ){
                            //短信
                            $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$no,SceneConfig::RETURN_APPLY_DISAGREE);
                            $b=$orderNoticeObj->notify();
                            Log::debug($b?"Order :".$returnInfo[0]['order_no']." IS OK":"IS error");
                    }
                }
            }

            return true;

        }catch( \Exception $exc){
            LogApi::debug("请求异常",$exc->getMessage());
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }




    }

    /**
     * 订单退款审核
     * @param $param
     * [
     *    'refund_no'   =>''  //退款单号  string 【必传】
     *    'status'      =>''  //审核状态  int   【必传】
     *    'remark'      =>''  //审核备注  string【必传】
     *
     * ]
     *  @param array $userinfo 业务参数
     * [
     *       'uid'        =>'',    用户id  int【必传】
     *       'type'       =>'',   请求类型（2前端，1后端） int 【必传】
     *      ‘username’  =>‘’，用户名 string【必传】
     * ]
     * @return  bool
     */
    public function refundApply(array $param,array $userinfo){
        //开启事务
        DB::beginTransaction();
        try{
            //获取退款单信息
            $return = \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($param['refund_no']);
            if(!$return){
                return false;
            }
            $return_info = $return->getData();
            //获取订单信息
            $order = \App\Order\Modules\Repository\Order\Order::getByNo($return_info['order_no']);
            if(!$order){
                return false;
            }
            $order_info = $order->getData();
            if($param['status'] == 0){
                //更新退款单状态为同意
                $returnApply = $return->refundAgree($param['remark']);
                if(!$returnApply){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
				//-+------------------------------------------------------------
				// 2018-07-28 liuhongxing
				// 如果待退款金额为0，则直接调退款成功的回调
				if( !(
						$return_info['pay_amount']>0 
						|| $return_info['auth_unfreeze_amount']>0 
						|| $return_info['auth_deduction_amount']>0
					) ){
                    //如果是小程序的订单
                    if($order_info['order_type'] == OrderStatus::orderMiniService){
                        //查询芝麻订单
                        $miniOrderInfo = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo($return_info['order_no']);
                        $data = [
                            'out_order_no' => $return_info['order_no'],//商户端订单号
                            'zm_order_no' => $miniOrderInfo['zm_order_no'],//芝麻订单号
                            'remark' => $param['remark'],//订单操作说明
                            'app_id' => $miniOrderInfo['app_id'],//小程序appid
                        ];
                        //通知芝麻取消请求
                        $canceRequest = \App\Lib\Payment\mini\MiniApi::OrderCancel($data);
                        if( !$canceRequest){
                            return false;
                        }
                    }
					// 不需要清算，直接调起退款成功
					$b = self::refundUpdate([
						'business_type' => $return_info['business_key'],
						'business_no'	=> $return_info['refund_no'],
						'status'		=> 'success',
					], $userinfo);
					if( $b==true ){ // 退款成功，已经关闭退款单，并且已经更新商品和订单）
						//事务提交
						DB::commit();
						return true;
					}
					// 失败
					DB::rollBack();
					return false;
				}
				//-+------------------------------------------------------------
                //判断退款是否为小程序订单
                if($order_info['order_type'] != OrderStatus::orderMiniService){
                    //获取订单的支付信息
                    $pay_result = $this->orderReturnRepository->getPayNo(1,$return_info['order_no']);
                    if(!$pay_result){
                        return false;
                    }
                    $create_data['out_payment_no']=$pay_result['payment_no'];//支付编号
                    $create_data['out_auth_no']=$pay_result['fundauth_no'];//预授权编号
                }

                //创建清单
                $create_data['order_no']=$order_info['order_no'];//订单类型
                if($order_info['pay_type'] == PayInc::LebaifenPay){
                    $create_data['order_type']= OrderStatus::miniRecover;//订单类型
                }else{
                    $create_data['order_type']=$order_info['order_type'];//订单类型
                }
                $create_data['business_type']=OrderCleaningStatus::businessTypeRefund;//业务类型
                $create_data['business_no']=$return_info['refund_no'];//业务编号
                $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态待退款
                $create_data['refund_amount']=$return_info['pay_amount'];//应退金额
                $create_data['auth_unfreeze_amount']=$return_info['auth_unfreeze_amount'];//应退押金
                $create_data['auth_deduction_amount']=$return_info['auth_deduction_amount'];//应扣押金
                //退款：直接支付
              /*  if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::FlowerStagePay ||$order_info['pay_type']==\App\Order\Modules\Inc\PayInc::UnionPay){
                    $create_data['refund_amount']=$order_info['order_amount']+$order_info['order_insurance'];//退款金额=订单实际支付总租金+意外险总金额
                    $create_data['auth_unfreeze_amount']=$order_info['order_yajin'];//订单实际支付押金
                }
                //退款：代扣+预授权
                if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::WithhodingPay){
                   // $create_data['refund_amount']=$order_info['order_amount']+$order_info['order_insurance'];//退款金额=订单实际支付总租金+意外险总金额
                    $create_data['auth_unfreeze_amount']=$order_info['order_yajin'];//订单实际支付押金
                }*/
                if( $create_data['refund_amount']>0 || $create_data['auth_unfreeze_amount']>0){
                    $create_clear=\App\Order\Modules\Repository\OrderClearingRepository::createOrderClean($create_data);//创建退款清单
                    if(!$create_clear){
                        //事务回滚
                        DB::rollBack();
                        return false;//创建退款清单失败
                    }
                }
                //插入操作日志
                OrderLogRepository::add($userinfo['uid'],$userinfo['username'],$userinfo['type'],$return_info['order_no'],"退款","审核同意");

            }else{

                //更新退款单状态为审核拒绝
                $returnApply=$return->refundAccept($param['remark']);
                if(!$returnApply){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                //更新订单状态
                $orderApply = $order->returnClose();
                if(!$orderApply){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                //获取商品信息
                $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info['goods_no'],true);
                if( !$goods ){
                    return false;
                }
                //更新商品状态为退款中
                $goodsRefund = $goods->refundRefuse();
                if( !$goodsRefund ){
                    //事务回滚
                    DB::rollBack();
                    return false;//商品状态修改为退款中失败
                }


                //插入操作日志
                OrderLogRepository::add($userinfo['uid'],$userinfo['username'],$userinfo['type'],$return_info['order_no'],"退款","审核拒绝");

            }

            //事务提交
            DB::commit();
            return true;
        }catch( \Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }

    }

    /**
     * 取消退货申请
     * @param $params   业务参数
     * [
     *    'refund_no'    => ['','']  //退货单号   string 【必传】
     *    'user_id'     => ''       //用户id      int    【必传】
     *    'business_key'=> ''      //业务类型     int    【必传】
     * ]
     * @throws \Exception
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return bool
     */
    public function cancelApply(array $params,array $userinfo){
        LogApi::debug("获取取消申请的参数",$params);
        //开启事务
        DB::beginTransaction();
        try{
            foreach($params['refund_no'] as $refund_no){
                //查询退货单信息
                $return = \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($refund_no);
                if(!$return){
                   return false;
                }
                $return_info[$refund_no] = $return->getData();
                LogApi::debug("查询退货单信息",$return_info);
                if($return_info[$refund_no]['user_id']!=$params['user_id']){
                    return false;
                }
                //收货之后不允许取消
                if($return_info[$refund_no]['status']>ReturnStatus::ReturnAgreed){
                    LogApi::debug("收货之后不允许取消,获取退换单数据",$return_info[$refund_no]);
                    return false;
                }
                //如果审核通过通知收发货取消收货
                if($return_info[$refund_no]['status'] == ReturnStatus::ReturnAgreed){
                   $cancelReceive = \App\Lib\Order\Receive::cancelReceive($return_info[$refund_no]['receive_no']);
                   if(!$cancelReceive){
                       DB::rollBack();
                       return false;//取消收货未执行成功
                   }
                }
                //更新退换货状态为已取消
                $cancelApply = $return->close();
                if(!$cancelApply){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                //修改商品状态为租用中
                $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info[$refund_no]['goods_no'] );
                if(!$goods->returnCancel()){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                $order_no = $return_info[$refund_no]['order_no'];//订单编号

            }
            //获取订单信息
            $order = \App\Order\Modules\Repository\Order\Order::getByNo($order_no);
            //解冻订单
            if(!$order->returnClose()){
                //事务回滚
                DB::rollBack();
                return false;
            }
            if($params['business_key']==OrderStatus::BUSINESS_RETURN){
                //插入操作日志
                $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
                    'order_no'     =>$return_info[$refund_no]['order_no'],
                    'action'       =>'取消退货',
                    'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN,
                    'business_no'  =>$return_info[$refund_no]['refund_no'],
                    'goods_no'     =>$return_info[$refund_no]['goods_no'],
                    'operator_id'  =>$userinfo['uid'],
                    'operator_name'=>$userinfo['username'],
                    'operator_type'=>$userinfo['type'],
                    'msg'           =>'已取消退货申请',
                ],$isCorntab=FALSE);

            }else{
                //插入操作日志
                $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
                    'order_no'     =>$return_info[$refund_no]['order_no'],
                    'action'       =>'取消换货',
                    'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_BARTER,
                    'business_no'  =>$return_info[$refund_no]['refund_no'],
                    'goods_no'     =>$return_info[$refund_no]['goods_no'],
                    'operator_id'  =>$userinfo['uid'],
                    'operator_name'=>$userinfo['username'],
                    'operator_type'=>$userinfo['type'],
                    'msg'           =>'已取消换货申请',
                ],$isCorntab=FALSE);
            }
            if(!$goodsLog){
                DB::rollBack();
               return false;
            }

            DB::commit();
            return true;
        }catch( \Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;
           }

    }

    /**
     * 取消退款
     *  @param array params 业务参数
     * [
     *       'user_id'         =>'', 用户id   int    【必传】
     *       'refund_no'       =>'',业务编码  string 【必传】
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return bool
     */
    public function cancelRefund($params,$userinfo){
        //开启事务
        DB::beginTransaction();
        try{
            //获取退款单信息
            $return = \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($params['refund_no']);
            if(!$return){
                return false;
            }
            $return_info = $return->getData();
            //获取订单信息
            $order = \App\Order\Modules\Repository\Order\Order::getByNo($return_info['order_no']);
            if($return_info['user_id']!=$params['user_id']){
                return false;
            }
            //审核过之后，退款中，已退款不能取消
            if($return_info['status']==ReturnStatus::ReturnAgreed || $return_info['status']==ReturnStatus::ReturnTui || $return_info['status']==ReturnStatus::ReturnTuiKuan){
                return false;
            }
            //更新退款单状态为已取消
            $cancelApply = $return->close();
            if(!$cancelApply){
                //事务回滚
                DB::rollBack();
                return false;
            }
            //更新订单状态
            $orderApply = $order->returnClose();
            if(!$orderApply){
                //事务回滚
                DB::rollBack();
                return false;
            }
            //操作日志
            OrderLogRepository::add($userinfo['uid'],$userinfo['username'],$userinfo['type'],$return_info['order_no'],"退款","取消退款申请");

            DB::commit();
            return true;
        }catch( \Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }
    }


    /**
     * 获取退换货订单列表方法
     * @param $params
     * [
     *   'page'          =>''     页数     int   【必传】
     *   'size'          =>''     条数     int   【必传】
     *   'begin_time'    =>''     开始时间 int   【可选】
     *   'end_time'      =>''     结束时间 int   【可选】
     *   'business_key'  =>''     业务类型 int   【可选】
     *   'keywords'      =>''     搜索关键词 string 【可选】
     *   'kw_type'       =>''     搜索类型   string 【可选】
     *   'return_status' =>''     退换货状态  int   【可选】
     *   'user_id'       =>''     用户id      int   【可选】
     *   'reason_key'    =>''     审核原因id  int   【可选】
     *   'order_status'  =>''     订单状态    int   【可选】
     *   'appid'         =>''      渠道入口id int   【可选】
     *
     * ]
     * @return array
     *
     */
    public function get_list($params)
    {
        $page = empty($params['page']) ? 1 : $params['page'];
        $size = !empty($params['size']) ? $params['size'] : config('web.pre_page_size');
        $where = [];
        if (isset($params['begin_time'])!= '') {
            $where['begin_time'] = strtotime($params['begin_time']);
        }
        if (isset($params['end_time'] )!= '') {
            $where['end_time'] = strtotime($params['end_time']);
        }
        if(isset($params['business_key'])>0) {
            $where['business_key'] = intval($params['business_key']);
        }
        if (isset($params['keywords'])!= '') {
            if (isset($params['kw_type'])&&$params['kw_type']=='goods_name') {
                $where['goods_name'] = $params['keywords'];
            }elseif(isset($params['kw_type'])&&$params['kw_type']=='order_no') {
                $where['order_no'] = $params['keywords'];
            }elseif(isset($params['kw_type'])&&$params['kw_type']=='mobile'){
                $where['mobile'] = $params['keywords'];
            }
        }
        if (isset($params['return_status']) && $params['return_status'] > 0) {
            $where['status'] = intval($params['return_status']);
        }
        if (isset($params['user_id'])) {
            $where['user_id'] = $params['user_id'];
        }
        if (isset($params['reason_key'])) {
            $where['reason_key'] = $params['reason_key'];
        }
        if (isset($params['order_status'])) {
            $where['order_status'] = $params['order_status'];
        }
        if (isset($params['appid'])) {
            $where['appid'] = $params['appid'];
        }
        // 查询退货申请单
        $additional['page'] = $page;
        $additional['size'] = $size;
        $where = $this->_parse_order_where($where);
        $data = $this->orderReturnRepository->get_list($where, $additional);
        foreach($data['data'] as $k=>$v){
            //是否显示审核操作
            if($data['data'][$k]->status!=ReturnStatus::ReturnCreated){
                $data['data'][$k]->operate_status=false;
            }else{
                $data['data'][$k]->operate_status=true;
            }
            //默认确认收货按钮不显示
            $data['data'][$k]->receive_button=false;
            //默认检测不合格拒绝退款按钮不显示
            $data['data'][$k]->check_button=false;
            //业务类型
            if($data['data'][$k]->business_key==OrderStatus::BUSINESS_REFUND){
                $data['data'][$k]->business_name=OrderStatus::getBusinessName(OrderStatus::BUSINESS_REFUND);//退款业务
            }elseif($data['data'][$k]->business_key==OrderStatus::BUSINESS_RETURN){
                $data['data'][$k]->business_name=OrderStatus::getBusinessName(OrderStatus::BUSINESS_RETURN);//退货业务
            }elseif($data['data'][$k]->business_key==OrderStatus::BUSINESS_BARTER){
                //是否显示确认收货按钮   true ：显示  false：不显示
                if($data['data'][$k]->status == ReturnStatus::ReturnDelivery){
                    $data['data'][$k]->receive_button=true;
                }else{
                    $data['data'][$k]->receive_button=false;
                }
                $data['data'][$k]->business_name=OrderStatus::getBusinessName(OrderStatus::BUSINESS_BARTER);//换货业务
            }
            //订单状态
            if($data['data'][$k]->order_status==OrderStatus::OrderWaitPaying){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderWaitPaying);//待支付
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderPaying){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderPaying);//支付中
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderPayed){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderPayed);//已支付
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderInStock){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderInStock);//备货中
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderDeliveryed){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderDeliveryed);//已发货
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderInService){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderInService);//租用中
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderCancel){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderCancel);//已取消完成(未支付)
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderClosedRefunded){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderClosedRefunded);//关闭（支付完成后退款）
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderCompleted){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderCompleted);//已完成
            }
            //（退款、退机、换机）状态
            if($data['data'][$k]->status==ReturnStatus::ReturnCreated){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnCreated);//提交申请
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnAgreed){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnAgreed);//同意
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnDenied){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnDenied);//拒绝
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnCanceled){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnCanceled);//取消退货申请
            }elseif($data['data'][$k]->status == ReturnStatus::ReturnReceive){
                if($data['data'][$k]->business_key == OrderStatus::BUSINESS_RETURN){
                    if($data['data'][$k]->evaluation_status == ReturnStatus::ReturnEvaluationFalse){
                        $data['data'][$k]->check_button=true;
                    }else{
                        $data['data'][$k]->check_button=false;
                    }
                }

                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnReceive);//已收货
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnTuiHuo){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiHuo);//已退货
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnHuanHuo){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnHuanHuo);//已换货
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnTuiKuan){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiKuan);//已退款
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnTui){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTui);//退款中
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnDelivery){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnDelivery);//已发货
            }
            //退换货问题
            if(isset($data['data'][$k]->reason_key)){
                if($data['data'][$k]->reason_key == ReturnStatus::ReturnGoodsQuestion){
                    $data['data'][$k]->reason_name=ReturnStatus::getQuestionName(ReturnStatus::ReturnGoodsQuestion);
                }elseif($data['data'][$k]->reason_key == ReturnStatus::ReturnUserQuestion){
                    $data['data'][$k]->reason_name=ReturnStatus::getQuestionName(ReturnStatus::ReturnUserQuestion);
                }
            }else{
                $data['data'][$k]->reason_name='';
            }
        }
        return $data;
    }

    /** 查询条件过滤
     * @param array $where	【可选】查询条件
     * [
     *      'user_id'      => '',	//【可选】用户id
     *      'business_key' => '',	//【必选】string；业务类型
     *      'status'       =>''     //【可选】int；阶段
     *      'begin_time'   =>''    //【可选】int；开始时间戳
     *      'end_time'     =>''    //【可选】int；截止时间戳
     *      'goods_name'   => '',	//【可选】设备名称
     *      'order_no'     => '',	//【可选】string；订单编号
     *      'reason_key'   =>''      //【可选】int；退货问题
     *      'user_mobile'  =>''      //【可选】int；下单用户手机号
     *      'order_status' =>''      //【可选】int；  订单状态
     *      'appid'        =>''      //【可选】int；应用来源
     *
     * ]
     * @return array	查询条件
     */
    public function _parse_order_where($where=[]){
        // 结束时间（可选），默认为为当前时间
        if( !isset($where['end_time']) ){
            $where['end_time'] = time();
        }
        if( isset($where['user_id'])){
            $where1[] = ['order_return.user_id', '=', $where['user_id']];
        }
        // 开始时间（可选）
        if( isset($where['begin_time'])){
            if( $where['begin_time']>$where['end_time'] ){
                return false;
            }
            $where1[] = ['order_return.create_time', '>=', $where['begin_time']];
            $where1[] = ['order_return.create_time', '<', ($where['end_time']+3600*24)];
        }else{
            $where1[] = ['order_return.create_time', '<', $where['end_time']];
        }
        unset($where['begin_time']);
        unset($where['end_time']);
        // order_no 订单编号查询，使用前缀模糊查询
        if( isset($where['order_no']) ){
            $where1[] = ['order_return.order_no', '=', $where['order_no']];
        }
        //退换货原因
        if( isset($where['reason_key']) ){
            $where1[] = ['order_return.reason_key', '=',$where['reason_key']];
        }
        // order_no 订单编号查询，使用前缀模糊查询
        if( isset($where['goods_name'])){
            $where1[] = ['order_goods.goods_name', 'like', '%'.$where['goods_name'].'%'];
        }
        if(isset($where['mobile'])){
            $where1[] = ['order_info.mobile','=',$where['mobile']];
        }
        //退换货单状态
        if( isset($where['status']) ){
            $where1[] = ['order_return.status', '=', $where['status']];
        }
        //订单状态
        if( isset($where['order_status']) ){
            $where1[] = ['order_info.status', '=', $where['order_status']];
        }
        //渠道入口id
        if(isset($where['appid']) ){
            $where1[] = ['order_info.appid', '=', $where['appid']];
        }
        //业务类型
        if( isset($where['business_key'])>0 ){
            $where1[] = ['order_return.business_key', '=', $where['business_key']];
        }else{
            $where1[] = ['order_return.business_key', '!=', OrderStatus::BUSINESS_REFUND];
        }
        return $where1;
    }

    /**
     * 导出，获取退换货订单列表方法
     * @param $params
     *   [
     *      'user_id'      => '',	       //【可选】用户id
     *      'business_key' => '',	       //【必选】string； 业务类型
     *      'return_status' =>''           //【可选】int；   退换货状态
     *      'begin_time'   =>''           //【可选】int；    开始时间戳
     *      'end_time'     =>''          //【可选】int；    截止时间戳
     *      'goods_name'   => '',	    //【可选】string    设备名称
     *      'order_no'     => '',	    //【可选】string；  订单编号
     *      'reason_key'   =>''        //【可选】int；     退货问题
     *      'user_mobile'  =>''       //【可选】int；     下单用户手机号
     *      'order_status' =>''       //【可选】int；     订单状态
     *      'appid'        =>''      //【可选】int；      应用来源
     *
     * ]
     * @return array
     *
     */
    public function getReturnList($params=array())
    {
        $where = [];
        //开始时间
        if (isset($params['begin_time'])!= '') {
            $where['begin_time'] = strtotime($params['begin_time']);
        }
        //结束时间
        if (isset($params['end_time'] )!= '') {
            $where['end_time'] = strtotime($params['end_time']);
        }
        //业务类型
        if(isset($params['business_key'])>0) {
            $where['business_key'] = intval($params['business_key']);
        }
        //搜索关键字
        if (isset($params['keywords'])!= '') {
            if (isset($params['kw_type'])&&$params['kw_type']=='goods_name') {
                $where['goods_name'] = $params['keywords'];
            }elseif(isset($params['kw_type'])&&$params['kw_type']=='order_no') {
                $where['order_no'] = $params['keywords'];
            }elseif(isset($params['kw_type'])&&$params['kw_type']=='mobile'){
                $where['mobile'] = $params['keywords'];
            }
        }
        //退换货单状态
        if (isset($params['return_status']) && $params['return_status'] > 0) {
            $where['status'] = intval($params['return_status']);
        }
        //用户id
        if (isset($params['user_id'])) {
            $where['user_id'] = $params['user_id'];
        }
        //退换货问题id
        if (isset($params['reason_key'])) {
            $where['reason_key'] = $params['reason_key'];
        }
        //订单状态
        if (isset($params['order_status'])) {
            $where['order_status'] = $params['order_status'];
        }
        if (isset($params['appid'])) {
            $where['appid'] = $params['appid'];
        }
        $where= $this->_parse_order_where($where);
        $data = $this->orderReturnRepository->getReturnList($where);
        foreach($data as $k=>$v){
            //是否显示操作按钮   true：显示  false:不显示
            if($data[$k]->status!=ReturnStatus::ReturnCreated){
                $data[$k]->operate_status=false;
            }else{
                $data[$k]->operate_status=true;
            }
            //业务类型
            if($data[$k]->business_key==OrderStatus::BUSINESS_REFUND){
                $data[$k]->business_name=OrderStatus::getBusinessName(OrderStatus::BUSINESS_REFUND);//退款业务
            }elseif($data[$k]->business_key==OrderStatus::BUSINESS_RETURN){
                $data[$k]->business_name=OrderStatus::getBusinessName(OrderStatus::BUSINESS_RETURN);//退货业务
            }elseif($data[$k]->business_key==OrderStatus::BUSINESS_BARTER){
                $data[$k]->business_name=OrderStatus::getBusinessName(OrderStatus::BUSINESS_BARTER);//换货业务
            }else{
                $data[$k]->business_name="";
            }
            //订单状态
            if($data[$k]->order_status==OrderStatus::OrderWaitPaying){
                $data[$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderWaitPaying);//待支付
            }elseif($data[$k]->order_status==OrderStatus::OrderPaying){
                $data[$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderPaying);//支付中
            }elseif($data[$k]->order_status==OrderStatus::OrderPayed){
                $data[$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderPayed);//已支付
            }elseif($data[$k]->order_status==OrderStatus::OrderInStock){
                $data[$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderInStock);//备货中
            }elseif($data[$k]->order_status==OrderStatus::OrderDeliveryed){
                $data[$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderDeliveryed);//已发货
            }elseif($data[$k]->order_status==OrderStatus::OrderInService){
                $data[$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderInService);//租用中
            }elseif($data[$k]->order_status==OrderStatus::OrderCancel){
                $data[$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderCancel);//已取消完成(未支付)
            }elseif($data[$k]->order_status==OrderStatus::OrderClosedRefunded){
                $data[$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderClosedRefunded);//关闭（支付完成后退款）
            }elseif($data[$k]->order_status==OrderStatus::OrderCompleted){
                $data[$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderCompleted);//已完成
            }else{
                $data[$k]->order_status_name="";
            }
            //（退款、退机、换机）状态
            if($data[$k]->status==ReturnStatus::ReturnCreated){
                $data[$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnCreated);//提交申请
            }elseif($data[$k]->status==ReturnStatus::ReturnAgreed){
                $data[$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnAgreed);//同意
            }elseif($data[$k]->status==ReturnStatus::ReturnDenied){
                $data[$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnDenied);//拒绝
            }elseif($data[$k]->status==ReturnStatus::ReturnCanceled){
                $data[$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnCanceled);//取消退货申请
            }elseif($data[$k]->status==ReturnStatus::ReturnReceive){
                $data[$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnReceive);//已收货
            }elseif($data[$k]->status==ReturnStatus::ReturnTuiHuo){
                $data[$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiHuo);//已退货
            }elseif($data[$k]->status==ReturnStatus::ReturnHuanHuo){
                $data[$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnHuanHuo);//已换货
            }elseif($data[$k]->status==ReturnStatus::ReturnTuiKuan){
                $data[$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiKuan);//已退款
            }elseif($data[$k]->status==ReturnStatus::ReturnTui){
                $data[$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTui);//退款中
            }elseif($data[$k]->status==ReturnStatus::ReturnDelivery){
                $data[$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnDelivery);//已发货
            }else{
                $data[$k]->status_name="";
            }
        }
        return apiResponse($data,ApiStatus::CODE_0);
    }
    /**
     * 退货结果查看
     * @param $params
     * [
     *    "business_key"  =>''  业务类型 int    【必选】
     *    "business_no"   =>''  业务编号 string 【可选】
     *    "goods_no"     =>''   商品编号 string 【必选】
     * ]
     * @return array|bool|string
     */
    public function returnResult(array $params){
        try{
            $buss = new \App\Order\Modules\Service\BusinessInfo();
            //获取状态流
            $stateFlow = $buss->getStateFlow();
            //业务类型
            $buss->setBusinessType($params['business_key']);
            if($params['business_key']==OrderStatus::BUSINESS_RETURN){
                //退货业务
                $buss->setBusinessName(OrderStatus::getBusinessName(OrderStatus::BUSINESS_RETURN));
                //退货状态流
                $buss->setStateFlow($stateFlow['returnStateFlow']);
            }
            if($params['business_key']==OrderStatus::BUSINESS_BARTER){
                //换货业务
                $buss->setBusinessName(OrderStatus::getBusinessName(OrderStatus::BUSINESS_BARTER));
                //换货状态流
                $buss->setStateFlow($stateFlow['barterStateFlow']);
            }
            $return=GoodsReturn::getReturnGoodsInfo($params['goods_no']);
            //$return值为空时，说明还没创建申请
            if(!$return){
                $buss->setStatus("A");
                //获取退换货原因
                $reasons = ReturnStatus::getReturnQuestionList();
				$_arr = [];
				foreach($reasons as $_id => $_name){
					$_arr[] = [
						'id'	=> $_id,
						'name'	=> $_name,
					];
				}
                $buss->setReturnReason($_arr);//退换货问题
            }else{
                //根据业务编号获取退货单信息
              //  $return=GoodsReturn::getReturnByRefundNo($params['business_no']);
              //  if(!$return){
              //     return false;
             //   }
                $return_info=$return->getData();
                $buss->setRefundNo($return_info['refund_no']); //退换货单号
                if($return_info['status']==ReturnStatus::ReturnCreated){   //待审核
                    $buss->setStatus("B"); //当前状态流
                }elseif($return_info['status']==ReturnStatus::ReturnAgreed){  //审核同意
                    $buss->setStatus("B");//当前状态流
                    $param=[
                        "method"=>"warehouse.delivery.logisticList" //收发货系统的物流接口方法
                    ];
                    //备注信息、客服电话
                    $remark['remark']="若填写错误，请及时联系客服进行修改";
                    $remark['mobile']=config('tripartite.Customer_Service_Phone'); //客服电话
                    $buss->setRemark($remark);
                    if(empty($return_info['logistics_no']) && empty($return_info['logistics_name'])) {
                        //获取物流信息
                        $header = ['Content-Type: application/json'];
                        $info = curl::post(config('tripartite.warehouse_api_uri'), json_encode($param), $header);
                        $info = json_decode($info, true);
                        if( is_null($info)
                            || !is_array($info)
                            || !isset($info['code'])
                            || !isset($info['msg'])
                            || !isset($info['data']) ){
                            return false;
                        }
                        $buss->setLogisticsInfo($info['data']); //物流信息
                    }

                }elseif($return_info['status']==ReturnStatus::ReturnDenied){  //审核拒绝
                    //已经拒绝的状态流
                    if($params['business_key']==OrderStatus::BUSINESS_RETURN){    //退货业务
                        if($return_info['evaluation_status']==ReturnStatus::ReturnEvaluationFalse){  //检测不合格
                            //退货检测不合格状态流
                            $buss->setStateFlow($stateFlow['returnCheckStateFlow']);
                            $buss->setStatus("D");  //当前状态流
                            $checkResult['check_result']="检测不合格";
                            $checkResult['check_remark']=$return_info['evaluation_remark']; //检测备注
                        }else{   //检测合格
                            //退货状态流
                            $buss->setStateFlow($stateFlow['returnDeniedStateFlow']);
                            $buss->setStatus("C");  //当前状态流
                        }

                        $buss->setStatusText("您的退货审核被拒绝");//状态说明
                    }

                    if($params['business_key']==OrderStatus::BUSINESS_BARTER){  //换货业务
                        if($return_info['evaluation_status']==ReturnStatus::ReturnEvaluationFalse){  //检测不合格
                            //换货检测不合格状态流
                            $buss->setStateFlow($stateFlow['barterStateFlow']);
                            $buss->setStatus("D"); //当前状态流
                            $checkResult['check_result']="检测不合格";
                            $checkResult['check_remark']=$return_info['evaluation_remark'];//检测备注
                        }else{  //检测合格
                            //换货状态流
                            $buss->setStateFlow($stateFlow['barterDeniedStateFlow']);
                            $buss->setStatus("C");//当前状态流
                        }

                        $buss->setStatusText("您的换货审核被拒绝");//状态说明
                    }
                    if(isset($checkResult)){
                        $buss->setCheckResult( $checkResult);
                    }

                }elseif($return_info['status']==ReturnStatus::ReturnReceive  //已收货
                    || $return_info['status']==ReturnStatus::ReturnTui      //退款中
                    || $return_info['status']==ReturnStatus::ReturnDelivery ){  // 已发货
                    $buss->setStatus("C");//当前状态流
                    $buss->setStatusText("检测");
                    if($return_info['evaluation_status']==ReturnStatus::ReturnEvaluation){  //待检测
                        $checkResult['check_result']="待检测";

                    }elseif($return_info['evaluation_status']==ReturnStatus::ReturnEvaluationFalse){  //检测不合格
                        //退货检测不合格的状态流
                        if($params['business_key']==OrderStatus::BUSINESS_RETURN){  //退货业务
                            //退货状态流
                            $buss->setStateFlow($stateFlow['returnCheckStateFlow']);
                        }
                        $checkResult['check_result']="检测不合格";

                    }elseif($return_info['evaluation_status']==ReturnStatus::ReturnEvaluationSuccess){  //检测合格
                        $checkResult['check_result']="检测合格";

                    }
                    $checkResult['check_remark']=$return_info['evaluation_remark'];
                    $buss->setCheckResult( $checkResult);
                }elseif( $return_info['status']==ReturnStatus::ReturnTuiHuo   //已退货   || 已换货
                    || $return_info['status']==ReturnStatus::ReturnHuanHuo ){
                    $buss->setStatus("D");
                    $buss->setStatusText("完成");
                    if($return_info['evaluation_status']==ReturnStatus::ReturnEvaluation){   //待检测
                        $checkResult['check_result']="待检测";

                    }elseif($return_info['evaluation_status']==ReturnStatus::ReturnEvaluationFalse){   //检测不合格
                        //退货检测不合格的状态流
                        if($params['business_key']==OrderStatus::BUSINESS_RETURN){  //退货业务
                            //退货状态流
                            $buss->setStateFlow($stateFlow['returnCheckStateFlow']);
                        }
                        $checkResult['check_result']="检测不合格";

                    }elseif($return_info['evaluation_status']==ReturnStatus::ReturnEvaluationSuccess){  //检测合格
                        $checkResult['check_result']="检测合格";

                    }
                    $checkResult['check_remark']=$return_info['evaluation_remark'];
                    if(isset($checkResult)){
                        $buss->setCheckResult( $checkResult);
                    }
                    //已完成退货退款，返回退还押金信息
                    if($return_info['status']==ReturnStatus::ReturnTuiHuo){   //退货完成
                        $returnUnfreeze="押金已退还至支付账户，由于银行账务流水，请耐心等待1-3个工作日";
                        $buss->returnUnfreeze( $returnUnfreeze);
                    }

                }elseif($return_info['status']==ReturnStatus::ReturnTuiKuan){ //退款完成
                    $buss->setStatus("D");
                    $buss->setStatusText("完成");
                }

                //退换货物流
                if(!empty($return_info['logistics_no']) && !empty($return_info['logistics_name'])){
                    $channel_list['logistics_no']=$return_info['logistics_no'];
                    $channel_list['logistics_name']=$return_info['logistics_name'];
                    $buss->setLogisticsForm($channel_list);
                }
                //退换货原因
                $quesion['reason_name']=ReturnStatus::getReturnQuestionName($return_info['reason_id']);
                $quesion['reason_text']=$return_info['reason_text'];//退换货原因
                $buss->setReturnReasonResult($quesion);
                //设置是否显示取消退换货按钮,状态为创建申请，审核同意时显示
                if($return_info['status'] == ReturnStatus::ReturnAgreed || $return_info['status'] == ReturnStatus::ReturnCreated ){
                    $buss->setCancel("0");
                }else{
                    $buss->setCancel("1");
                }
                //不等于审核拒绝拒绝并且是创建审核后的状态
                if($return_info['status'] != ReturnStatus::ReturnDenied && $return_info['status'] >ReturnStatus::ReturnCreated){
                    if($params['business_key']==OrderStatus::BUSINESS_RETURN){  //退货业务
                        $buss->setStatusText("您的退货申请已通过审核");
                    }
                    if($params['business_key']==OrderStatus::BUSINESS_BARTER){  //换货业务
                        $buss->setStatusText("您的换货申请已通过审核");
                    }

                }
                if($return_info['status']>ReturnStatus::ReturnCanceled){ //已取消
                    $buss->setReceive("已收货");
                }
            }
            //获取商品信息
            $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($params['goods_no']);
            if(!$goods){
                return false;
            }
            $goodsInfo = $goods->getData();
            $goodsInfo['specs'] = filterSpecs($goodsInfo['specs']); //商品规格信息格式转换
            $buss->setGoodsInfo($goodsInfo);
            //查询订单信息
            $order = \App\Order\Modules\Repository\Order\Order::getByNo($goodsInfo['order_no']);
            if(!$order){
                return false;
            }
            $orderInfo = $order->getData();
            //订单信息
            $buss->setOrderInfo($orderInfo);

            //获取换货信息
            if(!empty($return_info['barter_logistics_no']) && !empty($return_info['barter_logistics_id'])){
                $barter['barter_logistics_no']=$return_info['barter_logistics_no'];   //换货物流编号
                $barter['barter_logistics_name']=\App\Lib\Warehouse\Logistics::info($return_info['barter_logistics_id']); //换货物流名称
                $barter['order_no']=$return_info['order_no'];  //订单编号
                $barter['old_goods_name']=$goodsInfo['goods_name'];  //要换的手机名称
                $barter['goods_name']=$goodsInfo['goods_name'];      //换到的手机名称
                $buss->setBarterLogistics($barter);
            }
			
            return $buss->toArray();
        }catch( \Exception $exc){
			LogApi::error('退货信息查询失败',$exc);
            return false;
        }
    }


    /**
     * 检测合格或不合格
     * @param int		$business_key	业务类型
     * @param array		$data		
	 * [
	 *		'business_no'       => '',    业务编码   string   【必传】
	 *		'evaluation_remark' => '',    检测备注   string   【必传】
	 *		'compensate_amount' => '',    检测金额   float    【必传】
     *      'evaluation_status' => '',    检测状态   int      【必传】 1检测合格  ，2检测不合格
	 *		'evaluation_time'   => '',    检测时间   int     【必传】
     *      'goods_no'          =>''      商品编号   string  【必传】
     *
	 * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return bool	  true：成功；false：失败
     * @throws \Exception
     */
    public function isQualified(int $business_key, array $data, array $userinfo)
    {
        //开启事务
        DB::beginTransaction();
        try{
            //检测合格的退换货编号
            $yes_list=[];
            //检测不合格的状态
            $no_list=[];
            foreach($data as $k=>$v){
                //获取退货单信息
                $return = \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($v['business_no']);
                if(!$return){
                    LogApi::debug("退货单查询失败");
                    return false;
                }
                $return_info = $return->getData();
                //必须是已收货状态
                if($return_info['status']!=ReturnStatus::ReturnReceive){
                    LogApi::debug("必须是已收货状态才可以检测");
                    return false;
                }
                //获取订单信息
                $order = \App\Order\Modules\Repository\Order\Order::getByNo($return_info['order_no']);
                if(!$order){
                    LogApi::debug('获取订单查询失败');
                    return false;
                }
                $order_info = $order->getData();
                //获取商品信息
                $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info['goods_no']);
                if(!$goods){
                    LogApi::debug('商品信息查询失败');
                    return false;
                }
                $goods_info = $goods->getData();
                $params['evaluation_remark'] = $v['evaluation_remark']; //检测备注
                $params['evaluation_amount'] = $v['compensate_amount']; //检测金额
                $params['evaluation_time'] = $v['evaluation_time'];     //检测时间
				
				// 合格状态
                if($data[$k]['evaluation_status']==1) {
                    $yes_list[] = $return_info['refund_no'];
                    $params['evaluation_status'] = ReturnStatus::ReturnEvaluationSuccess;  //检测合格
                    // 更新退换货单信息
                    $updateReturn = $return->returnCheckOut($params);
                    if(!$updateReturn){
                        DB::rollBack();
                        LogApi::debug('退换货单检测结果更新失败');
                        return false;
                    }
					
					// 退货业务，创建清算单
                    if($business_key == OrderStatus::BUSINESS_RETURN){
                        // 如果待退款金额为0，则直接调退款成功的回调
                        if( !(
                            $return_info['pay_amount']>0
                            || $return_info['auth_unfreeze_amount']>0
                            || $return_info['auth_deduction_amount']>0
                        ) ){
                            // 不需要清算，直接调起退款成功
                            $b = self::refundUpdate([
                                'business_type' => $business_key,
                                'business_no'	=> $return_info['refund_no'],
                                'status'		=> 'success',
                            ], $userinfo);
                            if( $b==true ){ // 退款成功，已经关闭退款单，并且已经更新商品和订单）
                                //事务提交
                                DB::commit();
                                return true;
                            }
                            // 失败
                            DB::rollBack();
                            return false;
                        }
                        //获取订单的支付信息
                        $pay_result = $this->orderReturnRepository->getPayNo(OrderStatus::BUSINESS_ZUJI,$return_info['order_no']);
                        if(!$pay_result){
                            return false;
                        }
                        $create_data['order_no']=$return_info['order_no']; //订单类型
                        if($order_info['pay_type'] == PayInc::LebaifenPay){
                            $create_data['order_type']= OrderStatus::miniRecover;//订单类型
                        }else{
                            $create_data['order_type']=$order_info['order_type'];//订单类型
                        }
                        $create_data['business_type']=OrderStatus::BUSINESS_RETURN;//业务类型
                        $create_data['business_no']=$return_info['refund_no'];//业务编号
                        $create_data['out_payment_no']=$pay_result['payment_no'];//支付编号
                        $create_data['out_auth_no']=$pay_result['fundauth_no'];//预授权编号
                        $create_data['auth_deduction_amount']=$return_info['auth_deduction_amount'];//应扣押金金额
                        $create_data['auth_deduction_time']=0;//扣除押金时间
                        $create_data['auth_unfreeze_time']=0;//退还时间
                        $create_data['refund_time']=0;//退款时间
                        $create_data['refund_amount']=0;//退款金额

                        //退款：直接支付
                        if($order_info['pay_type'] == \App\Order\Modules\Inc\PayInc::FlowerStagePay
                            ||$order_info['pay_type']==\App\Order\Modules\Inc\PayInc::UnionPay
                            ||$order_info['pay_type']==\App\Order\Modules\Inc\PayInc::LebaifenPay
                        ){
                            $create_data['auth_unfreeze_amount']=$goods_info['yajin'];//商品实际支付押金
                            $create_data['refund_amount']=$goods_info['amount_after_discount'];//退款金额：商品实际支付优惠后总租金

                        }
                        //退款：代扣+预授权
                        if($order_info['pay_type'] == \App\Order\Modules\Inc\PayInc::WithhodingPay){
                            $create_data['auth_unfreeze_amount']=$goods_info['yajin'];//商品实际支付押金

                        }

                        $create_clear=\App\Order\Modules\Repository\OrderClearingRepository::createOrderClean($create_data);//创建退款清单
                        if(!$create_clear){
                            //事务回滚
                            DB::rollBack();
                            return false;//创建退款清单失败
                        }
                        //退货检测合格更新状态为退款中
                        $ReturnTui = $return->returnCheck();
                        if(!$ReturnTui){
                            //事务回滚
                            DB::rollBack();
                            return false;//更新失败
                        }
                        //插入操作日志
                        $goodsLog=\App\Order\Modules\Repository\GoodsLogRepository::add([
                            'order_no'     =>$return_info['order_no'],
                            'action'       =>'退货检测',
                            'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN,
                            'business_no'  =>$return_info['refund_no'],
                            'goods_no'     =>$return_info['goods_no'],
                            'operator_id'  =>$userinfo['uid'],
                            'operator_name'=>$userinfo['username'],
                            'operator_type'=>$userinfo['type'],
                            'msg'           =>'退货检测合格',
                        ],$isCorntab=FALSE);
                    }
                    $delivery_data['goods'][$k]['goods_no']=$return_info['goods_no'];
                    if($business_key == OrderStatus::BUSINESS_BARTER){
                        //插入操作日志
                        $goodsLog=\App\Order\Modules\Repository\GoodsLogRepository::add([
                            'order_no'     =>$return_info['order_no'],
                            'action'       =>'换货检测',
                            'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_BARTER,
                            'business_no'  =>$return_info['refund_no'],
                            'goods_no'     =>$return_info['goods_no'],
                            'operator_id'  =>$userinfo['uid'],
                            'operator_name'=>$userinfo['username'],
                            'operator_type'=>$userinfo['type'],
                            'msg'           =>'换货检测合格',
                        ],$isCorntab=FALSE);
                    }
                }else{
                    $no_list[] = $return_info['refund_no'];
                    if($business_key == OrderStatus::BUSINESS_RETURN){
                        //更新退货单检测信息
                        $updateCheck = $return->returnUnqualified($params);
                        if(!$updateCheck){
                            //事务回滚
                            DB::rollBack();
                            return false;
                        }
                        //插入操作日志
                        $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
                            'order_no'     =>$return_info['order_no'],
                            'action'       =>'退货检测',
                            'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN,
                            'business_no'  =>$return_info['refund_no'],
                            'goods_no'     =>$return_info['goods_no'],
                            'operator_id'  =>$userinfo['uid'],
                            'operator_name'=>$userinfo['username'],
                            'operator_type'=>$userinfo['type'],
                            'msg'           =>'退货检测不合格',
                        ],$isCorntab=FALSE);
                    }
                    //换货业务
                    if($business_key ==OrderStatus::BUSINESS_BARTER){
                        //更新退货单检测信息
                        $updateBarterCheck=$return->barterUnqualified($params);
                        if(!$updateBarterCheck){
                            //事务回滚
                            DB::rollBack();
                            return false;
                        }
                        //更新商品状态
                        $updateGoods = $goods->returnClose();
                        if(!$updateGoods){
                            //事务回滚
                            DB::rollBack();
                            return false;
                        }
                        //插入操作日志
                        $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
                            'order_no'     =>$return_info['order_no'],
                            'action'       =>'换货检测',
                            'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_BARTER,
                            'business_no'  =>$return_info['refund_no'],
                            'goods_no'     =>$return_info['goods_no'],
                            'operator_id'  =>$userinfo['uid'],
                            'operator_name'=>$userinfo['username'],
                            'operator_type'=>$userinfo['type'],
                            'msg'           =>'换货检测不合格',
                        ],$isCorntab=FALSE);
                    }


                }
                //操作日志错误
                if(!$goodsLog){
                    DB::rollBack();
                    return false;
                }
                //获取商品信息
                $goodsInfo = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info['goods_no'] );
                if(!$goodsInfo){
                    return false;
                }
                $goodsStatus[$k]=$goodsInfo->getData();
            }
            if($business_key == OrderStatus::BUSINESS_BARTER){
                //获取此订单的商品是否还有处理中的设备，没有则解冻
                $status=false;
                foreach($goodsStatus as $k=>$v){
                    if($v['goods_status']==OrderGoodStatus::RENTING_MACHINE){
                        $status=true;
                    }else{
                        $status=false;
                        break;
                    }
                }
                if($status==true){
                    //解冻订单并关闭订单
                    $orderInfo=\App\Order\Modules\Repository\Order\Order::getByNo($return_info['order_no']);
                    $updateOrder=$orderInfo->returnClose();
                    if(!$updateOrder){
                        return false;
                    }
                }
            }
            DB::commit();
            //发短信
            if($business_key==OrderStatus::BUSINESS_RETURN){
                if($yes_list){
                    foreach( $yes_list as $no ) {
                        //短信
                        $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$no,SceneConfig::RETURN_CHECK_OUT);
                        $b=$orderNoticeObj->notify();
                        Log::debug($b?"Order :".$return_info['order_no']." IS OK":"IS error");
                    }
                }
                if($no_list){
                    foreach( $no_list as $no ){
                        //短信
                        $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$no,SceneConfig::RETURN_UNQUALIFIED);
                        $b=$orderNoticeObj->notify();
                        Log::debug($b?"Order :".$return_info['order_no']." IS OK":"IS error");
                    }
                }
            }

            return true;
        }catch( \Exception $exc){
             throw new \Exception( $exc->getMessage());
        }


    }

    /***
     * 退换货确认收货
     * @param $params
     * [
     *   'refund_no'    =>[
     *                    'refund_no'=>'', //业务编号   string  【必传】
     *                    'goods_no'  =>''  //商品编号   String  【必传】
     *                    ],  //业务编号   string  【必传】
     *   'business_key'=>'',  //业务类型   int     【必传】
     * ]
     * @return  bool
     */
    public function returnReceive($params){
        //开启事务
        DB::beginTransaction();
        try{

                foreach($params['refund_no'] as $item){
                    if(!empty($item['refund_no'])){
                       //获取退货单信息
                       $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($item['refund_no']);
                    }else{
                        //获取退货单信息
                        $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnInfoByGoodsNo($item['goods_no'],ReturnStatus::ReturnAgreed);
                    }
                    if(!$return){
                        return false;
                    }

                    //修改退换货状态为已收货
                    if(!$return->returnReceive()){

                        DB::rollBack();
                        return false;
                    }
                    $return_info = $return->getData();
                }


            //提交事务
            DB::commit();
            if($params['business_key'] == OrderStatus::BUSINESS_RETURN){
                foreach($params['refund_no'] as $value){
                    //发送短信
                    $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI, $return_info['refund_no'] ,SceneConfig::RETURN_DELIVERY);
                    $b=$orderNoticeObj->notify();
                    Log::debug($b?"Order :".$return_info['order_no']." IS OK":"IS error");
                }
            }

            return true;
        }catch (\Exception $exc) {
            DB::rollBack();
            LogApi::debug($exc->getMessage());
            return false;
        }


    }

    /**
     * 退换货物流单号上传
     * @param $params
     * [
     *    'goods_info'=>['',''],   业务编号   string  【必传】
     * ]
     * @return bool|string
     * @throws \Exception
     *
     */
    public function uploadWuliu($params){
        //开启事务
        DB::beginTransaction();
        try{
            $data=[];
            foreach($params['goods_info'] as $k=>$refund_no){
                //获取退款单信息
                $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($refund_no);
                if(!$return){
                    return false;
                }
                $return_info = $return->getData();
                //获取订单信息
                $order = \App\Order\Modules\Repository\Order\Order::getByNo($return_info['order_no']);
                if(!$order){
                    return false;
                }
                if($return_info['user_id']!=$params['user_id']){
                    return false;
                }
                //订单未审核不能上传
                if($return_info['status']!= ReturnStatus::ReturnAgreed){
                    return false;
                }

                //更新物流单号
                if( !$return->uploadLogistics($params) ){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                $receive_no = $return_info['receive_no'];
            }
            $data['logistics_id']=$params['logistics_id'];  //物流id
            $data['logistics_no']=$params['logistics_no'];   //物流编号
            $data['receive_no']= $receive_no;  //收货单编号
            LogApi::debug("通知收发货系统的参数信息",$data);
            //上传物流单号到收货系统
            $create_receive = Receive::updateLogistics($data);
            if(!$create_receive){
                //事务回滚
                DB::rollBack();
                return false;
            }
            //提交事务
            DB::commit();
            return true;
        }catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }
    }


    /**
     * 换货用户收到货
     * @throws \Exception
     * @param $params
     *    'refund_no' =>'111'      //业务编号  string  【必传】
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return bool
     */
    public static function updateorder(string $refund_no,array $userinfo){
        //开启事物
       DB::beginTransaction();
        try{
            //获取退换单信息
            $return = \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($refund_no);
            if(!$return){
                return false;
            }
            $return_info = $return->getData();
            //获取订单信息
            $order = \App\Order\Modules\Repository\Order\Order::getByNo($return_info['order_no']);
            if(!$order){
                return false;
            }
            //更新退货单状态为已换货
            $updateBarter = $return->barterFinish();
            if(!$updateBarter){
                DB::rollBack();
                return false;
            }
            //获取商品信息
            $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info['goods_no']);
            if(!$goods){
                return false;
            }
            //更新商品状态为租用中
            $updateGoods = $goods->barterFinish();
            if(!$updateGoods){
                DB::rollBack();
                return false;
            }
            //插入操作日志
            $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
                'order_no'     =>$return_info['order_no'],
                'action'       =>'换货确认收货',
                'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_BARTER,
                'business_no'  =>$refund_no,
                'goods_no'     =>$return_info['goods_no'],
                'operator_id'  =>$userinfo['uid'],
                'operator_name'=>$userinfo['username'],
                'operator_type'=>$userinfo['type'],
                'msg'           =>'用户已收货',
            ],$isCorntab=FALSE);
            if(!$goodsLog){
                DB::rollBack();
                return false;
            }
            //订单解冻
            $updateOrder = $order->returnClose();
            if(!$updateOrder){
                DB::rollBack();
                return false;
            }
            //通知收发货确认收货参数
            $confirm_data['order_no'] = $return_info['order_no'];  //订单编号
            $confirm_data['receive_type'] = $userinfo['type'];     //渠道类型  1  管理员，2 用户，3 系统自动化
            $confirm_data['user_id'] = $userinfo['uid'];           //操作者id
            $confirm_data['user_name'] = $userinfo['username'];    //用户名
            LogApi::debug("换货通知收发货确认收货的参数",$confirm_data);
            $returnConfirm = Delivery::orderReceive($confirm_data);   //通知收货，确认收货
            if(!$returnConfirm){
                DB::rollBack();
                return false;
            }
            DB::commit();
            return true;

        }catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }
    }

    /**
     * 订单发货接口
     * @param $detail array
     * [
     *  'order_no'    =>'',//订单编号   string   【必传】
     *  'logistics_id'=>''//物流渠道ID  int      【必传】
     *  'logistics_no'=>''//物流单号    string   【必传】
     * ]
     * @param $goods_info array 商品信息 【必须】 参数内容如下
     * [
     *   [
     *      'goods_no'=>'abcd',  商品编号   string  【必传】
     *      'imei1'   =>'imei1', 商品imei1  string  【必传】
     *      'imei2'   =>'imei2', 商品imei2  string  【必传】
     *      'imei3'   =>'imei3', 商品imei3  string  【必传】
     *      'serial_number'=>'abcd' 商品序列号  string  【必传】
     *   ]
     *   [
     *      'goods_no'=>'abcd',  商品编号   string  【必传】
     *      'imei1'   =>'imei1', 商品imei1  string  【必传】
     *      'imei2'   =>'imei2', 商品imei2  string  【必传】
     *      'imei3'   =>'imei3', 商品imei3  string  【必传】
     *      'serial_number'=>'abcd' 商品序列号  string  【必传】
     *   ]
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return boolean
     */
    public static function createchange($detail,$goods_info,$userinfo){
        LogApi::debug("发货接受参数物流信息参数",$detail);
        LogApi::debug("发货接收商品信息参数",$goods_info);
        LogApi::debug("发货接收用户信息参数",$userinfo);
        //开启事物
        try{
            foreach ($goods_info as $k=>$v) {
                //获取设备信息
                $delivery = \App\Order\Modules\Repository\Order\DeliveryDetail::getGoodsDeliveryInfo($detail['order_no'],$goods_info[$k]['goods_no']);
                if(!$delivery){
                    LogApi::debug("获取设备信息失败");
                    return false;
                }
                //更新原设备为无效
                $updateDelivery = $delivery->barterDelivery();
                if(!$updateDelivery){
                    LogApi::debug("更新原设备为无效失败");
                    return false;
                }
                //换货信息
                $return = GoodsReturn::getReturnInfo($detail['order_no'],$goods_info[$k]['goods_no']);
                if(!$return){
                    return false;
                }
                $updateReturn = $return->barterDelivery($detail);//更新换货物流信息
                if(!$updateReturn){
                    LogApi::debug("更新换货物流信息失败");
                   return false;
                }
                $return_info = $return->getData();
                LogApi::debug("换货信息",$return_info);
                //插入操作日志
                $goodsLog=\App\Order\Modules\Repository\GoodsLogRepository::add([
                    'order_no'     =>$detail['order_no'],
                    'action'       =>'换货发货',
                    'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_BARTER,
                    'business_no'  =>$return_info['refund_no'],
                    'goods_no'     =>$goods_info[$k]['goods_no'],
                    'operator_id'  =>$userinfo['user_id'],
                    'operator_name'=>$userinfo['user_name'],
                    'operator_type'=>$userinfo['type'],
                    'msg'           =>'换货已发货',
                ],$isCorntab=FALSE);
                if(!$goodsLog){
                    return false;
                }
            }
            $goods_result = \App\Order\Modules\Repository\Order\DeliveryDetail::addGoodsDeliveryDetail($detail['order_no'],$goods_info);//添加商品扩展信息
            if(!$goods_result){
                return false;//创建换货记录失败
            }
            return true;
        }catch (\Exception $exc) {
            echo $exc->getMessage();
            return false;
        }


    }
    /**
     * 退款成功更新退款状态
     * @param array $params 业务参数
	 * [
     *		'business_type'=> '',//业务类型   int     【必传】
     *		'business_no' => '',//业务编码    string  【必传】
     *		'status'      => '',//支付状态    string  success：支付完成
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return bool
     */
    public static function refundUpdate(array $params,array $userinfo){
        //参数过滤
        $rules = [
            'business_type'   => 'required',//业务类型
            'business_no'     => 'required',//业务编码
            'status'          => 'required',//支付状态
        ];
        $validator = app('validator')->make($params, $rules);
        if ($validator->fails()) {
            LogApi::debug("参数错误",$params);
            return false;
        }
        try{
            //获取退货、退款单信息
            $return = \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($params['business_no']);
            if(!$return){
                LogApi::debug("未找到此退货、退款记录");
                return false;
            }
            $return_info = $return->getData();
            //获取订单信息
            $order=\App\Order\Modules\Repository\Order\Order::getByNo($return_info['order_no']);
            if(!$order){
                LogApi::debug("未找到订单记录");
                return false;
            }
            $order_info = $order->getData();
			// 判断订单状态，已退款完成时，直接返回成功
            if($order_info['order_status'] == OrderStatus::OrderClosedRefunded){
               return true;
            }

            //查询此订单的商品
           /* $goodInfo=\App\Order\Modules\Repository\OrderReturnRepository::getGoodsInfo($return_info['order_no']);
            if(!$goodInfo){
                LogApi::debug("未获取到订单的商品信息");
                return false;
            }
            $goodsInfo=$goodInfo->toArray();
            LogApi::debug("查询此订单的商品",$goodsInfo);*/

            if($params['business_type'] == OrderStatus::BUSINESS_RETURN){
                //修改退货单状态为已退货
                $updateReturn = $return->returnFinish($params);
                if(!$updateReturn){
                    LogApi::debug("修改退款、退货状态失败");
                    return false;
                }
                //获取商品信息
                $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info['goods_no']);
                if(!$goods){
                    LogApi::debug("获取商品信息失败");
                    return false;
                }
                //修改商品状态
               $updateGoods = $goods->returnFinish();
                if(!$updateGoods){
                    LogApi::debug("修改商品状态失败");
                    return false;
                }
                $returnData['goods_no']=$return_info['goods_no'];
                $returnData['order_no']=$return_info['order_no'];

            }else{
                //获取商品信息
                $goods = \App\Order\Modules\Repository\Order\Goods::getOrderNo($return_info['order_no']);
                if(!$goods){
                    LogApi::debug("获取商品信息失败");
                    return false;
                }
                //修改退货单状态为已退款
                $updateReturn = $return->refundFinish($params);
                if(!$updateReturn){
                    LogApi::debug("修改退款单状态失败");
                    return false;
                }
                //修改商品状态为已退款
                $setGoodsRefund = OrderGoodsRepository::setGoodsRefund($return_info['order_no']);
                if(!$setGoodsRefund){
                    LogApi::debug("修改商品状态为已退款失败");
                    return false;
                }
                $returnData['order_no']=$return_info['order_no'];

            }
            $goodsInfo = $goods->getData();
            //释放库存
            //查询商品的信息
            $orderGoods = OrderRepository::getGoodsListByGoodsId($returnData);
            if (!$orderGoods) {
                LogApi::debug("未获取到商品信息");
                return false;
            }
            LogApi::debug("查询订单商品的信息",$orderGoods);
            //操作订单是未冻结状态
            $setFreeze = $order->returnClose();
            if (!$setFreeze) {
                LogApi::debug("操作订单是未冻结状态失败");
                return false;
            }
            //操作关闭订单
            $closeOrder = OrderOperate::isOrderComplete($return_info['order_no']);
            if (!$closeOrder) {
                LogApi::debug("操作关闭订单失败");
                return false;
            }
            //释放库存
            if ($orderGoods){
                foreach ($orderGoods as $orderGoodsValues){
                    //暂时一对一
                    $goods_arr[] = [
                        'sku_id'=>$orderGoodsValues['zuji_goods_id'],
                        'spu_id'=>$orderGoodsValues['prod_id'],
                        'num'=>$orderGoodsValues['quantity']
                    ];

                    $success =Goods::addStock($goods_arr); //释放库存
                    if (!$success) {
                        LogApi::debug("释放库存失败");
                        return false;
                    }
                }
            }

            //分期关闭
            //查询分期
            //根据订单退和商品退走不同的地方
            if($params['business_type'] == OrderStatus::BUSINESS_RETURN){
                foreach($orderGoods as $k=>$v){
                    if ($orderGoods[$k]['zuqi_type'] == OrderStatus::ZUQI_TYPE_MONTH){
                        $success = \App\Order\Modules\Repository\Order\Instalment::close($returnData);//关闭用户的商品分期
                        if (!$success) {
                            LogApi::debug("关闭商品分期失败");
                            return false;
                        }

                    }
                }
                //插入操作日志
                $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
                    'order_no'     =>$return_info['order_no'],
                    'action'       =>'退货退款成功',
                    'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN,
                    'business_no'  =>$return_info['refund_no'],
                    'goods_no'     =>$return_info['goods_no'],
                    'operator_id'  =>$userinfo['uid'],
                    'operator_name'=>$userinfo['username'],
                    'operator_type'=>$userinfo['type'],
                    'msg'           =>'退款成功',
                ],$isCorntab=FALSE);
                if(!$goodsLog){
                    return false;
                }
            }else{
                //查询订单的状态
                $orderInfoData =  OrderRepository::getInfoById($return_info['order_no'],$return_info['user_id']);
                if ($orderInfoData['zuqi_type'] == OrderStatus::ZUQI_TYPE_MONTH){
                    $orderParams['order_no']=$return_info['order_no'];
                    $success = \App\Order\Modules\Repository\Order\Instalment::close($orderParams);
                    if (!$success) {
                        LogApi::debug("关闭订单分期失败");
                        return false;
                    }
                }
                //插入操作日志
                OrderLogRepository::add($userinfo['uid'],$userinfo['username'],$userinfo['type'],$return_info['order_no'],"退款","退款成功");
            }
            //获取订单用户认证信息
            $userInfo = OrderRepository::getUserCertified($order_info['order_no']);
            if(!$userInfo){
                return false;
            }
            LogApi::debug("押金解冻短信发送",[
                'mobile'=>$order_info['mobile'],
                'realName'=>$userInfo['realname'],
                'orderNo'=>$order_info['order_no'],
                'goodsName'=>$goodsInfo['goods_name'],
                'channel_id'=>$order_info['channel_id'],
                'tuihuanYajin'=>$return_info['auth_unfreeze_amount']
            ]);
            //发送短信，押金解冻短信发送
           $returnSend = ReturnDeposit::notify($order_info['channel_id'],SceneConfig::RETURN_DEPOSIT,[
                'mobile'=>$order_info['mobile'],
                'realName'=>$userInfo['realname'],
                'orderNo'=>$order_info['order_no'],
                'goodsName'=>$goodsInfo['goods_name'],
                'tuihuanYajin'=>$return_info['auth_unfreeze_amount']
                ]
            );
            Log::debug($returnSend?"Order :".  ['order_no']." IS OK":"IS error");
            //发送短信，通知用户押金已退还
           /* $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI, $return_info['refund_no'] ,SceneConfig::REFUND_SUCCESS);
            $b=$orderNoticeObj->notify();
            Log::debug($b?"Order :".$return_info['order_no']." IS OK":"IS error");*/
            LogApi::debug("退款执行成功");
            return true;
        }catch (\Exception $exc) {
            LogApi::debug("程序异常");
            echo $exc->getMessage();
            die;
        }
    }

    /**
     * 退换货点击审核弹出退换货单
     * @param $params
     *[
     *   'order_no'    =>'' ,订单编号   string   【必传】
     *   'business_key'=>'',业务类型    int      【必传】
     * ]
     * @return array
     */
    public function returnApplyList(array $params){
        $where[]=['order_return.order_no','=',$params['order_no']];
        $where[]=['order_return.business_key','=',$params['business_key']];
        if(isset($params['status'])){
            $where[]=['order_return.status','=',ReturnStatus::ReturnCreated];
        }
        $return_list = $this->orderReturnRepository->returnApplyList($where);//待审核的退换货列表
        return $return_list;
    }

    /**
     *获取退款单数据
     * @param $params
     * [
     *    'order_no'   =>'',订单编号   string  【必传】
     * ]
     * @return  array
     */
    public function getOrderStatus(array $params){
        $where[]=['order_no','=',$params['order_no']];
        $return_list = $this->orderReturnRepository->get_type($where);
        return $return_list;
    }
    /**
     * 退换货除已完成单的检测不合格的数据
     * @param $params   业务参数
     * [
     *     'order_no'          =>'', 订单编号   string   【必传】
     *     'business_key'      =>'', 业务类型   int     【必传】
     *     'evaluation_status' =>'', 检测状态   int    【必传】
     * ]
     * @return array
     */
    public function returnCheckList(array $params){
        $where[]=['order_return.order_no','=',$params['order_no']];
        $where[]=['order_return.business_key','=',$params['business_key']];
        $where[]=['order_return.evaluation_status','=',$params['evaluation_status']];
        $return_list= $this->orderReturnRepository->returnApplyList($where);//待审核的退换货列表
        foreach($return_list as $k=>$v){
            if($return_list[$k]->status==ReturnStatus::ReturnCreated || $return_list[$k]->status==ReturnStatus::ReturnAgreed||$return_list[$k]->status==ReturnStatus::ReturnDenied ){
                $return_list[$k]['goods_name']=$return_list[$k]->goods_name;
            }

        }
        return $return_list;
    }

    /**
     * 检测不合格拒绝退款
     * @param $params
     * [
     *   'refund_no'            =>'',  业务编号       string  【必传】
     *   'refuse_refund_remark' =>''   拒绝退款备注   string  【必传】
     *
     * ]
     * @return bool
     */
    public function refuseRefund(array $params){
        //开启事物
        DB::beginTransaction();
        try{
            foreach($params as $k=>$v){
                if(empty($params[$k]['refund_no'])){
                    return false;//参数不能为空
                }
                if(empty($params[$k]['refuse_refund_remark'])){
                    return false;//参数不能为空
                }
                //获取退货单的信息
                $return = \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($params[$k]['refund_no']);
                if(!$return){
                    return false;
                }
                $return_info[$k] = $return->getData();
                $order = $return_info[$k]['order_no'];
                //更新退货单状态为已取消
                $refuseReturn = $return->refuseRefund($params[$k]['refuse_refund_remark']);
                if(!$refuseReturn){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                //获取商品信息
                $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info[$k]['goods_no']);

                if(!$goods){
                    return false;
                }
                //更新商品状态为租用中
                $refuseGoods = $goods->returnClose();
                if(!$refuseGoods){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
            }
            //获取订单信息
            $order = \App\Order\Modules\Repository\Order\Order::getByNo($order);
            foreach($return_info as $k=>$v){
                $status[$k]=$return_info[$k]['status'];
            }
            if(!in_array(ReturnStatus::ReturnCreated,$status) && !in_array(ReturnStatus::ReturnAgreed,$status)&&  !in_array(ReturnStatus::ReturnTui,$status)){

                //修改订单为租用中
                $order_result = $order->returnClose();
                if(!$order_result) {
                    //事务回滚
                    DB::rollBack();
                    return ApiStatus::CODE_33007;//更新订单冻结状态失败
                }
            }
            //提交事务
            DB::commit();
            return true;

        }catch( \Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }

    }

    /**
     * 是否允许退换货审核
     * @param $params
     * [
     *    'order_no'   =>  ''   订单编号  string  【必传】
     *    'goods_no'   =>  ''   商品编号  string  【必传】
     * ]
     * @return   bool|array
     */
    public static function allowReturn($params){
        if(empty($params['goods_no']) || empty($params['order_no'])){
           return false;
        }
        $return = orderReturnRepository::returnList($params['order_no'],$params['goods_no']);//获取已取消除外的退货单信息
        if($return){
            return apiResponseArray(ApiStatus::CODE_0,$return,'成功');
        }

        return true;
    }



}