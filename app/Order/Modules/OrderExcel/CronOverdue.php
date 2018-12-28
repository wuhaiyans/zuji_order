<?php
/**
 *    逾期坏账相关数据excel导出
 *    author: limin
 *    date : 2018-12-06
 */
namespace App\Order\Modules\OrderExcel;

use App\Lib\Channel\Channel;
use App\Lib\Common\LogApi;
use App\Lib\Excel;
use App\Order\Models\Order;
use App\Order\Models\OrderMini;
use App\Order\Modules\Inc;
use Illuminate\Support\Facades\Redis;

//每周五0点整执行

class CronOverdue
{
    private static $redisKey = "overdueDate";
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
        //截止时间
        $deadline = "";
        if(isset($_GET['begin']) && isset($_GET['end'])){
            $someday = strtotime($_GET['begin']." -30 day");
            $overdueTime = strtotime($_GET['end']." -30 day");
            $backBegin = strtotime($_GET['begin']." 00:00:00");
            $backEnd = strtotime($_GET['end']." 23:59:59");
            $deadline = $_GET['begin']."|".$_GET['end'];
        }
        else{
            $deadline = "2018-08-24|".date("Y-m-d",strtotime(date("Y-m-d")." -1 day"));
            //检测以前是否执行过，是用上次执行时间计算，否从第一个租用中订单开始
            $someday = strtotime(date("2018-08-24"));
            //还机开始时间
            $backBegin = $someday;
            $lastDay = Redis::get(self::$redisKey);
            if($lastDay){
                $deadline = $lastDay;
                //坏账开始时间
                $someday = strtotime($lastDay." -30 day");
                $backBegin = strtotime($lastDay);
                $deadline = $lastDay."|".date("Y-m-d",strtotime(date("Y-m-d")." -1 day"));
            }

            $overdueTime = strtotime(date("Y-m-d"." 23:59:59",strtotime(date("Y-m-d")." -31 day")));
            $backEnd = time();
        }
        //未还时间条件
        $whereBack[] = ['order_goods.end_time', '>=', $backBegin,];
        $whereBack[] = ['order_goods.end_time', '<=', $backEnd,];
        $whereBack[] = ['order_info.order_status','=',Inc\OrderStatus::OrderInService,];

        //坏账时间条件
        $where[] = ['order_goods.end_time', '>=', $someday,];
        $where[] = ['order_goods.end_time', '<=', $overdueTime];

        //订单状态
        $where[] = ['order_info.order_status','=',Inc\OrderStatus::OrderInService,];

        //渠道条件设置所有小程序
        $channelId = [10,14,15,16];

        //未还订单数
        $backCount = Order::query()->leftJoin('order_goods','order_info.order_no', '=', 'order_goods.order_no')
            ->where($whereBack)
            ->whereIn("order_info.channel_id",$channelId)->count();
        $backGoodsYajin = Order::query()->leftJoin('order_goods','order_info.order_no', '=', 'order_goods.order_no')
            ->where($whereBack)
            ->whereIn("order_info.channel_id",$channelId)->sum("order_info.goods_yajin");
        $backOrderYajin = Order::query()->leftJoin('order_goods','order_info.order_no', '=', 'order_goods.order_no')
            ->where($whereBack)
            ->whereIn("order_info.channel_id",$channelId)->sum("order_info.order_yajin");

        $backMianyajin = $backGoodsYajin-$backOrderYajin;
        //坏账订单数
        $count = Order::query()->leftJoin('order_goods','order_info.order_no', '=', 'order_goods.order_no')
            ->where($where)
            ->whereIn("order_info.channel_id",$channelId)->count();
        //记录导出sql记录
        $huaSql = sql_profiler();
        LogApi::debug("overdue-".date("Y-m-d"),$huaSql);
        $data = [];
        $single = 0;
        $mianyajinSum = 0;

        $num = [];
        if($count>0){
            $limit = $count>=500?500:$count;
            //分批获取订单信息
            for($i=0;$i<ceil($count/$limit);$i++){
                $offset = $i*$limit;
                $orderList = Order::query()->leftJoin('order_goods','order_info.order_no', '=', 'order_goods.order_no')
                    ->where($where)->whereIn("order_info.channel_id",$channelId)
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
                    $goodsName = $item['goods_name'];
                    $mianyajin =  $item['goods_yajin']-$item['order_yajin'];
                    $beginTime = date("Y-m-d H:i:s",$item['begin_time']);
                    $endTime = date("Y-m-d H:i:s",$item['end_time']);
                    $unit = Inc\OrderStatus::getZuqiTypeName($item['zuqi_type']);
                    $zuqi = $item['zuqi'].$unit;
                    $overdueDay = ceil((time()-$item['end_time'])/86400);
                    $data[] = [
                        $channelName,
                        $miniUserId,
                        $goodsName,
                        $mianyajin,
                        $beginTime,
                        $endTime,
                        $zuqi,
                        "法大大",
                        "FaceId",
                        $overdueDay,
                    ];

                    $mianyajinSum+=$mianyajin;
                }
            }
            $num[] = [
                $deadline,
                $backCount+$count,
                $mianyajinSum+$backMianyajin,
                $backCount+$count,
                $backCount,
                $count,
                $mianyajinSum
            ];
        }

        //定义工作表名称
        $title = [
            "累计报表",
            "坏账明细",
        ];
        //定义excel头部参数名称
        $headers[] = [
            "截止日期",
            '订单数',
            '免押金额',
            '用户数',
            '到期未还订单数',
            '坏账订单数',
            '坏账订单免押金额',
        ];
        $headers[] = [
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
        $body = [
            $num,
            $data
        ];
        Excel::xlsxExport($body,$headers,$title,date("Y-m-d H.i"),"overdue");
        Redis::set(self::$redisKey,date("Y-m-d"));
        echo "订单总数:".$single."<br/><br/>";
        return;
    }
}