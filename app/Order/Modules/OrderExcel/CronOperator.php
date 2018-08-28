<?php
/**
 *    运营相关数据excel导出
 *    author: limin
 *    date : 2018-08-21
 */
namespace App\Order\Modules\OrderExcel;

use App\Lib\Channel\Channel;
use App\Lib\Excel;
use App\Order\Modules\Inc;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderUserAddressRepository;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;



class CronOperator
{


    /**
     *  每天定时导出整日数据
     * @return excel文件
     */
    public static function everDay()
    {
        //cul获取渠道应用信息
        $channelList = Channel::getChannelAppidListName();
        if(isset($_GET['day'])){
            $date = $_GET['day'];
        }else{
            //获取当天所有订单
            $date = date("Y-m-d",strtotime("Yesterday"));
        }
        $where[] = ['create_time', '>=', strtotime($date." 00:00:00"),];
        $where[] = ['create_time', '<=', strtotime($date." 23:59:59"),];

        $orderList = \App\Order\Models\Order::query()->where($where)->get()->toArray();

        if(!$orderList){
            return false;
        }
        //获取订单商品信息
        $orderNos = array_column($orderList,"order_no");
        $goodsList= OrderGoodsRepository::getOrderGoodsColumn($orderNos);
        //获取订单用户信息
        $userList = OrderUserCertifiedRepository::getUserColumn($orderNos);
        //获取订单地址信息
        $userAddressList = OrderUserAddressRepository::getUserAddressColumn($orderNos);
        //定义excel头部参数名称
        $headers = [
            '订单编号',
            '下单时间',
            '订单状态',
            '订单来源',
            '下单渠道',
            '支付方式及通道',
            '用户名',
            '手机号',
            '详细地址',
            '设备名称',
            '租期',
            '租金',
            '商品属性',
            '初始押金',
            '免押金额',
            '订单实际总租金',
            '订单实缴押金',
            '意外险总金额',
            '实际已优惠金额',
        ];
        $data = [];
        foreach($orderList as $item){
            $item['order_status'] = Inc\OrderStatus::getStatusName($item['order_status']);
            $item['order_type'] = Inc\OrderStatus::getTypeName($item['order_type']);
            $item['pay_type'] = Inc\PayInc::getPayName($item['pay_type']);
            $item['realname'] = $userList[$item['order_no']]['realname'];
            $item['user_address'] = $userAddressList[$item['order_no']]['address_info'];
            $item['goods_name'] = $goodsList[$item['order_no']]['goods_name'];
            $item['zuqi'] = $goodsList[$item['order_no']]['zuqi'].Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['zujin'] = $goodsList[$item['order_no']]['zujin'];
            $item['specs'] = $goodsList[$item['order_no']]['specs'];
            $item['goods_yajin'] = $goodsList[$item['order_no']]['goods_yajin'];
            $item['mianyajin'] = $goodsList[$item['order_no']]['goods_yajin']-$goodsList[$item['order_no']]['yajin'];
            $item['price'] = $goodsList[$item['order_no']]['price'];
            $item['yajin'] = $goodsList[$item['order_no']]['yajin'];
            $item['insurance'] = $goodsList[$item['order_no']]['insurance'];
            $item['discount_amount'] = $goodsList[$item['order_no']]['discount_amount']+$goodsList[$item['order_no']]['coupon_amount'];

            $item['zuqi_type']= Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['create_time'] = date("Y-m-d H:i:s",$item['create_time']);

            $item['app_name'] = $channelList[$item['appid']];

            $data[] = [
                $item['order_no']." ",
                $item['create_time'],
                $item['order_status'],
                $item['order_type'],
                $item['app_name'],
                $item['pay_type'],
                $item['realname'],
                $item['mobile'],
                $item['user_address'],
                $item['goods_name'],
                $item['zuqi'],
                $item['zujin'],
                $item['specs'],
                $item['goods_yajin'],
                $item['mianyajin'],
                $item['price'],
                $item['yajin'],
                $item['insurance'],
                $item['discount_amount'],
            ];
        }
        return Excel::localWrite($data,$headers,$date."-ever-day","operator/day");
    }

