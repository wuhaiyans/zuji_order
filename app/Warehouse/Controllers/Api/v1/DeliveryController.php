<?php

namespace App\Warehouse\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Warehouse\Modules\Service\DeliveryCreater;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    protected $DeliveryCreate;

    public function __construct(DeliveryCreater $DeliveryCreate)
    {
        $this->DeliveryCreate = $DeliveryCreate;
    }


    public function deliveryList(){
//        DB::connection('foo');
        echo "收货表列表接口";
    }

    /**
     * 创建发货单
     */
    public function deliveryCreate(Request $request){
        $orders =$request->all();

        $appid =$orders['appid'];//获取appid
        $order_no =$orders['params']['order_no'];//订单编号
        $delivery_detail =$orders['params']['delivery_detail'];//发货清单

        $delivery_row = [
//            'delivery_no'=>
        ];

    }

}

