<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;

use App\Order\Models\Order AS OrderModel;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\OrderGoodStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;

/**
 * 
 *
 * @author Administrator
 */
class Order {
	
	
	/**
	 *
	 * @var OrderModel
	 */
	private $model = [];
	
	/**
	 * 构造函数
	 * @param array $data 订单原始数据
	 */
	public function __construct( OrderModel $data ) {
		$this->model = $data;
	}
	
	/**
	 * 读取订单原始数据
	 * @return array
	 */
	public function getData():array{
		return $this->model->toArray();
	}
	
	/**
	 * 强制关闭
	 * @return bool 
	 */
	public function close():bool{
		$this->model->order_status = OrderStatus::OrderCancel;
		return $this->model->save();
	}
	
	/**
	 * 尝试关闭
	 * 如果订单中的商品都结束时，关闭订单
	 * @return bool 
	 */
	public function tryClose():bool{
		if( $this->_activeGoodsCount() == 0 ){
			return $this->close();
		}
		return false;
	}
	
//	/**
//	 * 恢复（关闭后的恢复）
//	 * 【暂时不支持】
//	 * @return bool
//	 */
//	public function resume():bool{
//		return true;
//	}
	
	/**
	 * 强制完成订单
	 * 强制结束订单和订单中租用中的的商品
	 * @return bool 
	 */
	public function finish():bool{
		$this->model->order_status = OrderStatus::OrderCompleted;
		return $this->model->save();
	}
	/**
	 * 尝试完成订单
	 * 每个商品完成时，尝试完成订单
	 * 如果订单中还有其他商品的租赁关系还未结束时，什么都不做
	 * 如果当前订单中所有商品都已经结束时，执行订单完成
	 * @return bool 
	 */
	public function tryFinish():bool{
		if( $this->_activeGoodsCount() == 0 ){
			return $this->finish();
		}
		// 
		return false;
	}
	
	/**
	 * 活跃订单数量
	 * @return int 有效订单数
	 */
	private function _activeGoodsCount():int{
		
		// 订单下的商品是否都已经结束
		// 租用中 或  其他业务处理中 的商品数量
		// 【问题： where条件的 in 表达式 无法使用，打印的sql为 = 操作】
		return \App\Order\Models\OrderGoods::where([
			'order_no' => $this->model->order_no,
			'goods_status' => ['in',[
				OrderGoodStatus::RENTING_MACHINE,
				OrderGoodStatus::REFUNDS,
				OrderGoodStatus::EXCHANGE_GOODS,
				OrderGoodStatus::BACK_IN_THE_MACHINE,
				OrderGoodStatus::BUY_OFF,
				OrderGoodStatus::RELET,
				]]
		])->limit(1)->count('id');
	}
	
	//-+------------------------------------------------------------------------
	// | 订单审核
	//-+------------------------------------------------------------------------
	/**
	 * 审核通过，变为备货中
	 * @return bool
	 */
	public function accept( ):bool{
		// 必须为已支付状态
		if( $this->model->order_status != OrderStatus::OrderPayed ){
			return false;
		}
		$this->model->order_status = OrderStatus::OrderInStock;
		return $this->model->save();
	}
	
//	/**
//	 * 审核拒绝，冻结订单，退款中
//	 * @return bool
//	 */
//	public function refuse( ):bool{
//		// 必须为已支付状态
//		if( $this->model->order_status != OrderStatus::OrderPayed ){
//			return false;
//		}
//		// 退款中
//		$this->model->freeze_type = OrderFreezeStatus::Refund;
//		return $this->model->save();
//		return true;
//	}
	
	//-+------------------------------------------------------------------------
	// | 支付
	//-+------------------------------------------------------------------------
	/**
	 * 支付完成，状态变为 已支付，待确认
	 * @params array	$data
	 * [
	 *		'status'	=> '',	//【必选】string 支付状态
	 *		'pay_type'	=> '',	//【必选】int 支付方式 （status=success时必选）
	 * ]
	 * @return bool
	 */
	public function setPayStatus( string $data ):bool{
		// 必须为 等待支付 或 支付中
		if( $this->model->order_status != OrderStatus::OrderPaying
				|| $this->model->order_status != OrderStatus::OrderWaitPaying){
			return false;
		}
		// 支付中
		if( $data['status'] == 'processing' ){
			$this->model->order_status = OrderStatus::OrderPaying; 
		}
		// 支付成功
		elseif( $data['status'] == 'success' ){
			$this->model->order_status = OrderStatus::OrderPayed; 
		}else{
			return false;
		}
		return $this->model->save();
	}
	
