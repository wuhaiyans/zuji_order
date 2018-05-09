<?php

namespace App\Warehouse\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Warehouse\Modules\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceiveController extends Controller
{
    protected $DeliveryCreate;

    public function __construct(Service\DeliveryCreater $DeliveryCreate)
    {
        $this->DeliveryCreate = $DeliveryCreate;
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

}

