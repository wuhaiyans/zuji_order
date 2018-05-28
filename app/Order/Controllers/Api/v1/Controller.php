<?php
namespace App\Order\Controllers\Api\v1;
use App\lib\ApiStatus;
use Illuminate\Http\Request;
use Validator;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    use Helpers;


    /**
     * 参数验证函数
     * Author: heaven
     * @param $rules 验证的规则
     * @param $params 验证的参数
     * @return array
     */
    protected function  validateParams($rules, $params)
    {

        if (!isset($params['params'])) {
            return apiResponseArray(ApiStatus::CODE_10102,[]);
        }

        if (is_string($params['params'])) {
            $params = json_decode($params['params'], true);
        } else if (is_array($params['params'])) {
            $params = $params['params'];
        }

        $validator = app('validator')->make($params, $rules);
        v($validator);
        if ($validator->fails()) {
            return apiResponseArray(ApiStatus::CODE_10102,[], $validator->errors()->first());
        }

        return apiResponseArray(ApiStatus::CODE_0, []);
    }


    /**
     * 队列处理成功，返回该函数
     * @param int $type
     * @return string
     */
    protected function innerOkMsg(){
        $returnData = array('status'=>'ok');
        return response()->json($returnData);

    }

    /**
     *
     * 消费处理失败，返回该函数，处理失败，队列可能会开启重试机制
     * @param int $type
     * @return string
     */
    protected function innerErrMsg(){
        $returnData = array('status'=>'error');
        return response()->json($returnData);

    }
}