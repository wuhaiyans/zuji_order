<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Excel;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\OrderExcel\CronCollection;
use App\Order\Modules\OrderExcel\CronOperator;
use App\Order\Modules\OrderExcel\CronRisk;
use App\Order\Modules\Repository\OrderUserAddressRepository;
use Illuminate\Http\Request;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;
use Illuminate\Support\Facades\DB;

/**
 * 订单买断接口控制器
 * @var  BuyoutController
 * @author limin<limin@huishoubao.com.cn>
 */

class TestExcelController extends Controller
{
    /********************临时导出****************************/
    /*
     * 用户买断
     * @param array $params 【必选】
     * [
     *      "goods_no"=>"",商品编号
     *      "user_id"=>"", 用户id
     * ]
     * @return json
     */
    public function operator(Request $request)
    {
        //接收请求参数
        $params =$request->all();

        $where[] = ['create_time', '>=', strtotime("2018-08-13 00:00:00"),];
        $where[] = ['create_time', '<=', strtotime("2018-08-19 23:59:59"),];

        $orderList = \App\Order\Models\Order::query()->where($where)->get()->toArray();

        if(!$orderList){
            return apiResponse([],ApiStatus::CODE_0);
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
        foreach($orderList as &$item){
            $item['order_status'] = OrderStatus::getStatusName($item['order_status']);
            $item['order_type'] = OrderStatus::getTypeName($item['order_type']);
            $item['pay_type'] = PayInc::getPayName($item['pay_type']);
            $item['realname'] = $userList[$item['order_no']]['realname'];
            $item['user_address'] = $userAddressList[$item['order_no']]['address_info'];
            $item['goods_name'] = $goodsList[$item['order_no']]['goods_name'];
            $item['zuqi'] = $goodsList[$item['order_no']]['zuqi'].OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['zujin'] = $goodsList[$item['order_no']]['zujin'];
            $item['specs'] = $goodsList[$item['order_no']]['specs'];
            $item['goods_yajin'] = $goodsList[$item['order_no']]['goods_yajin'];
            $item['mianyajin'] = $goodsList[$item['order_no']]['goods_yajin']-$goodsList[$item['order_no']]['yajin'];
            $item['price'] = $goodsList[$item['order_no']]['price'];
            $item['yajin'] = $goodsList[$item['order_no']]['yajin'];
            $item['insurance'] = $goodsList[$item['order_no']]['insurance'];
            $item['discount_amount'] = $goodsList[$item['order_no']]['discount_amount']+$goodsList[$item['order_no']]['coupon_amount'];

            $item['zuqi_type']= OrderStatus::getZuqiTypeName($goodsList[$item['order_no']]['zuqi_type']);
            $item['create_time'] = date("Y-m-d H:i:s",$item['create_time']);

            $data[] = [
                $item['order_no'],
                $item['create_time'],
                $item['order_status'],
                $item['order_type'],
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

        return Excel::localWrite($data, $headers,'运营数据-');
    }
    public function everDay(){
        $CronOperator = new CronOperator;
        $CronOperator->everDay();
        echo "success";
    }
    public function everWeek(){
        $CronOperator = new CronOperator;
        $CronOperator->everWeek();
        echo "success";
    }
    public function fiveteen(){
        $CronOperator = new CronOperator;
        $CronOperator->fiveteen();
        echo "success";
    }
    public function everMonth(){
        $CronOperator = new CronOperator;
        $CronOperator->everMonth();
        echo "success";
    }
    public function Month(){
        $obj = new CronCollection;
        $obj->everMonth();
        echo "success";
    }
    public function riskMonth(){
        $obj = new CronRisk();
        $obj->everMonth();
        echo "success";
    }
    public function riskAll(){
        $obj = new CronRisk();
        $obj->everAll();
        echo "success";
    }
}
