<?php

namespace App\Order\Controllers\Api\v1;
use App\Order\Models\Order;

class OrderController extends Controller
{
    public function store()
    {

//        echo 2344;exit;
        $order = Order::all();
        dd($order);
//        Auth::guard('api')->fromUser($user);
//         return $this->response->array(['test_message' => 'store verification code']);

    }
}
