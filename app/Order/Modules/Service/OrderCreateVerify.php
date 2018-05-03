<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use App\Lib\PayInc;

/**
 * 下单验证类
 */
class OrderCreateVerify
{
    public function Verify($pay_type,$user_info){
        if($pay_type == PayInc::WithhodingPay){
            $res =$this->UserWithholding($user_info['withholding_no'],$user_info['id']);
            if($res != ApiStatus::CODE_0){
                return $res;
            }
        }

    }
    /**
     *  验证代扣
     */
    private function UserWithholding($withholding_no,$user_id){
        if( $withholding_no!="" ){
          //  调用支付系统的方法 如下：Y/N
            $status ="N";
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
       return ApiStatus::CODE_0;

}
    /**
     *
     */
}