<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Order\Modules\Repository\Pay\UnderPay\UnderPayStatus;

/**
 * 支付控制器
 */
class PayincomeController extends Controller
{


    /**
     * 入账明细筛选条件
     * @return array
     *
     */
    public function payIncomeWhere(){
        $business_type = \App\Order\Modules\Inc\OrderStatus::getBusinessType();
//        unset($business_type[1]);
        unset($business_type[8]); // 退款业务  不属于入账范围

        $list = [
            'create_time'       => "",  //日期范围
            'order_no'          => "",  //日期范围
            'business_type'     => $business_type,
            'appid'             => \App\Order\Modules\Inc\OrderPayIncomeStatus::getBusinessType(),
            'channel'           => \App\Order\Modules\Repository\Pay\Channel::getBusinessType(),
            'amount'            => "",  //金额范围
        ];

        return apiResponse($list,ApiStatus::CODE_0,"success");
    }

    /**
     * 收支明细表
     * @requwet Array
     * [
     * 		'appid'				=> '', // 入账渠道：1生活号'
     * 		'business_type'		=> '', // 订单号
     *		'channel'			=> '', // 入账方式
     * 		'amount'			=> '', // 金额
     * 		'create_time'		=> '', // 创建时间
     * ]
     * @return array
     *
     */
    public function payIncomeQuery(Request $request){
        $request               = $request->all()['params'];
        $additional['page']    = isset($request['page']) ? $request['page'] : 1;
        $additional['limit']   = isset($request['limit']) ? $request['limit'] : config("web.pre_page_size");

        $params         = filter_array($request, [
            'appid'            	=> 'required',
            'order_no'        	=> 'required',
            'business_type'     => 'required',
            'channel'      		=> 'required',
            'amount'  			=> 'required',
            'begin_time'       	=> 'required',
            'end_time'       	=> 'required',
        ]);

        $incomeList = \App\Order\Modules\Repository\OrderPayIncomeRepository::queryList($params,$additional);
        if(!is_array($incomeList)){
            return apiResponse([], ApiStatus::CODE_50000, "程序异常");
        }
        $list['data']   = $incomeList;
        $list['total']  = \App\Order\Modules\Repository\OrderPayIncomeRepository::queryCount($params);


        return apiResponse($list,ApiStatus::CODE_0,"success");
    }



    /**
     * 收支明细详情表
     * @requwet Array
     * [
     * 		'appid'				=> '', // 入账渠道：1生活号'
     * 		'business_type'		=> '', // 订单号
     *		'channel'			=> '', // 入账方式
     * 		'amount'			=> '', // 金额
     * 		'create_time'		=> '', // 创建时间
     * ]
     * @return array
     *
     */
    public function payIncomeInfo(Request $request){
        $params     = $request->all();
        $rules = [
            'income_id'        => 'required',
        ];

        // 参数过滤
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([], $validateParams['code']);
        }

        $income_id = $params['params']['income_id'];
        $info = \App\Order\Modules\Repository\OrderPayIncomeRepository::getInfoById($income_id);


        if(!is_array($info)){
            return apiResponse([], ApiStatus::CODE_50000, "程序异常");
        }

        /**
         * 预订业务 体验活动业务  不创建订单  单独显示 用户信息
         */


        if($info['business_type'] == \App\Order\Modules\Inc\OrderStatus::BUSINESS_DESTINE){
            // 预订业务
            $ActivityDestine =  \App\Activity\Modules\Repository\Activity\ActivityDestine::getByNo($info['business_no']);
            if(!$ActivityDestine){
                $info['mobile']     = "--";
                $user_id            = "";
                //return apiResponse([], ApiStatus::CODE_50000, "程序异常");
            }else{
                $ActivityDestineInfo = $ActivityDestine->getData();

                $user_id = $ActivityDestineInfo['user_id'];
                $info['mobile']     =   isset($ActivityDestineInfo['mobile']) ? $ActivityDestineInfo['mobile'] : "";
            }
        }elseif($info['business_type'] == \App\Order\Modules\Inc\OrderStatus::BUSINESS_EXPERIENCE){

            // 体验活动业务
            $activityDestine = \App\Activity\Modules\Repository\Activity\ExperienceDestine::getByNo($info['business_no']);
            if(!$activityDestine){
                $info['mobile']     =   "--";
                $user_id            = "";
                //return apiResponse([], ApiStatus::CODE_50000, "程序异常");
            }else{
                $ExperienceDestineInfo = $activityDestine->getData();

                $user_id = $ExperienceDestineInfo['user_id'];
                $info['mobile']     =   isset($ExperienceDestineInfo['mobile']) ? $ExperienceDestineInfo['mobile'] : "";
            }
        }else{
            //获取订单信息
            $OrderRepository= new \App\Order\Modules\Repository\OrderRepository();
            $orderInfo = $OrderRepository->get_order_info(['order_no'=>$info['order_no']]);
            $orderInfo = $orderInfo[0];

            $user_id = $orderInfo['user_id'];

            $info['mobile']     =   isset($orderInfo['mobile']) ? $orderInfo['mobile'] : "";

        }
        if(!empty($user_id)){
            $memberInfo = \App\Lib\User\User::getUser($user_id);
            $info['realname']   = isset($memberInfo['realname']) ? $memberInfo['realname'] : "";
        }else{
            $info['realname']   = "--";
        }

        // 入账订单
        if($info['business_type'] == 1){
            $info['remark'] = isset($orderInfo['remark']) ? $orderInfo['remark'] : "";
        }else{

            // 查询分期
            $instalmentInfo = \App\Order\Modules\Service\OrderGoodsInstalment::queryInfo(['business_no'=>$info['business_no']]);
            $info['remark'] = isset($instalmentInfo['remark']) ? $instalmentInfo['remark'] : "";
        }

