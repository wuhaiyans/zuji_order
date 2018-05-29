<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Payment\UnionpayApi;
use App\Order\Modules\Service;
use Illuminate\Http\Request;


class UnionController extends Controller
{
    protected $orderTrade;

    public function __construct(Service\OrderTrade $orderTrade)
    {
        $this->orderTrade = $orderTrade;
    }

    //银联已开通银行卡列表查询接口
    public function bankCardlist(Request $request){
        $params =$request->all();
        $params =$params['params'];

        $user_id =$params['user_id'];
        $appid =$params['appid'];
        if(empty($appid)){
            return apiResponse([],ApiStatus::CODE_20001,"appid不能为空");
        }
        if(empty($user_id)){
            return apiResponse([],ApiStatus::CODE_20001,"user_id不能为空");
        }
        $data =[
            'user_id'=>$user_id,
        ];
        $res = UnionpayApi::BankCardList($data);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_60001,"请开通银行卡");
        }
        return apiResponse($res,ApiStatus::CODE_0);
    }
    
    //银联开通银行卡接口
    public function openBankCard(Request $request){
        var_dump("银联开通银行卡接口");die;
        //接收请求参数
        $params = $request->all();
        $appid =$params['appid'];
        //过滤参数
        $data = filter_array($params['data'],[
            "acc_no"  => "required", //银行卡号
            "certif_id" => 'required', //身份证号码
            "phone_no"  => "required", //	银行预留手机号
            "front_url"  => "required", //	回跳地址
            "user_id"=>"required", //用户ID
            'customer_nm'=>'required',//真实姓名
        ]);
        //验证参数
        if(count($data)!=6){
            return api_resopnse( [], ApiStatus::CODE_20001);
        }

        $res =UnionpayApi::openBankCard($data);
        if(!is_array($res)){
            return api_resopnse( [], ApiStatus::CODE_60001);
        }
        return api_resopnse($res, ApiStatus::CODE_0);

    }


    //银联查询开通结果接口
    public function getUnionStatus(Request $request){
        //接收请求参数
        $params = $request->all();
        $appid =$params['appid'];
        //过滤参数
        $data = filter_array($params['data'],[
            "acc_no"  => "required", //银行卡号
            "user_id"=>"required", //用户ID
        ]);
        //验证参数
        if(count($data)!=2){
            return api_resopnse( [], ApiStatus::CODE_20001);
        }
        $res =UnionpayApi::backPolling($data);
        if(!is_array($res)){
            return api_resopnse( [], ApiStatus::CODE_60001);
        }
        return api_resopnse($res, ApiStatus::CODE_0);

    }
    //银联短信验证码发送接口
    public function sendsms(Request $request){
        //接收请求参数
        $params = $request->all();
        $appid =$params['appid'];
        //过滤参数
        $data = filter_array($params['data'],[
            "bankcard_id"  => "required", //银行卡id
            "user_id"=>"required", //用户ID
            "order_no"  => "required", //订单号
        ]);
        //验证参数
        if(count($data)!=3){
            return api_resopnse( [], ApiStatus::CODE_20001);
        }
        $res =$this->orderTrade->sendsms($data);
        if(!$res){
            return api_resopnse( [], ApiStatus::CODE_60001);
        }
        return api_resopnse($res, ApiStatus::CODE_0);
    }
    //银联支付消费接口(限已开通银联用户)
    public function consume(Request $request){
        //接收请求参数
        $params = $request->all();
        $appid =$params['appid'];
        //过滤参数
        $data = filter_array($params['data'],[
            "bankcard_id"  => "required", //银行卡id
            "user_id"=>"required", //用户ID
            "order_no"  => "required", //订单号
            "sms_code"  => "required", // 短信验证码
        ]);
        //验证参数
        if(count($data)!=4){
            return api_resopnse( [], ApiStatus::CODE_20001);
        }
        $res =$this->orderTrade->consume($data);
        if(!$res){
            return api_resopnse( [], ApiStatus::CODE_60001);
        }
        return api_resopnse($res, ApiStatus::CODE_0);
    }

    //银联开通银行卡并支付接口
    public function openAndPay(Request $request){
        var_dump("暂时没有：银联开通银行卡并支付接口");die;
        //接收请求参数
        $params = $request->all();
        $appid =$params['appid'];
        //过滤参数
        $data = filter_array($params['data'],[
            "order_no"  => "required", //订单号
            "acc_no"  => "required", //银行卡号
            "cert_no" => 'required', //身份证号码
            "phone_no"  => "required", //	银行预留手机号
            "front_url"  => "required", //	前端回跳地址
        ]);
        //验证参数
        if(count($data)!=5){
            api_resopnse( [], ApiStatus::CODE_20001);
            return;
        }

    }

}
