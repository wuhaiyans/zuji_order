<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\Payment\WithholdingApi;
use App\Lib\PayInc;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderInstalmentRepository;
use App\Order\Modules\Repository\ThirdInterface;

class InstalmentController extends Controller
{


    // 创建订单分期
    public function create(Request $request){
        $request = $request->all();

        $order      = $request['params']['order'];
        $sku        = $request['params']['sku'];
        $coupon     = !empty($request['params']['coupon']) ? $request['params']['coupon'] : "";
        $user       = $request['params']['user'];
        //获取goods_no
        $order = filter_array($order, [
            'order_no'=>'required',
        ]);
        if(count($order) < 1){
            return apiResponse([],ApiStatus::CODE_20001,"order_no不能为空");
        }

        //获取sku
        $sku = filter_array($sku, [
            'goods_no'=>'required',
            'zuqi'=>'required',
            'zuqi_type'=>'required',
            'all_amount'=>'required',
            'amount'=>'required',
            'yiwaixian'=>'required',
            'zujin'=>'required',
            'pay_type'=>'required',
        ]);
        if(count($sku) < 8){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误");
        }

        filter_array($coupon, [
            'discount_amount' => 'required',    //【必须】int；订单号
            'coupon_type' => 'required',    //【必须】int；订单号
        ]);


        $user = filter_array($user, [
            'withholding_no' => 'required',    //【必须】int；订单号
        ]);
        if(empty($user)){
            return apiResponse([],ApiStatus::CODE_20001,"用户代扣协议号不能为空");
        }

        $params = [
            'order'     => $order,
            'sku'       => $sku,
            'coupon'    => $coupon,
            'user'      => $user,
        ];

        $res = OrderInstalment::create($params);

        if(!$res){
            return apiResponse([],ApiStatus::CODE_20001,"用户代扣协议号不能为空");
        }

        return apiResponse([],ApiStatus::CODE_0,"success");

    }

