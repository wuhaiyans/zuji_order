<?php
/**
 *  检查app是否审核通过
 *   heaven
 *   date:2018-07-17
 */
namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;

class CheckAppController extends Controller
{


    public function index(){
        $android =   config('web.check_verify_app_android');
        $ios =   config('web.check_verify_app_ios');
        $res    = [
            'android' => $android,
            'ios' => $ios,
        ];
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }






}
