<?php
namespace App\Order\Modules\Service;

use App\Lib\ApiStatus;
use App\Lib\Certification;
use App\Lib\OldInc;
use App\Lib\PayInc;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ThirdInterface;
use Illuminate\Support\Facades\DB;

class OrderCreater
{

    protected $third;
    protected $verify;
    protected $orderRepository;

    public function __construct(ThirdInterface $third,OrderCreateVerify $orderCreateVerify,OrderRepository $orderRepository)
    {
        $this->third = $third;
        $this->verify =$orderCreateVerify;
        $this->orderRepository = $orderRepository;

    }
    /**
     * 订单确认查询
     * @return array
     */
    public function confirmation($data)
    {
        try {
            //获取用户信息
            $user_info = $this->third->GetUser($data['user_id']);
            if (!is_array($user_info)) {
                return $user_info;
            }
            //var_dump($user_info);die;

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
                $error =$this->verify->get_error();
            }
            $schema_data =$this->verify->filter();
            //var_dump($schema_data);
            // 是否需要签署代扣协议
            $need_to_sign_withholding = 'N';
            if( $data['pay_type']== PayInc::WithhodingPay){
                if( !$schema_data['withholding']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }
            //租期类型格式
            $zuqi_type = "";
            if($schema_data['sku']['zuqi_type']==1){
                $zuqi_type = "day";
            }
            elseif($schema_data['sku']['zuqi_type']==2){
                $zuqi_type = "month";
            }
            if( $data['appid'] == OldInc::Jdxbxy_App_id ) {
                $need_to_credit_certificate="N";
            }else{
                $need_to_credit_certificate="Y";
            }
            /**********获取支付信用及规则信息*************/
//            if($params['payment_type_id']>0){
//                $this->credit = $this->load->service("payment/credit");
//                $credit_info = $this->credit->get_info_by_payment($params['payment_type_id']);
//                $credit_info = current($credit_info);
//                //信用类型
//                $credit_type = $credit_info['id'];
//                if(!$credit_info){
//                    api_resopnse( [], ApiStatus::CODE_40003,'不支持该支付方式');
//                    return;
//                }
//            }

            $result = [
                'coupon_no'         => $data['coupon_no'],
                'certified'			=> $schema_data['credit']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schema_data['credit']['certified_platform']),
                'credit'			=> ''.$schema_data['credit']['credit'],

                'credit_type'			=> 1,
                'credit_status'		=> $res &&$need_to_sign_withholding=='N'&&$need_to_credit_certificate=='N'?'Y':'N',  // 是否免押金
                // 订单金额
                'amount'			=> priceFormat($schema_data['sku']['amount']/100),
                // 优惠类型
                'coupon_type'	=> ''.$schema_data['coupon']['coupon_type'],
                // 优惠金额
                'discount_amount'	=> priceFormat($schema_data['sku']['discount_amount']/100),
                // 商品总金额
                'all_amount'		=> priceFormat($schema_data['sku']['all_amount']/100),
                // 买断价
                'buyout_price'	    => priceFormat($schema_data['sku']['buyout_price']/100),
                // 市场价
                'market_price'	    => priceFormat($schema_data['sku']['market_price']/100),
                //押金
                'yajin'				=> priceFormat($schema_data['sku']['yajin']/100),
                //免押金
                'mianyajin'			=> priceFormat($schema_data['sku']['mianyajin']/100),
                //原始租金
                'zujin'				=> priceFormat($schema_data['sku']['zujin']/100),
                //首期金额
                'first_amount'				=> priceFormat($schema_data['instalment']['first_amount']/100),
                //每期金额
                'fenqi_amount'				=> priceFormat($schema_data['instalment']['fenqi_amount']/100),
                //意外险
                'yiwaixian'			=> priceFormat($schema_data['sku']['yiwaixian']/100),
                //租期
                'zuqi'				=> ''.$schema_data['sku']['zuqi'],
                //租期类型
                'zuqi_type'			=> $zuqi_type,
                'chengse'			=> ''.$schema_data['sku']['chengse'],
                // 支付方式
                'payment_type_id'	 => ''.$schema_data['sku']['pay_type'],
                'contract_id'			 => ''.$schema_data['sku']['contract_id'],
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $need_to_credit_certificate,
                '_order_info' => $schema_data,
                '$b' => $res,
                '_error' => $error,
            ];
            //var_dump($result);die;
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
            $zuqi_type = "";
            if($schema_data['sku']['zuqi_type']==1){
                $zuqi_type = "day";
            }
            elseif($schema_data['sku']['zuqi_type']==2){
                $zuqi_type = "month";
            }
            if( $data['appid'] == OldInc::Jdxbxy_App_id ) {
                $need_to_credit_certificate="N";
            }else{
                $need_to_credit_certificate="Y";
            }
            /**********获取支付信用及规则信息*************/
//            if($params['payment_type_id']>0){
//                $this->credit = $this->load->service("payment/credit");
//                $credit_info = $this->credit->get_info_by_payment($params['payment_type_id']);
//                $credit_info = current($credit_info);
//                //信用类型
//                $credit_type = $credit_info['id'];
//                if(!$credit_info){
//                    api_resopnse( [], ApiStatus::CODE_40003,'不支持该支付方式');
//                    return;
//                }
//            }

            $result = [
                'coupon_no'         => $data['coupon_no'],
                'certified'			=> $schema_data['credit']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schema_data['credit']['certified_platform']),
                'credit'			=> ''.$schema_data['credit']['credit'],

                'credit_type'			=> 1,
                'credit_status'		=> $res &&$need_to_sign_withholding=='N'&&$need_to_credit_certificate=='N'?'Y':'N',  // 是否免押金
                // 订单金额
                'amount'			=> priceFormat($schema_data['sku']['amount']/100),
                // 优惠类型
                'coupon_type'	=> ''.$schema_data['coupon']['coupon_type'],
                // 优惠金额
                'discount_amount'	=> priceFormat($schema_data['sku']['discount_amount']/100),
                // 商品总金额
                'all_amount'		=> priceFormat($schema_data['sku']['all_amount']/100),
                // 买断价
                'buyout_price'	    => priceFormat($schema_data['sku']['buyout_price']/100),
                // 市场价
                'market_price'	    => priceFormat($schema_data['sku']['market_price']/100),
                //押金
                'yajin'				=> priceFormat($schema_data['sku']['yajin']/100),
                //免押金
                'mianyajin'			=> priceFormat($schema_data['sku']['mianyajin']/100),
                //原始租金
                'zujin'				=> priceFormat($schema_data['sku']['zujin']/100),
                //首期金额
                'first_amount'				=> priceFormat($schema_data['instalment']['first_amount']/100),
                //每期金额
                'fenqi_amount'				=> priceFormat($schema_data['instalment']['fenqi_amount']/100),
                //意外险
                'yiwaixian'			=> priceFormat($schema_data['sku']['yiwaixian']/100),
                //租期
                'zuqi'				=> ''.$schema_data['sku']['zuqi'],
                //租期类型
                'zuqi_type'			=> $zuqi_type,
                'chengse'			=> ''.$schema_data['sku']['chengse'],
                // 支付方式
                'payment_type_id'	 => ''.$schema_data['sku']['pay_type'],
                'contract_id'			 => ''.$schema_data['sku']['contract_id'],
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $need_to_credit_certificate,
                '_order_info' => $schema_data,
                'sku_info'			=> '',
            ];
            var_dump($result);die;
            return $result;
        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();die;
        }

    }
}