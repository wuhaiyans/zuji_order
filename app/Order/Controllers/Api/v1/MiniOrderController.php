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
        //获取订单号
        $orderNo = \App\Order\Modules\Service\OrderOperate::createOrderNo(1);
        //获取商品信息
        $goods_info = \App\Lib\Goods\Goods::getSku([$params['sku_id']]);
        if( $goods_info[$params['sku_id']]['sku_info']['zuqi_type'] == 2 ){//租期类型（1：天；2：月）
            $new_data = date('Y-m-d H:i:s');
            $overdue_time = date('Y-m-d H:i:s', strtotime($new_data.' +'.(intval($goods_info[$params['sku_id']]['sku_info']['zuqi'])+1).' month'));
        }else{
            $new_data = date('Y-m-d H:i:s');
            $overdue_time = date('Y-m-d H:i:s', strtotime($new_data.' +'.(intval($goods_info[$params['sku_id']]['sku_info']['zuqi'])+30).' day'));
        }
        $data = [
            'order_no' => $orderNo,
            'sku' => [$params],
            'overdue_time' => $overdue_time
        ];
        //redis 存储数据
        $values = Redis::set('dev:zuji:order:miniorder:temporaryorderno:'.$orderNo, json_encode($data));

        if(!$values){
            return apiResponse([],ApiStatus::CODE_35001,'保存临时订单号失败');
        }
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
            'appid' => 'required', //【必须】string；appid
            'zm_order_no' => 'required', //【必须】string；芝麻订单号
            'out_order_no' => 'required', //【必须】string；业务订单号
            'payment_type_id' => 'required', //【必须】string；支付方式id
            'coupon_no' => 'required', //【必须】string；优惠券
        ];
        $validateParams = $this->validateParams($rules,$params['params']);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];
        //判断支付状态
        if($params['payment_type_id'] != \App\Order\Modules\Inc\PayInc::MiniAlipay){
            return apiResponse([],ApiStatus::CODE_50005,'小程序支付状态错误');
        }
        //判断当前是否有临时订单
        $data = Redis::get('dev:zuji:order:miniorder:temporaryorderno:'.$params['out_order_no']);
        if(!$data){
            \App\Lib\Common\LogApi::notify('小程序临时订单不存在');
            return apiResponse([],$validateParams['code'],'业务临时订单不存在');
        }
        $data = json_decode($data,true);
        $data['pay_type'] = $params['payment_type_id'];
        $data['appid'] = $params['appid'];
        $data['coupon'] = [
            $params['coupon_no']
        ];
        //查询芝麻订单确认结果
        $miniApi = new CommonMiniApi(config('miniappid.ALIPAY_MINI_APP_ID'));
        //获取请求流水号
        $transactionNo = \App\Order\Modules\Service\OrderOperate::createOrderNo(1);
        //添加逾期时间
        $miniParams = [
            'transaction_id'=>$transactionNo,
            'order_no'=>$params['zm_order_no'],
            'out_order_no'=>$params['out_order_no'],
            'overdue_time'=>$data['overdue_time'],
        ];
        $b = $miniApi->orderConfirm($miniParams);
        if($b === false){
            \App\Lib\Common\LogApi::notify('芝麻接口请求错误',$miniParams);
            return apiResponse( [], ApiStatus::CODE_35003, $miniApi->getError());
        }
        $miniData = $miniApi->getResult();
        //用户处理
        $_user = \App\Lib\User\User::getUserId($miniData);
        $data['user_id'] = $_user['user_id'];
        //处理用户收货地址
        $addressId = \App\Lib\User\User::getAddressId($miniData);
        $data['address_info'] = [
            'province_id'=>$addressId['provin_id'],
            'city_id'=>$addressId['city_id'],
            'district_id'=>$addressId['country_id'],
            'mobile'=>$miniData['mobile'],
            'name'=>$miniData['name'],
            'address'=>$miniData['house'],
        ];
        //小程序订单确认
        $res = $this->OrderCreate->miniConfirmation($data);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30005,get_msg());
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
        $orderNo	= $params['params']['order_no'];//支付方式ID
        $payType	= $params['params']['pay_type'];//支付方式ID
        $sku		= $params['params']['sku_info'];
        $coupon		= $params['params']['coupon'];
        $userId		= $params['params']['user_id'];
        $address		= $params['params']['address'];

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
        if(empty($address)){
            return apiResponse([],ApiStatus::CODE_20001,"address不能为空");
        }
        if(count($sku)<1){
            return apiResponse([],ApiStatus::CODE_20001,"商品ID不能为空");
        }
        //处理用户收货地址
        $addressId = \App\Lib\User\User::getAddressId([
            'house'=>$address,
        ]);
        $data = [
            'appid'=>$appid,
            'pay_type'=>$payType,
            'order_no'=>$orderNo,
            'sku'=>$sku,
            'coupon'=>$coupon,
            'user_id'=>$userId,  //增加用户ID
        ];
        $data['address_info'] = [
            'province_id'=>$addressId['provin_id'],
            'city_id'=>$addressId['city_id'],
            'district_id'=>$addressId['country_id'],
            'address'=>$address,
        ];
        print_r($data);
        $res = $this->OrderCreate->miniCreate($data);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30005,get_msg());
        }

        return apiResponse($res,ApiStatus::CODE_0);
    }

    /**
     * 前段确认订单同步通知接口
     * 获取订单详细信息
     */
    public function frontTransition(Request $request){
        $params     = $request->all();
        // 验证参数
        $rules = [
            'appid' => 'required', //【必须】string；appid
            'zm_order_no' => 'required', //【必须】string；芝麻订单号
            'out_order_no' => 'required', //【必须】string；业务订单号
            'payment_type_id' => 'required', //【必须】string；支付方式id
            'coupon_no' => 'required', //【必须】string；优惠券
        ];
        $validateParams = $this->validateParams($rules,$params['params']);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];
        // 验签 验证 通过 修改数据
        if($params['order_status'] == 'SUCCESS'){
            \App\Lib\Common\LogApi::info('芝麻小程序确认订单同步通知参数',$params);
            return apiResponse( [], ApiStatus::CODE_0);
        }else{
            return apiResponse( [], ApiStatus::CODE_35004,'小程序处理中');
        }
    }

    /**
     * 小程序取消订单（解冻预授权 解约代扣）
     * $params = [
     *   'order_no'=>'',
     *   'remark'=>'',
     * ]
     */
    public function miniOrderCancel(Request $request){
        $params = $request->all();
        $rules = [
            'order_no'  => 'required',
            'remark'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {
            return apiResponse([],$validateParams['code']);
        }
        //查询芝麻订单
        $result = \App\Order\Modules\Repository\MiniOrderRentNotifyRepository::getMiniOrderRentNotify($params['order_no']);
        if( empty($result) ){
            \App\Lib\Common\LogApi::info('本地小程序确认订单回调记录查询失败',$params['order_no']);
            return apiResponse([],ApiStatus::CODE_35003,'本地小程序确认订单回调记录查询失败');
        }
        //发送取消请求
        $data = [
            'out_order_no'=>$result['out_order_no'],//商户端订单号
            'zm_order_no'=>$result['zm_order_no'],//芝麻订单号
            'remark'=>$params['remark'],//订单操作说明
            'app_id'=>$result['notify_app_id'],//小程序appid
        ];
        $b = \App\Lib\Payment\mini\MiniApi::OrderCancel($data);
        if($b === false){
            return apiResponse(['reason'=>\App\Lib\Payment\mini\MiniApi::getError()],ApiStatus::CODE_35005);
        }
        return apiResponse([],ApiStatus::CODE_0);
    }

}