	/**
	 * 获取支付信息
	 * <p>使用场景：</p>
	 * <p>1、订单创建支付单时，使用</p>
	 * <p>2、订单创建清算单时，使用</p>
	 * @return array
	 * [
	 *		'pay_status'=> '',	// 【必选】int 支付状态；0：未支付；1：支付成功
	 *		'pay_type'	=> '',	// 【必选】int 支付方式 默认为：0（未支付时）
	 *		'payment' => [		// 【可选】array 直接支付信息
	 *			'total_amount'		=> '',	//【必选】int 总金额；单位：分；默认为：0
	 *			'zujin_amount'		=> '',	//【必选】int 租金金额；单位：分；默认为：0
	 *			'insurance_amount'	=> '',	//【必选】int 保险金额；单位：分；默认为：0
	 *			'yajin_amount'		=> '',	//【必选】int 押金金额；单位：分；默认为：0
	 *		],
	 *		'fundauth' => [		// 【可选】array 预授权金额
	 *			'total_amount'		=> '',	//【必选】int 总金额；单位：分；默认为：0
	 *			'zujin_amount'		=> '',	//【必选】int 租金金额；单位：分；默认为：0
	 *			'insurance_amount'	=> '',	//【必选】int 保险金额；单位：分；默认为：0
	 *			'yajin_amount'		=> '',	//【必选】int 押金金额；单位：分；默认为：0
	 *		],
	 * ]
	 */
	public function getPayInfo(){
		
		return [
			'pay_status'=> '',
			'pay_type'	=> '',
			'payment' => [
				'total_amount'		=> '',
				'zujin_amount'		=> '',
				'insurance_amount'	=> '',
				'yajin_amount'		=> '',
			],
			'fundauth' => [
				'total_amount'		=> '',
				'zujin_amount'		=> '',
				'insurance_amount'	=> '',
				'yajin_amount'		=> '',
			],
		];
	}
	
