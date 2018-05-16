<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\Payment\WithholdingApi;
use App\Order\Modules\Inc\PayInc;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderInstalmentRepository;
use App\Order\Modules\Repository\ThirdInterface;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithholdController extends Controller
{


    //代扣 扣款接口
    public function createpay(Request $request){
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];

        if(!$appid){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $params = filter_array($params, [
            'instalment_id'=>'required',
            'remark'=>'required',
        ]);

        $instalment_id  = $params['instalment_id'];
        $remark         = $params['remark'];

        //开启事务
        DB::beginTransaction();

        // 查询分期信息
        $instalment_info = OrderInstalment::queryByInstalmentId($instalment_id);

        if( !is_array($instalment_info)){
            DB::rollBack();
            // 提交事务
            return apiResponse([], $instalment_info, ApiStatus::$errCodes[$instalment_info]);
        }

        $allow = OrderInstalment::allow_withhold($instalment_id);

        if(!$allow){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71000, "不允许扣款" );
        }

        // 生成交易码
        $trade_no = createNo();

        // 状态在支付中或已支付时，直接返回成功
        if( $instalment_info['status'] == OrderInstalmentStatus::SUCCESS && $instalment_info['status'] = OrderInstalmentStatus::PAYING ){
            return apiResponse($instalment_info,ApiStatus::CODE_0,"success");
        }

        // 扣款交易码
        if( $instalment_info['trade_no']=='' ){
            // 1)记录租机交易码
            $b = OrderInstalment::set_trade_no($instalment_id, $trade_no);
            if( $b === false ){
                DB::rollBack();
                return apiResponse([], ApiStatus::CODE_71002, "租机交易码错误");
            }
            $instalment_info['trade_no'] = $trade_no;
        }
        $trade_no = $instalment_info['trade_no'];

        // 订单
        //查询订单记录
        $order_info = OrderRepository::getInfoById($instalment_info['order_no']);
        if( !$order_info ){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_32002, "数据异常");
        }

        // 查询用户协议
        $third = new ThirdInterface();
        $user_info = $third->GetUser($order_info['user_id']);

        if( !is_array($user_info )){
            DB::rollBack();
            return apiResponse([], $instalment_info, ApiStatus::$errCodes[$instalment_info]);
        }

        // 保存 备注，更新状态
        $data = [
            'remark' => $remark,
            'status' => OrderInstalmentStatus::PAYING,// 扣款中
        ];
        $result = OrderInstalmentRepository::save(['id'=>$instalment_id],$data);
        if(!$result){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71001, '扣款备注保存失败');
        }
        // 商品
        $subject = '订单-'.$instalment_info['order_no'].'-'.$instalment_info['goods_no'].'-第'.$instalment_info['times'].'期扣款';

        // 价格
        $amount = $instalment_info['amount']/100;
        if( $amount<0 ){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71003, '扣款金额不能小于1分');
        }

        //扣款要发送的短信
        $data_sms =[
            'mobile'        => $user_info['mobile'],
            'orderNo'       => $order_info['order_no'],
            'realName'      => $user_info['realname'],
            'goodsName'     => $order_info['goods_name'],
            'zuJin'         => $amount,
        ];

        //判断支付方式
        if( $order_info['pay_type'] == PayInc::MiniAlipay ){
//            $this->zhima_order_confrimed_table =$this->load->table('order2/zhima_order_confirmed');
//            //获取订单的芝麻订单编号
//            $zhima_order_info = $this->zhima_order_confrimed_table->where(['order_no'=>$order_info['order_no']])->find(['lock'=>true]);
//            if(!$zhima_order_info){
//                $this->order_service->rollback();
//                showmessage('该订单没有芝麻订单号！','null',0);
//            }
//            //芝麻小程序下单渠道
//            $Withhold = new \zhima\Withhold();
//            $params['out_order_no'] = $order_info['order_no'];
//            $params['zm_order_no'] = $zhima_order_info['zm_order_no'];
//            $params['out_trans_no'] = $trade_no;
//            $params['pay_amount'] = $amount;
//            $params['remark'] = $remark;
//            $b = $Withhold->withhold( $params );
//            \zuji\debug\Debug::error(Location::L_Trade,"小程序退款请求",$params);
//            //判断请求发送是否成功
//            if($b == 'PAY_SUCCESS'){
//                // 提交事务
//                $this->order_service->commit();
//                \zuji\debug\Debug::error(Location::L_Trade,"小程序退款请求回执",$b);
//                showmessage('小程序扣款操作成功','null',1);
//            }elseif($b =='PAY_FAILED'){
//                $this->order_service->rollback();
//                $this->instalment_failed($instalment_info['fail_num'],$instalment_id,$instalment_info['term'],$data_sms);
//                showmessage("小程序支付失败", 'null');
//
//            }elseif($b == 'PAY_INPROGRESS'){
//                $this->order_service->commit();
//                showmessage("小程序支付处理中请等待", 'null');
//            }else{
//                $this->order_service->rollback();
//                showmessage("小程序支付处理失败", 'null');
//            }


        }else {

            // 支付宝用户的user_id
            $alipay_user_id = $user_info['alipay_user_id'];
            if (!$alipay_user_id) {
                DB::rollBack();
                return apiResponse([], ApiStatus::CODE_71009, '支付宝用户的user_id错误');
            }

            // 代扣协议编号
            $agreement_no = $user_info['withholding_no'];
            if (!$agreement_no) {
                DB::rollBack();
                return apiResponse([], ApiStatus::CODE_71004, '用户代扣协议编号错误');
            }
            // 代扣接口
            $withholding = new WithholdingApi();
            // 扣款
            $withholding_data = [
                'agreement_no'=>$agreement_no,
                'trade_no'=>$trade_no,
                'subject'=>$subject,
                'amount'=>$amount,
            ];
            $withholding_b = $withholding->withhold($appid,$withholding_data);
            if (!$withholding_b) {
                DB::rollBack();
                if (get_error() == "BUYER_BALANCE_NOT_ENOUGH" || get_error() == "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                    OrderInstalment::instalment_failed($instalment_info['fail_num'], $instalment_id, $instalment_info['term'], $data_sms);
                    return apiResponse([], ApiStatus::CODE_71004, '买家余额不足');
                } else {
                    return apiResponse([], ApiStatus::CODE_71006, '扣款失败');
                }
            }

            //发送短信
            SmsApi::sendMessage($data_sms['mobile'], 'hsb_sms_b427f', $data_sms);

            //发送消息通知
            //通过用户id查询支付宝用户id

//            $MessageSingleSendWord = new \alipay\MessageSingleSendWord($alipay_user_id);
//            //查询账单
//            $year = substr($instalment_info['term'], 0, 4);
//            $month = substr($instalment_info['term'], -2);
//            $y = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), 0, 4);
//            $m = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -5, -3);
//            $d = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -2);
//            $message_arr = [
//                'amount' => $amount,
//                'bill_type' => '租金',
//                'bill_time' => $year . '年' . $month . '月1日' . '-' . $y . '年' . $m . '月' . $d . '日',
//                'pay_time' => date('Y-m-d H:i:s'),
//            ];
//            $b = $MessageSingleSendWord->PaySuccess($message_arr);
//            if ($b === false) {
//                \zuji\debug\Debug::error(Location::L_Trade, '发送消息通知PaySuccess', $MessageSingleSendWord->getError());
//                return;
//            }

            // 提交事务
            DB::commit();
            return apiResponse([],ApiStatus::CODE_0,"success");
        }

    }

    /**
     * 多项扣款
     */
    public function multi_createpay(Request $request)
    {
        ini_set('max_execution_time', '0');

        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];

        if (!$appid) {
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $params = filter_array($params, [
            'ids' => 'required',
        ]);


        $ids = $params['ids'];
        $ids = explode(',', $ids);
        if (count($ids) < 1) {
            showmessage('参数错误', 'null');
        }

        foreach ($ids as $instalment_id) {

            if ($instalment_id < 1) {
                Log::error("参数错误");
                continue;
            }
            $remark = "代扣多项扣款";


            $instalment_info = OrderInstalment::queryByInstalmentId($instalment_id);
            if (!is_array($instalment_info)) {
                Log::error("分期信息查询失败");
                continue;
            }

            $allow = OrderInstalment::allow_withhold($instalment_id);
            if (!$allow) {
                Log::error("不允许扣款");
                continue;
            }

            // 生成交易码
            $trade_no = createNo();

            //开启事务
            DB::beginTransaction();

            // 状态在支付中或已支付时，直接返回成功
            if ($instalment_info['status'] == OrderInstalmentStatus::SUCCESS && $instalment_info['status'] = OrderInstalmentStatus::PAYING) {
                continue;
            }

            // 扣款交易码
            if ($instalment_info['trade_no'] == '') {
                // 1)记录租机交易码
                $b = OrderInstalment::set_trade_no($instalment_id, $trade_no);
                if ($b === false) {
                    DB::rollBack();
                    Log::error("租机交易码错误");
                    continue;
                }
                $instalment_info['trade_no'] = $trade_no;
            }
            $trade_no = $instalment_info['trade_no'];

// 订单
            //查询订单记录
            $order_info = OrderRepository::getInfoById($instalment_info['order_no']);
            if (!$order_info) {
                DB::rollBack();
                Log::error("数据异常");
                continue;
            }

            // 查询用户协议
            $third = new ThirdInterface();
            $user_info = $third->GetUser($order_info['user_id']);

            if (!is_array($user_info)) {
                DB::rollBack();
                Log::error("用户信息错误");
                continue;
            }

            // 保存 备注，更新状态
            $data = [
                'remark' => $remark,
                'status' => OrderInstalmentStatus::PAYING,// 扣款中
            ];
            $result = OrderInstalmentRepository::save(['id' => $instalment_id], $data);
            if (!$result) {
                DB::rollBack();
                Log::error("扣款备注保存失败");
                continue;
            }
            // 商品
            $subject = '订单-' . $instalment_info['order_no'] . '-' . $instalment_info['goods_no'] . '-第' . $instalment_info['times'] . '期扣款';

            // 价格
            $amount = $instalment_info['amount'] / 100;
            if ($amount < 0) {
                DB::rollBack();
                Log::error("扣款金额不能小于1分");
                continue;
            }

            //扣款要发送的短信
            $data_sms = [
                'mobile' => $user_info['mobile'],
                'orderNo' => $order_info['order_no'],
                'realName' => $user_info['realname'],
                'goodsName' => $order_info['goods_name'],
                'zuJin' => $amount,
            ];

            //判断支付方式
            //判断支付方式
            if ($order_info['pay_type'] == PayInc::MiniAlipay) {
//            $this->zhima_order_confrimed_table =$this->load->table('order2/zhima_order_confirmed');
//            //获取订单的芝麻订单编号
//            $zhima_order_info = $this->zhima_order_confrimed_table->where(['order_no'=>$order_info['order_no']])->find(['lock'=>true]);
//            if(!$zhima_order_info){
//                $this->order_service->rollback();
//                showmessage('该订单没有芝麻订单号！','null',0);
//            }
//            //芝麻小程序下单渠道
//            $Withhold = new \zhima\Withhold();
//            $params['out_order_no'] = $order_info['order_no'];
//            $params['zm_order_no'] = $zhima_order_info['zm_order_no'];
//            $params['out_trans_no'] = $trade_no;
//            $params['pay_amount'] = $amount;
//            $params['remark'] = $remark;
//            $b = $Withhold->withhold( $params );
//            \zuji\debug\Debug::error(Location::L_Trade,"小程序退款请求",$params);
//            //判断请求发送是否成功
//            if($b == 'PAY_SUCCESS'){
//                // 提交事务
//                $this->order_service->commit();
//                \zuji\debug\Debug::error(Location::L_Trade,"小程序退款请求回执",$b);
//                showmessage('小程序扣款操作成功','null',1);
//            }elseif($b =='PAY_FAILED'){
//                $this->order_service->rollback();
//                $this->instalment_failed($instalment_info['fail_num'],$instalment_id,$instalment_info['term'],$data_sms);
//                showmessage("小程序支付失败", 'null');
//
//            }elseif($b == 'PAY_INPROGRESS'){
//                $this->order_service->commit();
//                showmessage("小程序支付处理中请等待", 'null');
//            }else{
//                $this->order_service->rollback();
//                showmessage("小程序支付处理失败", 'null');
//            }


            } else {

                // 支付宝用户的user_id
                $alipay_user_id = $user_info['alipay_user_id'];
                if (!$alipay_user_id) {
                    DB::rollBack();
                    Log::error("支付宝用户的user_id错误");
                    continue;
                }

                // 代扣协议编号
                $agreement_no = $user_info['withholding_no'];
                if (!$agreement_no) {
                    DB::rollBack();
                    Log::error("用户代扣协议编号错误");
                    continue;
                }
                // 代扣接口
                $withholding = new WithholdingApi();
                // 扣款
                $withholding_data = [
                    'agreement_no' => $agreement_no,
                    'trade_no' => $trade_no,
                    'subject' => $subject,
                    'amount' => $amount,
                ];
                $withholding_b = $withholding->withhold($appid, $withholding_data);
                if (!$withholding_b) {
                    if (get_error() == "BUYER_BALANCE_NOT_ENOUGH" || get_error() == "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                        OrderInstalment::instalment_failed($instalment_info['fail_num'], $instalment_id, $instalment_info['term'], $data_sms);
                        Log::error("买家余额不足");
                        continue;
                    }
                }

                //发送短信
                SmsApi::sendMessage($data_sms['mobile'], 'hsb_sms_b427f', $data_sms);

                //发送消息通知
                //通过用户id查询支付宝用户id

//            $MessageSingleSendWord = new \alipay\MessageSingleSendWord($alipay_user_id);
//            //查询账单
//            $year = substr($instalment_info['term'], 0, 4);
//            $month = substr($instalment_info['term'], -2);
//            $y = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), 0, 4);
//            $m = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -5, -3);
//            $d = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -2);
//            $message_arr = [
//                'amount' => $amount,
//                'bill_type' => '租金',
//                'bill_time' => $year . '年' . $month . '月1日' . '-' . $y . '年' . $m . '月' . $d . '日',
//                'pay_time' => date('Y-m-d H:i:s'),
//            ];
//            $b = $MessageSingleSendWord->PaySuccess($message_arr);
//            if ($b === false) {
//                \zuji\debug\Debug::error(Location::L_Trade, '发送消息通知PaySuccess', $MessageSingleSendWord->getError());
//                return;
//            }
            }
            // 提交事务
            DB::commit();
        }
        return apiResponse([], ApiStatus::CODE_0, "success");

    }


}
