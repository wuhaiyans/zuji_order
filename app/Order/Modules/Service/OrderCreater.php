<?php
namespace App\Order\Modules\Service;

use App\Lib\ApiStatus;
use App\Lib\Certification;
use App\Lib\Goods\Goods;
use App\Lib\User\User;
use App\Order\Modules\Inc\PayInc;
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
     */

    public function creater($data){

        $orderCreaterComponnet = new \oms\OrderCreater( $business_key,$order_no );

        // 用户
        $UserComponnet = new \oms\order_creater\UserComponnet($orderCreaterComponnet,$user_id);
        $orderCreaterComponnet->set_user_componnet($UserComponnet);

        // 商品
        $SkuComponnet = new \oms\order_creater\SkuComponnet($orderCreaterComponnet,$sku_id,$params['payment_type_id']);
        $orderCreaterComponnet->set_sku_componnet($SkuComponnet);

        // 装饰者 信用
        $orderCreaterComponnet = new \oms\order_creater\CreditComponnet($orderCreaterComponnet,true,$app_id);

        if( $app_id != \zuji\Config::Jdxbxy_App_id ) {
            // 装饰者 风险
            $orderCreaterComponnet = new \oms\order_creater\YidunComponnet($orderCreaterComponnet);
        }
        // 装饰着 押金
        $orderCreaterComponnet = new \oms\order_creater\DepositComponnet($orderCreaterComponnet,$params['payment_type_id']);

        // 装饰着 代扣
        $orderCreaterComponnet = new \oms\order_creater\UserWithholding($orderCreaterComponnet);

        // 装饰者 收货地址
        $orderCreaterComponnet = new \oms\order_creater\AddressComponnet($orderCreaterComponnet,$address_id);

        // 装饰者 渠道
        $orderCreaterComponnet = new \oms\order_creater\ChannelComponnet($orderCreaterComponnet, $app_id);

        //装饰者 优惠券
        $orderCreaterComponnet = new \oms\order_creater\CouponComponnet($orderCreaterComponnet, $params['coupon_no']);

        // 装饰者 分期单
        $orderCreaterComponnet = new \oms\order_creater\InstalmentComponnet($orderCreaterComponnet);


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
            $user_info =User::getUser(config('tripartite.Interior_Goods_Request_data'),$data['user_id']);
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