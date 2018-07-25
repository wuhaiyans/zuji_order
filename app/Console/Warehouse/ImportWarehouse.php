<?php
/**
 * 老库导入收发货库
 *
 *
 * User: wangjinlin
 * Date: 2018/6/11
 * Time: 下午5:05
 */
//$stime=microtime(true);

//header("Content-type:text/html;charset=utf-8");
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
$user = 'nqyong';//用户名
$password = 'nqy3854MysqldB';//密码
$dbname1 = 'zuji';//老数据库库名
$dbname2 = 'zuji_order';//新订单系统库名
$dbname3 = 'zuji_warehouse';//新收发货库名
$host = '127.0.0.1';//host
$port = 3306;//端口

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
mysqli_query($db1,'set names utf8');

//数据库2 (新订单)
//$db2=new mysqli($host,$user,$password,$dbname2,$port);
//if(mysqli_connect_error()){
//    echo 'Could not connect to database 2.';
//    exit;
//}

//数据库2 (新收发货)
$db3=new mysqli($host,$user,$password,$dbname3,$port);
if(mysqli_connect_error()){
    echo 'Could not connect to database 3.';
    exit;
}
mysqli_query($db3,'set names utf8');

//$db2->autocommit(false);//关闭自动提交
//$db2->rollback();//回滚
//$db2->commit();//提交
//$db2->close();//关闭
//$db2->autocommit(TRUE); //开启自动提交功能

//关闭自动提交
//$db2->autocommit(false);
$db3->autocommit(false);

//DB1 数据查询
// 查询订单
$appid = "appid in (1,2,3,4,7,8,9,11,12,13,14,15,16,18,21,22,28,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,93,94,95,96,97,98,122,123,131,132)";
$result_order2_all1=$db1->query("SELECT order_id,business_key,order_no,appid FROM zuji_order2 WHERE order_id>".$order_ID_S." AND order_id<".$order_ID_N." AND business_key=1 AND ".$appid);
//$result_order2_all1=$db1->query("SELECT order_id,order_no FROM zuji_order2 WHERE order_no='20180227000712'");
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

//------------------导入发货------------------
if( !orderDelivery($order2_all1,$district_all1,$db1,$db3) ){
    $db3->rollback();//回滚
    //关闭链接
    $db1->close();
    $db3->close();
    die;

}

//------------------导入收货、检测------------------
if( !orderReceive($order2_all1,$district_all1,$db1,$db3) ){
    $db3->rollback();//回滚
    //关闭链接
    $db1->close();
    $db3->close();
    die;

}

//提交
//$db2->commit();
//$db2->autocommit(TRUE);
$db3->commit();
$db3->autocommit(TRUE);
$db1->close();
$db3->close();

$tn = time();
echo '结束时间:'.date('Y-m-d H:i:s',$tn).';<br>';
//$etime=microtime(true);
echo '用时:'.($tn-$t).'(秒);<br>';
echo '查询总条数:'.$sel.';<br>';
echo '插入总条数:'.$num.';<br>';

die;



//===方法体=====================================================================================================================

/**
 * 导入发货
 *
 * $order2_all1 订单二维数组
 * $db1         链接1(老)
 * $db3         链接3(新)
 */
