<?php

namespace App\Http\Controllers\Api\v1;
use App\Models\Order;

class OrderController extends Controller
{
    public function store()
    {

//        echo 2344;exit;
        $order = Order::find(5);
        dd($order);
//        Auth::guard('api')->fromUser($user);
//         return $this->response->array(['test_message' => 'store verification code']);

    }
}