        // 入账类型
        $type = \App\Order\Modules\Inc\OrderStatus::getBusinessType();

        // 入账方式
        $channel = \App\Order\Modules\Repository\Pay\Channel::getBusinessType();


        $info['create_time']    = date("Y-m-d H:i:s",$info['create_time']);
        // 入账类型
        $info['business_type']  = $type[$info['business_type']];
        // 入账方式
        $info['channel']        = $channel[$info['channel']];

        return apiResponse($info,ApiStatus::CODE_0,"success");
    }


    /**
     * 线下还款场景
     * @return Array
     */
    public function underLineScene(Request $request){

        $list = \App\Order\Modules\Repository\Pay\UnderPay\UnderPayStatus::getBusinessType();

        return apiResponse($list,ApiStatus::CODE_0,"success");
    }


    /**
     * 线下支付 获取所需要支付金额
     * * @requwet Array
     * [
     * 		'order_no'			=> '', // 订单号
     * 		'business_type'		=> '', // 业务类型
     * ]
     * @return int amount
     */
    public function underLineGetPayAmount(Request $request){

        $params     = $request->all();

        $rules = [
            'order_no'          => 'required',  // 订单号
            'business_type'     => 'required',  // 缴款用途( 业务类型 )
        ];

        // 参数过滤
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([], $validateParams['code']);
        }

        // 根据缴款用途( 业务类型 ) 实现不同业务操作


        $params = $params['params'];

        // 实现业务
        $business_type = $params['business_type'];
        switch ($business_type) {
            case UnderPayStatus::OrderWithhold:
                $orderService =  new \App\Order\Modules\Repository\Pay\UnderPay\OrderWithhold($params);
                break;
            case UnderPayStatus::OrderGiveback:
                $orderService =  new \App\Order\Modules\Repository\Pay\UnderPay\OrderGiveback($params);
                break;
            case UnderPayStatus::OrderRefund:
                $orderService =  new \App\Order\Modules\Repository\Pay\UnderPay\OrderRefund($params);
                break;
            case UnderPayStatus::OrderBuyout:
                $orderService =  new \App\Order\Modules\Repository\Pay\UnderPay\OrderBuyout($params);
                break;
            case UnderPayStatus::OrderRelet:
                $orderService =  new \App\Order\Modules\Repository\Pay\UnderPay\OrderRelet($params);
                break;
        }
        $amount = $orderService->getPayAmount();


        return $amount;
    }


    /**
     * 增加线下还款记录
     * @requwet Array
     * [
     * 		'order_no'			=> '', // 订单号
     * 		'business_type'		=> '', // 缴款用途( 业务类型 )
     *		'goods_no'			=> '', // 商品编号
     *      'under_channel'		=> '', // 线下还款方式
     * 		'amount'			=> '', // 金额
     * 		'create_time'		=> '', // 创建时间
     * ]
     * @return bool true false
     */
    public function underLineAdd(Request $request){

        $params     = $request->all();

        $rules = [
            'order_no'          => 'required',  // 订单号
            'business_type'     => 'required',  // 缴款用途( 业务类型 )
            'goods_no'          => 'required',  // 商品编号
            'under_channel'     => 'required',  // 线下还款方式
            'amount'            => 'required',  // 金额
            'create_time'       => 'required',  // 还款时间
        ];

        // 参数过滤
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([], $validateParams['code']);
        }

        // 根据缴款用途( 业务类型 ) 实现不同业务操作
        DB::beginTransaction();

        $params = $params['params'];

        // 实现业务
        $business_type = $params['business_type'];
        switch ($business_type) {
            case UnderPayStatus::OrderWithhold:
                $orderService =  new \App\Order\Modules\Repository\Pay\UnderPay\OrderWithhold($params);
                break;
            case UnderPayStatus::OrderGiveback:
                $orderService =  new \App\Order\Modules\Repository\Pay\UnderPay\OrderGiveback($params);
                break;
            case UnderPayStatus::OrderRefund:
                $orderService =  new \App\Order\Modules\Repository\Pay\UnderPay\OrderRefund($params);
                break;
            case UnderPayStatus::OrderBuyout:
                $orderService =  new \App\Order\Modules\Repository\Pay\UnderPay\OrderBuyout($params);
                break;
            case UnderPayStatus::OrderRelet:
                $orderService =  new \App\Order\Modules\Repository\Pay\UnderPay\OrderRelet($params);
                break;
        }

        $result = $orderService->execute();
        if(!$result){
            DB::rollBack();
            \App\Lib\Common\LogApi::error('[underLineAdd]业务实现失败',$params);
            return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
        }

        $data = [
            'name'          => "业务类型" . $params['business_type'] . "线下缴款",
            'order_no'      => $params['order_no'],
            'business_type' => $params['business_type'],
            'appid'         => \App\Order\Modules\Inc\OrderPayIncomeStatus::UNDERLINE,
            'channel'       => \App\Order\Modules\Repository\Pay\Channel::UnderLine,
            'amount'        => $params['amount'],
            'create_time'   => strtotime($params['create_time']),
            'remark'        => $params['remark'],
        ];

        // 创建入账记录
        $result = \App\Order\Modules\Repository\OrderPayIncomeRepository::create($data);
        if(!$result){
            DB::rollBack();
            return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
        }

        // 提交事务
        DB::commit();

        return apiResponse([],ApiStatus::CODE_0,"success");
    }


}
