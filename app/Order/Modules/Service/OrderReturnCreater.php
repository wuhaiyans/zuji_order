<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use \App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use App\Order\Modules\Repository\OrderReturnRepository;
use App\Order\Modules\Repository\OrderRepository;

class OrderReturnCreater
{

    protected $orderReturnRepository;
    protected $orderRepository;
    public function __construct(orderReturnRepository $orderReturnRepository,orderRepository $orderRepository)
    {
        
        $this->orderReturnRepository = $orderReturnRepository;
        $this->orderRepository = $orderRepository;
        
    }
    public function get_return_info($data){
        return $this->orderReturnRepository->get_return_info($data);
    }
    //添加退换货数据
    public function add($data){
        $to_data['order_no']=$data['order_no'];
        $to_data['goods_no']=$data['goods_no'];
        $to_data['user_id']=$data['user_id'];
        $to_data['business_key']=$data['business_key'];
        $to_data['loss_type']=$data['loss_type'];
        $to_data['reason_id']=$data['reason_id'];
        $to_data['reason_text']=$data['reason_text'];
        $to_data['status']='1';
        $to_data['refund_no']=createNo('2');
        $to_data['create_time']=time();
        return $this->orderReturnRepository->add($to_data);
    }
    /**
     * 管理员审核 --同意
     * @param int $id 【必选】退货单ID
     * @param array $data   【必选】退货单审核信息
     * array(
     *       'id'=>''【必选】退货单ID
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
    public function agree_return($params){
        if($params['id']<1){
            return apiResponse([],ApiStatus::CODE_33002,ApiStatus::$errCodes[ApiStatus::CODE_33002]);
        }
        if(empty($params['id'])){
            return apiResponse([],ApiStatus::CODE_33001,ApiStatus::$errCodes[ApiStatus::CODE_33001]);
        }
        if(empty($params['order_no'])){
            return apiResponse([],ApiStatus::CODE_33003,ApiStatus::$errCodes[ApiStatus::CODE_33003]);
        }
        if(empty($params['remark'])){
            return apiResponse([],ApiStatus::CODE_33005,ApiStatus::$errCodes[ApiStatus::CODE_33005]);
        }
        if(empty($params['status'])){
            return apiResponse([],ApiStatus::CODE_33004,ApiStatus::$errCodes[ApiStatus::CODE_33004]);
        }
        $res= $this->orderReturnRepository->update_return($params);
        if($res){
                if($this->orderReturnRepository->goods_update($params['order_no'],$params['status'])) {
                    //申请退货同意发送短信

                    return ApiStatus::CODE_0;//成功
                }
        }else{
            return ApiStatus::CODE_33008;//更新审核状态失败
        }

    }
    /**
     * 管理员审核 --不同意
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
        if($params['id']<1){
            return ApiStatus::CODE_33002;//退货单号错误
        }
        if(empty($params['id'])){
            return ApiStatus::CODE_33001;//退货单号不能为空
        }
        if(empty($params['order_no'])){
            return ApiStatus::CODE_33003;//订单编号不能为空
        }
        if(empty($params['remark'])){
            return ApiStatus::CODE_33005;//审核备注信息不能为空
        }
        $res = $this->orderReturnRepository->deny_return($params);
        if($res){
            if($this->orderRepository->deny_update($params['order_no'])){
                //申请退货拒绝发送短信
                return ApiStatus::CODE_0;//成功
            }else{
                return ApiStatus::CODE_33007;//更新审核状态失败
            }
        }else{
            return ApiStatus::CODE_33008;//更新审核状态失败
        }

    }
}