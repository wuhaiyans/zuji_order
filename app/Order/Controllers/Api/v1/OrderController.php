<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\JobQueueApi;
use App\Lib\Order\OrderInfo;
use App\Order\Modules\Repository\OrderRiskRepository;
use App\Order\Modules\Repository\OrderUserInfoRepository;
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
        $payType	= $params['params']['pay_type'];//支付方式ID
        $sku		= $params['params']['sku_info'];
        $coupon		= $params['params']['coupon'];
        $userId		= $params['params']['user_id'];

        //判断参数是否设置
        if(empty($appid)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[appid]");
        }
        if(empty($payType)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[支付方式]");
        }
        if(empty($userId)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户标识]");
        }
        if(count($sku)<1){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[商品]");
        }

        $data =[
            'appid'		=> $appid,
            'pay_type'	=> $payType,
            'sku'		=> $sku,
            'coupon'	=> $coupon,
            'user_id'	=> $userId,  //增加用户ID
        ];
        $res = $this->OrderCreate->confirmation( $data );
        if(!is_array($res)){
            return apiResponse([],$res,ApiStatus::$errCodes[$res]);
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



    public function orderListExport() {


        header("Content-type:text/html;charset=utf-8");
        header("Content-Type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=test.xlsx");
        ob_end_clean();
//        header('Cache-Control: max-age=0');//禁止缓存
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        //                $writer->save('hello world.xlsx');
        $writer->save('php://output');

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
            return apiResponse([],ApiStatus::CODE_60001);
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
        $res = OrderOperate::delivery($params['order_info'],$params['goods_info']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30012);
        }
        return apiResponse([],ApiStatus::CODE_0);
    }
    /**
     *  确认收货接口
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function deliveryReceive(Request $request)
    {
        $params =$request->all();
        $params =$params['params'];

        if(empty($params['order_no'])){
            return apiResponse([],ApiStatus::CODE_20001);
        }

        $res = OrderOperate::deliveryReceive($params['order_no'],$params['role']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30012);
        }
        return apiResponse([],ApiStatus::CODE_0);
    }

    /**
     * 确认订单接口
     * $params[
     *   'order_no'  => '',//订单编号
     *   'remark'=>'',//操作备注
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
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];
        $res =Service\OrderOperate::confirmOrder($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30011);
        }
        return apiResponse($res,ApiStatus::CODE_0);
        die;




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
            'order_no'=> 'required'
        ];

        $validateParams = $this->validateParams($rule,  $params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $code = Service\OrderOperate::cancelOrder($validateParams['data']['order_no'], $params['user_id']=18);

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
            'order_address_id' => 'required',
            'order_no'=> 'required',
            'mobile'  => 'required',
            'name'=> 'required',
            'address_info'=> 'required',

        ];

        $validateParams = $this->validateParams($rule,  $params);
        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code'], $validateParams['msg']);
        }


        $succss = OrderUserInfoRepository::modifyAddress($validateParams['data']);
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

        try{
            $params = $request->all();
            $rule = [
                'order_no'=> 'required'
            ];

            $validateParams = $this->validateParams($rule,  $params);


            if ($validateParams['code']!=0) {

                return apiResponse([],$validateParams['code']);
            }

            $orderData = OrderRiskRepository::getRisknfoByOrderNo($validateParams['data']['order_no']);

            if ($orderData) {

                return apiResponse($orderData,ApiStatus::CODE_0);
            } else {

                return apiResponse([],ApiStatus::CODE_30034);
            }

        }catch (\Exception $e) {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }

    }



}
