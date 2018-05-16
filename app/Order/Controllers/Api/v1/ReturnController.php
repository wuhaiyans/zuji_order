<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\PublicFunc;
use App\Lib\Warehouse\Receive;
use App\Order\Modules\Inc\ReturnStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderReturnCreater;
use App\Order\Modules\Service\OrderCreater;
use App\Order\Modules\Repository\ThirdInterface;
class ReturnController extends Controller
{
    protected $OrderCreate;
    protected $OrderReturnCreater;
    public function __construct(OrderCreater $OrderCreate,OrderReturnCreater $OrderReturnCreater)
    {
        $this->OrderCreate = $OrderCreate;
        $this->OrderReturnCreater = $OrderReturnCreater;
    }
    /*
     * 用户收到货退货时调用
     *
     */
    // 申请退货接口
    public function return_apply(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $data= filter_array($params,[
            'order_no'=>'required',
            'goods_no'=>'required',
            'user_id'=>'required',
            'business_key'=>'required',
            'loss_type'=>'required',
        ]);
        if(count($data)< 5){
            return false;
        }
        if($params['reason_id']){
            $params['reason_text'] = "";
        }
        if (empty($params['reason_id']) && empty($params['reason_text'])){
            return apiResponse([],ApiStatus::CODE_20001,"退货原因不能为空");
        }
        //验证是全新未拆封还是已拆封已使用
        if ($params['loss_type']!=ReturnStatus::OrderGoodsNew && $params['loss_type']!=ReturnStatus::OrderGoodsIncomplete) {
            return apiResponse([],ApiStatus::CODE_20001,"商品损耗类型不能为空");
        }
        $res = $this->OrderCreate->get_order_info($params);//获取订单信息
        if(empty($res)){
            return apiResponse([],ApiStatus::CODE_20001,"没有找到该订单");
        }
        $return_info= $this->OrderReturnCreater->get_return_info($params);//获取退货单信息
        if($return_info){
           if($return_info[0]['status'] ==ReturnStatus::ReturnCreated) {
              return apiResponse([],ApiStatus::CODE_20001,"已提交退货申请,请等待审核");
           }
        }
        $goods_info= $this->OrderReturnCreater->get_goods_info($params['goods_no']);//获取商品信息

        if($goods_info){
           $params['pay_amount']=$goods_info['price'];//实际支付金额
        }
        $return = $this->OrderReturnCreater->add($params);
        return apiResponse([],$return,"success");

    }
    /*
     *
     *
     * 用户已付款，备货中时使用
     */
    //申请退款
    public function returnmoney(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $data= filter_array($params,[
            'order_no'=>'required',
            'user_id'=>'required',
        ]);
        if(count($data)<2){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $return = $this->OrderReturnCreater->update_return_info($params);//修改信息
        return apiResponse([],$return,"success");

    }


    // 退货记录列表接口
    public function returnList(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $return_list = $this->OrderReturnCreater->get_list($params);
         return  apiResponse($return_list,ApiStatus::CODE_0,'success');

    }

    // 退货物流单号上传接口
    public function returnDeliverNo(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $params = filter_array($params,[
            'order_no'          => 'required',
            'wuliu_channel_id'  => 'required',
            'logistics_no'          =>'required',
            'user_id'          =>'required',
        ]);
        if(count($params)<4){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        //获取订单详情
        $where['orderNo'] = $params['order_no'];
        $order_info = $this->OrderCreate->get_order_detail($where);
        //获取退货单信息
        $return_info = $this->OrderReturnCreater->get_info_by_order_no($params['order_no']);
        if(!$order_info){
            return apiResponse([], ApiStatus::CODE_20001,'未找到该订单');
        }
        if(!$return_info){
            return apiResponse([], ApiStatus::CODE_34002,'无退货单信息');
        }
        if($return_info['user_id']!=$params['user_id']){
           return apiResponse([], ApiStatus::CODE_20001,'非当前用户');
        }
        if($return_info[0]['status'] != '2'){
            return apiResponse([], ApiStatus::CODE_20001,'该订单未通过审核,不能上传物流单号');
        }
        if($return_info[0]['logistics_no']){
            return apiResponse([], ApiStatus::CODE_20001,'已上传物流单号');
        }
        //更新物流单号
        DB::beginTransaction();

        $ret = $this->OrderReturnCreater->upload_wuliu($params);
        if(!$ret) {
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_33008, '上传物流失败');
        }

        //创建收货单
        $create_res= Receive::apply($ret['order_no'],$ret['wuliu_channel_id'],$ret['logistics_no']);
        if(!$create_res){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_34003, '创建收货单失败');
        }

        return apiResponse([], ApiStatus::CODE_0,'success');

    }

    // 退货结果查看接口
    public function returnResult(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        if(empty($params['order_no'])){
            return apiResponse( [], ApiStatus::CODE_33003,'订单编号不能为空' );
        }
        if(empty($params['goods_no'])){
            return apiResponse( [], ApiStatus::CODE_40000,'商品编号不能为空' );
        }
        $ret = $this->OrderReturnCreater->returnResult($params);
        if($ret){
            return apiResponse($ret,ApiStatus::CODE_34001,"success");
        }else{
            return apiResponse( [], ApiStatus::CODE_34002,'退货记录没找到' );
        }

    }
    //取消退货申请
    public function cancel_apply(Request $request)
    {
        $orders = $request->all();
        $params = $orders['params'];
        $ret = $this->OrderReturnCreater->cancel_apply($params);
        return apiResponse($ret);


    }
    //退换货检测结果
    public function is_qualified(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $res=$this->OrderReturnCreater->is_qualified($params['order_no'],$params['business_key'],$params['data'][0]);
        return apiResponse([],$res);
    }
    /**
     * 换货用户收货通知
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateorder(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $res=$this->OrderReturnCreater->updateorder($params);
        return apiResponse([],$res);
    }
    //客户发货后通知
    public function user_receive(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $res=$this->OrderReturnCreater->user_receive($params);
        return apiResponse([],$res);
    }
    //创建换货记录
    public function createchange(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $res=$this->OrderReturnCreater->createchange($params);
        return apiResponse([],$res);
    }
    //退款成功更新退款状态
    public function updateStatus(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $res=$this->OrderReturnCreater->updateStatus($params);
        return apiResponse([],$res);
    }
    //审核
    public function return_reply(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        if(empty($params['status'])){
            return apiResponse([],ApiStatus::CODE_20001);//参数错误
        }
        if($params['status']=='1'){
            $res=$this->OrderReturnCreater->agree_return($params);//审核同意
        }else{
            $res=$this->OrderReturnCreater->deny_return($params);
        }
        return apiResponse([],$res);
    }


}
