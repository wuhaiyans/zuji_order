<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Controllers\Api\v1;


use App\Http\Requests\Request;
use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderOperate;

class deliveryController extends Controller
{
    /**
     *  确认收货接口
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function deliveryReceive(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        $rules = [
            'order_no'  => 'required',
            'role'=>'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];

        $res = OrderOperate::deliveryReceive($params['order_no'],$params['role']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_30012);
        }
        return apiResponse([],ApiStatus::CODE_0);
    }
}