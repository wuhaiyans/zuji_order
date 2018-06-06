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
use App\Order\Modules\OrderCreater\AddressComponnet;
use App\Order\Modules\OrderCreater\ChannelComponnet;
use App\Order\Modules\OrderCreater\CouponComponnet;
use App\Order\Modules\OrderCreater\CreditComponnet;
use App\Order\Modules\OrderCreater\DepositComponnet;
use App\Order\Modules\OrderCreater\InstalmentComponnet;
use App\Order\Modules\OrderCreater\OrderComponnet;
use App\Order\Modules\OrderCreater\SkuComponnet;
use App\Order\Modules\OrderCreater\UserComponnet;
use App\Order\Modules\OrderCreater\WithholdingComponnet;
use App\Order\Modules\OrderCreater\YidunComponnet;

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
        ];
        $validateParams = $this->validateParams($rules,$params['params']);
        if ($validateParams['code'] != 0) {
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
            'sku_id' => intval($params['sku_id']),
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
        $data['pay_type'] = \App\Order\Modules\Inc\PayInc::MiniAlipay;
        $data['sku'] = [
            'sku_id'=>$data['sku_id']
        ];
        //查询芝麻订单确认结果
        $miniApi = new CommonMiniApi(config('ALIPAY_MINI_APP_ID'));
        //获取请求流水号
        $transactionNo = \App\Order\Modules\Service\OrderOperate::createOrderNo(1);
        $miniParams = [
            'transaction_id'=>$transactionNo,
            'order_no'=>$params['zm_order_no'],
        ];
        $miniData = $miniApi->orderConfirm($miniParams);
        if($miniData === false){
            \App\Lib\Common\LogApi::notify('芝麻接口请求错误',$miniParams);
            return apiResponse( [], ApiStatus::CODE_35003, '查询芝麻订单确认结果失败');
        }
        //添加逾期时间
        $miniData['overdue_time'] = $data['overdue_time'];
        print_r($miniData);
        print_r($params);
        print_r($data);die;
        //查询成功记录表
        $res = \App\Order\Modules\Repository\MiniOrderRepository::add($miniData);
        if( !$res ){
            \App\Lib\Common\LogApi::debug('小程序请求记录失败',$res);
        }

        //用户处理
        $userInfo = [];

        //处理用户收货地址
        $address_info = [];
//        $address_data = [
//            'mid' => $user_id,
//            'name' => $data['name'],
//            'mobile' => $data['mobile'],
//            'address' => $data['house'],
//        ];
//        $member_address_table = $load->table('member/member_address');
//        $member_address_service = $load->service('member/member_address');
//        $address_id = $member_address_table->edit_address($address_data);
//        $address_info = $member_address_service->user_address_default($user_id);
//        $address_info['address_id'] = $address_id;

        //优惠券处理

//        $couponData = \App\Lib\Coupon\Coupon::getCoupon(config('ALIPAY_MINI_APP_ID'));
//        //100元全场通用优惠券
//        $app_ids = [
//            1,5,9,11,12,13,14,15,16,21,22,24,27
//        ];
//        if(in_array($appid,$app_ids)){
//            $coupon = \zuji\coupon\Coupon::set_coupon_user(array("user_id"=>$this->member['id'],"only_id"=>'87da43c62f09a2c43f905ae05335c31c'));
//            $params['coupon_no'] = $coupon['coupon_no']?$coupon['coupon_no']:$params['coupon_no'];
//        }
//        //首月0租金优惠券领取活动--(临时)--
//        $sku_info = $this->load->service("goods2/goods_sku")->api_get_info($sku_id,"spu_id");
//        $this->coupon = $this->load->table("coupon/coupon_type");
//        $coupon_info = $this->coupon->where(['only_id'=>'4033f1cdfa5d835ea70cd07be787babc'])->find();
//        $num = explode(",",substr($coupon_info['range_value'],0,-1));
//        if(in_array($sku_info['spu_id'],$num)){
//            $coupon = \zuji\coupon\Coupon::set_coupon_user(array("user_id"=>$this->member['id'],"only_id"=>$coupon_info['only_id']));
//            $params['coupon_no'] = $coupon['coupon_no']?$coupon['coupon_no']:$params['coupon_no'];
//        }

        //商品信息处理
        $goods = \App\Lib\Goods\Goods::getSku( $data['sku_id']  );

        //小程序订单
        $orderType =OrderStatus::orderMiniService;
        try{
            //订单创建构造器
            $orderCreater = new OrderComponnet($data['order_no'],$userInfo['user_id'],$data['pay_type'],$params['appid'],$orderType);

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$userInfo['user_id'],$address_info['address_id']);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);

            // 信用
            $orderCreater = new CreditComponnet($orderCreater);

            //蚁盾数据
            $orderCreater = new YidunComponnet($orderCreater);

            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type']);

            //代扣
            $orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$userInfo['user_id']);

            //收货地址
            $orderCreater = new AddressComponnet($orderCreater);

            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$params['appid']);

            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$params['coupon']);

            //分期
            $orderCreater = new InstalmentComponnet($orderCreater,$data['pay_type']);

            $b = $orderCreater->filter();
            if(!$b){
                //把无法下单的原因放入到用户表中
                $userRemark =User::setRemark($userInfo['user_id'],$orderCreater->getOrderCreater()->getError());

            }
            $schemaData = $orderCreater->getDataSchema();
            $result = [
                'coupon'         => $params['coupon'],
                'certified'			=> $schemaData['user']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schemaData['user']['certified_platform']),
                'credit'			=> ''.$schemaData['user']['score'],
                '_order_info' => $schemaData,
                'b' => $b,
                '_error' => $orderCreater->getOrderCreater()->getError(),
                'pay_type'=>$data['pay_type'],
            ];
            return apiResponse( $result, ApiStatus::CODE_0 );

        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }

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
            'order_no'=>$orderNo,
            'address_id'=>$addressId,
            'sku'=>$sku,
            'coupon'=>$coupon,
            'user_id'=>$userId,  //增加用户ID
        ];
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