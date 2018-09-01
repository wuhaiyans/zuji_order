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
	 * 确认订单接口
     * @author wuhaiyan
	 * @param Request $request
     * $request['appid']
     * [
     *      'appid'	=> '',	            //【必选】int 渠道入口
     * ]
     * $request['params']               //【注：】确认订单不再支持[支付方式]参数，支付方式默认商品的第一个
	 * [
	 *		'pay_channel_id'	=> '',	//【必选】int 支付支付渠道
	 *		'sku_info'	=> [	        //【必选】array	SKU信息
	 *			[
	 *				'sku_id' => '',		//【必选】int SKU ID
	 *				'sku_num' => '',	//【必选】int SKU 数量
     *              'begin_time'=>'',   //【短租必须】string 租用开始时间
     *              'end_time'=>'',     //【短租必须】string 租用结束时间
	 *			]
	 *		]',
	 *		'coupon'	=> [],	        //【可选】array 优惠券
     * $request['userinfo']             //【必须】array 用户信息  - 转发接口获取
     * $userinfo [
     *      'type'=>'',     //【必须】string 用户类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,   //【必须】string 用户ID
     *      'user_name'=>1, //【必须】string 用户名
     *      'mobile'=>1,    //【必须】string 手机号
     * ]
	 * @return \Illuminate\Http\JsonResponse
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

        if($userType!=2 && empty($userInfo)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户信息错误]");
        }
        if(empty($payChannelId) || !isset($payChannelId)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[支付渠道]");
        }
        if(count($sku)<1){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[商品]");
        }



        $data =[
            'appid'		=> $appid,
            'sku'		=> $sku,
            'coupon'	=> $coupon,
            'user_id'	=> $params['userinfo']['uid'],  //增加用户ID
            'pay_channel_id'=>$payChannelId,
        ];
        $res = $this->OrderCreate->confirmation( $data );
        if(!is_array($res)){
            return apiResponse([],ApiStatus::CODE_60000,get_msg());
        }
        return apiResponse($res,ApiStatus::CODE_0);

    }
    /**
     *  订单生成接口
     * @author wuhaiyan
     * @param Request $request
     * $request['appid']
     * [
     *      'appid'	=> '',	            //【必选】int 渠道入口
     * ]
     * $request['params']
     * [
     *		'pay_channel_id'	=> '',	//【必选】int 支付支付渠道
     *		'pay_type'	=> '',	        //【必选】int 支付方式
     *		'address_id'	=> '',	    //【必选】int 用户收货地址
     *		'sku_info'	=> [	        //【必选】array	SKU信息
     *			[
     *				'sku_id' => '',		//【必选】 int SKU ID
     *				'sku_num' => '',	//【必选】 int SKU 数量
     *              'begin_time'=>'',   //【短租必须】string 租用开始时间
     *              'end_time'=>'',     //【短租必须】string 租用结束时间
     *			]
     *		]',
     *		'coupon'	=> [],	//【可选】array 优惠券
     * $request['userinfo']     //【必须】array 用户信息  - 转发接口获取
     * $userinfo [
     *      'type'=>'',     //【必须】string 用户类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,   //【必须】string 用户ID
     *      'user_name'=>1, //【必须】string 用户名
     *      'mobile'=>1,    //【必须】string手机号
     * ]
     * @return \Illuminate\Http\JsonResponse
     */

    
    public function create(Request $request){

        $params = $request->all();

        //获取appid
        $appid		= $params['appid'];
        $payType	= isset($params['params']['pay_type'])?$params['params']['pay_type']:0;//支付方式ID
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
        if(empty($payType) || !isset($payType) || $payType <1){
            return apiResponse([],ApiStatus::CODE_20001,"支付方式错误");
        }
        if($userType!=2 && empty($userInfo)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户信息错误]");
        }
        if(empty($addressId) || !isset($addressId)){
            return apiResponse([],ApiStatus::CODE_20001,"addressId不能为空");
        }
        if(empty($payChannelId) || !isset($payChannelId)){
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
            LogApi::info("下单失败",get_msg());
            return apiResponse([],ApiStatus::CODE_30005,get_msg());

        }

        return apiResponse($res,ApiStatus::CODE_0);
    }
    /**
     * 门店下单接口
     * @param Request $request
     * $params[
     *      'pay_type'=>'',     //支付方式ID
     *      'address_id'=>'',   //收货地址ID
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
            LogApi::error('前端订单列表异常',$e);
            return apiResponse([],ApiStatus::CODE_50000);

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

        set_time_limit(0);
        $params = $request->all();
        $pageSize = 50000;
        if (isset($params['size']) && $params['size']>=50000) {
            $pageSize = 50000;
        } else {
            $pageSize = $params['size'];
        }
        $params['page'] = $params['page']?? 1;
        $outPages       = $params['page']?? 1;

        $total_export_count = $pageSize;
        $pre_count = $params['smallsize']?? 500;

        $smallPage = ceil($total_export_count/$pre_count);
        $abc = 1;

        // 租期，成色，颜色，容量，网络制式
        $headers = ['订单编号','下单时间','订单状态', '订单来源','支付方式及通道','用户名','手机号','详细地址','设备名称','租期', '商品价格属性',
            '订单实际总租金','订单总押金','意外险总金额'];

        $orderExcel = array();
        while(true) {
            if ($abc>$smallPage) {
                break;
            }
            $offset = ($outPages - 1) * $total_export_count;
            $params['page'] = intval(($offset / $pre_count)+ $abc) ;
            ++$abc;
            $orderData = array();
            $orderData = Service\OrderOperate::getOrderExportList($params,$pre_count);
            if ($orderData) {
                $data = array();
                foreach ($orderData as $item) {
                    $data[] = [
                        $item['order_no'],
                        date('Y-m-d H:i:s', $item['create_time']),
                        $item['order_status_name'],
                        $item['appid_name'],
                        $item['pay_type_name'],
//                        $item['visit_name'],
                        $item['name'],
                        $item['mobile'],
                        $item['address_info'],
                        implode(",",array_column($item['goodsInfo'],"goods_name")),
                        implode(",",array_column($item['goodsInfo'],"zuqi_name")),
                        implode(",",array_column($item['goodsInfo'],"specs")),
                        $item['order_amount'],
                        $item['order_yajin'],
                        $item['order_insurance'],
                    ];
                }

                $orderExcel =  Excel::csvWrite1($data,  $headers, '订单列表导出',$abc);

            } else {
                break;
            }
        }

        return $orderExcel;
        exit;



    }

    /**
     * 获取订单状态流
     * @author wuhaiyan
     * @param Request $request['params']
     *      [
     *          'order_no'=>'',//【必须】string 订单编号
     *      ]
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
     * @author wuhaiyan
     * @param Request $request['params']
     *      [
     *          'order_no'=>'',     //【必须】string 订单编号
     *          'goods_no'=>'',     //【必须】string 商品编号
     *          'remark'=>'',       //【必须】string 备注信息
     *          'type'=>'',         //【必须】int 类型 1出险 2取消出险
     *      ]
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
     * @author wuhaiyan
     * @param Request $request['params']
     *      [
     *          'order_no'=>'',     //【必须】string 订单编号
     *          'visit_id'=>'',     //【必须】int 联系备注ID
     *          'visit_text'=>'',   //【必须】string 备注信息
     *      ]
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
     * @author wuhaiyan
     * @param Request $request['params']
     *      [
     *          'order_no'=>'',     //【必须】string 订单编号
     *      ]
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
     * 获取乐百分单的分期信息
     * @author wuhaiyan
     * @param Request $request['params']
     *      [
     *          'order_no'=>'',     //【必须】string 订单编号
     *      ]
     * @return \Illuminate\Http\JsonResponse
     */

    public function getLebaifenInstalment(Request $request)
    {
        $params =$request->all();
        $params =$params['params'];
        if(empty($params['order_no'])){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res = OrderOperate::getLebaifenInstalment($params['order_no']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_60001,"获取订单信息失败");
        }
        return apiResponse($res,ApiStatus::CODE_0);

    }

    /**
     *  发货接口
     * @author wuhaiyan
     * @param Request $request['params']
     * @param $orderDetail array
     * [
     *  'order_no'=>'',         //【必须】string 订单编号
     *  'logistics_id'=>''      //【必须】int 物流渠道ID
     *  'logistics_no'=>''      //【必须】string 物流单号
     * ]
     * @param $goods_info       //【必须】 array 商品信息 参数内容如下
     * [
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     * ]
     * @param $operator_info //【必须】 array 操作人员信息
     * [
     *      'type'=>'',     //【必须】int 用户类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,   //【必须】int 用户ID
     *      'user_name'=>1, //【必须】string 用户名
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
     * @author wuhaiyan
     * @param Request $request
     * $params
     * [
     *  'order_no' =>'',//【必须】string 订单编号
     *  'remark'=>'',   //【必须】string 备注
     * ],
     * $userinfo [
     *      'type'=>'',     //【必须】int 用户类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,   //【必须】int 用户ID
     *      'user_name'=>1, //【必须】string 用户名
     *      'mobile'=>1,    //【必须】string 手机号
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
     * @author wuhaiyan
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
     * @author wuhaiyan
     * @param Request $request
     * $params[
     *   'order_no'  => '', //【必须】string 订单编号
     *   'remark'=>'',      //【必须】string 备注
     * ]
     * $userinfo [
     *      'type'=>'',     //【必须】int 用户类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,   //【必须】int用户ID
     *      'user_name'=>1, //【必须】string用户名
     *      'mobile'=>1,    //【必须】string手机号
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
        ];

        $validateParams = $this->validateParams($rule,  $params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $uid='';
        if(isset($params['userinfo'])){
            $userInfo = $params['userinfo'];
            //['uid']
        }

            $resonId = '';
        $resonText = '';
        if (isset($validateParams['data']['reason_id'])) {

            $resonId = $validateParams['data']['reason_id'];

         } else {

            if (isset($validateParams['data']['reason_text'])) {

                $resonText = $validateParams['data']['reason_text'];
            }

        }
        $code = Service\OrderOperate::cancelOrder($validateParams['data']['order_no'], $userInfo, $resonId, $resonText);

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
