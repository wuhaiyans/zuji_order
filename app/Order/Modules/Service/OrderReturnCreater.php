<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use App\Lib\Warehouse\Receive;
use \App\Lib\Common\SmsApi;
use App\Order\Modules\Inc\OrderGoodStatus;
use Illuminate\Support\Facades\DB;
use \App\Order\Modules\Inc\ReturnStatus;
use \App\Order\Modules\Inc\OrderCleaningStatus;
use \App\Order\Modules\Inc\OrderStatus;
use \App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Repository\OrderReturnRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderClearingRepository;
use App\Lib\Warehouse\Delivery;
use App\Lib\Warehouse\Logistics;
class OrderReturnCreater
{
    protected $orderReturnRepository;
    public function __construct(orderReturnRepository $orderReturnRepository)
    {
        $this->orderReturnRepository = $orderReturnRepository;
    }
    public function get_return_info($data){
        return $this->orderReturnRepository->get_return_info($data);
    }
    //添加退换货数据
    /*goods_no=array('232424','23123123')商品编号   必选
     *order_no  商品编号  必选
     */
    public function add($params){
        if(empty($params['goods_no'])){
            return ApiStatus::CODE_40000;//商品编号不能为空
        }
        if(empty($params['order_no'])){
            return ApiStatus::CODE_33003;
        }
        $OrderRepository=new OrderRepository();
        foreach($params['goods_no'] as $k=>$v){
            $goods_info= $this->orderReturnRepository->getGoodsList($v,$params['order_no']);//获取商品信息
            $data[$k]['goods_no']=$v;
            $data[$k]['order_no']=$params['order_no'];
            $data[$k]['business_key']=$params['business_key'];
            $data[$k]['loss_type']=$params['loss_type'];
            $data[$k]['reason_id']=$params['reason_id'];
            $data[$k]['reason_text']=$params['reason_text'];
            $data[$k]['user_id']=$params['user_id'];
            $data[$k]['pay_amount']=isset($goods_info['price'])?$goods_info['price']:0;
            $data[$k]['status']=ReturnStatus::ReturnCreated;
            $data[$k]['refund_no']=createNo('2');
            $data[$k]['create_time']=time();
        }
        //开启事务
        DB::beginTransaction();
        $create_return=$this->orderReturnRepository->add($data);
        if(!$create_return){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_34007;//创建失败
        }
        $return_order=$OrderRepository->order_update($params['order_no']);//修改订单状态
        if(!$return_order){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33007;//修改订单状态失败
        }
        $return_goods = $this->orderReturnRepository->goods_update_status($params);//修改商品状态
        if(!$return_goods){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33009;//修改商品状态失败
        }
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;

    }
    /**
     * 退换货管理员审核 --同意
     * @$params

     */
    public function agree_return($params){
        $OrderClearingRepository=new OrderClearingRepository();
        $OrderRepository=new OrderRepository();
        $order_info=$OrderRepository->getOrderInfo($params);
        if(!$order_info){
            return ApiStatus::CODE_34005;//查无此订单
        }
        //开启事务
        DB::beginTransaction();
        foreach($params['agree'] as $k=>$v){
            $params_data[$k]['order_no']=$params['order_no'];
            $params_data[$k]['goods_no']=$v['goods_no'];
            $params_data[$k]['remark']=$v['remark'];
            $params_data[$k]['reason_key']=$v['reason_key'];
            $res= $this->orderReturnRepository->update_return($params_data[$k]);
            if(!$res){
                //事务回滚
                DB::rollBack();
                return ApiStatus::CODE_33008;//更新审核状态失败
            }
            if($params['business_key']==ReturnStatus::OrderHuanHuo){
                //获取商品信息
                $goods_info= $this->orderReturnRepository->get_goods_info($params_data[$k]);
                if(!$goods_info){
                    //事务回滚
                    DB::rollBack();
                    return ApiStatus::CODE_40000;//商品信息错误
                }
            }
            if($params['business_key']==ReturnStatus::OrderTuiHuo){
                //创建退款清单
                $create_data['order_no']=$params['order_no'];

                $pay_result=$this->orderReturnRepository->get_pay_no(1,$params['order_no']);
                if(!$pay_result){
                    return ApiStatus::CODE_50004;//订单未支付
                }
                if($pay_result['payment_no']){
                    $create_data['out_payment_no']=$pay_result['payment_no'];
                }
                if($pay_result['fundauth_no']){
                    $create_data['out_auth_no']=$pay_result['fundauth_no'];
                }
                $create_data['order_type']=$order_info['order_type'];//订单类型
                $create_data['business_type']=OrderCleaningStatus::businessTypeRefund;
                $create_data['business_no']=$params['order_no'];
                $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
                $create_data['deposit_unfreeze_amount']=$order_info['goods_yajin'];//退还押金金额
                $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
                $create_data['refund_amount']=$order_info['order_amount'];//退款金额（租金）
                $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                $create_data['user_id']=$order_info['user_id'];
                $create_clear= $OrderClearingRepository->createOrderClean($create_data);//创建退款清单
                if(!$create_clear){
                    //事务回滚
                    DB::rollBack();
                    return ApiStatus::CODE_34008;//创建退款清单失败
                }
                //修改退款状态为退款中
                $data['status']=ReturnStatus::ReturnTui;
                $where[]=['order_no','=',$params['order_no']];
                $where[]=['goods_no','=',$v['goods_no']];
                $tui_result=$this->orderReturnRepository->is_qualified($where,$data);
                if(!$tui_result){
                    //事务回滚
                    DB::rollBack();
                    return ApiStatus::CODE_33008;
                }
            }


        }
        if(isset($goods_info)){
            foreach($goods_info as $k=>$v){
                $receive_data[$k] =[
                    'goods_no' => $goods_info[$k]->goods_no,
                    'serial_no' => $goods_info[$k]->serial_number,
                    'quantity' => $goods_info[$k]->quantity,
                    'imei1'     =>$goods_info[$k]->imei1,
                    'imei2'     =>$goods_info[$k]->imei2,
                    'imei3'     =>$goods_info[$k]->imei3,

                ];
            }
            $create_receive= Receive::create($params['order_no'],$params['business_key'],$receive_data);//创建待收货单
            if(!$create_receive){
                //事务回滚
                DB::rollBack();
                return ApiStatus::CODE_34003;//创建待收货单失败
            }

        }


        //申请退货同意发送短信
        /*$b =SmsApi::sendMessage($order_info['user_mobile'],'SMS_113455999',[
               'realName' =>$order_info['realname'],
               'orderNo' => $params['order_no'],
               'goodsName' => ,
               'shoujianrenName' =>$order_info['name'],
               'returnAddress' => "test",
               'serviceTel'=>OldInc::Customer_Service_Phone,
           ],$params['order_no']);*/
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;//成功

    }
    /**
     * 管理员审核 --同意
     *
     *
     * array(
     *      'order_no' =>'',        //【必须】订单ID
     *      'remark'=>'',         //【必须】审核备注
     *      'status'=>''         //【必须】审核状态
     *
     * )
     *
     * @return boolean  true :插入成功  false:插入失败
     *
     *
     */
    /*  public function agree_return($params){
          $OrderClearingRepository=new OrderClearingRepository();
          $OrderRepository=new OrderRepository();
          //开启事务
          DB::beginTransaction();
          if(isset($params['agree'])){
              foreach($params['agree'] as $k=>$v){
                  $params_data[$k]['order_no']=$params['order_no'];
                  $params_data[$k]['goods_no']=$v['goods_no'];
                  $params_data[$k]['remark']=$v['remark'];
                  $params_data[$k]['reason_key']=$v['reason_key'];
                  $res= $this->orderReturnRepository->update_return($params_data[$k]);
                  if(!$res){
                      //事务回滚
                      DB::rollBack();
                      return ApiStatus::CODE_33008;//更新审核状态失败
                  }
                  $goods_result=$this->orderReturnRepository->goods_update($params_data[$k]);//修改商品状态
                  if(!$goods_result){
                      //事务回滚
                      DB::rollBack();
                      return ApiStatus::CODE_33009;
                  }
                  //获取商品信息
                  $goods_info= $this->orderReturnRepository->get_goods_info($params_data[$k]);
                  if(!$goods_info){
                      //事务回滚
                      DB::rollBack();
                      return ApiStatus::CODE_40000;//商品信息错误
                  }
              }

          }
          //获取用户订单信息
         // $params_where['order_no']=$params['order_no'];
          $order_info=$OrderRepository->getOrderInfo($params);
          if(!$order_info){
              return ApiStatus::CODE_34005;//查无此订单
          }
          $where[]=['order_no','=',$params['order_no']];
          if($params['business_key']==ReturnStatus::OrderTuiKuan){
              //创建退款清单
              $create_data['order_no']=$params['order_no'];
              $pay_result=$this->orderReturnRepository->get_pay_no($params['business_key'],$params['order_no']);
              if(!$pay_result){
                  return ApiStatus::CODE_50004;//订单未支付
              }
              if($pay_result['payment_no']){
                  $create_data['out_payment_no']=$pay_result['payment_no'];
              }
              if($pay_result['fundauth_no']){
                  $create_data['out_auth_no']=$pay_result['fundauth_no'];
              }
              $create_data['order_type']=$order_info['order_type'];//订单类型
              $create_data['business_type']=OrderCleaningStatus::businessTypeRefund;
              $create_data['business_no']=$params['order_no'];
              $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
              $create_data['deposit_unfreeze_amount']=$order_info['goods_yajin'];//退还押金金额
              $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
              $create_data['refund_amount']=$order_info['order_amount'];//退款金额（租金）
              $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
              $create_data['user_id']=$order_info['user_id'];
              $create_clear= $OrderClearingRepository->createOrderClean($create_data);//创建退款清单
              if(!$create_clear){
                  //事务回滚
                  DB::rollBack();
                  return ApiStatus::CODE_34008;//创建退款清单失败
              }
              //修改退款状态为退款中
              $data['status']=ReturnStatus::ReturnTui;
              $tui_result=$this->orderReturnRepository->is_qualified($where,$data);
              if(!$tui_result){
                  //事务回滚
                  DB::rollBack();
                  return ApiStatus::CODE_33008;
              }
              $goodsresult=$this->orderReturnRepository->goodsupdate($params);//修改商品状态
              if(!$goodsresult){
                  return ApiStatus::CODE_33009;
              }*/
    /*$b =SmsApi::sendMessage($order_info['user_mobile'],'SMS_113455999',[
         'realName' =>$order_info['realname'],
         'orderNo' => $params['order_no'],
         'goodsName' => ,
         'shoujianrenName' =>$order_info['name'],
         'returnAddress' => "test",
         'serviceTel'=>OldInc::Customer_Service_Phone,
     ],$params['order_no']);*/
    //提交事务
    /*DB::commit();
    return ApiStatus::CODE_0;
}
foreach($goods_info as $k=>$v){
    $receive_data[$k] =[
        'goods_no' => $goods_info[$k]->goods_no,
        'serial_no' => $goods_info[$k]->serial_number,
        'quantity' => $goods_info[$k]->quantity,
        'imei1'     =>$goods_info[$k]->imei1,
        'imei2'     =>$goods_info[$k]->imei2,
        'imei3'     =>$goods_info[$k]->imei3,

    ];
}
$create_receive= Receive::create($params['order_no'],$params['business_key'],$receive_data);//创建待收货单
if(!$create_receive){
    //事务回滚
    DB::rollBack();
    return ApiStatus::CODE_34003;//创建待收货单失败
}*/
    //申请退货同意发送短信
    /*$b =SmsApi::sendMessage($order_info['user_mobile'],'SMS_113455999',[
           'realName' =>$order_info['realname'],
           'orderNo' => $params['order_no'],
           'goodsName' => ,
           'shoujianrenName' =>$order_info['name'],
           'returnAddress' => "test",
           'serviceTel'=>OldInc::Customer_Service_Phone,
       ],$params['order_no']);*/
    //提交事务
    /*DB::commit();
    return ApiStatus::CODE_0;//成功

}*/

