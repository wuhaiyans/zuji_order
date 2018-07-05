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
use App\Lib\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Lib\Common\LogApi;
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
            'reason_id'=>'required',
            'reason_text'=>'required',

        ]);
        if(count($data)<4){
            return ApiStatus::CODE_20001;
        }
        if(empty($params['goods_no'])){
            return ApiStatus::CODE_20001;
        }
        //验证是全新未拆封还是已拆封已使用
       // if ($params['loss_type']!=ReturnStatus::OrderGoodsNew && $params['loss_type']!=ReturnStatus::OrderGoodsIncomplete) {
         //   return apiResponse([],ApiStatus::CODE_20001,"商品损耗类型不能为空");
      //  }

        $return = $this->OrderReturnCreater->add($params,$orders['userinfo']);
        if(!$return){
            return apiResponse([],ApiStatus::CODE_34006,"创建退换货单失败");
        }
        return apiResponse($return,ApiStatus::CODE_0);
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
        $return = $this->OrderReturnCreater->createRefund($params,$orders['userinfo']);//修改信息
        if(!$return){
            return apiResponse([],ApiStatus::CODE_34005,"创建退款单失败");
        }
        return apiResponse($return,ApiStatus::CODE_0);

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
        $res=$this->OrderReturnCreater->returnOfGoods($params,$orders['userinfo']);//审核同意
        if(!$res){
            return apiResponse([],ApiStatus::CODE_33001,"退换货审核失败");
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
            'refund_no'=> 'required',
            'remark'=> 'required',
            'status'=> 'required',
        ]);
        if(count($param)<3){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res= $this->OrderReturnCreater->refundApply($param,$orders['userinfo']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_33002,"退款审核失败");
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
     * 退款列表导出
     * @param Request $request
     *
     */
    public function refundListExport(Request $request){
        $params = $request->input('params');
        $refundData =$this->OrderReturnCreater->getReturnList($params);
        $returnListArray = objectToArray($refundData);
        $data=[];
        if ($returnListArray['original']['code']===ApiStatus::CODE_0) {
            $headers = ['订单编号', '用户名', '申请退款时间', '下单时间', '完成交易时间', '实付金额', '应退金额', '退款状态', '物流信息', '订单状态'];
            if($returnListArray['original']['data']){
                foreach ($returnListArray['original']['data'] as $item) {
                    $data[] = [
                        $item['order_no'],
                        $item['mobile'],
                        date('Y-m-d H:i:s', $item['c_time']),
                        date('Y-m-d H:i:s', $item['create_time']),
                        date('Y-m-d H:i:s', $item['complete_time']),
                        $item['pay_amount'],
                        $item['refund_amount'],
                        $item['logistics_name'],
                        $item['order_status_name'],
                    ];
                }
            }else{
                $data[] = [];
            }

            return Excel::write($data, $headers, '后台退款列表数据导出');
        }else {
            return apiResponse([], ApiStatus::CODE_34007);
        }
    }
    /**
     * 退换货列表导出
     * @param Request $request
     *
     */
    public function returnListExport(Request $request){
        $params = $request->input('params');
        $refundData =$this->OrderReturnCreater->getReturnList($params);
        $returnListArray = objectToArray($refundData);
        $data=[];
        if ($returnListArray['original']['code']===ApiStatus::CODE_0) {
            $headers = ['订单编号', '用户名', '下单时间', '申请退款时间', '订单金额', '设备名称', '租期', '退换货状态', '订单状态', '类型'];
            if($returnListArray['original']['data']){
                foreach ($returnListArray['original']['data'] as $item) {
                    $data[] = [
                        $item['order_no'],
                        $item['mobile'],
                        date('Y-m-d H:i:s', $item['create_time']),
                        date('Y-m-d H:i:s', $item['c_time']),
                        $item['order_amount'],
                        $item['goods_name'],
                        $item['zuqi'],
                        $item['status_name'],
                        $item['order_status_name'],
                        $item['business_name']

                    ];
                }
            }else{
                $data[] = [];
            }

            return Excel::write($data, $headers, '后台退换货列表数据导出');
        }else {
            return apiResponse([], ApiStatus::CODE_34007);
        }
    }
    /**
     * 换货列表导出
     * @param Request $request
     *
     */
    public function barterListExport(Request $request){
        $params = $request->input('params');
        $refundData =$this->OrderReturnCreater->getReturnList($params);
        $returnListArray = objectToArray($refundData);
        $data=[];
        if ($returnListArray['original']['code']===ApiStatus::CODE_0) {
            $headers = ['订单编号', '用户名', '申请换货时间', '下单时间', '完成交易时间', '实付金额', '应付金额', '订单金额', '设备名称', '租期','换货状态',  '订单状态'];
            if($returnListArray['original']['data']){
                foreach ($returnListArray['original']['data'] as $item) {
                    $data[] = [
                        $item['order_no'],
                        $item['mobile'],
                        date('Y-m-d H:i:s', $item['c_time']),
                        date('Y-m-d H:i:s', $item['create_time']),
                        date('Y-m-d H:i:s', $item['complete_time']),
                        $item['pay_amount'],
                        $item['refund_amount'],
                        $item['order_amount'],
                        $item['goods_name'],
                        $item['zuqi'],
                        $item['status_name'],
                        $item['order_status_name'],

                    ];
                }
            }else{
                $data[] = [];
            }

            return Excel::write($data, $headers, '后台换货列表数据导出');
        }else {
            return apiResponse([], ApiStatus::CODE_34007);
        }
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
            'logistics_id'		=> 'required',
            'logistics_name'	=> 'required',
            'logistics_no'      => 'required',
            'user_id'           => 'required',
        ]);
        if(count($param)<4){
            return  apiResponse([],ApiStatus::CODE_20001);
        }

        if (empty($params['goods_info'])) {
            return apiResponse([],ApiStatus::CODE_20001);
        }
        $res= $this->OrderReturnCreater->uploadWuliu($params);
        if(!$res){
            return apiResponse([], ApiStatus::CODE_33003,'上传物流失败');
        }
        return apiResponse([], ApiStatus::CODE_0);

    }

    // 退货结果查看接口
    /*
     * [
     *    "business_key"  =>'' 必选  业务类型
     *   'goods_no'       =>''必选   商品编号
     *   'order_no'       =>''必选   订单编号
     * ]
     *
     */
    public function returnResult(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $ret = $this->OrderReturnCreater->returnResult($params);
		//\App\Lib\Common\LogApi::debug('退货结果',$ret);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_33005);//退换货结果查看失败
        }
        return apiResponse($ret,ApiStatus::CODE_0);


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
        $ret = $this->OrderReturnCreater->cancelApply($params,$orders['userinfo']);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_33004);//取消退换货失败
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
        $ret = $this->OrderReturnCreater->cancelRefund($params,$orders['userinfo']);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_33007);//取消退款失败
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
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $paramsArr = isset($params['params']['data'])? $params['params']['data'] :'';
        LogApi::debug("接收检测参数",$paramsArr);
        foreach($paramsArr as $param){
            if(empty($param['goods_no'])
                || empty($param['evaluation_status'])
                || empty($param['evaluation_time'])
                ||empty($params['params']['business_key'])){
                return  apiResponse([],ApiStatus::CODE_20001);
            }
        }
        $res=$this->OrderReturnCreater->isQualified($params['params']['business_key'],$params['params']['data']);
        if(!$res){
            return  apiResponse([],ApiStatus::CODE_33008,"修改失败");//修改检测结果失败
        }
        return apiResponse([],ApiStatus::CODE_0,'检测合格');
    }

    /**
     * 换货用户收货通知
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * [
     * ‘order_no ’    =>'', //订单编号
     * ‘goods_info’   =>[   //商品编号数组
     *      ‘goods_no’=>''  //商品编号
     *      ‘goods_no’=>''  //商品编号
     *     ] ，
     * ‘status’       =>''  //物流状态
     *
     *
     *
     *
     *
     */
    public function updateOrder(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'    => 'required',
        ]);
        if(count($param)<1){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if(empty($params['goods_info'])){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->OrderReturnCreater->updateorder($params,$orders['userinfo']);
        if(!$res){
            return  apiResponse([],ApiStatus::CODE_33009);//修改失败
        }
        return  apiResponse([],ApiStatus::CODE_0);
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
            return apiResponse([],ApiStatus::CODE_34004);//拒绝退款失败
        }
         return apiResponse( [], ApiStatus::CODE_0);
    }

    /**
     * 是否允许进入退换货
     * @param Request $request
     * [
     *   "order_no"  =>"", 【必选】订单编号
     *    ""
     * ]
     */
    public function allowReturn(Request $request){
        $orders = $request->all();
        $rules = [
            'order_no'  => 'required',
            'goods_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$orders);
        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $return=$this->OrderReturnCreater->allowReturn($orders['params']);
        if(!$return){
            return apiResponse([],ApiStatus::CODE_0);//允许进入售后和退换货
        }
        return apiResponse([],ApiStatus::CODE_34008);//不允许进入退换货

    }
    //test
    public function refundUpdate(Request $request){
        $orders = $request->all();
        $aa=$this->OrderReturnCreater->refundUpdate($orders['params'],$orders['userinfo']);
        p($aa);

    }

}
