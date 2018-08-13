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

$stime=microtime(true);
$num = 0;//统计插入条数
$sel = 0;//统计查询条数

//增量索引值
$t = time();//初始化时间 或 增量时间
$_t = 0;//上次执行时间
$user_time=1532602800;//时间戳 从(2018-07-26 19:00:00)开始倒数据 用户表member
$spu_id=608;//商品从 608 开始导数据 spu

// ...
echo '开始时间:'.date('Y-m-d H:i:s',$t).';<br>';
//数据库配置
$user = 'nqyong_release';//用户名
$password = 'FM2rVp9x978Xxj6p';//密码
$dbname1 = 'zuji2';//老数据库库名(小程序)
$dbname2 = 'zuji';//租机库名 sku_id=3119 spu_id=637 user_id=82081
$host = 'rm-wz9vn1v4z6e94x0j9.mysql.rds.aliyuncs.com';//host
$port = 3306;//端口

//数据库1 (老)
$db1=new mysqli($host,$user,$password,$dbname1,$port);
if(mysqli_connect_error()){
    echo 'Could not connect to database 1.';
    exit;
}
mysqli_query($db1,'set names utf8');

//数据库2 (新订单)
$db2=new mysqli($host,$user,$password,$dbname2,$port);
if(mysqli_connect_error()){
    echo 'Could not connect to database 2.';
    exit;
}
mysqli_query($db2,'set names utf8');

//关闭自动提交
$db2->autocommit(false);

//------------------导入member用户信息------------------
if( !miniMember($db1,$db2,$user_time) ){
    $db2->rollback();//回滚
    //关闭链接
    $db1->close();
    $db2->close();
    die;

}

