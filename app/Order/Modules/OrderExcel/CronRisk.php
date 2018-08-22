<?php
/**
 *    风控相关数据excel导出
 *    author: limin
 *    date : 2018-08-22
 */
namespace App\Order\Modules\oderExcels;

use App\Lib\Channel\Channel;
use App\Lib\Excel;
use App\Order\Models\Order;
use App\Order\Models\OrderGoodsInstalment;
use App\Order\Modules\Inc;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderUserAddressRepository;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;



class CronRisk
{


    /**
     *  每月1号定时导出上个整月订单催收数据
     * @return excel文件
     */
    public static function everMonth()
    {
        //cul获取渠道应用信息
        $channelList = Channel::getChannel();

        //获取上个月所有催收订单
        $date = date("Y-m",strtotime('-1 month'));
        $day = date("t",strtotime($date));
        $where[] = ['withhold_day', '>=', strtotime($date."-01 00:00:00"),];
        $where[] = ['withhold_day', '<=', strtotime($date."-".$day." 23:59:59"),];
        $status = [
            Inc\OrderInstalmentStatus::UNPAID,
            Inc\OrderInstalmentStatus::FAIL,
            Inc\OrderInstalmentStatus::PAYING,
        ];

        $instalmentList = OrderGoodsInstalment::query()->where($where)->wherein("status",$status)->get()->toArray();

        if(!$instalmentList){
            return false;
        }

        $orderNos = array_column($instalmentList,"order_no");
        array_unique($orderNos);
        asort($orderNos);
        //获取订单信息
        $orderList = Order::query()->wherein("order_no",$orderNos)->get()->toArray();
        $orderList = array_keys_arrange($orderList,"order_no");
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
        foreach($orderList as &$item){
            $item['realname'] = $userList[$item['order_no']]['realname'];
            $item['mobile'] = $orderList[$item['order_no']]['mobile'];
            $item['payment_time'] = $item['payment_time']>0?date("Y-m-d H:i:s",$item['payment_time']):"";
            $item['cret_no'] = $userList[$item['order_no']]['cret_no'];
            $item['sex'] = (int)substr($item['cret_no'],16,1)% 2 === 0 ? '女' : '男';
            $item['create_time'] = date("Y-m-d H:i:s", $orderList[$item['order_no']]['create_time']);
            $item['zuqi'] = $goodsList[$item['order_no']]['zuqi'].Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['order_amount'] = $orderList[$item['order_no']]['order_amount'];
            $item['order_yajin'] = $orderList[$item['order_no']]['order_yajin'];
            $item['mianyajin'] = $goodsList[$item['order_no']]['goods_yajin']-$goodsList[$item['order_no']]['yajin'];
            $item['pay_type'] = Inc\PayInc::getPayName($orderList[$item['order_no']]['pay_type']);
            $item['pay_time'] = date("Y-m-d H:i:s", $orderList[$item['order_no']]['pay_time']);
            $item['app_name'] = $channelList[$orderList[$item['order_no']]['appid']];
            $item['user_address'] = $userAddressList[$item['order_no']]['address_info'];

            $item['order_status'] = Inc\OrderStatus::getStatusName($item['order_status']);
            $item['order_type'] = Inc\OrderStatus::getTypeName($item['order_type']);



            $item['goods_name'] = $goodsList[$item['order_no']]['goods_name'];

            $item['zujin'] = $goodsList[$item['order_no']]['zujin'];
            $item['specs'] = $goodsList[$item['order_no']]['specs'];
            $item['goods_yajin'] = $goodsList[$item['order_no']]['goods_yajin'];

            $item['price'] = $goodsList[$item['order_no']]['price'];
            $item['yajin'] = $goodsList[$item['order_no']]['yajin'];
            $item['insurance'] = $goodsList[$item['order_no']]['insurance'];
            $item['discount_amount'] = $goodsList[$item['order_no']]['discount_amount']+$goodsList[$item['order_no']]['coupon_amount'];

            $item['zuqi_type']= Inc\OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['create_time'] = date("Y-m-d H:i:s",$item['create_time']);

            $data[] = [
                $item['realname'],
                $item['mobile'],
                $item['order_no'],
                $item['times'],
                $item['amount'],
                $item['trade_no'],
                $item['payment_time'],
                $item['sex'],
                $item['cret_no'],
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
        return Excel::localWrite($data,$headers,$date."-ever-month","collection");
    }



}