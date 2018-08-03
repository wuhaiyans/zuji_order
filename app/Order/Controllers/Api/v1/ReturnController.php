<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\PublicFunc;
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
     * 用户收到货退货或换货时调用
     * 申请退货
     * @param array $params 业务参数
     * [
     *      'user_id'       =>''     用户id       int    【必选】
     *      'business_key'  =>''    业务类型      int    【必选】
     *      'reason_id'     =>''    退货原因id    int    【必选】
     *      'reason_text'   =>''    退货原因      string 【必选】
     *      'loss_type'     => '',  商品损耗      string 【必选】
     *      'order_no’     =>''    订单编号      string 【必选】
     *      'goods_no'      =>['','']商品编号     string 【必选】
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int    【必传】
     *      'username' =>''   用户名      string   【必传】
     *      'type'     =>''   渠道类型     int     【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     *
     * @return array
     * [
     *   'refund_no'   =>''  业务编号
     *   'goods_no’   =>''  商品编号
     *
     * ]
     */
    public function returnApply(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $data = filter_array($params,[
            'user_id'     =>'required',//用户id
            'business_key'=>'required',//业务类型
            'reason_id'   =>'required',//退换货原因id
            'reason_text' =>'required',    //退换或换货说明

        ]);
        if(count($data)<4){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        if(empty($params['goods_no'])){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        if(redisIncr($params['goods_no'].'_returnApply',60)>1) {
            return apiResponse([],ApiStatus::CODE_36001);
        }
        $return = $this->OrderReturnCreater->add($params,$orders['userinfo']);
        if(!$return){
            return apiResponse([],ApiStatus::CODE_34006,"申请失败");
        }
        return apiResponse($return,ApiStatus::CODE_0);
    }
    /*
     *
     *申请退款
     * 用户支付中，已支付使用
     * @params  array $params  业务参数
     * [
     *  ‘order_no’=>'' 订单编号  string 【必选】
     *   'user_id '  =>'' 用户id     int  【必选】
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''   用户id      int      【必传】
     *      'username' =>''   用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return array ['refund_no'=>'']  //业务编号
     */
    public function returnMoney(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $data = filter_array($params,[
            'order_no'=>'required', //订单编号
            'user_id' =>'required', //用户
        ]);
        if(count($data)<2){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $return = $this->OrderReturnCreater->createRefund($params,$orders['userinfo']);
        if(!$return){
            return apiResponse([],ApiStatus::CODE_34005,"取消订单失败");
        }
        return apiResponse($return,ApiStatus::CODE_0);

    }
    /**
     *   退换货审核
     * @param array  $params 业务参数
     * [
     *   'detail'=>[
     *     [
     *         'refund_no'  =>'',  业务编号  string 【必传】
     *         'remark'     =>'',  审核备注  string 【必传】
     *         'reason_key' =>''  审核原因id  int    【必传】
     *         'audit_state'=>''  true 审核通过，false 审核不通过  【必传】
     *    ],
     *    [
     *         'refund_no'   =>'',  业务编号  string 【必传】
     *         'remark'      =>'',  审核备注  string 【必传】
     *         'reason_key' =>''  审核原因id  int    【必传】
     *         'audit_state'=>''  true 审核通过，false 审核不通过  【必传】
     *    ],
     *   ]
     *  'business_key'  =>''    业务类型   int   【必传】
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return string
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
     *  [   'refund_no'  =>'',  业务编号  string  【必传】
     *      'remark'     =>'',  审核备注  string 【必传】
     *      'status'     =>''   审核状态 int    【必传】
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return string
     */
    public function refundReply(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'refund_no'=> 'required',    //业务编号
            'remark'   => 'required',    //审核备注
            'status'   => 'required',    //审核状态   0 同意，1拒绝
        ]);
        if(count($param)<3){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if($param['status'] != 0){
            return apiResponse([],ApiStatus::CODE_34009,"不支持退款审核拒绝");
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
        $params = $request->all();
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
        $params = $request->all();
        $refundData =$this->OrderReturnCreater->getReturnList($params);//获取退换货信息
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
        $params = $request->all();
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
     *[
     *
     * 'logistics_id'   =>'',  物流id   int    【必传】
     * 'logistics_name' =>'',  物流名称 string 【必传】
     * 'logistics_no'   =>'',  物流编号 string 【必传】
     * 'user_id'        =>'',  用户id   int    【必传】
     * 'goods_info'      =>['','']  业务编号(数组中值为业务编号即 refund_no) string 【必传】
     * ]
     * @return string
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
        if(strlen($params['logistics_no'])>20){
            return apiResponse([], ApiStatus::CODE_33003,'物流编号输入错误');
        }
        if(redisIncr($params['goods_info'][0].'_updateDeliveryNo',60)>1) {
            return apiResponse([],ApiStatus::CODE_36001);
        }
        $res= $this->OrderReturnCreater->uploadWuliu($params);
        if(!$res){
            return apiResponse([], ApiStatus::CODE_33003,'上传物流失败');
        }
        return apiResponse([], ApiStatus::CODE_0);

    }

    /*
     * 退货结果查看接口
     * @params   业务参数
     * [
     *    "business_key"   =>''   业务类型   int   【必选】
     *    "business_no"    =>''   业务编号   string【可选 】
     *    "goods_no"       =>''   商品编号   string【必选】
     * ]
     *@return array|string
     */
    public function returnResult(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'business_key'		=> 'required',
            'goods_no'		=> 'required',
        ]);
        if(count($param)<1){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if(redisIncr($params['goods_no'],'_returnResult',60)>1) {
            return apiResponse([],ApiStatus::CODE_36001);
        }
        $ret = $this->OrderReturnCreater->returnResult($params);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_33005);//退换货结果查看失败
        }
        return apiResponse($ret,ApiStatus::CODE_0);


    }

    /**
     * 取消退货申请
     * @param Request $request   业务参数
     * [
     *    'refund_no'=>['111','222'] 业务编号  string 【必传】
     *    'user_id'  =>''            用户id   int     【必传】
     * ]
     *
     * @param array $orders['userinfo'] 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return \Illuminate\Http\JsonResponse|string
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
        if(redisIncr($params['refund_no'],'_cancelApply',60)>1) {
            return apiResponse([],ApiStatus::CODE_36001);
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
     * @return  string
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
     * @param int	$params['params']['business_key']	$business_key	业务类型
     * @param array	 $params['params']['data']
     * [
     *		'business_no'       => '',    业务编码   string   【必传】
     *		'evaluation_remark' => '',    检测备注   string   【必传】
     *		'compensate_amount' => '',    检测金额   float    【必传】
     *      'evaluation_status' => '',    检测状态   int      【必传】 1检测合格  ，2检测不合格
     *		'evaluation_time'   => '',    检测时间   int     【必传】
     *      'goods_no'          =>''      商品编号   string  【必传】
     * ]
     * @param array $params['params']['userinfo'] 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     *
     * @return \Illuminate\Http\JsonResponse|string
     *
     */
    public function isQualified(Request $request){
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $paramsArr = isset($params['params']['data'])? $params['params']['data'] :'';
        foreach($paramsArr as $param){
            if(empty($param['goods_no'])
                || empty($param['evaluation_status'])
                || empty($param['evaluation_time'])
                ||empty($params['params']['business_key'])
                ||empty($param['business_no'])){
                return  apiResponse([],ApiStatus::CODE_20001);
            }
        }
        $operateUserInfo = isset($params['params']['userinfo'])? $params['params']['userinfo'] :'';
        LogApi::debug("检测获取用户信息",$operateUserInfo);
        if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
            return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
        }
        $res=$this->OrderReturnCreater->isQualified($params['params']['business_key'],$params['params']['data'],$params['params']['userinfo']);
        if(!$res){
			LogApi::error("检测结果保存失败",[
				'error' => \App\Lib\Common\Error::getError(),
				'request' => $params,
			]);
            return  apiResponse([],ApiStatus::CODE_33008,"修改失败");//修改检测结果失败
        }
        return apiResponse([],ApiStatus::CODE_0,'检测合格');
    }

    /**
     * 换货确认收货
     * @param Request $request
     * [
     *   'refund_no'   =>[
     *                   'refund_no'=>'', //业务编号   string  【必传】
     *                  'goods_no'  =>''  //商品编号   String  【必传】
     *                  ],
     *   'business_key'=>'',  //业务类型   int     【必传】
     * ]
     * @return string
     */
    public function returnReceive(Request $request){
        $orders = $request->all();
        $params = $orders['params'];
        if(empty($params['refund_no']) || empty($params['business_key'])){
            return apiResponse( [], ApiStatus::CODE_20001);
        }
        LogApi::debug("换货确认收货接受参数",$params);
        $res=$this->OrderReturnCreater->returnReceive($params);
        if(!$res){
            return  apiResponse([],ApiStatus::CODE_35009,"收货失败");//修改检测结果失败
        }
        return apiResponse([],ApiStatus::CODE_0,'收货成功');


    }

    /**
     * 换货用户收货通知
     * @param Request $request  $params['refund_no']  业务参数
     * ‘refund_no ’    =>'', //业务编号
     * @param array $orders['userinfo'] 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return  string
     *
     */
    public function updateOrder(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'refund_no'    => 'required',
        ]);
        if(count($param)<1){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res=OrderReturnCreater::updateorder($params['refund_no'],$orders['userinfo']);
        if(!$res){
            return  apiResponse([],ApiStatus::CODE_33009);//修改失败
        }
        return  apiResponse([],ApiStatus::CODE_0);
    }


    /**
     * 后台点击退换货审核弹出内容
     * @param Request $request  $params['params'] 业务参数
     *[
     *    'order_no'    =>''  订单编号    string   【必传】
     *    'business_key'=>''  业务编号    int      【必传】
     * ]
     * @return  array
     */
    public function returnApplyList(Request $request){
        $params = $request->all();
        $rules = [
            'order_no'      => 'required',
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
     * 获取订单检测不合格的数据
     * @param $params
     * [
     *     'order_no'          =>'', 订单编号   string   【必传】
     * ]
     *  @return  array
     */
    public function returnCheckList(Request $request){
        $params = $request->all();
        $rules = [
            'order_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params['params']['business_key']=OrderStatus::BUSINESS_RETURN;
        $params['params']['evaluation_status']=ReturnStatus::ReturnEvaluationFalse;
        $res=$this->OrderReturnCreater->returnCheckList($params['params']);
        return apiResponse($res,ApiStatus::CODE_0);
    }

    /**
     * 检测不合格拒绝退款
     * @param Request $request  $params   业务参数
     * [
     *   'refund_no'            =>'',  业务编号       string  【必传】
     *   'refuse_refund_remark' =>''   拒绝退款备注   string  【必传】
     *
     * ]
     * @return string
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
     * @param Request $request   $orders['params']  业务参数
     * [
     *    'order_no'   =>  ''   订单编号  string  【必传】
     *    'goods_no'   =>  ''   商品编号  string  【必传】
     * ]
     * @return  string
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
        $params = $orders['params'];
        $aa=OrderReturnCreater::refundUpdate($params,$orders['userinfo']);
        p($aa);

    }

}
