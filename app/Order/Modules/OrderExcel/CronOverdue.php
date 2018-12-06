<?php
/**
 *    逾期坏账相关数据excel导出
 *    author: limin
 *    date : 2018-12-06
 */
namespace App\Order\Modules\OrderExcel;

use App\Lib\Channel\Channel;
use App\Lib\Excel;
use App\Order\Models\Order;
use App\Order\Models\OrderMini;
use App\Order\Modules\Inc;




class CronOverdue
{
    private static $channel = [
        "10"=>"	支付宝小程序",
        "14"=>"极米小程序渠道",
        "15"=>"大疆小程序专用渠道",
        "16"=>"	努比亚小程序",
    ];

    /**
     *  每月1号定时导出上个整月订单风控数据
     * @return excel文件
     */
    public static function detail()
    {
        //error_reporting(E_ALL ^ E_NOTICE);
        $where = [
            ['id','<>',0]
        ];
        if($_GET['channel_id']){
            $where[] = ['channel_id','=',$_GET['channel_id']];
        }
        if(isset($_GET['begin']) && isset($_GET['end'])){
            $beginDay = $_GET['begin'];
            $endDay = $_GET['end'];
            $where[] = ['create_time', '>=', strtotime($beginDay." 00:00:00"),];
            $where[] = ['create_time', '<=', strtotime($endDay." 23:59:59"),];
        }
        else{
            //检测以前是否执行过，是用上次执行时间计算，否初始化全部
            if(true){
                $someday = strtotime(date("2018-11-30 -30 day"));
                $where[] = ['order_goods.end_time', '>=', $someday,];
            }
            $overdueTime = strtotime(date("Y-m-d")." -30 day");
            $where[] = ['order_goods.end_time', '<=', $overdueTime];
        }

        //订单状态
        $where[] = ['order_info.order_status','=',Inc\OrderStatus::OrderInService,];

        //渠道条件设置所有小程序
        $channelId = "10,14,15,16";

        $limit = 500;
        var_dump($where);
        echo sql_profiler();
        $count = Order::query()->leftJoin('order_goods','order_info.order_no', '=', 'order_goods.order_no')
            ->where($where)
            ->wherein("order_info.channel_id",$channelId)->count();
        echo "wwwwwwwwwwwwwwwwwwwww";

        die;
        $data = [];
        $single = 0;
        //分批获取订单信息
        for($i=0;$i<ceil($count/$limit);$i++){
            $offset = $i*$limit;
            $orderList = Order::query()->leftJoin('order_goods','order_info.order_no', '=', 'order_goods.order_no')
                ->where($where)->wherein("order_info.channel_id",$channelId)
                ->offset($offset)->limit($limit)->get()->toArray();
            $single += count($orderList);
            //拆分出订单号
            $orderNos = array_column($orderList,"order_no");

            //获取支付用户信息
            $miniUser = OrderMini::query()->wherein("order_no",$orderNos)->get()->toArray();
            $miniUser = array_column($miniUser,null,"order_no");

            foreach($orderList as $item){
                $channelName = self::$channel[$item['channel_id']];
                $miniUserId = isset($miniUser[$item['order_no']]['user_id'])?$miniUser[$item['order_no']]['user_id']:"";
                $mianyajin =  $item['goods_yajin']-$item['order_yajin'];
                $beginTime = date("Y-m-d H:i:s",$item['begin_time']);
                $endTime = date("Y-m-d H:i:s",$item['end_time']);
                $unit = Inc\OrderStatus::getZuqiTypeName($item['zuqi_type']);
                $zuqi = $item['zuqi'].$unit;
                $overdueDay = ceil((time()-$item['end_time'])/86400);
                $data[] = [
                    $channelName,
                    $miniUserId,
                    $mianyajin,
                    $beginTime,
                    $endTime,
                    $zuqi,
                    "法大大",
                    "FaceId",
                    $overdueDay,
                ];
            }
        }
        //定义excel头部参数名称
        $headers = [
            "订单渠道",
            '用户支付宝账号',
            '商品名称',
            '免押金额',
            '起租日',
            '约定归还日',
            '租期',
            '电子合约厂商',
            '人脸认证厂商',
            '逾期未归还天数',
        ];

        Excel::localWrite($data,$headers,date("Y-m-d"),"overdue");
        echo "订单总数:".$single."<br/><br/>";
        return;
    }

    /**
     *  导出所有
     * @return excel文件
     */
    public static function everAll()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        $where = [
            ['id','<>',0]
        ];
        if($_GET['channel_id']){
            $where[] = ['channel_id','=',$_GET['channel_id']];
        }
        if(isset($_GET['begin']) && isset($_GET['end'])){
            $beginDay = $_GET['begin'];
            $endDay = $_GET['end'];
            $where[] = ['create_time', '>=', strtotime($beginDay." 00:00:00"),];
            $where[] = ['create_time', '<=', strtotime($endDay." 23:59:59"),];
        }
        //cul获取渠道应用信息
        $channelList = Channel::getChannelAppidListName();


