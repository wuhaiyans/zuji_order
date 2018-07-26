<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\JobQueueApi;
use App\Lib\Common\LogApi;
use App\Lib\Excel;
use App\Lib\Order\OrderInfo;
use App\Lib\User\User;
use App\Order\Models\OrderUserAddress;
use App\Order\Modules\Repository\OrderRiskRepository;
use App\Order\Modules\Repository\OrderUserAddressRepository;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use App\Order\Modules\Repository\Pay\Channel;
use App\Order\Modules\Service;
use Illuminate\Http\Request;
use App\Order\Models\OrderGoodExtend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Order\Modules\Service\OrderOperate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;




class OrderController extends Controller
{
    protected $OrderCreate;

    public function __construct(Service\OrderCreater $OrderCreate)
    {
        $this->OrderCreate = $OrderCreate;
    }

    /**
	 * 
	 * @param Request $request
	 * [
	 *		// 【注：】下单时不再支持[支付方式]参数，下单成功后，由用户选择
	 *		'pay_type'	=> '',	//【必选】string 支付方式
	 *		'sku_info'	=> [	//【必选】string	SKU信息
	 *			[
	 *				'sku_id' => '',		//【必选】SKU ID
	 *				'sku_num' => '',	//【必选】SKU 数量
	 *			]
	 *		]',
	 *		'coupon'	=> '',	//【可选】string 优惠券
	 * ]
	 * @return type
	 */
    public function confirmation(Request $request){
        $params = $request->all();

        //获取appid
        $appid		= $params['appid'];
        $sku		= $params['params']['sku_info'];
        $userInfo   = isset($params['userinfo'])?$params['userinfo']:[];
        $userType   = isset($params['userinfo']['type'])?$params['userinfo']['type']:0;

        $coupon		= isset($params['params']['coupon'])?$params['params']['coupon']:[];


        $payChannelId =$params['params']['pay_channel_id'];

        //判断参数是否设置
        if(empty($appid)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[appid]");
        }

//        if($userType!=2 && empty($userInfo)){
//            return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户信息错误]");
//        }
        if(empty($payChannelId)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[支付渠道]");
        }
        if(count($sku)<1){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[商品]");
        }



        $data =[
            'appid'		=> $appid,
            'sku'		=> $sku,
            'coupon'	=> $coupon,
            'user_id'	=> 18,//$params['userinfo']['uid'],  //增加用户ID
            'pay_channel_id'=>$payChannelId,
        ];
        $res = $this->OrderCreate->confirmation( $data );
        if(!is_array($res)){
            return apiResponse([],ApiStatus::CODE_60000,get_msg());
        }
        return apiResponse($res,ApiStatus::CODE_0);

    }
    /**
     * 下单接口
     * @param Request $request
     * $params[
     *      'pay_type'=>'',//支付方式ID
     *      'address_id'=>'',//收货地址ID
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request){

        $params = $request->all();

        //获取appid
        $appid		= $params['appid'];
        $payType	= $params['params']['pay_type'];//支付方式ID
        $sku		= $params['params']['sku_info'];
        $userInfo   = isset($params['userinfo'])?$params['userinfo']:[];
        $userType   = isset($params['userinfo']['type'])?$params['userinfo']['type']:0;

        $coupon		= isset($params['params']['coupon'])?$params['params']['coupon']:[];

        $addressId		= $params['params']['address_id'];

        $payChannelId =$params['params']['pay_channel_id'];

        //判断参数是否设置
        if(empty($appid)){
            return apiResponse([],ApiStatus::CODE_20001,"appid不能为空");
        }
        if(empty($payType)){
            return apiResponse([],ApiStatus::CODE_20001,"支付方式不能为空");
        }
        if($userType!=2 && empty($userInfo)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户信息错误]");
        }
        if(empty($addressId)){
            return apiResponse([],ApiStatus::CODE_20001,"addressId不能为空");
        }
        if(empty($payChannelId)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[支付渠道]");
        }
        if(count($sku)<1){
            return apiResponse([],ApiStatus::CODE_20001,"商品ID不能为空");
        }

        $data =[
            'appid'=>$appid,
            'pay_type'=>$payType,
            'address_id'=>$addressId,
            'sku'=>$sku,
            'coupon'=>$coupon,
            'user_id'=>$params['userinfo']['uid'],  //增加用户ID
            'pay_channel_id'=>$payChannelId,
        ];
        $res = $this->OrderCreate->create($data);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30005,get_msg());
        }

        return apiResponse($res,ApiStatus::CODE_0);
    }
    /**
     * 门店下单接口
     * @param Request $request
     * $params[
     *      'pay_type'=>'',//支付方式ID
     *      'address_id'=>'',//收货地址ID
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCreate(Request $request){
        $params = $request->all();

        //获取appid
        $appid		= $params['appid'];
        $payType	= $params['params']['pay_type'];//支付方式ID
        $sku		= $params['params']['sku_info'];
        $coupon		= $params['params']['coupon'];
        $userId		= $params['params']['user_id'];
        $addressId		= $params['params']['address_id'];

        //判断参数是否设置
        if(empty($appid)){
            return apiResponse([],ApiStatus::CODE_20001,"appid不能为空");
        }
        if(empty($payType)){
            return apiResponse([],ApiStatus::CODE_20001,"支付方式不能为空");
        }
        if(empty($userId)){
            return apiResponse([],ApiStatus::CODE_20001,"userId不能为空");
        }
        if(empty($addressId)){
            return apiResponse([],ApiStatus::CODE_20001,"addressId不能为空");
        }
        if(count($sku)<1){
            return apiResponse([],ApiStatus::CODE_20001,"商品ID不能为空");
        }

        $data =[
            'appid'=>$appid,
            'pay_type'=>$payType,
            'address_id'=>$addressId,
            'sku'=>$sku,
            'coupon'=>$coupon,
            'user_id'=>$userId,  //增加用户ID
        ];
        $res = $this->OrderCreate->storeCreate($data);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30005,get_msg());
        }

        return apiResponse($res,ApiStatus::CODE_0);
    }

    /**
     * 订单列表接口
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderList(Request $request){
        try{

            $params = $request->input('params');

            $orderData = Service\OrderOperate::getOrderList($params);

            if ($orderData['code']===ApiStatus::CODE_0) {

                return apiResponse($orderData['data'],ApiStatus::CODE_0);
            } else {

                return apiResponse([],ApiStatus::CODE_33001);
            }

        }catch (\Exception $e) {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }
    }



    /**
     * 客户端订单列表接口
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClientOrderList(Request $request){
		
        try{


            $params = $request->all();

            if (!isset($params['userinfo']) || empty($params['userinfo'])) {

                return apiResponse([], ApiStatus::CODE_10102,[],'用户id为空');

            }
//			\App\Lib\Common\LogApi::debug('客户端查询订单列表请求',$params);
            $orderData = Service\OrderOperate::getClientOrderList($params);

            if ($orderData['code']===ApiStatus::CODE_0) {
//				\App\Lib\Common\LogApi::debug('客户端查询订单列表结果',$orderData['data']);
                return apiResponse($orderData['data'],ApiStatus::CODE_0);
            } else {

                return apiResponse([],ApiStatus::CODE_33001);
            }

        }catch (\Exception $e) {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }
    }


    /**
     * 订单列表导出接口
     * Author: heaven
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function orderListExport(Request $request) {

        $params = $request->all();
        $params['page'] = 1;
        $params['size'] = 10000;
        $orderData = Service\OrderOperate::getOrderList($params);

        if ($orderData['code']===ApiStatus::CODE_0) {

            $headers = ['订单编号','下单时间','订单状态', '订单来源','支付方式及通道','回访标识','用户名','手机号','详细地址','设备名称',
                '订单实际总租金','订单总押金','意外险总金额'];

            foreach ($orderData['data']['data'] as $item) {
                $data[] = [
                    $item['order_no'],
                    date('Y-m-d H:i:s', $item['create_time']),
                    $item['order_status_name'],
                    $item['appid_name'],
                    $item['pay_type_name'],
                    $item['visit_name'],
                    $item['name'],
                    $item['mobile'],
                    $item['address_info'],
                    implode(",",array_column($item['goodsInfo'],"goods_name")),
                    $item['order_amount'],
                    $item['order_yajin'],
                    $item['order_insurance'],
                ];
            }


            return Excel::write($data, $headers,'后台订单列表数据导出');
//            return apiResponse($orderData['data'],ApiStatus::CODE_0);
        } else {

            return apiResponse([],ApiStatus::CODE_34007);
        }

    }

    /**
     * 获取订单状态流
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */


