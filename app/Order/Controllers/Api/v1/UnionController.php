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
    public function cardlist(Request $request){
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
        $b = UnionpayApi::BankCardList($appid,$data);
        if(!is_array($b)){
            return apiResponse([],ApiStatus::CODE_60001,"请开通银行卡");
        }
        var_dump($b);die;
        return apiResponse($b,ApiStatus::CODE_20001,"user_id不能为空");




    }



}