    /**
     *  定时导出每周订单数据
     * @return excel文件
     */
    public static function everWeek(){
        //cul获取渠道应用信息
        $channelList = Channel::getChannelAppidListName();
        //获取当天所有订单
        $monday = date("Y-m-d",strtotime("Last week"));
        $sunday = date("Y-m-d",strtotime("Last Sunday"));
        $where[] = ['create_time', '>=', strtotime($monday." 00:00:00"),];
        $where[] = ['create_time', '<=', strtotime($sunday." 23:59:59"),];

        $orderList = \App\Order\Models\Order::query()->where($where)->get()->toArray();

        if(!$orderList){
            return false;
        }
        //获取订单商品信息
        $orderNos = array_column($orderList,"order_no");
        $goodsList = OrderGoodsRepository::getOrderGoodsColumn($orderNos);
        //获取订单用户信息
        $userList = OrderUserCertifiedRepository::getUserColumn($orderNos);
        //获取订单地址信息
        $userAddressList = OrderUserAddressRepository::getUserAddressColumn($orderNos);
        //定义excel头部参数名称
        $headers = [
            '订单编号',
            '下单时间',
            '订单状态',
            '订单来源',
            '下单渠道',
            '支付方式及通道',
            '用户名',
            '手机号',
            '详细地址',
            '设备名称',
            '租期',
            '租金',
            '商品属性',
            '初始押金',
            '免押金额',
            '订单实际总租金',
            '订单实缴押金',
            '意外险总金额',
            '实际已优惠金额',
        ];
        $data = [];
        foreach($orderList as $item){
            $item['order_status'] = Inc\OrderStatus::getStatusName($item['order_status']);
            $item['order_type'] = Inc\OrderStatus::getTypeName($item['order_type']);
            $item['pay_type'] = Inc\PayInc::getPayName($item['pay_type']);
            $item['realname'] = $userList[$item['order_no']]['realname'];
            $item['user_address'] = $userAddressList[$item['order_no']]['address_info'];
            $item['goods_name'] = $goodsList[$item['order_no']]['goods_name'];
            $item['zuqi'] = $goodsList[$item['order_no']]['zuqi'].Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['zujin'] = $goodsList[$item['order_no']]['zujin'];
            $item['specs'] = $goodsList[$item['order_no']]['specs'];
            $item['goods_yajin'] = $goodsList[$item['order_no']]['goods_yajin'];
            $item['mianyajin'] = $goodsList[$item['order_no']]['goods_yajin']-$goodsList[$item['order_no']]['yajin'];
            $item['price'] = $goodsList[$item['order_no']]['price'];
            $item['yajin'] = $goodsList[$item['order_no']]['yajin'];
            $item['insurance'] = $goodsList[$item['order_no']]['insurance'];
            $item['discount_amount'] = $goodsList[$item['order_no']]['discount_amount']+$goodsList[$item['order_no']]['coupon_amount'];

            $item['zuqi_type']= Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['create_time'] = date("Y-m-d H:i:s",$item['create_time']);

            $item['app_name'] = $channelList[$item['appid']];
            $data[] = [
                $item['order_no']." ",
                $item['create_time'],
                $item['order_status'],
                $item['order_type'],
                $item['app_name'],
                $item['pay_type'],
                $item['realname'],
                $item['mobile'],
                $item['user_address'],
                $item['goods_name'],
                $item['zuqi'],
                $item['zujin'],
                $item['specs'],
                $item['goods_yajin'],
                $item['mianyajin'],
                $item['price'],
                $item['yajin'],
                $item['insurance'],
                $item['discount_amount'],
            ];
        }
        return Excel::localWrite($data,$headers,$sunday."-ever-week","operator/week");
    }

