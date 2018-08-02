<?php
namespace App\OrderUser\Controllers\Api\v1;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    use Helpers;
    use ValidatesRequests;

    //缓存错误
    const SESSION_ERR_KEY = 'orderuser.error';

    /**
     * 处理传过来的参数
     *
     * @param array $rules 规则
     * [
     *  'name' => 'required',//表示发过来的请求  name是必须字段，没有则报缺少参数错误
     * ]
     */
    protected function _dealParams($rules)
    {
        $params = request()->input();

//        $params = apiData();

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