    /**
     *退换货 管理员审核 --不同意
     * @param int $id 【必选】退货单ID
     * @param array $params   【必选】退货单审核信息
     * array(
     *      'order_no' =>'',        // 【必须】订单编号
     *	    'remark' => ''，         // 【可选】 管理员审批内容
     * )
     * @return boolean  true :插入成功  false:插入失败
     *
     *
     */

    public function deny_return($params){
        $param = filter_array($params,[
            'order_no' => 'required',
            'business_key' => 'required',
        ]);
        if(count($param)<2){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $OrderRepository=new OrderRepository();
        //开启事务
        DB::beginTransaction();
        foreach($params['disagree'] as $k=>$v) {
            $params_data[$k]['order_no'] = $params['order_no'];
            $params_data[$k]['goods_no'] = $v['goods_no'];
            $params_data[$k]['remark'] = $v['remark'];
            $params_data[$k]['reason_key'] = $v['reason_key'];
            $res = $this->orderReturnRepository->deny_return($params_data[$k]);//修改退货单状态
            if(!$res){
                //事务回滚
                DB::rollBack();
                return ApiStatus::CODE_33008;//更新审核状态失败
            }
            $deny_goods=$this->orderReturnRepository->deny_goods_update($params_data[$k]);//修改商品状态
            if(!$deny_goods){
                //事务回滚
                DB::rollBack();
                return ApiStatus::CODE_33009;//修改商品信息失败
            }
        }
        $where[]=['order_no','=',$params['order_no']];
        $where[]=['business_key','=',$params['business_key']];
        //获取退换货的订单的数据
         $return_info=$this->orderReturnRepository->getReturnInfo($where);
         foreach($return_info as $k=>$v){
            $status[$k]=$return_info[$k]['status'];
         }
        if(!in_array(ReturnStatus::ReturnCreated,$status) && !in_array(ReturnStatus::ReturnAgreed,$status)&& !in_array(ReturnStatus::ReturnReceive,$status) && !in_array(ReturnStatus::ReturnTui,$status) && !in_array(ReturnStatus::ReturnTuiHuo,$status) && !in_array(ReturnStatus::ReturnTuiKuan,$status)){
            //如果部分审核同意，订单为冻结状态
            $goods_result= $OrderRepository->deny_update($params['order_no']);//修改订单冻结状态
            if(!$goods_result) {
                //事务回滚
                DB::rollBack();
                return ApiStatus::CODE_33007;//更新订单冻结状态失败
            }
        }


        //获取订单信息
      //  $data['order_no']=$params['order_no'];
       // $order_info= $OrderRepository->getOrderInfo($data);
        //获取商品信息
       // $goods_info= $this->orderReturnRepository->get_goods_info($params);

     //   if(!$goods_info){
       //     return ApiStatus::CODE_40000;//商品信息错误
      //  }

        //申请退货拒绝发送短信
        // SmsApi::sendMessage($user_info['mobile'],1,array());
        /* $b = SmsApi::sendMessage('13020059043','hsb_sms_d284d',[
             'realName' =>$order_info[0]->realname,
             'orderNo' =>$params['order_no'],
             'goodsName' => $goods_info[0]['good_name'],
             'serviceTel'=>OldInc::Customer_Service_Phone,
         ],$params['order_no']);*/
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;//成功
    }

    /**
     * 订单退款审核同意
     * @param $params
     *
     */
    public function  refundReplyAgree($params){
        $OrderClearingRepository=new OrderClearingRepository();
        $OrderRepository=new OrderRepository();
        //开启事务
        DB::beginTransaction();
        //获取用户订单信息
        $order_info=$OrderRepository->getOrderInfo($params);
        if(!$order_info){
            return ApiStatus::CODE_34005;//查无此订单
        }
        $where[]=['order_no','=',$params['order_no']];
        //获取订单支付方式
        if($order_info['order_status']!=OrderStatus::BUSINESS_RETURN && $order_info['order_status']!=OrderStatus::OrderPayed){
           return ApiStatus::CODE_34001;//此订单不符合规则
        }
        //获取退货单号
        $return_no=$this->orderReturnRepository->get_type($where);
        //创建退款清单
        $create_data['order_no']=$params['order_no'];
        $business_key=ReturnStatus::OrderTuiKuan;
        $pay_result=$this->orderReturnRepository->get_pay_no($business_key,$params['order_no']);
        if(!$pay_result){
            return ApiStatus::CODE_50004;//订单未支付
        }
        $create_data['order_type']=$order_info['order_type'];//订单类型
        $create_data['business_type']=OrderCleaningStatus::businessTypeRefund;//业务类型
        $create_data['business_no']=$return_no['refund_no'];//业务编号
        $create_data['user_id']=$order_info['user_id'];
        //退款：直接支付
        if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::FlowerStagePay ||$order_info['pay_type']==\App\Order\Modules\Inc\PayInc::UnionPay){
            $create_data['out_payment_no']=$pay_result['payment_no'];//支付编号
            $create_data['order_amount']=$order_info['order_amount']+$order_info['order_insurance'];//退款金额=订单实际支付总租金+意外险总金额
            if($create_data['order_amount']>0){
                $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
            }

        }
        //退款：代扣+预授权
        if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::FlowerDepositPay){
            $create_data['out_payment_no']=$pay_result['payment_no'];//支付编号
            $create_data['out_auth_no']=$pay_result['fundauth_no'];//预授权编号
           // $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
            $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
            $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
            $create_data['order_amount']=$order_info['order_amount']+$order_info['order_insurance'];//退款金额=订单实际支付总租金+意外险总金额
            $create_data['auth_unfreeze_amount']=$order_info['order_yajin'];//订单实际支付押金
            if($create_data['order_amount']>0 && $create_data['auth_unfreeze_amount']>0){
                $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
            }

        }
        //退款：预授权
        if($order_info['pay_type']==\App\Order\Modules\Inc\PayInc::WithhodingPay){
            $create_data['out_auth_no']=$pay_result['fundauth_no'];
            //$create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
            $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
            $create_data['auth_unfreeze_amount']=$order_info['order_yajin'];//订单实际支付押金
            if($create_data['auth_unfreeze_amount']>0){
                $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
            }
        }

