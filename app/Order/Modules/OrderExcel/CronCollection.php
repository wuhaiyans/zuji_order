<?php
/**
 *    催收相关数据excel导出
 *    author: limin
 *    date : 2018-08-21
 */
namespace App\Order\Modules\OrderExcel;

use App\Lib\Channel\Channel;
use App\Lib\Excel;
use App\Order\Models\Order;
use App\Order\Models\OrderGoodsInstalment;
use App\Order\Modules\Inc;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderUserAddressRepository;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;



class CronCollection
{
    /**
     *  导出1号-24号定时导出上个整月订单催收数据
     * @return excel文件
     */
    public static function otherMonth(){
        $_GET['other']=1;
        self::everMonth();
    }
    /**
     *  每月1号定时导出上个整月订单催收数据
     * @return excel文件
     */
    public static function everMonth()
    {
        error_reporting(E_ALL ^ E_NOTICE);

        //cul获取渠道应用信息
        $channelList = Channel::getChannelAppidListName();

        if($_GET['month']==4){
            $date = "2018-04";
            $beginTime = strtotime("2018-04-01 00:00:00");
            $endTime = strtotime("2018-04-30 23:59:59");
        }
        elseif($_GET['month']==5){
            $date = "2018-05";
            $beginTime = strtotime("2018-05-01 00:00:00");
            $endTime = strtotime("2018-05-31 23:59:59");
        }
        elseif($_GET['month']==6){
            $date = "2018-06";
            $beginTime = strtotime("2018-06-01 00:00:00");
            $endTime = strtotime("2018-06-30 23:59:59");
        }
        elseif($_GET['month']==7){
            $date = "2018-07";
            $beginTime = strtotime("2018-07-01 00:00:00");
            $endTime = strtotime("2018-07-31 23:59:59");
        }
        elseif($_GET['month']==8){
            $date = "2018-08";
            $beginTime = strtotime("2018-08-01 00:00:00");
            $endTime = strtotime("2018-08-31 23:59:59");
        }
        else{
            //获取上个月所有催收订单
            $date = date("Y-m",strtotime('-1 month'));
            $day = date("t",strtotime($date));
            $beginTime = strtotime($date."-01 00:00:00");
            $endTime = strtotime($date."-".$day." 23:59:59");
        }
        if($_GET['other']==1){
            $moth = "2018-".date("m",time());
            $date = $moth."-24";
            $beginTime = strtotime($moth."-01 00:00:00");
            $endTime = strtotime($moth."-24 23:59:59");
        }
        $where[] = ['withhold_day', '>=', $beginTime,];
        $where[] = ['withhold_day', '<=', $endTime,];
        $status = [
            Inc\OrderInstalmentStatus::UNPAID,
            Inc\OrderInstalmentStatus::FAIL
        ];

        $instalmentList = OrderGoodsInstalment::query()->where($where)->wherein("status",$status)->get()->toArray();

        if(!$instalmentList){
            return false;
        }

        $orderNos = array_column($instalmentList,"order_no");
        array_unique($orderNos);
        asort($orderNos);
        array_multisort($instalmentList,SORT_ASC,SORT_NUMERIC);
        //获取订单信息
        $orderList = Order::query()->wherein("order_no",$orderNos)->get()->toArray();
        $orderList = array_column($orderList,null,"order_no");
        //获取订单商品信息
        $goodsList= OrderGoodsRepository::getOrderGoodsColumn($orderNos);
        //获取订单用户信息
        $userList = OrderUserCertifiedRepository::getUserColumn($orderNos);
        //获取订单地址信息
        $userAddressList = OrderUserAddressRepository::getUserAddressColumn($orderNos);
        //定义excel头部参数名称
        $headers = [
            '用户姓名',
            '联系电话',
            '订单号',
            '第几期',
            '应付金额',
            '第三方交易号',
            '扣款成功时间',
            '性别',
            '身份证号',
            '下单时间',
            '租期',
            '月租金',
            '产品名称',
            '商品属性',
            '订单金额',
            '实押金',
            '免押金',
            '支付方式',
            '支付金额',
            '支付时间',
            '渠道',
            '收货地址',

        ];
        $data = [];
        $orderError = "";
        $userError = "";
        $goodsError = "";
        $addressError = "";
        foreach($instalmentList as $item){
            //订单相关信息
            if(empty($orderList[$item['order_no']])){
                continue;
            }

            $item['mobile'] = $orderList[$item['order_no']]['mobile'];
            $item['create_time'] = date("Y-m-d H:i:s", $orderList[$item['order_no']]['create_time']);
            $item['order_amount'] = $orderList[$item['order_no']]['order_amount'];
            $item['order_yajin'] = $orderList[$item['order_no']]['order_yajin'];
            $item['pay_type'] = Inc\PayInc::getPayName($orderList[$item['order_no']]['pay_type']);
            $item['pay_time'] = $orderList[$item['order_no']]['pay_time']>0?date("Y-m-d H:i:s",$orderList[$item['order_no']]['pay_time']):"";
            $item['app_name'] = $channelList[$orderList[$item['order_no']]['appid']];
            $item['create_time'] = date("Y-m-d H:i:s",$orderList[$item['order_no']]['create_time']);

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
            }else{
                $item['zuqi'] = $goodsList[$item['order_no']]['zuqi'].Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
                $item['mianyajin'] = $goodsList[$item['order_no']]['goods_yajin']-$goodsList[$item['order_no']]['yajin'];
                $item['zuqi_type']= Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
                $item['goods_name'] = $goodsList[$item['order_no']]['goods_name'];
                $item['zujin'] = $goodsList[$item['order_no']]['zujin'];
                $item['specs'] = $goodsList[$item['order_no']]['specs'];
            }
            //订单收货地址
            if(isset($userAddressList[$item['order_no']])){
                $item['user_address'] = $userAddressList[$item['order_no']]['address_info'];
            }else{
                $addressError .= $item['order_no'].",";
                $item['user_address'] = "";
            }
            $item['payment_time'] = $item['payment_time']>0?date("Y-m-d H:i:s",$item['payment_time']):"";

            $data[] = [
                $item['realname'],
                $item['mobile'],
                $item['order_no']." ",
                $item['times'],
                $item['amount'],
                $item['trade_no'],
                $item['payment_time'],
                $item['sex'],
                $item['cret_no']." ",
                $item['create_time'],
                $item['zuqi'],
                $item['zujin'],
                $item['goods_name'],
                $item['specs'],
                $item['order_amount'],
                $item['order_yajin'],
                $item['mianyajin'],
                $item['pay_type'],
                $item['amount'],
                $item['pay_time'],
                $item['app_name'],
                $item['user_address'],

            ];
        }
        Excel::localWrite($data,$headers,$date."-ever-month","collection");
        echo "订单：".$orderError."<br/><br/>";
        echo "用户：".$userError."<br/><br/>";
        echo "商品：".$goodsError."<br/><br/>";
        echo "地址：".$addressError."<br/><br/>";
        return;
    }



}