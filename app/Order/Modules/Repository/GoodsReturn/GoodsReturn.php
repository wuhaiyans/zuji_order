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
     *创建收货单后，更新退货单编号
     * @return bool
     */
    public function updateReceive(string $receive_no){
        $this->model->receive_no = $receive_no;
        return $this->model->save();
    }
    /**
     * 退换货审核拒绝
     * @return bool
     */
    public function refuse( array $data):bool{
        //退换货单必须是待审核
        if( $this->model->status !=ReturnStatus::ReturnCreated ){
            return false;
        }
        $this->model->status = ReturnStatus::ReturnDenied;
        $this->model->remark=$data['remark'];
        $this->model->reason_key=$data['reason_key'];
        return $this->model->save();
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
     * 退货==换货检测合格  共用
     * @return bool
     */
    public function returnCheckOut( array $data):bool{
        $this->model->evaluation_remark=$data['evaluation_remark'];
        $this->model->evaluation_amount=$data['evaluation_amount'];
        $this->model->evaluation_time=$data['evaluation_time'];
        $this->model->evaluation_status=ReturnStatus::ReturnEvaluationSuccess;
        $this->model->status=ReturnStatus::ReturnReceive;
        return $this->model->save();

    }
    /**
     * 退货检测不合格
     * @return bool
     */
    public function returnUnqualified( array $data):bool{
        $this->model->evaluation_remark=$data['evaluation_remark'];
        $this->model->evaluation_amount=$data['evaluation_amount'];
        $this->model->evaluation_time=$data['evaluation_time'];
        $this->model->evaluation_status=ReturnStatus::ReturnEvaluationFalse;
        $this->model->status=ReturnStatus::ReturnReceive;
        return $this->model->save();
    }
    /**
     * 换货检测不合格
     * @return bool
     */
    public function barterUnqualified( array $data):bool{
        $this->model->evaluation_remark=$data['evaluation_remark'];
        $this->model->evaluation_amount=$data['evaluation_amount'];
        $this->model->evaluation_time=$data['evaluation_time'];
        $this->model->evaluation_status=ReturnStatus::ReturnEvaluationFalse;
        $this->model->status=ReturnStatus::ReturnCanceled;//已取消
        return $this->model->save();
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
    public function returnFinish(array $data ):bool{
        $status=ReturnStatus::ReturnTuiHuo;//退货/退款单状态
        $this->model->complete_time=time();
        $this->model->status=$status;
        return $this->model->save();
    }
    /**
     * 退款完成
     * @return bool
     */
    public function refundFinish(array $data ):bool{
        $status=ReturnStatus::ReturnTuiKuan;//退货/退款单状态
        $this->model->complete_time=time();
        $this->model->status=$status;
        return $this->model->save();
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
        if($this->model->status==ReturnStatus::ReturnHuanHuo){
            return false;
        }
        $this->model->status=ReturnStatus::ReturnHuanHuo;
        $this->model->complete_time=time();
        return $this->model->save();
    }

    /**
     * 换货发货更新物流细信息
     * @param array $data
     * @return bool
     */
    public function barterDelivery(array $data ){
        $this->model->barter_logistics_id=$data['logistics_id'];
        $this->model->barter_logistics_no=$data['logistics_no'];
        $this->model->status = ReturnStatus::ReturnDelivery;
        return $this->model->save();
    }
    /**
     *
     * 更新物流单号
     * @return bool
     */
    public function uploadLogistics(array $data){
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
    /**
     * 获取订单
     * <p>当订单不存在时，抛出异常</p>
     * @param string $order_no		订单编号
     * @param string $goods_no	商品编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @throws \App\Lib\NotFoundException
     */
    public static function getReturnByInfo( string $order_no, string $goods_no,int $lock=0 ) {
        $builder = \App\Order\Models\OrderReturn::where([
            ['order_no', '=', $order_no],['goods_no', '=', $goods_no]
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
     * 获取订单
     * <p>当订单不存在时，抛出异常</p>
     * @param string $order_no		订单编号
     * @param string $goods_no	商品编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @throws \App\Lib\NotFoundException
     */
    public static function getReturnInfo( string $order_no, string $goods_no,int $lock=0 ) {
        $builder = \App\Order\Models\OrderReturn::where([
            ['order_no', '=', $order_no],['goods_no', '=', $goods_no],['status','!=',ReturnStatus::ReturnCanceled]
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
     * 获取订单
     * <p>当订单不存在时，抛出异常</p>
     * @param string $goods_no	商品编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @throws \App\Lib\NotFoundException
     */
    public static function getReturnGoodsInfo(string $goods_no,int $lock=0 ) {
        $builder = \App\Order\Models\OrderReturn::where([
            ['goods_no', '=', $goods_no],['status','!=',ReturnStatus::ReturnCanceled]
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
