<?php
/**
 *  芝麻小程序下单接口
 *   zhangjinhui
 *   date:2018-05-14
 */
namespace App\Order\Controllers\Api\v1;

use App\Lib\User\User;
use App\Lib\ApiStatus;
use App\Lib\Certification;
use Illuminate\Http\Request;
use App\Lib\Goods\Goods;
use Illuminate\Support\Facades\Redis;
use App\Order\Modules\Service;
use App\Lib\AlipaySdk\sdk\CommonMiniApi;
use App\Order\Modules\Inc\OrderStatus;
use Illuminate\Support\Facades\DB;
use App\Order\Modules\OrderCreater\RiskComponnet;
use App\Order\Modules\OrderCreater\AddressComponnet;
use App\Order\Modules\OrderCreater\ChannelComponnet;
use App\Order\Modules\OrderCreater\CouponComponnet;
use App\Order\Modules\OrderCreater\DepositComponnet;
use App\Order\Modules\OrderCreater\InstalmentComponnet;
use App\Order\Modules\OrderCreater\OrderComponnet;
use App\Order\Modules\OrderCreater\SkuComponnet;
use App\Order\Modules\OrderCreater\UserComponnet;
use App\Order\Modules\OrderCreater\WithholdingComponnet;

class MiniOrderController extends Controller
{
    protected $OrderCreate;

    public function __construct(Service\OrderCreater $OrderCreate)
    {
        $this->OrderCreate = $OrderCreate;
    }

