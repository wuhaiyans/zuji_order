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
class Goods {
	
	/**
	 * 获取商品列表
	 * @param string	$order_no		订单编号
	 * @param int		$lock			锁
	 * @return array
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getByOrderNo( string $order_no, int $lock=0 ) {
		
        $builder = \App\Order\Models\OrderGoods::where([
            ['order_no', '=', $order_no],
        ])->limit(1);
		if( $lock ){
			$builder->lockForUpdate();
		}
        $orderGoodData = $builder->get();
		$list = [];
		foreach( $orderGoodData as $it ) {
			$list[] = new Goods( $it->toArray() );
		}
		return $list;
	}
	
	/**
	 * 获取商品
	 * <p>当订单不存在时，抛出异常</p>
	 * @param string	$order_no		订单编号
	 * @param string	$goods_no		商品编号
	 * @param int		$lock			锁
	 * @return \App\Order\Modules\Repository\Order\Order
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getByGoodsNo( string $order_no, string $goods_no, int $lock=0 ) {
        $builder = \App\Order\Models\OrderGoods::where([
            ['order_no', '=', $order_no],
            ['goods_no', '=', $goods_no],
        ])->limit(1);
		if( $lock ){
			$builder->lockForUpdate();
		}
		$goods_info = $builder->first();
		if( !$goods_info ){
			throw new App\Lib\NotFoundException('商品未找到');
		}
		return new Goods( $goods_info->toArray() );
	}

	/**
     * 续租完成
     *      支付完成或创建分期成功执行
     *
     * 步骤:
     *  1.修改商品状态
     *  2.添加新周期
     *  3.修改订单状态
     *  4.解锁订单
     *
     * @author jinlin wang
     * @param array
     * @return boolean
     */
	public static function reletFinish(){
	    //修改商品状态
        //添加新周期
        //修改订单状态
        //解锁订单
        return true;
    }
	
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
        // 校验自己状态
        if( 0 ){
            return false;
        }

        // 更新状态
        if( 0 ){
            return false;
        }

        // 获取当前订单
        $order = Order::getByNo($this->data['order_no'] );
        // 更新订单状态
        return $order->returnClose( );
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
    //-+------------------------------------------------------------------------
    // | 插入Imei
    //-+------------------------------------------------------------------------
    public function createGoodsExtends():bool {
        return true;
    }
}
