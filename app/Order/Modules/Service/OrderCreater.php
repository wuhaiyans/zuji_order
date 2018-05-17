<?php
namespace App\Order\Modules\Service;

use App\Lib\ApiStatus;
use App\Lib\Certification;
use App\Lib\Goods\Goods;
use App\Lib\User\User;
use App\Order\Modules\Inc\PayInc;
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
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use Illuminate\Support\Facades\DB;

class OrderCreater
{
    protected $verify;
    protected $orderRepository;

    public function __construct(OrderCreateVerify $orderCreateVerify,OrderRepository $orderRepository)
    {
        $this->verify =$orderCreateVerify;
        $this->orderRepository = $orderRepository;
    }

    /**
     * 改版下单
     * @param $data
     * [
     *'appid'=>1, //appid
     *'pay_type'=>1, //支付方式
     *'address_id'=>$address_id, //收货地址
     *'sku'=>[0=>['sku_id'=>1,'sku_num'=>2]], //商品数组
     *'coupon'=>["b997c91a2cec7918","b997c91a2cec7000"], //优惠券组信息
     *'user_id'=>18,  //增加用户ID
     *];
     */

    public function creater($data){

        $order_no = OrderOperate::createOrderNo(1);
        //订单创建构造器
        $orderCreater = new OrderComponnet($order_no);

        // 用户
        $userComponnet = new UserComponnet($orderCreater,$data['user_id'],$data['address_id']);
        $orderCreater->setUserComponnet($userComponnet);

        // 商品
        $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
        $orderCreater->setSkuComponnet($skuComponnet);

        // 信用
        $orderCreater = new CreditComponnet($orderCreater,$data['appid']);

        //蚁盾数据
        $orderCreater = new YidunComponnet($orderCreater);

        //押金
        $orderCreater = new DepositComponnet($orderCreater,$data['pay_type']);

        //代扣
        $orderCreater = new WithholdingComponnet($orderCreater);

        //收货地址
        $orderCreater = new AddressComponnet($orderCreater,$data['address_id']);

        //渠道
        $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);

        //优惠券
        $orderCreater = new CouponComponnet($orderCreater,$data['coupon']);

        //分期
        $orderCreater = new InstalmentComponnet($orderCreater);

        $b = $orderCreater->filter();

        $schemaData = $orderCreater->getDataSchema();

        $b = $orderCreater->create();

