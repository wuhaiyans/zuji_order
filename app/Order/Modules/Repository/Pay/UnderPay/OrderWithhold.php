<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderInstalmentStatus;

class OrderWithhold implements UnderLine {

    /**
     * 商品编号
     */
    protected $order_no = '';

    private $componnet;


    public function __construct( $params ) {

        $this->componnet = $params;

        $this->order_no = $params['order_no'];

    }



    /**
     * 计算该付款金额
     * return string
     */
    public function getPayAmount(){
        $instalmentList  = $this->instalmentList();
        $amount = 0;
        foreach($instalmentList as $item){
            $amount += $item['amount'];
        }

        return $amount;

    }

    /**
     * 实现具体业务
     * @return bool true  false
     */
    public function execute(){


        $surplusAmount  = $this->componnet['amount'];

        $instalmentList  = $this->instalmentList();

        foreach($instalmentList as $item){

            if($surplusAmount >= $item['amount']){
                $instalmentStatus = \App\Order\Modules\Repository\Order\Instalment::underLinePaySuccess($item['id']);
                if(!$instalmentStatus){
                    return false;
                }
            }

            // 根据后端输入金额 循环修改分期状态
            $surplusAmount -= $item['amount'];
        }

        return true;
    }

    /**
     * 未完成的分期列表
     * @return array
     */
    public function instalmentList( ){


        $statusArr = [OrderInstalmentStatus::UNPAID,  OrderInstalmentStatus::FAIL];

        $where = [
            'order_no'  => $this->order_no,
        ];

        $instalmentList = \App\Order\Models\OrderGoodsInstalment::query()
            ->where($where)
            ->whereIn('status',$statusArr)
            ->get()->toArray();

        if(!$instalmentList){
            LogApi::debug('[underLinePay]分期错误：'.$this->order_no);
            return [];
        }

        return $instalmentList;
    }

}
