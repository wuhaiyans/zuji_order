<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderGivebackRepository;
use App\Order\Modules\Inc\OrderGivebackStatus;
use Illuminate\Support\Facades\DB;
use App\Order\Modules\Repository\Order\Goods;
use App\Lib\ApiStatus;

class OrderGiveback
{
	/**
	 * 订单还机数据处理仓库
	 * @var obj
	 */
	protected $order_giveback_repository;
	public function __construct(  ) {
		$this->order_giveback_repository = new OrderGivebackRepository();
	}
    /**
     * 保存还机单数据
     * @param $data
     * @return id
     */
    public function create($data){
		$data = filter_array($data, [
			'order_no' => 'required',//订单编号
			'goods_no' => 'required',//商品编号
			'user_id' => 'required',//用户id
			'logistics_id' => 'required',//物流类型
			'logistics_name' => 'required',//物流名称
			'logistics_no' => 'required',//物流单号
			'giveback_no' => 'required',//还机单编号
			'status' => 'required',//订单状态
		]);

		if( count($data)!=8 ){
			set_apistatus(\App\Lib\ApiStatus::CODE_92100, '还机单创建：必要参数缺失!');
			return false;
		}
		$data['create_time'] = $data['update_time'] = time();
        $result = $this->order_giveback_repository->create( $data );
		if( !$result ) {
			set_code(\App\Lib\ApiStatus::CODE_92201);
		}
		return $result;
    }
    /**
     * 获取当前条件下，是否有需要支付的还机单
	 * @param where $where 查询条件
	 * $where = [
	 *		'order_no' => '',//订单编号
	 *		'giveback_no' => '',//还机单编号
	 *		'goods_no' => '',//设备编号
	 * ]
	 * @return array|false
	 */
	public static function getNeedpayInfo( $where ) {
		$where = filter_array($where, [
			'order_no' => 'required',
			'giveback_no' => 'required',
			'goods_no' => 'required',
		]);
		if( empty($where) ) {
			set_apistatus(\App\Lib\ApiStatus::CODE_92300,'获取单条还机单数据时，参数为空!');
			return false;
		}
		$where['payment_status'] = [OrderGivebackStatus::PAYMENT_STATUS_IN_PAY, OrderGivebackStatus::PAYMENT_STATUS_NOT_PAY];
		$order_giveback_repository = new OrderGivebackRepository();
		return $order_giveback_repository->getNeedpayInfo($where);
	}
    /**
     * 根据商品编号获取一条还机单数据
	 * @param string $goodsNo 商品编号
	 * @return array|false
	 */
	public function getInfoByGoodsNo( $goodsNo ) {
		if( empty($goodsNo) ) {
			set_apistatus(\App\Lib\ApiStatus::CODE_92300,'获取还机单数据时订单编号参数为空!');
			return false;
		}
		return $this->order_giveback_repository->getInfoByGoodsNo($goodsNo);
	}
	
