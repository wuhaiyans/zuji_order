<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use App\Lib\OldInc;
use App\Lib\Warehouse\Receive;
use \App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use \App\Order\Modules\Inc\ReturnStatus;
use \App\Order\Modules\Inc\OrderStatus;
use \App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Repository\OrderReturnRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ThirdInterface;
use App\Order\Modules\Repository\OrderGoodsRepository;

class OrderReturnCreater
{

    protected $orderReturnRepository;
    protected $orderRepository;
    protected $orderGoodsRepository;
    public function __construct(orderReturnRepository $orderReturnRepository,orderRepository $orderRepository,orderGoodsRepository $orderGoodsRepository)
    {

        $this->orderReturnRepository = $orderReturnRepository;
        $this->orderRepository = $orderRepository;
        $this->orderGoodsRepository =$orderGoodsRepository;
    }
    public function get_return_info($data){
        return $this->orderReturnRepository->get_return_info($data);
    }
    //添加退换货数据
    public function add($data){
        $data['status']=ReturnStatus::ReturnCreated;
        $data['refund_no']=createNo('2');
        $data['create_time']=time();
        return $this->orderReturnRepository->add($data);
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
        if(empty($params['order_no'])){
            return ApiStatus::CODE_33003;
        }
        if(empty($params['remark'])){
            return ApiStatus::CODE_33005;
        }
        $res= $this->orderReturnRepository->update_return($params);

        if($res){
            if($this->orderReturnRepository->goods_update($params)) {
                //获取订单信息
                $data['orderNo']=$params['order_no'];
                $order_info= $this->orderRepository->getOrderInfo($data);
                //获取商品信息
                $goods_info= $this->orderReturnRepository->get_goods_info($params);
                if(!$goods_info){
                    return ApiStatus::CODE_40000;//商品信息错误
                }
                Receive::create($params['order_no'],$order_info[0]->business_key,$goods_info);//创建待收货单
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
        }else{
            return ApiStatus::CODE_33008;//更新审核状态失败
        }

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
        if($res){
            if($this->orderRepository->deny_update($params['order_no'])){
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
                $b = SmsApi::sendMessage('13020059043','hsb_sms_d284d',[
                    'realName' =>$order_info[0]->realname,
                    'orderNo' =>$order_info[0]->order_no,
                    'goodsName' => $goods_info[0]['good_name'],
                    'serviceTel'=>OldInc::Customer_Service_Phone,
                ],$order_info[0]->order_no);
                return ApiStatus::CODE_0;//成功
            }else{
                return ApiStatus::CODE_33007;//更新审核状态失败
            }
        }else{
            return ApiStatus::CODE_33008;//更新审核状态失败
        }

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

        if($res){
            $order = $this->orderRepository->deny_update($params['order_no']);
            if($order){
                //修改商品状态
                $order_goods = $this->orderReturnRepository->update_freeze($params);
                if($order_goods){
                    //修改订单冻结状态
                    if($this->orderReturnRepository->update_order_status($params)){
                        return ApiStatus::CODE_0;//成功
                    }

                }else{
                    return ApiStatus::CODE_33009;//const CODE_33008 = '33008'; //[退换货]修改退换货状态失败
                }
            }else{
                return ApiStatus::CODE_33007;//更新订单状态失败
            }

        }else{
            return ApiStatus::CODE_33008;//更新审核状态失败
        }
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

        if (isset($params['business_key']) > 0) {
            $where['business_key'] = intval($params['business_key']);
        }
        if (isset($params['keywords']) != '') {
            if (isset($params['kw_type']) == 'goods_name') {
                $where['goods_name'] = $params['keywords'];
            } elseif (isset($params['kw_type']) == 'order_no') {
                $where['order_no'] = $params['keywords'];
            } elseif (isset($params['kw_type']) == 'user_id') {
                $user_info = ThirdInterface::GetUser($params['keywords']);
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
        // 查询退货申请单
        $additional['page'] = $page;
        $additional['limit'] = $size;
        $where = $this->_parse_order_where($where);
        $data = $this->orderReturnRepository->get_list($where, $additional);
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
          'goods_name' => 'required',
          'order_no' => 'required|is_string',
          'status' => 'required|is_int',
          'begin_time'  => 'required',
          'end_time'    => 'required',
          'user_id' => 'required|is_id',
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
    public function is_qualified($params){
        if(empty($params['goods_no'])){
            return ApiStatus::CODE_40000;//商品信息错误
        }
        if(empty($params['order_no'])){
            return ApiStatus::CODE_33003;//订单编号不能为空
        }
        if(empty($params['status'])){
            return ApiStatus::CODE_34004;//退货状态不能为空
        }
        $result= $this->orderReturnRepository->is_qualified($params);
        if($result){
            $data['deposit_unfreeze_amount']=$result['price']-$result['insurance'];//退还押金金额=实际支付金额-意外险
            //创建记录
        }

    }
    /*
     * 申请退货->申请退款
     * $order_no
     */
    public function update_return_info($params){
        if(empty($params['order_no'])) {
            return ApiStatus::CODE_20001;
        }
        if(empty($params['user_id'])) {
            return ApiStatus::CODE_20001;
        }
        $order_result= $this->orderReturnRepository->get_order_info($params);//获取订单信息
        if($order_result){
           if($order_result['order_status']==OrderStatus::OrderInStock  || $order_result['order_status']==OrderStatus::OrderPayed){
               $result= $this->orderReturnRepository->update_return_info($params['order_no']);
               if($result){
                   // 修改冻结类型
                   $freeze_result= $this->orderReturnRepository->update_freeze($params,$freeze_type=OrderFreezeStatus::Refund);
                   if(!$freeze_result){
                       return ApiStatus::CODE_33007;//修改冻结类型失败
                   }
                   // $order_result= $this->orderReturnRepository->get_order_info($params);//获取订单信息
                   //  if($order_result){
                   $data['business_key']=ReturnStatus::OrderTuiKuan;
                   $data['order_no']=$order_result['order_no'];
                   $data['user_id']=$order_result['user_id'];
                   $data['pay_amount']=$order_result['order_amount']+$order_result['order_yajin']+$order_result['order_insurance'];//实际支付金额=订单实际总租金+押金+意外险
                   $data['refund_amount']=$data['pay_amount'];//应退退款金额
                   $data['status']=ReturnStatus::ReturnCreated;
                   $data['refund_no']=createNo('2');
                   $data['create_time']=time();
                   //创建申请退款记录
                   $addresult= $this->orderReturnRepository->add($data);
                   if($addresult){
                       $order_result= $this->orderReturnRepository->goods_update_status($params);//获取商品信息
                       return ApiStatus::CODE_0;
                   }else{
                       return ApiStatus::CODE_34003;//创建失败
                   }
               }else{
                   return ApiStatus::CODE_33009;//更新商品状态失败
               }
           }else{
                 return false;
           }
        }else{
            return ApiStatus::CODE_34005;//未找到此订单
        }


    }
    //申请退货--修改商品信息
    public function goods_update($params){
        if(empty($params['order_no'])) {
            return ApiStatus::CODE_33003;
        }
        if(empty($params['goods_no'])) {
            return ApiStatus::CODE_40000;
        }
        $result= $this->orderReturnRepository->goods_update_status($params);
        if($result){
            return ApiStatus::CODE_0;
        }else{
            return ApiStatus::CODE_33009;
        }
    }
}