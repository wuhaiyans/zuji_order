<?php
namespace App\Order\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderGiveback;
use App\Order\Modules\Inc\OrderGivebackStatus;
use App\Order\Modules\Service\OrderGoods;
use App\Order\Modules\Service\OrderGoodsInstalment;
use App\Order\Modules\Service\OrderWithhold;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Repository\Order\Goods;

/**
 * 小程序还机处理接口
 * Class MiniGivebackController
 * @package App\Order\Controllers\Api\v1
 * @author zhangjinhui
 */

class MiniGivebackController extends Controller
{
    /**
     * 小程序还机支付赔偿金额接口
     * @param $param
     * @return array
     */
    public function givebackPay(Request $request){

        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
//        \App\Lib\Common\LogApi::debug('调用主动支付接口',$params);
        $operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
        if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
            return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
        }
        $paramsArr = isset($params['params'])? $params['params'] :'';
        $rules = [
            'goods_no'     => 'required',//商品编号
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_91000,$validator->errors()->first());
        }
        $goodsNo = $paramsArr['goods_no'];
        //-+--------------------------------------------------------------------
        // | 业务处理：获取判断当前还机单状态、更新还机单状态
        //-+--------------------------------------------------------------------
        //创建服务层对象
        $orderGivebackService = new OrderGiveback();
        //获取还机单基本信息
        $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($goodsNo);
        if( !$orderGivebackInfo ){
            return apiResponse([], get_code(), get_msg());
        }
        $orderGoodsInfo = $this->__getOrderGoodsInfo($orderGivebackInfo['goods_no']);
        if( !$orderGoodsInfo ) {
            return apiResponse([], get_code(), get_msg());
        }
        //获取芝麻订单信息
        $orderMiniInfo = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo($orderGivebackInfo['order_no']);
        if( !$orderMiniInfo ) {
            return apiResponse([], get_code(), get_msg());
        }
        $paramsArr['zm_app_id'] = $orderMiniInfo['app_id'];//小程序APPID
        //开启事务
        DB::beginTransaction();
        try {
            //-+------------------------------------------------------------------------------
            // |收货时：查询未完成分期直接进行代扣，并记录代扣状态
            //-+------------------------------------------------------------------------------
            //获取当前商品未完成分期列表数据
            $instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$paramsArr['goods_no'],'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
            if( !empty($instalmentList[$paramsArr['goods_no']]) ){
                //发送短信
                $notice = new \App\Order\Modules\Service\OrderNotice(
                    \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
                    $paramsArr['goods_no'],
                    "GivebackConfirmDelivery");
                $notice->notify();
                //未扣款代扣全部执行
                foreach ($instalmentList[$paramsArr['goods_no']] as $instalmentInfo) {
                    $b = OrderWithhold::instalment_withhold($instalmentInfo['id']);
                    if(!$b){
                        return apiResponse([], ApiStatus::CODE_35006, \App\Lib\Payment\mini\MiniApi::getError());
                    }
                }
                //修改代扣状态为代扣中
                $data['withhold_status'] = OrderGivebackStatus::WITHHOLD_STATUS_IN_WITHHOLD;
                //更新还机单
                $orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);
                if($orderGivebackResult){
                    return apiResponse([], ApiStatus::CODE_0, '小程序分期金额支付请求成功');
                }else{
                    return apiResponse([], ApiStatus::CODE_35006, '小程序分期金额修改支付状态失败');
                }

            }else{
                $arr = [
                    'zm_order_no'=>$orderMiniInfo['zm_order_no'],
                    'out_order_no'=>$orderGivebackInfo['order_no'],
                    'pay_amount'=>$orderGivebackInfo['compensate_amount'],
                    'remark'=>$orderGivebackInfo['giveback_no'],
                    'app_id'=>$paramsArr['zm_app_id'],
                ];
                //租金已支付（扣除赔偿金，关闭订单）
                //判断是否有请求过（芝麻支付接口）
                $where = [
                    'out_trans_no'=>$orderGivebackInfo['giveback_no'],
                    'order_operate_type'=>'FINISH',
                ];
                $orderMiniCreditPayInfo = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::getMiniCreditPayInfo($where);
                if( empty($orderMiniCreditPayInfo) ) {
                    $arr['out_trans_no'] = $orderGivebackInfo['giveback_no'];
                }else{
                    $arr['out_trans_no'] = $orderMiniCreditPayInfo['out_trans_no'];
                }
                $orderCloseResult = \App\Lib\Payment\mini\MiniApi::OrderClose($arr);
                //提交事务
                DB::commit();
                if( $orderCloseResult['code'] == 10000  ){
                    return apiResponse([], ApiStatus::CODE_0, '小程序赔偿金支付请求成功');
                }else{
                    return apiResponse([], ApiStatus::CODE_35006, \App\Lib\Payment\mini\MiniApi::getError().$orderCloseResult['msg']);
                }
            }
        }catch(\Exception $ex){
            //事务回滚
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_94000,$ex->getMessage());
        }
    }

    /**
     * 还机支付状态查询接口
     * @params request
     * @return array
     */
    public function givebackPayStatus( Request $request ){
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
        if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
            return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
        }
        $paramsArr = isset($params['params'])? $params['params'] :'';
        $rules = [
            'goods_no'     => 'required',//商品编号
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_91000,$validator->errors()->first());
        }
        //-+--------------------------------------------------------------------
        // | 业务处理：获取判断当前还机单状态、更新还机单状态
        //-+--------------------------------------------------------------------
        //创建服务层对象
        $orderGivebackService = new OrderGiveback();
        //获取还机单基本信息
        $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($paramsArr['goods_no']);
        if( !$orderGivebackInfo ){
            return apiResponse([], get_code(), get_msg());
        }
        //还机单存在判断支付状态
        return apiResponse([
            'payment_status'=>$orderGivebackInfo['payment_status']
        ], ApiStatus::CODE_0);
    }

    /**
     * 还机确认收货结果
     * @param $params
     */
    public function givebackConfirmEvaluation( $params ) {
        \App\Lib\Common\LogApi::notify('小程序还机确认收货结果',[
            $params,
        ]);
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
        if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
            return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
        }
        $paramsArr = isset($params['params'])? $params['params'] :'';
        $rules = [
            'goods_no'     => 'required',//商品编号
            'evaluation_status'     => 'required',//检测状态【1：合格；2：不合格】
            'evaluation_time'     => 'required',//检测时间
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_91000,$validator->errors()->first());
        }
        if( !in_array($paramsArr['evaluation_status'], [OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED,OrderGivebackStatus::EVALUATION_STATUS_QUALIFIED])  ){
            return apiResponse([],ApiStatus::CODE_91000,'检测状态参数值错误!');
        }
        if( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED && (empty($paramsArr['evaluation_remark']) || empty($paramsArr['compensate_amount'])) ){
            return apiResponse([],ApiStatus::CODE_91000,'检测不合格时：检测备注和赔偿金额均不能为空!');
        }
        $paramsArr['compensate_amount'] = isset($paramsArr['compensate_amount'])? floatval($paramsArr['compensate_amount']):0;
        $paramsArr['evaluation_remark'] = isset($paramsArr['evaluation_remark'])?strval($paramsArr['evaluation_remark']):'';
        $goodsNo = $paramsArr['goods_no'];//商品编号提取

        //-+--------------------------------------------------------------------
        // | 业务处理
        //-+--------------------------------------------------------------------

        //创建商品服务层对象
        $orderGoodsService = new OrderGoods();
        $orderGivebackService = new OrderGiveback();
        //-+--------------------------------------------------------------------
        // | 业务处理：判断是否需要支付【1有无未完成分期，2检测不合格的赔偿】
        //-+--------------------------------------------------------------------
        //获取商品信息
        $orderGoodsInfo = $orderGoodsService->getGoodsInfo($goodsNo);
        if( !$orderGoodsInfo ) {
            return apiResponse([], get_code(), get_msg());
        }
        //获取还机单信息
        $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($goodsNo);
        if( !$orderGivebackInfo ) {
            return apiResponse([], get_code(), get_msg());
        }
        //获取芝麻订单信息
        $orderMiniInfo = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo($orderGivebackInfo['order_no']);
        if( !$orderMiniInfo ) {
            return apiResponse([], get_code(), get_msg());
        }
        if( $orderGivebackInfo['status'] != OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK ){
            return apiResponse([], ApiStatus::CODE_92500, '当前还机单不处于待检测状态，不能进行检测处理!');
        }

        //获取当前商品未完成分期列表数据
        $instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$goodsNo,'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
        //剩余分期需要支付的总金额、还机需要支付总金额
        $instalmentAmount = $givebackNeedPay = 0;
        //剩余分期数
        $instalmentNum = 0;
        if( !empty($instalmentList[$goodsNo]) ){
            foreach ($instalmentList[$goodsNo] as $instalmentInfo) {
                if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]) ){
                    $instalmentAmount += $instalmentInfo['amount'];
                    $instalmentNum++;
                }
            }
        }

        //拼接相关参数到paramsArr数组
        $paramsArr['order_no'] = $orderGivebackInfo['order_no'];//订单编号
        $paramsArr['user_id'] = $orderGivebackInfo['user_id'];//用户id
        $paramsArr['giveback_no'] = $orderGivebackInfo['giveback_no'];//还机单编号

        $paramsArr['instalment_num'] = $instalmentNum;//需要支付的分期的期数
        $paramsArr['instalment_amount'] = $instalmentAmount;//需要支付的分期的金额
        $paramsArr['yajin'] = $orderGoodsInfo['yajin'];//押金金额
        $paramsArr['zm_order_no'] = $orderMiniInfo['zm_order_no'];//芝麻订单号
        $paramsArr['zm_app_id'] = $orderMiniInfo['app_id'];//小程序APPID
        //开启事务
        DB::beginTransaction();
        try{
            //-+----------------------------------------------------------------
            // | 检测合格-代扣成功(无剩余分期)
            //-+----------------------------------------------------------------
            if( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_QUALIFIED && !$instalmentNum ){
                $dealResult = $this->__dealEvaYesWitYes($paramsArr, $orderGivebackService, $status);
            }
            //-+----------------------------------------------------------------
            // | 检测合格-代扣不成功(有剩余分期)
            //-+----------------------------------------------------------------
            elseif ( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_QUALIFIED && $instalmentNum ) {
                $dealResult = $this->__dealEvaYesWitNo($paramsArr, $orderGivebackService, $status);
            }

            //-+----------------------------------------------------------------
            // | 检测不合格-代扣成功(无剩余分期)
            //-+----------------------------------------------------------------

            elseif ( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED && !$instalmentNum ) {
                $dealResult = $this->__dealEvaNoWitYes($paramsArr, $orderGivebackService, $status);
            }

            //-+----------------------------------------------------------------
            // | 检测不合格-代扣不成功(有剩余分期)
            //-+----------------------------------------------------------------
            elseif ( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED && $instalmentNum ) {
                $dealResult = $this->__dealEvaNoWitNo($paramsArr, $orderGivebackService, $status);
            }
            //-+----------------------------------------------------------------
            // | 不应该出现的结果，直接返回错误
            //-+----------------------------------------------------------------
            else {
                throw new \Exception('这简直就是一个惊天大bug，天上有漏洞----->你需要一个女娲—.—');
            }
            //更新还机表状态失败回滚
            if( !$dealResult ){
                DB::rollBack();
                return apiResponse([], get_code(), get_msg());
            }
            //记录日志
            $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
                'order_no'=>$orderGivebackInfo['order_no'],
                'action'=>'还机单检测',
                'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//此处用常量
                'business_no'=>$orderGivebackInfo['giveback_no'],
                'goods_no'=>$orderGivebackInfo['goods_no'],
                'operator_id'=>$operateUserInfo['uid'],
                'operator_name'=>$operateUserInfo['username'],
                'operator_type'=>$operateUserInfo['type']==1?\App\Lib\PublicInc::Type_Admin:\App\Lib\PublicInc::Type_User,//此处用常量
                'msg'=>'还机单提交检测结果',
            ]);
            if( !$goodsLog ){
                DB::rollBack();
                return apiResponse([],ApiStatus::CODE_92700,'设备日志生成失败！');
            }
        } catch (\Exception $ex) {
            //回滚事务
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_94000, $ex->getMessage());
        }
        //提交事务
        DB::commit();

