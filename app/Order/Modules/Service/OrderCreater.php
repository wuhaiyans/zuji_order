<?php
namespace App\Order\Modules\Service;

use App\Lib\ApiStatus;
use App\Lib\Certification;
use App\Lib\OldInc;
use App\Lib\PayInc;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use App\Order\Modules\Repository\ThirdInterface;
use Illuminate\Support\Facades\DB;

class OrderCreater
{

    protected $third;
    protected $verify;
    protected $orderRepository;
    protected $orderUserInfoRepository;

    public function __construct(ThirdInterface $third,OrderCreateVerify $orderCreateVerify,OrderRepository $orderRepository,orderUserInfoRepository $orderUserInfoRepository)
    {
        $this->third = $third;
        $this->verify =$orderCreateVerify;
        $this->orderRepository = $orderRepository;
        $this->orderUserInfoRepository = $orderUserInfoRepository;

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
            $user_info = $this->third->GetUser($data['user_id']);
            if (!is_array($user_info)) {
                return $user_info;
            }
            //var_dump($user_info);die;

            //获取风控信息
            $this->third->GetFengkong();

            //获取商品详情
            $goods = $this->third->GetSku($data['sku']);
            if (!is_array($goods)) {
                return $goods;
            }
            foreach ($goods as $k =>$v){

                $goods_info =$v;
                $data['channel_id'] = $goods_info['spu_info']['channel_id'];
                //下单验证
                $res = $this->verify->Verify($data, $user_info, $goods_info);
                if(!$res){
                    $order_flag =false;
                    $error =$this->verify->get_error();
                }
                $goods_data[] =$this->verify->GetSchema();
            }
            $user_data =$this->verify->GetUserSchema();
            // 是否需要签署代扣协议
            $need_to_sign_withholding = 'N';
            if( $data['pay_type']== PayInc::WithhodingPay){
                if( !$user_data['user']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }
            if( $data['appid'] == OldInc::Jdxbxy_App_id ) {
                $need_to_credit_certificate="N";
            }else{
                $need_to_credit_certificate="Y";
            }

            $result = [
                'coupon_no'         => $data['coupon_no']?$data['coupon_no']:"",
                'certified'			=> $user_data['credit']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($user_data['credit']['certified_platform']),
                'credit_status'		=> $order_flag &&$need_to_sign_withholding=='N'&&$need_to_credit_certificate=='N'?'Y':'N',  // 是否免押金
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $need_to_credit_certificate,
                'user_info' => $user_data,
                'sku_info'=>$goods_data,
                '$b' => $order_flag,
                '_error' => $error,
                // 支付方式
                'pay_type'	 => ''.$data['pay_type'],
            ];
            var_dump($result);die;
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
        DB::beginTransaction();
        try {
            //获取用户信息
            $user_info = $this->third->GetUser($data['user_id'],$data['address_id']);
            if (!is_array($user_info)) {
                return $user_info;
            }
            //只有提交订单时 要验证 收货地址信息
            $address =$this->verify->AddressVerify($user_info);
            if(!$address){
                return ApiStatus::CODE_41005;
            }

            //获取商品详情
            $goods_info = $this->third->GetSku($data['sku_id']);
            if (!is_array($goods_info)) {
                return $goods_info;
            }
            //var_dump($goods_info);die;
            $data['channel_id'] = $goods_info['spu_info']['channel_id'];
            //获取风控信息
            $this->third->GetFengkong();
            $this->third->GetCredit();

            //下单验证
            $res = $this->verify->Verify($data, $user_info, $goods_info);
            $error ="";
            if(!$res){
                return $this->verify->get_error();
            }
            $schema_data =$this->verify->filter();

            $b =$this->orderRepository->create($data,$schema_data);
            if(!$b){
                DB::rollBack();
                return ApiStatus::CODE_30005;
            }

            //var_dump($schema_data);
            // 是否需要签署代扣协议
            $need_to_sign_withholding = 'N';
            if( $data['pay_type']== PayInc::WithhodingPay){
                if( !$schema_data['withholding']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }
            //租期类型格式
            if( $data['appid'] == OldInc::Jdxbxy_App_id ) {
                $need_to_credit_certificate="N";
            }else{
                $need_to_credit_certificate="Y";
            }
echo "订单";
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
}