        //创建退款清单
     /*   $create_data['order_no']=$params['order_no'];
        $business_key=ReturnStatus::OrderTuiKuan;
        $pay_result=$this->orderReturnRepository->get_pay_no($business_key,$params['order_no']);
        if(!$pay_result){
            return ApiStatus::CODE_50004;//订单未支付
        }
        if($pay_result['payment_no']){
            $create_data['out_payment_no']=$pay_result['payment_no'];
        }
        if($pay_result['fundauth_no']){
            $create_data['out_auth_no']=$pay_result['fundauth_no'];
        }
        $create_data['order_type']=$order_info['order_type'];//订单类型
        $create_data['business_type']=OrderCleaningStatus::businessTypeRefund;
        $create_data['business_no']=$params['order_no'];
        $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
        $create_data['deposit_unfreeze_amount']=$order_info['goods_yajin'];//退还押金金额
        $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
        $create_data['refund_amount']=$order_info['order_amount'];//退款金额（租金）
        $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
        $create_data['user_id']=$order_info['user_id'];
        */
        $create_clear= $OrderClearingRepository->createOrderClean($create_data);//创建退款清单
        if(!$create_clear){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_34008;//创建退款清单失败
        }
        //修改退款状态为退款中
        $data['status']=ReturnStatus::ReturnTui;
        $data['remark']=$params['remark'];
        $tui_result=$this->orderReturnRepository->is_qualified($where,$data);
        if(!$tui_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33008;
        }

