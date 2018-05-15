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



    protected function  validateParams($rules)
    {
        $params = request()->all();

        if (!isset($params['params'])) {
            return [];
        }

        if (is_string($params['params'])) {
            $params = json_decode($params['params'], true);
        } else if (is_array($params['params'])) {
            $params = $params['params'];
        }

        $validator = app('validator')->make($params, $rules);

        if ($validator->fails()) {
            return apiResponse([], ApiStatus::CODE_10101,$validator->errors()->first());
            return false;
        }

        return $params;
    }
}