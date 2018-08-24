<?php
/**
 *    风控相关数据excel导出
 *    author: limin
 *    date : 2018-08-21
 */
namespace App\Order\Modules\OrderExcel;

use App\Lib\Channel\Channel;
use App\Lib\Excel;
use App\Order\Models\Order;
use App\Order\Models\OrderGoodsInstalment;
use App\Order\Models\OrderRisk;
use App\Order\Modules\Inc;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderUserAddressRepository;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;



class CronRisk
{


    /**
     *  每月1号定时导出上个整月订单风控数据
     * @return excel文件
     */
    public function everMonth()
    {
        error_reporting(E_ALL ^ E_NOTICE);

        //cul获取渠道应用信息
        $channelList = Channel::getChannelListName();


        //获取所有订单
        $status = [
            Inc\OrderStatus::OrderInService,
            Inc\OrderStatus::OrderCompleted
        ];
        $limit = 500;
        $count = Order::query()->wherein("order_status",$status)->count();
        $data = [];
        //分批获取订单信息
        for($i=0;$i<ceil($count/$limit);$i++){
            $offset = $i==0?0:$i*$limit+1;
            $orderList = Order::query()->wherein("order_status",$status)->offset($offset)->limit($limit)->get()->toArray();
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

            $orderError = "";
            $userError = "";
            $goodsError = "";
            $addressError = "";
            foreach($orderList as $item){
                //订单相关信息

                $item['create_time'] = date("Y-m-d H:i:s", $item['create_time']);
                $item['pay_type'] = Inc\PayInc::getPayName($item['pay_type']);
                $item['app_name'] = $channelList[$item['appid']];

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
                for($i=1;$i<=12;$i++){
                    $item['term_'.$i] = "";
                }
                if($newInstalment[$item['order_no']]){
                    $instalment = $newInstalment[$item['order_no']];
                    foreach($instalment as $after){
                        $item['term_'.$after['times']] = $after['amount'];
                    }
                }

                $data[] = [
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
                    $item['order_insurance'],
                    $item['order_yajin'],
                    $item['mianyajin'],
                    $item['app_name'],
                    $item['user_address'],
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
            '碎屏意外险',
            '实押金',
            '免押金',
            '渠道',
            '收货地址',
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


        Excel::localWrite($data,$headers,date("Y-m-d"),"risk");
        echo "订单：".$orderError."<br/><br/>";
        echo "用户：".$userError."<br/><br/>";
        echo "商品：".$goodsError."<br/><br/>";
        echo "地址：".$addressError."<br/><br/>";
        return;
    }



}