function orderDelivery($order2_all1,$district_all1,$db1,$db3){
    global $num,$sel;
    //拼接发货单sql
    $delivery_insert_sql = "INSERT INTO zuji_delivery (delivery_no,app_id,order_no,logistics_id,logistics_no,customer,customer_mobile,customer_address,status,create_time,delivery_time,status_time,status_remark,receive_type,business_key) VALUES ";
    //拼接发货商品清单sql
    $delivery_goods_insert_sql = "INSERT INTO zuji_delivery_goods (delivery_no,goods_no,serial_no,goods_name,quantity,quantity_delivered,status,status_time) VALUES ";
    //拼接设备IMEI号表sql
    $delivery_goods_imei_insert_sql = "INSERT INTO zuji_delivery_goods_imei (delivery_no,goods_no,serial_no,imei,apple_serial,status,price,create_time,status_time) VALUES ";

    foreach ($order2_all1 as $key=>$item){
        $delivery_all1 = [];
        $delivery_result=$db1->query("SELECT * FROM zuji_order2_delivery WHERE order_id=".$item['order_id']." AND business_key IN(1,5,6,9) ORDER BY delivery_id DESC");
        while($arr = $delivery_result->fetch_assoc()){
            //订单二维数组 order_id,order_no
            $delivery_all1[]=$arr;
        }
        $sel++;
        if( !$delivery_all1 ){
            continue;
        }
        $address_row=$db1->query("SELECT * FROM zuji_order2_address WHERE order_id=".$item['order_id']." ORDER BY address_id DESC LIMIT 1")->fetch_assoc();
        $sel++;
        $goods_row=$db1->query("SELECT * FROM zuji_order2_goods WHERE order_id=".$item['order_id']." ORDER BY goods_id DESC LIMIT 1")->fetch_assoc();
        $sel++;
        //$sku_row=$db1->query("SELECT `sn` FROM zuji_goods_sku WHERE sku_id=".$goods_row['sku_id'])->fetch_assoc();
        //$sel++;

        foreach ($delivery_all1 as $k=>$delivery_row){
            $delivery_no = $delivery_row['delivery_id'];
            $address_info = '';//地址详情

            //省
            $address_info .= $address_row['province_id']?$district_all1[$address_row['province_id']]['name'].' ':'';
            //市
            $address_info .= $address_row['city_id']?$district_all1[$address_row['city_id']]['name'].' ':'';
            //区县
            $address_info .= $address_row['country_id']?$district_all1[$address_row['country_id']]['name'].' ':'';
            $address_info .= $address_row['address']?replaceSpecialChar($address_row['address']):'';

            $delivereyGoods = getDeliveryGoodsStatus($delivery_row['delivery_status']);

            $delivery_insert_sql .= "('".$delivery_no."','".$item['appid']."','".$item['order_no']."','".$delivery_row['wuliu_channel_id']."','".$delivery_row['wuliu_no']."','".($address_row['name']?replaceSpecialChar($address_row['name']):'')."','".$address_row['mobile']."','".$address_info."','".getStatus($delivery_row['delivery_status'])."','".$delivery_row['create_time']."','".$delivery_row['delivery_time']."','".$delivery_row['update_time']."','系统导入','3','".replaceBusinessKey($delivery_row['business_key'])."'),";
            $num++;
            $delivery_goods_insert_sql .= "('".$delivery_no."','".$goods_row['goods_id']."','1','".$goods_row['sku_name']."','1','".$delivereyGoods[0]."','".$delivereyGoods[1]."','".$goods_row['update_time']."'),";
            $num++;
            //imei
            if ($goods_row['serial_number']){
                //苹果
                $delivery_goods_imei_insert_sql .= "('".$delivery_no."','".$goods_row['goods_id']."','1','".replaceSpecialChar($goods_row['imei1'])."','".$goods_row['serial_number']."','1','".$goods_row['recycle_price']."','".$goods_row['create_time']."','".$goods_row['update_time']."'),";
                $num++;
            }else{
                //安卓
                $delivery_goods_imei_insert_sql .= "('".$delivery_no."','".$goods_row['goods_id']."','1','".replaceSpecialChar($goods_row['imei1'])."','0','1','".$goods_row['recycle_price']."','".$goods_row['create_time']."','".$goods_row['update_time']."'),";
                $num++;
                if($goods_row['imei2']){
                    $delivery_goods_imei_insert_sql .= "('".$delivery_no."','".$goods_row['goods_id']."','2','".replaceSpecialChar($goods_row['imei2'])."','0','1','".$goods_row['recycle_price']."','".$goods_row['create_time']."','".$goods_row['update_time']."'),";
                    $num++;
                }
                if($goods_row['imei3']){
                    $delivery_goods_imei_insert_sql .= "('".$delivery_no."','".$goods_row['goods_id']."','3','".replaceSpecialChar($goods_row['imei3'])."','0','1','".$goods_row['recycle_price']."','".$goods_row['create_time']."','".$goods_row['update_time']."'),";
                    $num++;
                }
            }
        }
    }
    $delivery_insert_sql = substr($delivery_insert_sql,0,-1);
    $delivery_goods_insert_sql = substr($delivery_goods_insert_sql,0,-1);
    $delivery_goods_imei_insert_sql = substr($delivery_goods_imei_insert_sql,0,-1);
    if ($delivery_insert_sql) {
        if( !$db3->query($delivery_insert_sql) ){
            echo '导入发货单失败;';
            echo $delivery_insert_sql;
            return false;
        }
    }
    if ($delivery_goods_insert_sql) {
        if( !$db3->query($delivery_goods_insert_sql) ){
            echo '导入发货商品清单失败;';
            echo $delivery_goods_insert_sql;
            return false;
        }
    }
    if ($delivery_goods_imei_insert_sql) {
        if( !$db3->query($delivery_goods_imei_insert_sql) ){
            echo '导入发货设备IMEI号表失败;';
            echo $delivery_goods_imei_insert_sql;
            return false;
        }
    }

    echo '导入发货单,发货商品清单,发货设备IMEI号表成功;';
    return true;
}

