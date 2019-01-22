<?php

namespace App\Order\Controllers\Api\v1;
use App\Order\Modules\Service\OrderBlock;
use Illuminate\Http\Request;

/**
 * @var  区块链推送
 * @author limin<limin@huishoubao.com.cn>
 */

class OrderBlockController extends Controller
{
    public function orderPushBlock(Request $request){
        $params =$request->all();
        $params =$params['params'];
        if(empty($params['order_no'])){
            return  apiResponse([],ApiStatus::CODE_20001,"订单号不存在");
        }
        if(empty($params['type'])){
            return  apiResponse([],ApiStatus::CODE_20001,"类型不存在");
        }

        $ret = OrderBlock::orderPushBlock($params['order_no'],$params['type'],$params);
        return $ret;
    }

}
