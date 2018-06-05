<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;

/**
 * 
 *
 * @author Administrator
 */
class Order {
	
	
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
	 * 读取订单原始数据
	 * @return array
	 */
	public function getData():array{
		return $this->data;
	}
	
	/**
	 * 取消
	 * @return bool 
	 */
	public function cancel():bool{
		return true;
	}
	
	/**
	 * 恢复（取消后的恢复）
	 * @return bool
	 */
	public function resume():bool{
		return true;
	}
	
	//-+------------------------------------------------------------------------
	// | 订单审核
	//-+------------------------------------------------------------------------
	/**
	 * 审核通过
	 * @return bool
	 */
	public function accept( string $status ):bool{
		return true;
	}
	/**
	 * 审核拒绝
	 * @return bool
	 */
	public function refuse( string $status ):bool{
		return true;
	}
	//-+------------------------------------------------------------------------
	// | 支付
	//-+------------------------------------------------------------------------
	/**
	 * 支付完成
	 * @return bool
	 */
	public function setPayStatus( string $status ):bool{
		return true;
	}
	
	//-+------------------------------------------------------------------------
	// | 退款
	//-+------------------------------------------------------------------------
	
	/**
	 * 申请退款
	 * @return bool
	 */
	public function refundOpen( ):bool{
		return true;
	}
	/**
	 * 取消退款
	 * @return bool
	 */
	public function refundClose( ):bool{
		return true;
	}
	/**
	 * 完成退款
	 * @return bool
	 */
	public function refundFinish( ):bool{
		return true;
	}
	
	//-+------------------------------------------------------------------------
	// | 发货
	//-+------------------------------------------------------------------------
    /**
     * 申请发货
     * @return bool
     */
    public function applyDelivery():bool{
        return true;
    }
	/**
	 * 取消发货
	 * @return bool
	 */
	public function closeDelivery( ):bool{
		return true;
	}
	/**
	 * 发货完成
	 * @return bool
	 */
	public function finishDelivery( ):bool{
		return true;
	}
	/**
	 * 签收
	 * @return bool
	 */
	public function signDelivery( ):bool{
		return true;
	}
	
	
	
	
	
	/**
	 * 获取订单
	 * <p>当订单不存在时，抛出异常</p>
	 * @param string $order_no		订单编号
	 * @param int		$lock			锁
	 * @return \App\Order\Modules\Repository\Order\Order
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getByNo( string $order_no, int $lock=0 ) {
		return new Order();
		throw new App\Lib\NotFoundException('');
	}
    //-+------------------------------------------------------------------------
    // | 退货
    //-+------------------------------------------------------------------------
    /**
     * @return bool
     * 申请退货
     */
    public function returnOpen( ):bool{
        return true;
    }
    /**
     * @return bool
     * 取消退货
     */
    public function returnClose( ):bool{
        return true;
    }
    /**
     * @return bool
     * 完成退货
     */
    public function returnFinish( ):bool{
        return true;
    }
    //-+------------------------------------------------------------------------
    // | 换货
    //-+------------------------------------------------------------------------
    /**
     * @return bool
     * 申请换货
     */
    public function barterOpen( ):bool{
        return true;
    }
    /**
     * @return bool
     * 取消换货
     */
    public function barterClose( ):bool{
        return true;
    }
    /**
     * @return bool
     * 完成换货
     */
    public function barterFinish( ):bool{
        return true;
    }


}
