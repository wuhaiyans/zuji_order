<?php
namespace App\Order\Modules\Repository\GoodsReturn;
use App\Lib\Common\LogApi;
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
        $this->model->status = ReturnStatus::ReturnCanceled;  //已取消
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
        $this->model->status = ReturnStatus::ReturnAgreed;  //审核同意
        $this->model->remark=$data['remark']; //审核备注
        $this->model->reason_key=$data['reason_key'];//退换货问题id
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
        $this->model->status = ReturnStatus::ReturnDenied; //审核拒绝
        $this->model->remark=$data['remark']; //审核备注
        $this->model->reason_key=$data['reason_key']; //退换货问题id
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
        LogApi::debug("[refundApply]更新退款单状态为同意的接受参数",$remark);
        LogApi::debug("[refundApply]更新退款的状态".$this->model->status);
        //退换货单必须是待审核
        if( $this->model->status !=ReturnStatus::ReturnCreated ){
            return false;
        }
        $this->model->remark = $remark;//审核备注
        $this->model->status = ReturnStatus::ReturnAgreed; //审核同意
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
        $this->model->remark = $remark;//审核备注
        $this->model->status = ReturnStatus::ReturnDenied;  //审核拒绝
        return $this->model->save();
    }
    /**
     * 取消退款
     *@return bool
     */
    public function cancelRefund():bool{
        //退换货单必须未取消
        if( $this->model->status ==ReturnStatus::ReturnCanceled ){
            return false;
        }
        $this->model->status = ReturnStatus::ReturnCanceled;  //已取消
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
        $this->model->refuse_refund_remark = $remark; //拒绝备注信息
        $this->model->status = ReturnStatus::ReturnDenied;   //拒绝
        return $this->model->save();
    }
    /**
     * 退货==换货检测合格  共用
     * @return bool
     */
    public function returnCheckOut( array $data):bool{
        $this->model->evaluation_remark=$data['evaluation_remark'];//检测备注
        $this->model->evaluation_amount=$data['evaluation_amount'];//检测金额
        $this->model->evaluation_time=$data['evaluation_time'];//检测时间
        $this->model->evaluation_status=ReturnStatus::ReturnEvaluationSuccess;//检测合格
        return $this->model->save();

    }

    /***
     * 退货检测合格更新状态为退款中
     * @return bool
     */
    public function returnCheck():bool{
        $this->model->status=ReturnStatus::ReturnTui;//退款中
        return $this->model->save();
    }
    /**
     * 退货检测不合格
     * @return bool
     */
    public function returnUnqualified( array $data):bool{
        $this->model->evaluation_remark=$data['evaluation_remark'];//检测备注
        $this->model->evaluation_amount=$data['evaluation_amount'];//检测金额
        $this->model->evaluation_time=$data['evaluation_time'];//检测时间
        $this->model->evaluation_status=ReturnStatus::ReturnEvaluationFalse;//检测不合格
        return $this->model->save();
    }

    /**
     * 平台确认收货
     * @return bool
     */
    public function returnReceive():bool{
        $this->model->status=ReturnStatus::ReturnReceive;//已收货
        return $this->model->save();
    }
    /**
     * 换货检测不合格
     * @return bool
     */
    public function barterUnqualified( array $data):bool{
        $this->model->evaluation_remark=$data['evaluation_remark']; //检测备注
        $this->model->evaluation_amount=$data['evaluation_amount']; //检测金额
        $this->model->evaluation_time=$data['evaluation_time'];     //检测时间
        $this->model->evaluation_status=ReturnStatus::ReturnEvaluationFalse; //检测不合格
        $this->model->status=ReturnStatus::ReturnDenied;//拒绝
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
    public function returnFinish():bool{
        $this->model->complete_time=time();//完成时间
        $this->model->status=ReturnStatus::ReturnTuiHuo;//退货完成
        return $this->model->save();
    }
    /**
     * 退款完成
     * @return bool
     */
    public function refundFinish():bool{
        $this->model->complete_time=time();//完成时间
        $this->model->status=ReturnStatus::ReturnTuiKuan;//已退款
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
        //必须是非换货完成状态
        if($this->model->status==ReturnStatus::ReturnHuanHuo){
            return false;
        }
        $this->model->status=ReturnStatus::ReturnHuanHuo; //换货完成
        $this->model->complete_time=time();//完成时间
        return $this->model->save();
    }

    /**
     * 换货发货更新物流信息
     * @param array $data
     * @return bool
     */
    public function barterDelivery(array $data ){
        $this->model->barter_logistics_id=$data['logistics_id'];//换货物流id
        $this->model->barter_logistics_no=$data['logistics_no'];//换货物流编号
        $this->model->status = ReturnStatus::ReturnDelivery; //已发货
        $this->model->delivery_time =time();//发货时间
        return $this->model->save();
    }
    /**
     *
     * 更新物流单号
     * @return bool
     */
    public function uploadLogistics(array $data){
        $this->model->logistics_id=$data['logistics_id'];   //物流公司id
        $this->model->logistics_name=$data['logistics_name']; //物流名称
        $this->model->logistics_no=$data['logistics_no'];    //物流编号
        return $this->model->save();

    }

    /**
     * 根据业务单号获取退货单信息
     * <p>当订单不存在时，抛出异常</p>
     * @param string $refund_no		退换货编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @return bool
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
     * 获取退货单信息
     * <p>当订单不存在时，抛出异常</p>
     * @param string $order_no		退换货编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @return bool
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
     * 获取退货单信息
     * <p>当订单不存在时，抛出异常</p>
     * @param string $order_no		订单编号
     * @param string $goods_no	商品编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @return bool
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
     * 获取退货单信息
     * <p>当订单不存在时，抛出异常</p>
     * @param string $order_no		订单编号
     * @param string $goods_no	商品编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @return bool
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
     * 获取退货单信息
     * <p>当订单不存在时，抛出异常</p>
     * @param string $goods_no	商品编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @return bool
     */
    public static function getReturnGoodsInfo(string $goods_no,int $lock=0 ){
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
    /**
     * 根据商品编号和退货状态获取退货单数据
     *
     * @param string $goods_no	商品编号
     * @param string $status	退货状态
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @return bool
     */
    public static function getReturnInfoByGoodsNo(string $goods_no,string $status,int $lock=0 ) {
        $builder = \App\Order\Models\OrderReturn::where([
            ['goods_no', '=', $goods_no],['status','=',ReturnStatus::ReturnAgreed]
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
     * 根据订单编号和退货状态获取退货单数据
     *
     * @param string $order_no	商品编号
     * @param string $status	退货状态
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\GoodsReturn\GoodsReturn
     * @return bool
     */
    public static function getReturnInfoByOrderNo(string $order_no,int $lock=0 ) {
        $builder = \App\Order\Models\OrderReturn::where([
            ['order_no', '=', $order_no],['status','!=',ReturnStatus::ReturnCanceled]
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
