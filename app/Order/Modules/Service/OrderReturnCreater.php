<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use App\Lib\Warehouse\Receive;
use \App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use \App\Order\Modules\Inc\ReturnStatus;
use \App\Order\Modules\Inc\OrderCleaningStatus;
use \App\Order\Modules\Inc\OrderStatus;
use \App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Repository\OrderReturnRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Lib\User\User;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderClearingRepository;
use App\Lib\Warehouse\Delivery;
class OrderReturnCreater
{

    protected $orderReturnRepository;
    protected $orderRepository;
    protected $orderGoodsRepository;
    protected $OrderClearingRepository;
    public function __construct(orderReturnRepository $orderReturnRepository,orderRepository $orderRepository,orderGoodsRepository $orderGoodsRepository,OrderClearingRepository $OrderClearingRepository)
    {

        $this->orderReturnRepository = $orderReturnRepository;
        $this->OrderClearingRepository = $OrderClearingRepository;
        $this->orderRepository = $orderRepository;
        $this->orderGoodsRepository =$orderGoodsRepository;
    }
    public function get_return_info($data){
        return $this->orderReturnRepository->get_return_info($data);
    }
    //添加退换货数据
    public function add($params){
        if(empty($params['goods_no'])){
            return ApiStatus::CODE_40000;//商品编号不能为空
        }
        if(empty($params['order_no'])) {
            return ApiStatus::CODE_33003;
        }
        $goods_no=explode(',',$params['goods_no']);
       foreach($goods_no as $k=>$v){
           $data[$k]['goods_no']=$v;
           $data[$k]['order_no']=$params['order_no'];
           $data[$k]['business_key']=$params['business_key'];
           $data[$k]['loss_type']=$params['loss_type'];
           $data[$k]['reason_id']=$params['reason_id'];
           $data[$k]['reason_text']=$params['reason_text'];
           $data[$k]['user_id']=$params['user_id'];
           $data[$k]['status']=ReturnStatus::ReturnCreated;
           $data[$k]['refund_no']=createNo('2');
           $data[$k]['create_time']=time();
       }
       $create_return=$this->orderReturnRepository->add($data);

       if(!$create_return){
           return ApiStatus::CODE_34007;//创建失败
       }
        $return_order= $this->orderRepository->order_update($params['order_no']);//修改订单状态
        if(!$return_order){
            return ApiStatus::CODE_33007;//修改订单状态失败
        }
        $return_goods = $this->orderReturnRepository->goods_update_status($params);//修改商品状态

        if(!$return_goods){
           return ApiStatus::CODE_33009;//修改商品状态失败
        }
        return ApiStatus::CODE_0;

    }
    /**
     * 管理员审核 --同意
     * @param int $id 【必选】退货单ID
     * @param array $data   【必选】退货单审核信息
     * array(
     *       'id'=>''【必选】退货单ID
     *      'order_no' =>'',        //【必须】订单ID
     *      'remark'=>'',         //【必须】审核备注
     *      'status'=>''         //【必须】审核状态
     *
     * )
     *
     * @return boolean  true :插入成功  false:插入失败
     *
     *
     */
    public function agree_return($params){
        $param = filter_array($params,[
            'order_no' => 'required',
            'remark'  => 'required',
        ]);

        if(count($param)<2){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res= $this->orderReturnRepository->update_return($params);//修改退货单信息
        if(!$res){
            return ApiStatus::CODE_33008;//更新审核状态失败
        }
        //获取用户订单信息
        $params_where['orderNo']=$params['order_no'];
        $order_info=$this->orderRepository->getOrderInfo($params_where);
        $where[]=['order_no','=',$params['order_no']];
        if(isset($params['goods_no'])){
            $where[]=['goods_no','=',$params['goods_no']];
        }
       if($res['business_key']==ReturnStatus::OrderTuiKuan){
           //创建退款清单
           $create_data['order_no']=$params['order_no'];
           $create_data['out_account']=$order_info[0]->pay_type;//出账方式
           $create_data['business_type']=OrderCleaningStatus::businessTypeRefund;
           $create_data['business_no']=ReturnStatus::OrderTuiKuan;
           $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
           $create_data['deposit_unfreeze_amount']=$order_info[0]->goods_yajin;//退还押金金额
           $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
           $create_data['refund_amount']=$order_info[0]->order_amount;//退款金额（租金）
           $create_data['refund_status']=OrderCleaningStatus::refundCancel;//退款状态
           $create_clear= $this->OrderClearingRepository->createOrderClean($create_data);//创建退款清单
          if(!$create_clear){
              return ApiStatus::CODE_34008;//创建退款清单失败
          }
           //修改退款状态为退款中
           $data['status']=ReturnStatus::ReturnTui;
           $tui_result=$this->orderReturnRepository->is_qualified($where,$data);
           if(!$tui_result){
               return ApiStatus::CODE_33008;
           }
           $goodsresult=$this->orderReturnRepository->goodsupdate($params);//修改商品状态
           if(!$goodsresult){
               return ApiStatus::CODE_33009;
           }
           /*$b =SmsApi::sendMessage($order_info[0]->mobile,'SMS_113455999',[
               'realName' =>$order_info[0]->realName,
               'orderNo' => $order_info[0]->order_no,
               'goodsName' => $goods_info[0]['good_name'],
               'shoujianrenName' => "test",
               'returnAddress' => "test",
               'serviceTel'=>OldInc::Customer_Service_Phone,
           ],$order_info[0]->order_no);*/
           return ApiStatus::CODE_0;
        }
        $goods_result=$this->orderReturnRepository->goods_update($params);//修改商品状态
        if(!$goods_result) {
            return ApiStatus::CODE_33009;
        }
            //获取商品信息
       $goods_info= $this->orderReturnRepository->get_goods_info($params);
       if(!$goods_info){
          return ApiStatus::CODE_40000;//商品信息错误
       }
       $return_info= $this->orderReturnRepository->get_type($where);//获取业务类型
      // $create_receive= Receive::create($params['order_no'],$return_info['business_key'],$goods_info);//创建待收货单
       // if(!$create_receive){
       //    return false;
      //  }
            //申请退货同意发送短信
          /*  $b =SmsApi::sendMessage($order_info[0]->mobile,'SMS_113455999',[
                'realName' =>$order_info[0]->realName,
                'orderNo' => $order_info[0]->order_no,
                'goodsName' => $goods_info[0]['good_name'],
                'shoujianrenName' => "test",
                'returnAddress' => "test",
                'serviceTel'=>OldInc::Customer_Service_Phone,
            ],$order_info[0]->order_no);*/
            return ApiStatus::CODE_0;//成功

    }
    /**
     * 管理员审核 --不同意
     * @param int $id 【必选】退货单ID
     * @param array $params   【必选】退货单审核信息
     * array(
     *      'order_no' =>'',        // 【必须】订单编号
     *	    'remark' => ''，         // 【可选】 管理员审批内容
     * )
     * @return boolean  true :插入成功  false:插入失败
     *
     *
     */

    public function deny_return($params){
        if(empty($params['order_no'])){
            return ApiStatus::CODE_33003;
        }
        if(empty($params['remark'])){
            return ApiStatus::CODE_33005;
        }
        $res = $this->orderReturnRepository->deny_return($params);//修改退货单状态
        if(!$res){
            return ApiStatus::CODE_33008;//更新审核状态失败
        }
       $goods_result= $this->orderRepository->deny_update($params['order_no']);//修改订单冻结状态
        if(!$goods_result) {
            return ApiStatus::CODE_33007;//更新订单冻结状态失败
        }
        $deny_goods=$this->orderReturnRepository->deny_goods_update($params);//修改商品状态
        if(!$deny_goods){
           return ApiStatus::CODE_33009;//修改商品信息失败
        }

        //获取订单信息
        $data['orderNo']=$params['order_no'];
        $order_info= $this->orderRepository->getOrderInfo($data);
        //获取商品信息
        $goods_info= $this->orderReturnRepository->get_goods_info($params);

        if(!$goods_info){
            return ApiStatus::CODE_40000;//商品信息错误
        }
        //申请退货拒绝发送短信
        // SmsApi::sendMessage($user_info['mobile'],1,array());
       /* $b = SmsApi::sendMessage('13020059043','hsb_sms_d284d',[
            'realName' =>$order_info[0]->realname,
            'orderNo' =>$order_info[0]->order_no,
            'goodsName' => $goods_info[0]['good_name'],
            'serviceTel'=>OldInc::Customer_Service_Phone,
        ],$order_info[0]->order_no);*/
        return ApiStatus::CODE_0;//成功
    }
    //取消退货申请
    public function cancel_apply($params){
        if(empty($params['order_no'])){
            return apiResponse([], ApiStatus::CODE_33003,'订单编号不能为空');
        }
        if(empty($params['user_id'])){
            return apiResponse([], ApiStatus::CODE_20001,'用户id不能为空');
        }
        //查询是否存在此退货单，存在判断退货单状态是否允许取消
        $return_result = $this->orderReturnRepository->get_info_by_order_no($params);
        if($return_result){
            if($return_result['status']==ReturnStatus::ReturnReceive || $return_result['status']==ReturnStatus::ReturnTuiHuo || $return_result['status']==ReturnStatus::ReturnHuanHuo || $return_result['status']==ReturnStatus::ReturnTuiKuan || $return_result['status']==ReturnStatus::ReturnTui){
                return apiResponse([], ApiStatus::CODE_34006,'不允许取消退货申请');
            }
        }
        $res = $this->orderReturnRepository->cancel_apply($params);

        if(!$res) {
            return ApiStatus::CODE_33008;//更新审核状态失败
        }
        $order = $this->orderRepository->deny_update($params['order_no']);
        if(!$order) {
            return ApiStatus::CODE_33007;//更新订单状态失败
        }
        //修改商品状态
        $order_goods = $this->orderReturnRepository->update_freeze($params);
        if(!$order_goods) {
            return ApiStatus::CODE_33009;//const CODE_33008 = '33008'; //[退换货]修改退换货状态失败
        }
        //修改订单冻结状态
        $freeze_type=OrderFreezeStatus::Non;
        $freeze_result=$this->orderReturnRepository->update_freeze($params,$freeze_type);
        if(!$freeze_result){
            return ApiStatus::CODE_33007;//修改订单冻结状态失败
        }
        return ApiStatus::CODE_0;//成功

    }
    //获取退款单信息
    public function get_info_by_order_no($order_no){
        if(empty($order_no)){
            return ApiStatus::CODE_33003;//const CODE_33008 = '33008'; //[退换货]订单编号不能为空
        }
        return $this->orderReturnRepository->get_info_by_order_no($order_no);
    }
    //上传物流单号
    public function upload_wuliu($data){
        return $this->orderReturnRepository->upload_wuliu($data);
    }
    //获取退换货订单列表方法
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

        if(isset($params['business_key']) > 0) {
            $where['business_key'] = intval($params['business_key']);
        }
        if (isset($params['keywords']) != '') {
            if (isset($params['kw_type']) == 'goods_name') {
                $where['goods_name'] = $params['keywords'];
            } elseif (isset($params['kw_type']) == 'order_no') {
                $where['order_no'] = $params['keywords'];
            } elseif (isset($params['kw_type']) == 'mobile'){
                $user_info = $this->orderReturnRepository->get_user_info($params['keywords']);
                if (empty($user_info)) {
                    // 如果没有用户  直接返回空
                    return apiResponse([], ApiStatus::CODE_0, 'success');
                } else {
                    $where['user_id'] = $user_info['user_id'];
                }
            }
        }
        if (isset($params['return_status']) && $params['return_status'] > 0) {
            $where['status'] = intval($params['return_status']);
        }
        if (isset($params['user_id'])!='') {
            $where['user_id'] = $params['user_id'];
        }
        if (isset($params['order_status'])!='') {
            $where['order_status'] = $params['order_status'];
        }
        if (isset($params['appid'])!='') {
            $where['appid'] = $params['appid'];
        }
        // 查询退货申请单
        $additional['page'] = $page;
        $additional['limit'] = $size;

        $where = $this->_parse_order_where($where);
       
        $data = $this->orderReturnRepository->get_list($where, $additional);
        foreach($data['data'] as $k=>$v){
            //业务类型
            if($data['data'][$k]->business_key==ReturnStatus::OrderTuiKuan){
                $data['data'][$k]->business_name=ReturnStatus::getBusinessName(ReturnStatus::OrderTuiKuan);
            }elseif($data['data'][$k]->business_key==ReturnStatus::OrderTuiHuo){
                $data['data'][$k]->business_name=ReturnStatus::getBusinessName(ReturnStatus::OrderTuiHuo);
            }elseif($data['data'][$k]->business_key==ReturnStatus::OrderHuanHuo){
                $data['data'][$k]->business_name=ReturnStatus::getBusinessName(ReturnStatus::OrderHuanHuo);
            }
            //订单状态
            if($data['data'][$k]->order_status==OrderStatus::OrderWaitPaying){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderWaitPaying);
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderPayed){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderPayed);
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderInStock){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderInStock);
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderDeliveryed){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderDeliveryed);
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderInService){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderInService);
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderClosed){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderClosed);
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderRefunded){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderRefunded);
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderGivebacked){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderGivebacked);
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderBuyouted){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderBuyouted);
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderChanged){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderChanged);
            }
            $data['data'][$k]->c_time=date('Y-m-d H:i:s',$data['data'][$k]->c_time);
            $data['data'][$k]->create_time=date('Y-m-d H:i:s',$data['data'][$k]->create_time);
            $data['data'][$k]->complete_time=date('Y-m-d H:i:s',$data['data'][$k]->complete_time);
            $data['data'][$k]->wuliu_channel_name="顺丰快递";
        }
        return $data;
    }

