<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;
use App\Order\Models\OrderGoods;
use App\Order\Modules\Inc\GoodStatus;
use App\Order\Modules\Inc\OrderGoodStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\publicInc;

/**
 * 
 *
 * @author Administrator
 */
class Goods {
	
    /**
     *
     * @var OrderGoods
     */
    private $model = [];

    private $order = null;

    /**
     * 构造函数
     * @param array $data 订单原始数据
     */
    public function __construct( OrderGoods $model ) {
        $this->model = $model;
    }

    /**
     * 读取商品原始数据
     * @return array
     */
    public function getData():array{
        return $this->model->toArray();
    }


    /**
     * 获取商品对应订单
     */
    public function getOrder( ){
        if( is_null($this->order) ){
            $this->order = Order::getByNo( $this->model->order_no, true);
        }
        return $this->order;
    }

    //-+------------------------------------------------------------------------
    // | 退货
    //-+------------------------------------------------------------------------
    /**
     * 申请退货
     * @return bool
     */
    public function returnOpen( ):bool{
        //商品必须为租用中
        if( $this->model->goods_status != OrderGoodStatus::RENTING_MACHINE ){
            return false;
        }
        // 状态改为退货中
        $this->model->goods_status = OrderGoodStatus::REFUNDS;
        return $this->model->save();
    }
    /**
     * 取消退货
     * @return bool
     */
    public function returnClose( ){
        // 校验自己状态
        if(!$this->data ){
            return false;
        }

        // 更新商品状态
        $where[]=['id','=',$this->data['id']];
        $data['goods_status']=OrderGoodStatus::RENTING_MACHINE;
        $updateGoodsStatus=OrderGoods::where($where)->update($data);
        if(!$updateGoodsStatus){
            return false;
        }
        try{
            // 获取当前订单
            $orderInfo =Order::getByNo($this->data['order_no']);
            $order=new \App\Order\Modules\Repository\Order\Order($orderInfo);
            // 更新订单状态
            $b = $order->returnClose($order);
            if( !$b ){
                return false;
            }
        }catch(\Exception $exc){
            return false;
        }


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
        //商品必须为租用中
        if( $this->model->goods_status != OrderGoodStatus::RENTING_MACHINE ){
            return false;
        }
        // 状态改为换货中
        $this->model->goods_status = OrderGoodStatus::EXCHANGE_GOODS;
        return $this->model->save();
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
    // | 还机
    //-+------------------------------------------------------------------------
	/**
	 * 还机开始
	 * @return bool
	 */
    public function givebackOpen():bool {
        return true;
    }
	/**
	 * 还机关闭
	 * @return bool
	 */
    public function givebackClose():bool {
        return true;
    }
	/**
	 * 还机完成
	 * @return bool
	 */
    public function givebackFinish():bool {
        return true;
    }
	
    //-+------------------------------------------------------------------------
    // | 买断
    //-+------------------------------------------------------------------------
	/**
	 * 买断开始
	 * @return bool
	 */
    public function buyoutOpen():bool {
        return true;
    }
	/**
	 * 买断关闭
	 * @return bool
	 */
    public function buyoutClose():bool {
        return true;
    }
	/**
	 * 买断完成
	 * @return bool
	 */
    public function buyoutFinish():bool {
        return true;
    }
	
	//-+------------------------------------------------------------------------
	// | 续租
	//-+------------------------------------------------------------------------
    /**
     * 续租开始
     *
     * @param array
     * [
     *      'zuqi'  =>  '', // 【必选】int 租期
     * ]
     */
    public function reletOpen($params){
        //校验 时间格式
        if( $this->data['zuqi_type']==OrderStatus::ZUQI_TYPE1 ){
            if( $params['zuqi']<3 || $params['zuqi']<30 ){
                set_msg('租期错误');
                return false;
            }
        }else{
            if( !publicInc::getCangzuRow($params['zuqi']) && $params['zuqi']!=0 ){
                set_msg('租期错误');
                return false;
            }
        }

        $amount = $this->data['zujin']*$params['zuqi'];

        // 更新goods状态

        // 订单续租
        $order = Order::getByNo($this->data['order_no']);
        return $order->reletOpen();
    }

    /**
     * 续租关闭
     */
    public function reletClose(){

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
	
	//-+------------------------------------------------------------------------
	// | 静态方法
	//-+------------------------------------------------------------------------
	/**
	 * 获取商品列表
	 * @param string	$order_no		订单编号
	 * @param int		$lock			锁
	 * @return array
	 */
	public static function getByOrderNo( string $order_no, int $lock=0 ) {
		
        $builder = \App\Order\Models\OrderGoods::where([
            ['order_no', '=', $order_no],
        ]);
		if( $lock ){
			$builder->lockForUpdate();
		}
        $orderGoodData = $builder->get();
		$list = [];
		foreach( $orderGoodData as $it ) {
			$list[] = new Goods( $it );
		}
		return $list;
	}
	
	/**
	 * 获取商品
	 * <p>当订单不存在时，抛出异常</p>
	 * @param int   	$id		    ID
	 * @param int		$lock		锁
	 * @return \App\Order\Modules\Repository\Order\Goods
	 * @return  bool
	 */
	public static function getByGoodsId( int $id, int $lock=0 ) {
        $builder = \App\Order\Models\OrderGoods::where([
            ['id', '=', $id],
        ])->limit(1);
		if( $lock ){
			$builder->lockForUpdate();
		}
		$goods_info = $builder->first();
		if( !$goods_info ){
			return false;
		}
		return new Goods( $goods_info );
	}
    /**
    * 获取商品
    * <p>当订单不存在时，抛出异常</p>
    * @param int   	$goods_no		    商品编号
    * @param int		$lock		锁
    * @return \App\Order\Modules\Repository\Order\Goods
    * @return  bool
    */
    public static function getByGoodsNo( $goods_no, int $lock=0 ) {
        $builder = \App\Order\Models\OrderGoods::where([
            ['goods_no', '=', $goods_no],
        ])->limit(1);
        if( $lock ){
            $builder->lockForUpdate();
        }
        $goods_info = $builder->first();
        if( !$goods_info ){
            return false;
        }
        return new Goods( $goods_info );
    }

}