    public function getOrderStatus(Request $request)
    {
        $params =$request->all();
        $rules = [
            'order_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];

        $res = OrderOperate::getOrderStatus($params['order_no']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50000);
        }
        return apiResponse($res,ApiStatus::CODE_0);

    }

    /**
     *  增加订单出险/取消 记录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function addOrderInsurance(Request $request)
    {
        $params =$request->all();
        $rules = [
            'order_no'  => 'required',
            'goods_no' =>'required',
            'remark'=>'required',
            'type'=>'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];

        $res = OrderOperate::orderInsurance($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50000);
        }
        return apiResponse([],ApiStatus::CODE_0);

    }

    /**
     * 获取出险详情接口
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function outInsuranceDetail(Request $request)
    {

        $params =$request->all();
        $rules = [
            'order_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$validateParams['data'];

        $res = OrderOperate::getInsuranceInfo($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30036);
        }
        return apiResponse($res,ApiStatus::CODE_0);


    }



    /**
     *  增加联系备注
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function saveOrderVisit(Request $request)
    {
        $params =$request->all();
        $rules = [
            'order_no'  => 'required',
            'visit_id'=>'required',
            'visit_text'=>'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];

        $res = OrderOperate::orderVistSave($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50000);
        }
        return apiResponse([],ApiStatus::CODE_0);

    }

    /**
     * 获取订单操作日志
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function orderLog(Request $request)
    {
        $params =$request->all();
        $params =$params['params'];
        if(empty($params['order_no'])){
            return  apiResponse([],ApiStatus::CODE_20001);
        }

        $res = OrderOperate::orderLog($params['order_no']);
        if(!is_array($res)){
            return apiResponse([],ApiStatus::CODE_60001,"无订单日志");
        }
        return apiResponse($res,ApiStatus::CODE_0);

    }

    /**
     *  发货接口
     * @param $orderDetail array
     * [
     *  'order_no'=>'',//订单编号
     *  'logistics_id'=>''//物流渠道ID
     *  'logistics_no'=>''//物流单号
     * ]
     * @param $goods_info array 商品信息 【必须】 参数内容如下
     * [
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     * ]
     * @param $operator_info array 操作人员信息
     * [
     *      'type'=>发货类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,//用户ID
     *      'user_name'=>1,//用户名
     * ]
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function delivery(Request $request)
    {
        $params =$request->all();
        $params =$params['params'];
        if(count($params['order_info']) <3){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if(count($params['goods_info']) <1){

            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res = OrderOperate::delivery($params['order_info'],$params['goods_info'],$params['operator_info']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30014);
        }
        return apiResponse([],ApiStatus::CODE_0);
    }
    /**
     * 确认收货接口
     * @param Request $request
     * $params
     * [
     *  'order_no' =>'',//订单编号
     *  'remark'=>'',//备注
     * ]
     * @return \Illuminate\Http\JsonResponse
     */

