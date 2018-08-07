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

    /**
     * 分期列表接口
     * @$params array
     * [
     *      'goods_no'      => 'GA80106950235887',  // 商品编号
     *      'order_no'      => 'A801106950194751',  // 订单号
     *      'status'        => '1',                 // 状态
     *      'mobile'        => '13654565804',       // 手机号
     *      'term'          => '201806',            // 分期
     *      'begin_time'    => '201806',            // 开始时间
     *      'end_time'      => '201808',            // 结束时间
     * ]
     * return  array $result
     */
    public function instalment_list(Request $request){
        $request               = $request->all()['params'];
        $additional['page']    = isset($request['page']) ? $request['page'] : 1;
        $additional['limit']   = isset($request['limit']) ? $request['limit'] : config("web.pre_page_size");

        $params         = filter_array($request, [
            'begin_time'    => 'required',
            'end_time'      => 'required',
            'goods_no'      => 'required',
            'order_no'      => 'required',
            'status'        => 'required',
            'mobile'        => 'required',
            'term'          => 'required',
        ]);
        
        $list = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::queryList($params,$additional);

        foreach($list as &$item){

            $item['payment_time']   = $item['payment_time'] ? date("Y-m-d H:i:s",$item['payment_time']) : "";
            $item['update_time']    = $item['update_time'] ? date("Y-m-d H:i:s",$item['update_time']) : "";

            // 姓名
            $member = \App\Lib\User\User::getUser($item['user_id']);
            $item['realname']       = !empty($member) ? $member['realname'] : "--";

            // 状态
            $item['status']         = OrderInstalmentStatus::getStatusName($item['status']);

            // 还款日
            $item['day']            = $item['day'] ? withholdDate($item['term'],$item['day']) : "";

            // 是否允许扣款 按钮
            $item['allowWithhold']  = OrderGoodsInstalment::allowWithhold($item['id']);
        }

        $result['data']     = $list;
        $result['total']    = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::queryCount($params);

        if(!is_array($list)){
            return apiResponse([], ApiStatus::CODE_50000, "程序异常");
        }
        return apiResponse($result,ApiStatus::CODE_0,"success");

    }

    /**
     * 扣款明细接口
     * @$params array
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


    /**
    * 分期提前还款详情接口
    * @$params array $request
    * [
    *		'instalment_id'		=> '', //【必选】string 分期id
    *		'no_login'		    => '', //【可选】int 是否登录 1 不用登录
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
        if(empty($params['params']['no_login'])){
            if($uid != $instalmentInfo['user_id']){
                return apiResponse([], ApiStatus::CODE_50000, "用户信息错误");
            }
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


    /**
    * 分期备注信息
    * @$params array $request
    * [
    *		'instalment_id'		=> '', //【必选】string 分期id
    *		'contact_status'	=> '', //【必选】int 是否联系到用户
    *		'remark'		    => '', //【必选】string 备注信息
    * ]
    * @return bool
    */
    public function instalmentRemark(Request $request){
        $params     = $request->all();
        // 参数过滤
        $rules = [
            'instalment_id'         => 'required',  //商品编号
            'contact_status'        => 'required',  //是否联系到用户
            'remark'                => 'required',  //备注信息
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $data = $params['params'];

        $data['create_time'] = time();

        $remarkId = \App\Order\Models\OrderGoodsInstalmentRemark::insert($data);
        if(!$remarkId){
            return apiResponse([],ApiStatus::CODE_20001, "分期备注失败");
        }

        return apiResponse([],ApiStatus::CODE_0,"success");

    }

    /**
   * 分期联系日历
   * @$params array $request
   * [
   *		'instalment_id'		=> '', //【必选】string 分期id
   * ]
   * @return array instalmentList
   */
    public function instalmentRemarkList(Request $request){
        $params     = $request->all();

        // 参数过滤
        $rules = [
            'instalment_id'         => 'required',  //商品编号
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }

        $instalment_id      = $params['params']['instalment_id'];

        $remarkList = \App\Order\Models\OrderGoodsInstalmentRemark::query()
            ->where(['instalment_id' => $instalment_id])
            ->get()->toArray();

        return apiResponse($remarkList, ApiStatus::CODE_0);

    }


    /**
     * 分期列表导出接口
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function instalmentListExport(Request $request) {
        $request               = $request->all();
        $additional['page']    = isset($request['page']) ? $request['page'] : 1;
        $additional['limit']   = isset($request['limit']) ? $request['limit'] : 10000;

        $params         = filter_array($request, [
            'begin_time'    => 'required',
            'end_time'      => 'required',
            'goods_no'      => 'required',
            'order_no'      => 'required',
            'status'        => 'required',
            'mobile'        => 'required',
            'term'          => 'required',
        ]);

        $list = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::queryList($params,$additional);

        //定义excel头部参数名称
        $headers = [
            '分期ID', '订单编号', '商品编号', '用户名', '手机号', '还款日', '原始金额', '原始优惠金额', '应付金额', '实际支付金额', '支付时优惠金额', '期数', '状态', '支付类型', '扣款时间', '更新时间',
        ];

        $data = [];
        foreach($list as &$item){
            // 姓名
            $member = \App\Lib\User\User::getUser($item['user_id']);
            $item['realname']       = !empty($userInfo['realname']) ? $member['realname'] : "--";
            // 状态
            $item['status']         = OrderInstalmentStatus::getStatusName($item['status']);
            // 还款日
            $item['day']            = $item['day'] ? withholdDate($item['term'],$item['day']) : "";
            // 支付类型
            $item['pay_type']       = $item['pay_type'] == 1 ? "主动还款" : "代扣";

            $item['payment_time']   = !empty($item['payment_time']) ? date("Y-m-d H:i:s", $item['payment_time'] ) : "--";
            $item['update_time']    = !empty($item['update_time']) ? date("Y-m-d H:i:s", $item['update_time'] ) : "--";

            $data[] = [
                $item['id'],                        // 分期ID
                $item['order_no'],                  // 订单编号
                $item['goods_no'],                  // 商品编号
                $item['realname'],                  // 用户名
                $item['mobile'],                    // 手机号
                $item['day'],                       // 还款日
                $item['original_amount'],           // 原始金额
                $item['discount_amount'],           // 原始优惠金额
                $item['amount'],                    // 应付金额
                $item['payment_amount'],            // 实际支付金额
                $item['payment_discount_amount'],   // 支付时优惠金额
                $item['term'],                      // 期数
                $item['status'],                    // 状态
                $item['pay_type'],                  // 支付类型
                $item['payment_time'],              // 扣款时间
                $item['update_time'],               // 更新时间
            ];
        }

        return \App\Lib\Excel::write($data, $headers,'后台分期数据导出-');

    }

    /**
     * 扣款失败发送短信
     * @$params array $request
     * [
     * 		'instalment_id'		=> '', //【必选】int 分期ID
     *		'mobile'		    => '', //【必选】string 电话号
     * ]
     * @return array instalmentList
     */
    public function sendMessage(Request $request) {
        $params     = $request->all();
        // 参数过滤
        $rules = [
            'instalment_id'     => 'required',
            'mobile'            => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }

        $instalment_id          = $params['params']['instalment_id'];
        $mobile                 = $params['params']['mobile'];

        try{

            //发送短信
            $notice = new \App\Order\Modules\Service\OrderNotice(
                \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
                $instalment_id,
                'GivebackEvaNoWitYesEno',
                ['mobile' => $mobile]);
            $notice->notify();

        } catch (\Exception $ex) {
            return apiResponse([], ApiStatus::CODE_72000,$ex->getMessage());
        }

        return apiResponse([], ApiStatus::CODE_0, '发送短信成功');

    }

}
