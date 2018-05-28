<?php
namespace App\Lib\AlipaySdk\sdk;

use App\Lib\AlipaySdk\sdk\MessageSingleSend;

class MessageSingleSendWord {

    //发送通知用户id
    private $to_user_id = '';
    //乐租生活
    private $app_id = '2017101309291418';
//    机市
//    private $app_id = '2017102309481957';

    private $error = '';

    public function __construct( $to_user_id = '' ) {
        $this->to_user_id = $to_user_id;
    }

    public function getError( ) {
        return $this->error;
    }

    /*
     * 还机提醒 模板
     * 发送环节：租期到期
     *  [
     *      'goods_name'=>'',租赁商品
     *      'zuji_qizu_time'=>'',（起租）租赁时间
     *      'zuji_huanji_time'=>'',还机时间
     * ]
     */
    public function ReturnMachine( $params ){
        $params = filter_array($params, [
            'goods_name' => 'required',
            'zuji_qizu_time' => 'required',
            'zuji_huanji_time' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['goods_name']) ){
            $this->error = 'goods_name为空';
            return false;
        }
        if( !isset($params['zuji_qizu_time']) ){
            $this->error = 'zuji_qizu_time为空';
            return false;
        }
        if( !isset($params['zuji_huanji_time']) ){
            $this->error = 'zuji_huanji_time为空';
            return false;
        }
        //乐租生活
        $templateId = '47e4516b18414c20bd2a8ba061b3f7a0';
//        机市
//        $templateId = '54759cfd375a45c0b2bfca11986ab923';
        $first = [
           'value'=> '您好，您租赁的商品即将到期，请在订单页面操作买断或归还商品。租期结束后7日内未操作，需强制买断。'
        ];
        $keyword1 = [
            'value'=> $params['goods_name']
        ];
        $keyword2 = [
            'value'=> $params['zuji_qizu_time']
        ];
        $keyword3 = [
            'value'=> $params['zuji_huanji_time']
        ];
        $remark = [
            'value'=> '如有疑问，请咨询 400-080-9966'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
                'keyword3' => $keyword3,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

//    /*
//     * 商品支付成功通知 模板
//     *  [
//     *
//     * ]
//     */
//    public function PaySuccessGoods(){
//        $templateId = '054fb07ca51e491eb2b16306742533d6';
//        $first = [
//            'value'=> '您的租金已经成功支付。'
//        ];
//        $keyword1 = [
//
//        ];
//        $keyword2 = [
//
//        ];
//        $keyword3 = [
//
//        ];
//        $keyword4 = [
//
//        ];
//        $keyword5 = [
//
//        ];
//        $remark = [
//            'value'=> '用机市租机，开启信用生活！'
//        ];
//        $params = [
//            'to_user_id'=>$this->to_user_id,
//            'template_id'=>$templateId,
//            'head_color'=>'',
//            'url'=>'',
//            'keyword'=>[
//                'keyword1' => $keyword1,
//                'keyword2' => $keyword2,
//                'keyword3' => $keyword3,
//                'keyword4' => $keyword4,
//                'keyword5' => $keyword5,
//            ],
//            'action_name'=>'',
//            'first'=>$first,
//            'remark'=>$remark,
//        ];
//        //传入APPid
//        $MessageSingleSend = new MessageSingleSend($this->app_id);
//        $b = $MessageSingleSend->MessageSingleSend( $params );
//        return $b;
//    }

    /*
     * 订单状态更新 模板
     * 发送环节：订单超时、订单取消
     *  [
     *      'order_no'=>'',订单编号
     *      'status'=>'',订单状态（中文）
     * ]
     */
    public function StatusUpdate( $params ){
        $params = filter_array($params, [
            'order_no' => 'required',
            'status' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['order_no']) ){
            $this->error = 'order_no为空';
            return false;
        }
        if( !isset($params['status']) ){
            $this->error = 'status为空';
            return false;
        }
        //乐租生活
        $templateId = 'f6b72a5a665445e4abe91b1f6713085d';
        //机市
//        $templateId = '08cbcd8764284f119c270059cc687ea4';
        $first = [
            'value'=> '尊敬的用户，您在机市的订单，因长时间未支付，系统已经为您关闭。'
        ];
        $keyword1 = [
            'value'=>$params['order_no']
        ];
        $keyword2 = [
            'value'=>$params['status']
        ];
        $remark = [
            'value'=> '如您还有租机需求，可以从新下单。'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 发货通知 模板
     *  发送环节：机市发货
     *  [
     *      'order_no'=>'', 订单号
     *      'goods_name'=>'', 商品名称
     *      'amount'=>'', 订单金额 非整数 例：100.00
     *      'fast_mail_no'=>'', 快递单号
     * ]
     */
    public function SendGoods( $params ){
        $params = filter_array($params, [
            'order_no' => 'required',
            'goods_name' => 'required',
            'amount' => 'required',
            'fast_mail_no' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['order_no']) ){
            $this->error = 'order_no为空';
            return false;
        }
        if( !isset($params['goods_name']) ){
            $this->error = 'goods_name为空';
            return false;
        }
        if( !isset($params['amount']) ){
            $this->error = 'amount为空';
            return false;
        }
        if( !isset($params['fast_mail_no']) ){
            $this->error = 'fast_mail_no为空';
            return false;
        }
        //乐租生活
        $templateId = '7f595ff3b49b4ede9761d500b169ba70';
        //机市
//        $templateId = '99f18a03748540a6971c9595c5cb5335';
        $first = [
            'value'=> '尊敬的用户您好，您在机市租赁的商品已发货，顺丰单号为：'.$params['fast_mail_no'].'，请您及时关注物流状态。'
        ];
        $keyword1 = [
            'value'=> $params['order_no']
        ];
        $keyword2 = [
            'value'=> $params['goods_name']
        ];
        $keyword3 = [
            'value'=>'￥'. $params['amount']
        ];
        $remark = [
            'value'=> '如有疑问，请致电客服。'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
                'keyword3' => $keyword3,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 订单签收提醒 模板
     *  发送环节：订单签收
     *  [
     *      'goods_name'=>'' , 商品名称
     *      'order_no'=>'' ,订单编号
     *      'fast_mail_company'=>'' ,物流服务(公司)
     *      'fast_mail_no'=>'' ,快递单号
     *      'sing_time'=>'' ,签收时间
     * ]
     */
    public function SignIn($params){
        $params = filter_array($params, [
            'order_no' => 'required',
            'goods_name' => 'required',
            'fast_mail_company' => 'required',
            'fast_mail_no' => 'required',
            'sing_time' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['order_no']) ){
            $this->error = 'order_no为空';
            return false;
        }
        if( !isset($params['goods_name']) ){
            $this->error = 'goods_name为空';
            return false;
        }
        if( !isset($params['fast_mail_company']) ){
            $this->error = 'fast_mail_company为空';
            return false;
        }
        if( !isset($params['fast_mail_no']) ){
            $this->error = 'fast_mail_no为空';
            return false;
        }
        if( !isset($params['sing_time']) ){
            $this->error = 'sing_time为空';
            return false;
        }
        //乐租生活
        $templateId = '74a0fc71878445e8b11418749b35e8dc';
        //机市
//        $templateId = 'f0a85fc43f7c4adbb377f35ca6c1f63b';
        $first = [
            'value'=> '您租用的手机已经签收'
        ];
        $keyword1 = [
            'value'=>$params['goods_name']
        ];
        $keyword2 = [
            'value'=>$params['order_no']
        ];
        $keyword3 = [
            'value'=>$params['fast_mail_company']
        ];
        $keyword4 = [
            'value'=>$params['fast_mail_no']
        ];
        $keyword5 = [
            'value'=>$params['sing_time']
        ];
        $remark = [
            'value'=> '感谢您选择机市提供的服务。'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
                'keyword3' => $keyword3,
                'keyword4' => $keyword4,
                'keyword5' => $keyword5,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 还款通知  模板
     * 发送环节：用户到期还款
     *  [
     *      'amount'=>'', 应还总金额
     *      'overdue_day'=>'', 逾期天数
     *      'goods_name'=>'', 商品名称
     * ]
     */
    public function Repayment($params){
        $params = filter_array($params, [
            'amount' => 'required',
            'overdue_day' => 'required',
            'goods_name' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['amount']) ){
            $this->error = 'amount为空';
            return false;
        }
        if( !isset($params['overdue_day']) ){
            $this->error = 'overdue_day为空';
            return false;
        }
        if( !isset($params['goods_name']) ){
            $this->error = 'goods_name为空';
            return false;
        }
        //乐租生活
        $templateId = '8c29de5afcfb45649c610e2793f82ae3';
        //机市
//        $templateId = 'ee728f87efd04f7bac772ec7271acda4';
        $first = [
            'value'=> '尊敬的用户： 您租用的'.$params['goods_name'].'，尚未交纳租金，请及时交纳：'
        ];
        $keyword1 = [
            'value'=> '￥'.$params['amount']
        ];
        $keyword2 = [
            'value'=> $params['overdue_day']
        ];
        $remark = [
            'value'=> '如果您已还清欠款，无需理会此提醒。'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 退款通知 模板
     *  发送环节：用户重复提交租金
     *  [
     *      'reason'=>'', 退款原因
     *      'amount'=>'', 退款金额
     * ]
     */
    public function Refund($params){
        $params = filter_array($params, [
            'reason' => 'required',
            'amount' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['amount']) ){
            $this->error = 'amount为空';
            return false;
        }
        if( !isset($params['reason']) ){
            $this->error = 'reason为空';
            return false;
        }
        //乐租生活
        $templateId = '6769dc8c8c2b4798acf6572624cc8bae';
        //机市
//        $templateId = '42648371b2514169b0f4e25700805c5c';
        $first = [
            'value'=> '尊敬的用户，您已重复提交租金，将多余租金退还给您。'
        ];
        $keyword1 = [
            'value'=>'￥'.$params['amount']
        ];
        $keyword2 = [
            'value'=> $params['reason']
        ];
        $remark = [
            'value'=> '备注：如有疑问，请联系客服：400-080-9966。'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 优惠券到账提醒 模板
     *  发送环节：领取优惠劵成功
     *  [
     *      'denomination'=>'', 优惠券面额
     *      'start_day'=>'', 生效日期
     *      'end_day'=>'', 失效日期
     * ]
     */
    public function Coupon( $params ){
        $params = filter_array($params, [
            'denomination' => 'required',
            'start_day' => 'required',
            'end_day' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['denomination']) ){
            $this->error = 'denomination为空';
            return false;
        }
        if( !isset($params['start_day']) ){
            $this->error = 'start_day为空';
            return false;
        }
        if( !isset($params['end_day']) ){
            $this->error = 'end_day为空';
            return false;
        }
        //乐租生活
        $templateId = '28e6102b8f1d4773bf43dc28d549e178';
        //机市
//        $templateId = '4f57ef1da9f24e11b349b7833e6f17cd';
        $first = [
            'value'=> '尊敬的用户您好，您的优惠劵已到账。'
        ];
        $keyword1 = [
            'value'=>$params['denomination']
        ];
        $keyword2 = [
            'value'=>$params['start_day']
        ];
        $keyword3 = [
            'value'=>$params['end_day']
        ];
        $remark = [
            'value'=> '具体优惠内容请在我的优惠劵中查看。'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
                'keyword3' => $keyword3,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 支付成功通知 模板
     * 发送环节：支付租金成功
     *  [
     *      'amount'=>'',支付金额
     *      'bill_type'=>'',账单类型
     *      'bill_time'=>'',租金账期
     *      'pay_time'=>'',支付时间
     * ]
     */
    public function PaySuccess($params){
        $params = filter_array($params, [
            'amount' => 'required',
            'bill_type' => 'required',
            'bill_time' => 'required',
            'pay_time' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['amount']) ){
            $this->error = 'amount为空';
            return false;
        }
        if( !isset($params['bill_type']) ){
            $this->error = 'bill_type为空';
            return false;
        }
        if( !isset($params['bill_time']) ){
            $this->error = 'bill_time为空';
            return false;
        }if( !isset($params['pay_time']) ){
            $this->error = 'pay_time为空';
            return false;
        }
        //乐租生活
        $templateId = 'ab9b684006f64758b8563b2bf642bd48';
        //机市
//        $templateId = '8f6be20cd7e444538f1d414af575ac2f';
        $first = [
            'value'=>'您的租金已经成功支付。'
        ];
        $keyword1 = [
            'value'=>'￥'.$params['amount']
        ];
        $keyword2 = [
            'value'=>$params['bill_type']
        ];
        $keyword3 = [
            'value'=>$params['bill_time']
        ];
        $keyword4 = [
            'value'=>$params['pay_time']
        ];
        $remark = [
            'value'=>'用机市租机，开启信用生活！'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
                'keyword3' => $keyword3,
                'keyword4' => $keyword4,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 租金账单通知 模板
     *  发送环节：分期扣款前一天发送短信
     *  [
     *      'zuji_bill'=>'',租金账期
     *      'zuji_amount'=>'',租金金额
     *      'expire_time'=>'',到期时间
     *      'goods_name'=>'',商品名称
     * ]
     */
    public function RentBill($params){
        $params = filter_array($params, [
            'zuji_bill' => 'required',
            'zuji_amount' => 'required',
            'expire_time' => 'required',
            'goods_name' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['zuji_bill']) ){
            $this->error = 'zuji_bill为空';
            return false;
        }
        if( !isset($params['zuji_amount']) ){
            $this->error = 'zuji_amount为空';
            return false;
        }
        if( !isset($params['expire_time']) ){
            $this->error = 'expire_time为空';
            return false;
        }
        if( !isset($params['goods_name']) ){
            $this->error = 'goods_name为空';
            return false;
        }
        //乐租生活
        $templateId = '2870384f4509442ca9fbb1399d05a9f4';
        //机市
//        $templateId = 'b0688814ca2c4a839955388a6067942b';
        $first = [
            'value'=>'尊敬的用户，您在机市租赁的'.$params['goods_name'].'，明天将对您的租金进行代扣，请保持账户余额充足！'
        ];
        $keyword1 = [
            'value'=>$params['zuji_bill']
        ];
        $keyword2 = [
            'value'=>'￥'.$params['zuji_amount']
        ];
        $keyword3 = [
            'value'=>$params['expire_time']
        ];
        $remark = [
            'value'=>'如对账单有疑问，请联系400-080-9966。'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
                'keyword3' => $keyword3,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 审核结果通知 模板
     *  发送环节：退货审核结果
     *  [
     *      'audit_status'=>'',审核状态
     *      'audit_time'=>'',审核时间
     * ]
     */
    public function AuditResults($params){
        $params = filter_array($params, [
            'audit_status' => 'required',
            'audit_time' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['audit_status']) ){
            $this->error = 'audit_status为空';
            return false;
        }
        if( !isset($params['audit_time']) ){
            $this->error = 'audit_time为空';
            return false;
        }
        //乐租生活
        $templateId = '3fefcfa853164158927fc56ad4fa7357';
        //机市
//        $templateId = 'c657eb9e887a4883991bfb99b0f1cbec';
        $first = [
            'value'=>'尊敬的用户您的退货审核'.$params['audit_status'].'，请您尽快寄出设备。'
        ];
        $keyword1 = [
            'value'=>$params['audit_status']
        ];
        $keyword2 = [
            'value'=>$params['audit_time']
        ];
        $remark = [
            'value'=>'退货地址：深圳市南山区高新南九道威新软件园8号楼7层'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 成功下单通知 模板
     *  [
     *  order_no 订单号
     *  order_amount 订单号
     *  receiver 收货人
     *  mobile 联系电话
     *  address 收货地址
     * ]
     */
    public function SuccessfulOrder( $params ){
        $params = filter_array($params, [
            'order_no' => 'required',
            'order_amount' => 'required',
            'receiver' => 'required',
            'mobile' => 'required',
            'address' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['order_no']) ){
            $this->error = 'order_no为空';
            return false;
        }
        if( !isset($params['order_amount']) ){
            $this->error = 'order_amount为空';
            return false;
        }
        if( !isset($params['receiver']) ){
            $this->error = 'receiver为空';
            return false;
        }
        if( !isset($params['mobile']) ){
            $this->error = 'mobile为空';
            return false;
        }
        if( !isset($params['address']) ){
            $this->error = 'address为空';
            return false;
        }
        //乐租生活
        $templateId = '001d3c0132c64188aff08fe84366709a';
        //机市
//        $templateId = 'c4234ca5f91746108816fde7abd38ac1';
        $first = [
            'value'=>'您已成功下单！'
        ];
        $keyword1 = [
            'value'=>$params['order_no']
        ];
        $keyword2 = [
            'value'=>'￥'.$params['order_amount']
        ];
        $keyword3 = [
            'value'=>$params['receiver']
        ];
        $keyword4 = [
            'value'=>$params['mobile']
        ];
        $keyword5 = [
            'value'=>$params['address']
        ];
        $remark = [
            'value'=>'请核对信息后进行支付！'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
                'keyword3' => $keyword3,
                'keyword4' => $keyword4,
                'keyword5' => $keyword5,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new \alipay\MessageSingleSend('2017101309291418');
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 订单未支付通知 模板
     * 发送环节：下单成功后1小时未支付
     *  [
     *      'order_time'=>'',下单时间
     *      'order_no'=>'',订单编号
     * ]
     */
    public function OrdersNotPay($params){
        $params = filter_array($params, [
            'order_no' => 'required',
            'order_time' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['order_no']) ){
            $this->error = 'order_no为空';
            return false;
        }
        if( !isset($params['order_time']) ){
            $this->error = 'order_time为空';
            return false;
        }
        //乐租生活
        $templateId = '6fe44ad3032b41359571d60421ae49ad';
        //机市
//        $templateId = '4fd06c8a12794a7890b0447ff58e9645';
        $first = [
            'value'=>'您好，您在机市有由订单尚未支付订单，请您及时支付，订单有效时间1小时。'
        ];
        $keyword1 = [
            'value'=>$params['order_time']
        ];
        $keyword2 = [
            'value'=>$params['order_no']
        ];
        $remark = [
            'value'=>'如你想租用此款商品，请立即支付。'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 押金退还提醒 模板
     * 发送环节：订单取消，完成租赁
     *  [
     *      'refund_time'=>'',退还时间
     *      'refund_yajin'=>'',退还押金
     * ]
     */
    public function DepositReturn($params){
        $params = filter_array($params, [
            'refund_time' => 'required',
            'refund_yajin' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['refund_time']) ){
            $this->error = 'refund_time为空';
            return false;
        }
        if( !isset($params['refund_yajin']) ){
            $this->error = 'refund_yajin为空';
            return false;
        }
        //乐租生活
        $templateId = '18a603b223d64c93add5aa9f6478e53c';
        //机市
//        $templateId = 'd02715d764524f59b56d028566df2f04';
        $first = [
            'value'=>'您好，您在机市的押金已退还。'
        ];
        $keyword1 = [
            'value'=>$params['refund_time']
        ];
        $keyword2 = [
            'value'=>'￥'.$params['refund_yajin']
        ];
        $remark = [
            'value'=>'欢迎您再次使用机市，祝您生活愉快~'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if( $b === false ){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return $b;
    }

    /*
     * 订单冻结提醒 模板
     * 发送环节：支付押金成功
     *  [
     *      'order_no'=>'',订单编号
     *      'freeze_yaji'=>'',冻结押金
     * ]
     */
    public function OrderFreezing ($params){
        $params = filter_array($params, [
            'order_no' => 'required',
            'freeze_yaji' => 'required',
        ]);
        if( empty($this->to_user_id) ){
            $this->error = 'to_user_id为空' ;
            return false;
        }
        if( !isset($params['order_no']) ){
            $this->error = 'order_no为空';
            return false;
        }
        if( !isset($params['freeze_yaji']) ){
            $this->error = 'freeze_yaji为空';
            return false;
        }
        //乐租生活
        $templateId = '0404ecd7f8e64e01a3628237fd13fefe';
        //机市
//        $templateId = '1f3dd488924746938fc14b44b5608aad';
        $first = [
            'value'=>'您的订单已成交'
        ];
        $keyword1 = [
            'value'=>$params['order_no']
        ];
        $keyword2 = [
            'value'=>'￥'.$params['freeze_yaji']
        ];
        $remark = [
            'value'=>'如有疑问，请致电400-080-9966'
        ];
        $params = [
            'to_user_id'=>$this->to_user_id,
            'template_id'=>$templateId,
            'head_color'=>'',
            'url'=>'',
            'keyword'=>[
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
            ],
            'action_name'=>'',
            'first'=>$first,
            'remark'=>$remark,
        ];
        //传入APPid
        $MessageSingleSend = new MessageSingleSend($this->app_id);
        $b = $MessageSingleSend->MessageSingleSend( $params );
        if($b  === false){
            $this->error = $MessageSingleSend->getError();
            return false;
        }
        return true;
    }
}