/**
 * 导入收货表,检测信息
 */
function orderReceive($order2_all1,$district_all1,$db1,$db3){
    global $num,$sel;
    //导入收货单sql
    $receive_insert_sql = "INSERT INTO zuji_receive (receive_no,app_id,order_no,logistics_id,logistics_no,customer,customer_mobile,customer_address,status,type,status_time,create_time,receive_time,check_time,check_result,check_description,business_key) VALUES ";
    $receive_goods_insert_sql = "INSERT INTO zuji_receive_goods (receive_no,refund_no,serial_no,goods_no,goods_name,quantity,quantity_received,status,status_time,check_time,check_result,check_description,check_price) VALUES ";
    $receive_goods_imei_insert_sql = "INSERT INTO zuji_receive_goods_imei (receive_no,serial_no,goods_no,imei,status,create_time,cancel_time,cancel_remark,type,serial_number) VALUES ";
    //查询db1订单商品表
    foreach ($order2_all1 as $key=>$item) {
        $receive_all1=[];
        $receive_result=$db1->query("SELECT * FROM zuji_order2_receive WHERE order_id=".$item['order_id']." AND business_key IN(1,5,6,9) ORDER BY receive_id DESC");
        while($arr = $receive_result->fetch_assoc()){
            //订单二维数组 order_id,order_no
            $receive_all1[]=$arr;
        }
        $sel++;
        if( !$receive_all1 ){
            continue;
        }
        $address_row=$db1->query("SELECT * FROM zuji_order2_address WHERE order_id=".$item['order_id']." ORDER BY address_id DESC LIMIT 1")->fetch_assoc();
        $sel++;
        $goods_row=$db1->query("SELECT * FROM zuji_order2_goods WHERE order_id=".$item['order_id']." ORDER BY goods_id DESC LIMIT 1")->fetch_assoc();
        $sel++;
        //$sku_row=$db1->query("SELECT `sn` FROM zuji_goods_sku WHERE sku_id=".$goods_row['sku_id'])->fetch_assoc();
        //$sel++;
        foreach ($receive_all1 as $k=>$receive_row){
            $receive_no = $receive_row['receive_id'];
            $address_info = '';//地址详情
            $evaluation_row=$db1->query("SELECT * FROM zuji_order2_evaluation WHERE order_id=".$item['order_id']." AND business_key IN(1,5,6,9) ORDER BY evaluation_id DESC LIMIT 1")->fetch_assoc();
            $sel++;
            //省
            $address_info .= $address_row['province_id']?$district_all1[$address_row['province_id']]['name'].' ':'';
            //市
            $address_info .= $address_row['city_id']?$district_all1[$address_row['city_id']]['name'].' ':'';
            //区县
            $address_info .= $address_row['country_id']?$district_all1[$address_row['country_id']]['name'].' ':'';
            $address_info .= $address_row['address']?replaceSpecialChar($address_row['address']):'';

            if ($evaluation_row){
                //存在检测单
                $status_arr = getReceiveStatusE($evaluation_row['evaluation_status']);
                $goodsStatus = getGoodsReceiveStatusE($evaluation_row['evaluation_status']);
                $checkResult = getCheckResult($evaluation_row['evaluation_status']);
                $receive_insert_sql .= "('".$receive_no."','".$item['appid']."','".$item['order_no']."','".$receive_row['wuliu_channel_id']."','".$receive_row['wuliu_no']."','".($address_row['name']?replaceSpecialChar($address_row['name']):'')."','".$address_row['mobile']."','".$address_info."','".$status_arr[0]."','".replaceBusinessKeyType($receive_row['business_key'])."','".$receive_row['update_time']."','".$receive_row['create_time']."','".$receive_row['receive_time']."','".$evaluation_row['evaluation_time']."','".$status_arr[1]."','".$evaluation_row['evaluation_remark']."','".replaceBusinessKey($receive_row['business_key'])."'),";
                $num++;
                $receive_goods_insert_sql .= "('".$receive_no."','".$receive_row['wuliu_no']."','1','".$goods_row['goods_id']."','".$goods_row['sku_name']."','1','1','".$goodsStatus."','".$receive_row['update_time']."','".$evaluation_row['evaluation_time']."','".$checkResult."','".$evaluation_row['evaluation_remark']."','0'),";
                $num++;
                //imei
                if ($goods_row['serial_number']){
                    //苹果
                    $receive_goods_imei_insert_sql .= "('".$receive_no."','1','".$goods_row['goods_id']."','".replaceSpecialChar($goods_row['imei1'])."','".$status_arr[2]."','".$goods_row['create_time']."','0','0','1','".$goods_row['serial_number']."'),";
                    $num++;
                }else{
                    //安卓
                    $receive_goods_imei_insert_sql .= "('".$receive_no."','1','".$goods_row['goods_id']."','".replaceSpecialChar($goods_row['imei1'])."','".$status_arr[2]."','".$goods_row['create_time']."','0','0','2','".$goods_row['serial_number']."'),";
                    $num++;
                    if($goods_row['imei2']){
                        $receive_goods_imei_insert_sql .= "('".$receive_no."','2','".$goods_row['goods_id']."','".replaceSpecialChar($goods_row['imei2'])."','".$status_arr[2]."','".$goods_row['create_time']."','0','0','2','".$goods_row['serial_number']."'),";
                        $num++;
                    }
                    if($goods_row['imei3']){
                        $receive_goods_imei_insert_sql .= "('".$receive_no."','3','".$goods_row['goods_id']."','".replaceSpecialChar($goods_row['imei3'])."','".$status_arr[2]."','".$goods_row['create_time']."','0','0','2','".$goods_row['serial_number']."'),";
                        $num++;
                    }
                }
            }else{
                //不存在检测单
                $status = getReceiveStatus($receive_row['receive_status']);
                $goodsStatus = getGoodsReceiveStatus($receive_row['receive_status']);
                $receive_insert_sql .= "('".$receive_no."','".$item['appid']."','".$item['order_no']."','".$receive_row['wuliu_channel_id']."','".$receive_row['wuliu_no']."','".($address_row['name']?replaceSpecialChar($address_row['name']):'')."','".$address_row['mobile']."','".$address_info."','".$status."','".replaceBusinessKeyType($receive_row['business_key'])."','".$receive_row['update_time']."','".$receive_row['create_time']."','".$receive_row['receive_time']."','0','0','0','".replaceBusinessKey($receive_row['business_key'])."'),";
                $num++;
                $receive_goods_insert_sql .= "('".$receive_no."','".$receive_row['wuliu_no']."','1','".$goods_row['goods_id']."','".$goods_row['sku_name']."','1','1','".$goodsStatus."','".$receive_row['update_time']."','0','0','0','0'),";
                $num++;
                //imei
                if ($goods_row['serial_number']){
                    //苹果
                    $receive_goods_imei_insert_sql .= "('".$receive_no."','1','".$goods_row['goods_id']."','".replaceSpecialChar($goods_row['imei1'])."','".$status."','".$goods_row['create_time']."','0','0','1','".$goods_row['serial_number']."'),";
                    $num++;
                }else{
                    //安卓
                    $receive_goods_imei_insert_sql .= "('".$receive_no."','1','".$goods_row['goods_id']."','".replaceSpecialChar($goods_row['imei1'])."','".$status."','".$goods_row['create_time']."','0','0','2','".$goods_row['serial_number']."'),";
                    $num++;
                    if($goods_row['imei2']){
                        $receive_goods_imei_insert_sql .= "('".$receive_no."','2','".$goods_row['goods_id']."','".replaceSpecialChar($goods_row['imei2'])."','".$status."','".$goods_row['create_time']."','0','0','2','".$goods_row['serial_number']."'),";
                        $num++;
                    }
                    if($goods_row['imei3']){
                        $receive_goods_imei_insert_sql .= "('".$receive_no."','3','".$goods_row['goods_id']."','".replaceSpecialChar($goods_row['imei3'])."','".$status."','".$goods_row['create_time']."','0','0','2','".$goods_row['serial_number']."'),";
                        $num++;
                    }
                }

            }
        }

    }
    $receive_insert_sql = substr($receive_insert_sql,0,-1);
    $receive_goods_insert_sql = substr($receive_goods_insert_sql,0,-1);
    $receive_goods_imei_insert_sql = substr($receive_goods_imei_insert_sql,0,-1);

    if ($receive_insert_sql) {
        if( !$db3->query($receive_insert_sql) ){
            echo '导入收货检测单失败;';
            echo $receive_insert_sql;
            return false;
        }
    }
    if ($receive_goods_insert_sql) {
        if( !$db3->query($receive_goods_insert_sql) ){
            echo '导入收货检测商品清单失败;';
            echo $receive_goods_insert_sql;
            return false;
        }
    }
    if ($receive_goods_imei_insert_sql) {
        if( !$db3->query($receive_goods_imei_insert_sql) ){
            echo '导入收货检测设备IMEI号表失败;';
            echo $receive_goods_imei_insert_sql;
            return false;
        }
    }

    echo '导入收货单,收货检测商品清单,收货检测设备IMEI号表成功;';
    return true;

}

