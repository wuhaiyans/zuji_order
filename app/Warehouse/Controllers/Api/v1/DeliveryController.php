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
        $request =$request->all();

        $appid =$request['appid'];//获取appid
        $delivery_row['order_no'] =$request['params']['order_no'];//订单编号
        $delivery_row['delivery_detail'] =$request['params']['delivery_detail'];//发货清单

        $this->DeliveryCreate->confirmation($delivery_row);
    }

}

