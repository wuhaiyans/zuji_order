<?php
/**
 * Created by PhpStorm.
 * User: FF
 * Date: 2018/5/2
 * Time: 10:35
 */
class test{
    private $orderCreater;
    public function __construct(OrderCreater $orderCreater,UserComponnet $userComponnet)
    {
        //生成订单编号
        $this->orderCreater =   $orderCreater;
    }

    public function test1($id){
       if($id<1){

          Test::test();
       }
    }
}