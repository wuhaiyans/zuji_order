<?php
/**
 * 导出商品图片信息并修改
 *
 * User: wangjinlin
 * Date: 2018/6/11
 * Time: 下午5:05
 */

$stime=microtime(true);
$num = 0;//统计插入条数
$sel = 0;//统计查询条数

$t = time();//初始化时间
$_t = 0;//上次执行时间
$str_l = "https://s1.huishoubao.com/zuji/images/content/";//要查找的值

echo '开始时间:'.date('Y-m-d H:i:s',$t).';___';
//数据库配置
$user = 'root';//用户名
$password = '123456';//密码
$dbname1 = 'zuji';//老数据库库名
$host = '127.0.0.1';//host
$port = 3306;//端口

//数据库1 (老)
$db1=new mysqli($host,$user,$password,$dbname1,$port);
if(mysqli_connect_error()){
    echo 'Could not connect to database 1.';
    exit;
}
mysqli_query($db1,'set names utf8');

//关闭自动提交
$db1->autocommit(false);

//DB1 数据查询
// 查询商品
$result_spu_all1=$db1->query("SELECT id,imgs,thumb,content FROM zuji_goods_spu");

while($arr = $result_spu_all1->fetch_assoc()){
    //商品二维数组
    $spu_all[]=$arr;
}
$sel++;

//------------------导入订单发货信息------------------
if( !spuImgs($spu_all,$str_l) ){
    echo 'error';
    die;

}

$db1->close();

$tn = time();
echo '结束时间:'.date('Y-m-d H:i:s',$tn).';___';
$etime=microtime(true);
echo '用时:'.($tn-$t).'(秒);___';
echo '查询总条数:'.$sel.';___';
echo '插入总条数:'.$num.';___';

die;


//===方法体=====================================================================================================================

/**
 * 导入商品图片信息
 */
function spuImgs($spu_all,$str_l){
    global $num;
    echo '导入商品图片信息开始 '.date('Y-m-d H:i:s',time()).'___';

    $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");

    foreach ($spu_all as $key=>$item) {
        if($item['imgs']){
            $imgs_all = json_decode($item['imgs'],true);
            foreach ($imgs_all as $k=>$value){
                if(strstr($value,$str_l)){
                    //存在
                    fwrite($myfile, $value."\n");
                    $num++;
                }
            }
        }
//        if($item['thumb']){
//            if(strstr($item['thumb'],$str_l)){
//                //存在
//                fwrite($myfile, $item['thumb']."\n");
//            }
//        }
//        if($item['content']){
//            if(strstr($item['content'],$str_l)){
//                //存在
//                fwrite($myfile, $item['content']."\n");
//            }
//        }

    }
    //关闭
    fclose($myfile);
    return true;

}