    public function deliveryReceive(Request $request)
    {
        $params =$request->all();
        $userInfo =isset($params['userinfo'])?$params['userinfo']:[];
        $params =$params['params'];

        if(empty($params['order_no'])){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        $params['userinfo'] =$userInfo;

        $res = OrderOperate::deliveryReceive($params,0);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30012);
        }
        return apiResponse([],ApiStatus::CODE_0);
    }
    /**
     * 所有有关订单统计查询

     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function counted(Request $request){

        $params =$request->all();

        $res =Service\OrderOperate::counted();
        return apiResponse($res,ApiStatus::CODE_0);
        die;

    }

    /**
     * 确认订单接口
     * $params[
     *   'order_no'  => '',//订单编号
     *   'remark'=>'',//操作备注
     *  'userinfo'  //转发过来的信息
     * ]
     * $userinfo [
     *  'uid'=>'',
     *  'mobile'=>'',
     *  'type'=>'',
     *  'username'=>'',
     *
     * ]
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function confirmOrder(Request $request){

        $params =$request->all();
        $rules = [
            'order_no'  => 'required',
            'remark'=>'required',
        ];
        $userInfo =$params['userinfo'];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];
        $params['userinfo'] =$userInfo;
        $res =Service\OrderOperate::confirmOrder($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30011);
        }
        return apiResponse($res,ApiStatus::CODE_0);
    }

    /**
     * 未支付用户取消接口
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrder(Request $request)
    {

        $params = $request->all();
        $rule = [
            'order_no'=> 'required',
            'reason_id'=> 'required',
        ];

        $validateParams = $this->validateParams($rule,  $params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $uid='';
        if(isset($params['userinfo'])){
            $uid=$params['userinfo']['uid'];
        }
        $code = Service\OrderOperate::cancelOrder($validateParams['data']['order_no'], $uid, $validateParams['data']['reason_id']);

        return apiResponse([],$code);


    }


    /*
      *
      *
      * 发货后，更新物流单号
      *
      * */
    public function updateDelivery(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        if(empty($params['order_no'])){
            return ApiStatus::CODE_30005;//订单编码不能为空
        }
        if(empty($params['delivery_sn'])){
            return ApiStatus::CODE_30006;//物流单号不能为空
        }
        if(empty($params['delivery_type'])){
            return ApiStatus::CODE_30007;//物流渠道不能为空
        }
        $res = $this->OrderCreate->update($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_20001,"更新物流单号失败");
        }
        return apiResponse(['id'=>$res],ApiStatus::CODE_0,"success");

    }


    /**
     * 订单详情接口
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderInfo(Request $request)
    {
            try{
                $params = $request->all();
                $rule = [
                    'order_no'=> 'required'
                ];

                $validateParams = $this->validateParams($rule,  $params);


                if ($validateParams['code']!=0) {

                    return apiResponse([],$validateParams['code']);
                }


                $orderData = Service\OrderOperate::getOrderInfo($validateParams['data']['order_no']);
                if ($orderData['code']===ApiStatus::CODE_0) {

                    return apiResponse($orderData['data'],ApiStatus::CODE_0);
                } else {

                    return apiResponse([],ApiStatus::CODE_32002);
                }

            }catch (\Exception $e) {
                return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

            }


    }


    /**
     *
     * 订单列表过滤筛选列表接口
     * Author: heaven
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function orderListFilter()
    {

        $res = \App\Order\Modules\Inc\OrderListFiler::orderInc();
        return apiResponse($res,ApiStatus::CODE_0,"success");


    }


    /**
     * 修改收货地址信息
     * Author: heaven
     * @param Request $request
     */
    public function modifyAddress(Request $request)
    {

        $params = $request->all();
        $rule = [
            'order_no'=> 'required',
            'mobile'  => 'required',
            'name'=> 'required',
            'address_info'=> 'required',

        ];

        $validateParams = $this->validateParams($rule,  $params);
        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code'], $validateParams['msg']);
        }


        $succss = OrderUserAddressRepository::modifyAddress($validateParams['data']);
        if(!$succss){
            return apiResponse([],ApiStatus::CODE_30013);
        }
        return apiResponse([],ApiStatus::CODE_0);

    }


    /**
     * 获取风控信息
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRiskInfo(Request $request)
    {
        $params = $request->all();
        $rule = [
            'order_no'=> 'required'
        ];
        $validateParams = $this->validateParams($rule,  $params);

        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $orderData =OrderOperate::getOrderRisk($validateParams['data']['order_no']);

        return apiResponse($orderData,ApiStatus::CODE_0);

    }


    /**
     * 根据订单获取商品列表
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGoodsListByOrderNo(Request $request)
    {
        try{
            $params = $request->all();
            $rule = [
                'order_no'=> 'required'
            ];

            $validateParams = $this->validateParams($rule,  $params);


            if ($validateParams['code']!=0) {

                return apiResponse([],$validateParams['code']);
            }

            $goodsData = OrderOperate::getGoodsListByOrderNo($validateParams['data']['order_no']);

            if ($goodsData) {

                return apiResponse($goodsData,ApiStatus::CODE_0);
            } else {

                return apiResponse([],ApiStatus::CODE_50003);
            }

        }catch (\Exception $e) {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }

    }

    /**
     *
     *
     */
    public function getPayInfoByOrderNo(Request $request)
    {

        try{
            $params = $request->all();
            $rule = [
                'order_no'=> 'required'
            ];
            $validateParams = $this->validateParams($rule,  $params);


                if ($validateParams['code']!=0) {

                    return apiResponse([],$validateParams['code']);
                }

                $orderData = OrderOperate::getOrderinfoByOrderNo($validateParams['data']['order_no']);
                $payInfo = array();

                if ($orderData) {

                    if ($orderData['order_status']==1 || $orderData['order_status']==2) {
                        $orderParams = [
                            'payType' => $orderData['pay_type'],//支付方式 【必须】<br/>
                            'payChannelId' => Channel::Alipay,//支付渠道 【必须】<br/>
                            'userId' => $params['userinfo']['uid'],//业务用户ID<br/>
                            'fundauthAmount' => $orderData['order_yajin'],//Price 预授权金额，单位：元<br/>
                            'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,//Price 预授权金额，单位：元<br/>
                            'business_no' => $validateParams['data']['order_no'],//Price 预授权金额，单位：元<br/>
                        ];
                        $payInfo = OrderOperate::getPayStatus($orderParams);


                    }


                }
                return apiResponse($payInfo,ApiStatus::CODE_0);

            }catch (\Exception $e)
            {
                return apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
            }

    }





}
