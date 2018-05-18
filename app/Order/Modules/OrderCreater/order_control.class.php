<?php

use zuji\order\Order;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\Config;
use zuji\order\RefundStatus;
use zuji\order\delivery\Delivery;
use zuji\Time;
use zuji\order\Service;
use zuji\email\EmailConfig;
use zuji\cache\Redis;
use zuji\jd\BaiTiao;

hd_core::load_class('api', 'api');
/**
 * 订单控制器
 * @access public
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class order_control extends user_control {

    protected $member = array();

    public function _initialize() {
        parent::_initialize();
        $this->userId = $this->member['id'];
    }
    //记录状态流及记录操作日志
    public function log($orderObj,$title,$text=""){
        // 当前操作员
        $Operator = new oms\operator\User( $this->member['id'], $this->member['username'] );
        // 订单 观察者主题
        $OrderObservable = $orderObj->get_observable();
        // 订单 观察者 状态流
        $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
        // 订单 观察者  日志
        $LogObserver = new oms\observer\LogObserver( $OrderObservable , $title, $text);
        //插入日志
        $LogObserver->set_operator($Operator);
    }
    public function plat_log($orderObj,$title,$text=""){
        // 当前操作员
        $Operator = new oms\operator\User( $this->member['id'], $this->member['username'] );
        // 订单 观察者主题
        $OrderObservable = $orderObj->get_observable();
        // 订单 观察者  日志
        $LogObserver = new oms\observer\LogObserver( $OrderObservable , $title, $text);
        //插入日志
        $LogObserver->set_operator($Operator);
    }
    //发送邮件
    public function send_email($title,$body){
        $data =[
            'subject'=>$title,
            'body'=>$body,
            'address'=>[
                ['address' => EmailConfig::Service_Username]
            ],
        ];
        $send =EmailConfig::system_send_email($data);
        if(!$send){
            Debug::error(Location::L_Return, "发送邮件失败", $data);
        }
    }

    /**
     * 订单操作状态列表
     * @param array $order
     * @return array
     */
    public function get_root(array $order,array $operation): array{

        $root = [
            //支付
            'payment' => false,
            //可取消
            'cancel' => false,
            //不可取消提示
            'cancel_msg' => false,
            //审核通过
            'passed' => false,
            //审核拒绝
            'no_passed'=>false,
            //确认收货
            'confirm' => false,
            //退货
            'return' => false,
            //物流查询
            'logistics' => false,
            //服务结束
            'service' => false
        ];
        switch($order['status']){
            //已下单
            case \oms\state\State::OrderCreated:
                $root['cancel'] = $operation['cancel'];
                if($order['business_key'] == zuji\Business::BUSINESS_ZUJI)
                {
                    $root['payment'] = $order['payment_type_id'] == \zuji\Config::WithhodingPay?true:$operation['payment'];
                }
                break;
            //已取消
            case \oms\state\State::OrderCanceled:
                break;
            //订单关闭
            case \oms\state\State::OrderClosed:
                break;
            //租用中
            case \oms\state\State::OrderInService:
                $root['return'] = $operation['return'];
                break;
            //审核通过
            case \oms\state\State::StorePassed:
                $root['passed'] = true;
                break;
            //审核拒绝
            case \oms\state\State::StoreRefuse:
                $root['no_passed'] = true;
                break;
            //门店确认订单
            case \oms\state\State::StoreConfirmed:
                if($order['business_key'] == zuji\Business::BUSINESS_STORE){
                    $root['cancel'] = $operation['cancel'];
                    $root['payment'] = $order['payment_type_id'] == \zuji\Config::WithhodingPay?true:$operation['payment'];
                }
                break;
            //已支付
            case \oms\state\State::PaymentSuccess:
                $root['cancel_msg'] = $order['business_key'] == zuji\Business::BUSINESS_STORE?false:true;
                break;
            //线上确认订单
            case \oms\state\State::OrderConfirmed:
                break;
            //退款中
            case \oms\state\State::OrderRefunding:
                break;
            //已退款
            case \oms\state\State::OrderRefunded:
                break;
            //已发货
            case \oms\state\State::OrderDeliveryed:
                $root['logistics'] = true;
                $root['confirm'] = $operation['delivery'];
                break;
            //用户拒签
            case \oms\state\State::OrderRefused:
                $root['logistics'] = true;
                break;
            //退货审核中
            case \oms\state\State::OrderReturnChecking:
                $root['return'] = true;
                break;
            //退货中
            case \oms\state\State::OrderReturning:
                $root['return'] = true;
                break;
            //平台已收货
            case \oms\state\State::OrderReceived:
                $root['return'] = true;
                break;
            //检测合格
            case \oms\state\State::OrderEvaluationQualified:
                $root['return'] = true;
                break;
            //检测不合格
            case \oms\state\State::OrderEvaluationUnqualified:
                $root['return'] = true;
                break;
        }
        return $root;
    }
    //订单列表查询
    public function query(){
        $params   = $this->params;
        //所有订单
        if($params['status'] == "ALL"){
            $where = [];
        }
        //待支付订单
        elseif($params['status'] == "PAY"){
            $where['status'] = oms\state\State::OrderCreated;
            $where['payment_status'] = [zuji\order\PaymentStatus::PaymentInvalid,zuji\order\PaymentStatus::PaymentCreated,zuji\order\PaymentStatus::PaymentWaiting,zuji\order\PaymentStatus::PaymentPaying ];
        }
        //待收货订单
        elseif($params['status'] == "RECEIVE"){
            $where['order_status'] = zuji\order\OrderStatus::OrderCreated;
            $where['delivery_status'] = zuji\order\DeliveryStatus::DeliverySend;
        }
        //售后服务
        elseif($params['status'] == "AFTER"){
            $where['order_status'] = zuji\order\OrderStatus::OrderCreated;
        }
        else{
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Order_Error_Status,'');
            return;
        }
        //特殊appid订单显示处理
        $appid = api_request()->getAppid();
        if(in_array($appid,config("APPID_SPECIAL"))){
            $where['appid'] = $appid;
        }
        else{
            $where['appid'] = ['not in',implode(config("APPID_SPECIAL"),",")];
        }
        //分页
        $data = array(
            'order_list'    =>  '',
            'count'          =>  ''
        );

        /*****************依赖服务************/
        $this->order   = $this->load->service('order2/order');
        $this->delivery  = $this->load->service('order2/delivery');
        $this->service_serve  = $this->load->service('order2/service');
        $this->spu_serve    = $this->load->service('goods2/goods_spu');
        $this->instalment_service   = $this->load->service('order2/instalment');
        $this->zhima_certification_service =$this->load->service('order2/zhima_certification');
        $where['user_id'] = $this->userId ;
        //获取订单数
        $count = $this->order->get_order_count($where);
        if(!$count){
            api_resopnse( $data, ApiStatus::CODE_0 );
        }
        //选择性分页
        if($params['page']>0){
            $options['size']   = 10;
            $options['page'] = intval($params['page']);
        }
        else
        {
            $options['size']   = $count;
            $options['page']  = 1;
        }
        $data['count'] = $count;
        $options['goods_info'] = true;
        $total_page = ceil($data['count']/$options['size']);
        $data['total_page'] = $total_page;
        //获取订单数据列表
        $order_list = $this->order ->get_order_list($where,$options);
        $order_list = $this->arrayKey($order_list,"order_id");
        if(!$order_list){
            api_resopnse( [], ApiStatus::CODE_50003 ,'订单获取失败');
        }
        $order_ids = array_column($order_list,'order_id');
        asort($order_ids);
        $additional['size'] = count($order_ids);
        $additional['page']  = 1;

        //获取商品获取缩略图
        $goods_info =array_column($order_list, 'goods_info');
        $spu_ids = array_column($goods_info,'spu_id');
        $spu_where['id'] = ['in',implode(',',$spu_ids)];
        $spu_goods = $this->spu_serve->api_get_list($spu_where,'id,thumb,imgs');
        $spu_goods = $this->arrayKey($spu_goods,"id");

        //发货单id集
        $delivery_ids = [];
        //服务单id集
        $service_ids = [];

        //组装数据格式
        $order_new = array();

        foreach($order_list as $key=>$val){
            //商品规格解析
            $spu_id = $val['goods_info']['spu_id'];
            $specs_list = $val['goods_info']['specs'];
            $specs_value  = array_column($specs_list,"value");
            $specs = implode("\n",$specs_value);
            //订单状态匹配
            $orderObj = new oms\Order($val);
            $status_name = $orderObj->get_client_name();
            $root = $orderObj->get_client_operations($val);
            $order_root = $this->get_root($val,$root);
            $return = $order_root['return'];
            if($val['status'] == oms\state\State::OrderInService){
                $return = $val['business_key']==zuji\Business::BUSINESS_STORE?true:$return;
            }

            //收集服务状态下服务单id
            if($val['status'] == oms\state\State::OrderInService){
                array_push($service_ids,$val['service_id']);
            }
            //收集发货状态下发货单id
            if($order_root['logistics']){
                array_push($delivery_ids,$val['delivery_id']);
            }
            //是否有未支付的激活分期
            $unpaid_instalment =$this->instalment_service->get_order_instalment_unpaid($val['order_id']);

            $prePay_btn = 0;

            if(count($unpaid_instalment) >0 && $val['status'] == oms\state\State::OrderInService){
                $prePay_btn = 1;
            }
            //获取订单渠道类型
            $this->channel_appid = $this->load->service('channel/channel_appid');
            $appid_info = $this->channel_appid->get_info($val['appid']);

            //获取订单的芝麻订单编号
            $zhima_order_info = $this->zhima_certification_service->where(['out_order_no'=>$val['order_no']])->find();
            $zhima_order_no ="";
            if($zhima_order_info){
                $zhima_order_no = $zhima_order_info['order_no'];
            }
            if($val['zuqi_type']==1){
                $zuqi_type = "day";
            }
            elseif($val['zuqi_type']==2){
                $zuqi_type = "month";
            }
            $order_new[$key] = array(
                'create_time'  =>  date("Y-m-d H:i:s",$val['create_time']),
                'status'  => $val['status'],
                'status_name' => $status_name,
                'business_key' => $val['business_key'],
                'app_id'       => $val['appid'],
                'order_id'      => $val['order_id'],
                'order_no'      => $val['order_no'],
                'amount'        => $val['amount'],
                'mobile'        => $val['mobile'],
                'yajin'          => $val['yajin'],
                'mianyajin'     => $val['mianyajin'],
                'zujin'          => $val['zujin'],
                'yiwaixian'      => $val['yiwaixian'],
                'zuqi'           => $val['zuqi'],
                'zuqi_type'      => $zuqi_type,
                'appid_type'    => $appid_info['appid']['type'],
                'zhima_order_no' =>$zhima_order_no,
                'payment_type_id'=>$val['payment_type_id'],
                'sku_info'       => array(
                    'sku_id'      =>  $val['goods_info']['sku_id'],
                    'sku_name'   =>  $val['goods_info']['sku_name'],
                    'thumb'       =>  $spu_goods[$spu_id]['thumb'],
                    'imgs'       =>  json_decode($spu_goods[$spu_id]['imgs'],true),
                    'specs'       =>  $specs
                ),
                /*前端操作按钮状态定义*/
                'payment_btn' => $order_root['payment'],
                'cancel_btn' => $order_root['cancel'],
                'cancel_alert_btn' => $order_root['cancel_msg'],
                'confirm_btn' => $order_root['confirm'],
                'return_btn' => $return,
                'logistics_btn' => $order_root['logistics'],
                'service_btn' => $order_root['service'],
                'need_to_fund_auth' => 'Y' ,
                'prePay_btn'=>$prePay_btn,
            );
        }
        //租机服务单
        $service_list = [];
        if(!empty($delivery_ids)){
            $service_where['service_id'] = $service_ids;
            $service_list = $this->service_serve->get_list($service_where,$additional);
            $service_list = $this->arrayKey($service_list,"order_id");
        }
        //发货单
        $delivery_list = [];
        if(!empty($delivery_ids)){
            $delivery_where['delivery_id'] = $delivery_ids;
            $delivery_list = $this->delivery->get_list($delivery_where,$additional);
            $delivery_list = $this->arrayKey($delivery_list,"order_id");
        }

        foreach($order_new as &$val){

            //发货单
            $delivery = $delivery_list[$val['order_id']];
            //物流单号
            $val['wuliu_no']   = $delivery['wuliu_no']?$delivery['wuliu_no']:"";

            //服务单
            $service = $service_list[$val['order_id']];
            if(!empty($service) && $val['zuqi_type']==2){
                //计算是否到期
                $Service = Service::createService( $service );
                $days = $Service->get_remaining_days();
                if( $days<=7 && $days>0 ){
                    $val['status_name'] = "即将到期";
                }elseif($days<=0){
                    $val['status_name'] = "已到期";
                }
            }
        }
        array_multisort($order_new,SORT_DESC);
        $data['order_list'] = $order_new;
        api_resopnse( $data, ApiStatus::CODE_0 );
        return;
    }
    //订单支付状态查询
    public function pay_status(){
        $params   = $this->params;
        $params = filter_array($params,[
            'order_no'=>'required',
        ]);
        if(!$params['order_no']){
            api_resopnse( [], ApiStatus::CODE_20001 ,'order_no必须');
            return;
        }
        //加载订单服务
        $this->order   = $this->load->service('order2/order');
        //设置查询条件
        $where['order_no'] = $params['order_no'];
        //查询一条订单
        $order = $this->order->get_order_info($where);
        if(!$order){
            api_resopnse( [], ApiStatus::CODE_50003 ,'订单获取失败');
            return;
        }
        if($order['payment_status'] != zuji\order\PaymentStatus::PaymentSuccessful){
            api_resopnse( [], ApiStatus::CODE_50000 ,'订单未支付');
            return;
        }
        api_resopnse( [], ApiStatus::CODE_0 ,'订单已支付');
        return;
    }
    //订单详情查询
    public function get(){
        $params   = $this->params;
        $params = filter_array($params,[
            'order_no'=>'required',
        ]);
        if(!isset($params['order_no']) && $params['order_no']<1){
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Order_Error_Order_no,'');
            return;
        }
        //查询条件
        $where['order_no'] = $params['order_no'];
        $additional['goods_info'] = true;
        $additional['address_info'] = true;
        //获取订单详情
        $data = $this->order_detail($where,$additional);
        if(!$data){
            api_resopnse( [], ApiStatus::CODE_50003 ,'订单获取失败');
            return;
        }

        api_resopnse( $data,ApiStatus::CODE_0 );
        return;
    }

    //默认订单详情查询（最新订单）
    public function get_default(){
		// 兼容，如果订单编号参数不存在，默认取最新的订单
		$this->order   = $this->load->service('order2/order');
		$where = [
            'appid'=>api_request()->getAppid(),
            'user_id'=> $this->member['id'],
            'order_status' => 1
        ];
        //特殊appid订单显示处理
        $appid = api_request()->getAppid();
        if(in_array($appid,config("APPID_SPECIAL"))){
            $where['appid'] = $appid;
        }
        else{
            $where['appid'] = ['not in',implode(config("APPID_SPECIAL"),",")];
        }
		$additional = ['page'=>1,'size'=>1];
		$order_list = $this->order->get_order_list($where,$additional);
		if( count($order_list) ){
			$order_no = $order_list[0]['order_no'];
		}
		if( !isset($order_no) && !$order_no ){ // 没有默认订单
			api_resopnse( [], ApiStatus::CODE_50003,'订单获取失败');
			return;
		}
		
        //查询条件
        $where['order_no'] = $order_no;
        $additional['goods_info'] = true;
        $additional['address_info'] = true;
        //获取订单详情
        $data = $this->order_detail($where,$additional);
        if(!$data){
            api_resopnse( [], ApiStatus::CODE_50003 ,'订单获取失败');
            return;
        }
        api_resopnse( $data,ApiStatus::CODE_0 );
        return;
    }
	
    //订单详情公有方法
    public function order_detail($where,$additional){
        /*****************依赖服务************/
        $this->order   = $this->load->service('order2/order');
        $this->delivery  = $this->load->service('order2/delivery');
        $this->service_serve  = $this->load->service('order2/service');
        $this->spu_serve     = $this->load->service('goods2/goods_spu');
        $this->sku_serve     = $this->load->service('goods2/goods_sku');

        //获取订单
        $order = $this->order->get_order_info($where,$additional);
        //判断订单是否存在
        if(!$order){
            return false;
        }

        //获取订单状态
        $orderObj = new oms\Order($order);
        $status_name = $orderObj->get_client_name();
        $root = $orderObj->get_client_operations($order);
        $order_root = $this->get_root($order,$root);
        /*操作权限*/
        //维修
        $repair_btn = $order['zuqi_type']==1?false:true;
        //退货
        $return_btn = $order_root['return'];
        if($order['return_id']>0 && $order['status'] == oms\state\State::OrderInService){
            $return_btn = false;
        }
        //确认收货
        $confirm_btn = $order_root['confirm']?true:false;
        //取消订单
        $cancel_btn = $order_root['cancel']?true:false;
        //审核
        $pass_btn = $order_root['passed']?true:false;
        //审核拒绝
        $no_pass_btn = $order_root['no_passed']?true:false;
        //支付
        $payment_btn = $order_root['payment']?true:false;;
        //支付时间
        $paytime_btn = false;
        //租期时间
        $service_time_btn = $order['status']==oms\state\State::OrderInService?true:false;

        //获取支付时间
        $payment_time = "";
        if($order['payment_status'] == zuji\order\PaymentStatus::PaymentSuccessful){
            $this->payment_serve = $this->load->service('order2/payment');
            $payment_info = $this->payment_serve->get_info_by_order_id($order['order_id']);
            if($payment_info){
                $payment_time = date("Y-m-d H:i:s",$payment_info['payment_time']);
                $paytime_btn = true;
            }
        }
        $delivery = [];
        //获取发货单信息
        if($order['delivery_status']>0){
            //寄回发货单
            $delivery = $this->delivery->get_info($order['delivery_id']);
            //发货单
            if($delivery['business_key'] == zuji\Business::BUSINESS_ZUJI && $order['return_status'] == zuji\order\ReturnStatus::ReturnInvalid){
                $data_time = (time()-$delivery['confirm_time'])/86400;
                //发货七天内可以退货
                if($data_time<=7){
                    $return_btn  = true;
                }
                else{
                    $return_btn  = false;
                }
            }
        }

        //获取订单服务信息
        $service =  $this->service_serve->get_info_by_order_id($order['order_id']);
        //计算是否到期
        $days = "";
        $begin_time = "";
        $end_time = "";
        if(!empty($service)){
            $begin_time = date("Y-m-d H:i:s",$service['begin_time']);
            $end_time  = date("Y-m-d H:i:s",$service['end_time']);
            //租机有到期状态，无人机没有到期状态
            if($order['zuqi_type']!=1){
                $Service = Service::createService( $service );
                $days = $Service->get_remaining_days();
                if( $days<=7 && $days>0 ){
                    $status_name = "即将到期";
                }elseif($days<=0){
                    $status_name = "已到期";
                }
            }
        }

        $data = [];

        //按钮状态
        $data['confirm_btn'] = $confirm_btn;
        $data['cancel_btn'] = $cancel_btn;
        $data['repair_btn'] = $repair_btn;
        $data['return_btn'] = $return_btn;
        $data['payment_btn'] = $payment_btn;
        $data['paytime_btn'] = $paytime_btn;
        $data['service_time_btn'] = $service_time_btn;
        $data['pass_btn'] = $pass_btn;
        $data['no_pass_btn'] = $no_pass_btn;

        //获取订单渠道类型
        $this->channel_appid = $this->load->service('channel/channel_appid');
        $appid_info = $this->channel_appid->get_info($order['appid']);

        //订单基本信息
        if($order['zuqi_type']==1){
            $zuqi_type = "day";
        }
        elseif($order['zuqi_type']==2){
            $zuqi_type = "month";
        }
        $data['appid'] = $order['appid'];
        $data['appid_type'] = $appid_info['appid']['type'];
        $data['order_no'] = $order['order_no'];
        $data['status'] = $order['status'];
        $data['payment_type_id'] =  $order['payment_type_id'];
        $data['create_time'] = date("Y-m-d H:i:s",$order['create_time']);
        $data['all_amount'] = $order['all_amount'];
        $data['amount'] = $order['amount'];
        $data['discount_amount'] = $order['discount_amount']?$order['discount_amount']:"";
        $data['buyout_price'] = $order['buyout_price']?$order['buyout_price']:"";
        $data['yajin'] = $order['yajin'];
        $data['mianyajin'] = $order['mianyajin'];
        $data['fenqi_amount'] = sprintf("%.2f",round($order['amount']/$order['zuqi'],2));
        $data['zujin'] = $order['zujin'];
        $data['zuqi'] = $order['zuqi'];
        $data['zuqi_type'] = $zuqi_type;
        $data['yiwaixian'] = $order['yiwaixian'];
        $data['wuliu_no'] = $delivery['wuliu_no']?$delivery['wuliu_no']:"";
        $data['order_no'] = $order['order_no'];
        $data['order_id'] = $order['order_id'];
        $data['payment_time'] = $payment_time;
        $data['begin_time'] = $begin_time;
        $data['end_time'] = $end_time;
        $data['day'] =$days;
        $data['status_name'] = $status_name;
        $data['status_key'] = "";
        //芝麻订单号
        //获取订单的芝麻订单编号
        $this->zhima_certification_service =$this->load->service('order2/zhima_certification');
        $zhima_order_info = $this->zhima_certification_service->where(['out_order_no'=>$order['order_no']])->find();
        $zhima_order_no ="";
        if($zhima_order_info){
            $zhima_order_no = $zhima_order_info['order_no'];
        }
        $data['zhima_order_no'] = $zhima_order_no;
        //首次分期金额计算
        $this->service = $this->load->service("order2/instalment");
        $result = $this->service->get_order_instalment($order['order_id']);
        if($result){
            $data['first_amount'] = sprintf("%.2f",$result[0]['amount']/100);
            //如果租期类型按天则重新计算分期金额
            $data['fenqi_amount'] = $order['zuqi_type']==1?sprintf("%.2f",round($order['amount']/$order['zuqi'],2)):sprintf("%.2f",$result[count($result)-1]['amount']/100);
        }

        //商品信息
        $spu_goods = $this->spu_serve->api_get_info($order['goods_info']['spu_id']);
        $data['imgs'] = json_decode($spu_goods['imgs'],true);
        //订单合同模板id
        $data['contract_id'] = $spu_goods['contract_id'];

        $thumb = $spu_goods['thumb'];
        $specs_list = $order['goods_info']['specs'];
        $specs_value  = array_column($specs_list,"value");

        $sku_goods = $this->sku_serve->api_get_info($order['goods_info']['sku_id'],"market_price");

        $data['sku_info']   =   array(
            'sku_id'=>$order['goods_info']['sku_id'],
            'sku_name'=>$order['goods_info']['sku_name'],
            'thumb'=>$thumb,
            'specs'=>implode("|",$specs_value),

            'market_price'   => sprintf("%.2f",$sku_goods['market_price']),
            // 买断金    =  （市场价 * 120%） -  （租金 * 月租金）
            'buyout_price'   => ($sku_goods['market_price'] * 120 / 100) - ($data['zujin'] * $data['zuqi']),
        );
        $data['buyout_price'] = sprintf("%.2f", ($sku_goods['market_price'] * 120 / 100) - ($data['zujin'] * $data['zuqi']));

        //用户地址信息
        if($order['address_info']){
            $this->district_service = $this->load->service('admin/district');
            $province  = $this->district_service->get_name($order['address_info']['province_id']);
            $city       = $this->district_service->get_name($order['address_info']['city_id']);
            $country   = $this->district_service->get_name($order['address_info']['country_id']);
            //地址信息
            $data['address_info']   = array(
                'name'=>$order['address_info']['name'],
                'mobile'=>$order['address_info']['mobile'],
                'address'=>$province.'-'.$city.'-'.$country.' '.$order['address_info']['address'],
            );
        }
        //优惠券信息
        if($order['discount_amount']>0){
            $this->coupon = $this->load->table('order2/order2_coupon');
            $coupon = $this->coupon->where(['order_id'=>$order['order_id']])->field("coupon_name,coupon_type,discount_amount")->find();
            $coupon['discount_amount'] /=100;
            // $coupon['coupon_type'] = zuji\coupon\CouponStatus::get_coupon_type_name($coupon['coupon_type']);
            $data['coupon_info'] = $coupon;
        }
        return $data;
    }
    //订单确认查询
    public function confirmation_query(){
        $app_id = api_request()->getAppid();
        $params = $this->params;
        $params = filter_array($params, [
            'sku_id' => 'required|is_id',	//【必须】int；SKU ID
            'payment_type_id' => 'required', //【必须】int；支付方式
            'coupon_no' => 'required',	//【可选】int；优惠券码
        ]);
        if( empty($params['sku_id']) ){
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Sku_Error_Sku_id,'sku_id 参数错误');
            return;
        }

        $params['payment_type_id'] = $params['payment_type_id']?$params['payment_type_id']:0;

        $sku_id  = intval($params['sku_id']);

        $user_id = $this->member['id'];
        //首月0租金优惠券领取活动--(临时)--
        $sku_info = $this->load->service("goods2/goods_sku")->api_get_info($sku_id,"spu_id");
        $this->coupon = $this->load->table("coupon/coupon_type");
        $coupon_info = $this->coupon->where(['only_id'=>'4033f1cdfa5d835ea70cd07be787babc'])->find();
        $num = explode(",",substr($coupon_info['range_value'],0,-1));
        if(in_array($sku_info['spu_id'],$num)){
            $coupon = \zuji\coupon\Coupon::set_coupon_user(array("user_id"=>$this->member['id'],"only_id"=>$coupon_info['only_id']));
            $params['coupon_no'] = $coupon['coupon_no'];
        }
		//-+--------------------------------------------------------------------
		// | 用户信用认证的验证
		//-+--------------------------------------------------------------------
		//初始化信用认证和当前数据库已有认证的标识
		$certified_flag = true;//认证存在且一致|认证不存在（保存认证）【false：有认证且认证姓名或身份证号不一致】

        /**************获取用户信息************/
		//获取用户信息
		$user_info = $this->get_user();
		if( $app_id == \zuji\Config::Jdxbxy_App_id ) {
			//订单表服务层
			$this->order_service = $this->load->service('order2/order');
			//京东授权表服务层
			$this->member_jd_service = $this->load->service('member2/member_jd');
			//京东授权记录表服务层
			$this->certification_jd_service = $this->load->service('member2/certification_jd');
			//用户表服务层
			$this->member_service = $this->load->service('member2/member');
			//查询京东授权表，是否存在绑定的用户信息
			$user_auth_info = $this->member_jd_service->get_info_by_member_id($user_id);
			
			//创建京东小白信用接口调用类
			$jd_obj = new BaiTiao();
			//通过京东授权令牌获取用户权益信息
			$user_jd_info = $jd_obj->callback(['open_id'=>$user_auth_info['open_id']]);
			if( !$user_jd_info ) {
                api_resopnse( [], ApiStatus::CODE_50001,'京东小白信用认证失败，请从新尝试');
                return;
			}
			//判断当前用户的信息和京东认证的信息（真实姓名是否存在且一致）
			if( !empty($user_info['realname']) && $user_info['realname'] != $user_jd_info['realname'] ) {
				//清除京东的真实姓名信息，不对已经认证的用户真实姓名和身份证号进行修改
				unset($user_jd_info['realname']);
				//将信用认证是否一致的标识置为false
				$certified_flag = false;
			}
			//开启事务
			$this->order_service->startTrans();
			//更新京东授权表，用户表，授权记录表
			$user_jd_info['member_id'] = $user_id;//拼接用户id信息
			$user_jd_info['appid'] = $app_id;
			$member_result = $this->member_service->update_jd_login_info($user_jd_info);
			$member_jd_result = $this->member_jd_service->update_auth(['id'=>$user_auth_info['id'],'is_auth'=>1]);
			$certification_jd_result = $this->certification_jd_service->create($user_jd_info);
			if( !$member_result || !$member_jd_result || !$certification_jd_result ) {
				//回滚事务
				$this->order_service->rollback();
				//记录debug
				Debug::error(Location::L_UserAuthorization, '京东小白授权确认订单出错', ['error'=> get_error(),'$member_result'=>$member_result,'$member_jd_result'=>$member_jd_result,'$certification_jd_result'=>$certification_jd_result]);
                api_resopnse( [], ApiStatus::CODE_50001,'京东小白信用认证失败，请从新尝试');
                return;
			}
			//提交事务
			$this->order_service->commit();
			$need_to_credit_certificate = 'N';
		}else{
			// 获取用户最后一次认证的 信息
			$this->certification_alipay = $this->load->service('member2/certification_alipay');
			$cert_info = $this->certification_alipay->get_last_info_by_user_id($user_id);

			if( $cert_info['order_no'] ){
				// 查询用户认证结果
				$zhima = new \zuji\certification\Zhima();
				$zhima_order_info = $zhima->getOrderInfo($cert_info['order_no'], $user_id);
				// 获取成功：则重新获取用户信息
				if( $zhima_order_info ){
					//重新获取用户信息（因为已经更新了用户的认证信息）
					$user_info = $this->get_user();
				}
				//已经更新了最后一次的用户认证信息，再次获取用户最后一次认证信息，和当前用户信息比对使用；
				$cert_info = $this->certification_alipay->get_last_info_by_user_id($user_id);
			}
			//身份证号不一致的情况将信用认证是否一致的标识置为false
			if( !empty($cert_info['cert_no']) && !empty($user_info['cert_no']) && $cert_info['cert_no'] != $user_info['cert_no'] ) {
				$certified_flag = false;
			}

			// 是否需要信用认证
			$need_to_credit_certificate = 'Y';
			// 未初始化认证的，直接去认证
			if( $_SESSION['_cert_init_']===true && $cert_info['create_time'] ){
				// 设置每次调起信用认证
				$cert_time = strtotime($cert_info['create_time']);
				// 信用失效时间判断(10分钟)
				if( (time() - $cert_time) <= 60*60 ){
					$need_to_credit_certificate = 'N';
				}
			}
		}

        /**********获取支付信用及规则信息*************/
        if($params['payment_type_id']>0){
            $this->credit = $this->load->service("payment/credit");
            $credit_info = $this->credit->get_info_by_payment($params['payment_type_id']);
            $credit_info = current($credit_info);
            //信用类型
            $credit_type = $credit_info['id'];
            if(!$credit_info){
                api_resopnse( [], ApiStatus::CODE_40003,'不支持该支付方式');
                return;
            }
        }

        try {
            $business_key = \zuji\Business::BUSINESS_ZUJI;// 此处的 业务类型 作为 确认订单的默认值（该接口只读，不记录订单，用任何业务类型都不影响）
            // 订单创建器
            $orderCreaterComponnet = new \oms\OrderCreater( $business_key );

            // 用户
            $UserComponnet = new \oms\order_creater\UserComponnet($orderCreaterComponnet,$user_id);
            $orderCreaterComponnet->set_user_componnet($UserComponnet);

            // 商品
            $SkuComponnet = new \oms\order_creater\SkuComponnet($orderCreaterComponnet,$sku_id,$params['payment_type_id']);
            $orderCreaterComponnet->set_sku_componnet($SkuComponnet);

            // 装饰者 信用
            $orderCreaterComponnet = new \oms\order_creater\CreditComponnet($orderCreaterComponnet,$certified_flag,$app_id);

			if( $app_id != \zuji\Config::Jdxbxy_App_id ) {
				// 装饰者 风险
				$orderCreaterComponnet = new \oms\order_creater\YidunComponnet($orderCreaterComponnet);
			}

            // 装饰着 押金
            $orderCreaterComponnet = new \oms\order_creater\DepositComponnet($orderCreaterComponnet,$params['payment_type_id'],$certified_flag);

            // 装饰着 代扣
            $orderCreaterComponnet = new \oms\order_creater\UserWithholding($orderCreaterComponnet);

            // 装饰着 渠道
            $orderCreaterComponnet = new \oms\order_creater\ChannelComponnet($orderCreaterComponnet, $app_id);

            //装饰者 优惠券
            $orderCreaterComponnet = new \oms\order_creater\CouponComponnet($orderCreaterComponnet, $params['coupon_no']);

            // 装饰者 分期单
            $orderCreaterComponnet = new \oms\order_creater\InstalmentComponnet($orderCreaterComponnet);

            // 过滤
            $b = $orderCreaterComponnet->filter();
            if( !$b ){
                $this->order_remark($user_id,$orderCreaterComponnet->get_order_creater()->get_error());
            }
            // 元数据
            $schema_data = $orderCreaterComponnet->get_data_schema();
            // 是否需要签署代扣协议
            $need_to_sign_withholding = 'N';
            if( $schema_data['sku']['payment_type_id']==\zuji\Config::WithhodingPay){
                if( !$schema_data['withholding']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }
            //租期类型格式
            $zuqi_type = "";
            if($schema_data['sku']['zuqi_type']==1){
                $zuqi_type = "day";
            }
            elseif($schema_data['sku']['zuqi_type']==2){
                $zuqi_type = "month";
            }

            $result = [
                'coupon_no'         => $params['coupon_no'],
                'certified'			=> $schema_data['credit']['certified']?'Y':'N',
                'certified_platform'=> zuji\certification\Certification::getPlatformName($schema_data['credit']['certified_platform']),
                'credit'			=> ''.$schema_data['credit']['credit'],

                'credit_type'			=> $credit_type,
                'credit_status'		=> $b &&$need_to_sign_withholding=='N'&&$need_to_credit_certificate=='N'?'Y':'N',  // 是否免押金

                // 订单金额
                'amount'			=> Order::priceFormat($schema_data['sku']['amount']/100),
                // 优惠类型
                'coupon_type'	=> ''.$schema_data['coupon']['coupon_type'],
                // 优惠金额
                'discount_amount'	=> Order::priceFormat($schema_data['sku']['discount_amount']/100),
                // 商品总金额
                'all_amount'		=> Order::priceFormat($schema_data['sku']['all_amount']/100),
                // 买断价
                'buyout_price'	    => Order::priceFormat($schema_data['sku']['buyout_price']/100),
                // 市场价
                'market_price'	    => Order::priceFormat($schema_data['sku']['market_price']/100),
                //押金
                'yajin'				=> Order::priceFormat($schema_data['sku']['yajin']/100),
                //免押金
                'mianyajin'			=> Order::priceFormat($schema_data['sku']['mianyajin']/100),
                //原始租金
                'zujin'				=> Order::priceFormat($schema_data['sku']['zujin']/100),
                //首期金额
                'first_amount'				=> Order::priceFormat($schema_data['instalment']['first_amount']/100),
                //每期金额
                'fenqi_amount'				=> Order::priceFormat($schema_data['instalment']['fenqi_amount']/100),
                //意外险
                'yiwaixian'			=> Order::priceFormat($schema_data['sku']['yiwaixian']/100),
                //租期
                'zuqi'				=> ''.$schema_data['sku']['zuqi'],
                //租期类型
                'zuqi_type'			=> $zuqi_type,
                'chengse'			=> ''.$schema_data['sku']['chengse'],
                // 支付方式
                'payment_type_id'	 => ''.$schema_data['sku']['payment_type_id'],
                'contract_id'			 => ''.$schema_data['sku']['contract_id'],
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $need_to_credit_certificate,
                '_order_info' => $schema_data,
                '$b' => $b,
                '_error' => $orderCreaterComponnet->get_order_creater()->get_error(),
            ];
            Debug::error(Location::L_Order,'订单确认查询',$result);
            api_resopnse( $result, ApiStatus::CODE_0);
            return;

        } catch (\oms\order_creater\ComponnetException $exc) {
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Sku_Error_Sku_id,$exc->getMessage());
            return;
        }
        exit;
    }
    //线上渠道创建订单
    public function create(){
        $app_id = api_request()->getAppid();
        $params   = $this->params;
        $params = filter_array($params, [
            'payment_type_id' => 'required', //【必须】int；支付方式
            'address_id' => 'required|is_id',	//【必须】int；用户收货地址ID
            'sku_id' => 'required|is_id',	//【必须】int；SKU ID
            'coupon_no'=>'required',  //【可选】string;优惠券编号
        ]);

        if(empty($params['address_id']) ){
            api_resopnse( [], ApiStatus::CODE_20001,'参数错误', ApiSubCode::Address_Error_Address_id,'收货地址错误');
            return;
        }
        $address_id = $params['address_id'];

        if( empty($params['sku_id']) ){
            api_resopnse( [], ApiStatus::CODE_20001,'参数错误', ApiSubCode::Sku_Error_Sku_id,'商品ID错误');
            return;
        }
        if( empty($params['payment_type_id']) ){
            api_resopnse( [], ApiStatus::CODE_20001,'payment_type_id必须');
            return;
        }
        $redis_key = $this->member['id']."-".date("YmdHi");
        $redis = \zuji\cache\Redis::getInstans();
        $result = $redis->get($redis_key);
        if($result){
            api_resopnse( [], ApiStatus::CODE_20001,'正在处理中');
            return;
        }
        else{
            $redis->set($redis_key,json_encode(['user_id'=>$this->member['id']]),60);
        }

        $sku_id  = intval($params['sku_id']);
        $user_id = $this->member['id'];

		//-+--------------------------------------------------------------------
		// | 用户信用认证的验证
		//-+--------------------------------------------------------------------
		//初始化信用认证和当前数据库已有认证的标识
		$certified_flag = true;//认证存在且一致|认证不存在（保存认证）【false：有认证且认证姓名或身份证号不一致】
		
        /**************获取用户信息************/
		//获取用户信息
		$user_info = $this->get_user();
		if( $app_id == \zuji\Config::Jdxbxy_App_id ) {
			//订单表服务层
			$this->order_service = $this->load->service('order2/order');
			//京东授权表服务层
			$this->member_jd_service = $this->load->service('member2/member_jd');
			//京东授权记录表服务层
			$this->certification_jd_service = $this->load->service('member2/certification_jd');
			//用户表服务层
			$this->member_service = $this->load->service('member2/member');
			//查询京东授权表，是否存在绑定的用户信息
			$user_auth_info = $this->member_jd_service->get_info_by_member_id($user_id);
			
			//创建京东小白信用接口调用类
			$jd_obj = new BaiTiao();
			//通过京东授权令牌获取用户权益信息
			$user_jd_info = $jd_obj->callback(['open_id'=>$user_auth_info['open_id']]);
			if( !$user_jd_info ) {
                api_resopnse( [], ApiStatus::CODE_50001,'京东小白信用认证失败，请从新尝试');
                return;
			}
			//判断当前用户的信息和京东认证的信息（真实姓名是否存在且一致）
			if( !empty($user_info['realname']) && $user_info['realname'] != $user_jd_info['realname'] ) {
				//清除京东的真实姓名信息，不对已经认证的用户真实姓名和身份证号进行修改
				unset($user_jd_info['realname']);
				//将信用认证是否一致的标识置为false
				$certified_flag = false;
			}
			//开启事务
			$this->order_service->startTrans();
			//更新京东授权表，用户表，授权记录表
			$user_jd_info['member_id'] = $user_id;//拼接用户id信息
			$user_jd_info['appid'] = $app_id;
			$member_result = $this->member_service->update_jd_login_info($user_jd_info);
			$member_jd_result = $this->member_jd_service->update_auth(['id'=>$user_auth_info['id'],'is_auth'=>1]);
			$certification_jd_result = $this->certification_jd_service->create($user_jd_info);
			if( !$member_result || !$member_jd_result || !$certification_jd_result ) {
				//回滚事务
				$this->order_service->rollback();
				//记录debug
				Debug::error(Location::L_UserAuthorization, '京东小白授权确认订单出错', ['error'=> get_error(),'$member_result'=>$member_result,'$member_jd_result'=>$member_jd_result,'$certification_jd_result'=>$certification_jd_result]);
                api_resopnse( [], ApiStatus::CODE_50001,'京东小白信用认证失败，请从新尝试');
                return;
			}
			//提交事务
			$this->order_service->commit();
		}else{
			// 获取用户最后一次认证的 信息
			$this->certification_alipay = $this->load->service('member2/certification_alipay');
			$cert_info = $this->certification_alipay->get_last_info_by_user_id($user_id);

			if( $cert_info['order_no'] ){
				// 查询用户认证结果
				$zhima = new \zuji\certification\Zhima();
				$zhima_order_info = $zhima->getOrderInfo($cert_info['order_no'], $user_id);
				// 获取成功：则重新获取用户信息
				if( $zhima_order_info ){
					//重新获取用户信息（因为已经更新了用户的认证信息）
					$user_info = $this->get_user();
				}
				//已经更新了最后一次的用户认证信息，再次获取用户最后一次认证信息，和当前用户信息比对使用；
				$cert_info = $this->certification_alipay->get_last_info_by_user_id($user_id);
			}
			//身份证号不一致的情况将信用认证是否一致的标识置为false
			if( !empty($cert_info['cert_no']) && !empty($user_info['cert_no']) && $cert_info['cert_no'] != $user_info['cert_no'] ) {
				$certified_flag = false;
			}
		}

        // 订单编号
        $order_no = \zuji\Business::create_business_no();

        $order_service = $this->load->service('order2/order');

        //开启事务
        $b = $order_service->startTrans();
        if( !$b ){
            api_resopnse( [], ApiStatus::CODE_40003,'事务失败', '','服务器繁忙，请稍后重试...');
            return;
        }

        /**********获取支付信用及规则信息*************/
        $this->credit = $this->load->service("payment/credit");
        $credit_info = $this->credit->get_info_by_payment($params['payment_type_id']);
        if( !$credit_info ){
            api_resopnse( [], ApiStatus::CODE_40003,'不支持该支付方式', '','服务器繁忙，请稍后重试...');
            return;
        }
        $credit_info = current($credit_info);
        //信用类型
        $credit_type = $credit_info['id'];

        try {
            $business_key = \zuji\Business::BUSINESS_ZUJI;// 此处的 业务类型 作为 确认订单的默认值（该接口只读，不记录订单，用任何业务类型都不影响）
            // 订单创建器
            $orderCreaterComponnet = new \oms\OrderCreater( $business_key,$order_no );

            // 用户
            $UserComponnet = new \oms\order_creater\UserComponnet($orderCreaterComponnet,$user_id);
            $orderCreaterComponnet->set_user_componnet($UserComponnet);

            // 商品
            $SkuComponnet = new \oms\order_creater\SkuComponnet($orderCreaterComponnet,$sku_id,$params['payment_type_id']);
            $orderCreaterComponnet->set_sku_componnet($SkuComponnet);

            // 装饰者 信用
            $orderCreaterComponnet = new \oms\order_creater\CreditComponnet($orderCreaterComponnet,true,$app_id);

			if( $app_id != \zuji\Config::Jdxbxy_App_id ) {
				// 装饰者 风险
				$orderCreaterComponnet = new \oms\order_creater\YidunComponnet($orderCreaterComponnet);
			}
            // 装饰着 押金
            $orderCreaterComponnet = new \oms\order_creater\DepositComponnet($orderCreaterComponnet,$params['payment_type_id']);

            // 装饰着 代扣
            $orderCreaterComponnet = new \oms\order_creater\UserWithholding($orderCreaterComponnet);

            // 装饰者 收货地址
            $orderCreaterComponnet = new \oms\order_creater\AddressComponnet($orderCreaterComponnet,$address_id);

            // 装饰者 渠道
            $orderCreaterComponnet = new \oms\order_creater\ChannelComponnet($orderCreaterComponnet, $app_id);

            //装饰者 优惠券
            $orderCreaterComponnet = new \oms\order_creater\CouponComponnet($orderCreaterComponnet, $params['coupon_no']);

            // 装饰者 分期单
            $orderCreaterComponnet = new \oms\order_creater\InstalmentComponnet($orderCreaterComponnet);

            $b = $orderCreaterComponnet->filter();
            if( !$b ){
                $order_service->rollback();
                Debug::error(Location::L_Order,'[创建订单]组件过滤失败',$orderCreaterComponnet->get_order_creater()->get_error());
                // 无法下单原因
                $this->order_remark($user_id,$orderCreaterComponnet->get_order_creater()->get_error());
                api_resopnse( [], ApiStatus::CODE_50002,'', '', $orderCreaterComponnet->get_order_creater()->get_error());
                return;
            }
            // 元数据
            $schema_data = $orderCreaterComponnet->get_data_schema();
            $b = $orderCreaterComponnet->create();
            //创建成功组装数据返回结果
            if(!$b){
                $order_service->rollback();
                $error = $orderCreaterComponnet->get_order_creater()->get_error();
                // 无法下单原因
                $this->order_remark($user_id,$error);
                Debug::error(Location::L_Order, '[创建订单]失败', ['error'=>$error,'_data_schema'=>$schema_data]);
                api_resopnse( [], ApiStatus::CODE_50003, get_error(),  ApiSubCode::Order_Creation_Failed, '服务器繁忙，请稍后重试...');
                return;
            }
            $order_id = $orderCreaterComponnet->get_order_creater()->get_order_id();
            $order_no = $orderCreaterComponnet->get_order_creater()->get_order_no();

            // 记录操作日志
            $this->add_order_log($schema_data['user']['user_id'],$schema_data['user']['mobile'],$order_no,'创建订单','');

            $b = $order_service->commit();
            if( !$b ){
                api_resopnse( [], ApiStatus::CODE_50003, '事务失败',  ApiSubCode::Order_Creation_Failed, '服务器繁忙，请稍后重试...');
                return;
            }
            // 清空 无法下单原因
            $this->order_remark($user_id,'');

            //创建订单后 发送支付短信。
            $result = ['auth_token'=>  $this->auth_token,];
            $sms = new \zuji\sms\HsbSms();
             $b = $sms->send_sm($schema_data['user']['mobile'],'SMS_113450944',[
                'goodsName' => $schema_data['sku']['sku_name'],    // 传递参数
             ],$order_no);

//            $b = $sms->send_sm($schema_data['user']['mobile'],'hsb_sms_a3bd84',[
//               'jieRi' => "五一",
//               'yanchiZhouqi' => "2018年4月29日至2018年5月1日",
//               'zidongQuxiao' => "2",
//           ],$order_no);

            if (!$b) {
                Debug::error(Location::L_Order,'线上下单短信',$b);
            }
//            //发送消息推送
//            //通过用户id查询支付宝用户id
//            $this->certification_alipay = $this->load->service('member2/certification_alipay');
//            $to_user_id = $this->certification_alipay->get_last_info_by_user_id($user_id);
//            if(!empty($to_user_id['user_id'])){
//                $MessageSingleSendWord = new \alipay\MessageSingleSendWord( $to_user_id['user_id'] );
//                $message_arr = [
//                    'order_no'=>$order_no,
//                    'order_amount'=>Order::priceFormat($schema_data['sku']['amount']/100),
//                    'receiver'=>$to_user_id['name'],
//                    'mobile'=>$to_user_id['mobile'],
//                    'address'=>$to_user_id['house'],
//                ];
//                $b = $MessageSingleSendWord->SuccessfulOrder( $message_arr );
//                if( $b === false ){
//                    $order_service->rollback();
//                    api_resopnse( [], ApiStatus::CODE_20001,'',$MessageSingleSendWord->getError());
//                    return;
//                }
//            }
            // 是否需要签署代扣协议
            $need_to_sign_withholding = 'N';
            if( $schema_data['sku']['payment_type_id']==\zuji\Config::WithhodingPay){
                if( !$schema_data['withholding']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }
            $result = [
                'order_id'			=> $order_id,
                'order_no'			=> $order_no,
                'certified'			=> $schema_data['credit']['certified']?'Y':'N',
                'certified_platform'=> zuji\certification\Certification::getPlatformName($schema_data['credit']['certified_platform']),
                'credit'			=> $schema_data['credit']['credit'],
                'credit_type'			=> $credit_type,
                'credit_status'		=> $schema_data['sku']['yajin']==0?'Y':'N',  // 是否免押金
                // 订单金额
                'amount'			=> Order::priceFormat($schema_data['sku']['amount']/100),
                // 优惠金额
                'discount_amount'	=> Order::priceFormat($schema_data['sku']['discount_amount']/100),
                // 商品总金额
                'all_amount'		=> Order::priceFormat($schema_data['sku']['all_amount']/100),
                // 买断价
                'buyout_price'	    => Order::priceFormat($schema_data['sku']['buyout_price']/100),
                //押金
                'yajin'				=> Order::priceFormat($schema_data['sku']['yajin']/100),
                //免押金
                'mianyajin'			=> Order::priceFormat($schema_data['sku']['mianyajin']/100),
                //原始租金
                'zujin'				=> Order::priceFormat($schema_data['sku']['zujin']/100),
                //首期金额
                'first_amount'				=> Order::priceFormat($schema_data['instalment']['first_amount']/100),
                //每期金额
                'fenqi_amount'				=> Order::priceFormat($schema_data['instalment']['fenqi_amount']/100),
                //意外险
                'yiwaixian'			=> Order::priceFormat($schema_data['sku']['yiwaixian']/100),
                //租期
                'zuqi'				=> $schema_data['sku']['zuqi'],
                'chengse'			=> $schema_data['sku']['chengse'],
                'payment_type_id'				=> $schema_data['sku']['payment_type_id'],
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'			=> $need_to_sign_withholding,
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $schema_data['credit']['certified']?'N':'Y',
                'sku_info'			=> '',
                '_order_info' => $schema_data,
            ];
            //Debug::error(Location::L_Order,'创建订单成功返回参数',$result);
            api_resopnse( ['order_info'=>$result], ApiStatus::CODE_0);
            return;

        } catch (\oms\order_creater\ComponnetException $exc) {
            $order_service->rollback();
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Sku_Error_Sku_id,$exc->getMessage());
            return;
        } catch (\Exception $e){
            $order_service->rollback();
            api_resopnse( [], ApiStatus::CODE_50003, '下单失败',  ApiSubCode::Order_Creation_Failed, '服务器繁忙，请稍后重试...');
            return;
        }
        exit;
    }
    //线下渠道订单创建
    public function offline_create(){
        $app_id = api_request()->getAppid();
        $params   = $this->params;
        $params = filter_array($params, [
            'payment_type_id' => 'required|is_int', //【必须】int；支付方式
            'sku_id' => 'required|is_id',	//【必须】int；SKU ID
            'coupon_no'=>'required',  //【可选】string;优惠券编号
        ]);

        if( !$params['sku_id']){
            api_resopnse( [], ApiStatus::CODE_20001,'参数错误', ApiSubCode::Sku_Error_Sku_id,'商品ID错误');
            return;
        }

        $params['payment_type_id'] = $params['payment_type_id']?$params['payment_type_id']:zuji\Config::WithhodingPay;

        $sku_id  = intval($params['sku_id']);

        $user_id = $this->member['id'];

        //获取用户信息
        $user_info = $this->get_user();

        // 获取用户最后一次认证的 信息
        $this->certification_alipay = $this->load->service('member2/certification_alipay');
        $cert_info = $this->certification_alipay->get_last_info_by_user_id($user_id);

        // 查询用户认证结果
        $zhima = new \zuji\certification\Zhima();
        $zhima_order_info = $zhima->getOrderInfo($cert_info['order_no'], $user_id);

        // 获取成功：则重新获取用户信息
        if( $zhima_order_info ){
            //重新获取用户信息（因为已经更新了用户的认证信息）
            $user_info = $this->get_user();
        }else{
            api_resopnse( [], ApiStatus::CODE_40003,'权限拒绝', ApiSubCode::User_Uncertified,'未通过实名认证');
            return;
        }

        // 订单编号
        $order_no = \zuji\Business::create_business_no();

        $order_service = $this->load->service('order2/order');

        //开启事务
        $b = $order_service->startTrans();
        if( !$b ){
            api_resopnse( [], ApiStatus::CODE_40003,'事务失败', '','服务器繁忙，请稍后重试...');
            return;
        }
        /**********获取支付信用及规则信息*************/
        $this->credit = $this->load->service("payment/credit");
        $credit_info = $this->credit->get_info_by_payment($params['payment_type_id']);
        if( !$credit_info ){
            api_resopnse( [], ApiStatus::CODE_40003,'不支持该支付方式', '','服务器繁忙，请稍后重试...');
            return;
        }
        $credit_info = current($credit_info);
        //信用类型
        $credit_type = $credit_info['id'];

        try {
            $business_key = \zuji\Business::BUSINESS_STORE;// 此处的 业务类型 作为 确认订单的默认值（该接口只读，不记录订单，用任何业务类型都不影响）
            // 订单创建器
            $orderCreaterComponnet = new \oms\OrderCreater( $business_key,$order_no );

            // 用户
            $UserComponnet = new \oms\order_creater\UserComponnet($orderCreaterComponnet,$user_id);
            $orderCreaterComponnet->set_user_componnet($UserComponnet);

            // 商品
            $SkuComponnet = new \oms\order_creater\SkuComponnet($orderCreaterComponnet,$sku_id,$params['payment_type_id']);
            $orderCreaterComponnet->set_sku_componnet($SkuComponnet);

            // 装饰者 信用
            $orderCreaterComponnet = new \oms\order_creater\CreditComponnet($orderCreaterComponnet,true,$app_id);
			
			// 装饰者 风险
			$orderCreaterComponnet = new \oms\order_creater\YidunComponnet($orderCreaterComponnet);

            // 装饰着 押金
            $orderCreaterComponnet = new \oms\order_creater\DepositComponnet($orderCreaterComponnet,$params['payment_type_id']);

            // 装饰着 代扣
            $orderCreaterComponnet = new \oms\order_creater\UserWithholding($orderCreaterComponnet);

            // 装饰着 渠道
            $orderCreaterComponnet = new \oms\order_creater\ChannelComponnet($orderCreaterComponnet, $app_id);

            //装饰者 优惠券
            $orderCreaterComponnet = new \oms\order_creater\CouponComponnet($orderCreaterComponnet, $params['coupon_no']);

            // 装饰者 分期单
            $orderCreaterComponnet = new \oms\order_creater\InstalmentComponnet($orderCreaterComponnet);

            $b = $orderCreaterComponnet->filter();
            if( !$b ){
                $order_service->rollback();
                // 无法下单原因
                $this->order_remark($user_id,$orderCreaterComponnet->get_order_creater()->get_error());
//				var_dump( $orderCreaterComponnet->get_order_creater()->get_error() );
                api_resopnse( [], ApiStatus::CODE_50002,'', '', $orderCreaterComponnet->get_order_creater()->get_error());
                return;
            }

            // 元数据
            $schema_data = $orderCreaterComponnet->get_data_schema();
            $b = $orderCreaterComponnet->create();
            //创建成功组装数据返回结果
            if(!$b){
                $order_service->rollback();
                $error = $orderCreaterComponnet->get_order_creater()->get_error();
                // 无法下单原因
                $this->order_remark($user_id,$error);
                Debug::error(Location::L_Order, '下单失败', ['error'=>$error,'_data_schema'=>$schema_data]);
                api_resopnse( [], ApiStatus::CODE_50003, get_error(),  ApiSubCode::Order_Creation_Failed, '服务器繁忙，请稍后重试...');
                return;
            }
            $order_id = $orderCreaterComponnet->get_order_creater()->get_order_id();
            $order_no = $orderCreaterComponnet->get_order_creater()->get_order_no();

            // 记录操作日志
            $this->add_order_log($schema_data['user']['user_id'],$schema_data['user']['mobile'],$order_no,'创建订单','');

            $b = $order_service->commit();
            if( !$b ){
                api_resopnse( [], ApiStatus::CODE_50003, '事务失败',  ApiSubCode::Order_Creation_Failed, '服务器繁忙，请稍后重试...');
                return;
            }
            // 清空 无法下单原因
            $this->order_remark($user_id,'');

            //创建订单后 发送支付短信。
            $appid_info = $this->load->table('channel/channel_appid')->get_info($app_id);
            if(empty($appid_info)){
                Debug::error(Location::L_Order,'线下下单获取appid信息失败',['appid:' => $app_id]);
            }
            $sms = new \zuji\sms\HsbSms();
            $b = $sms->send_sm($schema_data['user']['mobile'],'hsb_sms_b6667',[
                'storeName' =>$appid_info['name'],    // 传递参数
                'goodsName' => $schema_data['sku']['sku_name'],    // 传递参数
            ],$order_no);
            if (!$b) {
                Debug::error(Location::L_Order,'线下下单短信',$b);
            }

            $result = [
                'order_id'			=> $order_id,
                'order_no'			=> $order_no,
                'certified'			=> $schema_data['credit']['certified']?'Y':'N',
                'certified_platform' => zuji\certification\Certification::getPlatformName($schema_data['credit']['certified_platform']),
                'credit'			=> ''.$schema_data['credit']['credit'],
                'credit_status'		=> $schema_data['sku']['yajin']==0?'Y':'N',  // 是否免押金
                // 订单金额
                'amount'			=> Order::priceFormat($schema_data['sku']['amount']/100),
                // 优惠金额
                'discount_amount'	=> Order::priceFormat($schema_data['sku']['discount_amount']/100),
                // 商品总金额
                'all_amount'		=> Order::priceFormat($schema_data['sku']['all_amount']/100),
                // 买断价
                'buyout_price'	    => Order::priceFormat($schema_data['sku']['buyout_price']/100),
                //押金
                'yajin'				=> Order::priceFormat($schema_data['sku']['yajin']/100),
                //免押金
                'mianyajin'			=> Order::priceFormat($schema_data['sku']['mianyajin']/100),
                //原始租金
                'zujin'				=> Order::priceFormat($schema_data['sku']['zujin']/100),
                //首期金额
                'first_amount'				=> Order::priceFormat($schema_data['instalment']['first_amount']/100),
                //每期金额
                'fenqi_amount'				=> Order::priceFormat($schema_data['instalment']['fenqi_amount']/100),
                //意外险
                'yiwaixian'			=> Order::priceFormat($schema_data['sku']['yiwaixian']/100),
                //租期
                'zuqi'				=> ''.$schema_data['sku']['zuqi'],
                'chengse'			=> ''.$schema_data['sku']['chengse'],
                'payment_type_id'				=> ''.$schema_data['sku']['payment_type_id'],
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'			=> $schema_data['withholding']['withholding_no']?'N':'Y',
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $schema_data['credit']['certified']?'N':'Y',
                'sku_info'			=> '',
                '_order_info' => $schema_data,
            ];
            api_resopnse( ['order_info'=>$result], ApiStatus::CODE_0);
            return;
        } catch (\oms\order_creater\ComponnetException $exc) {
            $order_service->rollback();
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Sku_Error_Sku_id,$exc->getMessage());
            return;
        } catch (\Exception $e){
            $order_service->rollback();
            api_resopnse( [], ApiStatus::CODE_50003, '下单失败',  ApiSubCode::Order_Creation_Failed, '服务器繁忙，请稍后重试...');
            return;
        }
        exit;
    }

    //重构数组键名
    function arrayKey($infos,$key){
        $retArr = array();
        if( $infos && count($infos) > 0 )
        {
            foreach( $infos as $info )
            {
                $retArr[ $info[ $key ] ] = $info;
            }
        }
        return $retArr;
    }
    //订单取消接口
    public function cancel()
    {
        $params = $this->params;
        //验证参数
        $params = filter_array($params,[
            'order_no' => 'required',
            'reason_id' => 'required|is_id',
            'reason_text' => 'required',
        ]);
        if (empty($params['order_no'])) {
            api_resopnse( [], ApiStatus::CODE_20001,'订单编号必须',ApiSubCode::Order_Error_Order_no,'');
            return;
        }
        if(empty($params['reason_id']) && empty($params['reason_text'])){
            api_resopnse( [], ApiStatus::CODE_20001,'问题原因必须',ApiSubCode::Retrun_Error_Reason_id,'');
            return;
        }
        /*****************依赖服务************/
        $this->order = $this->load->service('order2/order');
        //开启事务
        $this->order->startTrans();
        $where['order_no'] = $params['order_no'];
        //获取订单信息
        $order = $this->order->get_order_info($where,['lock'=>true]);
        if ($order ===false) {
            api_resopnse( [], ApiStatus::CODE_50003,'');
            return;
        }
        if ($order['user_id'] != $this->userId) {
            api_resopnse( [], ApiStatus::CODE_50003,'');
            return;
        }
        if ($order['payment_status'] == zuji\order\PaymentStatus::PaymentSuccessful || $order['payment_status'] == oms\state\State::FundsAuthorized) {
            api_resopnse( [], ApiStatus::CODE_50003,'该订单已支付,请联系客服取消订单');
            return;
        }
        $orderObj = new oms\Order($order);
        if(!$orderObj->allow_to_cancel_order()){
            api_resopnse( [], ApiStatus::CODE_50003,'该订单状态不支持取消订单');
            return;
        }
        //取消操作记录状态流并插入日志
        if($params['reason_id'] ==0){
            $beizhu= $params['reason_text'];
        }else{
            $beizhu=\zuji\order\Reason::$_ORDER_QUESTION[\zuji\order\Reason::ORDER_CANCEL][$params['reason_id']];
        }
        $this->log($orderObj,"取消订单",$beizhu);
        $order['reason_id'] = $params['reason_id'];
        $order['reason_text'] = $params['reason_text'];
        $ret = $orderObj->cancel_order($order);
        if(!$ret){
            //事务回滚
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50000,'订单取消失败');
            return;
        }
        //提交事务
        $this->order->commit();
        //取消订单发送短信
        \zuji\sms\SendSms::cancel_order([
            'mobile' => $order['mobile'],
            'orderNo' => $order['order_no'],
            'realName' => $order['realname'],
            'goodsName' => $order['goods_name'],
        ]);
        api_resopnse( [], ApiStatus::CODE_0);
    }
    //订单确认收货
    public function delivery(){
        $params = $this->params;
        //验证参数
        $params = filter_array($params,[
            'order_no' => 'required',
        ]);
        if(empty($params['order_no'])){
            api_resopnse( [], ApiStatus::CODE_50003,'请求异常',ApiSubCode::Order_Error_Order_no,'参数错误');
            return;
        }
        /*****************依赖服务************/
        $this->order   = $this->load->service('order2/order');
        $this->delivery = $this->load->service('order2/delivery');
        //开启事务
        $this->order->startTrans();

        //获取订单信息
        $where['order_no'] = $params['order_no'];
        $order_info = $this->order->get_order_info($where,['lock'=>true]);
        //验证订单
        if(!$order_info || $order_info['user_id'] != $this->userId){
            api_resopnse( [], ApiStatus::CODE_50003,'禁止操作','','');
            return;
        }

		// 查询发货单信息
        $delivery_info = $this->delivery->get_info($order_info['delivery_id']);
        if(!$delivery_info){
            api_resopnse( [], ApiStatus::CODE_50003,'发货单错误', '', '不存在关联的发货单信息');
            return;
        }
        // 对象
        $orderObj = new oms\Order($order_info);
        if(!$orderObj->allow_to_sign_delivery()){
            api_resopnse( [], ApiStatus::CODE_50003,'该订单状态不能确认收货');
            return;
        }
        $this->log($orderObj,"确认收货成功");

        $ret = $orderObj->sign_delivery($order_info);
        if(!$ret){
            //事务回滚
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50000,'确认收货失败');
            return;
        }
        //提交事务
        $this->order->commit();

        $this->service_service = $this->load->service('order2/service');
        $service_info =$this->service_service->get_info_by_order_id($order_info['order_id']);
        $this->instalment_table = $this->load->table('order2/order2_instalment');
        $instalment_info =$this->instalment_table->get_order_list(['order_id'=>$order_info['order_id']]);

        //确认收货发送短信
        \zuji\sms\SendSms::confirmed_delivery([
            'mobile' => $order_info['mobile'],
            'orderNo' => $order_info['order_no'],
            'realName' => $order_info['realname'],
            'goodsName' => $order_info['goods_name'],
            'zuQi' => $order_info['zuqi'],
            'zuQiType' => $order_info['zuqi_type'],
            'beginTime' => date("Y-m-d H:i:s",$service_info['begin_time']),
            'endTime' => date("Y-m-d H:i:s",$service_info['end_time']),
            'zuJin' => $order_info['zujin'],
            'createTime' => $instalment_info[0]['term'],
        ]);
        //确认收货发送消息通知
        //通过用户id查询支付宝用户id
        $this->certification_alipay = $this->load->service('member2/certification_alipay');
        $to_user_id = $this->certification_alipay->get_last_info_by_user_id($order_info['user_id']);
        if(!empty($to_user_id['user_id'])){
            $MessageSingleSendWord = new \alipay\MessageSingleSendWord( $to_user_id['user_id'] );
            $message_arr = [
                'goods_name'=>$order_info['goods_name'] ,
                'fast_mail_company' => '顺丰速运',
                'fast_mail_no' => $delivery_info['wuliu_no'],
                'sing_time' => date('Y-m-d H:i:s'),
                'order_no'=>$order_info['order_no'],
            ];
            $b = $MessageSingleSendWord->SignIn( $message_arr );
            if( $b === false ){
                api_resopnse( [], ApiStatus::CODE_20001,'',$MessageSingleSendWord->getError());
                return;
            }
        }
        api_resopnse( [], ApiStatus::CODE_0);
    }
    //申请退货
    public function return_apply(){
        $params = $this->params;
        //验证参数
        $data = filter_array($params,[
            'order_no' => 'required',
            'loss_type' => 'required',
            'reason_id' => 'required|is_id',
            'reason_text' => 'required',
        ]);
        if (empty($data['order_no']) ) {
            api_resopnse( [], ApiStatus::CODE_20001,'订单编号必须', ApiSubCode::Order_Error_Order_no,'');
            return;
        }
        if($data['reason_id']){
            $data['reason_text'] = "";
        }
        if (empty($data['reason_id']) && empty($data['reason_text'])) {
            api_resopnse( [], ApiStatus::CODE_20001,'退货原因必须', ApiSubCode::Retrun_Error_Reason_id,'');
            return;
        }
        //验证是全新未拆封还是已拆封已使用
        if ($data['loss_type']!=zuji\order\ReturnStatus::OrderGoodsNew && $data['loss_type']!=zuji\order\ReturnStatus::OrderGoodsIncomplete) {
            api_resopnse( [], ApiStatus::CODE_20001,'商品损耗类型必须', ApiSubCode::Retrun_Error_Loss_type,'');
            return;
        }
        /*****************依赖服务************/
        $this->order   = $this->load->service('order2/order');
        $this->return  = $this->load->service('order2/return');
        //开启事务
        $this->order->startTrans();
        //获取订单详情
        $where['order_no'] = $data['order_no'];
        $order_info = $this->order->get_order_info($where,['lock'=>true]);
        if(empty($order_info)) {
            api_resopnse( $order_info, ApiStatus::CODE_50003,'没有找到该订单', ApiSubCode::Order_Error_Order_no,'' );
            return;
        }
        if($order_info['return_status'] == zuji\order\ReturnStatus::ReturnWaiting) {
            api_resopnse( [], ApiStatus::CODE_50003,  '已提交退货申请,请等待审核', ApiSubCode::Order_Error_Order_no,'');
            return;
        }

        // 发货单对象
        $orderObj = new oms\Order($order_info);
        if(!$orderObj->allow_to_apply_for_return()){
            api_resopnse( [], ApiStatus::CODE_50003,'该订单不能申请退货');
            return;
        }

        $order_info['loss_type'] = $data['loss_type'];
        $order_info['reason_id'] = $data['reason_id'];
        $order_info['reason_text'] = $data['reason_text'];
        $order_info['address_id'] = zuji\order\Address::AddressOne;

        $this->log($orderObj,"申请退货成功");
        $ret = $orderObj->apply_for_return($order_info);
        if(!$ret){
            //事务回滚
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50000,'申请退货失败');
            return;
        }
        //发送邮件 -----begin
        $this->send_email("退货申请",'订单编号：'.$data['order_no']." 用户已申请退货，请处理。");
        //提交事务
        $this->order->commit();

        //申请退货发送短信
        \zuji\sms\SendSms::apply_return([
            'mobile' => $order_info['mobile'],
            'orderNo' => $order_info['order_no'],
            'realName' => $order_info['realname'],
            'goodsName' => $order_info['goods_name'],
        ]);
        api_resopnse( [], ApiStatus::CODE_0);
    }
    //退货物流上传
    public function return_logistics(){
        $params = $this->params;
        $params = filter_array($params,[
            'order_no'          => 'required',
            'wuliu_channel_id'  => 'required',
            'wuliu_no'          =>'required'
        ]);

        if(empty($params['order_no'])){
            api_resopnse( [], ApiStatus::CODE_20001,'order_no必须填', ApiSubCode::Order_Error_Order_no,'' );
            return;
        }
        if(empty($params['wuliu_channel_id'])){
            api_resopnse( [], ApiStatus::CODE_20001,'wuliu_no必须填', ApiSubCode::Retrun_Error_Wuliu_channel_id,'' );
            return;
        }
        if(empty($params['wuliu_no'])){
            api_resopnse( [], ApiStatus::CODE_20001,'wuliu_no必须填', ApiSubCode::Retrun_Error_Wuliu_no,'' );
            return;
        }

        /*****************依赖服务************/
        $this->receive  = $this->load->service('order2/receive');
        $this->return  = $this->load->service('order2/return');
        $this->order  = $this->load->service('order2/order');

        //获取订单详情
        $where['order_no'] = $params['order_no'];
        $order_info = $this->order->get_order_info($where);
        //获取订单信息
        $return_info = $this->return->get_info_by_order_no($params['order_no']);
        if(!$order_info){
            api_resopnse( [], ApiStatus::CODE_50003,'未找到该订单');
            return;
        }
        if(!$return_info){
            api_resopnse( [], ApiStatus::CODE_50003,'无退货单');
            return;
        }
        if($return_info['user_id']!=$this->userId){
            api_resopnse( [], ApiStatus::CODE_20001,'非当前用户');
            return;
        }
        if($return_info['return_status'] != zuji\order\ReturnStatus::ReturnAgreed && $return_info['return_status'] != zuji\order\ReturnStatus::ReturnHuanhuo){
            api_resopnse( [], ApiStatus::CODE_50000,'该订单未通过审核,不能上传物流单号');
            return;
        }

        $receive = $this->receive->get_info_by_order_id($order_info['order_id']);
        if(!$receive){
            api_resopnse( [], ApiStatus::CODE_50000,'退货单出错');
            return;
        }
        if($receive['wuliu_no']){
            api_resopnse( [], ApiStatus::CODE_50000,'已上传物流单号');
            return;
        }

        // 订单对象
        $orderObj = new oms\Order($order_info);

        if(!$orderObj->allow_to_upload_wuliu()){
            api_resopnse( [], ApiStatus::CODE_50003,'该订单不能上传');
            return;
        }
        //保存信息
        $data['receive_id']         = $receive['receive_id'];
        $data['wuliu_no']          = $params['wuliu_no'];
        $data['wuliu_channel_id']  = $params['wuliu_channel_id'];

        $this->plat_log($orderObj,"用户上传物流单号成功");

        $ret = $orderObj->upload_wuliu($data);

        if(!$ret){
            api_resopnse( [], ApiStatus::CODE_50000,'上传物流失败');
            return;
        }
        api_resopnse( [], ApiStatus::CODE_0);

    }

    //退货记录列表查询
    public function return_query(){
        $params = $this->params;
        $where['user_id'] = $this->userId;

        /*****************依赖服务************/
        $this->order   = $this->load->service('order2/order');
        $this->sku_serve = $this->load->service('goods2/goods_sku');
        $this->return  = $this->load->service('order2/return');

        //获取退货单数据条数
        $count = $this->return->get_count($where);
        if($count<1){
            api_resopnse( [], ApiStatus::CODE_0,'无相关退货单');
            return;
        }
        //选择性分页
        if(intval($params['page'])>0){
            $additional['size']   = 20;
            $additional['page'] = intval($params['page']);
        }
        else
        {
            $additional['size']   = $count;
            $additional['page']  = 1;
        }
        //获取退货单列表数据
        $return_list = $this->return->get_list($where,$additional);
        //订单id拆分
        $order_ids =  array_unique(array_column($return_list,"order_id"));
        krsort($order_ids);
        $order_where['order_id'] = $order_ids;
        $additional['goods_info'] = true;
        //获取订单信息
        $order_list = $this->order->get_order_list($order_where,$additional);
        $order_list = $this->arrayKey($order_list,"order_id");
        //获取商品缩略图
        $goods_info =array_column($order_list, 'goods_info');
        $sku_ids = array_unique(array_column($goods_info,'sku_id'));
        $sku_where['sku_id'] = ['in',implode(',',$sku_ids)];
        $sku_goods = $this->sku_serve->api_get_list($sku_where,'sku_id,thumb');
        $sku_goods = $this->arrayKey($sku_goods,"sku_id");
        //退货状态
        $return_status = zuji\order\ReturnStatus::getStatusList();
        $order_return_list = [];
        foreach($return_list as $key=>$val){
            $order_info =  $order_list[$val['order_id']];
            $order_return_list[$key]['order_no'] = $val['order_no'];
            $order_return_list[$key]['status_name'] = $return_status[$val['return_status']];
            $order_return_list[$key]['sku_name'] =$order_info['goods_info']['sku_name'];
            $specs = $order_info['goods_info']['specs'];
            $specs_value = array_column($specs,"value");
            $order_return_list[$key]['specs'] = implode('|',$specs_value);
            $order_return_list[$key]['thumb'] = $sku_goods[$order_info['goods_info']['sku_id']]['thumb'];
            $order_return_list[$key]['return_time'] = date("Y-m-d H:i:s");
        }
        api_resopnse( $order_return_list, ApiStatus::CODE_0 ,'获取成功');
    }

    //退货结果查看
    public function return_get(){

        $params = $this->params;
        $data = filter_array($params,[
            'order_no' => 'required',
        ]);
        if(!$data['order_no']){
            api_resopnse( [], ApiStatus::CODE_20001,'订单编号必须', ApiSubCode::Order_Error_Order_no,'');
            return;
        }
        /*****************依赖服务************/
        $this->order   = $this->load->service('order2/order');
        $this->return  = $this->load->service('order2/return');

        //获取订单详情
        $where['order_no'] = $data['order_no'];
        $additional['goods_info'] = true;
        $order_info = $this->order_detail($where,$additional);

        if(!$order_info){
            api_resopnse( [], ApiStatus::CODE_50003,'未找到该订单');
            return;
        }
        //退货单
        $return_info = $this->return->get_info_by_order_no($params['order_no']);
        if(!$return_info){
            $result['order_no'] = $order_info['order_no'];
            $result['goods_name'] = $order_info['sku_info']['sku_name'];
            $result['goods_images'] = $order_info['sku_info']['thumb'];
            $result['goods_spec'] = $order_info['sku_info']['specs'];
            $result['day'] = $order_info['day'];
            $result['return_status'] = 1;
            $result['logistics_upload'] = false;
            $result['service_key']   = "init";
            api_resopnse($result, ApiStatus::CODE_0 ,'获取成功');
            return;
        }

        $logistics_upload = true;
        //确认服务状态
        if($order_info['status'] == oms\state\State::OrderReturnChecking){
            $return_status = 1;
            $service_name = "待审核";
            $service_key = "check";
        }
        elseif($order_info['status'] == oms\state\State::OrderReturning){


            $this->receive = $this->load->service("order2/receive");
            $delivery_info = $this->receive->get_info_by_order_id($order_info['order_id']);
            if($delivery_info['wuliu_no']){
                $service_name="平台待收货";
                $return_status = 3;
                $service_key = "platform_receive";
            }
            else{
                $return_status = 2;
                $service_name = "审核通过";
                $service_key = "check_end";
                $logistics_upload = false;
            }
        }
        elseif($order_info['status'] == oms\state\State::OrderReceived){
            $service_name="平台已收货";
            $return_status = 3;
            $service_key = "platform_receive";
        }
        elseif($order_info['status'] == oms\state\State::OrderEvaluationQualified){
            $service_name="检测合格";
            $return_status = 3;
            $service_key = "platform_receive";
        }
        elseif($order_info['status'] == oms\state\State::OrderEvaluationUnqualified){
            $service_name="检测不合格";
            $return_status = 3;
            $service_key = "detecting";
        }
        elseif($order_info['status'] == oms\state\State::OrderHuanhuoing){
            $service_name="换货中";
            $return_status = 4;
            $service_key = "refunding";
        }
        elseif($order_info['status'] == oms\state\State::OrderHuijiing){
            $service_name="回寄中";
            $return_status = 3;
            $service_key = "detecting";
        }
        elseif($order_info['status'] == oms\state\State::OrderRefunding){
            $service_name="退款中";
            $return_status = 4;
            $service_key = "refunded";
        }
        elseif($order_info['status'] == oms\state\State::OrderRefunded){
            $service_name="已退款";
            $return_status = 4;
            $service_key = "refunded";
        }
        elseif($order_info['status'] == oms\state\State::OrderRefunded){
            $service_name="退款失败";
            $return_status = 5;
            $service_key = "refund_error";
        }
        $reason_return =zuji\order\Reason::$_ORDER_QUESTION[zuji\order\Reason::ORDER_RETURN];
        $reason_name  = $return_info['reason_id']?$reason_return[$return_info['reason_id']]:$return_info['reason_text'];

        $result['order_no'] = $order_info['order_no'];
        $result['goods_name']   = $order_info['sku_info']['sku_name'];
        $result['goods_images'] = $order_info['sku_info']['thumb'];
        $result['goods_spec']    = $order_info['sku_info']['specs'];
        $result['day']=$order_info['day'];
        $result['reason_text']    = $reason_name;
        $result['return_status'] = $return_status;
        $result['service_name'] = $service_name;
        $result['service_key']   = $service_key;
        $result['logistics_upload'] = $logistics_upload;
        $result['return_time']  = date("Y-m-d H:i:s",$return_info['create_time']);
        api_resopnse($result, ApiStatus::CODE_0 ,'获取成功');
        return;
    }

}
