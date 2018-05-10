<?php

namespace App\Warehouse\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Warehouse\Modules\Service\ReceiveService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceiveController extends Controller
{

    use ValidatesRequests;

    const SESSION_ERR_KEY = 'delivery.error';

    protected $DeliveryCreate;

    public function __construct(ReceiveService $service)
    {
        $this->receive = $service;
    }


//    public function receiveList()
//    {
////        DB::connection('foo');
//        echo "收货表列表接口";
//    }
//
//    /**
//     * 创建收货单
//     */
//    public function receiveCreate()
//    {
//
//    }


    /**
     * 列表
     */
    public function list()
    {
        $params = $this->_dealParams([]);
        $list = $this->receive->list($params);
        return \apiResponse($list);
    }

    /**
     * 清单查询
     */
    public function show()
    {

    }

    /**
     * 创建
     */
    public function create()
    {

    }

    /**
     * 取消收货单
     */
    public function cancel()
    {

    }

    /**
     * 签收
     */
    public function received()
    {

    }

    /**
     * 取消签收
     */
    public function calcelReceive()
    {

    }


    /**
     * 验收 针对设备
     */
    public function check()
    {

    }

    /**
     * 取消验收 针对设备
     */
    public function cancelCheck()
    {

    }

    /**
     * 完成签收 针对收货单
     */
    public function finishCheck()
    {

    }

    /**
     * 录入检测项
     */
    public function note()
    {

    }


    /**
     * 处理传过来的参数
     */
    private function _dealParams($rules)
    {
        $params = request()->input();

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
            session()->flash(self::SESSION_ERR_KEY, $validator->errors()->first());
            return false;
        }

        return $params;
    }

}

