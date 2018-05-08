<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 10:01
 */

namespace App\Warehouse\Controllers\Api\v1;


use App\Warehouse\Models\Delivery;


class TestController extends Controller
{
    public function test()
    {


        return Delivery::find('a1')->imeis;


//        return DeliveryGoods::class;
//        return \App\Warehouse\Models\Delivery::generateSerial();
//        return Delivery::cancel(121);
//        return Delivery::apply(121);

    }

    public function apply()
    {
        $request = request()->input();

        dd($request);die;
    }
}