/** 查询条件过滤
 * @param array $where	【可选】查询条件
 * [
 *      'return_id' => '',	//【可选】mixed 退货申请单ID，string|array （string：多个','分割）（array：ID数组）多个只支持
 *      'case_id' => '',	//【可选】string；退货原因ID
 *      'status'=>''      //【可选】int；阶段
 *      'begin_time'=>''      //【可选】int；开始时间戳
 *      'end_time'=>''      //【可选】int；  截止时间戳
 * ]
 * @return array	查询条件
 */
public function _parse_order_where($where=[]){

      $where = filter_array($where, [
          'business_key' => 'required|is_id',
      ]);
      // 结束时间（可选），默认为为当前时间
      if( !isset($where['end_time']) ){
          $where['end_time'] = time();
      }
      if( isset($where['user_id']) ){
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
        $where1[] = ['order_return.order_no', 'like', $where['order_no'].'%'];
    }
    // order_no 订单编号查询，使用前缀模糊查询
    if( isset($where['goods_name'])){
        $where1[] = ['order_goods.goods_name', 'like', $where['goods_name'].'%'];
    }
    if( isset($where['status']) ){
        $where1[] = ['order_return.status', '=', $where['status']];
    }
    if( isset($where['order_status']) ){
        $where1[] = ['order_info.status', '=', $where['order_status']];
    }
    if( isset($where['appid']) ){
        $where1[] = ['order_info.appid', '=', $where['appid']];
    }
    if( isset($where['business_key']) ){
        $where1[] = ['order_return.business_key', '=', $where['business_key']];
    }
    return $where1;
}
    //获取商品信息
    public function get_goods_info($goods_no){
        if(empty($goods_no)){
            return ApiStatus::CODE_20001;//商品编号不能为空
        }
        return $this->orderGoodsRepository->getgoodsList($goods_no);

    }
    //退货结果查看
    public function returnResult($params){
        if(empty($params['order_no'])){
            return apiResponse( [], ApiStatus::CODE_33003,'订单编号不能为空' );
        }
        if(empty($params['goods_no'])){
            return apiResponse( [], ApiStatus::CODE_40000,'商品编号不能为空' );
        }
       $result= $this->orderReturnRepository->returnResult($params);
        if($result){
            return $result;
        }else{
            return false;
        }

    }
    //检测合格或不合格
    public function is_qualified($order_no,$business_key,$data)
    {
        if (empty($order_no)){
            return ApiStatus::CODE_33003;//订单编号不能为空
        }
        $where[] = ['order_no', '=', $order_no];
        if (isset($data['sku_no'])){
            $where[] = ['goods_no', '=', $data['sku_no']];
        }
        //获取订单信息
       $order_info = $this->orderReturnRepository->order_info($order_no);
        $params['remark'] = $data['check_description'];
        if ($data['check_result'] == "success"){
            if ($business_key == ReturnStatus::OrderTuiHuo) {
                $params['status'] = ReturnStatus::ReturnTuiHuo;
                $result = $this->orderReturnRepository->is_qualified($where, $params);//修改退货单状态和原因
                if(!$result){
                    return ApiStatus::CODE_33008;//修改退货单信息失败
                }
                //修改商品状态
                $goods_data['goods_status']=$params['status'];
                $extend_where[] = ['order_no', '=', $order_no];
                if (isset($data['sku_no'])){
                    $extend_where[] = ['good_no', '=', $data['sku_no']];
                }
                $goods_result=$this->orderReturnRepository->updategoods($where,$goods_data);
                if (!$goods_result){
                    return ApiStatus::CODE_33009;//修改商品状态失败
                }
                //创建退货记录
                if (isset($data['order_no']) && isset($data['good_id']) &&  isset($data['sku_no']) &&  isset($data['serial_number'])){
                    return ApiStatus::CODE_20001;//参数错误
                }
                $createdata['order_no']=$order_no;
                $createdata['good_id']=$data['good_id'];
                $createdata['good_no']=$data['sku_no'];
                $createdata['serial_number']=$data['serial_number'];
                $createdata['imei1']=isset($data['imei1'])?$data['imei1']:'' ;
                $createdata['imei2']=isset($data['imei2'])?$data['imei2']:'' ;
                $createdata['imei3']=isset($data['imei3'])?$data['imei3']:'' ;
                $createdata['status']='1' ;
                $create_result= $this->orderReturnRepository->createchange($createdata);
                if(!$create_result){
                    return ApiStatus::CODE_34009;//创建换货单记录失败
                }
                //创建退款清单
                $create_data['order_no']=$order_no;
                $create_data['out_account']=$order_info['pay_type'];//出账方式
                $create_data['business_type']=OrderCleaningStatus::businessTypeRefund;
                $create_data['business_no']=ReturnStatus::OrderTuiKuan;
                $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
                $create_data['deposit_unfreeze_amount']=$order_info['goods_yajin'];//退还押金金额
                $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
                $create_data['refund_amount']=$order_info['order_amount'];//退款金额（租金）
                $create_data['refund_status']=OrderCleaningStatus::refundCancel;//退款状态
                //信息待定
                $create_clear= $this->OrderClearingRepository->createOrderClean($create_data);//创建退款清单
                if(!$create_clear){
                    return ApiStatus::CODE_34008;//创建退款清单失败
                }
                //修改退款状态为退款中
                $data_tui['status']=ReturnStatus::ReturnTui;
                $tui_result=$this->orderReturnRepository->is_tui_qualified($where,$data_tui);
                if(!$tui_result){
                    return ApiStatus::CODE_33008;
                }
                return ApiStatus::CODE_0;
            }
            if ($business_key == ReturnStatus::OrderHuanHuo){
                /*  $params['status'] = ReturnStatus::ReturnHuanHuo;
               /* $result = $this->orderReturnRepository->is_qualified($where, $params);//修改退货单状态和原因
                  if(!$result){
                      return ApiStatus::CODE_33008;//修改退货单信息失败
                  }
                  //修改商品状态
                  $goods_result = $this->orderReturnRepository->updategoods($where, $params['status']);
                  if (!$goods_result){
                      return ApiStatus::CODE_33009;//修改商品状态失败
                  }*/
               $delivery= Delivery::apply($order_no);//创建换货发货请求
                if(!$delivery){
                    return false;//发货失败
                }

            }

        }
        if($data['check_result'] == "false"){
            //寄回
            $delivery= Delivery::apply($order_no);//创建发货请求
            if(!$delivery){
                return false;//发货失败
            }

        }

         return ApiStatus::CODE_0;
    }
    /*
     * 申请退货->申请退款
     * $order_no
     */
    public function update_return_info($params){
        $data= filter_array($params,[
            'order_no'=>'required',
            'user_id'=>'required',
        ]);
        if(count($data)<2){
            return  ApiStatus::CODE_20001;
        }
        $order_result= $this->orderReturnRepository->get_order_info($params);//获取订单信息
        if(!$order_result){
            return ApiStatus::CODE_34005;//未找到此订单
        }
        if($order_result['order_status']!=OrderStatus::OrderInStock  || $order_result['order_status']!=OrderStatus::OrderPayed){
            return ApiStatus::CODE_34001;//此订单不符合规则
        }
       $data['business_key']=ReturnStatus::OrderTuiKuan;
       $data['order_no']=$order_result['order_no'];
       $data['user_id']=$order_result['user_id'];
       $data['pay_amount']=$order_result['order_amount']+$order_result['order_yajin']+$order_result['order_insurance'];//实际支付金额=订单实际总租金+押金+意外险
       $data['status']=ReturnStatus::ReturnCreated;
       $data['refund_no']=createNo('2');
       $data['create_time']=time();
       //创建申请退款记录
       $addresult= $this->orderReturnRepository->add($data);
       if(!$addresult){
           return ApiStatus::CODE_34003;//创建失败
       }
       // 修改冻结类型
       $freeze_result= $this->orderReturnRepository->update_freeze($params,$freeze_type=OrderFreezeStatus::Refund);
       if(!$freeze_result){
           return ApiStatus::CODE_33007;//修改冻结类型失败
       }
       $update_result= $this->orderReturnRepository->goods_update_status($params);//修改商品信息
       if(!$update_result){
           return  ApiStatus::CODE_33007;//修改商品状态失败
       }
        return ApiStatus::CODE_0;




    }
    //换货用户收货

    public function updateorder($params){
        if (empty($params['order_no']) && empty($params['goods_no'])){
            return ApiStatus::CODE_20001;//参数错误
        }
        $where[] = ['order_no', '=', $params['order_no']];
        $where[] = ['goods_no', '=', $params['goods_no']];
        $data['status']=ReturnStatus::ReturnHuanHuo;//已换货
        $result = $this->orderReturnRepository->is_qualified($where, $data);//修改退货单状态和原因
        if(!$result){
            return ApiStatus::CODE_33008;//修改退货单信息失败
        }
        //更新订单冻结状态
        $freeze_type=OrderFreezeStatus::Non;
        $freeze = $this->orderReturnRepository->update_freeze($params, $freeze_type);
        if (!$freeze){
            return ApiStatus::CODE_33007;//修改订单状态失败
        }
        //修改商品状态
        $goodsdata['goods_status']=ReturnStatus::ReturnHuanHuo;//已换货
        $goods_result = $this->orderReturnRepository->updategoods($where, $goodsdata);
        if (!$goods_result){
            return ApiStatus::CODE_33009;//修改商品状态失败
        }
        return ApiStatus::CODE_0;
    }
    //用户退货收货
    public function user_receive($params){
        if (empty($params['order_no']) && empty($params['goods_no'])){
            return ApiStatus::CODE_20001;//参数错误
        }
        $where[] = ['order_no', '=', $params['order_no']];
        $where[] = ['goods_no', '=', $params['goods_no']];
        $data['status']=ReturnStatus::ReturnReceive;//已收货
        $result = $this->orderReturnRepository->is_qualified($where,$data);//修改退货单状态和原因
        if(!$result){
            return ApiStatus::CODE_33008;//修改退货单信息失败
        }
        //修改商品状态
        $goodsdata['goods_status']=ReturnStatus::ReturnReceive;//已收货
        $goods_result = $this->orderReturnRepository->updategoods($where, $goodsdata);
        if (!$goods_result){
            return ApiStatus::CODE_33009;//修改商品状态失败
        }
        return ApiStatus::CODE_0;
    }
    //创建换货单记录
    public function createchange($params){
        if (isset($param['order_no']) && isset($param['good_id']) &&  isset($param['good_no']) &&  isset($param['serial_number'])){
           return ApiStatus::CODE_20001;//参数错误
        }
        $goods_result= $this->orderReturnRepository->createchange($params);
        if(!$goods_result){
            return ApiStatus::CODE_34009;//创建换货单记录失败
        }
        return ApiStatus::CODE_0;

    }
    //退款成功更新退款状态
    public function updateStatus($params){
        if(empty($params['order_no'])){
            return ApiStatus::CODE_20001;//参数错误
        }
        //修改退款单状态
        $return_result= $this->orderReturnRepository->updateStatus($params);
        if(!$return_result){
            return ApiStatus::CODE_33008;//修改退货单信息失败
        }
        //修改商品状态
        $goods_result= $this->orderReturnRepository->updategoodsStatus($params);
        if(!$goods_result){
            return ApiStatus::CODE_33009;//修改商品状态失败
        }
        //修改订单状态
        $order_result= $this->orderReturnRepository->updateorderStatus($params);
        if(!$order_result){
            return ApiStatus::CODE_33007;//修改订单状态失败
        }
        return ApiStatus::CODE_0;

    }

}