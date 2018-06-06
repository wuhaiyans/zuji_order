<?php
namespace App\Order\Modules\Repository\GoodsReturn;
use App\Order\Models\OrderReturn;
use App\Order\Modules\Inc\ReturnStatus;

/**
 *
 * @author Administrator
 */
class GoodsReturn {

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    //-+------------------------------------------------------------------------
    // | 退货
    //-+------------------------------------------------------------------------
    /**
     * 取消退货
     * @return bool
     */
    public function close(){
        // 校验状态
        if(!$this->data){
            return false;
        }
        //修改退货单状态
        foreach($this->data as $k=>$v){
            $where[$k][]=['id','=',$this->data[$k]['id']];
            $data['status']=ReturnStatus::ReturnCanceled;
            $updateReturnStatus=OrderReturn::where($where[$k])->update($data);
            if(!$updateReturnStatus){
                return false;
            }
        }
        try{
            foreach($this->data as $k=>$v){
                //修改商品状态
                $goodsInfo =\App\Order\Modules\Repository\Order\Goods::getByGoodsNo($this->data[$k]['goods_no'] );
                $goods=new \App\Order\Modules\Repository\Order\Goods($goodsInfo);
                p($goods);
                $b = $goods->returnClose();
                p($b);
                if( !$b ){
                    return false;
                }
            }
            return true;

        }catch(\Exception $exc){
            return false;
        }
    }
    /**
     * 审核同意
     * @return bool
     */
    public function accept( ):bool{
        return true;
    }
    /**
     * 审核拒绝
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
     * 取消退款
     *@return bool
     */
    public function cancelRefund( ):bool{
        return true;
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





}