    /**
     * 创建临时订单
     * @author: <zhangjinghui@huishoubao.com.cn>
     *
     *  参数
     *      sku_id      子商品ID
     */
    public function getTemporaryOrderNo(Request $request){
        $params     = $request->all();
        // 验证参数
        $rules = [
            'sku_id' => 'required', //【必须】int；子商品ID
            'sku_num' => 'required', //【必须】int；子商品ID
        ];
        $validateParams = $this->validateParams($rules,$params['params']);
        if ( $validateParams['code']!=0 ) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];
        //开启事务
        DB::beginTransaction();
        try{
            //获取订单号
            $orderNo = \App\Order\Modules\Service\OrderOperate::createOrderNo(1);
            //获取商品信息
            $goods_info = \App\Lib\Goods\Goods::getSku([$params['sku_id']]);
            $sku_info = $goods_info[$params['sku_id']]['sku_info'];
            $spu_info = $goods_info[$params['sku_id']]['spu_info'];
            //商品件数固定为1
            $count = 1;
            $name = $sku_info['sku_name'];
            if( $sku_info['zuqi_type'] == 2 ){//租期类型（1：天；2：月）
                //长租
                $new_data = date('Y-m-d H:i:s');
                $overdue_time = date('Y-m-d H:i:s', strtotime($new_data.' +'.(intval($sku_info['zuqi'])+1).' month'));
                //总租金
                $total_amount = normalizeNum($sku_info['shop_price']*intval($sku_info['zuqi'])+$spu_info['yiwaixian']);
                //单月租金
                $single_amount = $sku_info['shop_price'];
                //总押金
                $deposit = $sku_info['yajin'];
                //分期数 短租为1期
                $installmentCount = intval($sku_info['zuqi']);
            }else{
                //短租
                $new_data = date('Y-m-d H:i:s');
                $overdue_time = date('Y-m-d H:i:s', strtotime($new_data.' +'.(intval($sku_info['zuqi'])+30).' day'));
                //总租金
                $total_amount = normalizeNum($sku_info['shop_price']*intval($sku_info['zuqi'])+$spu_info['yiwaixian']);
                //单月租金
                $single_amount = $sku_info['shop_price'];
                //总押金
                $deposit = $sku_info['yajin'];
                //分期数 短租为1期
                $installmentCount = 1;
            }
            //判断商品类目来调取芝麻小程序类目
            if(empty(config('minicategory.'.$spu_info['catid']))){
                return apiResponse([],ApiStatus::CODE_35021,'小程序获取芝麻商品类目失败');
            }
            $data = [
                'order_no' => $orderNo,
                'sku' => [$params],
                'goods_info' => [
                    'sku_id'=>$sku_info['sku_id'],
                    'spu_id'=>$sku_info['spu_id'],
                    'total_amount'=> normalizeNum($sku_info['shop_price']*intval($sku_info['zuqi'])),
                ],
                'overdue_time' => $overdue_time,
                'zhima_params'=>[
                    'amount'=>$total_amount,
                    'deposit'=>$deposit,
                    'out_order_no'=>$orderNo,
                    'overdue_time' => $overdue_time,
                    'category' => config('minicategory.'.$spu_info['catid']),
                    'products'=>[
                        'count'=>$count,
                        'amount'=>$single_amount,
                        'deposit'=>$deposit,
                        'installmentCount'=>$installmentCount,
                        'name'=>$name,
                    ],
                ],
            ];
            //redis 存储数据
            $values = Redis::set('dev:zuji:order:miniorder:temporaryorderno:'.$orderNo, json_encode($data));
            if(!$values){
                //回滚事务
                DB::rollBack();
                return apiResponse([],ApiStatus::CODE_35001,'保存临时订单号失败');
            }
            //存储数据日志
            $result = \App\Order\Modules\Repository\OrderMiniCreditRentRepository::add([
                'order_no'=>$orderNo,
                'data'=>json_encode($data),
            ]);
            if( !$result ){
                //回滚事务
                DB::rollBack();
                \App\Lib\Common\LogApi::debug('小程序临时订单号请求记录失败',$result);
            }
        } catch (\Exception $ex) {
            //回滚事务
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_35000, $ex->getMessage());
        }
        //提交事务
        DB::commit();
        //返回订单号
        return apiResponse($data,ApiStatus::CODE_0,'临时订单号创建成功');
    }


    /**
     * 订单确认（查询订单信息 获取免押金额）
     */
    public function confirmationQuery(Request $request){
        $params     = $request->all();
        // 验证参数
        $rules = [
            'zm_order_no' => 'required', //【必须】string；芝麻订单号
            'out_order_no' => 'required', //【必须】string；业务订单号
            'pay_type' => 'required', //【必须】string；支付方式id
        ];
        $validateParams = $this->validateParams($rules,$params['params']);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code'],$validateParams['msg']);
        }
		// 当前会话
        if ( !isset($params['auth_token']) && strlen( $params['auth_token'] ) ) {
			\App\Lib\Common\LogApi::error('无会话标识', $params);
            return apiResponse([],ApiStatus::CODE_50000,'请求失败，请稍候重试');
        }
		
        $param = $params['params'];
        //开启事务
        DB::beginTransaction();
        try{
            //判断支付状态
            if($param['pay_type'] != \App\Order\Modules\Inc\PayInc::MiniAlipay){
                return apiResponse([],ApiStatus::CODE_50005,'小程序支付状态错误');
            }
            //判断当前是否有临时订单
            $data = Redis::get('dev:zuji:order:miniorder:temporaryorderno:'.$param['out_order_no']);
            if(!$data){
                \App\Lib\Common\LogApi::error('小程序临时订单不存在',$data);
                return apiResponse([], ApiStatus::CODE_35010,'业务临时订单不存在');
            }
            $data = json_decode($data,true);
            $data['pay_type'] = $param['pay_type'];
            $data['appid'] = $params['appid'];
            $data['coupon'] = isset($param['coupon'])?$param['coupon']:[];
            //判断APPid是否有映射
            if(empty(config('miniappid.'.$data['appid']))){
                return apiResponse([],ApiStatus::CODE_35011,'匹配小程序appid错误');
            }
            //查询芝麻订单确认结果
            $miniApi = new CommonMiniApi(config('miniappid.'.$data['appid']));
            //获取请求流水号（用户是否请求过）
            //确认订单查询（芝麻小程序数据）
            $miniOrderInfo = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo($param['zm_order_no']);
            if($miniOrderInfo){
                $transactionNo = $miniOrderInfo['transaction_id'];
            }else{
                $transactionNo = \App\Order\Modules\Service\OrderOperate::createOrderNo(1);
            }
            //添加逾期时间
            $miniParams = [
                'transaction_id'=>$transactionNo,
                'order_no'=>$param['zm_order_no'],
                'out_order_no'=>$param['out_order_no'],
                'overdue_time'=>$data['overdue_time'],
            ];
            $b = $miniApi->orderConfirm($miniParams);
            if($b === false){
                \App\Lib\Common\LogApi::error('芝麻接口请求错误：'.$miniApi->getError(),$miniParams);
				return apiResponse([],ApiStatus::CODE_50000,'服务器超时，请稍候重试');
            }
            $miniData = $miniApi->getResult();
			\App\Lib\Common\LogApi::info('芝麻订单认证结果-'.$param['zm_order_no'],$miniData);
			
            //用户处理
            $_user = \App\Lib\User\User::getUserId($miniData, $params['auth_token']);
			if( !$_user ){
                \App\Lib\Common\LogApi::error('芝麻确认订单，获取用户失败',$miniData);
				return apiResponse([],ApiStatus::CODE_50000,'服务器超时，请稍候重试');
			}
			\App\Lib\Common\LogApi::info('当前登录用户信息',$_user);
            $data['user_id'] = $_user['user_id'];
            $miniData['member_id'] = $_user['user_id'];
            if( empty($data['coupon']) ){//前端无传输coupon
                //小程序自动领取优惠券
                $drawCouponArr = [
                    'only_id'=>\App\Lib\Coupon\Coupon::$coupon_only,
                    'user_id'=>$data['user_id'],
                ];
                \App\Lib\Coupon\Coupon::drawCoupon($drawCouponArr);
                $queryCouponArr = [
                    'spu_id'=>$data['goods_info']['spu_id'],
                    'sku_id'=>$data['goods_info']['sku_id'],
                    'user_id'=>$data['user_id'],
                    'payment'=>$data['goods_info']['total_amount'],
                ];
                $queryCoupon = \App\Lib\Coupon\Coupon::queryCoupon($queryCouponArr);
                if( isset($queryCoupon[0]['coupon_no']) ){//查询优惠券是否存在
                    $data['coupon'] = [
                        $queryCoupon[0]['coupon_no']
                    ];
                }
            }
            //风控系统处理
            $b = \App\Lib\Risk\Risk::setMiniRisk($miniData,$params['auth_token'],$params['appid']);
            if($b != true){
				return apiResponse([],ApiStatus::CODE_50000,'服务器超时，请稍候重试');
                //return apiResponse( [], ApiStatus::CODE_35008, '风控系统接口请求错误');
            }
            //处理用户收货地址
            $address = \App\Lib\User\User::getAddressId([
                'house'=>$miniData['house'],
                'user_id'=>$_user['user_id'],
                'name'=>$miniData['name'],
                'mobile'=>$miniData['mobile'],
            ]);
            if(!$address){
                \App\Lib\Common\LogApi::error('芝麻小程序收货地址设置错误',$miniData);
				return apiResponse([],ApiStatus::CODE_50000,'服务器超时，请稍候重试');
                //return apiResponse([],ApiStatus::CODE_35013,'老系统收货地址信息处理失败');
            }
            $data['mobile']=$miniData['mobile'];
            $data['name']=$miniData['name'];
            $data['address']=$miniData['house'];
            $data['address_id']=$address['address_id'];
            $data['credit_amount']=$miniData['credit_amount'];
            //小程序订单确认
            $res = $this->OrderCreate->miniConfirmation($data);
            if(!$res){
                //回滚事务
                DB::rollBack();
                \App\Lib\Common\LogApi::error('芝麻确认订单失败',$data);
				return apiResponse([],ApiStatus::CODE_50000,'服务器超时，请稍候重试');
                //return apiResponse([],ApiStatus::CODE_35012,get_msg());
            }
			\App\Lib\Common\LogApi::debug('芝麻小程序确认订单结果',$res);
        } catch (\Exception $ex) {
            //回滚事务
            DB::rollBack();
			\App\Lib\Common\LogApi::error('芝麻确认订单异常',$ex);
			return apiResponse([],ApiStatus::CODE_50000,'服务器超时，请稍候重试');
            //return apiResponse([], ApiStatus::CODE_35000, $ex->getMessage());
        }
        //提交事务
        DB::commit();
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
        $orderNo	= $params['params']['order_no'];
        $payType	= $params['params']['pay_type'];//支付方式ID
        $sku		= $params['params']['sku_info'];
        $coupon		= isset($params['params']['coupon'])?$params['params']['coupon']:[];
        $userId		= $params['params']['user_id'];
        $address_id		= $params['params']['address_id'];

        //判断参数是否设置
        if(empty($appid)){
            return apiResponse([],ApiStatus::CODE_20001,"appid不能为空");
        }
        if(empty($orderNo)){
            return apiResponse([],ApiStatus::CODE_20001,"orderNo不能为空");
        }
        if(empty($payType)){
            return apiResponse([],ApiStatus::CODE_20001,"支付方式不能为空");
        }
        if(empty($userId)){
            return apiResponse([],ApiStatus::CODE_20001,"userId不能为空");
        }
        if(empty($address_id)){
            return apiResponse([],ApiStatus::CODE_20001,"address_id不能为空");
        }
        if(empty($sku)){
            return apiResponse([],ApiStatus::CODE_20001,"商品ID不能为空");
        }

        //开启事务
        DB::beginTransaction();
        try{
            //确认订单查询（芝麻小程序数据）
            $res = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo($orderNo);
            if(!$res){
                return apiResponse([],ApiStatus::CODE_35002,"小程序订单查询失败");
            }
            $data = [
                'appid'=>$appid,
                'pay_type'=>$payType,
                'order_no'=>$orderNo,
                'sku'=>$sku,
                'coupon'=>$coupon,
                'user_id'=>$userId,
                'address_id'=>$address_id,
                'name'=>$res['name'],
                'mobile'=>$res['mobile'],
                'credit_amount'=>$res['credit_amount'],
                'cert_no'=>$res['cert_no'],
            ];
            $res = $this->OrderCreate->miniCreate($data);
            if($res == false){
                //回滚事务
                DB::rollBack();
                if(get_msg() == ApiStatus::CODE_35017){
                    return apiResponse([],ApiStatus::CODE_35017,'有未完成订单');
                }
                return apiResponse([],ApiStatus::CODE_30005,get_msg());
            }

            return apiResponse($res,ApiStatus::CODE_0);
        }catch(\Exception $e){
            //回滚事务
            DB::rollBack();
            \App\Lib\Common\LogApi::error('芝麻创建订单异常',$e);
            return apiResponse([],ApiStatus::CODE_50000,'服务器超时，请稍候重试');
            //return apiResponse([], ApiStatus::CODE_35000, $ex->getMessage());
        }

    }

    /**
     * 前段确认订单同步通知接口
     * 获取订单详细信息
     */
    public function frontTransition(Request $request){
        $params     = $request->all();
        \App\Lib\Common\LogApi::info('芝麻小程序确认订单同步通知参数',$params);
        // 验证参数
        $rules = [
            'zm_order_no' => 'required', //【必须】string；芝麻订单号
            'out_order_no' => 'required', //【必须】string；业务订单号
            'order_status' => 'required', //【必须】string；当前订单状态
        ];
        $validateParams = $this->validateParams($rules,$params['params']);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code'],$validateParams['msg']);
        }
        $param = $params['params'];
        // 验签 验证 通过 修改数据
        if($param['order_status'] == 'SUCCESS'){
            return apiResponse( [], ApiStatus::CODE_0);
        }else if($param['order_status'] == 'FAIL'){
            return apiResponse( [], ApiStatus::CODE_35019);
        }else if($param['order_status'] == 'UNKOWN'){
            return apiResponse( [], ApiStatus::CODE_35020,'小程序同步接口状态错误UNKOWN');
        }
    }

    /**
     * 小程序获取用户最新下单订单信息+商品信息接口
     * @return array
     */
    public function getOrderNo(Request $request){
        $params = $request->all();
        $rules = [
            'uid'  => 'required',
            'username'  => 'required',
            'type'  => 'required',
        ];
        //验证用户数据是存在
        $validateParams = $this->validateParams($rules,$params['userinfo']);
        $userinfo = $params['userinfo'];
        if (empty($validateParams) || $validateParams['code']!=0) {
            return apiResponse([],$validateParams['code'],$validateParams['msg']);
        }
        //查询用户最新订单
        $Info = \App\Order\Modules\Repository\OrderRepository::getUserNewOrder( $userinfo['uid'] ,$params['appid'] );
        if( empty($Info) ){
            \App\Lib\Common\LogApi::info('本地小程序查询用户订单不存在（或无激活订单）',$userinfo['uid']);
            return apiResponse([],ApiStatus::CODE_0,'本地小程序查询用户订单不存在（或无激活订单）');
        }
        return apiResponse($Info,ApiStatus::CODE_0);
    }

    /**
     * 小程序取消订单（解冻预授权 解约代扣）
     * $params = [
     *   'order_no'=>'',
     *   'remark'=>'',
     * ]
     */
    public function orderCancel(Request $request){
        $params = $request->all();
        $rules = [
            'order_no'  => 'required',
            'remark_id'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params['params']);
        $param = $params['params'];
        //开启事务
        DB::beginTransaction();
        try {
            if (empty($validateParams) || $validateParams['code'] != 0) {
                return apiResponse([], $validateParams['code'], $validateParams['msg']);
            }
            //查询芝麻订单
            $miniOrderInfo = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo($param['order_no']);
            $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById($param['order_no']);
            if (empty($miniOrderInfo)) {
                \App\Lib\Common\LogApi::info('本地小程序查询芝麻订单信息表失败', $param['order_no']);
                return apiResponse([], ApiStatus::CODE_35003, '本地小程序查询芝麻订单信息表失败');
            }
            $remark = \App\Order\Modules\Inc\OrderStatus::getOrderCancelResasonName($param['remark_id']);
            //发送取消请求
            $data = [
                'out_order_no' => $miniOrderInfo['order_no'],//商户端订单号
                'zm_order_no' => $miniOrderInfo['zm_order_no'],//芝麻订单号
                'remark' => $remark,//订单操作说明
                'app_id' => $miniOrderInfo['app_id'],//小程序appid
            ];
            $b = \App\Lib\Payment\mini\MiniApi::OrderCancel($data);
            if ($b === false) {
                //回滚事务
                DB::rollBack();
                return apiResponse(['reason' => \App\Lib\Payment\mini\MiniApi::getError()], ApiStatus::CODE_35005);
            }
            //取消订单修改订单状态
            $code = \App\Order\Modules\Service\OrderOperate::cancelOrder($miniOrderInfo['order_no'], $orderInfo['user_id']);
            if ($code != ApiStatus::CODE_0) {
                //回滚事务
                DB::rollBack();
                \App\Lib\Common\LogApi::debug('小程序取消商户端订单失败', $orderInfo);
                return apiResponse([], $code, '小程序取消商户端订单失败');
            }
            //用户取消订单写入日志系统
            \App\Order\Modules\Repository\OrderLogRepository::add(
                $orderInfo['user_id'],
                $orderInfo['mobile'],
                \App\Lib\PublicInc::Type_User,
                $miniOrderInfo['order_no'],
                "取消订单",
                $remark
            );
            //提交事务
            DB::commit();
            return apiResponse([],ApiStatus::CODE_0,'取消小程序订单成功');
        } catch  (\Exception $ex) {
            //回滚事务
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_35000, $ex->getMessage());
        }
    }

    /**
     * 小程序查询订单支付状态
     * $params = [
     *   'order_no'=>'',
     * ]
     */
    public function orderMiniPayStatus(Request $request){
        $params = $request->all();
        $rules = [
            'order_no' => 'required',
        ];
        $validateParams = $this->validateParams($rules, $params['params']);
        $param = $params['params'];
        try {
            if (empty($validateParams) || $validateParams['code'] != 0) {
                return apiResponse([], $validateParams['code'], $validateParams['msg']);
            }
            //查询商品信息
            $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById($param['order_no']);
            if (empty($orderInfo)) {
                \App\Lib\Common\LogApi::info('查询订单信息表失败', $param['order_no']);
                return apiResponse([], ApiStatus::CODE_35003, '查询订单信息表失败');
            }
            $orderGoodsInfo = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow([
                'order_no'=>$param['order_no']
            ]);
            if (empty($orderGoodsInfo)) {
                \App\Lib\Common\LogApi::info('查询商品信息表失败', $param['order_no']);
                return apiResponse([], ApiStatus::CODE_35003, '查询商品信息表失败');
            }
            //计算押金 减免押金 原押金
            $orderGoodsInfo['less_yajin'] = normalizeNum($orderGoodsInfo['goods_yajin'] - $orderGoodsInfo['yajin']);
            return apiResponse([
                'orderGoodsInfo'=>$orderGoodsInfo,
                'order_status'=>$orderInfo['order_status']
            ], ApiStatus::CODE_0);
        }catch(\Exception $e){
            return apiResponse([], ApiStatus::CODE_35000, $e->getMessage());
        }
    }

    /**
     * 小程序系统任务取消订单接口（30分钟自动执行）
     */
    public function orderCancelTimedTask(){
        //查询商户订单
        $order_info = \App\Order\Models\Order::where(['order_status'=>OrderStatus::OrderWaitPaying,'pay_type'=>\App\Order\Modules\Inc\PayInc::MiniAlipay ,'create_time'=>['LT',time()-1800]])->select();
        if( empty($order_info) ){
            \App\Lib\Common\LogApi::debug('小程序定时取消商户订单数据查询错误',$order_info);
            return apiResponse([],ApiStatus::CODE_35003,'小程序定时取消商户订单数据查询错误');
        }
        //循环取消操作
        foreach($order_info as $key=>$val){
            //查询芝麻订单
            $result = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo($val['order_no']);
            if( empty($result) ){
                \App\Lib\Common\LogApi::debug('小程序定时取消芝麻订单查询失败',$val['order_no']);
                continue;
            }
            //发送取消请求
            $data = [
                'out_order_no'=>$val['order_no'],//商户端订单号
                'zm_order_no'=>$val['zm_order_no'],//芝麻订单号
                'remark'=>'小程序系统取消订单操作（定时取消）',//订单操作说明
                'app_id'=>$val['app_id'],//小程序appid
            ];
            $b = \App\Lib\Payment\mini\MiniApi::OrderCancel($data);
            if($b === false){
                \App\Lib\Common\LogApi::debug('小程序定时取消芝麻订单查询失败',$val['order_no']);
                continue;
            }
            //取消订单修改订单状态
            $code = \App\Order\Modules\Service\OrderOperate::cancelOrder($val['order_no'],$val['user_id']);
            if( $code != ApiStatus::CODE_0){
                \App\Lib\Common\LogApi::debug('小程序定时取消商户端订单失败',$val['order_no']);
                continue;
            }
            //用户取消订单写入日志系统
            \App\Order\Modules\Repository\OrderLogRepository::add(
                $val['user_id'],
                $val['mobile'],
                \App\Lib\PublicInc::Type_System,
                $val['order_no'],
                "取消订单",
                '小程序定时取消商户端订单操作'
            );
        }
    }
}