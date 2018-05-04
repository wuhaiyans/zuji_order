<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use App\Lib\PayInc;
use App\Order\Modules\Repository\ThirdInterface;

/**
 * 下单验证类
 */
class OrderCreateVerify
{
    protected $third;

    public function __construct(ThirdInterface $third)
    {
        $this->third = $third;
    }

    public function Verify($data,$user_info){

        //判断是否需要签约代扣协议
        if($data['pay_type'] == PayInc::WithhodingPay){
            $Withhold =$this->UserWithholding($user_info['withholding_no'],$user_info['id']);
            if(!is_array($Withhold)){
                return $Withhold;
            }
        }

        //判断该渠道是否有效等
        $channel = $this->Channel($data['appid'],$data['channel_id']);
        if(!is_array($channel)){
            return $channel;
        }

        return $channel;

    }
    /**
     *  验证代扣
     */
    private function UserWithholding($withholding_no,$user_id){
        if( $withholding_no!="" ){
          //  调用支付系统的方法 如下：Y/N
            $status ="Y";
            if( $status!='Y' ){
                //用户已经解约代扣协议
                return ApiStatus::CODE_30001;
            }
//            // 更新用户签约协议状态
//            $withholding_table = \hd_load::getInstance()->table('payment/withholding_alipay');
//
//            // 一个合作者ID下同一个支付宝用户只允许签约一次
//            $where = [
//                'user_id' => $this->user_id,
//                'agreement_no' => $this->withholding_no,
//            ];
//            $withholding_info = $withholding_table->field(['id','user_id','partner_id','alipay_user_id','agreement_no','status'])->where( $where )->limit(1)->find();
//            if( !$withholding_info ){// 查询失败
//                \zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[创建订单]查询用户代扣协议失败', $where);
//                throw new ComponnetException('下单查询用户代扣协议信息失败');
//            }
//            // 支付宝用户号
//            $this->alipay_user_id = $withholding_info['alipay_user_id'];
            //--网络查询支付宝接口，获取代扣协议状态----------------------------------
//            try {
//                $withholding = new \alipay\Withholding();
//                $status = $withholding->query( $this->alipay_user_id );
//                if( $status=='Y' ){
//                    $this->flag = true;
//                }else{
//                    $this->get_order_creater()->set_error('[下单][代扣组件]用户已经解约代扣协议');
//                    $this->flag = false;
//                    $this->withholding_no = '';// 用户已解约，清空代扣协议号
//                }
//            } catch (\Exception $exc) {
//                \zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[下单][代扣组件]支付宝接口查询用户代扣协议出现异常', $exc->getMessage());
//                $this->get_order_creater()->set_error('[下单][代扣组件]支付宝接口查询用户代扣协议出现异常');
//                $this->flag = false;
//            }
        }else{
            //未签约代扣协议
            return ApiStatus::CODE_30000;
        }
       return [];

}
    /**
     *  验证渠道
     */
    private function Channel($appid,$channel_id){
         $info =$this->third->GetChannel($appid);
         if(!is_array($info)){
             return $info;
         }

        $this->app_id = intval($info['appid']['id']);
        $this->app_name = $info['appid']['name'];
        $this->app_type = intval($info['appid']['type']);
        $this->app_status = intval($info['appid']['status'])?1:0;
        $this->channel_id = intval($info['_channel']['id']);
        $this->channel_name = $info['_channel']['name'];
        $this->channel_alone_goods = intval($info['_channel']['alone_goods'])?1:0;
        $this->channel_status = intval($info['_channel']['status'])?1:0;

        if( $this->app_status == 0 ){
            return ApiStatus::CODE_30002;
        }
        if( $this->channel_status == 0 ){
            return ApiStatus::CODE_30003;
        }
        if( $this->channel_alone_goods==1 ){
            if( $channel_id != $this->channel_id ){
                return ApiStatus::CODE_30004;
            }
        }
        return [
            'channel' => [
                'app_id' => $this->app_id,
                'app_name' => $this->app_name,
                'app_type' => $this->app_type,
                'app_status' => $this->app_status,
                'channel_id' => $this->channel_id,
                'channel_name' => $this->channel_name,
                'channel_status' => $this->channel_status,
                'channel_alone_goods' => $this->channel_alone_goods,
            ]
        ];
    }



}