//生成单号(发货单/收货单)
function generateNo(){
    return date('YmdHis') . rand(1000, 9999);
}

//发货状态转换
function getStatus($n){
    $arr = [
        0=>0,
        1=>1,
        2=>2,
        3=>2,
        4=>3,
        5=>4,
        6=>6,
        7=>5
    ];
    return $arr[$n];
}

//配货状态转换
function getDeliveryGoodsStatus($n){
    if($n>4){
        return [1,2];
    }else{
        return [0,0];
    }
}

//收货状态转换(不包括检测)
function getReceiveStatus($n){
    $arr = [
        0=>0,
        1=>1,
        2=>1,
        3=>2,
        4=>2,
        5=>0
    ];
    return $arr[$n];
}

//收货商品状态转换(不包括检测)
function getGoodsReceiveStatus($n){
    $arr = [
        0=>0,
        1=>1,
        2=>1,
        3=>3,
        4=>3,
        5=>0
    ];
    return $arr[$n];
}

//收货状态转换(包括检测)
function getReceiveStatusE($n){
    if ($n>3){
        return [3,1,4];
    }else{
        return [2,0,3];
    }
}

//收货商品状态转换(包括检测)
function getGoodsReceiveStatusE($n){
    if ($n>3){
        return 5;
    }else{
        return 3;
    }
}

//收货商品检测是否合格 转换
function getCheckResult($n){
    if($n==5){
        return 1;
    }elseif ($n==6){
        return 2;
    }else{
        return 0;
    }
}

//过滤特殊字符
function replaceSpecialChar($strParam){
    $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
    return preg_replace($regex,"",$strParam);
}

//过滤老库中的 business_key 转换为新订单key
function replaceBusinessKey($n){
    $arr = [
        1=>2,
        5=>4,
        6=>3,
        9=>3,
    ];
    return ($arr[$n]?$arr[$n]:1);
}
//过滤老库中的 business_key 转换为收发货类型
function replaceBusinessKeyType($n){
    $arr = [
        1=>2,
        5=>1,
        6=>3,
        9=>3,
    ];
    return ($arr[$n]?$arr[$n]:1);
}
