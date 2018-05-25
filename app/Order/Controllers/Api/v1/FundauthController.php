<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use App\Lib\Payment\CommonFundAuthApi;
use App\Order\Modules\Repository\OrderPayWithholdRepository;

class FundauthController extends Controller
{

    /**
     * 预授权获取URL接口
     * @param array $params
     * [
     *		'out_auth_no'	    => '', //业务系统授权码
     *		'amount'			=> '', //授权金额；单位：分
     *		'channel_type'		=> '', //授权渠道
     *		'front_url'			=> '', //前端回跳地址
     *		'back_url'			=> '', //后台通知地址
     *		'name'				=> '', //预授权名称
     *		'user_id'			=> '', //用户ID
     */
    public function fundauth(Request $request){

        $params     = $request->all();

        $rules = [
            'amount'            => 'required',      //授权金额；单位：分
            'front_url'         => 'required',      //前端回跳地址
            'name'              => 'required',      //预授权名称
            'user_id'           => 'required|int',  //用户ID
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];

        $params['out_auth_no']  = createNo(4);         //订单系统授权码
        $params['channel_type'] = 2;                   //渠道 默认为2 支付宝
        $params['back_url']     = env("API_INNER_URL") . "/fundauthNotify";//后台通知地址

        $data = CommonFundAuthApi::fundauthUrl($params);
        $url = !empty($data['authorization_url']) ? $data['authorization_url'] : "";
        return apiResponse(['url'=>$url],ApiStatus::CODE_0,"success");
    }

    /*
    * 预授权状态查询接口
    * @request array
    * [
    *      'appid'     => '' //渠道ID
    *      'params'    => [
    *           'user_id'        => '' // 用户id
    *      ]
    * ]
    * return String $url  预授权地址
    */
    public function fundauth_query(Request $request){
        $params     = $request->all();
        $rules = [
            'user_id'           => 'required|int',  //用户id
        ];

        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];

        $withholdInfo = OrderPayWithholdRepository::find($params['user_id']);
        if(!$withholdInfo){
            return apiResponse([], ApiStatus::CODE_81001, "获取用户协议错误");
        }

        $data = [
            'auth_no'       => $withholdInfo['withhold_no'],
            'out_auth_no'   => $withholdInfo['out_withhold_no'],
            'user_id'       => $params['user_id'],
        ];

        $result = CommonFundAuthApi::queryFundAuthStatus( $data );
        if(!$result){
            apiResponse([],ApiStatus::CODE_81002, "预授权状态查询失败");
        }

        apiResponse($result,ApiStatus::CODE_0,"success");
    }

    /**
     * 预授权解冻接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'user_id' => '',    //用户ID
     *		'amount' => '',     //解冻金额 单位：分
     *		'name' => '',       //名称
     * ]
     */
    public function fundauth_unfreeze( Request $request ){
        $params     = $request->all();
        $rules = [
            'amount'            => 'required',      //授权金额；单位：分
            'name'              => 'required',      //预授权名称
            'user_id'           => 'required|int',  //用户ID
        ];

        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];

        $withholdInfo = OrderPayWithholdRepository::find($params['user_id']);
        if(!$withholdInfo){
            return apiResponse([], ApiStatus::CODE_81001, "获取用户协议错误");
        }
        $data['out_trade_no']   = $withholdInfo['out_trade_no'];
        $data['auth_no']        = $withholdInfo['auth_no'];
        $data['back_url']       = env("API_INNER_URL") . "/fundauthUnfreezeNotify";//后台通知地址


        $result = CommonFundAuthApi::unfreeze( $params );
        if(!$result){
            apiResponse([],ApiStatus::CODE_81003, "预授权解冻失败");
        }

        apiResponse([],ApiStatus::CODE_0,"success");
    }

    /**
     * 预授权转支付接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'name' => '',   //交易名称
     *		'amount' => '', //交易金额；单位：分
     *		'user_id' => '', //用户ID
     * ]
     */
    public function fundauth_to_pay( Request $request ){
        $params     = $request->all();
        $rules = [
            'amount'            => 'required',      //授权金额；单位：分
            'name'              => 'required',      //预授权名称
            'user_id'           => 'required|int',  //用户ID
        ];

        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];

        $withholdInfo = OrderPayWithholdRepository::find($params['user_id']);
        if(!$withholdInfo){
            return apiResponse([], ApiStatus::CODE_81001, "获取用户协议错误");
        }

        $data = [
            'name'		    => $params['name'],                 //交易名称
     		'out_trade_no'  => $withholdInfo['withhold_no'],    //订单系统交易码
     		'auth_no'       => $withholdInfo['out_withhold_no'],//支付系统授权码
     		'amount'        => $params['amount'],               //解冻金额 单位：分
     		'back_url'      => env("API_INNER_URL") . "/unfreezeAndPayNotify",//后台通知地址
     		'user_id'       => $params['user_id'],              //用户id
        ];

        $result = CommonFundAuthApi::unfreezeAndPay($data);
        if(!$result){
            apiResponse([], ApiStatus::CODE_81004, "预授权转支付失败");
        }

        apiResponse([], ApiStatus::CODE_0, "success");
    }

}
