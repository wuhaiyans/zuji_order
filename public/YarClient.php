<?php
/**
 *
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2019/1/8 0008
 * Time: 下午 6:52
 *yar_client.php
 */
$url = "http://dev-order.com/YarServer.php";

echo str_repeat("-",40),"single request",str_repeat("-",40),"\n";
$start = time();
$c = new Yar_Client($url);
$c->test1("single arg1");
$c->test2("single arg2");
$c->test3("single arg3");
$c->test4("single arg4");
echo str_repeat("-",30),"time:",time()-$start,str_repeat("-",30),"\n";

echo str_repeat("-",40),"concurrent",str_repeat("-",40),"\n";
$start = time();
 Yar_Concurrent_Client::call($url,"test1",array("arg1"),"callback");
 Yar_Concurrent_Client::call($url,"test2",array("arg1"),"callback");
 Yar_Concurrent_Client::call($url,"test3",array("arg1"),"callback");
 Yar_Concurrent_Client::call($url,"test4",array("arg1"),"callback");
 Yar_concurrent_Client::loop();
 function callback($retval,$info){
     $args = func_get_args();
     $args['time'] = date("y-m-d H:i:s");
     var_dump($args);
 }
echo str_repeat("-",30),"time:",time()-$start,str_repeat("-",30),"\n";