<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23 0023
 * Time: 下午 6:57
 */

namespace App\Order\Modules\Service;
class BusinessInfo
{
    private $data = [];
    public function __construct()
    {
    }
    /**
     * 业务类型
     * @param string $type
     */

    public  function setBusinessType( string $type )
    {
          $this->data['business_type'] = $type;
    }

    /**
     * 业务名称
     * @param string $name
     */
    public  function setBusinessName( string $name )
    {
        $this->data['business_name'] = $name;
    }
    public  function setBusinessNo( string $no )
    {
    }

    /**
     * 订单信息
     * @param array $order_info
     *
     */
    public  function setOrderInfo( array $order_info )
    {
        $this->data['order_info']=$order_info;
    }

    /**
     * 商品信息
     * @param array $goods_info
     *
     */
    public  function setGoodsInfo( array $goods_info )
    {
        $this->data['goods_info']=$goods_info;
    }

    /**
     * 状态流
     * @param array $state_flow
     *
     */
    public  function setStateFlow( array $state_flow )
    {
        $this->data['stateFlow'] = $state_flow;
    }

    /**
     * 业务状态
     * @param string $status
     */
    public  function setStatus( string $status )
    {
        $this->data['status'] = $status;
    }

    /**
     * 业务状态名称
     * @param string $status_text
     */
    public  function setStatusText( string $status_text )
    {
        $this->data['status_text'] = $status_text;
    }

    /**
     * 收货物流信息
     * @param array $logistics_form
     */
    public  function setLogisticsForm( array $logistics_form )
    {
        $this->data['logistics_form']=$logistics_form;
    }

    /**
     * 物流信息
     * @param array $logistics_info
     */
    public  function setLogisticsInfo( array $logistics_info )
    {
        $this->data['logistics_info']=$logistics_info;
    }
    /**
     * 快递信息  已收货
     * @param string $status_text
     */
    public  function setReceive( string $receive )
    {
        $this->data['receive'] = $receive;
    }

    /**
     * 退换货问题
     * @param array $reason
     */
    public  function setReturnReason( array $reason )
    {
        $this->data['reason_list']=$reason;
    }

    /**
     * 用户选择的退换货的原因信息
     * @param array $reason
     */
    public  function setReturnReasonResult( array $reason )
    {
        $this->data['reason_info']=$reason;
    }

    /**检测结果
     * @param string $checkInfo
     */
    public  function setCheckResult( array $checkInfo )
    {
        $this->data['check_info']=$checkInfo;
    }

    /**
     * 换货物流信息及商品信息
     * @param array $barterLogistics
     */
    public function setBarterLogistics(array $barterLogistics){
        $this->data['barter_logistics']=$barterLogistics;
    }

    /**
     * 退换货编号
     * @param string $refund_no
     */
    public function setRefundNo(string $refund_no){
        $this->data['refund_no']=$refund_no;
    }
    /**
     * 退货退款完成退还押金信息
     * @param string $checkInfo
     */
    public  function returnUnfreeze( string $returnUnfreeze )
    {
        $this->data['returnUnfreeze']=$returnUnfreeze;
    }
    /**
     * 是否显示取消按钮
     * true   显示
     * false  不显示
     * @param string $status
     */
    public function setCancel(string $status){
        if($status=="1"){
            $this->data['cancel_button']=false;
        }else{
            $this->data['cancel_button']=true;
        }

    }
    /**
     * 备注信息及客服电话
     * @param string $remark
     */
    public function setRemark(array $remark){
        $this->data['remark']=$remark;
    }
    public  static function getStateFlow(){
        // 业务状态
        return [
                'returnStateFlow'  => [    //正常退货状态流

                 [
                        'status' => 'A',
                        'name' => '退货申请',
                    ],
                    [
                        'status' => 'B',
                        'name' => '退货审核',
                    ],
                    [
                        'status' => 'C',
                        'name' => '退货检测',
                    ],
                    [
                        'status' => 'D',
                        'name' => '退还押金',
                    ]
                 ],
                'barterStateFlow'  => [        //正常换货状态流

                [
                    'status' => 'A',
                    'name' => '换货申请',
                ],
                [
                    'status' => 'B',
                    'name' => '换货审核',
                ],
                [
                    'status' => 'C',
                    'name' => '换货检测',
                ],
                [
                    'status' => 'D',
                    'name' => '租用中',
                ]
                ],
                'returnCancelStateFlow'  => [     //退货取消状态流
                    [
                        'status' => 'A',
                        'name' => '退货申请',
                    ],
                    [
                        'status' => 'B',
                        'name' => '退货审核',
                    ],
                    [
                        'status' => 'C',
                        'name' => '退货取消',
                    ],
                ],
                'barterCancelStateFlow'  => [     //换货取消状态流
                    [
                        'status' => 'A',
                        'name' => '换货申请',
                    ],
                    [
                        'status' => 'B',
                        'name' => '换货审核',
                    ],
                    [
                        'status' => 'C',
                        'name' => '换货取消',
                    ],
                ],
                'returnDeniedStateFlow'  => [   //退货拒绝状态流
                    [
                        'status' => 'A',
                        'name' => '退货申请',
                    ],
                    [
                        'status' => 'B',
                        'name' => '退货审核',
                    ],
                    [
                        'status' => 'C',
                        'name' => '租用中',
                    ],
                ],
                'barterDeniedStateFlow'  => [           //换货拒绝状态流
                    [
                        'status' => 'A',
                        'name' => '换货申请',
                    ],
                    [
                        'status' => 'B',
                        'name' => '换货审核',
                    ],
                    [
                        'status' => 'C',
                        'name' => '租用中',
                    ],
                ],
                'returnCheckStateFlow'  => [           //退货检测不合格状态流
                    [
                        'status' => 'A',
                        'name' => '退货申请',
                    ],
                    [
                        'status' => 'B',
                        'name' => '退货审核',
                    ],
                    [
                        'status' => 'C',
                        'name' => '退货检测',
                    ],
                    [
                        'status' => 'D',
                        'name' => '租用中',
                    ],
                ]


            ];
    }
    public function toArray()
    {
        return $this->data;
    }

}