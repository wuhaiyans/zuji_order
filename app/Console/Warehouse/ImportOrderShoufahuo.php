<?php
/**
 * 链接两个数据库倒数据
 *
 * 功能点
 *  1.初始化
 *  2.增量
 *  3.批量插入
 *  4.辅助数据查询一次缓存一次,下次用不用查直接用
 *
 * User: wangjinlin
 * Date: 2018/6/11
 * Time: 下午5:05
 */

//INSERT INTO order_user_address (order_no,consignee_mobile,NAME,province_id,city_id,area_id,address_info,create_time,update_time) VALUES ('20180227000697','13972858733','余华','420000','421200','','湖北 咸宁 咸安 温泉月亮湾花园17栋102'','1529047688','1529047688'),('20180227000706','18359771800','黄育权','350000','350100','','福建 福州 闽清 省璜镇璜兰村璜兰街5号','1529047688','1529047688'),('20180227000712','13972858733','余华','420000','421200','','湖北 咸宁 咸安 温泉月亮湾花园17栋102'','1529047688','1529047688')
$stime=microtime(true);
$num = 0;//统计插入条数
$sel = 0;//统计查询条数

//增量索引值
$t = time();//初始化时间 或 增量时间
$_t = 0;//上次执行时间
$order_ID_S = 0;//订单增量开始ID 初始化为0
$order_ID_N = 1000000;//订单增量结束ID 必填
// ...
echo '开始时间:'.date('Y-m-d H:i:s',$t).';<br>';
//数据库配置
$user = 'root';
$password = '123456';
$dbname1 = 'zuji';
$dbname2 = 'zuji_order';
$dbname3 = 'zuji_warehouse';
$host = '127.0.0.1';
$port = 3306;

//$user = 'root';
//$password = 'd^GHL,Oc@De3jW';
//$dbname1 = 'zuji';
//$dbname2 = 'zuji_order';
//$dbname3 = 'zuji_warehouse';
//$host = '119.29.141.207';
//$port = 3306;

//数据库1 (老)
$db1=new mysqli($host,$user,$password,$dbname1,$port);
if(mysqli_connect_error()){
    echo 'Could not connect to database 1.';
    exit;
}

//数据库2 (新订单)
$db2=new mysqli($host,$user,$password,$dbname2,$port);
if(mysqli_connect_error()){
    echo 'Could not connect to database 2.';
    exit;
}

//数据库2 (新收发货)
//$db3=new mysqli($host,$user,$password,$dbname3,$port);
//if(mysqli_connect_error()){
//    echo 'Could not connect to database 2.';
//    exit;
//}

//$db2->autocommit(false);//关闭自动提交
//$db2->rollback();//回滚
//$db2->commit();//提交
//$db2->close();//关闭
//$db2->autocommit(TRUE); //开启自动提交功能

//关闭自动提交
$db2->autocommit(false);
//$db3->autocommit(false);

//DB1 数据查询
// 查询订单
$result_order2_all1=$db1->query("SELECT order_id,order_no FROM zuji_order2 WHERE order_id>".$order_ID_S." AND order_id<".$order_ID_N);
//$result_order2_all1=$db1->query("SELECT order_id,order_no FROM zuji_order2 WHERE order_no='20180227000712'");
//echo "SELECT order_id,order_no FROM zuji_order2 WHERE order_id>".$order_ID;die;
while($arr = $result_order2_all1->fetch_assoc()){
    //订单二维数组 order_id,order_no
    $order2_all1[]=$arr;
}
$sel++;

//省,市,区县
$result_zuji_district_all1 = $db1->query("SELECT `id`,`name` FROM zuji_district");
while($arr = $result_zuji_district_all1->fetch_assoc()){
    //二维数组 id,name
    $district_all1[$arr['id']]=$arr;
}
$sel++;

//------------------导入订单收货地址------------------
if( !orderAddress($order2_all1,$district_all1,$db1,$db2,$t) ){
    $db2->rollback();//回滚
    //关闭链接
    $db1->close();
    $db2->close();
    die;

}

//------------------导入订单发货信息------------------
if( !orderDelivery($order2_all1,$db1,$db2,$t) ){
    $db2->rollback();//回滚
    //关闭链接
    $db1->close();
    $db2->close();
    die;

}

//------------------导入商品发货信息表------------------
if( !goodsDelivery($order2_all1,$db1,$db2) ){
    $db2->rollback();//回滚
    //关闭链接
    $db1->close();
    $db2->close();
    die;

}

//提交
$db2->commit();
$db2->autocommit(TRUE);
//$db3->commit();
//$db3->autocommit(TRUE);
$db1->close();
$db2->close();

$tn = time();
echo '结束时间:'.date('Y-m-d H:i:s',$tn).';<br>';
$etime=microtime(true);
echo '用时:'.($tn-$t).'(秒);<br>';
echo '查询总条数:'.$sel.';<br>';
echo '插入总条数:'.$num.';<br>';