    /**
     *  每天定时导出今天前15天订单数据
     * @return excel文件
     */
    public static  function fiveteen(){
        //cul获取渠道应用信息
        $channelList = Channel::getChannelAppidListName();
        //获取当天所有订单
        $beginDay = date("Y-m-d",strtotime("Yesterday -15 day"));
        $endDay = date("Y-m-d",strtotime("Yesterday"));
        $where[] = ['create_time', '>=', strtotime($beginDay." 00:00:00"),];
        $where[] = ['create_time', '<=', strtotime($endDay." 23:59:59"),];

        $orderList = \App\Order\Models\Order::query()->where($where)->get()->toArray();

        if(!$orderList){
            return false;
        }
        //获取订单商品信息
        $orderNos = array_column($orderList,"order_no");
        $goodsList= OrderGoodsRepository::getOrderGoodsColumn($orderNos);
        //获取订单用户信息
        $userList = OrderUserCertifiedRepository::getUserColumn($orderNos);
        //获取订单地址信息
        $userAddressList = OrderUserAddressRepository::getUserAddressColumn($orderNos);
        //定义excel头部参数名称
        $headers = [
            '订单编号',
            '下单时间',
            '订单状态',
            '订单来源',
            '下单渠道',
            '支付方式及通道',
            '用户名',
            '手机号',
            '详细地址',
            '设备名称',
            '租期',
            '租金',
            '商品属性',
            '初始押金',
            '免押金额',
            '订单实际总租金',
            '订单实缴押金',
            '意外险总金额',
            '实际已优惠金额',
        ];
        $data = [];
        foreach($orderList as $item){
            $item['order_status'] = Inc\OrderStatus::getStatusName($item['order_status']);
            $item['order_type'] = Inc\OrderStatus::getTypeName($item['order_type']);
            $item['pay_type'] = Inc\PayInc::getPayName($item['pay_type']);
            $item['realname'] = $userList[$item['order_no']]['realname'];
            $item['user_address'] = $userAddressList[$item['order_no']]['address_info'];
            $item['goods_name'] = $goodsList[$item['order_no']]['goods_name'];
            $item['zuqi'] = $goodsList[$item['order_no']]['zuqi'].Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['zujin'] = $goodsList[$item['order_no']]['zujin'];
            $item['specs'] = $goodsList[$item['order_no']]['specs'];
            $item['goods_yajin'] = $goodsList[$item['order_no']]['goods_yajin'];
            $item['mianyajin'] = $goodsList[$item['order_no']]['goods_yajin']-$goodsList[$item['order_no']]['yajin'];
            $item['price'] = $goodsList[$item['order_no']]['price'];
            $item['yajin'] = $goodsList[$item['order_no']]['yajin'];
            $item['insurance'] = $goodsList[$item['order_no']]['insurance'];
            $item['discount_amount'] = $goodsList[$item['order_no']]['discount_amount']+$goodsList[$item['order_no']]['coupon_amount'];

            $item['zuqi_type']= Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['create_time'] = date("Y-m-d H:i:s",$item['create_time']);

            $item['app_name'] = $channelList[$item['appid']];
            $data[] = [
                $item['order_no']." ",
                $item['create_time'],
                $item['order_status'],
                $item['order_type'],
                $item['app_name'],
                $item['pay_type'],
                $item['realname'],
                $item['mobile'],
                $item['user_address'],
                $item['goods_name'],
                $item['zuqi'],
                $item['zujin'],
                $item['specs'],
                $item['goods_yajin'],
                $item['mianyajin'],
                $item['price'],
                $item['yajin'],
                $item['insurance'],
                $item['discount_amount'],
            ];
        }
        return Excel::localWrite($data,$headers,$endDay."-ever-day-15th","operator/15th");
    }

