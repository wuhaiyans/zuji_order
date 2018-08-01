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
     * 小程序还机信息详情接口
     * @param Request $request
     */
    public function givebackInfo(Request $request){
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $paramsArr = isset($params['params'])? $params['params'] :[];
        if( empty($paramsArr['goods_no']) ) {
            return apiResponse([],ApiStatus::CODE_91001);
        }
        $goodsNo = $paramsArr['goods_no'];//提取商品编号
        //-+--------------------------------------------------------------------
        // | 通过商品编号获取需要展示的数据
        //-+--------------------------------------------------------------------

        //初始化最终返回数据数组
        $data = [];
        $orderGoodsInfo = $this->__getOrderGoodsInfo($goodsNo);
        if( !$orderGoodsInfo ) {
            return apiResponse([], get_code(), get_msg());
        }

        //创建服务层对象
        $orderGivebackService = new OrderGiveback();
        //获取还机单基本信息
        $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo( $goodsNo );
        //还机信息为空则返回还机申请页面信息
        if( !$orderGivebackInfo ){
            //组合最终返回商品基础数据
            $data['goods_info'] = $orderGoodsInfo;//商品信息
            $data['giveback_address'] = config('tripartite.Customer_Service_Address');
            $data['giveback_username'] = config('tripartite.Customer_Service_Name');;
            $data['giveback_tel'] = config('tripartite.Customer_Service_Phone');;
            $data['status'] = ''.OrderGivebackStatus::adminMapView(OrderGivebackStatus::STATUS_APPLYING);//状态
            $data['status_text'] = '还机申请中';//后台状态

            //物流信息
            $logistics_list = [];
            $logistics = \App\Warehouse\Config::$logistics;
            foreach ($logistics as $id => $name) {
                $logistics_list[] = [
                    'id' => $id,
                    'name' => $name,
                ];
            }
            $data['logistics_list'] = $logistics_list;//物流列表
            return apiResponse(GivebackController::givebackReturn($data),ApiStatus::CODE_0,'数据获取成功');
        }


        $orderGivebackInfo['status_name'] = OrderGivebackStatus::getStatusName($orderGivebackInfo['status']);
        $orderGivebackInfo['payment_status_name'] = OrderGivebackStatus::getPaymentStatusName($orderGivebackInfo['payment_status']);
        $orderGivebackInfo['evaluation_status_name'] = OrderGivebackStatus::getEvaluationStatusName($orderGivebackInfo['evaluation_status']);
        $orderGivebackInfo['yajin_status_name'] = OrderGivebackStatus::getEvaluationStatusName($orderGivebackInfo['yajin_status']);



        //组合最终返回商品基础数据
        $data['goods_info'] = $orderGoodsInfo;//商品信息
        $data['giveback_info'] =$orderGivebackInfo;//还机单信息
        //判断是否已经收货
        $isDelivery = false;
        if( $orderGivebackInfo['status'] != OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY ){
            $isDelivery = true;
        }
        //快递信息
        $data['logistics_info'] =[
            'logistics_name' => $orderGivebackInfo['logistics_name'],
            'logistics_no' => $orderGivebackInfo['logistics_no'],
            'is_delivery' => $isDelivery,//是否已收货
        ];
        //检测结果
        if( $orderGivebackInfo['evaluation_status'] != OrderGivebackStatus::EVALUATION_STATUS_INIT ){
            $data['evaluation_info'] = [
                'evaluation_status_name' => $orderGivebackInfo['evaluation_status_name'],
                'evaluation_status_remark' => $orderGivebackInfo['yajin_status'] == OrderGivebackStatus::YAJIN_STATUS_RETURN_COMOLETION? '押金已退还至支付账户，由于银行账务流水，请耐心等待1-3个工作日。':'',
                'reamrk' => '',
                'compensate_amount' => '',
            ];
        }
        if( $orderGivebackInfo['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED ){
            $data['evaluation_info']['remark'] = $orderGivebackInfo['evaluation_remark'];//检测备注
            $data['evaluation_info']['compensate_amount'] = $orderGivebackInfo['compensate_amount'];//赔偿金额
        }
        //退还押金
        if( $orderGivebackInfo['yajin_status'] == OrderGivebackStatus::YAJIN_STATUS_IN_RETURN || $orderGivebackInfo['yajin_status'] == OrderGivebackStatus::YAJIN_STATUS_RETURN_COMOLETION ){
            $data['yajin_info'] = [
                'yajin_status_name' => $orderGivebackInfo['yajin_status_name'],
            ];
        }
        //赔偿金额计算(检测不合格，没有未支付分期金额，押金》赔偿金，才能押金抵扣)
        if( $orderGivebackInfo['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED && !$orderGivebackInfo['instalment_amount'] && $orderGoodsInfo['yajin']>=$orderGivebackInfo['compensate_amount'] ){
            $data['compensate_info'] = [
                'compensate_all_amount' => $orderGivebackInfo['instalment_amount'] + $orderGivebackInfo['compensate_amount'],
                'compensate_deduction_amount' => $orderGivebackInfo['instalment_amount'] + $orderGivebackInfo['compensate_amount'],
                'compensate_release_amount' => $orderGoodsInfo['yajin'] - ($orderGivebackInfo['instalment_amount'] + $orderGivebackInfo['compensate_amount']),
            ];
        }else{
            $data['compensate_info'] = [
                'compensate_all_amount' => $orderGivebackInfo['instalment_amount'] + $orderGivebackInfo['compensate_amount'],
                'compensate_deduction_amount' => 0,
                'compensate_release_amount' => $orderGoodsInfo['yajin'],
            ];
        }

        $data['status'] = ''.OrderGivebackStatus::adminMapView($orderGivebackInfo['status']);//状态

        //物流信息
        return apiResponse(GivebackController::givebackReturn($data),ApiStatus::CODE_0,'数据获取成功');
    }

    /**
     * 小程序提交还机申请接口
     * @param $param
     * @return array
     */
    public function givebackCreate(Request $request){
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
        if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
            return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
        }
        $paramsArr = isset($params['params'])? $params['params'] :[];
        $rules = [
            'goods_no'     => 'required',//商品编号
            'order_no'     => 'required',//订单编号
            'user_id'     => 'required',//用户id
            'logistics_no'     => 'required',//物流单号
            'logistics_id'     => 'required',//物流id
            'logistics_name'     => 'required',//物流名称
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_91000,$validator->errors()->first());
        }
        $goodsNoArr = is_array($paramsArr['goods_no']) ? $paramsArr['goods_no'] : [$paramsArr['goods_no']];
        //-+--------------------------------------------------------------------
        // | 业务处理：冻结订单、生成还机单、推送到收发货系统【加事务】
        //-+--------------------------------------------------------------------
        //开启事务
        DB::beginTransaction();
        try{
            foreach ($goodsNoArr as $goodsNo) {
                //生成还机单编号
                $paramsArr['giveback_no'] = $giveback_no = createNo(7);
                //初始化还机单状态
                $paramsArr['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY;
                $paramsArr['goods_no'] = $goodsNo;
                //生成还机单
                $orderGivebackService = new OrderGiveback();
                $orderGivebackIId = $orderGivebackService->create($paramsArr);
                if( !$orderGivebackIId ){
                    //事务回滚
                    DB::rollBack();
                    return apiResponse([], get_code(), get_msg());
                }
//				//修改商品表业务类型、商品编号、还机状态
//				$orderGoodsService = new OrderGoods();
//				$orderGoodsResult = $orderGoodsService->update(['goods_no'=>$paramsArr['goods_no']], [
//					'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
//					'business_no' => $giveback_no,
//					'goods_status' => $status,
//				]);
                //修改商品表业务类型、商品编号、还机状态
                $orderGoods = Goods::getByGoodsNo($paramsArr['goods_no']);
                if( !$orderGoods ){
                    //事务回滚
                    DB::rollBack();
                    return apiResponse([], ApiStatus::CODE_92401);
                }
                $orderGoodsResult = $orderGoods->givebackOpen( $giveback_no );
                if(!$orderGoodsResult){
                    //事务回滚
                    DB::rollBack();
                    return apiResponse([],  ApiStatus::CODE_92200, '同步更新商品状态出错');
                }
                //获取用户信息
                $userInfo = \App\Order\Modules\Repository\OrderUserAddressRepository::getUserAddressInfo($paramsArr['order_no']);
                $orderGoodsInfo = $orderGoods->getData();
                //推送到收发货系统
                $warehouseResult = \App\Lib\Warehouse\Receive::create($paramsArr['order_no'], 1, [
                    [
                        'goods_no'=>$goodsNo,
                        'goods_name'=>$orderGoodsInfo['goods_name'],
                        'business_no' => $giveback_no,
                    ],
                ],[
                    'logistics_id' => $paramsArr['logistics_id'],
                    'logistics_no' => $paramsArr['logistics_no'],
                    'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
                    'business_no' => $giveback_no,
                    'customer' => $userInfo['name'],
                    'customer_mobile' => $userInfo['consignee_mobile'],
                    'customer_address' => $userInfo['address_info'],
                ]);
                if( !$warehouseResult ){
                    //事务回滚
                    DB::rollBack();
                    return apiResponse([], ApiStatus::CODE_93200, '收货单创建失败!');
                }

                //发送短信
                $notice = new \App\Order\Modules\Service\OrderNotice(
                    \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
                    $goodsNo,
                    "GivebackCreate");
                $notice->notify();

            }
            //冻结订单

            $orderFreezeResult = \App\Order\Modules\Repository\OrderRepository::orderFreezeUpdate($paramsArr['order_no'], \App\Order\Modules\Inc\OrderFreezeStatus::Reback);
            if( !$orderFreezeResult ){
                return apiResponse([],ApiStatus::CODE_92700,'订单冻结失败！');
            }

            //记录日志
            $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
                'order_no'=>$paramsArr['order_no'],
                'action'=>'小程序还机单生成',
                'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//此处用常量
                'business_no'=>$giveback_no,
                'goods_no'=>$goodsNo,
                'operator_id'=>$operateUserInfo['uid'],
                'operator_name'=>$operateUserInfo['username'],
                'operator_type'=>$operateUserInfo['type']==1?\App\Lib\PublicInc::Type_Admin:\App\Lib\PublicInc::Type_User,//此处用常量
                'msg'=>'用户申请还机',
            ]);
            if( !$goodsLog ){
                return apiResponse([],ApiStatus::CODE_92700,'设备日志生成失败！');
            }

        } catch (\Exception $ex) {
            //事务回滚
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_94000,$ex->getMessage());
        }
        //提交事务
        DB::commit();

