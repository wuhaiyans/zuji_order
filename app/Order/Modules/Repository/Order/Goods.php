<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;

/**
 * Description of Goods
 *
 * @author Administrator
 */
class Goods {
	//put your code here
    /**
     *
     * @var array
     */
    private $data = [];

    /**
     * 构造函数
     * @param array $data 订单原始数据
     */
    public function __construct( array $data ) {
        $this->data = $data;
    }

    /**
     * 读取商品原始数据
     * @return array
     */
    public function getData():array{
        return $this->data;
    }
    //-+------------------------------------------------------------------------
    // | 退货
    //-+------------------------------------------------------------------------
    /**
     * 申请退货
     * @return bool
     */
    public function returnOpen( ):bool{

        return true;
    }
    /**
     * 取消退货
     * @return bool
     */
    public function returnClose( ):bool{
        return true;
    }
    /**
     * 完成退货
     * @return bool
     */
    public function returnFinish( ):bool{
        return true;
    }
    //-+------------------------------------------------------------------------
    // | 换货
    //-+------------------------------------------------------------------------
    /**
     * 申请换货
     * @return bool
     */
    public function barterOpen( ):bool{
        return true;
    }
    /**
     * 取消换货
     * @return bool
     */
    public function barterClose( ):bool{
        return true;
    }
    /**
     * 完成换货
     * @return bool
     */
    public function barterFinish( ):bool{
        return true;
    }
}
