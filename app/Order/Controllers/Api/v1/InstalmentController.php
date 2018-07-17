<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderGoodsInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Service\OrderGoods;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstalmentController extends Controller
{


    // 创建订单分期
    public function create(Request $request){
        $request    = $request->all();

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
            'goods_no'      => 'required',
            'zuqi'          => 'required',
            'zuqi_type'     => 'required',
            'all_amount'    => 'required',
            'amount'        => 'required',
            'insurance'     => 'required',
            'zujin'         => 'required',
            'pay_type'      => 'required',
        ]);
        if(count($sku) < 8){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误");
        }

        filter_array($coupon, [
            'discount_amount'   => 'required',    //fool；优惠金额
            'coupon_type'       => 'required',    //int；优惠券类型
        ]);


        $user = filter_array($user, [
            'user_id' => 'required',            //【必须】用户ID
            'withholding_no' => 'required',    //【必须】string；代扣协议号
            
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

        $res        = new \App\Order\Modules\Repository\Order\Instalment();
        $data       = $res->create($params);

        if(!$data){
            return apiResponse([],ApiStatus::CODE_20001, "创建分期失败");
        }

        return apiResponse([],ApiStatus::CODE_0,"success");

    }

    //分期列表接口
    public function instalment_list(Request $request){
        $request               = $request->all()['params'];
        $additional['page']    = isset($request['page']) ? $request['page'] : 1;
        $additional['limit']   = isset($request['limit']) ? $request['limit'] : config("web.pre_page_size");

        $params         = filter_array($request, [
            'goods_no'  => 'required',
            'order_no'  => 'required',
            'status'    => 'required',
            'mobile'    => 'required',
            'term'      => 'required',
        ]);
        $list = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::queryList($params,$additional);
        foreach($list as &$item){
            $item['status']         = OrderInstalmentStatus::getStatusList($item['status']);
            $item['payment_time']   = $item['payment_time'] ? date("Y-m-d H:i:s",$item['payment_time']) : "";
            $item['update_time']    = $item['update_time'] ? date("Y-m-d H:i:s",$item['update_time']) : "";
        }
        if(!is_array($list)){
            return apiResponse([], ApiStatus::CODE_50000, "程序异常");
        }
        return apiResponse($list,ApiStatus::CODE_0,"success");

    }

    /*
     * 扣款明细接口
     * @param array $request
	 * [
	 *		'goods_no'		=> '', //【必选】string 商品编号
	 * ]
	 * @return array instalmentList
     */
    public function info(Request $request){
        $params     = $request->all();
        $uid        = $params['userinfo']['uid'];
        // 参数过滤
        $rules = [
            'goods_no'         => 'required',  //商品编号
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }

        $goodsNo         = $params['params']['goods_no'];

        // 订单详情
        $orderGoodsService = new OrderGoods();
        $orderGoodsInfo = $orderGoodsService->getGoodsInfo($goodsNo);
        if($orderGoodsInfo == []){
            return apiResponse([], ApiStatus::CODE_50000, "订单信息不存在");
        }

        // 订单详情
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getOrderInfo(['order_no'=>$orderGoodsInfo['order_no']]);
        if(!$orderInfo){
            return apiResponse([], ApiStatus::CODE_50000, "订单信息不存在");
        }

        // 用户验证
        if($uid != $orderGoodsInfo['user_id']){
            return apiResponse([], ApiStatus::CODE_50000, "用户信息错误");
        }


        // 分期列表
        $where = [
            'goods_no' => $goodsNo,
        ];
        $instalmentList = \App\Order\Modules\Service\OrderGoodsInstalment::queryList($where);
        if($instalmentList == []){
            return apiResponse([], ApiStatus::CODE_50000, "分期信息不存在");
        }
        $instalmentList = $instalmentList[$goodsNo];

        $allow = 0;
        foreach($instalmentList as &$item){

            // 是否允许扣款
            $item['allow_pay']  = 0;
            if($orderInfo['order_status'] == \App\Order\Modules\Inc\OrderStatus::OrderInService){
                if($item['status'] == OrderInstalmentStatus::UNPAID || $item['status'] == OrderInstalmentStatus::FAIL ){
                    if($allow == 0){
                        $item['allow_pay']  = 1;
                    }
                    $allow = 1;
                }
            }

            // 是否扣款
            $item['status']     = $item['status'] == OrderInstalmentStatus::SUCCESS ? "是" : "否";

            // 扣款时间
            $item['payment_time'] = !empty($item['payment_time']) ? date("Y-m-d H:i:s",$item['payment_time']) : "";

            // 是否有意外险 默认没有意外险 0
            $item['yiwaixian']  = 0;

            if($item['times'] == 1){
                $item['yiwaixian']  = 1;
                $item['yiwaixian_amount']   = $orderGoodsInfo['insurance'];
                $item['fenqi_amount']       = $item['amount'] - $orderGoodsInfo['insurance'];
            }

        }
        return apiResponse($instalmentList,ApiStatus::CODE_0,"success");

    }


    /*
    * 分期提前还款详情接口
    * @param array $request
    * [
    *		'instalment_id'		=> '', //【必选】string 分期id
    * ]
    * @return array instalmentList
    */
    public function queryInfo(Request $request){
        $params     = $request->all();
        $uid        = $params['userinfo']['uid'];
        // 参数过滤
        $rules = [
            'instalment_id'         => 'required',  //商品编号
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $instalment_id      = $params['params']['instalment_id'];


        $instalmentInfo     = \App\Order\Modules\Service\OrderGoodsInstalment::queryInfo(['id'=>$instalment_id]);
        if(!$instalmentInfo){
            return apiResponse([], ApiStatus::CODE_50000, "分期信息不存在");
        }

        // 用户验证
        if($uid != $instalmentInfo['user_id']){
            return apiResponse([], ApiStatus::CODE_50000, "用户信息错误");
        }

        // 订单详情
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getOrderInfo(['order_no'=>$instalmentInfo['order_no']]);
        if(!$orderInfo){
            return apiResponse([], ApiStatus::CODE_50000, "订单信息不存在");
        }

        // 商品详情
        $OrderGoods = new \App\Order\Modules\Service\OrderGoods();
        $goodInfo = $OrderGoods->getGoodsInfo($instalmentInfo['goods_no']);


        // 首期意外险
        $instalmentInfo['yiwaixian_amount'] = 0;
        if($instalmentInfo['times'] == 1){
            $instalmentInfo['yiwaixian_amount'] = $goodInfo['insurance'];
        }

        $instalmentInfo['allow_pay'] = 0;
        if($orderInfo['order_status'] == \App\Order\Modules\Inc\OrderStatus::OrderInService){
            if($instalmentInfo['status'] == OrderInstalmentStatus::UNPAID || $instalmentInfo['status']==OrderInstalmentStatus::FAIL){
                $instalmentInfo['allow_pay']  = 1;
            }
        }

        // 分期金额
        $instalmentInfo['fenqi_amount']     = $goodInfo['zujin'];//$instalmentInfo['original_amount'];

        // 租金抵用券
        $couponInfo = \App\Lib\Coupon\Coupon::getUserCoupon($instalmentInfo['user_id']);
        if(is_array($couponInfo) && $couponInfo['youhui'] > 0){
            $discount_amount = $couponInfo['youhui'];

            if($discount_amount >= $instalmentInfo['amount']){
                $instalmentInfo['discount_amount']     = $instalmentInfo['amount'];
                $instalmentInfo['amount']              = '0.00';
            }else{
                $amount = $instalmentInfo['amount'] - $couponInfo['youhui'];

                $instalmentInfo['discount_amount']     = $discount_amount/100;
                $instalmentInfo['amount']              = $amount;
            }
        }

        $instalmentInfo['status'] = $instalmentInfo['status'] == OrderInstalmentStatus::SUCCESS ? "是" : "否";

        // 用户信息
        $memberInfo = \App\Lib\User\User::getUser($instalmentInfo['user_id']);

        $instalmentInfo['realname']         = "*" . mb_substr($memberInfo['realname'], 1, mb_strlen ( $memberInfo['realname'] )-1, 'utf-8');
        $instalmentInfo['mobile']           = substr($memberInfo['mobile'], -4);

        //收货时间
        $instalmentInfo['receive_date']     = $orderInfo['receive_time'] != "" ? date("Ymd",$orderInfo['receive_time']) : "";

        //代扣日
        $instalmentInfo['withhold_date']    = withholdDate($instalmentInfo['term'],$instalmentInfo['day']);

        return apiResponse($instalmentInfo, ApiStatus::CODE_0);





    }



}