        die;

    }
    /**
     * 订单确认查询
     * @return array
     */
    public function confirmation($data)
    {
        $error ="";
        $order_flag =true;
        try {
            //获取用户信息
            $user_info =User::getUser(config('tripartite.Interior_Goods_Request_data'),$data['user_id'],$data['address_id']);
            if (!is_array($user_info)) {
                return $user_info;
            }
            //var_dump($user_info);die;
            //获取商品详情
            $goods = Goods::getSku(config('tripartite.Interior_Goods_Request_data'),$data['sku']);
            if (!is_array($goods)) {
                return $goods;
            }
            for($i=0;$i<count($data['sku']);$i++){
                $goods[$data['sku'][$i]['sku_id']]['sku_info']['sku_num'] = $data['sku'][$i]['sku_num'] ;
            }
            // var_dump($goods);
            $data['zuqi_type'] =1;
            $arr2 = array_column($goods, 'sku_info');
            for ($i =0;$i<count($arr2);$i++){
                if($arr2[$i]['zuqi_type'] ==2 && (count($arr2) >1 || $arr2[$i]['sku_num'] >1)){
                    return ApiStatus::CODE_40003;
                }
                if($arr2[$i]['zuqi_type'] ==2){
                    $data['zuqi_type'] =2;
                }
            }
            //验证优惠券信息
            if(count($data['coupon']) >0){
                $data = $this->verify->couponVerify($data,$goods);
                if(!is_array($data)){
                    $order_flag =false;
                    $error =$this->verify->get_error();
                    return $error;
                }
            }


            foreach ($goods as $k =>$v){
                $goods_info =$v;
                $data['channel_id'] = $goods_info['spu_info']['channel_id'];
                //下单验证
                $res = $this->verify->verify($data, $user_info, $goods_info);
                if(!$res){
                    $order_flag =false;
                    $error =$this->verify->get_error();
                }
                $goods_data[] =$this->verify->filter();
            }

            $user_data =$this->verify->GetUserSchema();
            // 是否需要签署代扣协议
            $need_to_sign_withholding = 'N';
            if( $data['pay_type']== PayInc::WithhodingPay){
                if( !$user_data['user']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }
            $result = [
                'coupon'         => $data['coupon'],
                'certified'			=> $user_data['credit']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($user_data['credit']['certified_platform']),
                'credit_status'		=> $order_flag &&$need_to_sign_withholding=='N',  // 是否免押金
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                'user_info' => $user_data,
                'sku_info'=>$goods_data,
                '$b' => $order_flag,
                '_error' => $error,
                // 支付方式
                'pay_type'	 => ''.$data['pay_type'],
            ];

            return $result;
        } catch (\Exception $exc) {
            echo $exc->getMessage();
            die;
        }
    }

    /**
     * 创建订单
     * @return array
     */
    public function create($data)
    {
        $data['order_no'] = OrderOperate::createOrderNo(1);
        $order_flag =true;
        DB::beginTransaction();
        try {
            //获取用户信息
            $user_info =User::getUser(config('tripartite.Interior_Goods_Request_data'),$data['user_id'],$data['address_id']);
            if (!is_array($user_info)) {
                return $user_info;
            }
            //只有提交订单时 要验证 收货地址信息
            $address =$this->verify->AddressVerify($user_info);
            if(!$address){
                return ApiStatus::CODE_41005;
            }

            //获取商品详情
            $goods = Goods::getSku(config('tripartite.Interior_Goods_Request_data'),$data['sku']);
            if (!is_array($goods)) {
                return $goods;
            }

            for($i=0;$i<count($data['sku']);$i++){
                $goods[$data['sku'][$i]['sku_id']]['sku_info']['sku_num'] = $data['sku'][$i]['sku_num'] ;
            }

            $data['zuqi_type'] =1;
            $arr2 = array_column($goods, 'sku_info');
            for ($i =0;$i<count($arr2);$i++){
                if($arr2[$i]['zuqi_type'] ==2 && (count($arr2) >1 || $arr2[$i]['sku_num'] >1)){
                    return ApiStatus::CODE_40003;
                }
                if($arr2[$i]['zuqi_type'] ==2){
                    $data['zuqi_type'] =2;
                }
            }

            if(count($data['coupon']) >0){
                $data = $this->verify->couponVerify($data,$goods);
                if(!is_array($data)){
                    $order_flag =false;
                    $error =$this->verify->get_error();
                    return $error;
                }
            }

            foreach ($goods as $k =>$v){
                $goods_info =$v;
                $data['channel_id'] = $goods_info['spu_info']['channel_id'];
                //下单验证
                $res = $this->verify->Verify($data, $user_info, $goods_info);
                if(!$res){
                    $order_flag =false;
                    $error =$this->verify->get_error();
                    return $error;
                }
                $goods_data[] =$this->verify->filter();
            }


            $user_data =$this->verify->GetUserSchema();
            // 是否需要签署代扣协议
            $need_to_sign_withholding = 'N';
            if( $data['pay_type']== PayInc::WithhodingPay){
                if( !$user_data['user']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }

            $result = [
                'coupon'         => $data['coupon'],
                'certified'			=> $user_data['credit']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($user_data['credit']['certified_platform']),
                'credit_status'		=> $order_flag &&$need_to_sign_withholding=='N',  // 是否免押金
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                'user_info' => $user_data,
                'sku_info'=>$goods_data,
                'order_no'=>$data['order_no'],
                // 支付方式
                'pay_type'	 =>$data['pay_type'],
            ];

            $b =$this->orderRepository->create($data,$result);
            if(!$b){
                DB::rollBack();
                return ApiStatus::CODE_30005;
            }
            var_dump($goods_data);die;
           DB::commit();
            return $result;
        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();die;
        }

    }
    /*
    *
    * 发货后，更新物流单号方法
    */
    public function updateDelivery($params){
        if(empty($params['order_no'])){
            return ApiStatus::CODE_30005;//订单编码不能为空
        }
        if(empty($params['delivery_sn'])){
            return ApiStatus::CODE_30006;//物流单号不能为空
        }
        if(empty($params['delivery_type'])){
            return ApiStatus::CODE_30007;//物流渠道不能为空
        }
        $res = $this->orderUserInfoRepository->update($params);
        if(!$res){
            return false;
        }
        return true;
    }

    /*
     *
     * 更新物流单号
     */
    public function update($params){
        return $this->orderUserInfoRepository->update($params);
    }
    //获取订单信息
    public function get_order_info($where){
        return $this->orderRepository->get_order_info($where);
    }
    //更新订单状态
    public function order_update($order_no){
        return $this->orderRepository->order_update($order_no);
    }
    public function get_order_detail($where=[]){
        return $this->orderRepository->getOrderInfo($where);



    }

}