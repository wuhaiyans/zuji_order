<?php
namespace App\Warehouse\Controllers\Api\v1;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    use Helpers;
    use ValidatesRequests;

    const SESSION_ERR_KEY = 'delivery.error';

    /**
     * 处理传过来的参数
     */
    protected function _dealParams($rules)
    {
//        $params = request()->input();

        $params = apiData();

        if (!isset($params['params'])) {
            return [];
        }

        if (is_string($params['params'])) {
            $param = json_decode($params['params'], true);
        } else if (is_array($params['params'])) {
            $param = $params['params'];
        }

        $validator = app('validator')->make($param, $rules);

        if ($validator->fails()) {
            session()->flash(self::SESSION_ERR_KEY, $validator->errors()->first());
            return false;
        }

        $param['app_id'] = $params['appid'];

        return $param;
    }
}