//------------------导入spu,sku商品信息表------------------
if( !miniGoods($db1,$db2,$spu_id) ){
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
 * 导入member用户信息
 */
function miniMember($db1,$db2,$user_time){
    global $num,$sel;
    echo '导入用户信息开始 '.date('Y-m-d H:i:s',time()).'<br>';
    //DB1 数据查询 zuji_member
    $result_member_all1=$db1->query("SELECT * FROM `zuji_member` WHERE register_time>".$user_time);//register_time>".$user_time." AND
    while($arr = $result_member_all1->fetch_assoc()){
        $row = $db2->query("SELECT * FROM zuji_member WHERE username='".$arr['username']."' OR mobile='".$arr['mobile']."'")->fetch_assoc();
        if(!$row){
            $user_all[]=$arr;
        }
    }
    $sel++;
    //导入新订单表sql 36
    $member_insert_sql = "INSERT INTO zuji_member (username,password,encrypt,mobile,certified,certified_platform,credit,face,risk,cert_no,realname,credit_time,islock,block,login_ip,login_num,login_time,group_id,email,integral,money,register_time,register_ip,frozen_money,exp,emailstatus,mobilestatus,order_remark,withholding_no,appid,weixin,work_unit,position,company_address,marriage,student_number) VALUES ";

    foreach ($user_all as $key=>$item) {
        $member_insert_sql .= "(";
        $member_insert_sql .= "'".$item['username']."',";
        $member_insert_sql .= "'".$item['password']."',";
        $member_insert_sql .= "'".$item['encrypt']."',";
        $member_insert_sql .= "'".$item['mobile']."',";
        $member_insert_sql .= "'".$item['certified']."',";
        $member_insert_sql .= "'".$item['certified_platform']."',";
        $member_insert_sql .= "'".$item['credit']."',";
        $member_insert_sql .= "'".$item['face']."',";
        $member_insert_sql .= "'".$item['risk']."',";
        $member_insert_sql .= "'".$item['cert_no']."',";
        $member_insert_sql .= "'".$item['realname']."',";
        $member_insert_sql .= "'".$item['credit_time']."',";
        $member_insert_sql .= "'".$item['islock']."',";
        $member_insert_sql .= "'".$item['block']."',";
        $member_insert_sql .= "'".$item['login_ip']."',";
        $member_insert_sql .= "'".$item['login_num']."',";
        $member_insert_sql .= "'".$item['login_time']."',";
        $member_insert_sql .= "'".$item['group_id']."',";
        $member_insert_sql .= "'".$item['email']."',";
        $member_insert_sql .= "'".$item['integral']."',";
        $member_insert_sql .= "'".$item['money']."',";
        $member_insert_sql .= "'".$item['register_time']."',";
        $member_insert_sql .= "'".$item['register_ip']."',";
        $member_insert_sql .= "'".$item['frozen_money']."',";
        $member_insert_sql .= "'".$item['exp']."',";
        $member_insert_sql .= "'".$item['emailstatus']."',";
        $member_insert_sql .= "'".$item['mobilestatus']."',";
        $member_insert_sql .= "'".$item['order_remark']."',";
        $member_insert_sql .= "'".$item['withholding_no']."',";
        $member_insert_sql .= "'".$item['appid']."',";
        $member_insert_sql .= "'".$item['weixin']."',";
        $member_insert_sql .= "'".$item['work_unit']."',";
        $member_insert_sql .= "'".$item['position']."',";
        $member_insert_sql .= "'".$item['company_address']."',";
        $member_insert_sql .= "'".$item['marriage']."',";
        $member_insert_sql .= "'".$item['student_number']."'";
        $member_insert_sql .= "),";
        $num++;

    }
    $member_insert_sql = substr($member_insert_sql,0,-1);
//    echo $goods_delivery_insert_sql;
    if($db2->query($member_insert_sql)){
        echo '导入用户信息信息表成功;<br>';
        return true;
    }else{
        echo '导入用户信息表失败;<br>';
        echo $member_insert_sql;
        return false;
    }

}

/**
 * 导入spu,sku商品信息表
 */
function miniGoods($db1,$db2,$spu_id){
    global $num,$sel;
    echo '导入spu,sku商品信息开始 '.date('Y-m-d H:i:s',time()).'<br>';

    //DB1 数据查询 zuji_goods_spu
    $spu_all1=$db1->query("SELECT * FROM `zuji_goods_spu` WHERE id>=".$spu_id);
    while($arr = $spu_all1->fetch_assoc()){
        //zuji_goods_spu二维数组
        $spu_all[]=$arr;
    }
    $sel++;

    //导入 sku sql 35
    $sku_insert_sql = "INSERT INTO zuji_goods_sku (spu_id,sku_name,subtitle,style,sn,barcode,spec,imgs,thumb,status,status_ext,number,pre_number,market_price,shop_price,yajin,zuqi,zuqi_type,buyout_price,chengse,sort,keyword,description,content,show_in_lists,warn_number,prom_type,prom_id,up_time,update_time,edition,weight,volume,sku_ids,spu_ids) VALUES ";
    //查询db1订单商品表 38
    foreach ($spu_all as $key=>$item) {
        //导入spu sql
        $spu_insert_sql = "INSERT INTO zuji_goods_spu (name,sn,subtitle,style,catid,brand_id,keyword,description,imgs,thumb,min_price,max_price,yiwaixian_cost,yiwaixian,max_month,min_month,min_zuqi_type,max_zuqi_type,status,specs,sku_total,sort,content,give_point,warn_number,spec_id,type_id,weight,volume,delivery_template_id,start_rents,start_month,channel_id,peijian,machine_id,payment_rule_id,contract_id,spu_ids) VALUES ";
        $spu_insert_sql .= "(";
        $spu_insert_sql .= "'".$item['name']."',";
        $spu_insert_sql .= "'".$item['sn']."-xcx',";
        $spu_insert_sql .= "'".$item['subtitle']."',";
        $spu_insert_sql .= "'".$item['style']."',";
        $spu_insert_sql .= "'".$item['catid']."',";
        $spu_insert_sql .= "'".$item['brand_id']."',";
        $spu_insert_sql .= "'".$item['keyword']."',";
        $spu_insert_sql .= "'".$item['description']."',";
        $spu_insert_sql .= "'".$item['imgs']."',";
        $spu_insert_sql .= "'".$item['thumb']."',";
        $spu_insert_sql .= "'".$item['min_price']."',";
        $spu_insert_sql .= "'".$item['max_price']."',";
        $spu_insert_sql .= "'".$item['yiwaixian_cost']."',";
        $spu_insert_sql .= "'".$item['yiwaixian']."',";
        $spu_insert_sql .= "'".$item['max_month']."',";
        $spu_insert_sql .= "'".$item['min_month']."',";
        $spu_insert_sql .= "'".$item['min_zuqi_type']."',";
        $spu_insert_sql .= "'".$item['max_zuqi_type']."',";
        $spu_insert_sql .= "'".$item['status']."',";
        $spu_insert_sql .= "'".$item['specs']."',";
        $spu_insert_sql .= "'".$item['sku_total']."',";
        $spu_insert_sql .= "'".$item['sort']."',";
        $spu_insert_sql .= "'".$item['content']."',";
        $spu_insert_sql .= "'".$item['give_point']."',";
        $spu_insert_sql .= "'".$item['warn_number']."',";
        $spu_insert_sql .= "'".$item['spec_id']."',";
        $spu_insert_sql .= "'".$item['type_id']."',";
        $spu_insert_sql .= "'".$item['weight']."',";
        $spu_insert_sql .= "'".$item['volume']."',";
        $spu_insert_sql .= "'".$item['delivery_template_id']."',";
        $spu_insert_sql .= "'".$item['start_rents']."',";
        $spu_insert_sql .= "'".$item['start_month']."',";
        $spu_insert_sql .= "'".$item['channel_id']."',";
        $spu_insert_sql .= "'".$item['peijian']."',";
        $spu_insert_sql .= "'".$item['machine_id']."',";
        $spu_insert_sql .= "'".$item['payment_rule_id']."',";
        $spu_insert_sql .= "'".$item['contract_id']."',";
        $spu_insert_sql .= "'".$item['id']."'";
        $spu_insert_sql .= ")";
        $num++;
        if($db2->query($spu_insert_sql)){
            $spu_id = mysqli_insert_id($db2);
            //DB1 数据查询 zuji_goods_sku
            $sku_all1=$db1->query("SELECT * FROM `zuji_goods_sku` WHERE spu_id=".$item['id']);
            while($arr2 = $sku_all1->fetch_assoc()){
                //订单二维数组 order_id,order_no
                $sku_all[]=$arr2;
                $sku_insert_sql .= "(";
                $sku_insert_sql .= "'".$spu_id."',";
                $sku_insert_sql .= "'".$arr2['sku_name']."',";
                $sku_insert_sql .= "'".$arr2['subtitle']."',";
                $sku_insert_sql .= "'".$arr2['style']."',";
                $sku_insert_sql .= "'".$arr2['sn']."-xcx',";
                $sku_insert_sql .= "'".$arr2['barcode']."',";
                $sku_insert_sql .= "'".$arr2['spec']."',";
                $sku_insert_sql .= "'".$arr2['imgs']."',";
                $sku_insert_sql .= "'".$arr2['thumb']."',";
                $sku_insert_sql .= "'".$arr2['status']."',";
                $sku_insert_sql .= "'".$arr2['status_ext']."',";
                $sku_insert_sql .= "'".$arr2['number']."',";
                $sku_insert_sql .= "'".$arr2['pre_number']."',";
                $sku_insert_sql .= "'".$arr2['market_price']."',";
                $sku_insert_sql .= "'".$arr2['shop_price']."',";
                $sku_insert_sql .= "'".$arr2['yajin']."',";
                $sku_insert_sql .= "'".$arr2['zuqi']."',";
                $sku_insert_sql .= "'".$arr2['zuqi_type']."',";
                $sku_insert_sql .= "'".$arr2['buyout_price']."',";
                $sku_insert_sql .= "'".$arr2['chengse']."',";
                $sku_insert_sql .= "'".$arr2['sort']."',";
                $sku_insert_sql .= "'".$arr2['keyword']."',";
                $sku_insert_sql .= "'".$arr2['description']."',";
                $sku_insert_sql .= "'".$arr2['content']."',";
                $sku_insert_sql .= "'".$arr2['show_in_lists']."',";
                $sku_insert_sql .= "'".$arr2['warn_number']."',";
                $sku_insert_sql .= "'".$arr2['prom_type']."',";
                $sku_insert_sql .= "'".$arr2['prom_id']."',";
                $sku_insert_sql .= "'".$arr2['up_time']."',";
                $sku_insert_sql .= "'".$arr2['update_time']."',";
                $sku_insert_sql .= "'".$arr2['edition']."',";
                $sku_insert_sql .= "'".$arr2['weight']."',";
                $sku_insert_sql .= "'".$arr2['volume']."',";
                $sku_insert_sql .= "'".$arr2['sku_id']."',";
                $sku_insert_sql .= "'".$arr2['spu_id']."'";
                $sku_insert_sql .= "),";
                $num++;
            }
            $sel++;
        }else{
            echo '导入spu:'.$item['id'].'信息表失败;<br>';
            echo $spu_insert_sql;
            return false;
        }
    }
    $sku_insert_sql = substr($sku_insert_sql,0,-1);
//    echo $goods_delivery_insert_sql;
    if($db2->query($sku_insert_sql)){
        echo '导入sku商品信息表成功;<br>';
        return true;
    }else{
        echo '导入sku商品信息表失败;<br>';
        echo $sku_insert_sql;
        return false;
    }

}

