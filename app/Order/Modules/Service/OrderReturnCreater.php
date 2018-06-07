<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use App\Lib\NotFoundException;
use App\Lib\Warehouse\Receive;
use \App\Lib\Common\SmsApi;
use App\Order\Models\OrderReturn;
use App\Order\Modules\Inc\OrderGoodStatus;
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
     *       'order_no'      => '',   【必选】 商品编号
     *      'goods_no'      => [],   【必选】array 商品编号数组
     *      'business_key'  => '',   【必选】业务类型
     *      'loss_type'     => '',   【必选】商品损耗
     *      'reason_id'     => '',   【必选】退货原因id
     *      'reason_text'   => '',   【可选】退货原因备注
     *      'user_id'   => '',       【必选】用户id
     * ]
     * @return bool true：退货成功；false：退货失败
     */
    public function add(array $params){
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
                // 订单
                $order = $goods->getOrder();
                if(!$order){
                    DB::rollBack();
                    return false;
                }
                $order_info = $order->getData();
                if( $order_info['user_id'] != $params['user_id'] ){
                    return false;
                }

                //用户必须在收货后天内才可以申请退换货
                $nowdata=time();
                if($nowdata/$order_info['delivery_time']>7){
                    return false;
                }
                // 商品退货
                if( !$goods->returnOpen() ) {
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                //获取商品数组
                $goods_info = $goods->getData();
                // 创建退货单
                $data = [
                    'goods_no'      => $goods_info['goods_no'],
                    'order_no'      => $goods_info['order_no'],
                    'business_key' => $params['business_key'],
                    'loss_type'     => $params['loss_type'],
                    'reason_id'     => $params['reason_id'],
                    'reason_text'   => $params['reason_text'],
                    'user_id'       => $params['user_id'],
                    'status'        => ReturnStatus::ReturnCreated,
                    'refund_no'     => create_return_no(),
                    'create_time'  => time(),
                ];
                $create = OrderReturnRepository::createReturn($data);
                if(!$create){
                    //事务回滚
                    DB::rollBack();
                    return false;//创建失败
                }
                $no_list[] = $data['refund_no'];
            }
            //修改冻结状态为退货中
            if( $params['business_key'] == OrderStatus::BUSINESS_RETURN ){
                $orderStaus=$order->returnOpen();
                $goodsStaus=$goods->returnOpen();
            }
            //修改冻结状态为换货中
            if( $params['business_key'] == OrderStatus::BUSINESS_BARTER ){
                $orderStaus=$order->barterOpen();
                $goodsStaus=$goods->barterOpen();
            }
            if(!$orderStaus){
                //事务回滚
                DB::rollBack();
                return false;
            }
            if(!$goodsStaus){
                //事务回滚
                DB::rollBack();
                return false;
            }
            DB::commit();
            foreach( $no_list as $no ){
                //短信
                if( $params['business_key'] == OrderStatus::BUSINESS_RETURN ){
                    $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_RETURN, $no ,SceneConfig::RETURN_APPLY);
                    $b=$orderNoticeObj->notify();
                    Log::debug($b?"Order :".$goods_info['order_no']." IS OK":"IS error");
                }
            }
            return true;
        }catch( \Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }

    }
    /**
     *申请退款
     * @param array $params 业务参数
     * [
     *       'order_no'      => '',   【必选】 商品编号
     *       'user_id'   => '',       【必选】用户id
     * ]
     * @return bool true：申请成功；false：盛情失败
     */
    public function CreateRefund($params){
        //开启事务
        DB::beginTransaction();
        try {
            //获取订单信息
            $order = \App\Order\Modules\Repository\Order\Order::getByNo($params['order_no'], true);
            if (!$order){
                return false;
            }
            $order_info=$order->getData();
            //订单必须是已支付，未发货
            if( $order_info['order_status'] !=OrderStatus::OrderPayed && $order_info['order_status'] !=OrderStatus::OrderPaying && $order_info['order_status'] !=OrderStatus::OrderInStock){
                return false;
            }
            //如果订单是已确认，待发货状态，通知收发货系统取消发货
            if($order_info['order_status']==OrderStatus::OrderInStock){
                $cancel=Delivery::cancel($params['order_no']);
                if(!$cancel){
                    return false;//取消发货失败
                }
            }
            //冻结订单
            $orderFreeze=$order->refundOpen();
            if(!$orderFreeze){
                //事务回滚
                DB::rollBack();
                return false;//订单冻结失败
            }
            //创建退款单
            $data['business_key']=OrderStatus::BUSINESS_REFUND;
            $data['order_no']=$params['order_no'];
            $data['user_id']=$params['user_id'];
            $data['pay_amount']=$order_info['order_amount']+$order_info['order_yajin']+$order_info['order_insurance'];//实际支付金额=订单实际总租金+押金+意外险
            $data['status']=ReturnStatus::ReturnCreated;
            $data['refund_no']=create_return_no();
            $data['create_time']=time();
            //创建申请退款记录
            $addresult= OrderReturnRepository::createRefund($data);
            if(!$addresult){
                //事务回滚
                DB::rollBack();
                return false;//创建失败
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
     * 审核同意
     * @param $params
     * [
     * 'business_key'=>'',
     *  'detail'=> [
     *      [
     *      'refund_no'=>'',
     *     'remark'=>'',
     *      'reason_key'=>''
     *      'audit_state'=>''
     *      ],
     *      [
     *      'refund_no'=>'',
     *     'remark'=>'',
     *      'reason_key'=>''
     *      'audit_state'=>''
     *      ]
     *     ]
     * ]
     */
    public function returnOfGoods($params){
        //开启事务
        DB::beginTransaction();
        try {
            //审核同意的编号
            $yes_list = [];
            //审核拒绝的编号
            $no_list = [];
            foreach ($params['detail'] as $k => $v) {
                $param= filter_array($params['detail'][$k],[
                    'refund_no'=>'required',
                    'remark'=>'required',
                    'reason_key'=>'required',
                    'audit_state'=>'required',
                ]);
                if(count($param)<4){
                    return  false;
                }
                //获取退换货单的信息
                $return = \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($params['detail'][$k]['refund_no']);
                if (!$return) {
                    return false;
                }
                $returnInfo[$k]=$return->getData();
                //获取商品信息
                $goods=\App\Order\Modules\Repository\Order\Goods::getByGoodsNo($returnInfo[$k]['goods_no']);
                if(!$goods){
                    return false;
                }
               $goods_info= $goods->getData();
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
                    $data[$k]['goods_no']=$returnInfo[$k]['goods_no'];
                    //获取商品扩展信息
                    $goodsDelivery[$k]=\App\Order\Modules\Repository\Order\DeliveryDetail::getGoodsDelivery($order,$data);
                    if(!$goodsDelivery){
                        return false;
                    }
                    $goodsDeliveryInfo[$k]=$goodsDelivery[$k]->getData();
                    $goodsDeliveryInfo[$k]['quantity']=$goods_info['quantity'];
                    $yes_list[] = $params['detail'][$k]['refund_no'];
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
                    $returnClose=$goods->returnClose();
                    if (!$returnClose){
                        //事务回滚
                        DB::rollBack();
                        return false;
                    }
                }
            }
            //获取订单信息
            $order=\App\Order\Modules\Repository\Order\Order::getByNo($returnInfo[0]['order_no']);
            foreach($returnInfo as $k=>$v){
                $status[$k]=$returnInfo[$k]['status'];
            }
            if(!in_array(ReturnStatus::ReturnCreated,$status) && !in_array(ReturnStatus::ReturnAgreed,$status)&& !in_array(ReturnStatus::ReturnReceive,$status) && !in_array(ReturnStatus::ReturnTui,$status) && !in_array(ReturnStatus::ReturnTuiHuo,$status) && !in_array(ReturnStatus::ReturnTuiKuan,$status)){
                //如果部分审核同意，订单为冻结状态
                $order_result= $order->returnClose();
                if(!$order_result) {
                    //事务回滚
                    DB::rollBack();
                    return ApiStatus::CODE_33007;//更新订单冻结状态失败
                }
            }
            //事务提交
            DB::commit();
            //存在审核同意商品
            if($goodsDeliveryInfo){
                foreach($goodsDeliveryInfo as $k=>$v){
                    $receive_data[$k] =[
                        'goods_no' => $goodsDeliveryInfo[$k]['goods_no'],
                        'serial_no' => $goodsDeliveryInfo[$k]['serial_number'],
                        'quantity'  => $goodsDeliveryInfo[$k]['quantity'],
                        'imei1'     =>$goodsDeliveryInfo[$k]['imei1'],
                        'imei2'     =>$goodsDeliveryInfo[$k]['imei2'],
                        'imei3'     =>$goodsDeliveryInfo[$k]['imei3'],
                    ];
                }
                $create_receive= Receive::create($order,$params['business_key'],$receive_data);//创建待收货单
                if(!$create_receive){
                    return false;//创建待收货单失败
                }
            }
            //审核发送短信
          /*  if($params['business_key']==OrderStatus::BUSINESS_RETURN){
                if($yes_list){
                    foreach( $yes_list as $no ) {
                        //短信
                        $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_RETURN,$no,SceneConfig::RETURN_APPLY_AGREE);
                        $b=$orderNoticeObj->notify();
                        Log::debug($b?"Order :".$params['order_no']." IS OK":"IS error");
                    }
                }
                if($no_list){
                    foreach( $no_list as $no ){
                            //短信
                            $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_RETURN,$no,SceneConfig::RETURN_APPLY_DISAGREE);
                            $b=$orderNoticeObj->notify();
                            Log::debug($b?"Order :".$params['order_no']." IS OK":"IS error");
                    }
                }
            }*/

            return true;

        }catch( \Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }




    }

    /**
     * 订单退款审核
     * @param $param
     */
    public function refundApply($param){
        //开启事务
        DB::beginTransaction();
        try{
            //获取订单信息
            $order= \App\Order\Modules\Repository\Order\Order::getByNo($param['order_no']);
            if(!$order){
                return false;
            }
            $order_info=$order->getData();
            //获取退款单信息
            $return= \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByOrderNo($param['order_no']);
            if(!$return){
                return false;
            }
            $return_info=$return->getData();
            if($param['status']==0){
                //更新退款单状态为同意
                $returnApply=$return->refundAgree($param['remark']);

                if(!$returnApply){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                //获取订单的支付信息
                $pay_result=$this->orderReturnRepository->getPayNo(1,$param['order_no']);
                if(!$pay_result){
                    return false;
                }
                //创建清单
                $create_data['order_no']=$order_info['order_no'];//订单类型
                $create_data['order_type']=$order_info['order_type'];//订单类型
                $create_data['business_type']=OrderCleaningStatus::businessTypeRefund;//业务类型
                $create_data['business_no']=$return_info['refund_no'];//业务编号
                //退款：直接支付
                if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::FlowerStagePay ||$order_info['pay_type']==\App\Order\Modules\Inc\PayInc::UnionPay){
                    $create_data['out_payment_no']=$pay_result['payment_no'];//支付编号
                    $create_data['order_amount']=$order_info['order_amount']+$order_info['order_insurance'];//退款金额=订单实际支付总租金+意外险总金额
                    if($create_data['order_amount']>0){
                        $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                    }
                }
                //退款：代扣+预授权
                if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::FlowerDepositPay){
                    $create_data['out_payment_no']=$pay_result['payment_no'];//支付编号
                    $create_data['out_auth_no']=$pay_result['fundauth_no'];//预授权编号
                    $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
                    $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                    $create_data['order_amount']=$order_info['order_amount']+$order_info['order_insurance'];//退款金额=订单实际支付总租金+意外险总金额
                    $create_data['auth_unfreeze_amount']=$order_info['order_yajin'];//订单实际支付押金
                    if($create_data['order_amount']>0 && $create_data['auth_unfreeze_amount']>0){
                        $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                    }

                }
                //退款：代扣
                if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::WithhodingPay){
                    $create_data['out_auth_no']=$pay_result['payment_no'];
                    $create_data['order_amount']=$order_info['order_amount']+$order_info['order_insurance'];//退款金额=订单实际支付总租金+意外险总金额
                    if($create_data['order_amount']>0){
                        $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                    }
                }
                $create_clear=\App\Order\Modules\Repository\OrderClearingRepository::createOrderClean($create_data);//创建退款清单
                if(!$create_clear){
                    //事务回滚
                    DB::rollBack();
                    return false;//创建退款清单失败
                }
            }else{
                //更新退款单状态为审核拒绝
                $returnApply=$return->refundAccept($param['remark']);
                if(!$returnApply){
                    return false;
                }
                //更新订单状态
                $orderApply=$order->returnClose();
                if(!$orderApply){
                    return false;
                }

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
     * @param $params
     * @return string
     * @throws \Exception
     *  退货单号refund_no=>['111','222']
     */
    public function cancelApply($params){
        //开启事务
        DB::beginTransaction();
        try{
            foreach($params['refund_no'] as $refund_no){
                //查询退货单信息
                $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($refund_no);
                $return_info[$refund_no]=$return->getData();
                if($return_info[$refund_no]['user_id']!=$params['user_id']){
                    return false;
                }
                //更新退换货状态为已取消
                $cancelApply=$return->close();
                if(!$cancelApply){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                //修改商品状态为租用中
                $goods =\App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info[$refund_no]['goods_no'] );
                if(!$goods->returnClose()){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                $order_no=$return_info[$refund_no]['order_no'];
            }
            //获取订单信息
            $order =\App\Order\Modules\Repository\Order\Order::getByNo($order_no);
            //解冻订单
            if(!$order->returnClose()){
                //事务回滚
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
     * @param $params
     */
    public function cancelRefund($params){
        //开启事务
        DB::beginTransaction();
        try{
            //获取退款单信息
            $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($params['refund_no']);
            $return_info=$return->getData();
            //获取订单信息
            $order =\App\Order\Modules\Repository\Order\Order::getByNo($return_info['order_no']);
            if($return_info['user_id']!=$params['user_id']){
                return false;
            }
            //退款中，已退款不允许取消
            if($return_info['status']==ReturnStatus::ReturnTui || $return_info['status']==ReturnStatus::ReturnTuiKuan){
                return false;
            }
            //更新退款单状态为已取消
            $cancelApply=$return->close();
            if(!$cancelApply){
                //事务回滚
                DB::rollBack();
                return false;
            }
            //更新订单状态
            $orderApply=$order->returnClose();
            if(!$orderApply){
                //事务回滚
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
     * 上传物流单号
     * @param $data
     * @return \Illuminate\Http\JsonResponse|string
     * @throws \Exception
     */
    public function upload_wuliu($data){
        $param = filter_array($data,[
            'order_no'           => 'required',
            'logistics_id'  => 'required',
            'logistics_name'  => 'required',
            'logistics_no'       =>'required',
            'user_id'             =>'required',
        ]);
        if(count($param)<5){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if(empty($data['goods_no'])){
            return ApiStatus::CODE_20001;
        }
        //开启事务
        DB::beginTransaction();
        $ret= $this->orderReturnRepository->upload_wuliu($data);
        if(!$ret){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33008;
        }
        //上传物流单号到收货系统
        $data_params['order_no']=$data['order_no'];
        $data_params['logistics_id']=$data['logistics_id'];
        $data_params['logistics_no']=$data['logistics_no'];
        foreach($data['goods_no'] as $k=>$v){
            $data_params['goods_info'][$k]['goods_no']=$v;
            $where[]=['order_no','=',$data['order_no']];
            $where[]=['goods_no','=',$v];
        }
        $create_receive= Receive::updateLogistics($data_params);
        if(!$create_receive){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_34003;//创建待收货单失败
        }
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;
    }

    /**
     * 获取退换货订单列表方法
     * @param $params
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
        $additional['limit'] = $size;
        $where= $this->_parse_order_where($where);
        $data = $this->orderReturnRepository->get_list($where, $additional);
        foreach($data['data'] as $k=>$v){
            if($data['data'][$k]->status!=ReturnStatus::ReturnCreated){
                $data['data'][$k]->operate_status=false;
            }else{
                $data['data'][$k]->operate_status=true;
            }
            //业务类型
            if($data['data'][$k]->business_key==OrderStatus::BUSINESS_REFUND){
                $data['data'][$k]->business_name=OrderStatus::getBusinessName(OrderStatus::BUSINESS_REFUND);//退款业务
            }elseif($data['data'][$k]->business_key==OrderStatus::BUSINESS_RETURN){
                $data['data'][$k]->business_name=OrderStatus::getBusinessName(OrderStatus::BUSINESS_RETURN);//退货业务
            }elseif($data['data'][$k]->business_key==OrderStatus::BUSINESS_BARTER){
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
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnReceive){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnReceive);//已收货
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnTuiHuo){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiHuo);//已退货
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnHuanHuo){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnHuanHuo);//已换货
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnTuiKuan){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiKuan);//已退款
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnTui){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTui);//退款中
            }
        }
        return $data;
    }

    /** 查询条件过滤
     * @param array $where	【可选】查询条件
     * [
     *      'user_id' => '',	//【可选】用户id
     *      'business_key' => '',	//【必选】string；业务类型
     *      'status'=>''      //【可选】int；阶段
     *      'begin_time'=>''      //【可选】int；开始时间戳
     *      'end_time'=>''      //【可选】int；  截止时间戳
     *      'goods_name' => '',	//【可选】设备名称
     *      'order_no' => '',	//【可选】string；订单编号
     *      'reason_key'=>''      //【可选】int；退货问题
     *      'user_mobile'=>''      //【可选】int；下单用户手机号
     *      'order_status'=>''      //【可选】int；  订单状态
     *      'appid'=>''      //【可选】int；应用来源
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
            $where1[] = ['order_return.create_time', '<=', $where['end_time']];
        }else{
            $where1[] = ['order_return.create_time', '<', $where['end_time']];
        }
        unset($where['begin_time']);
        unset($where['end_time']);
        // order_no 订单编号查询，使用前缀模糊查询
        if( isset($where['order_no']) ){
            $where1[] = ['order_return.order_no', 'like', '%'.$where['order_no'].'%'];
        }
        if( isset($where['reason_key']) ){
            $where1[] = ['order_return.reason_key', '=',$where['reason_key']];
        }
        // order_no 订单编号查询，使用前缀模糊查询
        if( isset($where['goods_name'])){
            $where1[] = ['order_goods.goods_name', 'like', '%'.$where['goods_name'].'%'];
        }
        if(isset($where['mobile'])){
            $where1[] = ['order_info.mobile', '=', $where['mobile']];
        }
        if( isset($where['status']) ){
            $where1[] = ['order_return.status', '=', $where['status']];
        }
        if( isset($where['order_status']) ){
            $where1[] = ['order_info.status', '=', $where['order_status']];
        }
        if(isset($where['appid']) ){
            $where1[] = ['order_info.appid', '=', $where['appid']];
        }
        if( isset($where['business_key'])>0 ){
            $where1[] = ['order_return.business_key', '=', $where['business_key']];
        }else{
            $where1[] = ['order_return.business_key', '!=', OrderStatus::BUSINESS_REFUND];
        }
        return $where1;
    }
    //获取商品信息
    /*public function get_goods_info($params){
        if(empty($params['goods_no'])){
            return ApiStatus::CODE_20001;//商品编号不能为空
        }
        if(empty($params['order_no'])){
            return ApiStatus::CODE_20001;//订单编号不能为空
        }
        return $this->orderReturnRepository->getGoodsList($params);

    }*/
    /**
     * 退货结果查看
     * @param $params
     * @return array|bool|string
     */
    public function returnResult($params){
        try{
            foreach($params as $k=>$v){
               if(empty($v['refund_no'])){
                   return false;
               }
               //获取退货单信息
                $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($v['refund_no']);
               if(!$return){
                  return false;
               }
               $return_info=$return->getData();
                //（退款、退机、换机）状态
                if($return_info['status']==ReturnStatus::ReturnCreated){
                    $return_info['return_status']=ReturnStatus::getStatusName(ReturnStatus::ReturnCreated);//提交申请

                }elseif($return_info['status']==ReturnStatus::ReturnAgreed){
                    $return_info['return_status']=ReturnStatus::getStatusName(ReturnStatus::ReturnAgreed);//同意

                }elseif($return_info['status']==ReturnStatus::ReturnDenied){
                    $return_info['return_status']=ReturnStatus::getStatusName(ReturnStatus::ReturnDenied);//拒绝
                }elseif($return_info['status']==ReturnStatus::ReturnCanceled){
                    $return_info['return_status']=ReturnStatus::getStatusName(ReturnStatus::ReturnCanceled);//取消退货申请
                }elseif($return_info['status']==ReturnStatus::ReturnReceive){
                    $return_info['return_status']=ReturnStatus::getStatusName(ReturnStatus::ReturnReceive);//已收货
                }elseif($return_info['status']==ReturnStatus::ReturnTuiHuo){
                    $result['return_status']=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiHuo);//已退货
                }elseif($return_info['status']==ReturnStatus::ReturnHuanHuo){
                    $return_info['return_status']=ReturnStatus::getStatusName(ReturnStatus::ReturnHuanHuo);//已换货
                }elseif($return_info['status']==ReturnStatus::ReturnTuiKuan){
                    $return_info['return_status']=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiKuan);//已退款
                }elseif($return_info['status']==ReturnStatus::ReturnTui){
                    $return_info['return_status']=ReturnStatus::getStatusName(ReturnStatus::ReturnTui);//退款中
                }
                if(!empty($return_info['evaluation_status'])){
                    if($return_info['evaluation_status']==ReturnStatus::ReturnEvaluationSuccess){
                        $return_info['check_status']="检测合格";
                    }
                    if($return_info['evaluation_status']==ReturnStatus::OrderGoodsIncomplete){
                        $return_info['check_status']="检测不合格";
                    }
                    if($return_info['evaluation_status']==ReturnStatus::ReturnEvaluation){
                        $return_info['check_status']="待检测";
                    }
                }
                return $return_info;
            }
        }catch( \Exception $exc){
            echo $exc->getMessage();
            die;
        }

        $result= $this->orderReturnRepository->returnResult($params);
        if(!$result){
           return ApiStatus::CODE_34002;
        }

    }
    /**
     * 检测合格或不合格
     * @param $order_no
     * @param $business_key
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function isQualified($business_key,$data)
    {
        //开启事务
        DB::beginTransaction();
        try{
            $list=[];
            $yes_list=[];
            $no_list=[];
            foreach($data as $k=>$v){
                if(empty($data[$k]['goods_no']) && empty($data[$k]['check_result']) && empty($data[$k]['check_description'])   && empty($data[$k]['price'])  && empty($data[$k]['evaluation_time']) && empty($data[$k]['refund_no'])){
                   return false;//参数错误
                }
                $order_no='';
                //获取退货单信息
                $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($v['refund_no']);
                if(!$return){
                    return false;
                }
                $return_info=$return->getData();
                //获取订单信息
                $order =\App\Order\Modules\Repository\Order\Order::getByNo($return_info['order_no']);
                if(!$order){
                    return false;
                }
                $order_info=$order->getData();
                //获取商品信息
                $goods =\App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info['goods_no'] );
                if(!$goods){
                    return false;
                }
                $goods_info=$goods->getData();
                $params['evaluation_remark'] = $data[$k]['check_description'];
                $params['evaluation_amount'] =$data[$k]['price'];
                $params['evaluation_time'] =$data[$k]['evaluation_time'];
                if($data[$k]['check_result']=="success") {
                    $yes_list[]=$return_info['refund_no'];
                    $order_no=$return_info['order_no'];//订单编号
                    $params['evaluation_status'] = ReturnStatus::ReturnEvaluationSuccess;
                    //更新退换货单信息
                    $updateReturn=$return->returnCheckOut($params);
                    if(!$updateReturn){
                        DB::rollBack();
                        return false;
                    }
                    if($business_key ==OrderStatus::BUSINESS_RETURN){
                        //获取订单的支付信息
                        $pay_result=$this->orderReturnRepository->getPayNo(1,$return_info['order_no']);
                        if(!$pay_result){
                            return false;
                        }
                        $create_data['order_no']=$return_info['order_no'];//订单类型
                        $create_data['order_type']=$order_info['order_type'];//订单类型
                        $create_data['business_type']=OrderCleaningStatus::businessTypeReturn;//业务类型
                        $create_data['business_no']=$return_info['refund_no'];//业务编号
                        //退款：直接支付
                        if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::FlowerStagePay ||$order_info['pay_type']==\App\Order\Modules\Inc\PayInc::UnionPay){
                            $create_data['out_payment_no']=$pay_result['payment_no'];//支付编号
                            $create_data['order_amount']=$goods_info['amount_after_discount'];//退款金额：商品实际支付优惠后总租金
                            if($goods_info['order_amount']>0){
                                $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                            }

                        }
                        //退款：代扣+预授权
                        if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::FlowerDepositPay){
                            $create_data['out_payment_no']=$pay_result['payment_no'];//支付编号
                            $create_data['out_auth_no']=$pay_result['fundauth_no'];//预授权编号
                            // $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
                            $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
                            $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                            $create_data['order_amount']=$goods_info['amount_after_discount'];//退款金额：商品实际支付优惠后总租金
                            $create_data['auth_unfreeze_amount']=$goods_info['yajin'];//商品实际支付押金
                            if($create_data['order_amount']>0 && $create_data['auth_unfreeze_amount']>0){
                                $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                            }

                        }
                        //退款：代扣
                        if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::WithhodingPay){
                            $create_data['out_auth_no']=$pay_result['payment_no'];
                            $create_data['order_amount']=$goods_info['amount_after_discount'];//退款金额：商品实际支付优惠后总租金
                            if($create_data['order_amount']>0){
                                $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                            }
                        }
                        $create_clear=\App\Order\Modules\Repository\OrderClearingRepository::createOrderClean($create_data);//创建退款清单
                        if(!$create_clear){
                            //事务回滚
                            DB::rollBack();
                            return false;//创建退款清单失败
                        }
                    }
                    $delivery_data['goods'][$k]['goods_no']=$return_info['goods_no'];

                }else{
                    $list[]=$return_info['status'];
                    $no_list[]=$return_info['status'];
                    if($business_key ==OrderStatus::BUSINESS_RETURN){
                        //更新退货单检测信息
                        $updateCheck=$return->returnUnqualified($params);
                        if(!$updateCheck){
                            //事务回滚
                            DB::rollBack();
                            return false;
                        }
                    }
                    if($business_key ==OrderStatus::BUSINESS_BARTER){
                        //更新退货单检测信息
                        $updateBarterCheck=$return->barterUnqualified($params);
                        if(!$updateBarterCheck){
                            //事务回滚
                            DB::rollBack();
                            return false;
                        }
                        //更新商品状态
                        $updateGoods=$goods->returnClose();
                        if(!$updateGoods){
                            //事务回滚
                            DB::rollBack();
                            return false;
                        }
                    }


                }
            }
            if($business_key==OrderStatus::BUSINESS_BARTER){
                if($list){
                    if(!in_array(ReturnStatus::ReturnCreated,$list) && !in_array(ReturnStatus::ReturnAgreed,$list)&& !in_array(ReturnStatus::ReturnReceive,$list) && !in_array(ReturnStatus::ReturnTui,$list)) {
                        //修改订单为租用中
                        $order_result = $order->returnClose();
                        if (!$order_result) {
                            //事务回滚
                            DB::rollBack();
                            return ApiStatus::CODE_33007;//更新订单冻结状态失败
                        }
                    }
                }
            }


            DB::commit();
            if($business_key ==OrderStatus::BUSINESS_BARTER){
                if($order_no){
                    //获取用户收货信息
                    $userAddress=\App\Order\Modules\Repository\Order\Address::getByOrderNo($order_no);
                    if(!$userAddress){
                        return false;
                    }
                    $user_info=$userAddress->getData();
                    $delivery_data['order_no']=$order_no;
                    $delivery_data['mobile']=$user_info['consignee_mobile'];
                    $delivery_data['realname']=$user_info['name'];
                    $delivery_data['address_info']=$user_info['address_info'];
                    //创建换货单
                    $delivery_result=Delivery::createDelivery($delivery_data);
                    if(!$delivery_result){
                        return false;//创建换货单失败
                    }
                }
            }

            //发短信
           /* if($business_key==OrderStatus::BUSINESS_RETURN){
                if($yes_list){
                    foreach( $yes_list as $no ) {
                        //短信
                        $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_RETURN,$no,SceneConfig::RETURN_CHECK_OUT);
                        $b=$orderNoticeObj->notify();
                        Log::debug($b?"Order :".$return_info['order_no']." IS OK":"IS error");
                    }
                }
                if($no_list){
                    foreach( $no_list as $no ){
                        //短信
                        $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_RETURN,$no,SceneConfig::RETURN_UNQUALIFIED);
                        $b=$orderNoticeObj->notify();
                        Log::debug($b?"Order :".$return_info['order_no']." IS OK":"IS error");
                    }
                }
            }*/

            return true;
        }catch( \Exception $exc){
            echo $exc->getMessage();
            die;
        }
    }
    /*
     * 申请订单退款
     * order_no
     * user_id
     * business_key
     */
    public function update_return_info($params){
        $param= filter_array($params,[
            'order_no'=>'required',
            'user_id'=>'required',
            'business_key'=>'required',
        ]);
        if(count($param)<3){
            return  ApiStatus::CODE_20001;
        }
        $order_result= $this->orderReturnRepository->get_order_info($params);//获取订单信息
        if(!$order_result){
            return ApiStatus::CODE_34005;//未找到此订单
        }
        if($order_result['order_status'] !=OrderStatus::BUSINESS_RETURN && $order_result['order_status'] !=OrderStatus::OrderPayed){
            return ApiStatus::CODE_33002;//此订单不符合规则
        }
        //如果订单是已确认，待发货状态，通知收发货系统取消发货
        if($order_result['order_status']==OrderStatus::OrderInStock){
            $cancel=Delivery::cancel($params['order_no']);
            if(!$cancel){
                return ApiStatus::CODE_33006;//取消发货失败
            }
        }
        $data['business_key']=$params['business_key'];
        $data['order_no']=$order_result['order_no'];
        $data['user_id']=$order_result['user_id'];
        $data['pay_amount']=$order_result['order_amount']+$order_result['order_yajin']+$order_result['order_insurance'];//实际支付金额=订单实际总租金+押金+意外险
        $data['status']=ReturnStatus::ReturnCreated;
        $data['refund_no']=createNo('2');
        $data['create_time']=time();
        //开启事物
        DB::beginTransaction();
        //创建申请退款记录
        $addresult= $this->orderReturnRepository->add($data);
        if(!$addresult){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_34003;//创建失败
        }
        // 修改冻结类型
        $freeze_result= $this->orderReturnRepository->update_freeze($params,$freeze_type=OrderFreezeStatus::Refund);
        if(!$freeze_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33007;//修改冻结类型失败
        }
        $update_result= $this->orderReturnRepository->goods_update_status($params);//修改商品信息
        if(!$update_result){
            //事务回滚
            DB::rollBack();
            return  ApiStatus::CODE_33007;//修改商品状态失败
        }
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;

    }

    /**
     * 退换货物流单号上传
     * @param $params
     * @return bool|string
     * @throws \Exception
     *
     */
    public function uploadWuliu($params){
        //开启事务
        DB::beginTransaction();
        try{
            $data=[];
            foreach($params['goods_info'] as $k=>$v){
                //获取退款单信息
                $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($v['refund_no']);
                if(!$return){
                    return false;
                }
                $return_info=$return->getData();
                //获取订单信息
                $order=\App\Order\Modules\Repository\Order\Order::getByNo($return_info['order_no']);
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
                $uploadLogistics= $return->uploadLogistics($params);
                if(!$uploadLogistics){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                $data['order_no']=$return_info['order_no'];
                $data['logistics_id']=$params['logistics_id'];
                $data['logistics_no']=$params['logistics_no'];
                $data['goods_info'][$k]['goods_no']=$return_info['goods_no'];
                //提交事务
                DB::commit();
                return ApiStatus::CODE_0;
            }
            //上传物流单号到收货系统
            $create_receive= Receive::updateLogistics($data);
            if(!$create_receive){
                return false;
            }

        }catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }
    }


    /**
     * 换货用户收到货
     * @param $params
     * @return string
     * @throws \Exception
     *[order_no=>'111'
     * [goods_no=>'222']
     * [goods_no=>'333']
     * ]
     */
    public function updateorder($params){
        //开启事物
        DB::beginTransaction();
        try{
            //获取订单信息
            $order=\App\Order\Modules\Repository\Order\Order::getByNo($params['order_no']);
            if(!$order){
                return false;
            }
            foreach($params['goods_info'] as $goods_no){
                //获取换货单信息
                $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByInfo($params['order_no'],$goods_no);
                if(!$return){
                    return false;
                }
                //更新退货单状态为已换货
                $updateBarter=$return->barterFinish();
                if(!$updateBarter){
                    DB::rollBack();
                    return false;
                }
                $goods=\App\Order\Modules\Repository\Order\Goods::getByGoodsNo($goods_no);
                if(!$goods){
                    return false;
                }
                //更新商品状态为已换货
                $updateGoods=$goods->barterFinish();
                if(!$updateGoods){
                    DB::rollBack();
                    return false;
                }
            }
            //订单解冻
            $updateOrder=$order->returnClose();
            if(!$updateOrder){
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
     * 换货已发货通知
     * @param $params
     * @return \Illuminate\Http\JsonResponse|string
     *
     */
    public function createchange($order_no,$goods_info){
        //开启事物
        DB::beginTransaction();
        try{
            foreach ($goods_info as $k=>$v) {
                //获取设备信息
                $delivery=\App\Order\Modules\Repository\Order\DeliveryDetail::getGoodsDeliveryInfo($order_no,$v['goods_no']);
                //更新原设备为无效
                $updateDelivery=$delivery->barterDelivery();
                if(!$updateDelivery){
                    DB::rollBack();
                    return false;
                }
            }
            $goods_result= \App\Order\Modules\Repository\Order\DeliveryDetail::addGoodsDeliveryDetail($order_no,$goods_info);
            if(!$goods_result){
                return false;//创建换货记录失败
            }
            DB::commit();
            return true;
        }catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }


    }
    //退款成功更新退款状态
    public function refundUpdate($params){
        //开启事物
        DB::beginTransaction();
        try{
            //获取退货单信息
            $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($params['business_no']);
            if(!$return){
                return false;
            }

            $return_info=$return->getData();
            //获取订单信息
            $order=\App\Order\Modules\Repository\Order\Order::getByNo($params['order_no']);
            if(!$order){
                return false;
            }
            //查询此订单的商品
            $goodInfo=\App\Order\Modules\Repository\OrderReturnRepository::getGoodsInfo($params['order_no']);
            if($return_info['goods_no']){
                //修改退货单状态为已退货
                $updateReturn=$return->returnFinish($params);
                if(!$updateReturn){
                    DB::rollBack();
                    return false;
                }
                //获取商品信息
                $goods=\App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info['goods_no']);
                if(!$goods){
                    return false;
                }
                //修改商品状态
               $updateGoods= $goods->returnFinish();
                if(!$updateGoods){
                    DB::rollBack();
                    return false;
                }
                //获取此订单的商品是否还有处理中的设备，没有则解冻
                $status=false;
                foreach($goodInfo as $k=>$v){
                   if($v['status']==OrderGoodStatus::RENTING_MACHINE || $v['status']==OrderGoodStatus::REFUNDED || $v['status']==OrderGoodStatus::EXCHANGE_OF_GOODS){
                       $status=true;
                   }else{
                        $status=false;
                        break;
                    }
                }
                if($status==true){
                    //解冻订单并关闭订单
                    $updateOrder=$order->refundFinish();
                    if(!$updateOrder){
                        DB::rollBack();
                        return false;
                    }
                }
                $params['goods_no']=$return_info['goods_no'];
                //释放库存
                //查询商品的信息
                $orderGoods = OrderRepository::getGoodsListByGoodsId($params);


            }else{
                //修改退货单状态为已退货
                $updateReturn=$return->refundFinish($params);
                if(!$updateReturn){
                    DB::rollBack();
                    return false;
                }
                //解冻订单并关闭订单
                $updateOrder=$order->refundFinish($params);
                if(!$updateOrder){
                    DB::rollBack();
                    return false;
                }
                //释放库存
                //查询商品的信息
                $orderGoods = OrderRepository::getGoodsListByGoodsId($params);
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
                }
                $success =Goods::addStock($goods_arr);
            }
            //分期关闭
            //查询分期
            //根据订单退和商品退走不同的地方
            if(isset($params['goods_no'])){
                if ($orderGoods['zuqi_type'] == OrderStatus::ZUQI_TYPE_MONTH){
                    $success =  OrderInstalment::close($params);
                    if (!$success) {
                        DB::rollBack();
                        return false;
                    }

                }
            }else{
                //查询订单的状态
                $orderInfoData =  OrderRepository::getInfoById($params['order_no'],$return_info['user_id']);
                if ($orderInfoData['zuqi_type'] == OrderStatus::ZUQI_TYPE_MONTH){
                    $success =  OrderInstalment::close($params['order_no']);
                    if (!$success) {
                        DB::rollBack();
                        return false;
                    }
                }
            }
            if (!$success || empty($orderGoods)) {
                DB::rollBack();
                return false;
            }

            DB::commit();
            return true;
            //解冻订单
        }catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }
    }
    //检测不合格线下寄回并修改订单冻结状态
    public function updateOrderStatus($params){
        if($params['order_no']){
            return ApiStatus::CODE_20001;
        }
        $data['freeze_type']=OrderFreezeStatus::Non;
        $order_result= $this->orderReturnRepository->update_freeze($params,$data);
        if(!$order_result){
            return ApiStatus::CODE_33007;
        }
        return ApiStatus::CODE_0;
    }

    /**
     * 退货退款创建清单
     * @param $params
     */
    public function refundTo($params){
        $param = filter_array($params,[
            'order_no'=> 'required',
            'goods_no'  => 'required',
            'pay_refund'=>'required',
            'business_type'=>'required',
            'business_no'=>'required',
        ]);
        if(count($param)<5){
            return  apiResponse([],ApiStatus::CODE_20001,'参数错误');
        }
        //获取商品和订单信息
        $goods_result=$this->orderReturnRepository->orderGoodsInfo($params);
        if(!$goods_result){
            return ApiStatus::CODE_34005;//获取商品和订单信息错误
        }
        if($params['pay_refund']>$goods_result[0]->price){
           return false;
        }
        if(isset($params['goods_yajin'])>$goods_result[0]->yajin){
          return false;
        }
        if(isset($params['refund_price'])>$goods_result[0]->yajin){
            return false;
        }
        $OrderClearingRepository=new OrderClearingRepository();
        //获取此订单的支付方式
        $pay_result=$this->orderReturnRepository->payRefund($params);
        if(!$pay_result){
            return ApiStatus::CODE_50001;//获取订单支付方式错误
        }
        //创建退款清单
        $create_data['out_account']=$goods_result[0]->pay_type;//支付方式
        $create_data['order_type']=$goods_result[0]->order_type;//订单类型
        $create_data['business_type']=$params['business_type'];
        $create_data['business_no']=$params['business_no'];
        $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
        if(isset($params['refund_price'])){
            $create_data['deposit_unfreeze_amount']=$params['refund_price'];//退还押金金额
        }
        $create_data['payment_no']=$pay_result['payment_no'];
        $create_data['auth_no']=$pay_result['fundauth_no'];
        $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
        $create_data['refund_amount']=$params['pay_refund'];//退款金额（租金）
        $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
        $create_data['user_id']=$goods_result[0]->user_id;
        $create_data['app_id']=$goods_result[0]->appid;
        $create_clear= $OrderClearingRepository->createOrderClean($create_data);//创建退款清单
        if(!$create_clear){
            return ApiStatus::CODE_34008;//创建退款清单失败
        }
        return  ApiStatus::CODE_0;

    }

    /**
     * 退换货点击审核弹出退换货单
     * @param $params
     */
    public function returnApplyList($params){
        $where[]=['order_return.order_no','=',$params['order_no']];
        $where[]=['order_return.business_key','=',$params['business_key']];
        if(isset($params['status'])){
            $where[]=['order_return.status','=',ReturnStatus::ReturnCreated];
        }
        $return_list= $this->orderReturnRepository->returnApplyList($where);//待审核的退换货列表
        return $return_list;
    }

    /**
     *获取退款单数据
     * @param $params
     */
    public function getOrderStatus($params){
        $where[]=['order_no','=',$params['order_no']];
        $return_list= $this->orderReturnRepository->get_type($where);
        return $return_list;
    }
    /**
     * 退换货除已完成单的检测不合格的数据
     * @param $params
     */
    public function returnCheckList($params){
        $where[]=['order_return.order_no','=',$params['order_no']];
        $where[]=['order_return.business_key','=',$params['business_key']];
        $where[]=['order_return.evaluation_status','=',$params['evaluation_status']];
        $return_list= $this->orderReturnRepository->returnApplyList($where);//待审核的退换货列表
        $data=[];
        foreach($return_list as $k=>$v){
            if($return_list[$k]->status==ReturnStatus::ReturnCreated || $return_list[$k]->status==ReturnStatus::ReturnAgreed||$return_list[$k]->status==ReturnStatus::ReturnDenied ){
                $data[$k]['goods_name']=$return_list[$k]->goods_name;
            }

        }
        return $data;
    }

    /**
     * 检测不合格拒绝退款
     * @param $params
     *
     */
    public function refuseRefund($params){
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
                $return=\App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($params[$k]['refund_no']);
                if(!$return){
                    return false;
                }
                $return_info[$k]=$return->getData();
                $order=$return_info[$k]['order_no'];
                //更新退货单状态为已取消
                $refuseReturn=$return->refuseRefund($params[$k]['refuse_refund_remark']);
                if(!$refuseReturn){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
                //获取商品信息
                $goods=\App\Order\Modules\Repository\Order\Goods::getByGoodsNo($return_info[$k]['goods_no']);

                if(!$goods){
                    return false;
                }
                //更新商品状态为租用中
                $refuseGoods=$goods->returnClose();
                if(!$refuseGoods){
                    //事务回滚
                    DB::rollBack();
                    return false;
                }
            }
            //获取订单信息
            $order=\App\Order\Modules\Repository\Order\Order::getByNo($order);
            foreach($return_info as $k=>$v){
                $status[$k]=$return_info[$k]['status'];
            }
            if(!in_array(ReturnStatus::ReturnCreated,$status) && !in_array(ReturnStatus::ReturnAgreed,$status)&& !in_array(ReturnStatus::ReturnReceive,$status) && !in_array(ReturnStatus::ReturnTui,$status) && !in_array(ReturnStatus::ReturnTuiHuo,$status) && !in_array(ReturnStatus::ReturnTuiKuan,$status)){
                //修改订单为租用中
                $order_result= $order->returnClose();
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
}