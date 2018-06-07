<?php
namespace App\Order\Modules\Repository\GoodsReturn;
use App\Order\Models\OrderReturn;
use App\Order\Modules\Inc\ReturnStatus;

/**
 *
 * @author Administrator
 */
class GoodsReturn {

    protected $OrderReturn;

    public function __construct(OrderReturn $OrderReturn)
    {
        $this->model = $OrderReturn;
    }

    //-+------------------------------------------------------------------------
    // | 退货
    //-+------------------------------------------------------------------------
    /**
     * 读取退换货单原始数据
     * @return array
     */
    public function getData():array{
        return $this->model->toArray();
    }
    /**
     * 取消退货
     * @return bool
     */
    public function close(){
        // 校验状态
        if($this->model->status==ReturnStatus::ReturnCanceled){
            return false;
        }
        $this->model->status = ReturnStatus::ReturnCanceled;
        return $this->model->save();
    }
    /**
     * 退换货审核同意
     * @return bool
     */
    public function accept( array $data):bool{
        //退换货单必须是待审核
        if( $this->model->status !=ReturnStatus::ReturnCreated ){
            return false;
        }
        $this->model->status = ReturnStatus::ReturnAgreed;
        $this->model->remark=$data['remark'];
        $this->model->reason_key=$data['reason_key'];
        return $this->model->save();
    }
    /**
     * 退换货审核拒绝
     * @return bool
     */
    public function refuse( ):bool{
        return true;
    }
    /**
     *  取消发货
     *@return bool
     */
    public function cancelDelivery( ):bool{
        return true;
    }
    /**
     * 退款审核同意
     * @return bool
     */
    public function refundAgree(string $remark):bool{
        //退换货单必须是待审核
        if( $this->model->status !=ReturnStatus::ReturnCreated ){
            return false;
        }
        $this->model->remark = $remark;
        $this->model->status = ReturnStatus::ReturnAgreed;
        return $this->model->save();
    }
    /**
     * 退款审核拒绝
     * @return bool
     */
    public function refundAccept( string $remark):bool{
        //退换货单必须是待审核
        if( $this->model->status !=ReturnStatus::ReturnCreated ){
            return false;
        }
        $this->model->remark = $remark;
        $this->model->status = ReturnStatus::ReturnDenied;
        return $this->model->save();
    }
    /**
     * 取消退款
     *@return bool
     */
    public function cancelRefund():bool{
        //退换货单必须未取消
        if( $this->model->status =ReturnStatus::ReturnCanceled ){
            return false;
        }
        $this->model->status = ReturnStatus::ReturnCanceled;
        return $this->model->save();
    }
    /**
     * 退货检测不合格拒绝退款
     *@return bool
     */
    public function refuseRefund(string $remark){
        //退换货单必须未取消
        if( $this->model->status==ReturnStatus::ReturnCanceled ){
            return false;
        }
        $this->model->refuse_refund_remark = $remark;
        $this->model->status = ReturnStatus::ReturnCanceled;
        return $this->model->save();
    }
    /**
     * 退货检测合格
     * @return bool
     */
    public function returnCheckOut( ):bool{
        return true;
    }
    /**
     * 检测不合格
     * @return bool
     */
    public function Unqualified( ):bool{
        return true;
    }
    /**
     * 退货完成，退款进行中
     * @return bool
     */
    public function returnRefund( ):bool{
        return true;
    }
    /**
     * 退货退款完成
     * @return bool
     */
    public function returnFinish( ):bool{
        return true;
    }
    /**
     * 拒绝退款
     */
    public function refundRefuse( ):bool{
        return true;
    }
    /**
     * 换货检测合格
     * @return bool
     */
    public function barterCheckOut( ):bool{
        return true;
    }
    /**
     * 换货完成
     * @return bool
     */
    public function barterFinish( ):bool{
        return true;
    }
    /**
     *
     * 更新物流单号
     * @return bool
     */
    public function uploadLogistics(array $data){
        if($this->model->logistics_no!=''){
            return false;
        }
        $this->model->logistics_id=$data['logistics_id'];
        $this->model->logistics_name=$data['logistics_name'];
        $this->model->logistics_no=$data['logistics_no'];
        return $this->model->save();

    }

    /**
     * 获取订单
     * <p>当订单不存在时，抛出异常</p>
     * @param string $refund_no		退换货编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @throws \App\Lib\NotFoundException
     */
    public static function getReturnByRefundNo( string $refund_no, int $lock=0 ) {
        $builder = \App\Order\Models\OrderReturn::where([
            ['refund_no', '=', $refund_no],
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
    /**
     * 获取退换货单
     * <p>当订单不存在时，抛出异常</p>
     * @param string $order_no		退换货编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @throws \App\Lib\NotFoundException
     */
    public static function getReturnByOrderNo( string $order_no, int $lock=0 ) {
        $builder = \App\Order\Models\OrderReturn::where([
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
