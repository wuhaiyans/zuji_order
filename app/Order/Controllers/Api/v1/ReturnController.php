<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\PublicFunc;
use App\Lib\Warehouse\Receive;
use App\Order\Modules\Inc\OrderStatus;
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
     * 申请退货
     * @param array $params 业务参数
     * [
     *      user_id用户id     必选
     *      business_key业务类型  必选
     *      loss_type商品损耗    必选
     *      reason_id退货原因id   必选
     *      reason_text退货原因   可选
     *      'goods_no'=>[]商品编号 必选
     * ]
     */
    public function returnApply(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $data= filter_array($params,[
            'user_id'=>'required',
            'business_key'=>'required',
            'loss_type'=>'required',
        ]);
        if(count($data)<3){
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
        if(!$return){
            return apiResponse([],ApiStatus::CODE_34007,"创建失败");
        }
        return apiResponse([],ApiStatus::CODE_0);
    }
    /*
     *
     *申请退款
     * 用户支付中，已支付使用
     * order_no订单编号  必选
     * user_id用户id      必选
     *
     */
    public function returnMoney(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $data= filter_array($params,[
            'order_no'=>'required',
            'user_id'=>'required',
        ]);
        if(count($data)<2){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $return = $this->OrderReturnCreater->createRefund($params);//修改信息
        if(!$return){
            return apiResponse([],ApiStatus::CODE_34007,"创建失败");
        }
        return apiResponse([],ApiStatus::CODE_0);

    }
    /**
     *   退换货审核
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 选中即同意
     * [
     *   'detail'=>[
     * [
     *      'refund_no'=>'',
     *      'remark'=>'',
     *      'reason_key'=>''
     *      'audit_state'=>''true 审核通过，false 审核不通过
     *    ],
     *   [
     *      'refund_no'=>'',
     *      'remark'=>'',
     *      'reason_key'=>''
     *      'audit_state'=>''
     *    ]
     * ]
     *   'business_key'=>''
     * ]
     *
     */

    public function returnReply(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        if(empty($params['business_key'])){
           return apiResponse([],ApiStatus::CODE_20001,"参数错误");
        }
        $res=$this->OrderReturnCreater->returnOfGoods($params);//审核同意
        if(!$res){
            return apiResponse([],ApiStatus::CODE_34007,"审核失败");
        }
        return apiResponse([],ApiStatus::CODE_0);

    }
    /**
     * 退款---审核
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
        $res= $this->OrderReturnCreater->refundApply($param);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_34007,"审核失败");
        }
        return apiResponse([],ApiStatus::CODE_0);
    }
    // 换货/退款记录列表接口
    /*
     * business_key 业务类型  必选
     *
     */
    public function returnList(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];

        $return_list = $this->OrderReturnCreater->get_list($params);
        return  apiResponse($return_list,ApiStatus::CODE_0,'success');

    }

    /**
     * 物流单号上传
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     *[
     *
     * logistics_id
     * logistics_name
     * logistics_no
     * user_id
     * refund_no=>[
     *            ''
     *            ''
     *           ]
     * ]
     */
    public function updateDeliveryNo(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'logistics_id'  => 'required',
            'logistics_name'  => 'required',
            'logistics_no'       =>'required',
            'user_id'             =>'required',
        ]);
        if(count($param)<4){
            return  apiResponse([],ApiStatus::CODE_20001);
        }

        if (empty($params['goods_info'])) {
            return apiResponse([],ApiStatus::CODE_20001);
        }
        $res= $this->OrderReturnCreater->uploadWuliu($params);
        if(!$res){
            return apiResponse([], ApiStatus::CODE_34002,'上传物流失败');
        }
        return apiResponse([], ApiStatus::CODE_0);

    }

    // 退货结果查看接口
    /*
     * [
     *   ['refund_no'=>''] 必选
     *   ['refund_no'=>''] 必选
     * ]
     *
     */
    public function returnResult(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $ret = $this->OrderReturnCreater->returnResult($params);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_34002);//未找到退货单信息
        }
        return apiResponse([$ret],ApiStatus::CODE_0);


    }

    /**
     * 取消退货申请
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     * 'refund_no'=>['111','222'] 退货单编号
     * user_id
     */
    public function cancelApply(Request $request)
    {
        $orders = $request->all();
        $params = $orders['params'];
        if(empty($params['refund_no'])){
            return apiResponse( [], ApiStatus::CODE_20001);
        }
        if(empty($params['user_id'])){
            return apiResponse( [], ApiStatus::CODE_20001);
        }
        $ret = $this->OrderReturnCreater->cancelApply($params);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_33004);//取消失败
        }
        return apiResponse( [], ApiStatus::CODE_0);
    }

    /**
     * 取消退款
     * @param Request $request
     */
    public function cancelRefund(Request $request){
        $orders = $request->all();
        $params = $orders['params'];
        if(empty($params['refund_no'])){
            return apiResponse( [], ApiStatus::CODE_20001);
        }
        if(empty($params['user_id'])){
            return apiResponse( [], ApiStatus::CODE_20001);
        }
        $ret = $this->OrderReturnCreater->cancelRefund($params);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_33002);//取消退款失败
        }
        return apiResponse( [], ApiStatus::CODE_0);
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
            'business_key'             =>'required',
        ]);
        if(count($param)<1){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if(empty($params['data'])){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->OrderReturnCreater->isQualified($param['business_key'],$params['data']);
        if(!$res){
            return  apiResponse([],ApiStatus::CODE_33008);//修改失败
        }
        return apiResponse([],ApiStatus::CODE_0);
    }

    /**
     * 换货用户收货通知
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * [
     * order_no
     * goods_info=>[
     *   goods_no=>''
     *  goods_no=>''
     * ]
     *
     *
     */
    public function updateOrder(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'           => 'required',
        ]);
        if(count($param)<1){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->OrderReturnCreater->updateorder($params);
        if(!$res){
            return  apiResponse([],ApiStatus::CODE_33008);//更新失败
        }
        return  apiResponse([],ApiStatus::CODE_0);
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
            'business_type'   =>'required',
            'business_no'     =>'required',
            'status'           =>'required',
            'order_no'        =>'required',
        ]);
        if(count($param)<4){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->OrderReturnCreater->refundUpdate($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_33008);//退款完成修改失败

        }
        return apiResponse([],ApiStatus::CODE_0);
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
        if($params['params']['business_key']!=OrderStatus::BUSINESS_RETURN){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        $params['params']['evaluation_status']=ReturnStatus::ReturnEvaluationFalse;
        $res=$this->OrderReturnCreater->returnCheckList($params['params']);
        return apiResponse($res,ApiStatus::CODE_0);
    }

    /**
     * 检测不合格拒绝退款
     * @param Request $request
     */
    public function refuseRefund(Request $request){
        $orders = $request->all();
        $params = $orders['params'];
        $res = $this->OrderReturnCreater->refuseRefund($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_33008);//拒绝退款失败
        }
         return apiResponse( [], ApiStatus::CODE_0);
    }


}