//		$return  = $this->givebackReturn(['status'=>"D","status_text"=>"完成"]);
        return apiResponse([], ApiStatus::CODE_0, '检测结果同步成功');
    }

    /**
     * 检测结果处理【检测合格-代扣成功(无剩余分期)】
     * @param OrderGiveback $orderGivebackService 还机单服务对象
     * @param array $paramsArr 业务处理的必要参数数组
     * $paramsArr = [<br/>
     *		'goods_no' => '',//商品编号	【必须】<br/>
     *		'evaluation_status' => '',//检测结果 【必须】<br/>
     *		'evaluation_time' => '',//检测时间 【必须】<br/>
     *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
     *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
     *		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
     *		'order_no' => '',//订单编号 【必须】<br/>
     *		'user_id' => '',//用户id 【必须】<br/>
     *		'giveback_no' => '',//还机单编号 【必须】<br/>
     *		'instalment_num' => '',//剩余分期期数 【必须】【可为0】<br/>
     *		'instalment_amount' => '',//剩余分期总金额 【必须】【可为0】<br/>
     *		'yajin' => '',//押金金额 【必须】【可为0】<br/>
     * ]
     * @param int $status 还机单最新还机单状态
     * @return bool 处理结果【true:处理完成;false:处理出错】
     */
    private function __dealEvaYesWitYes( $paramsArr, OrderGiveback $orderGivebackService, &$status ) {
        //初始化更新还机单的数据
        $data = $this->__givebackUpdateDataInit($paramsArr);
        //-+--------------------------------------------------------------------
        // | 有押金->退押金处理（小程序关闭订单解冻押金）
        //-+--------------------------------------------------------------------
        $arr = [
            'zm_order_no'=>$paramsArr['zm_order_no'],
            'out_order_no'=>$paramsArr['order_no'],
            'pay_amount'=>$paramsArr['compensate_amount'],
            'remark'=>$paramsArr['giveback_no'],
            'app_id'=>$paramsArr['zm_app_id'],
        ];
        //判断是否有请求过（芝麻支付接口）
        $where = [
            'out_trans_no'=>$paramsArr['giveback_no'],
            'order_operate_type'=>'FINISH',
        ];
        $orderMiniCreditPayInfo = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::getMiniCreditPayInfo($where);
        if( $orderMiniCreditPayInfo ) {
            $arr['out_trans_no'] = $orderMiniCreditPayInfo['out_trans_no'];
        }else{
            $arr['out_trans_no'] = $paramsArr['giveback_no'];
        }
        $orderCloseResult = \App\Lib\Payment\mini\MiniApi::OrderClose($arr);
        if( $orderCloseResult['code'] != 10000  ){
            return false;
        }
        //拼接需要更新还机单状态
        $data['status'] = $status =$goodsStatus = OrderGivebackStatus::STATUS_DEAL_WAIT_RETURN_DEPOSTI;
        $data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
        $data['payment_time'] = time();
        $data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_IN_RETURN;
        \App\Lib\Common\LogApi::notify('检测合格-代扣成功(无剩余分期)',[
            $paramsArr,
            $data
        ]);
        //更新还机单
        $orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);
        //发送短信
        $notice = new \App\Order\Modules\Service\OrderNotice(
            \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
            $paramsArr['goods_no'],
            "GivebackWithholdSuccess");
        $notice->notify();
        return $orderGivebackResult ? true : false;
    }

    /**
     * 检测结果处理【检测合格-代扣失败(有剩余分期)】
     * @param OrderGiveback $orderGivebackService 还机单服务对象
     * @param array $paramsArr 业务处理的必要参数数组
     * $paramsArr = [<br/>
     *		'goods_no' => '',//商品编号	【必须】<br/>
     *		'evaluation_status' => '',//检测结果 【必须】<br/>
     *		'evaluation_time' => '',//检测时间 【必须】<br/>
     *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
     *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
     *		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
     *		'order_no' => '',//订单编号 【必须】<br/>
     *		'user_id' => '',//用户id 【必须】<br/>
     *		'giveback_no' => '',//还机单编号 【必须】<br/>
     *		'instalment_num' => '',//剩余分期期数 【必须】【可为0】<br/>
     *		'instalment_amount' => '',//剩余分期总金额 【必须】【可为0】<br/>
     *		'yajin' => '',//押金金额 【必须】【可为0】<br/>
     * ]
     * @param int $status 还机单最新还机单状态
     * @return bool 处理结果【true:处理完成;false:处理出错】
     */
    private function __dealEvaYesWitNo( $paramsArr, OrderGiveback $orderGivebackService, &$status ) {
        //初始化更新还机单的数据
        $data = $this->__givebackUpdateDataInit($paramsArr);
        //-+--------------------------------------------------------------------
        // | 订单关闭扣除用户租金，更新还机单
        //-+--------------------------------------------------------------------
        //获取当前商品未完成分期列表数据
        $instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$paramsArr['goods_no'],'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
        if( !empty($instalmentList[$paramsArr['goods_no']]) ){
            //发送短信
            $notice = new \App\Order\Modules\Service\OrderNotice(
                \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
                $paramsArr['goods_no'],
                "GivebackConfirmDelivery");
            $notice->notify();
            //未扣款代扣全部执行
            foreach ($instalmentList[$paramsArr['goods_no']] as $instalmentInfo) {
                OrderWithhold::instalment_withhold($instalmentInfo['id']);
            }
        }
        //拼接需要更新还机单状态
        $data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
        $data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
        $data['payment_time'] = time();
        //更新还机单
        \App\Lib\Common\LogApi::notify('检测合格-代扣失败(有剩余分期)',[
            $paramsArr,
            $data
        ]);
        $orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);
        //发送短信
        $notice = new \App\Order\Modules\Service\OrderNotice(
            \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
            $paramsArr['goods_no'],
            "GivebackWithholdFail");
        $notice->notify();

        return $orderGivebackResult ? true : false;
    }

    /**
     * 检测结果处理【检测不合格-代扣成功(无剩余分期)】
     * @param OrderGiveback $orderGivebackService 还机单服务对象
     * @param array $paramsArr 业务处理的必要参数数组
     * $paramsArr = [<br/>
     *		'goods_no' => '',//商品编号	【必须】<br/>
     *		'evaluation_status' => '',//检测结果 【必须】<br/>
     *		'evaluation_time' => '',//检测时间 【必须】<br/>
     *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
     *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
     *		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
     *		'order_no' => '',//订单编号 【必须】<br/>
     *		'user_id' => '',//用户id 【必须】<br/>
     *		'giveback_no' => '',//还机单编号 【必须】<br/>
     *		'instalment_num' => '',//剩余分期期数 【必须】【可为0】<br/>
     *		'instalment_amount' => '',//剩余分期总金额 【必须】【可为0】<br/>
     *		'yajin' => '',//押金金额 【必须】【可为0】<br/>
     * ]
     * @param int $status 还机单最新还机单状态
     * @return bool 处理结果【true:处理完成;false:处理出错】
     */
    private function __dealEvaNoWitYes( $paramsArr, OrderGiveback $orderGivebackService, &$status ) {
        //初始化更新还机单的数据
        $data = $this->__givebackUpdateDataInit($paramsArr);
        //-+--------------------------------------------------------------------
        // | 业务验证
        //-+--------------------------------------------------------------------
        //拼接需要更新还机单状态更新还机单状态
        $data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
        $data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_IN_PAY;
        $data['payment_time'] = time();
        //发送短信
        $notice = new \App\Order\Modules\Service\OrderNotice(
            \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
            $paramsArr['goods_no'],
            'GivebackEvaNoWitYesEno',
            ['amount' => $paramsArr['compensate_amount'] ]);
        $notice->notify();
        \App\Lib\Common\LogApi::notify('检测不合格-代扣成功(无剩余分期)',[
            $paramsArr,
            $data
        ]);
        //更新还机单
        $orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);
        return $orderGivebackResult ? true : false;
    }

    /**
     * 检测结果处理【检测不合格-代扣失败(有剩余分期)】
     * @param OrderGiveback $orderGivebackService 还机单服务对象
     * @param array $paramsArr 业务处理的必要参数数组
     * $paramsArr = [<br/>
     *		'goods_no' => '',//商品编号	【必须】<br/>
     *		'evaluation_status' => '',//检测结果 【必须】<br/>
     *		'evaluation_time' => '',//检测时间 【必须】<br/>
     *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
     *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
     *		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
     *		'order_no' => '',//订单编号 【必须】<br/>
     *		'user_id' => '',//用户id 【必须】<br/>
     *		'giveback_no' => '',//还机单编号 【必须】<br/>
     *		'instalment_num' => '',//剩余分期期数 【必须】【可为0】<br/>
     *		'instalment_amount' => '',//剩余分期总金额 【必须】【可为0】<br/>
     *		'yajin' => '',//押金金额 【必须】【可为0】<br/>
     * ]
     * @param int $status 还机单最新还机单状态
     * @return bool 处理结果【true:处理完成;false:处理出错】
     */
    private function __dealEvaNoWitNo( $paramsArr, OrderGiveback $orderGivebackService, &$status ) {
        //初始化更新还机单的数据
        $data = $this->__givebackUpdateDataInit($paramsArr);
        //-+--------------------------------------------------------------------
        // | 生成支付单，更新还机单
        //-+--------------------------------------------------------------------
        //获取当前商品未完成分期列表数据
        $instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$paramsArr['goods_no'],'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
        if( !empty($instalmentList[$paramsArr['goods_no']]) ){
            //发送短信
            $notice = new \App\Order\Modules\Service\OrderNotice(
                \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
                $paramsArr['goods_no'],
                "GivebackConfirmDelivery");
            $notice->notify();
            //未扣款代扣全部执行
            foreach ($instalmentList[$paramsArr['goods_no']] as $instalmentInfo) {
                OrderWithhold::instalment_withhold($instalmentInfo['id']);
            }
        }
        //拼接需要更新还机单状态
        $data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
        $data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_IN_PAY;
        $data['payment_time'] = time();
        if($paramsArr['yajin'] < $paramsArr['compensate_amount']){
            $smsModel = "GivebackEvaNoWitNoEnoNo";
        }else{
            $smsModel = "GivebackEvaNoWitNoEno";
        }
        //发送短信
        $notice = new \App\Order\Modules\Service\OrderNotice(
            \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
            $paramsArr['goods_no'],
            $smsModel,
            ['amount' => $paramsArr['compensate_amount'] ]);
        $notice->notify();
        \App\Lib\Common\LogApi::notify('检测不合格-代扣失败(有剩余分期)',[
            $paramsArr,
            $data
        ]);
        //更新还机单
        $orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);
        return $orderGivebackResult ? true : false;
    }

    /**
     * 获取还机商品信息
     * @param $goodsNo
     * @return array
     */
    private function __getOrderGoodsInfo( $goodsNo ){

        //获取商品基础数据
        //创建商品服务层对象
        $orderGoodsService = new OrderGoods();
        $orderGoodsInfo = $orderGoodsService->getGoodsInfo($goodsNo);
        if( !$orderGoodsInfo ) {
            return [];
        }
        //商品信息解析
        $orderGoodsInfo['goods_specs'] = filterSpecs($orderGoodsInfo['specs']);//商品规格信息
        $orderGoodsInfo['goods_img'] = $orderGoodsInfo['goods_thumb'];//商品缩略图
        return $orderGoodsInfo;
    }

    /**
     * 还机单检测完成需要更新的基础数据初始化
     * @param array $paramsArr
     * $paramsArr = [<br/>
     *		'evaluation_status' => '',//检测结果 【必须】<br/>
     *		'evaluation_time' => '',//检测时间 【必须】<br/>
     *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
     *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/>
     *		'instalment_amount' => '',//剩余分期金额 【可选】【存在未支付分期时必须】<br/>
     *		'instalment_num' => '',//剩余分期数 【可选】【存在未支付分期时必须】<br/>
     * ]
     * @return array $data
     * $data = [<br/>
     *		'evaluation_status' => '',//检测结果 <br/>
     *		'evaluation_time' => '',//检测时间 <br/>
     *		'evaluation_remark' => '',//检测备注 <br/>
     *		'compensate_amount' => '',//赔偿金额 【<br/>
     *		'instalment_amount' => '',//赔偿金额 【<br/>
     *		'instalment_num' => '',//赔偿金额 【<br/>
     * ]
     */
    private function __givebackUpdateDataInit( $paramsArr ) {
        return [
            'evaluation_status' => $paramsArr['evaluation_status'],
            'evaluation_time' => $paramsArr['evaluation_time'],
            'evaluation_remark' => isset($paramsArr['evaluation_remark']) ? $paramsArr['evaluation_remark'] : '',
            'compensate_amount' => isset($paramsArr['compensate_amount']) ? $paramsArr['compensate_amount'] : 0,
            'instalment_amount' => isset($paramsArr['instalment_amount']) ? $paramsArr['instalment_amount'] : 0,
            'instalment_num' => isset($paramsArr['instalment_num']) ? $paramsArr['instalment_num'] : 0,
        ];
    }
}