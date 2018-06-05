<?php
namespace App\Order\Modules\Repository\GoodsReturn;

/**
 *
 * @author Administrator
 */
class GoodsReturn {

    private $data;

    public function __construct( $data )
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
    public function close( ):bool{

        // 校验状态
        if( 0 ){
            return false;
        }

        // 更新状态
        if( 0 ){
            return false;
        }


        try{
            // goods_no
            $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo( $this->data['goods_no'] );
            $b = $goods->returnClose();

            //
            if( !$b ){
                return false;
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
