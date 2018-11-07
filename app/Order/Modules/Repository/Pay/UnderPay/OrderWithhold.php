<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderInstalmentStatus;

class OrderWithhold implements UnderLine {

    /**
     * 商品编号
     */
    protected $order_no = '';

    /**
    * 分期数组
    */
    private $instalment_ids;

    private $componnet;


    public function __construct( $params ) {

        $this->componnet = $params;

        $this->order_no = $params['order_no'];

        $this->instalment_ids = $params['extend'];
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


        $instalmentList  = $this->instalmentList();

        foreach($instalmentList as $item){
            $resule = \App\Order\Modules\Repository\Order\Instalment::underLinePaySuccess($item['id']);
            if(!$resule){
                LogApi::debug('[underLinePay]修改分期状态错误：'.$this->order_no);
                return false;
            }
        }

        return true;

    }

    /**
     * 未完成的分期列表
     * @return array
     */
    public function instalmentList( ){


        $statusArr = [OrderInstalmentStatus::UNPAID,  OrderInstalmentStatus::FAIL];

        if(!$this->instalment_ids){
            LogApi::debug('[underLinePay]获取分期信息错误：'.$this->order_no);
            return [];
        }

        $instalment_ids = explode(',',$this->instalment_ids);

        $instalmentList = \App\Order\Models\OrderGoodsInstalment::query()
            ->whereIn('id',$instalment_ids)
            ->whereIn('status',$statusArr)
            ->get()->toArray();

        if(!$instalmentList){
            LogApi::debug('[underLinePay]分期错误：'.$this->order_no);
            return [];
        }

        return $instalmentList;
    }



}