    /**
     *  每月定时导出上月24-下月24号订单数据
     * @return excel文件
     */
    public static  function everMonth(){
        //cul获取渠道应用信息
        $channelList = Channel::getChannelAppidListName();
        if(isset($_GET['begin']) && isset($_GET['end'])){
            $beginDay = $_GET['begin'];
            $endDay = $_GET['end'];
        }else{
            //获取上月26号-下月25号所有订单
            $beginDay = date("Y-m-26",strtotime("Last Month"));
            $endDay = date("Y-m-25",time());
        }

        $where[] = ['create_time', '>=', strtotime($beginDay." 00:00:00"),];
        $where[] = ['create_time', '<=', strtotime($endDay." 23:59:59"),];

        $orderList = \App\Order\Models\Order::query()->where($where)->get()->toArray();

        if(!$orderList){
            return false;
        }
        //获取订单商品信息
        $orderNos = array_column($orderList,"order_no");
        $goodsList= OrderGoodsRepository::getOrderGoodsColumn($orderNos);
        //获取订单用户信息
        $userList = OrderUserCertifiedRepository::getUserColumn($orderNos);
        //获取订单地址信息
        $userAddressList = OrderUserAddressRepository::getUserAddressColumn($orderNos);
        //定义excel头部参数名称
        $headers = [
            '订单编号',
            '下单时间',
            '订单状态',
            '订单来源',
            '下单渠道',
            '支付方式及通道',
            '用户名',
            '手机号',
            '详细地址',
            '设备名称',
            '租期',
            '租金',
            '商品属性',
            '初始押金',
            '免押金额',
            '订单实际总租金',
            '订单实缴押金',
            '意外险总金额',
            '实际已优惠金额',
        ];
        $data = [];
        foreach($orderList as $item){
            $item['order_status'] = Inc\OrderStatus::getStatusName($item['order_status']);
            $item['order_type'] = Inc\OrderStatus::getTypeName($item['order_type']);
            $item['pay_type'] = Inc\PayInc::getPayName($item['pay_type']);
            $item['realname'] = empty($userList[$item['order_no']]['realname'])?"":$userList[$item['order_no']]['realname'];
            $item['user_address'] = empty($userAddressList[$item['order_no']]['address_info'])?"":$userAddressList[$item['order_no']]['address_info'];
            $item['goods_name'] = $goodsList[$item['order_no']]['goods_name'];
            $item['zuqi'] = $goodsList[$item['order_no']]['zuqi'].Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['zujin'] = $goodsList[$item['order_no']]['zujin'];
            $item['specs'] = $goodsList[$item['order_no']]['specs'];
            $item['goods_yajin'] = $goodsList[$item['order_no']]['goods_yajin'];
            $item['mianyajin'] = $goodsList[$item['order_no']]['goods_yajin']-$goodsList[$item['order_no']]['yajin'];
            $item['price'] = $goodsList[$item['order_no']]['price'];
            $item['yajin'] = $goodsList[$item['order_no']]['yajin'];
            $item['insurance'] = $goodsList[$item['order_no']]['insurance'];
            $item['discount_amount'] = $goodsList[$item['order_no']]['discount_amount']+$goodsList[$item['order_no']]['coupon_amount'];

            $item['zuqi_type']= Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['create_time'] = date("Y-m-d H:i:s",$item['create_time']);

            $item['app_name'] = $channelList[$item['appid']];
            $data[] = [
                $item['order_no']." ",
                $item['create_time'],
                $item['order_status'],
                $item['order_type'],
                $item['app_name'],
                $item['pay_type'],
                $item['realname'],
                $item['mobile'],
                $item['user_address'],
                $item['goods_name'],
                $item['zuqi'],
                $item['zujin'],
                $item['specs'],
                $item['goods_yajin'],
                $item['mianyajin'],
                $item['price'],
                $item['yajin'],
                $item['insurance'],
                $item['discount_amount'],
            ];
        }
        return Excel::localWrite($data,$headers,$endDay."-ever-month","operator/month");
    }
}