        /*$b =SmsApi::sendMessage($order_info['user_mobile'],'SMS_113455999',[
             'realName' =>$order_info['realname'],
             'orderNo' => $params['order_no'],
             'goodsName' => ,
             'shoujianrenName' =>$order_info['name'],
             'returnAddress' => "test",
             'serviceTel'=>OldInc::Customer_Service_Phone,
         ],$params['order_no']);*/
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;


    }
    /**
     * 订单退款审核拒绝
     * @param $params
     *
     */
    public function refundReplyDisagree($params){
        $OrderRepository=new OrderRepository();
        $res = $this->orderReturnRepository->deny_return($params);//修改退货单状态
        if(!$res){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33008;//更新审核状态失败
        }
        $deny_goods=$this->orderReturnRepository->deny_goods_update($params);//修改商品状态
        if(!$deny_goods){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33009;//修改商品信息失败
        }
        $goods_result= $OrderRepository->deny_update($params['order_no']);//修改订单冻结状态
        if(!$goods_result) {
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33007;//更新订单冻结状态失败
        }
    }


    //取消退货申请
    public function cancel_apply($params){
        $param = filter_array($params,[
            'order_no' => 'required',
            'user_id'  => 'required',
        ]);

        if(count($param)<2){
            return  ApiStatus::CODE_20001;
        }
        if(empty($params['goods_no'])){
            return ApiStatus::CODE_20001;
        }
        $OrderRepository=new OrderRepository();
        //查询是否存在此退货单，存在判断退货单状态是否允许取消
        $return_result = $this->orderReturnRepository->get_info_by_order_no($params);
        if($return_result){
            foreach($return_result as $k=>$v){
                if($return_result[$k]->status==ReturnStatus::ReturnReceive || $return_result[$k]->status==ReturnStatus::ReturnTuiHuo || $return_result[$k]->status==ReturnStatus::ReturnHuanHuo || $return_result[$k]->status==ReturnStatus::ReturnTuiKuan || $return_result[$k]->status==ReturnStatus::ReturnTui){
                    return ApiStatus::CODE_34006;
                }
            }

        }
        //开启事务
        DB::beginTransaction();
        $res = $this->orderReturnRepository->cancel_apply($params);
        if(!$res) {
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33008;//更新退货单状态失败
        }
        //修改商品状态
        $order_goods = $this->orderReturnRepository->cancel_goods_update($params);
        if(!$order_goods) {
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33009; //[退换货]修改商品状态失败
        }
        //修改订单冻结状态
        $freeze_type=OrderFreezeStatus::Non;
        $freeze_result=$this->orderReturnRepository->update_freeze($params,$freeze_type);
        if(!$freeze_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33007;//修改订单冻结状态失败
        }
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;//成功

    }
    //获取退款单信息
    public function get_info_by_order_no($params){
        if(empty($params['order_no'])){
            return ApiStatus::CODE_33003;//const CODE_33008 = '33003'; //[退换货]订单编号不能为空
        }
        if(empty($params['user_id'])){
            return ApiStatus::CODE_20001;
        }
        return $this->orderReturnRepository->get_info_by_order_no($params);
    }
    //上传物流单号
    public function upload_wuliu($data){
        $param = filter_array($data,[
            'order_no'           => 'required',
            'logistics_id'  => 'required',
            'logistics_name'  => 'required',
            'logistics_no'       =>'required',
            'user_id'             =>'required',
        ]);
        if(count($param)<5){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if(empty($data['goods_no'])){
            return ApiStatus::CODE_20001;
        }
        //开启事务
        DB::beginTransaction();
        $ret= $this->orderReturnRepository->upload_wuliu($data);
        if(!$ret){
            //事务回滚
            DB::rollBack();
            return aApiStatus::CODE_33008;
        }
        //上传物流单号到收货系统
        $data_params['order_no']=$data['order_no'];
        $data_params['logistics_id']=$data['logistics_id'];
        $data_params['logistics_no']=$data['logistics_no'];
        foreach($data['goods_no'] as $k=>$v){
            $data_params['goods_info'][$k]['goods_no']=$v;
            $where[]=['order_no','=',$data['order_no']];
            $where[]=['goods_no','=',$v];
            //获取商品信息
            $goods_res= $this->orderReturnRepository->getGoodsExtendInfo($where);
            $data_params['goods_info'][$k]['imei1']=$goods_res['imei1'];
            $data_params['goods_info'][$k]['imei2']=$goods_res['imei2'];
            $data_params['goods_info'][$k]['imei3']=$goods_res['imei3'];
        }
        $create_receive= Receive::updateLogistics($data_params);
        if(!$create_receive){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_34003;//创建待收货单失败
        }
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;
    }
    //获取退换货订单列表方法
    public function get_list($params)
    {
        $page = empty($params['page']) ? 1 : $params['page'];
        $size = !empty($params['size']) ? $params['size'] : config('web.pre_page_size');
        $where = [];
        if (isset($params['begin_time'])!= '') {
            $where['begin_time'] = strtotime($params['begin_time']);
        }
        if (isset($params['end_time'] )!= '') {
            $where['end_time'] = strtotime($params['end_time']);
        }

        if(isset($params['business_key']) > 0) {
            $where['business_key'] = intval($params['business_key']);
        }
        if (isset($params['keywords'])!= '') {
            if (isset($params['kw_type'])&&$params['kw_type']=='goods_name') {
                $where['goods_name'] = $params['keywords'];
            }elseif(isset($params['kw_type'])&&$params['kw_type']=='order_no') {
                $where['order_no'] = $params['keywords'];
            }elseif(isset($params['kw_type'])&&$params['kw_type']=='mobile'){
                $where['user_mobile'] = $params['keywords'];
            }
        }
        if (isset($params['return_status']) && $params['return_status'] > 0) {
            $where['status'] = intval($params['return_status']);
        }
        if (isset($params['user_id'])) {
            $where['user_id'] = $params['user_id'];
        }
        if (isset($params['reason_key'])) {
            $where['reason_key'] = $params['reason_key'];
        }
        if (isset($params['order_status'])) {
            $where['order_status'] = $params['order_status'];
        }
        if (isset($params['appid'])) {
            $where['appid'] = $params['appid'];
        }
        // 查询退货申请单
        $additional['page'] = $page;
        $additional['limit'] = $size;
        $where= $this->_parse_order_where($where);
        $data = $this->orderReturnRepository->get_list($where, $additional);
        foreach($data['data'] as $k=>$v){
            if($data['data'][$k]->status!=ReturnStatus::ReturnCreated){
                $data['data'][$k]->operate_status="false";
            }else{
                $data['data'][$k]->operate_status="true";
            }
            //业务类型
            if($data['data'][$k]->business_key==ReturnStatus::OrderTuiKuan){
                $data['data'][$k]->business_name=ReturnStatus::getBusinessName(ReturnStatus::OrderTuiKuan);//退款业务
            }elseif($data['data'][$k]->business_key==ReturnStatus::OrderTuiHuo){
                $data['data'][$k]->business_name=ReturnStatus::getBusinessName(ReturnStatus::OrderTuiHuo);//退货业务
            }elseif($data['data'][$k]->business_key==ReturnStatus::OrderHuanHuo){
                $data['data'][$k]->business_name=ReturnStatus::getBusinessName(ReturnStatus::OrderHuanHuo);//换货业务
            }
            //订单状态
            if($data['data'][$k]->order_status==OrderStatus::OrderWaitPaying){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderWaitPaying);//待支付
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderPayed){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderPayed);//已支付
            }elseif($data['data'][$k]->order_status==OrderStatus::BUSINESS_RETURN){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::BUSINESS_RETURN);//已支付
            } elseif($data['data'][$k]->order_status==OrderStatus::OrderInStock){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderInStock);//备货中
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderDeliveryed){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderDeliveryed);//已发货
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderInService){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderInService);//租用中
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderClosed){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderClosed);//关闭:已取消完成
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderRefunded){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderRefunded);//退货退款完成单
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderGivebacked){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderGivebacked);//还机完成单
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderBuyouted){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderBuyouted);//买断完成单
            }elseif($data['data'][$k]->order_status==OrderStatus::OrderChanged){
                $data['data'][$k]->order_status_name=OrderStatus::getStatusName(OrderStatus::OrderChanged);//换货完成单
            }
            //（退款、退机、换机）状态
            if($data['data'][$k]->status==ReturnStatus::ReturnCreated){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnCreated);//提交申请
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnAgreed){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnAgreed);//同意
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnDenied){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnDenied);//拒绝
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnCanceled){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnCanceled);//取消退货申请
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnReceive){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnReceive);//已收货
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnTuiHuo){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiHuo);//已退货
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnHuanHuo){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnHuanHuo);//已换货
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnTuiKuan){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiKuan);//已退款
            }elseif($data['data'][$k]->status==ReturnStatus::ReturnTui){
                $data['data'][$k]->status_name=ReturnStatus::getStatusName(ReturnStatus::ReturnTui);//退款中
            }
          //  $data['data'][$k]->c_time=date('Y-m-d H:i:s',$data['data'][$k]->c_time);
         //   $data['data'][$k]->create_time=date('Y-m-d H:i:s',$data['data'][$k]->create_time);
         //   $data['data'][$k]->complete_time=date('Y-m-d H:i:s',$data['data'][$k]->complete_time);
          //  $data['data'][$k]->wuliu_channel_name=Logistics::info($data['data'][$k]->wuliu_channel_id);//物流渠道
        }
        return $data;
    }

    /** 查询条件过滤
     * @param array $where	【可选】查询条件
     * [
     *      'return_id' => '',	//【可选】mixed 退货申请单ID，string|array （string：多个','分割）（array：ID数组）多个只支持
     *      'case_id' => '',	//【可选】string；退货原因ID
     *      'status'=>''      //【可选】int；阶段
     *      'begin_time'=>''      //【可选】int；开始时间戳
     *      'end_time'=>''      //【可选】int；  截止时间戳
     * ]
     * @return array	查询条件
     */
    public function _parse_order_where($where=[]){
        $p_where = filter_array($where, [
            'business_key' => 'required|is_id',
        ]);
        if(count($p_where)<1){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        // 结束时间（可选），默认为为当前时间
        if( !isset($where['end_time']) ){
            $where['end_time'] = time();
        }
        if( isset($where['user_id']) ){
            $where1[] = ['order_return.user_id', '=', $where['user_id']];
        }
        // 开始时间（可选）
        if( isset($where['begin_time'])){
            if( $where['begin_time']>$where['end_time'] ){
                return false;
            }
            $where1[] = ['order_return.create_time', '>=', $where['begin_time']];
            $where1[] = ['order_return.create_time', '<=', $where['end_time']];
        }else{
            $where1[] = ['order_return.create_time', '<', $where['end_time']];
        }
        unset($where['begin_time']);
        unset($where['end_time']);
        // order_no 订单编号查询，使用前缀模糊查询
        if( isset($where['order_no']) ){
            $where1[] = ['order_return.order_no', 'like', '%'.$where['order_no'].'%'];
        }
        if( isset($where['reason_key']) ){
            $where1[] = ['order_return.reason_key', '=',$where['reason_key']];
        }
        // order_no 订单编号查询，使用前缀模糊查询
        if( isset($where['goods_name'])){
            $where1[] = ['order_goods.goods_name', 'like', '%'.$where['goods_name'].'%'];
        }
        if(isset($where['user_mobile'])){
            $where1[] = ['order_userinfo.user_mobile', '=', $where['user_mobile']];
        }
        if( isset($where['status']) ){
            $where1[] = ['order_return.status', '=', $where['status']];
        }
        if( isset($where['order_status']) ){
            $where1[] = ['order_info.status', '=', $where['order_status']];
        }

        if(isset($where['appid']) ){

            $where1[] = ['order_info.appid', '=', $where['appid']];
        }
        if( isset($where['business_key']) ){
            $where1[] = ['order_return.business_key', '=', $where['business_key']];
        }
        return $where1;
    }
    //获取商品信息
    /*public function get_goods_info($params){
        if(empty($params['goods_no'])){
            return ApiStatus::CODE_20001;//商品编号不能为空
        }
        if(empty($params['order_no'])){
            return ApiStatus::CODE_20001;//订单编号不能为空
        }
        return $this->orderReturnRepository->getGoodsList($params);

    }*/
    //退货结果查看
    public function returnResult($params){
        if(empty($params['order_no'])){
            return apiResponse( [], ApiStatus::CODE_33003,'订单编号不能为空' );
        }
        if(empty($params['goods_no'])){
            return apiResponse( [], ApiStatus::CODE_40000,'商品编号不能为空' );
        }
        $result= $this->orderReturnRepository->returnResult($params);
        //（退款、退机、换机）状态
        if($result['status']==ReturnStatus::ReturnCreated){
            $result['status']=ReturnStatus::getStatusName(ReturnStatus::ReturnCreated);//提交申请
        }elseif($result['status']==ReturnStatus::ReturnAgreed){
            $result['status']=ReturnStatus::getStatusName(ReturnStatus::ReturnAgreed);//同意
        }elseif($result['status']==ReturnStatus::ReturnDenied){
            $result['status']=ReturnStatus::getStatusName(ReturnStatus::ReturnDenied);//拒绝
        }elseif($result['status']==ReturnStatus::ReturnCanceled){
            $result['status']=ReturnStatus::getStatusName(ReturnStatus::ReturnCanceled);//取消退货申请
        }elseif($result['status']==ReturnStatus::ReturnReceive){
            $result['status']=ReturnStatus::getStatusName(ReturnStatus::ReturnReceive);//已收货
        }elseif($result['status']==ReturnStatus::ReturnTuiHuo){
            $result['status']=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiHuo);//已退货
        }elseif($result['status']==ReturnStatus::ReturnHuanHuo){
            $result['status']=ReturnStatus::getStatusName(ReturnStatus::ReturnHuanHuo);//已换货
        }elseif($result['status']==ReturnStatus::ReturnTuiKuan){
            $result['status']=ReturnStatus::getStatusName(ReturnStatus::ReturnTuiKuan);//已退款
        }elseif($result['status']==ReturnStatus::ReturnTui){
            $result['status']=ReturnStatus::getStatusName(ReturnStatus::ReturnTui);//退款中
        }
        if(!$result){
            return ApiStatus::CODE_34002;
        }
        return $result;
    }
    //检测合格或不合格
  /*  public function is_qualified($order_no,$business_key,$data)
    {
        if (empty($order_no)){
            return ApiStatus::CODE_33003;//订单编号不能为空
        }
        $where[] = ['order_no', '=', $order_no];
        //开启事务
        DB::beginTransaction();
        //获取订单信息
      //  $order_info = $this->orderReturnRepository->order_info($order_no);
        foreach($data as $k=>$v){
            $where[]=['goods_no','=',$data[$k]['goods_no']];
            $params['evaluation_remark'] = $data[$k]['check_description'];
            $params['evaluation_amount'] =$data[$k]['price'];
            $params['evaluation_time'] =$data[$k]['evaluation_time'];
            if($data[$k]['check_result']=="success") {
                $params['evaluation_status'] = ReturnStatus::ReturnEvaluationSuccess;
                if ($business_key == ReturnStatus::OrderTuiHuo) {
                    $params['status'] = ReturnStatus::ReturnTuiHuo;
                    //修改商品状态
                    $goods_data['goods_status'] = $params['status'];
                }
                if ($business_key == ReturnStatus::OrderHuanHuo) {
                    $params['status'] = ReturnStatus::ReturnReceive;
                    //修改商品状态
                    $goods_data['goods_status'] = $params['status'];
                   // $goods_data['business_key'] = $business_key;
                }
                $goods_result = $this->orderReturnRepository->updategoods($where, $goods_data);
                if (!$goods_result) {
                    //事务回滚
                    DB::rollBack();
                    return ApiStatus::CODE_33009;//修改商品状态失败
                }

            }
            if ($data[$k]['check_result'] == 'false') {
                $params['evaluation_status'] = ReturnStatus::ReturnEvaluationFalse;
                //修改订单冻结状态
                $freeze_type = OrderFreezeStatus::Non;
                $freeze_result = $this->orderReturnRepository->update_freeze($params, $freeze_type);
                if (!$freeze_result) {
                    //事务回滚
                    DB::rollBack();
                    return ApiStatus::CODE_33007;//修改订单冻结状态失败
                }
            }
            $result = $this->orderReturnRepository->is_qualified($where, $params);//修改退货单状态和原因
            if (!$result) {
                //事务回滚
                DB::rollBack();
                return ApiStatus::CODE_33008;//修改退货单信息失败
            }


        }

        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;
    }*/
    //检测合格或不合格
      public function is_qualified($order_no,$business_key,$data)
      {
          if (empty($order_no)){
              return ApiStatus::CODE_20001;//参数错误
          }
          $where[] = ['order_no', '=', $order_no];
         // $OrderRepository=new OrderRepository();
          //开启事务
          DB::beginTransaction();
          //获取订单信息
          foreach($data as $k=>$v){
              $where[]=['goods_no','=',$data[$k]['goods_no']];
              $where_info['order_no']=$order_no;
              $where_info['goods_no']=$data[$k]['goods_no'];
              //获取订单和退货单信息
              $order_info = $this->orderReturnRepository->order_info($where_info);
              //获取商品信息
              $goods_info = $this->orderReturnRepository->getGoodsInfo($where);
              $params['evaluation_remark'] = $data[$k]['check_description'];
              $params['evaluation_amount'] =$data[$k]['price'];
              $params['evaluation_time'] =$data[$k]['evaluation_time'];
              if($data[$k]['check_result']=="success"){
                  $params['evaluation_status'] = ReturnStatus::ReturnEvaluationSuccess;
                  if ($business_key == ReturnStatus::OrderTuiHuo){
                      //修改商品状态
                    //  $goods_data['goods_status'] = $params['status'];
                   //   $goods_result = $this->orderReturnRepository->updategoods($where, $goods_data);
                   //   if (!$goods_result) {
                          //事务回滚
                   //       DB::rollBack();
                   //       return ApiStatus::CODE_33009;//修改商品状态失败
                  //    }
                      //获取订单支付方式
                      if($order_info[0]->order_status!=OrderStatus::BUSINESS_RETURN &&$order_info[0]->order_status!=OrderStatus::OrderPayed){
                          return ApiStatus::CODE_34001;//此订单不符合规则
                      }
                      //创建退款清单
                      $create_data['order_no']=$order_no;
                      $pay_result=$this->orderReturnRepository->get_pay_no($business_key,$order_no);
                      if(!$pay_result){
                          return ApiStatus::CODE_50004;//订单未支付
                      }
                      $create_data['order_type']=$order_info[0]->order_type;//订单类型
                      $create_data['business_type']=OrderCleaningStatus::businessTypeReturn;//业务类型
                      $create_data['business_no']=$order_info[0]->refund_no;//业务编号
                      $create_data['user_id']=$order_info[0]->user_id;
                      $create_data['app_id']=$order_info[0]->appid;
                      //退款：直接支付
                      if($order_info[0]->pay_type==\App\Order\Modules\Inc\PayInc::FlowerStagePay ||$order_info[0]->pay_type==\App\Order\Modules\Inc\PayInc::UnionPay){
                          $create_data['out_payment_no']=$pay_result['payment_no'];//支付编号
                          $create_data['order_amount']=$goods_info['amount_after_discount'];//退款金额：商品实际支付优惠后总租金
                          if($goods_info['order_amount']>0){
                              $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                          }

                      }
                      //退款：代扣+预授权
                      if($order_info[0]->pay_type==\App\Order\Modules\Inc\PayInc::FlowerDepositPay){
                          $create_data['out_payment_no']=$pay_result['payment_no'];//支付编号
                          $create_data['out_auth_no']=$pay_result['fundauth_no'];//预授权编号
                          // $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
                          $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
                          $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                          $create_data['order_amount']=$goods_info['amount_after_discount'];//退款金额：商品实际支付优惠后总租金
                          $create_data['auth_unfreeze_amount']=$order_info['yajin'];//商品实际支付押金
                          if($create_data['order_amount']>0 && $create_data['auth_unfreeze_amount']>0){
                              $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                          }

                      }
                      //退款：预授权
                      if($order_info[0]->pay_type==\App\Order\Modules\Inc\PayInc::WithhodingPay){
                          $create_data['out_auth_no']=$pay_result['fundauth_no'];
                          //$create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
                          $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
                          $create_data['auth_unfreeze_amount']=$order_info['yajin'];//商品实际支付押金
                          if($create_data['auth_unfreeze_amount']>0){
                              $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                          }
                      }
                      $OrderClearingRepository=new OrderClearingRepository();
                      //创建退款清单
                     /* $create_data['order_no']=$order_no;//支付方式
                      $create_data['out_account']=$order_info[0]->pay_type;//支付方式
                      $create_data['order_type']=$order_info[0]->order_type;//订单类型
                      $create_data['business_type']=OrderCleaningStatus::businessTypeReturn;//业务类型：退货
                       $create_data['business_no']=$order_info[0]->refund_no;//待定
                      $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
                      $create_data['out_payment_no']=$pay_result['payment_no'];
                      $create_data['out_auth_no']=$pay_result['fundauth_no'];
                      $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
                      $create_data['refund_amount']=$order_info[0]->refund_amount;//退款金额（租金）
                      $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
                      $create_data['user_id']=$order_info[0]->user_id;
                      $create_data['app_id']=$order_info[0]->appid;*/
                      $create_clear= $OrderClearingRepository->createOrderClean($create_data);//创建退款清单
                      if(!$create_clear){
                          return ApiStatus::CODE_34008;//创建退款清单失败
                      }
                      $params['status'] = ReturnStatus::ReturnTui;
                      $result = $this->orderReturnRepository->is_qualified($where, $params);//修改退货单状态和原因
                      if (!$result) {
                          //事务回滚
                          DB::rollBack();
                          return ApiStatus::CODE_33008;//修改退货单信息失败
                      }

                  }
                  if ($business_key == ReturnStatus::OrderHuanHuo) {
                      $params['status'] = ReturnStatus::ReturnReceive;
                      $result = $this->orderReturnRepository->is_qualified($where, $params);//修改退货单状态和原因
                      if (!$result) {
                          //事务回滚
                          DB::rollBack();
                          return ApiStatus::CODE_33008;//修改退货单信息失败
                      }
                     $deliveray_data['goods'][$k]['goods_no']=$data[$k]['goods_no'];
                  }

              }
              if ($data[$k]['check_result'] == 'false') {
                  $params['evaluation_status'] = ReturnStatus::ReturnEvaluationFalse;
                  //修改订单冻结状态
                  $freeze_type = OrderFreezeStatus::Non;
                  $freeze_result = $this->orderReturnRepository->update_freeze($params, $freeze_type);
                  if (!$freeze_result) {
                      //事务回滚
                      DB::rollBack();
                      return ApiStatus::CODE_33007;//修改订单冻结状态失败
                  }
                  $result = $this->orderReturnRepository->is_qualified($where, $params);//修改退货单状态和原因
                  if (!$result) {
                      //事务回滚
                      DB::rollBack();
                      return ApiStatus::CODE_33008;//修改退货单信息失败
                  }
              }



          }
          //如果业务类型是换货并且有检测合格的数据，创建换货记录
          if($business_key == ReturnStatus::OrderHuanHuo){
              if($deliveray_data){
                  $order_data['order_no']=$order_no;
                  //查询用户信息
                  $user_result= $this->orderReturnRepository->getUserInfo($order_data);
                  if(!$user_result){
                      return false;
                  }

                  $deliveray_data['mobile']=$user_result['mobile'];
                  $deliveray_data['realname']=$user_result['realname'];
                  $deliveray_data['address_info']=$user_result['address_info'];
                  //创建换货单
                  $delivery_result=Delivery::createDelivery($deliveray_data);
                  if(!$delivery_result){
                      return ApiStatus::CODE_34009;//创建换货单失败
                  }
                  //修改商品状态
                  $goods_data['goods_status'] = OrderGoodStatus::EXCHANGE_GOODS;//换货中
                  $goods_result = $this->orderReturnRepository->updategoods($where, $goods_data);
                  if (!$goods_result) {
                      //事务回滚
                      DB::rollBack();
                      return ApiStatus::CODE_33009;//修改商品状态失败
                  }
              }
          }

          //提交事务
          DB::commit();
          return ApiStatus::CODE_0;
      }
    /*
     * 申请退货->申请退款
     * $order_no
     */
    public function update_return_info($params){
        $param= filter_array($params,[
            'order_no'=>'required',
            'user_id'=>'required',
            'business_key'=>'required',
        ]);
        if(count($param)<3){
            return  ApiStatus::CODE_20001;
        }
        $order_result= $this->orderReturnRepository->get_order_info($params);//获取订单信息
        if(!$order_result){
            return ApiStatus::CODE_34005;//未找到此订单
        }
        if($order_result['order_status'] !=OrderStatus::BUSINESS_RETURN && $order_result['order_status'] !=OrderStatus::OrderPayed){
            return ApiStatus::CODE_34001;//此订单不符合规则
        }
        $data['business_key']=$params['business_key'];
        $data['order_no']=$order_result['order_no'];
        $data['user_id']=$order_result['user_id'];
        $data['pay_amount']=$order_result['order_amount']+$order_result['order_yajin']+$order_result['order_insurance'];//实际支付金额=订单实际总租金+押金+意外险
        $data['status']=ReturnStatus::ReturnCreated;
        $data['refund_no']=createNo('2');
        $data['create_time']=time();
        //开启事物
        DB::beginTransaction();
        //创建申请退款记录
        $addresult= $this->orderReturnRepository->add($data);
        if(!$addresult){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_34003;//创建失败
        }
        // 修改冻结类型
        $freeze_result= $this->orderReturnRepository->update_freeze($params,$freeze_type=OrderFreezeStatus::Refund);
        if(!$freeze_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33007;//修改冻结类型失败
        }
        $update_result= $this->orderReturnRepository->goods_update_status($params);//修改商品信息
        if(!$update_result){
            //事务回滚
            DB::rollBack();
            return  ApiStatus::CODE_33007;//修改商品状态失败
        }
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;

    }
    //换货用户收货

    public function updateorder($params){
        if (empty($params['order_no']) && empty($params['goods_no'])){
            return ApiStatus::CODE_20001;//参数错误
        }
        $where[] = ['order_no', '=', $params['order_no']];
        $where[] = ['goods_no', '=', $params['goods_no']];
        $data['status']=ReturnStatus::ReturnHuanHuo;//已换货
        //开启事物
        DB::beginTransaction();
        $result = $this->orderReturnRepository->is_qualified($where, $data);//修改退货单状态和原因
        if(!$result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33008;//修改退货单信息失败
        }
        //更新订单冻结状态
        $freeze_type=OrderFreezeStatus::Non;
        $freeze = $this->orderReturnRepository->update_freeze($params, $freeze_type);
        if (!$freeze){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33007;//修改订单状态失败
        }
        //修改商品状态
        $goodsdata['goods_status']=ReturnStatus::ReturnHuanHuo;//已换货
        $goods_result = $this->orderReturnRepository->updategoods($where, $goodsdata);
        if (!$goods_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33009;//修改商品状态失败
        }
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;
    }
    //用户退货收货
    public function user_receive($params){
        if (empty($params['order_no']) && empty($params['goods_no'])){
            return ApiStatus::CODE_20001;//参数错误
        }
        $where[] = ['order_no', '=', $params['order_no']];
        $where[] = ['goods_no', '=', $params['goods_no']];
        $data['status']=ReturnStatus::ReturnReceive;//已收货
        //开启事物
        DB::beginTransaction();
        $result = $this->orderReturnRepository->is_qualified($where,$data);//修改退货单状态和原因
        if(!$result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33008;//修改退货单信息失败
        }
        //修改商品状态
        $goodsdata['goods_status']=ReturnStatus::ReturnReceive;//已收货
        $goods_result = $this->orderReturnRepository->updategoods($where, $goodsdata);
        if (!$goods_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33009;//修改商品状态失败
        }
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;
    }
    //创建换货单记录
    public function createchange($params){
        $param = filter_array($params,[
            'order_no'  =>'required',
            'goods_id'    =>'required',
            'goods_no'          =>'required',
            'serial_number'        =>'required',
        ]);
        if(count($param)<4){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $goods_result= $this->orderReturnRepository->createchange($params);
        if(!$goods_result){
            return ApiStatus::CODE_34009;//创建换货单记录失败
        }
        return ApiStatus::CODE_0;

    }
    //退款成功更新退款状态
    public function refundUpdate($params){
        $param = filter_array($params,[
            'business_type'  =>'required',
            'business_no'    =>'required',
            'status'          =>'required',
            'order_no'        =>'required',
        ]);
        if(count($param)<4){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        if($params['status']=="processing"){//退款处理中
            $return_data['status']=ReturnStatus::ReturnTui;//退货/退款单状态
            $goods_data['goods_status']=ReturnStatus::ReturnTui;//商品状态

        }
        if($params['status']=="success"){
            $return_data['status']=ReturnStatus::ReturnTuiKuan;//退货/退款单状态
            $goods_data['goods_status']=ReturnStatus::ReturnTuiKuan;//商品状态
            $order_data['order_status']=OrderStatus::OrderRefunded;//订单状态
        }
        //获取退货单信息
        $where[]=['refund_no','=',$params['business_no']];
        $return_info= $this->orderReturnRepository->get_type($where );
        if($return_info){
            $params['order_no']=$return_info['order_no'];
            if($params['business_type']!=ReturnStatus::OrderTuiKuan){
                if($return_info['goods_no']){
                    $params['goods_no']=$return_info['goods_no'];
                }
            }
        }
        //开启事物
        DB::beginTransaction();
        //修改退款单状态
        $return_result= $this->orderReturnRepository->updateStatus($params,$return_data);
        if(!$return_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33008;//修改退货单信息失败
        }
        //修改商品状态
        $goods_result= $this->orderReturnRepository->updategoodsStatus($params,$goods_data);
        if(!$goods_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33009;//修改商品状态失败
        }
        //修改订单状态
        $order_result= $this->orderReturnRepository->updateorderStatus($params,$order_data);
        if(!$order_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33007;//修改订单状态失败
        }
        //提交事务
        DB::commit();
        return ApiStatus::CODE_0;

    }
    //检测不合格线下寄回并修改订单冻结状态
    public function updateOrderStatus($params){
        if($params['order_no']){
            return ApiStatus::CODE_20001;
        }
        $data['freeze_type']=OrderFreezeStatus::Non;
        $order_result= $this->orderReturnRepository->update_freeze($params,$data);
        if(!$order_result){
            return ApiStatus::CODE_33007;
        }
        return ApiStatus::CODE_0;
    }

    /**
     * 换货
     * @param $params
     */
    public function exchangeGoods($params){
        $param = filter_array($params,[
            'order_no'=> 'required',
        ]);
        if(count($param)<1){
            return  ApiStatus::CODE_20001;
        }
        if(empty($params['goods'])){
            return  ApiStatus::CODE_20001;
        }
        //查询用户信息
        $user_result= $this->orderReturnRepository->getUserInfo($params);
        if(!$user_result){
           return false;
        }

        $data['mobile']=$user_result['mobile'];
        $data['realname']=$user_result['realname'];
        $data['address_info']=$user_result['address_info'];
        foreach($params['goods'] as $k=>$v){
            $data[$k]['goods_no']=$v;
        }
        $data['order_no']=$params['order_no'];
        //开启事物
     /*   DB::beginTransaction();
        $where[]=['order_no','=',$params['order_no']];
        $where[]=['goods_no','=',$params['goods_no']];
        $data['status']='1';//无效
        //修改商品为无效
        $goods_result= $this->orderReturnRepository->updateGoodsExtendStatus($where,$data);
        if(!$goods_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33009;//修改商品状态失败
        }
        //创建新商品
        foreach($params['goods_info'] as $k=>$v){
            $create_data[$k]['order_no']=$params['order_no'];
            $create_data[$k]['goods_no']=$params['goods_no'];
            $create_data[$k]['goods_id']=$params['goods_id'];
            $create_data[$k]['imei1']=$params['goods_info'][$k]['imei'];
            $create_data[$k]['serial_number']=$params['goods_info'][$k]['serial_number'];
        }
        $create_goods_result= $this->orderReturnRepository->createGoods($create_data);
        if(!$create_goods_result){
            //事务回滚
            DB::rollBack();
            return ApiStatus::CODE_33009;//创建失败
        }
        $delivery_data['order_no']=$params['order_no'];
        $delivery_data['goods_no']=$params['goods_no'];
        foreach($params['goods_info'] as $k=>$v){
            $delivery_data[$k]['imei']=$params['goods_info'][$k]['imei'];
        }*/
        //创建换货单
        $delivery_result=Delivery::createDelivery($data);
        if(!$delivery_result){
            return ApiStatus::CODE_34009;//创建换货单失败
        }
        return ApiStatus::CODE_0;
    }
    /**
     * 退货检测合格点击退款获得的数据
     * @param $params
     */
    public function goodsRefund($params){
        $param = filter_array($params,[
            'order_no'=> 'required',
            'goods_no'  => 'required',
        ]);
        if(count($param)<2){
            return  apiResponse([],ApiStatus::CODE_20001,'参数错误');
        }
        //获取此订单的支付方式
        $pay_result=$this->orderReturnRepository->payRefund($params);
        if(!$pay_result){
            return ApiStatus::CODE_50001;//获取订单支付方式错误
        }
        //获取商品和订单信息
        $goods_result=$this->orderReturnRepository->orderGoodsInfo($params);
        if(!$goods_result){
            return ApiStatus::CODE_34005;//获取商品和订单信息错误
        }
        //支付
        if($pay_result['payment_no']){
                //商品名称
                $data['goods_name']=$goods_result[0]->goods_name;
                $data['goods_price']=$goods_result[0]->amount_after_discount;//商品优惠后总结
                $data['pay_refund']=$goods_result[0]->amount_after_discount+$goods_result[0]->insurance;//应退金额=商品优惠后金额+意外险
        }
        //预授权
       // if($pay_result('fundauth_no')){
                //应扣金额
                $data['goods_yajin'] = $goods_result[0]->buyout_price - $goods_result[0]->evaluation_amount;//买断价格-检测价格
                //应还金额
                $data['refund_price'] =$goods_result[0]->yajin-$data['goods_yajin'];//商品押金-应扣金额
      //  }
        return $data;

    }

    /**
     * 退货退款创建清单
     * @param $params
     */
    public function refundTo($params){
        $param = filter_array($params,[
            'order_no'=> 'required',
            'goods_no'  => 'required',
            'pay_refund'=>'required',
            'business_type'=>'required',
            'business_no'=>'required',
        ]);
        if(count($param)<5){
            return  apiResponse([],ApiStatus::CODE_20001,'参数错误');
        }
        //获取商品和订单信息
        $goods_result=$this->orderReturnRepository->orderGoodsInfo($params);
        if(!$goods_result){
            return ApiStatus::CODE_34005;//获取商品和订单信息错误
        }
        if($params['pay_refund']>$goods_result[0]->price){
           return false;
        }
        if(isset($params['goods_yajin'])>$goods_result[0]->yajin){
          return false;
        }
        if(isset($params['refund_price'])>$goods_result[0]->yajin){
            return false;
        }
        $OrderClearingRepository=new OrderClearingRepository();
        //获取此订单的支付方式
        $pay_result=$this->orderReturnRepository->payRefund($params);
        if(!$pay_result){
            return ApiStatus::CODE_50001;//获取订单支付方式错误
        }
        //创建退款清单
        $create_data['out_account']=$goods_result[0]->pay_type;//支付方式
        $create_data['order_type']=$goods_result[0]->order_type;//订单类型
        $create_data['business_type']=$params['business_type'];
        $create_data['business_no']=$params['business_no'];
        $create_data['deposit_deduction_status']=OrderCleaningStatus::depositDeductionStatusNoPay;//代扣押金状态
        if(isset($params['refund_price'])){
            $create_data['deposit_unfreeze_amount']=$params['refund_price'];//退还押金金额
        }
        $create_data['payment_no']=$pay_result['payment_no'];
        $create_data['auth_no']=$pay_result['fundauth_no'];
        $create_data['deposit_unfreeze_status']=OrderCleaningStatus::depositUnfreezeStatusCancel;//退还押金状态
        $create_data['refund_amount']=$params['pay_refund'];//退款金额（租金）
        $create_data['refund_status']=OrderCleaningStatus::refundUnpayed;//退款状态  待退款
        $create_data['user_id']=$goods_result[0]->user_id;
        $create_data['app_id']=$goods_result[0]->appid;
        $create_clear= $OrderClearingRepository->createOrderClean($create_data);//创建退款清单
        if(!$create_clear){
            return ApiStatus::CODE_34008;//创建退款清单失败
        }
        return  ApiStatus::CODE_0;

    }

    /**
     * 退换货点击审核弹出退换货单
     * @param $params
     */
    public function returnApplyList($params){
        $where[]=['order_return.order_no','=',$params['order_no']];
        $where[]=['order_return.business_key','=',$params['business_key']];
        if(isset($params['status'])){
            $where[]=['order_return.status','=',$params['status']];
        }
        if(isset($params['evaluation_status'])){
            $where[]=['order_return.evaluation_status','=',$params['evaluation_status']];
        }

        $return_list= $this->orderReturnRepository->returnApplyList($where);//创建退款清单
        return $return_list;
    }

    /**
     *获取退款单数据
     * @param $params
     */
    public function getOrderStatus($params){
        $where[]=['order_no','=',$params['order_no']];
        $return_list= $this->orderReturnRepository->get_type($where);
        return $return_list;
    }
}