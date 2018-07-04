<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $list = [
            'create_time'       => "",  //日期范围
            'business_type'     =>  \App\Order\Modules\Inc\OrderStatus::getBusinessType(),             
            'appid'             => [    //入账渠道
                1   => '生活号',
                2   => '分期代扣',
                3   => '主动还款',
            ],
            'channel'           => [    //入账方式
                1   => '银联',
                2   => '支付宝',
                3   => '京东支付',
            ],
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

        //获取订单信息
        $OrderRepository= new \App\Order\Modules\Repository\OrderRepository();
        $orderInfo = $OrderRepository->get_order_info(['order_no'=>$info['order_no']]);
        $orderInfo = $orderInfo[0];

        $memberInfo = \App\Lib\User\User::getUser($orderInfo['user_id']);

        $info['realname']   =   isset($memberInfo['realname']) ? $memberInfo['realname'] : "";
        $info['mobile']     =   isset($memberInfo['mobile']) ? $memberInfo['mobile'] : "";

        // 入账订单
        if($info['business_type'] == 1){
            $info['remark'] = isset($orderInfo['remark']) ? $orderInfo['remark'] : "";
        }else{

            // 查询分期
            $instalmentInfo = \App\Order\Modules\Service\OrderGoodsInstalment::queryInfo(['trade_no'=>$info['business_no']]);
            $info['remark'] = isset($instalmentInfo['remark']) ? $instalmentInfo['remark'] : "";
        }

        // 入账类型
        $type = [
            1 => "下单支付",
            2 => "分期代扣",
            3 => "主动还款",
        ];

        // 入账方式
        $channel = [
            1 => "银联",
            2 => "支付宝",
            3 => "京东支付",
        ];

        $info['create_time']    = date("Y-m-d H:i:s",$info['create_time']);
        // 入账类型
        $info['business_type']  = $type[$info['business_type']];
        // 入账方式
        $info['channel']        = $channel[$info['channel']];

        return apiResponse($info,ApiStatus::CODE_0,"success");
    }



}
