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
     *order_no订单编号   必选
     * user_id用户id     必选
     * business_key业务类型  必选
     * loss_type商品损耗    必选
     * reason_id退货原因id   必选
     * reason_text退货原因   可选
     * goods_no=array('12312300','23123123')商品编号 必选
     */
    // 申请退换货接口
    public function returnApply(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $data= filter_array($params,[
            'order_no'=>'required',
            'user_id'=>'required',
            'business_key'=>'required',
            'loss_type'=>'required',
        ]);
        if(count($data)< 4){
            return ApiStatus::CODE_20001;
        }
        if(empty($params['goods_no'])){
            return ApiStatus::CODE_20001;
        }
        if($params['reason_id']){
            $params['reason_text'] = "";
        }
        if (empty($params['reason_id']) && empty($params['reason_text'])){
            return apiResponse([],ApiStatus::CODE_20001,"退换货原因不能为空");
        }
        //验证是全新未拆封还是已拆封已使用
        if ($params['loss_type']!=ReturnStatus::OrderGoodsNew && $params['loss_type']!=ReturnStatus::OrderGoodsIncomplete) {
            return apiResponse([],ApiStatus::CODE_20001,"商品损耗类型不能为空");
        }

        $return = $this->OrderReturnCreater->add($params);
        return apiResponse([],$return);

    }
    /*
     *
     *
     * 用户支付中，已支付使用
     * order_no订单编号  必选
     * user_id用户id      必选
     *
     */
    //申请退款
    public function returnMoney(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $data= filter_array($params,[
            'order_no'=>'required',
            'user_id'=>'required',
            'business_key'=>'required',
        ]);
        if(count($data)<3){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $return = $this->OrderReturnCreater->update_return_info($params);//修改信息
        return apiResponse([],$return);

    }
    /**
     *   退换货审核
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 选中即同意
     * [
     * order_no
     * 'agree'=>[
     *    ['goods_no'=>'','remark'=>'','reason_key'=>'']
     *    ['goods_no'=>'','remark'=>'','reason_key'=>'']
     * ]
     * 'disagree'=>[
     *    ['goods_no'=>'','remark'=>'','reason_key'=>'']
     *    ['goods_no'=>'','remark'=>'','reason_key'=>'']
     * ]
     *business_key
     * ]
     *
     */

    public function returnReply(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'=> 'required',
            'business_key'=> 'required',
        ]);
        if(count($param)<2){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if(isset($params['agree'])){
            $res=$this->OrderReturnCreater->agree_return($params);//审核同意
            if($res!=0){
                return  apiResponse([],$res);
            }

        }
        if(isset($params['disagree'])){
            $res=$this->OrderReturnCreater->deny_return($params);
            if($res!=0){
                return  apiResponse([],$res);
            }
        }
        return  apiResponse([],$res);;
    }
    /**
     * 订单退款审核
     * @param Request $request
     *
     */
    public function refundReply(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'=> 'required',
            'remark'=> 'required',
            'status'=> 'required',
        ]);
        if(count($param)<3){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if($params['status']==0){
            $res=$this->OrderReturnCreater->refundReplyAgree($params);//审核同意
        }else{
            $res=$this->OrderReturnCreater->refundReplyDisagree($params);//审核同意
        }
        return apiResponse([],$res);
    }
    // 退货/换货/退款记录列表接口
    /*
     * business_key 业务类型  必选
     *
     */
    public function returnList(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'business_key'=> 'required',
        ]);
        if(count($param)<1){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $return_list = $this->OrderReturnCreater->get_list($params);
        return  apiResponse($return_list,ApiStatus::CODE_0,'success');

    }

    // 退货物流单号上传接口
    /*
     * order_no订单编号  必选
     * logistics_id 物流类型  必选
     * logistics_name 物流名称 必选
     * logistics_no 物流编号  必选
     * user_id  用户id  必选
     * goods_no=array('sdada','adasdas')订单编号  可选
     *
     */
    public function returnDeliverNo(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'           => 'required',
            'logistics_id'  => 'required',
            'logistics_name'  => 'required',
            'logistics_no'       =>'required',
            'user_id'             =>'required',
        ]);
        if(count($param)<5){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if(empty($params['goods_no'])){
            return ApiStatus::CODE_20001;
        }
        //获取订单详情
        $order_info = $this->OrderCreate->get_order_detail($params);
        if(!$order_info){
            return apiResponse([], ApiStatus::CODE_20001,'未找到该订单');
        }
        //获取退货单信息
        $return_info = $this->OrderReturnCreater->get_info_by_order_no($params);
        if(!$return_info){
            return apiResponse([], ApiStatus::CODE_34002,'无退货单信息');
        }
        if($return_info[0]->user_id!=$params['user_id']){
           return apiResponse([], ApiStatus::CODE_20001,'非当前用户');
        }
        if($return_info[0]->business_key==ReturnStatus::OrderTuiHuo){
            if($return_info[0]->status != ReturnStatus::ReturnAgreed){
                return apiResponse([], ApiStatus::CODE_20001,'该订单未通过审核,不能上传物流单号');
            }
        }
        if($return_info[0]->logistics_no){
            return apiResponse([], ApiStatus::CODE_20001,'已上传物流单号');
        }
        $ret = $this->OrderReturnCreater->upload_wuliu($params);

        return apiResponse([], $ret);

    }

    // 退货结果查看接口
    /*
     * order_no  订单编号  必选
     * goods_no=array('3123123','21312312') 商品编号    必选
     */
    public function returnResult(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        if(empty($params['order_no'])){
            return apiResponse( [], ApiStatus::CODE_20001,'订单编号不能为空' );
        }
        if(empty($params['goods_no'])){
            return apiResponse( [], ApiStatus::CODE_20001,'商品编号不能为空' );
        }
        $ret = $this->OrderReturnCreater->returnResult($params);
        if($ret){
            return apiResponse([$ret],ApiStatus::CODE_0);
        }else{
            return apiResponse( [], $ret);
        }

    }

    /**
     * 取消退货申请
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function cancelApply(Request $request)
    {
        $orders = $request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no' => 'required',
            'user_id'  => 'required',
        ]);

        if(count($param)<2){
            return  ApiStatus::CODE_20001;
        }
        if(empty($params['goods_no'])){
            return ApiStatus::CODE_20001;
        }
        $ret = $this->OrderReturnCreater->cancel_apply($params);
        return apiResponse( [], $ret);
    }

    /**
     * 退换货结果检测
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function isQualified(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'           => 'required',
            'business_key'             =>'required',
        ]);
        if(count($param)<2){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->OrderReturnCreater->is_qualified($params['order_no'],$params['business_key'],$params['data']);
        return apiResponse([],$res);
    }
    /**
     * 客户发货后通知
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function userReceive(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'           => 'required',
            'goods_no'             =>'required',
        ]);
        if(count($param)<2){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->OrderReturnCreater->user_receive($params);
        return apiResponse([],$res);
    }
    /**
     * 换货用户收货通知
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'           => 'required',
            'goods_no'             =>'required',
        ]);
        if(count($param)<2){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->OrderReturnCreater->updateorder($params);
        return apiResponse([],$res);
    }


    /**
     * 退款成功更新退款状态
     * @param Request $request
     *
     */
    public function refundUpdate(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'business_type'           =>'required',
            'business_no'     =>'required',
            'status'     =>'required',
            'order_no'     =>'required',
        ]);
        if(count($param)<4){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->OrderReturnCreater->refundUpdate($params);
        return apiResponse([],$res);
    }

    /**
     * 换货
     * @param Request $request
     * [
     *   'order_no',
     *   'goods_no'=['sdfsda','12123123'],
     *
     * ]
     */
    public function exchangeGoods(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'=> 'required',
        ]);
        if(count($param)<1){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        if(empty($params['goods'])){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->OrderReturnCreater->exchangeGoods($params);
        return apiResponse([],$res);
    }


    /**
     *params[
     * 'order_no'
     * 'business_key'
     * ]
     * 后台点击退换货审核弹出内容
     */
    public function returnApplyList(Request $request){
        $params = $request->all();
        $rules = [
            'order_no'  => 'required',
            'business_key'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params['params']['status']=ReturnStatus::ReturnCreated;
        $res=$this->OrderReturnCreater->returnApplyList($params['params']);
        return apiResponse($res,ApiStatus::CODE_0);
    }
    /**
     *params[
     * 'order_no'
     * 'business_key'
     * ]
     * 获取订单检测不合格的数据
     */
    public function returnCheckList(Request $request){
        $params = $request->all();
        $rules = [
            'order_no'  => 'required',
            'business_key'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        if($params['params']['business_key']!=ReturnStatus::OrderTuiHuo){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        $params['params']['evaluation_status']=ReturnStatus::ReturnEvaluationFalse;
        $res=$this->OrderReturnCreater->returnCheckList($params['params']);
        return apiResponse($res,ApiStatus::CODE_0);
    }


}