//		$return  = $this->givebackReturn(['status'=>"A","status_text"=>"申请换机"]);

        return apiResponse([], ApiStatus::CODE_0, '数据获取成功');
    }

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
        //判断租金是否为2000以上
        if($orderMiniInfo['instalment_status'] == 4){
            //查询用户是否在APP已经进行主动还款操作
            $instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$goodsNo,'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
            if( empty($instalmentList[$goodsNo]) ){
                //更新还机单租金状态为已还款
                $orderGivebackResult = $orderGivebackService->update(['goods_no'=>$goodsNo], [
                    'instalment_status' => OrderGivebackStatus::ZUJIN_SUCCESS,
                ]);
                if( !$orderGivebackResult ){
                    //事务回滚
                    DB::rollBack();
                    return apiResponse([],ApiStatus::CODE_92701,'更新还机单数据失败');
                }
            }else{
                return apiResponse([], ApiStatus::CODE_35016,'您还有剩余租金未结清，请在拿趣用APP中进行还款在进行还机操作' );
            }
        }
        //判断APPid是否有映射
        if(empty(config('miniappid.'.$params['appid']))){
            return apiResponse([],ApiStatus::CODE_35011,'匹配小程序appid错误');
        }
        $paramsArr['zm_app_id'] = config('miniappid.'.$params['appid']);//小程序APPID
        //开启事务
        DB::beginTransaction();
        try {
            //-+------------------------------------------------------------------------------
            // |收货时：查询未完成分期直接进行代扣，并记录代扣状态
            //-+------------------------------------------------------------------------------
            if($orderGivebackInfo['instalment_status'] == OrderGivebackStatus::ZUJIN_INIT || $orderGivebackInfo['instalment_status'] == OrderGivebackStatus::ZUJIN_FAIL){
                //判断是否有请求过（芝麻支付接口）
                $orderMiniCreditPayInfo = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::getMiniCreditPayInfo($paramsArr['order_no'],'INSTALLMENT',$paramsArr['giveback_no']);
                if( !$orderMiniCreditPayInfo ) {
                    return apiResponse([],ApiStatus::CODE_35015,'请求扣款记录不存在');
                }
                $arr['out_trans_no'] = $orderMiniCreditPayInfo['out_trans_no'];
                $arr = [
                    'zm_order_no'=>$orderMiniInfo['zm_order_no'],
                    'out_order_no'=>$orderGivebackInfo['order_no'],
                    'pay_amount'=>$orderGivebackInfo['instalment_amount'],
                    'remark'=>$orderGivebackInfo['giveback_no'],
                    'app_id'=>$paramsArr['zm_app_id'],
                ];
                $pay_status = \App\Lib\Payment\mini\MiniApi::withhold($arr);
                //提交事务
                DB::commit();
                //判断请求发送是否成功
                if($pay_status == 'PAY_SUCCESS'){
                    //租金支付发起成功，如有赔偿金额则未支付修改为支付中
                    if($orderGivebackInfo['instalment_status'] == OrderGivebackStatus::PAYMENT_STATUS_NOT_PAY){
                        $orderGivebackResult = $orderGivebackService->update(['goods_no'=>$goodsNo], [
                            'payment_status' => OrderGivebackStatus::PAYMENT_STATUS_IN_PAY,
                        ]);
                        if( !$orderGivebackResult ){
                            //事务回滚
                            DB::rollBack();
                            return apiResponse([],ApiStatus::CODE_92701,'更新还机单数据失败');
                        }
                    }
                    return apiResponse([], ApiStatus::CODE_0, '小程序支付扣除租金成功');
                }elseif($pay_status =='PAY_FAILED'){
                    return apiResponse([], ApiStatus::CODE_35006, '小程序扣除租金失败');
                }elseif($pay_status == 'PAY_INPROGRESS'){
                    return apiResponse([], ApiStatus::CODE_35007, '小程序扣除租金处理中请等待');
                }else{
                    return apiResponse([], ApiStatus::CODE_35000, '小程序扣除租金处理失败（内部失败）');
                }
            }else{
                //租金已支付（扣除赔偿金，关闭订单）
                //判断是否有请求过（芝麻支付接口）
                $orderMiniCreditPayInfo = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::getMiniCreditPayInfo($paramsArr['order_no'],'FINISH',$paramsArr['giveback_no']);
                if( $orderMiniCreditPayInfo ) {
                    $arr['out_trans_no'] = $orderMiniCreditPayInfo['out_trans_no'];
                }else{
                    $arr['out_trans_no'] = createNo();
                }
                $arr = [
                    'zm_order_no'=>$orderMiniInfo['zm_order_no'],
                    'out_order_no'=>$orderGivebackInfo['order_no'],
                    'pay_amount'=>$orderGivebackInfo['compensate_amount'],
                    'remark'=>$orderGivebackInfo['giveback_no'],
                    'app_id'=>$paramsArr['zm_app_id'],
                ];
                $orderCloseResult = \App\Lib\Payment\mini\MiniApi::OrderClose($arr);
                //提交事务
                DB::commit();
                if( $orderCloseResult['code'] == 10000  ){
                    return apiResponse([], ApiStatus::CODE_0, '小程序赔偿金支付请求成功');
                }else{
                    return apiResponse([], ApiStatus::CODE_35006, '小程序赔偿金支付失败'.$orderCloseResult['msg']);
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
     * 还机确认收货
     * @param Request $request
     * @return array
     */
    public function givebackConfirmDelivery( Request $request ) {
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
        $goodsNo = $paramsArr['goods_no'];
        //-+--------------------------------------------------------------------
        // | 业务处理：获取判断当前还机单状态、更新还机单状态
        //-+--------------------------------------------------------------------
        //获取还机单信息
        $orderGivebackService = new OrderGiveback();//创建还机单服务层
        $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($goodsNo);
        //还机单状态必须为待收货
        if( !$orderGivebackInfo ){
            return apiResponse([], get_code(), get_msg());
        }
        if( $orderGivebackInfo['status'] == OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK ){
            return apiResponse([],ApiStatus::CODE_92500,'当前还机单已经收货');
        }
        if( $orderGivebackInfo['status'] != OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY ) {
            return apiResponse([],ApiStatus::CODE_92500,'当前还机单不处于待收货状态，不能进行收货操作');
        }
        //开启事务
        DB::beginTransaction();
        try{
            //-+------------------------------------------------------------------------------
            // |收货时：查询未完成分期直接进行代扣，并记录代扣状态
            //-+------------------------------------------------------------------------------
            //获取当前商品未完成分期列表数据
            $instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$goodsNo,'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
            if( !empty($instalmentList[$goodsNo]) ){
                //发送短信
                $notice = new \App\Order\Modules\Service\OrderNotice(
                    \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
                    $goodsNo,
                    "GivebackConfirmDelivery");
                $notice->notify();
                //未扣款代扣全部执行
                foreach ($instalmentList[$goodsNo] as $instalmentInfo) {
                    OrderWithhold::instalment_withhold($instalmentInfo['id']);
                }
                //代扣已执行
                $withhold_status = OrderGivebackStatus::WITHHOLD_STATUS_ALREADY_WITHHOLD;
            } else {
                //发送短信
                $notice = new \App\Order\Modules\Service\OrderNotice(
                    \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
                    $goodsNo,
                    "GivebackConfirmNoWith");
                $notice->notify();
                //无需代扣
                $withhold_status = OrderGivebackStatus::WITHHOLD_STATUS_NO_NEED_WITHHOLD;
            }

            //更新还机单状态到待收货
            $orderGivebackResult = $orderGivebackService->update(['goods_no'=>$goodsNo], [
                'status' => OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK,
                'withhold_status' => $withhold_status,
            ]);
            if( !$orderGivebackResult ){
                //事务回滚
                DB::rollBack();
                return apiResponse([],ApiStatus::CODE_92701);
            }
            //记录日志
            $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
                'order_no'=>$orderGivebackInfo['order_no'],
                'action'=>'还机单收货',
                'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//此处用常量
                'business_no'=>$orderGivebackInfo['giveback_no'],
                'goods_no'=>$orderGivebackInfo['goods_no'],
                'operator_id'=>$operateUserInfo['uid'],
                'operator_name'=>$operateUserInfo['username'],
                'operator_type'=>$operateUserInfo['type']==1?\App\Lib\PublicInc::Type_Admin:\App\Lib\PublicInc::Type_User,//此处用常量
                'msg'=>'还机单确认收货操作',
            ]);
            if( !$goodsLog ){
                return apiResponse([],ApiStatus::CODE_92700,'设备日志生成失败！');
            }
        } catch (\Exception $ex) {
            //事务回滚
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_94000,$ex->getMessage());
        }
        //提交事务
        DB::commit();

//		$return  = $this->givebackReturn(['status'=>"B","status_text"=>"还机确认收货"]);
        return apiResponse([], ApiStatus::CODE_0, '确认收货成功');

    }

    /**
     * 还机确认收货结果
     * @param Request $request
     */
    public function givebackConfirmEvaluation( Request $request ) {
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
        $out_trans_no = '';
        if( !empty($instalmentList[$goodsNo]) ){
            foreach ($instalmentList[$goodsNo] as $instalmentInfo) {
                if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]) ){
                    $instalmentAmount += $instalmentInfo['amount'];
                    $instalmentNum++;
                    $out_trans_no = $instalmentInfo['business_no'];
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
        $paramsArr['out_trans_no'] = $out_trans_no;//芝麻请求流水号
        //判断APPid是否有映射
        if(empty(config('miniappid.'.$params['appid']))){
            return apiResponse([],ApiStatus::CODE_35011,'匹配小程序appid错误');
        }
        $paramsArr['zm_app_id'] = config('miniappid.'.$params['appid']);//小程序APPID

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
        if( $paramsArr['yajin'] ){
            //判断是否有请求过（芝麻支付接口）
            $orderMiniCreditPayInfo = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::getMiniCreditPayInfo($paramsArr['order_no'],'FINISH',$paramsArr['giveback_no']);
            if( $orderMiniCreditPayInfo ) {
                $arr['out_trans_no'] = $orderMiniCreditPayInfo['out_trans_no'];
            }else{
                $arr['out_trans_no'] = createNo();
            }
            $arr = [
                'zm_order_no'=>$paramsArr['zm_order_no'],
                'out_order_no'=>$paramsArr['order_no'],
                'pay_amount'=>$paramsArr['compensate_amount'],
                'remark'=>$paramsArr['giveback_no'],
                'app_id'=>$paramsArr['zm_app_id'],
            ];
            $orderCloseResult = \App\Lib\Payment\mini\MiniApi::OrderClose($arr);
            if( $orderCloseResult['code'] != 10000  ){
                return false;
            }
            //拼接需要更新还机单状态
            $data['status'] = $status =$goodsStatus = OrderGivebackStatus::STATUS_DEAL_WAIT_RETURN_DEPOSTI;
            $data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
            $data['payment_time'] = time();
            $data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_IN_RETURN;
        }
        //-+--------------------------------------------------------------------
        // | 无押金->直接修改订单
        //-+--------------------------------------------------------------------
        else{
            //更新商品表状态
            $orderGoods = Goods::getByGoodsNo($paramsArr['goods_no']);
            if( !$orderGoods ){
                return false;
            }
            $orderGoodsResult = $orderGoods->givebackFinish();
            if(!$orderGoodsResult){
                return false;
            }
            //解冻订单
            if(!OrderGiveback::__unfreeze($paramsArr['order_no'])){
                set_apistatus(ApiStatus::CODE_92700, '订单解冻失败!');
                return false;
            }
            //拼接需要更新还机单状态
            $data['status'] = $status = $goodsStatus = OrderGivebackStatus::STATUS_DEAL_DONE;
            $data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
            $data['payment_time'] = time();
            $data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_NO_NEED_RETURN;
        }

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
        //分期金额大于两千则通过APP还款
        if( $paramsArr['instalment_amount'] < 2000.00 ){
            //判断是否有请求过（芝麻支付接口）
            $orderMiniCreditPayInfo = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::getMiniCreditPayInfo($paramsArr['order_no'],'INSTALLMENT',$paramsArr['giveback_no']);
            if( $orderMiniCreditPayInfo ) {
                $arr['out_trans_no'] = $orderMiniCreditPayInfo['out_trans_no'];
            }else{
                $arr['out_trans_no'] = $paramsArr['out_trans_no'];
            }
            $arr = [
                'zm_order_no'=>$paramsArr['zm_order_no'],
                'out_order_no'=>$paramsArr['order_no'],
                'pay_amount'=>$paramsArr['instalment_amount'],
                'remark'=>$paramsArr['giveback_no'],
                'app_id'=>$paramsArr['zm_app_id'],
            ];
            $pay_status = \App\Lib\Payment\mini\MiniApi::withhold($arr);
            //判断请求发送是否成功
            if($pay_status == 'PAY_SUCCESS'){
                $data['instalment_status'] = $status = OrderGivebackStatus::ZUJIN_SUCCESS;
                //分期扣款成功，关闭分期单
                $instalmentResult = \App\Order\Modules\Repository\Order\Instalment::close(['goods_no'=>$paramsArr['goods_no'],'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]]);
                //分期关闭失败，回滚
                if( !$instalmentResult ) {
                    DB::rollBack();
                    return false;
                }
            }elseif($pay_status =='PAY_FAILED'){
                $data['instalment_status'] = $status = OrderGivebackStatus::ZUJIN_FAIL;
            }elseif($pay_status == 'PAY_INPROGRESS'){
                $data['instalment_status'] = $status = OrderGivebackStatus::ZUJIN_INIT;
            }else{
                $data['instalment_status'] = $status = OrderGivebackStatus::ZUJIN_FAIL;
            }
        }else{
            $data['remark'] = '小程序还机代扣金额一次性不能超过2000，剩余租金请用户主动到APP支付 ';
            $data['instalment_status'] = $status = OrderGivebackStatus::ZUJIN_EXCEED;
        }
        //拼接需要更新还机单状态
        $data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
        $data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
        $data['payment_time'] = time();
        //更新还机单
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
        // | 业务验证（押金>=赔偿金：还机清算 || 押金<赔偿金：还机支付）、更新还机单
        //-+--------------------------------------------------------------------
        //押金>=赔偿金：还机清算
        if( $paramsArr['yajin'] >= $paramsArr['compensate_amount'] ){

            //拼接需要更新还机单状态更新还机单状态
            $data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
            $data['payment_status'] = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
            $data['payment_time'] = time();
            $data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_IN_RETURN;

            //发送短信
            $notice = new \App\Order\Modules\Service\OrderNotice(
                \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
                $paramsArr['goods_no'],
                'GivebackEvaNoWitYesEno',
                ['amount' => $paramsArr['compensate_amount'] ]);
            $notice->notify();
        }

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
        //分期金额大于两千则通过APP还款
        if( $paramsArr['instalment_amount'] < 2000.00 ){
            //判断是否有请求过（芝麻支付接口）
            $orderMiniCreditPayInfo = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::getMiniCreditPayInfo($paramsArr['order_no'],'INSTALLMENT',$paramsArr['giveback_no']);
            if( $orderMiniCreditPayInfo ) {
                $arr['out_trans_no'] = $orderMiniCreditPayInfo['out_trans_no'];
            }else{
                $arr['out_trans_no'] = $paramsArr['out_trans_no'];
            }
            $arr = [
                'zm_order_no'=>$paramsArr['zm_order_no'],
                'out_order_no'=>$paramsArr['order_no'],
                'pay_amount'=>$paramsArr['instalment_amount'],
                'remark'=>$paramsArr['giveback_no'],
                'app_id'=>$paramsArr['zm_app_id'],
            ];
            $pay_status = \App\Lib\Payment\mini\MiniApi::withhold($arr);
            //判断请求发送是否成功
            if($pay_status == 'PAY_SUCCESS'){
                $data['instalment_status'] = $status = OrderGivebackStatus::ZUJIN_SUCCESS;
            }elseif($pay_status =='PAY_FAILED'){
                $data['instalment_status'] = $status = OrderGivebackStatus::ZUJIN_FAIL;
            }elseif($pay_status == 'PAY_INPROGRESS'){
                $data['instalment_status'] = $status = OrderGivebackStatus::ZUJIN_INIT;
            }else{
                $data['instalment_status'] = $status = OrderGivebackStatus::ZUJIN_FAIL;
            }
        }else{
            $data['remark'] = '小程序还机代扣金额一次性不能超过2000，剩余租金请用户主动到APP支付 ';
            $data['instalment_status'] = $status = OrderGivebackStatus::ZUJIN_EXCEED;
        }
        //拼接需要更新还机单状态
        $data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
        $data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NOT_PAY;
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