    /**
     * 验证当前设备是否有处于还机状态
	 * @param string $goodsNo 商品编号
	 * @return array|false
	 */
	public static function verifyGoodsNo( $goodsNo, $userId ) {
		//初始化数据
		$result = [
			'is_have' => false,//是否有还机单
			'giveback_no' => '',//还机单编号
		];
		if( empty($goodsNo) || empty($userId) ) {
			return $result;
		}
		$order_giveback_repository = new OrderGivebackRepository();
		$givebackInfo = $order_giveback_repository->getInfoByGoodsNo($goodsNo);
		if( empty( $givebackInfo ) || $givebackInfo['user_id'] != $userId ){
			return $result;
		}
		$result['giveback_no'] = $givebackInfo['giveback_no'];
		return $result;
	}
    /**
     * 根据还机编号获取一条还机单数据
	 * @param string $givebackNo 还机编号
	 * @return array|false
	 */
	public function getInfoByGivabackNo( $givebackNo ) {
		if( empty($givebackNo) ) {
			set_apistatus(\App\Lib\ApiStatus::CODE_92300,'获取还机单数据时还机单编号参数为空!');
			return false;
		}
		return $this->order_giveback_repository->getInfoByGivabackNo($givebackNo);
	}
    /**
     * 获取当前订单下所有未完成的还机单
	 * @param string $orderNo 订单编号
	 * @return array|false
	 */
	public function getUnfinishedListByOrderNo( $orderNo ) {
		if( empty($orderNo) ) {
			set_apistatus(\App\Lib\ApiStatus::CODE_92300,'获取未完成的还机单列表：订单编号参数为空!');
			return false;
		}
		return $this->order_giveback_repository->getUnfinishedListByOrderNo( $orderNo );
	}
	/**
	 * 获取还机单列表
	 * @param array $where 查询条件
	 * $where = [<br\>
	 *     'begin_time' => '',//还机单创建的开始时间<br\>
	 *     'end_time' => '',//还机单创建的结束时间<br\>
	 *     'status' => '',//还机单状态<br\>
	 *     'kw_type' => '',//搜索关键字类型<br\>
	 *     'keywords' => '',//搜索关键字<br\>
	 * ]<br\>
	 * @param array $additional 附加条件
	 * $additonal = [<br\>
	 *		'page' => '',//页数<br\>
	 *		'size' => '',//每页大小<br\>
	 * ]<br\>
	 * @return array $result 返回的数据
	 * $result = [<br\>
	 *		'current_page'=>'',//当前页<br\>
	 *		'first_page_url'=>'',//第一页url<br\>
	 *		'from'=>'',//从第几条数据<br\>
	 *		'last_page'=>'',//最终页<br\>
	 *		'last_page_url'=>'',//最终页url<br\>
	 *		'next_page_url'=>'',//下一页<br\>
	 *		'path'=>'',//当前域名路径<br\>
	 *		'per_page'=>'',//每页大小<br\>
	 *		'prev_page_url'=>'',//上一页<br\>
	 *		'to'=>'',//到第几条数据<br\>
	 *		'total'=>'',//一共几条数据<br\>
	 *      'data' => [ //数据详情<br\>
	 *			'id' => ''//主键id<br\>
	 *			'giveback_no' => ''//还机编号（业务编号）<br\>
	 *			'order_no' => ''//订单编号<br\>
	 *			'goods_no' => ''//商品编号<br\>
	 *			'goods_name' => ''//设备名称<br\>
	 *			'amount_after_discount' => ''//设备优惠后总金额<br\>
	 *			'zuqi' => ''//商品租期<br\>
	 *			'zuqi_type' => ''//商品租期类型<br\>
	 *			'user_id' => ''//用户id<br\>
	 *			'username' => ''//用户名<br\>
	 *			'mobile' => ''//用户手机号<br\>
	 *			'status' => ''//状态值<br\>
	 *			'status_name' => ''//状态名称<br\>
	 *			'instalment_num' => ''//剩余分期数<br\>
	 *			'instalment_amount' => ''//剩余分期支付金额<br\>
	 *			'payment_status' => ''//支付状态值<br\>
	 *			'payment_status_name' => ''//支付状态名称<br\>
	 *			'payment_time' => ''//支付时间<br\>
	 *			'logistics_id' => ''//物流id<br\>
	 *			'logistics_name' => ''//物流名称<br\>
	 *			'logistics_no' => ''//物流编号<br\>
	 *			'evaluation_status' => ''//检测状态值<br\>
	 *			'evaluation_status_name' => ''//检测状态名称<br\>
	 *			'evaluation_remark' => ''//检测备注<br\>
	 *			'evaluation_time' => ''//检测时间<br\>
	 *			'yajin_status' => ''//押金状态值<br\>
	 *			'yajin_status_name' => ''//押金状态名称<br\>
	 *			'compensate_amount' => ''//赔偿金额<br\>
	 *			'create_time' => ''//创建时间<br\>
	 *			'update_time' => ''//最后更新时间<br\>
	 *			'remark' => ''//备注<br\>
	 *      ]
	 * ]
	 */
	public function getList( $where = [], $additional = [] ) {
		$this->__parseWhere( $where );
		$this->__parseAddition( $additional );
        $orderList = DB::table('order_giveback')
            ->leftJoin('order_goods', 'order_goods.goods_no', '=', 'order_giveback.goods_no')
            ->leftJoin('order_info','order_info.order_no', '=', 'order_goods.order_no')
            ->where($where)
            ->select('order_giveback.*','order_goods.goods_name','order_goods.amount_after_discount','order_goods.zuqi_type','order_goods.zuqi','order_info.mobile')
//			paginate: 参数
//			perPage:表示每页显示的条目数量
//			columns:接收数组，可以向数组里传输字段，可以添加多个字段用来查询显示每一个条目的结果
//			pageName:表示在返回链接的时候的参数的前缀名称，在使用控制器模式接收参数的时候会用到
//			page:表示查询第几页及查询页码
            ->paginate($additional['size'],['*'], 'p', $additional['page']);
		$orderList = json_decode(json_encode($orderList),true);
		if( $orderList ){
			foreach ($orderList['data'] as  &$value) {
				$value['username'] = $value['mobile'];
				$value['status_name'] = OrderGivebackStatus::getStatusName($value['status']);
				$value['payment_status_name'] = OrderGivebackStatus::getPaymentStatusName($value['payment_status']);
				$value['evaluation_status_name'] = OrderGivebackStatus::getEvaluationStatusName($value['evaluation_status']);
				$value['yajin_status_name'] = OrderGivebackStatus::getYajinStatusName($value['yajin_status']);
				$value['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
				$value['evaluation_time'] = date('Y-m-d H:i:s',$value['evaluation_time']);
				$value['update_time'] = date('Y-m-d H:i:s',$value['update_time']);
				$value['payment_time'] = date('Y-m-d H:i:s',$value['payment_time']);
			}
		}
        return $orderList;
	}
	
    /**
     * 根据条件更新数据
	 * @param array $where 更新条件【至少含有一项条件】
	 * $where = [<br/>
	 *		'goods_no' => '',//商品编号<br/>
	 * ]<br/>
	 * @param array $data 需要更新的数据 【至少含有一项数据】
	 * $data = [<br/>
	 *		'status'=>'',//还机状态<br/>
	 *		'withhold_status'=>'',//代扣状态<br/>
	 *		'instalment_num'=>'',//剩余还款的分期数<br/>
	 *		'instalment_amount'=>'',//剩余还款的分期总金额（分）<br/>
	 *		'payment_status'=>'',//支付状态 0默认<br/>
	 *		'payment_time'=>'',//支付时间<br/>
	 *		'logistics_id'=>'',//物流类型<br/>
	 *		'logistics_name'=>'',//物流名称<br/>
	 *		'logistics_no'=>'',//物流编号<br/>
	 *		'evaluation_status'=>'',//检测结果<br/>
	 *		'evaluation_remark'=>'',//检测备注<br/>
	 *		'evaluation_time'=>'',//检测时间<br/>
	 *		'yajin_status'=>'',//押金退还状态<br/>
	 *		'compensate_amount'=>'',//赔偿金额<br/>
	 *		'remark'=>'',//备注<br/>
	 * ]
	 */
	public function update( $where, $data ) {
		$where = filter_array($where, [
			'goods_no' => 'required',
			'giveback_no' => 'required',
		]);
		$data = filter_array($data, [
			'status' => 'required',
			'withhold_status' => 'required',
			'instalment_num' => 'required',
			'instalment_amount' => 'required',
			'payment_status' => 'required',
			'payment_time' => 'required',
			'logistics_id' => 'required',
			'logistics_name' => 'required',
			'logistics_no' => 'required',
			'evaluation_status' => 'required',
			'evaluation_remark' => 'required',
			'evaluation_time' => 'required',
			'compensate_amount' => 'required',
			'remark' => 'required',
		]);
		if( count( $where ) < 1 ){
			set_apistatus(\App\Lib\ApiStatus::CODE_92600,'还机单修改：条件参数为空');
			return false;
		}
		if( count( $data ) < 1 ){
			set_apistatus(\App\Lib\ApiStatus::CODE_92600,'还机单修改：数据参数为空');
			return false;
		}
		$data['update_time'] = time();
		return $this->order_giveback_repository->update( $where, $data );
	}
	/**
	 * 还机单清算完成回调接口
	 * @param array $params 还机单清算完成回调参数<br/>
	 * $params = [
	 *		'business_type' => '',//业务类型【必须是还机业务】
	 *		'business_no' => '',//业务编码【必须是还机单编码】
	 *		'status' => '',//支付状态  processing：处理中；success：支付完成
	 * ]
	 */
	public static function callbackClearing( $params ) {
		try{
			//参数过滤
			$rules = [
				'business_type'     => 'required',//业务类型
				'business_no'     => 'required',//业务编码
				'status'     => 'required',//支付状态
			];
			$validator = app('validator')->make($params, $rules);
			if ($validator->fails()) {
				set_apistatus(ApiStatus::CODE_91000, $validator->errors()->first());
				\App\Lib\Common\LogApi::debug('【还机清算回调】参数有误', ['$params'=>$params]);
				return false;
			}
			//清算成功
			if( $params['status'] != 'success' || $params['business_type'] != \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK ){
				set_apistatus(ApiStatus::CODE_91000, '状态值或业务类型有误!');
				\App\Lib\Common\LogApi::debug('【还机清算回调】状态值或业务类型有误', ['$params'=>$params]);
				return false;
			}

			//创建服务层对象
			$orderGivebackService = new OrderGiveback();
			//获取还机单信息
			$orderGivebackInfo = $orderGivebackService->getInfoByGivabackNo($params['business_no']);
			if( !$orderGivebackInfo ) {			
				set_msg('还机单信息获取失败');
				\App\Lib\Common\LogApi::debug('【还机清算回调】还机单信息获取失败', ['$params'=>$params,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
		
		//-+--------------------------------------------------------------------
		// | 更新订单状态（交易完成）
		//-+--------------------------------------------------------------------
			$orderGivebackResult = $orderGivebackService->update(['giveback_no'=>$params['business_no']], [
				'status'=> OrderGivebackStatus::STATUS_DEAL_DONE,
				'yajin_status'=> OrderGivebackStatus::YAJIN_STATUS_RETURN_COMOLETION,
			]);
			if( !$orderGivebackResult ){
				set_msg('还机单状态更新失败');
				\App\Lib\Common\LogApi::debug('【还机清算回调】还机单状态更新失败', ['$orderGivebackResult'=>$orderGivebackResult,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//解冻订单
			if(!self::__unfreeze($orderGivebackInfo['order_no'])){
				\App\Lib\Common\LogApi::debug('【还机清算回调】订单解冻失败', ['$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//更新商品表状态
			$orderGoods = Goods::getByGoodsNo($orderGivebackInfo['goods_no']);
			if( !$orderGoods ){
				set_msg('商品仓库获取失败');
				\App\Lib\Common\LogApi::debug('【还机清算回调】商品仓库更新状态失败', ['$orderGoods'=>$orderGoods,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			$orderGoodsResult = $orderGoods->givebackFinish();
			if( !$orderGoodsResult ){
				set_msg('商品仓库更新状态失败');
				\App\Lib\Common\LogApi::debug('【还机清算回调】商品仓库更新状态失败', ['$orderGoodsResult'=>$orderGoodsResult,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//记录日志
			$goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
				'order_no'=>$orderGivebackInfo['order_no'],
				'action'=>'还机单清算',
				'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//此处用常量
				'business_no'=>$orderGivebackInfo['giveback_no'],
				'goods_no'=>$orderGivebackInfo['goods_no'],
				'operator_id'=>0,
				'operator_name'=>'支付回调',
				'operator_type'=>\App\Lib\PublicInc::Type_System,//此处用常量
				'msg'=>'还机单支付完成',
			]);
			if( !$goodsLog ){
				set_apistatus(ApiStatus::CODE_92700, '设备日志记录失败!');
				\App\Lib\Common\LogApi::debug('【还机清算回调】设备日志记录失败', ['$goodsLog'=>$goodsLog,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
		} catch (\Exception $ex) {
			\App\Lib\Common\LogApi::debug('还机单清算回调错误', $ex);
			set_msg($ex->getMessage());
			return false;
		}
		return true;
	}
	
	/**
	 * 还机单支付完成回调接口
	 * @param Request $request
	 */
	public static function callbackPayment( $params ) {
		try{
			//参数过滤
			$rules = [
				'business_type'     => 'required',//业务类型
				'business_no'     => 'required',//业务编码
				'status'     => 'required',//支付状态
			];
			$validator = app('validator')->make($params, $rules);
			if ($validator->fails()) {
				set_apistatus(ApiStatus::CODE_91000, $validator->errors()->first());
				\App\Lib\Common\LogApi::debug('【还机支付回调】参数错误', ['$params'=>$params]);
				return false;
			}
			//清算成功
			if( $params['status'] != 'success' || $params['business_type'] != \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK ){
				set_apistatus(ApiStatus::CODE_91000, '状态值或业务类型有误!');
				\App\Lib\Common\LogApi::debug('【还机支付回调】状态值或业务类型有误', ['$params'=>$params]);
				return false;
			}
			$orderGivebackService = new OrderGiveback();
			//获取还机单信息
			$orderGivebackInfo = $orderGivebackService->getInfoByGivabackNo($params['business_no']);
			if( !$orderGivebackInfo ) {
				\App\Lib\Common\LogApi::debug('【还机支付回调】还机单信息获取失败', ['$params'=>$params,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//创建服务层对象
			$orderGoods = Goods::getByGoodsNo($orderGivebackInfo['goods_no']);
			if( !$orderGoods ){
				\App\Lib\Common\LogApi::debug('【还机支付回调】商品服务层创建失败', ['$orderGoods'=>$orderGoods,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//获取商品信息
			$orderGoodsInfo = $orderGoods->getData();
			if( !$orderGoodsInfo ) {
				\App\Lib\Common\LogApi::debug('【还机支付回调】商品信息获取失败', ['$orderGoodsInfo'=>$orderGoodsInfo,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//-+--------------------------------------------------------------------
			// | 判断订单押金，是否生成清算单
			//-+--------------------------------------------------------------------

			//-+--------------------------------------------------------------------
			// | 不生成=》更新订单状态（交易完成）
			//-+--------------------------------------------------------------------
			if( $orderGoodsInfo['yajin'] == 0 ){
				$status = OrderGivebackStatus::STATUS_DEAL_DONE;
				$orderGivebackResult = $orderGivebackService->update(['giveback_no'=>$params['business_no']], [
					'status'=> $status,
					'yajin_status'=> OrderGivebackStatus::YAJIN_STATUS_NO_NEED_RETURN,
					'payment_status'=> OrderGivebackStatus::PAYMENT_STATUS_ALREADY_PAY,
					'payment_time'=> time(),
				]);
				
				//解冻订单
				if(!self::__unfreeze($orderGoodsInfo['order_no'])){
					\App\Lib\Common\LogApi::debug('【还机支付回调】订单解冻失败', ['$orderGivebackResult'=>$orderGivebackResult,'$orderGivebackInfo'=>$orderGivebackInfo]);
					return false;
				}
				//需要记录清算，清算数据为空即可
				$paymentNo = $fundauthNo = '';
			}
			//-+--------------------------------------------------------------------
			// | 生成=>更新订单状态（处理中，待清算）
			//-+--------------------------------------------------------------------
			else{
				$status = OrderGivebackStatus::STATUS_DEAL_WAIT_RETURN_DEPOSTI;
				$orderGivebackResult = $orderGivebackService->update(['giveback_no'=>$params['business_no']], [
					'status'=> $status,
					'yajin_status'=> OrderGivebackStatus::YAJIN_STATUS_IN_RETURN,
					'payment_status'=> OrderGivebackStatus::PAYMENT_STATUS_ALREADY_PAY,
					'payment_time'=> time(),
				]);
				//获取当时订单支付时的相关pay的对象信息【查询payment_no和funath_no】
				$payObj = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,$orderGoodsInfo['order_no'] );
				$paymentNo = $payObj->getPaymentNo();
				$fundauthNo = $payObj->getFundauthNo();
			}
			if( !$orderGivebackResult ){
				\App\Lib\Common\LogApi::debug('【还机支付回调】还机单状态更新失败', ['$orderGivebackResult'=>$orderGivebackResult,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//清算处理数据拼接
			$clearData = [
				'user_id' => $orderGivebackInfo['user_id'],
				'order_no' => $orderGivebackInfo['order_no'],
				'business_type' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
				'business_no' => $orderGivebackInfo['giveback_no'],
				'auth_deduction_amount' => 0,//扣除押金金额
				'auth_unfreeze_amount' => $orderGoodsInfo['yajin'],//退还押金金额
				'out_payment_no' => $paymentNo,//payment_no
				'out_auth_no' => $fundauthNo,//和funath_no
			];
			//进入清算处理
			$orderCleanResult = \App\Order\Modules\Service\OrderCleaning::createOrderClean($clearData);
			if( !$orderCleanResult ){
				set_apistatus(ApiStatus::CODE_93200, '押金退还清算单创建失败!');
				\App\Lib\Common\LogApi::debug('【还机支付回调】押金退还清算单创建失败', ['$orderCleanResult'=>$orderCleanResult,'$clearData'=>$clearData,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			
			//更新商品表状态
			if( $status == OrderGivebackStatus::STATUS_DEAL_DONE ){
				$orderGoodsResult = $orderGoods->givebackFinish();
				if(!$orderGoodsResult){
					\App\Lib\Common\LogApi::debug('【还机支付回调】更新商品表状态失败', ['$orderGoodsResult'=>$orderGoodsResult,'$orderGivebackInfo'=>$orderGivebackInfo]);
					return false;
				}
			}
			//记录日志
			$goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
				'order_no'=>$orderGivebackInfo['order_no'],
				'action'=>'还机单支付',
				'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//此处用常量
				'business_no'=>$orderGivebackInfo['giveback_no'],
				'goods_no'=>$orderGivebackInfo['goods_no'],
				'operator_id'=>0,
				'operator_name'=>'支付回调',
				'operator_type'=>\App\Lib\PublicInc::Type_System,//此处用常量
				'msg'=>'还机单支付完成',
			]);
			if( !$goodsLog ){
				set_apistatus(ApiStatus::CODE_92700, '设备日志记录失败!');
				\App\Lib\Common\LogApi::debug('【还机支付回调】设备日志记录失败', ['$goodsLog'=>$goodsLog,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
		} catch (\Exception $ex) {
			\App\Lib\Common\LogApi::debug('【还机支付回调】异常', $ex);
			return false;
		}
		return true;
	}
	public static function __unfreeze($orderNo) {
		$orderGivebackService = new OrderGiveback();
		//解冻订单
		//查询当前订单处于还机未结束的订单数量（大于1则不能解冻订单）
		$givebackUnfinshedList = $orderGivebackService->getUnfinishedListByOrderNo($orderNo);
		if( count($givebackUnfinshedList) != 1 ){
			return true;
		} 
		$orderFreezeResult = \App\Order\Modules\Repository\OrderRepository::orderFreezeUpdate($orderNo, \App\Order\Modules\Inc\OrderFreezeStatus::Non);
		if( !$orderFreezeResult ){
			set_apistatus(ApiStatus::CODE_92700, '订单解冻失败!');
			return false;
		}
		//解冻成功，调用订单是否完成接口
		$orderComplete = OrderOperate::isOrderComplete($orderNo);
		if( !$orderComplete ){
			set_apistatus(ApiStatus::CODE_92700, '订单关闭失败!');
			return false;
		}
		return true;
	}
	private function __parseWhere( &$where ) {
		$whereArray = [];
        //根据还机单状态
        if (isset($where['status']) && $where['status']!= OrderGivebackStatus::STATUS_ALL) {
            $whereArray[] = ['order_giveback.status', '=', $where['status']];
        }

        //应用来源ID
        if (isset($where['order_appid']) && !empty($where['order_appid'])) {
            $whereArray[] = ['order_info.appid', '=', $where['order_appid']];
        }
        //还机单创建开始时间
        if ( isset($where['begin_time']) && !empty($where['begin_time']) ) {
            $whereArray[] = ['order_giveback.create_time', '>=', strtotime($where['begin_time'])];
        }
        //还机单创建结束时间
        if ( isset($where['end_time']) && !empty($where['end_time'])) {
            $whereArray[] = ['order_giveback.create_time', '<=', strtotime($where['end_time'])];
        }

        //根据订单编号
        if (isset($where['kw_type']) && $where['kw_type'] == OrderGivebackRepository::KWTYPE_ORDERNO && !empty($where['keywords']) ) {
            $whereArray[] = ['order_giveback.order_no', '=', $where['keywords']];
        }
        //根手机号
        if (isset($where['kw_type']) && $where['kw_type'] == OrderGivebackRepository::KWTYPE_MOBILE && !empty($where['keywords']) ) {
            $whereArray[] = ['order_info.mobile', '=', $where['keywords']];
        }
        //根据设备名称
        if (isset($where['kw_type']) && $where['kw_type'] == OrderGivebackRepository::KWTYPE_GOODSNAME && !empty($where['keywords']) ) {
            $whereArray[] = ['order_goods.goods_name', 'like', $where['keywords'].'%'];
        }
		$where = $whereArray;
		return true;
	}
	private function __parseAddition( &$addition ) {
		$addition['page'] = isset($addition['page']) && $addition['page'] ? $addition['page'] : 1;
		$addition['size'] = isset($addition['size']) && $addition['size'] ? max($addition['size'],20) : 20;
		return true;
	}
}
