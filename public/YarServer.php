<?php
/**
 *
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2019/1/8 0008
 * Time: 下午 6:48
 * yar_server.php
 */
class API {
    /**
     * the doc info will be generated automatically into service info page.
     * @params
     * @return
     */
    public function api2() {
        echo "api function";
    }
    public function test1(){
        sleep(1);                //模拟实际情况
        $arg = func_get_args();
        echo "test1 method with arg 0[$arg[0]] \n";
        return "ret1:".date("y-m-d H:i:s");
    }
    public function test2(){
        sleep(2);               //模拟实际情况
        $arg = func_get_args();
        echo "test2 method with arg 0[$arg[0]] \n";
        return "ret2:".date("y-m-d H:i:s");
    }
    public function test3(){
        sleep(3);             echo  '<span style="font-family: Arial, Helvetica, sans-serif;">//模拟实际情况</span>';
            $arg = func_get_args();
        echo "test3 method with arg 0[$arg[0]] \n";
    return "ret3:".date("y-m-d H:i:s");
}
    public function test4(){
        sleep(4);
        echo '<span style="font-family: Arial, Helvetica, sans-serif;">//模拟实际情况</span>';
            $arg = func_get_args();
    echo "test4 method with arg 0[$arg[0]] \n";
    return "ret4:".date("y-m-d H:i:s");
}
    protected function client_can_not_see() {
        echo __FILE__;
    }
    protected function client_can_not_see2() {
        echo __FILE__;
    }
}

$service = new Yar_Server(new API());
$service->handle();

?>