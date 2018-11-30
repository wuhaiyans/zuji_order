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

        $amount    = 0;
        $minAmount = 0;

        foreach($instalmentList as $key=>$item){
            if($key == 0){
                $minAmount = $item['amount'];
            }
            $amount += $item['amount'];
        }


        $data = [
            'amount'       => $amount,
            'minAmount'    => $minAmount
        ];
        return $data;

    }

    /**
     * 实现具体业务
     * @return bool true  false
     */
    public function execute(){


        $surplusAmount  = bcmul($this->componnet['amount'],100);

        $instalmentList  = $this->instalmentList();

        foreach($instalmentList as $item){
            $item['amount'] = bcmul($item['amount'],100);
            if($surplusAmount >= $item['amount']){
                $instalmentStatus = \App\Order\Modules\Repository\Order\Instalment::underLinePaySuccess($item['id']);
                if(!$instalmentStatus){
                    throw new \Exception("修改分期状态失败");
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
            ->orderBy("times","asc")
            ->get()->toArray();

        if(!$instalmentList){

            LogApi::debug('[underLinePay]分期错误：'.$this->order_no);
            throw new \Exception("暂无服务中未扣款分期！");
        }

        return $instalmentList;
    }

}