	//-+------------------------------------------------------------------------
	// | 发货
	//-+------------------------------------------------------------------------
    /**
     *  申请发货
	 * 【申请发货和确认订单 是一个操作】
     * @return bool
     */
    public function deliveryOpen(string $remark):bool{
        if($this->model->order_status!=OrderStatus::OrderPayed && $this->model->freeze_type == OrderFreezeStatus::Non){
            return false;
        }
        $this->model->order_status =OrderStatus::OrderInStock;
        $this->model->confirm_time =time();
        $this->model->remark =$remark;
        return $this->model->save();
    }
	/**
	 * 取消发货，状态切回到 已支付，待确认
	 * @return bool
	 */
	public function deliveryClose( ):bool{
		$this->model->order_status = OrderStatus::OrderPaying; 
		return $this->model->save();
	}
	/**
	 * 发货完成，状态变为 已返货，待签收
	 * @return bool
	 */
	public function deliveryFinish( ):bool{
        if($this->model->order_status!=OrderStatus::OrderInStock && $this->model->freeze_type == OrderFreezeStatus::Non){
            return false;
        }
		$this->model->order_status = OrderStatus::OrderDeliveryed;
		$this->model->delivery_time = time();
		return $this->model->save();
	}
	/**
	 * 签收，状态变为 已签收，租用中
	 * @return bool
	 */
	public function sign( ):bool{
		return true;
	}
	
	
	//-+------------------------------------------------------------------------
	// | 退款
	//-+------------------------------------------------------------------------
	/**
	 * 申请退款，冻结状态，退款中
	 * @return bool
	 */
	public function refundOpen( ):bool{
		// 退款中
		$this->model->freeze_type = OrderFreezeStatus::Refund;
		return $this->model->save();
	}
	/**
	 * 取消退款，取消退款冻结
	 * @return bool
	 */
	public function refundClose( ):bool{
		return true;
	}
	/**
	 * 完成退款，更新状态：订单关闭
	 * @return bool
	 */
	public function refundFinish( ):bool{
		$this->model->order_status = OrderStatus::OrderClosedRefunded; 
		return $this->model->save();
		return true;
	}
	
	
    //-+------------------------------------------------------------------------
    // | 退货
    //-+------------------------------------------------------------------------
    /**
     * 申请退货
     * @return bool
     */
    public function returnOpen( ):bool{
        //
        if( $this->model->freeze_type !=0 ){
            return false;
        }
        $this->model->freeze_type = OrderFreezeStatus::GoodsReturn;
        return $this->model->save();
    }
    /**
     * 取消退货
     * @return bool
     */
    public function returnClose( ):bool{
        // 校验自己状态
        if(!$this->model){
            return false;
        }
        // 更新订单状态
        $where[]=['order_no','=',$this->data['order_no']];
        $data['freeze_type']=OrderFreezeStatus::Non;
        $updateOrderStatus=$this->model::where($where)->update($data);
        if(!$updateOrderStatus){
            return false;
        }
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
     * 订单续租开始
     *
     * 1.验证订单是否冻结
     * 2.冻结订单
     */
    public function reletOpen($reletNo){
        $this->getData();
        $b = ReletRepository::reletPayStatus($reletNo, ReletStatus::STATUS2);
        if (!$b) {
            LogApi::notify("续租修改支付状态失败", $reletNo);
            return false;
        }
        //查询
        // 续租表
        $reletRow = OrderRelet::where('relet_no','=',$reletNo)->first(['goods_id'])->toArray();

        // 设备表
        $goodsObj = OrderGoods::where('id','=',$reletRow['goods_id'])->first();
        // 设备周期表
        $goodsUnitRow = OrderGoodsUnit::where([
            ['order_no','=',$goodsObj->order_no],
            ['goods_no','=',$goodsObj->goods_no]
        ])->orderBy('id','desc')->first();
        if($goodsUnitRow){
            $goodsUnitRow = $goodsUnitRow->toArray();
        }else{
            LogApi::notify("周期表数据错误", $reletNo);
            return false;
        }
        //判断租期类型
        if($reletRow['zuqi_type']==OrderStatus::ZUQI_TYPE1){
            $t = $reletRow['zuqi']*(60*60*24);
        }else{
            $t = $reletRow['zuqi']*30*(60*60*24);
        }
        $data = [
            'order_no'=>$goodsObj->order_no,
            'goods_no'=>$goodsObj->goods_no,
            'user_id'=>$goodsObj->user_id,
            'unit'=>$reletRow['zuqi_type'],
            'unit_value'=>$reletRow['zuqi'],
            'begin_time'=>$goodsUnitRow['begin_time'],
            'end_time'=>$goodsUnitRow['begin_time']+$t,
        ];

        //修改订单商品状态
        $goodsObj->goods_status=OrderGoodStatus::RENEWAL_OF_RENT;
        $goodsObj->update_time=time();
        if( !$goodsObj->save() ){
            LogApi::notify("续租修改设备状态失败", $reletNo);
            return false;
        }
        //添加设备周期表
        if( !OrderGoodsUnit::insert($data) ){
            LogApi::notify("续租添加设备周期表失败", $reletNo);
            return false;
        }

        LogApi::notify("续租支付成功", $reletNo);
        return true;
    }

    /**
     * 订单续租关闭
     *
     * 1.验证订单是否冻结
     * 2.解冻订单
     */
    public function reletClose(){
        $this->data;
    }

    /**
     * 订单续租完成
     *
     * 1.验证订单是否冻结
     * 2.解冻订单
     */
    public function reletFinish(){
        $this->data;
    }

    /**
     * 验证订单是否冻结
     *
     * @return bool false冻结,ture未冻结
     */
    public function nonFreeze():bool {
        if($this->model->freeze_type==OrderFreezeStatus::Non){
            return true;
        }else{
            return false;
        }
    }

	
	/**
	 * 获取订单
	 * <p>当订单不存在时，抛出异常</p>
	 * @param string $order_no		订单编号
	 * @param int		$lock			锁
	 * @return \App\Order\Modules\Repository\Order\Order
	 * @return  bool
	 */
	public static function getByNo( string $order_no, int $lock=0 ) {
        $builder = \App\Order\Models\Order::where([
            ['order_no', '=', $order_no],
        ])->limit(1);
		if( $lock ){
			$builder->lockForUpdate();
		}
		$order_info = $builder->first();
		if( !$order_info ){
			return false;
		}
		return new self( $order_info );
	}
}
