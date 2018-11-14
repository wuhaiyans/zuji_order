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
            'kw_type'           => 'required',
            'keywords'          => 'required',
            'begin_time'       	=> 'required',
            'end_time'       	=> 'required',
        ]);


        $incomeList = \App\Order\Modules\Repository\OrderPayIncomeRepository::queryList($params,$additional);
        if(!is_array($incomeList)){
            return apiResponse([], ApiStatus::CODE_50000, "程序异常");
        }

        // 线下入账列表接口 展示缴款用途名称
        foreach($incomeList as &$item){
            $business_type_name = \App\Order\Modules\Repository\Pay\UnderPay\UnderPayStatus::getBusinessTypeName($item['business_type']);
            $businessType = \App\Order\Modules\Inc\OrderStatus::getBusinessName($item['business_type']);
            if(!$business_type_name){
                $business_type_name = "业务类型-" . $businessType . "支付";
            }
            $item['business_type_name'] = $business_type_name;
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


        $info['create_time']    = date("Y-m-d H:i:s",$info['create_time']);

        // 入账类型
        $business_type_name = \App\Order\Modules\Repository\Pay\UnderPay\UnderPayStatus::getBusinessTypeName($info['business_type']);

        $info['business_type']  = \App\Order\Modules\Inc\OrderStatus::getBusinessName($info['business_type']);
        if(!$business_type_name){
            $business_type_name = "业务类型-" . $info['business_type'] . "支付";
        }
        $info['business_type_name'] = $business_type_name;


        // 入账方式
        $channel        = \App\Order\Modules\Repository\Pay\Channel::getBusinessName($info['channel']);

        // 线下支付方式
        if($info['channel'] == \App\Order\Modules\Repository\Pay\Channel::UnderLine){
            $under_channel =  \App\Order\Modules\Repository\Pay\UnderPay\UnderPayStatus::getUnderLineBusinessTypeName($info['under_channel']);
            $under_channel = $under_channel ? $under_channel : "--";
            $channel = $channel . "-" . $under_channel;
        }

        $info['channel'] = $channel;

        // 从属设备
        $goods_obj = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow(['order_no'=>$info['order_no']]);
        $goodsInfo = objectToArray($goods_obj);

        $info['goods_name'] =   $goodsInfo['goods_name'] ? $goodsInfo['goods_name'] : "";


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
     * 线下缴款类型
     * @return Array
     */
    public function underLinePayType(Request $request){

        $list = \App\Order\Modules\Repository\Pay\UnderPay\UnderPayStatus::getUnderBusinessType();

        return apiResponse($list,ApiStatus::CODE_0,"success");
    }

    /**
     * 线下手机号获取订单信息
     * @return Array
     */
    public function getOrderInfoByPhone(Request $request){
        try{
            $params     = $request->all();
            $rules = [
                'mobile'          => 'required',  // 手机号
            ];
            // 参数过滤
            $validateParams = $this->validateParams($rules,$params);
            if ($validateParams['code'] != 0) {
                return apiResponse([], $validateParams['code']);
            }

            $params = $params['params'];
            $whereArray = [
                'mobile'    => $params['mobile']
            ];

            $orderList = DB::table('order_info')
                ->select('order_no')
                ->where($whereArray)
                ->get();
            $orderList = objectToArray($orderList);
            if(!$orderList){
                return apiResponse( [], ApiStatus::CODE_50000, '参数错误...');
            }

            foreach($orderList as &$item){
                $goods_obj = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow(['order_no'=>$item]);
                $goodsInfo = objectToArray($goods_obj);

                $item['goods_name'] =   $goodsInfo['goods_name'] ? $goodsInfo['goods_name'] : "";
                $item['goods_no']   =   $goodsInfo['goods_no'] ? $goodsInfo['goods_no'] : "";


                // 订单服务 开始 结束时间
                $item['begin_time'] = $goodsInfo['begin_time'] ? $goodsInfo['begin_time'] : "";
                $item['end_time']   = $goodsInfo['end_time'] ? $goodsInfo['end_time'] : "";

                // 获取商品最大 续租天数
                $data = [
                    'business_type' => \App\Order\Modules\Repository\Pay\UnderPay\UnderPayStatus::OrderRelet,
                    'order_no' => $item['order_no'],
                ];
                $orderService = new \App\Order\Modules\Repository\Pay\UnderPay\UnderPay($data);
                $maxRelet = $orderService->getClssObj()->getReletTime();
                $item = array_merge($item,$maxRelet);
            }
            return apiResponse($orderList,ApiStatus::CODE_0,"success");

        } catch (\Exception $e) {

            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }

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
        try{

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
            $orderService = new \App\Order\Modules\Repository\Pay\UnderPay\UnderPay($params);
            $amount = $orderService->getPayAmount();
            if($amount === false){
                return apiResponse([], ApiStatus::CODE_50003, "获取支付金额失败");
            }

            return apiResponse($amount, ApiStatus::CODE_0, "success");

        } catch (\Exception $e) {

            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }
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
        try{
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
            $orderService = new \App\Order\Modules\Repository\Pay\UnderPay\UnderPay($params);
            $result = $orderService->execute();
            if(!$result){
                DB::rollBack();
                \App\Lib\Common\LogApi::error('[underLinePay]业务实现失败',$params);
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
                'under_channel' => $params['under_channel'],
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

        } catch (\Exception $e) {

            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }
    }


}
