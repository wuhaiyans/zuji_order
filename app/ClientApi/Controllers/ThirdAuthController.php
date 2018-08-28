<?php
/**
 *      author: heaven
 *      验证client，token信息,请求真实api地址信息，并返回数据
 *      date: 2018-06-08
 */
namespace App\ClientApi\Controllers;
use Illuminate\Http\Request;

use App\Lib\Common\LogApi;
use App\Lib\User\User;
use App\ClientApi\Controllers;
use App\Lib\Curl;
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\Log;

class ThirdAuthController extends Controller{

	
	public function getUrl(Request $request){
        $params =$request->all();
        $rules = [
            'callback_url'  => 'required',
            'order_no'  => 'required',
            'pay_channel_id'  => 'required',
            'extended_params'  => 'required',	// 支付扩展参数
        ];
	}
	
}