die;



//===方法体=====================================================================================================================

/**
 * 添加订单收货地址
 *
 * $order2_all1 订单二维数组
 * $db1         链接1(老)
 * $db2         链接2(新)
 * $t           执行时间
 */
function orderAddress($order2_all1,$district_all1,$db1,$db2,$t){
    global $num,$sel;
    echo '导入订单用户收货信息开始 '.date('Y-m-d H:i:s',time()).'<br>';
    //拼接订单收货地址信息
    $district = [];//省市区 二维
    $address_insert_sql = "INSERT INTO order_user_address (order_no,consignee_mobile,name,province_id,city_id,area_id,address_info,create_time,update_time) VALUES ";
    foreach ($order2_all1 as $key=>$item){
        $order2_address_all1 = [];//初始化
        $result1=$db1->query("SELECT * FROM zuji_order2_address WHERE order_id=".$item['order_id']);
        while($arr = $result1->fetch_assoc()){
            $order2_address_all1[]=$arr;//订单收货二维数组
        }
        $sel++;
        foreach ($order2_address_all1 as $k=>$v){
            $address_info = '';//地址详情
            //省
            $address_info .= $district_all1[$v['province_id']]['name'].' ';
            //市
            $address_info .= $district_all1[$v['city_id']]['name'].' ';
            //区县
            $address_info .= $district_all1[$v['country_id']]['name'].' ';
            $address_info .= replaceSpecialChar($v['address']);

            //order_no,consignee_mobile,name,province_id,city_id,area_id,address_info,create_time,update_time
            $address_insert_sql .= "('".$item['order_no']."','".$v['mobile']."','".replaceSpecialChar($v['name'])."','".$v['province_id']."','".$v['city_id']."','".$v['country_id']."','".$address_info."','".$t."','".$t."'),";
            $num++;
        }
    }
    $address_insert_sql = substr($address_insert_sql,0,-1)
    ;
//    echo $address_insert_sql;
    if($db2->query($address_insert_sql)){
        echo '导入订单收货地址成功;<br>';
        return true;
    }else{
        echo '导入订单收货地址失败;<br>';
        return false;
    }
}

/**
 * 导入订单发货信息
 */
function orderDelivery($order2_all1,$db1,$db2,$t){
    global $num,$sel;
    echo '导入订单发货信息开始 '.date('Y-m-d H:i:s',time()).'<br>';
    //导入新订单表sql
    $order_delivery_insert_sql = "INSERT INTO order_delivery (order_no,logistics_no,logistics_id,create_time) VALUES ";
    //查询db1订单商品表
    foreach ($order2_all1 as $key=>$item) {
        $row = $db1->query("SELECT * FROM zuji_order2_delivery WHERE order_id=" . $item['order_id'])->fetch_assoc();
        $sel++;
        $order_delivery_insert_sql .= "('".$item['order_no']."','".$row['wuliu_no']."','".$row['wuliu_channel_id']."','".$t."'),";
        $num++;
    }
    $order_delivery_insert_sql = substr($order_delivery_insert_sql,0,-1);
//    echo $goods_delivery_insert_sql;
    if($db2->query($order_delivery_insert_sql)){
        echo '导入订单发货信息表成功;<br>';
        return true;
    }else{
        echo '导入订单发货信息表失败;<br>';
        return false;
    }

}

/**
 * 导入商品发货信息表
 */
function goodsDelivery($order2_all1,$db1,$db2){
    global $num,$sel;
    echo '导入商品发货信息开始 '.date('Y-m-d H:i:s',time()).'<br>';
    //导入新订单表sql
    $goods_delivery_insert_sql = "INSERT INTO order_goods_delivery (order_no,goods_no,imei1,imei2,imei3,serial_number,status) VALUES ";
    //查询db1订单商品表
    foreach ($order2_all1 as $key=>$item) {
        $row = $db1->query("SELECT * FROM zuji_order2_goods WHERE order_id=" . $item['order_id'])->fetch_assoc();
        $sel++;
        $sku_row=$db1->query("SELECT `sn` FROM zuji_goods_sku WHERE sku_id=".$row['sku_id'])->fetch_assoc();
        $sel++;
        $goods_delivery_insert_sql .= "('".$item['order_no']."','".$sku_row['sn']."','".$row['imei1']."','".$row['imei2']."','".$row['imei3']."','".$row['serial_number']."','1'),";
        $num++;
    }
    $goods_delivery_insert_sql = substr($goods_delivery_insert_sql,0,-1);
//    echo $goods_delivery_insert_sql;
    if($db2->query($goods_delivery_insert_sql)){
        echo '导入订单商品发货信息表成功;<br>';
        return true;
    }else{
        echo '导入订单商品发货信息表失败;<br>';
        return false;
    }

}

//过滤特殊字符
function replaceSpecialChar($strParam){
    $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
    return preg_replace($regex,"",$strParam);
}