        $limit = 500;
        $count = Order::query()->where($where)->count();
        if($count>10000){
            echo "超出导出限制";die;
        }
        $data = [];
        $single = 0;
        //分批获取订单信息
        for($i=0;$i<ceil($count/$limit);$i++){
            $offset = $i*$limit;
            $orderList = Order::query()->where($where)->offset($offset)->limit($limit)->get()->toArray();
            $single += count($orderList);
            //拆分出订单号
            $orderNos = array_column($orderList,"order_no");

            //获取分期信息
            $instalmentList = OrderGoodsInstalment::query()->wherein("order_no",$orderNos)->get()->toArray();
            $newInstalment = [];
            foreach($orderNos as $number){
                foreach($instalmentList as $value){
                    if($number == $value['order_no'] ){
                        $newInstalment[$number][] = $value;
                    }
                }
            }
            //获取风控信息
            $riskList = OrderRisk::query()->wherein("order_no",$orderNos)->get()->toArray();
            $newRiskList = [];
            foreach($orderNos as $number){
                foreach($riskList as $value){
                    if($number == $value['order_no'] ){
                        $newRiskList[$number][] = $value;
                    }
                }
            }

            //获取订单商品信息
            $goodsList= OrderGoodsRepository::getOrderGoodsColumn($orderNos);
            //获取订单用户信息
            $userList = OrderUserCertifiedRepository::getUserColumn($orderNos);
            //获取订单地址信息
            $userAddressList = OrderUserAddressRepository::getUserAddressColumn($orderNos);
            //获取订单信用分信息
            $riskSoceList = OrderRiskRepository::getRiskColumn($orderNos);

            $orderError = "";
            $userError = "";
            $goodsError = "";
            $addressError = "";
            foreach($orderList as $item){

                //用户相关信息
                if(empty($userList[$item['order_no']])){
                    $userError .= $item['order_no'].",";
                    $item['realname'] = "";
                    $item['cret_no'] = "";
                    $item['sex'] = "";
                }else{
                    $user = $userList[$item['order_no']];
                    $item['realname'] = $user['realname'];
                    $item['cret_no'] = $user['cret_no'];
                    $item['sex'] = (int)substr($item['cret_no'],16,1)% 2 === 0 ? '女' : '男';
                    $item['credit'] =  $user['credit'];
                }
                if($riskSoceList[$item['order_no']]){
                    $item['credit'] =  $riskSoceList[$item['order_no']]['score'];
                }

                //商品相关信息
                if(empty($goodsList[$item['order_no']])){
                    $goodsError .= $item['order_no'].",";
                    $item['zuqi'] = "";
                    $item['mianyajin'] = "";
                    $item['zuqi_type']= "";
                    $item['goods_name'] ="";
                    $item['zujin'] = "";
                    $item['specs'] = "";
                    $item['market_price'] = "";
                    $item['begin_time'] = "";
                    $item['end_time'] = "";
                }else{
                    $item['zuqi'] = $goodsList[$item['order_no']]['zuqi'].Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
                    $item['mianyajin'] = $goodsList[$item['order_no']]['goods_yajin']-$goodsList[$item['order_no']]['yajin'];
                    $item['zuqi_type']= Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
                    $item['goods_name'] = $goodsList[$item['order_no']]['goods_name'];
                    $item['zujin'] = $goodsList[$item['order_no']]['zujin'];
                    $item['specs'] = $goodsList[$item['order_no']]['specs'];
                    $item['market_price'] = $goodsList[$item['order_no']]['market_price'];
                    $item['begin_time'] = date("Y-m-d H:i:s",$goodsList[$item['order_no']]['begin_time']);
                    $item['end_time'] = date("Y-m-d H:i:s",$goodsList[$item['order_no']]['end_time']);
                }
                //订单收货地址
                if(isset($userAddressList[$item['order_no']])){
                    $item['user_address'] = $userAddressList[$item['order_no']]['address_info'];
                }else{
                    $addressError .= $item['order_no'].",";
                    $item['user_address'] = "";
                }

                /************************风控处理*********************/
                $item['yidun_decision_name'] = "";
                $item['yidun_hit_rules'] = "";
                $item['tongdun_decision_name'] = "";
                $item['tongdun_hit_rules'] = "";
                $item['knight_decision_name'] = "";
                $item['knight_hit_rules'] = "";
                if($newRiskList[$item['order_no']]){
                    $risk = $newRiskList[$item['order_no']];
                    foreach($risk as $stem){
                        if($stem['type'] == "yidun"){
                            $num = empty($stem['data'])?"":json_decode($stem['data'],true);
                            if($num){
                                $item['yidun_decision_name'] = $num['decision_name'];
                                $item['yidun_hit_rules'] = json_encode($num['hit_rules']);
                            }
                        }
                        elseif($stem['type'] == "mno"){
                            $num = empty($stem['data'])?"":json_decode($stem['data'],true);
                            if($num){
                                $item['tongdun_decision_name'] = $num['decision_name'];
                                $item['tongdun_hit_rules'] = json_encode($num['hit_rules']);
                            }
                        }
                        elseif($stem['type'] == "yidun"){
                            $num = empty($stem['data'])?"":json_decode($stem['data'],true);
                            if($num){
                                $item['knight_decision_name'] = $num['decision_name'];
                                $item['knight_hit_rules'] = json_encode($num['hit_rules']);
                            }
                        }
                    }
                }
                /************************分期处理*********************/
                //初始化分期
                for($init=1;$init<=12;$init++){
                    $item['term_'.$init] = "";
                }
                $item['is_pay'] = "";
                if($newInstalment[$item['order_no']]){
                    $instalment = $newInstalment[$item['order_no']];
                    foreach($instalment as $after){
                        if($after['status'] == Inc\OrderInstalmentStatus::SUCCESS){
                            $item['term_'.$after['times']] = $after['amount'];
                        }
                        elseif($after['status'] == Inc\OrderInstalmentStatus::FAIL){
                            $item['term_'.$after['times']] = "扣款失败";
                        }
                        if(date("Ym",strtotime("last month")) == $after['term'] && $after['withhold_day']<=time()){
                            $item['is_pay'] = $after['status']==Inc\OrderInstalmentStatus::SUCCESS?"是":"否";
                        }
                    }
                }
                if($item['pay_type'] == Inc\PayInc::FlowerStagePay){
                    $item['is_pay'] = "";
                }
                //订单相关信息
                $item['order_status'] = Inc\OrderStatus::getStatusName($item['order_status']);
                $item['create_time'] = date("Y-m-d H:i:s", $item['create_time']);
                $item['pay_type'] = Inc\PayInc::getPayName($item['pay_type']);
                $item['app_name'] = $channelList[$item['appid']];
                $item['payment_time'] = $item['payment_time']>0?date("Y-m-d H:i:s",$item['payment_time']):"";

                $data[] = [
                    $item['order_no']." ",
                    $item['realname'],
                    $item['sex'],
                    $item['cret_no']." ",
                    $item['mobile'],
                    $item['yidun_decision_name'],
                    $item['yidun_hit_rules'],
                    $item['tongdun_decision_name'],
                    $item['tongdun_hit_rules'],
                    $item['knight_decision_name'],
                    $item['knight_hit_rules'],

                    $item['create_time'],
                    $item['zuqi'],
                    $item['zujin'],
                    $item['credit'],
                    $item['goods_name'],
                    $item['order_amount'],
                    $item['market_price'],
                    $item['order_insurance'],
                    $item['order_yajin'],
                    $item['mianyajin'],
                    $item['app_name'],
                    $item['user_address'],
                    $item['pay_type'],
                    $item['is_pay'],
                    $item['order_status'],
                    $item['begin_time'],
                    $item['end_time'],
                    $item['term_1'],
                    $item['term_2'],
                    $item['term_3'],
                    $item['term_4'],
                    $item['term_5'],
                    $item['term_6'],
                    $item['term_7'],
                    $item['term_8'],
                    $item['term_9'],
                    $item['term_10'],
                    $item['term_11'],
                    $item['term_12'],
                ];
            }
        }
        //定义excel头部参数名称
        $headers = [
            "订单号",
            '用户姓名',
            '性别',
            '身份证号',
            '手机号',
            '蚁盾描述',
            '命中策略策略',
            '同盾描述',
            '命中策略',
            '白骑士描述',
            '命中策略',
            '下单时间',
            '租期',
            '月租金',
            '信用分',
            '选购产品',
            '订单金额',
            '市场价',
            '碎屏意外险',
            '实押金',
            '免押金',
            '渠道',
            '收货地址',
            '支付方式',
            '最近是否还款',
            '订单状态',
            '起租时间',
            '结束时间',
            '第1期',
            '第2期',
            '第3期',
            '第4期',
            '第5期',
            '第6期',
            '第7期',
            '第8期',
            '第9期',
            '第10期',
            '第11期',
            '第12期',
        ];


        Excel::localWrite($data,$headers,date("Y-m-d H.i.s"),"risk");
        echo "订单总数:".$single."<br/><br/>";
        echo "订单：".$orderError."<br/><br/>";
        echo "用户：".$userError."<br/><br/>";
        echo "商品：".$goodsError."<br/><br/>";
        echo "地址：".$addressError."<br/><br/>";
        return;
    }

}