    //分期列表接口
    public function instalment_list(Request $request){
        $request = $request->all()['params'];

        $request = filter_array($request, [
            'goods_no'=>'required',
            'order_no'=>'required',
        ]);
        if(empty($request['order_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"order_no参数错误");
        }


        $code = new OrderInstalment();
        $list = $code->queryList($request);

        if(!is_array($list)){
            return apiResponse([], $list, ApiStatus::$errCodes[$list]);
        }
        return apiResponse($list,ApiStatus::CODE_0,"success");

    }

    //代扣 扣款接口
    public function createpay(Request $request){
        $request = $request->all()['params'];
        $request = filter_array($request, [
            'instalment_id'=>'required',
            'user_id'=>'required',
            'remark'=>'required',
        ]);

        $instalment_id  = $request['instalment_id'];
        $user_id        = $request['user_id'];
        $remark         = $request['remark'];

        // 查询分期信息
        $instalment_info = OrderInstalment::queryByInstalmentId($instalment_id);

        if( !is_array($instalment_info)){
            // 提交事务
            return apiResponse([], $instalment_info, ApiStatus::$errCodes[$instalment_info]);
        }

        $allow = OrderInstalment::allow_withhold($instalment_id);

        if(!$allow){
            return apiResponse([], ApiStatus::CODE_71000, "不允许扣款" );
        }
        // 生成交易码
        $trade_no = createNo();


        // 状态在支付中或已支付时，直接返回成功
        if( $instalment_info['status'] == OrderInstalmentStatus::SUCCESS && $instalment_info['status'] = OrderInstalmentStatus::PAYING ){
            return apiResponse($list,ApiStatus::CODE_0,"success");
        }

        // 扣款交易码
        if( $instalment_info['trade_no']=='' ){
            // 1)记录租机交易码
            $b = OrderInstalment::set_trade_no($instalment_id, $trade_no);
            if( $b === false ){
                return apiResponse([], ApiStatus::CODE_71002, "租机交易码错误");
            }
            $instalment_info['trade_no'] = $trade_no;
        }
        $trade_no = $instalment_info['trade_no'];

        // 订单
        //查询订单记录
        $order_info = OrderRepository::getInfoById($instalment_info['order_no']);
        if( !$order_info ){
            return apiResponse([], ApiStatus::CODE_32002, "数据异常");
        }

        // 查询用户协议
        $third = new ThirdInterface();
        $user_info = $third->GetUser($order_info['user_id']);

        if( !is_array($user_info )){
            return apiResponse([], $instalment_info, ApiStatus::$errCodes[$instalment_info]);
        }

        // 保存 备注，更新状态
        $data = [
            'remark' => $remark,
            'status' => OrderInstalmentStatus::PAYING,// 扣款中
        ];
        $result = OrderInstalmentRepository::save(['id'=>$instalment_id],$data);
        if(!$result){
            return apiResponse([], ApiStatus::CODE_71001, '扣款备注保存失败');
        }
        // 商品
        $subject = '订单-'.$instalment_info['order_no'].'-'.$instalment_info['goods_no'].'-第'.$instalment_info['times'].'期扣款';

        // 价格
        $amount = $instalment_info['amount']/100;
        if( $amount<0 ){
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
            $this->zhima_order_confrimed_table =$this->load->table('order2/zhima_order_confirmed');
            //获取订单的芝麻订单编号
            $zhima_order_info = $this->zhima_order_confrimed_table->where(['order_no'=>$order_info['order_no']])->find(['lock'=>true]);
            if(!$zhima_order_info){
                $this->order_service->rollback();
                showmessage('该订单没有芝麻订单号！','null',0);
            }
            //芝麻小程序下单渠道
            $Withhold = new \zhima\Withhold();
            $params['out_order_no'] = $order_info['order_no'];
            $params['zm_order_no'] = $zhima_order_info['zm_order_no'];
            $params['out_trans_no'] = $trade_no;
            $params['pay_amount'] = $amount;
            $params['remark'] = $remark;
            $b = $Withhold->withhold( $params );
            \zuji\debug\Debug::error(Location::L_Trade,"小程序退款请求",$params);
            //判断请求发送是否成功
            if($b == 'PAY_SUCCESS'){
                // 提交事务
                $this->order_service->commit();
                \zuji\debug\Debug::error(Location::L_Trade,"小程序退款请求回执",$b);
                showmessage('小程序扣款操作成功','null',1);
            }elseif($b =='PAY_FAILED'){
                $this->order_service->rollback();
                $this->instalment_failed($instalment_info['fail_num'],$instalment_id,$instalment_info['term'],$data_sms);
                showmessage("小程序支付失败", 'null');

            }elseif($b == 'PAY_INPROGRESS'){
                $this->order_service->commit();
                showmessage("小程序支付处理中请等待", 'null');
            }else{
                $this->order_service->rollback();
                showmessage("小程序支付处理失败", 'null');
            }


        }else {

            // 代扣协议编号
            $agreement_no = $user_info['withholding_no'];
            if (!$agreement_no) {
                return apiResponse([], ApiStatus::CODE_71004, '用户代扣协议编号错误');
            }
            // 代扣接口
            $withholding = new WithholdingApi();
            // 扣款
//            $b = $withholding->withhold($appid,$parmas,$agreement_no, $trade_no, $subject, $amount);
            $b = $withholding->withhold($appid,$parmas);
            if (!$b) {
                $this->order_service->rollback();
                if (get_error() == "BUYER_BALANCE_NOT_ENOUGH" || get_error() == "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                    $this->instalment_failed($instalment_info['fail_num'], $instalment_id, $instalment_info['term'], $data_sms);
                    showmessage("买家余额不足", 'null');
                } else {
                    showmessage(get_error(), 'null');
                }
            }

            //发送短信
            \zuji\sms\SendSms::instalment_pay($data_sms);

            //发送消息通知
//            //通过用户id查询支付宝用户id
//            $this->certification_alipay = $this->load->service('member2/certification_alipay');
//            $to_user_id = $this->certification_alipay->get_last_info_by_user_id($order_info['user_id']);
//            if (!empty($to_user_id['user_id'])) {
//                $MessageSingleSendWord = new \alipay\MessageSingleSendWord($to_user_id['user_id']);
//                //查询账单
//                $year = substr($instalment_info['term'], 0, 4);
//                $month = substr($instalment_info['term'], -2);
//                $y = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), 0, 4);
//                $m = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -5, -3);
//                $d = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -2);
//                $message_arr = [
//                    'amount' => $amount,
//                    'bill_type' => '租金',
//                    'bill_time' => $year . '年' . $month . '月1日' . '-' . $y . '年' . $m . '月' . $d . '日',
//                    'pay_time' => date('Y-m-d H:i:s'),
//                ];
//                $b = $MessageSingleSendWord->PaySuccess($message_arr);
//                if ($b === false) {
//                    \zuji\debug\Debug::error(Location::L_Trade, '发送消息通知PaySuccess', $MessageSingleSendWord->getError());
//                    return;
//                }
//            }
            // 提交事务
            $this->order_service->commit();
            showmessage('操作成功', 'null', 1);

        }
        p($allow);


    }





}
