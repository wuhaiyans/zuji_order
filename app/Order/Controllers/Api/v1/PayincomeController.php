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

        $list = \App\Order\Modules\Repository\OrderPayIncomeRepository::queryList($params,$additional);

        if(!is_array($list)){
            return apiResponse([], ApiStatus::CODE_50000, "程序异常");
        }
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
        $Info = \App\Order\Modules\Repository\OrderPayIncomeRepository::getInfoById($income_id);

        p($Info);
        if(!is_array($list)){
            return apiResponse([], ApiStatus::CODE_50000, "程序异常");
        }
        return apiResponse($list,ApiStatus::CODE_0,"success");
    }

}
