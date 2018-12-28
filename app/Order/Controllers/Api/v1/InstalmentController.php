<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderLogRepository;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderGoodsInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Service\OrderGoods;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Lib\Excel;

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
            'order_no'      => 'required',
            'goods_no'      => 'required',
            'status'        => 'required',
            'beoverdue_day' => 'required',
            'kw_type'       => 'required',
            'keywords'      => 'required',
            'term'          => 'required',
        ]);

        if(isset($params['keywords'])){
            if($params['kw_type'] == 1){
                $params['order_no'] = $params['keywords'];
            }
            elseif($params['kw_type'] == 2){
                $params['mobile'] = $params['keywords'];
            }
            else{
                $params['order_no'] = $params['keywords'];
            }
        }        
        $params['is_instalment_list'] = 1;
        $list = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::queryList($params,$additional);   
        foreach($list as &$item){

            $item['payment_time']   = $item['payment_time'] ? date("Y-m-d H:i:s",$item['payment_time']) : "";
            $item['update_time']    = $item['update_time'] ? date("Y-m-d H:i:s",$item['update_time']) : "";

            // 姓名
            $member = \App\Order\Models\OrderUserCertified::where(['order_no'=>$item['order_no']])->first();
            $item['realname']       = !empty($member['realname']) ? $member['realname'] : "--";

            //线下手动还款按钮
            if(in_array($item['status'], [OrderInstalmentStatus::SUCCESS,OrderInstalmentStatus::CANCEL])){
                $item['confirm_btn'] = false;
            }else{
                $item['confirm_btn'] = true;
            }

            //逾期天数
            if($item['status'] == OrderInstalmentStatus::UNPAID || $item['status'] == OrderInstalmentStatus::FAIL){
                $item['beover_due']      = $item['withhold_day'] ? getBeoverdue($item['withhold_day']) : "";
            }

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
            if($orderInfo['order_status'] == \App\Order\Modules\Inc\OrderStatus::OrderInService && $orderInfo['pay_type'] != \App\Order\Modules\Inc\PayInc::FlowerFundauth){
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
        $couponInfo = \App\Lib\Coupon\Coupon::getUserCoupon($instalmentInfo['user_id'],$orderInfo['appid']);
        if(is_array($couponInfo) && $couponInfo['youhui'] > 0){
            $discount_amount = $couponInfo['youhui'] / 100;

            if($discount_amount >= $instalmentInfo['amount']){
                $instalmentInfo['discount_amount']     = $instalmentInfo['amount'];
                $instalmentInfo['amount']              = '0.00';
            }else{
                $amount = $instalmentInfo['amount'] - $couponInfo['youhui'];

                $instalmentInfo['discount_amount']     = $discount_amount;
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
        set_time_limit(0);
        try{

            $params  = $request->all();

            if(isset($params['keywords'])){
                if($params['kw_type'] == 1){
                    $params['order_no'] = $params['keywords'];
                }
                elseif($params['kw_type'] == 2){
                    $params['mobile'] = $params['keywords'];
                }
                else{
                    $params['order_no'] = $params['keywords'];
                }
            }
            $params['is_instalment_list'] = 1;

            LogApi::info("[instalmentListExport]",$params);

            $params['page']     = !empty($params['page']) ? $params['page'] : 1;
            $outPages           = !empty($params['page']) ? $params['page'] : 1;

            $total_export_count = 5000;

            $pre_count = 500;

            $smallPage = ceil($total_export_count/$pre_count);

            $i = 1;
            header ( "Content-type:application/vnd.ms-excel" );
            header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "后台分期列表数据导出" ) . ".csv" );

            // 打开PHP文件句柄，php://output 表示直接输出到浏览器
            $fp = fopen('php://output', 'a');

            // 租期，成色，颜色，容量，网络制式
            $headers = ['商品名称','机型','租期', '第几期还款','本月应扣金额','碎屏险卖价','碎屏险成本','扣款状态','扣款成功时间'];

            // 将中文标题转换编码，否则乱码
            foreach ($headers as $k => $v) {
                $column_name[$k] = iconv('utf-8', 'GB18030', $v);
            }

            // 将标题名称通过fputcsv写到文件句柄
            fputcsv($fp, $column_name);

            while(true){
                if ($i > $smallPage) {
                    exit;
                }

                $offset = ( $outPages - 1) * $total_export_count;

                $params['page'] = intval(($offset / $pre_count) + $i) ;
                ++$i;


                $list = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::instalmentExport($params);

                $data = [];

                foreach($list as &$item){
                    // 状态
                    $item['status']             = OrderInstalmentStatus::getStatusName($item['status']);
                    // 还款日
                    $item['payment_time']       = !empty($item['payment_time']) ? date('Y-m-d H:i:s',$item['payment_time']) : "--";

                    $data[] = [
                        $item['goods_name'],                // 商品名称
                        $item['specs'],                     // 机型
                        $item['zuqi'],                      // 租期
                        $item['times'],                     // 第几期还款
                        $item['amount'],                    // 本月应扣金额
                        $item['insurance'],                 // 碎屏险卖价
                        $item['insurance_cost'],            // 碎屏险成本
                        $item['status'],                    // 扣款状态
                        $item['payment_time'],              // 扣款成功时间
                    ];
                }

                $Excel =  Excel::csvOrderListWrite($data, $fp);
            }


            return $Excel;

        } catch (\Exception $e) {

            return apiResponse([],ApiStatus::CODE_50000,$e);

        }
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
                'WithholdFailInitiative',
                ['mobile' => $mobile]);
            $notice->notify();

        } catch (\Exception $ex) {
            return apiResponse([], ApiStatus::CODE_72000,$ex->getMessage());
        }

        return apiResponse([], ApiStatus::CODE_0, '发送短信成功');

    }

    /**
     * 线下手动还款确认接口
     * @$params array $request
     * [
     * 		'instalment_id'		=> '', //【必选】int 分期ID
     *		'remark'		    => '', //【可选】string 备注
     *		'trade_no'		    => '', //【可选】string 交易号
     * ]
     * @return bool
     */
    public function repaymentConfirm(Request $request){
        //接收参数
        $params = $request->all();
        $userInfo = $params['userinfo'];
        $params = $params['params'];

        //验证参数
        if (empty($params['instalment_id'])){
            return apiResponse([],ApiStatus::CODE_20001,"instalment_id必须");
        }
        if ($params['trade_no']){
            $data['trade_no'] = $params['trade_no'];
        }
        if ($params['remark']){
            $data['remark'] = $params['remark'];
        }
        //获取该条分期详情
        $instalmentDetail = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::getInfoById($params['instalment_id']);
        //验证是否已还款或者已取消
        if(in_array($instalmentDetail['status'], [OrderInstalmentStatus::SUCCESS,OrderInstalmentStatus::CANCEL])){
            return apiResponse([],ApiStatus::CODE_50000,"分期单状态异常");
        }
        //更新分期单
        $nowTime = time();
        $data['pay_type'] = 2;
        $data['status'] = 2;
        $data['payment_time'] = $nowTime;
        $data['update_time'] = $nowTime;

        $where['id'] = $params['instalment_id'];
        $ret = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::save($where,$data);
        if(!$ret){
            return apiResponse([], ApiStatus::CODE_50000, '还款失败');
        }
        //插入订单日志
        OrderLogRepository::add($userInfo['uid'],$userInfo['username'],$userInfo['type'],$instalmentDetail['order_no'],$instalmentDetail['term']."期线下还款","还款成功");

        return apiResponse([], ApiStatus::CODE_0, '还款成功');
    }
}
