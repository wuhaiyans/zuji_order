<?php
/**
 *  芝麻小程序回调
 *   zhangjinhui
 *   date:2018-05-14
 */
namespace App\Order\Controllers\Api\v1;

use Illuminate\Support\Facades\Redis;
use App\Order\Modules\Service\OrderGiveback;
use App\Lib\ApiStatus;
use App\Order\Modules\Inc\OrderGivebackStatus;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Service\OrderGoodsInstalment;

class MiniNotifyController extends Controller
{
    //取消
    private $CANCEL = 'ZM_RENT_ORDER_CANCEL';
    //完结
    private $FINISH = 'ZM_RENT_ORDER_FINISH';
    //确认订单
    private $CREATE = 'ZM_RENT_ORDER_CREATE';
    //返回数组
    private $data = [];
    //返回成功状态success
    private $success = 'success';
    //返回失败状态fail
    private $fail = 'fail';
    /**
     * 芝麻支付宝小程序 代扣接口(订单关闭 订单取消)异步回调
     * Author: zhangjinhui
     * @param $this->data
     * @return string
     */
    public function withholdingCloseCancelNotify(){
        //关闭订单回调
//        $json = '{"pay_amount":"0.00","notify_app_id":"2018032002411058","out_order_no":"A820152365009739","notify_type":"ZM_RENT_ORDER_FINISH","channel":"rent","zm_order_no":"2018082000001001097894683192","pay_status":"PAY_SUCCESS","sign":"lr+QBAo5pLEA1nXIPk4pJPZ1lLk7jQTPDAh9Euvh8XT90SZeMzusMroj0JnNIdZlKmKZ2CbNhE+dt0kd3Hfl2UkvvxlodiTe9EXsaIrHTy5SWdAmB7elBs7+BhEmxVzGDBgrsgKNB6F2uqyyfB95rqChzwvS+JFYex2teq6hVurFOki7u8+EkkftmsRO2vN+6idbEQMsssYvJYSGfXoq8\/joTHLX8jPiOuc4sAMPoAxLZ0PsLuJQ4Jd3xhypdJ76qu0z0oW9PXypheuXiJzNiV2MPs9fEOJkde99\/h9XZ5MJtgHdgiN9IZdQbwyHHRBnkE2yZwKrosAdHCnvurkpZg==","sign_type":"RSA2"}';
        //创建订单回调
//        $json = '{"fund_type":"ALL","order_create_time":"2018-08-02 15:19:26","notify_app_id":"2018032002411058","out_order_no":"A802193823842289","notify_type":"ZM_RENT_ORDER_CREATE","credit_privilege_amount":"0.00","channel":"rent","zm_order_no":"2018080200001001094519709098","sign":"JfPuvci5BAW3jiHzJCdmVUm3ax1QyAF8MuBsm9FHQqtgeispRePUCbud5AM36l6qCv\/RloHsv0TFjVbFAaQ3mYhIb2H7uSfEuCaIBUWSDY68\/wMyp1wM7BbJ0VmyKvvFHvrqz22lDABK3P8w3QdZptkF2dZ2200FTWLkSf7n+W7jmaOBxoJfgLTPfItDbx4T0FH86i335mG9wydOuSrk2H+4ARpuh7J8\/COkHdqQtJsSUO5L0rfs3cKcWi+licuVoYftjwMjAQo55DOJBrMsC4wZKVjLeZ6JVtsryjD0I2pUQSh5rU+SseQC6ib8gB6QrLMkC9T2MWPdcZi0hJ3L1A==","sign_type":"RSA2"}';
        //取消订单回调
//        $json = '{"notify_app_id":"2018032002411058","out_order_no":"A802193823842289","notify_type":"ZM_RENT_ORDER_CANCEL","channel":"rent","zm_order_no":"2018080200001001094519709098","sign":"Yosi\/ZKTDVvPGUwvseryPC0bh0ZBk7DtRsoXKim8CZOKyjUI1zJXJcSkYE1L7PBoU0G4Ccq527M+BuN5MteH4yPjtjTBlsAsPLme+0jsvcXuy2+rJetmMSqsfU5OsAvET1uue2NpABd65lUT0rf\/Xe2sRR8SmBQyXWNyA2sQNN6XbD8hcSa1ZkY0ijSNlJAju85VQGxF6aDLe04UNtP\/CDVaQYavdMvqoUIIIIzVaAQx88Rs87xulAA+jwdI63e6tNvxmh\/c2O\/TySEayzbOEXWokTt3WtwYMjyqFE251l+zuDM7GstFkooBxiC34IqNvjfQgPDtkyOIyTtxyYQGNQ==","sign_type":"RSA2"}';
//        $_POST = json_decode($json,true);
        \App\Lib\Common\LogApi::setSource('zm_withholding_close_cancel');
        if( isset($_POST['out_order_no']) ) {
            \App\Lib\Common\LogApi::id($_POST['out_order_no']);
        }
        \App\Lib\Common\LogApi::notify('芝麻小程序回调参数记录',$_POST);

        if( ! isset($_POST['notify_app_id']) ){
            \App\Lib\Common\LogApi::error('芝麻小程序回调参数错误',$_POST);
            echo '芝麻小程序回调参数错误';exit;
        }
        $appid = $_POST['notify_app_id'];
        $CommonMiniApi = new \App\Lib\AlipaySdk\sdk\CommonMiniApi( $appid );
        $b = $CommonMiniApi->verify( $_POST );
        if(!$b){
            \App\Lib\Common\LogApi::error('扣款回调验签','签名验证失败fail');
            echo '签名验证失败fail';exit;
        }
        $this->data = $_POST;
        //当前订单是否需要进行转发（查询订单信息）
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $_POST['out_order_no'] );
        if( $orderInfo == false ){
            $this->curl_dev( $_POST );
        }
        try{
        if($this->data['notify_type'] == $this->CANCEL){
            //入库取消订单回调信息
                $arr_log = [
                    'notify_type'=>$_POST['notify_type'],
                    'zm_order_no'=>$_POST['zm_order_no'],
                    'out_order_no'=>$_POST['out_order_no'],
                    'channel'=>$_POST['channel'],
                    'notify_app_id'=>$_POST['notify_app_id'],
                    'data_text'=>json_encode($_POST),
                ];
                if(isset($_POST['cancel_time'])){
                    $arr_log['cancel_time'] = $_POST['cancel_time'];
                }
                $result = \App\Order\Modules\Repository\OrderMiniNotifyLogRepository::add($arr_log);
            if( !$result ){
                \App\Lib\Common\LogApi::debug('小程序取消订单回调记录失败',$_POST);
            }
                $this->orderCancelNotify();
        } if($this->data['notify_type'] == $this->FINISH){
            //入库 完成 或 扣款 回调信息
            $redis_order = null;
            if(isset($_POST['out_trans_no'])){
                $redis_order = Redis::get('zuji:order:miniorder:'.$_POST['out_trans_no']);
                $out_trans_no = $_POST['out_trans_no'];
                $alipay_fund_order_no = $_POST['alipay_fund_order_no'];
                $pay_time = $_POST['pay_time'];
            }
            if(!$redis_order){
                $redis_order = Redis::get('zuji:order:miniorder:'.$_POST['out_order_no']);
                $out_trans_no = isset($_POST['out_trans_no'])?$_POST['out_trans_no']:'';
                $alipay_fund_order_no = isset($_POST['alipay_fund_order_no'])?$_POST['alipay_fund_order_no']:'';
                $pay_time = isset($_POST['pay_time'])?$_POST['pay_time']:null;
            }
//            $redis_order = 'MiniOrderClose';
//            $redis_order = 'MiniWithhold';
            $arr_log = [
                'notify_type'=>$_POST['notify_type'],
                'zm_order_no'=>$_POST['zm_order_no'],
                'out_trans_no'=>$out_trans_no,
                'out_order_no'=>$_POST['out_order_no'],
                'alipay_fund_order_no'=>$alipay_fund_order_no,
                'pay_amount'=>$_POST['pay_amount'],
                'pay_status'=>$_POST['pay_status'],
                'channel'=>$_POST['channel'],
                'pay_time'=>$pay_time,
                'notify_app_id'=>$_POST['notify_app_id'],
                'redis_key'=>$redis_order,
                'data_text'=>json_encode($_POST),
            ];
            $result = \App\Order\Modules\Repository\OrderMiniNotifyLogRepository::add($arr_log);
            if( !$result ){
                \App\Lib\Common\LogApi::debug('小程序完成 或 扣款 回调记录失败',$_POST);
            }
            if( $redis_order == 'MiniWithhold' ){
                $this->withholdingNotify();
                return;
            }else if( $redis_order == 'MiniOrderClose' ){
                    $this->orderCloseNotify();
                return;
            }else{
                \App\Lib\Common\LogApi::debug('小程序完成 或 扣款 回调redisKey查询不存在',$_POST);
                echo 'redisKey查询不存在';die;
            }
        }else if($this->data['notify_type'] == $this->CREATE){
                //入库 确认订单 回调信息
                $arr_log = [
                    'notify_type'=>$_POST['notify_type'],
                    'zm_order_no'=>$_POST['zm_order_no'],
                    'out_order_no'=>$_POST['out_order_no'],
                    'credit_privilege_amount'=>$_POST['credit_privilege_amount'],
                    'fund_type'=>$_POST['fund_type'],
                    'channel'=>$_POST['channel'],
                    'order_create_time'=>$_POST['order_create_time'],
                    'notify_app_id'=>$_POST['notify_app_id'],
                    'data_text'=>json_encode($_POST),
                ];
                $result = \App\Order\Modules\Repository\OrderMiniNotifyLogRepository::add($arr_log);
            if( !$result ){
                \App\Lib\Common\LogApi::debug('小程序订单确认支付回调记录失败',$_POST);
            }
            $this->rentTransition();
        }
        }catch(\Exception $ex){
            //记录日志
            \App\Lib\Common\LogApi::debug('小程序处理异常',$ex->getMessage());
            echo $ex->getMessage();exit;
        }
    }

    /**
     * 芝麻支付宝小程序 订单关闭接口异步回调
     * Author: zhangjinhui
     * @param $this->data
     * @return string
     */
    private function orderCloseNotify(){
        $data = $this->data;
        //查询订单信息
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $data['out_order_no'] );
        if( $orderInfo == false ){
            echo '订单不存在';return;
        }
        if($orderInfo['order_status'] == \App\Order\Modules\Inc\OrderStatus::OrderCompleted){
            //当前订单已还机完成
            echo $this->success;return;
        }
        //开启事务
        \DB::beginTransaction();
        //判断订单是否为还机关闭订单
        if($data['pay_status'] == "PAY_SUCCESS"){
            $order_goods = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow([
                'order_no'=>$data['out_order_no']
            ]);
            //判断订单是否为还机状态
            if($order_goods['goods_status'] == \App\Order\Modules\Inc\OrderGoodStatus::CLOSED_THE_MACHINE || $order_goods['goods_status'] ==\App\Order\Modules\Inc\OrderGoodStatus::BACK_IN_THE_MACHINE){
                $orderGivebackService = new OrderGiveback();
                //获取还机单基本信息
                $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($order_goods['goods_no']);
                if(empty($orderGivebackInfo)){
                    echo '还机单不存在';return;
                }
                //查询判断分期是否已经结清
                $instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$orderGivebackInfo['goods_no'],'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
                if( empty($instalmentList[$orderGivebackInfo['goods_no']]) ) {//分期结清了
                    $params = [
                        'business_type'=>\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//还机业务编码
                        'business_no'=>$orderGivebackInfo['giveback_no'],
                        'status'=>$this->success,
                        'order_type'=>\App\Order\Modules\Inc\OrderStatus::orderMiniService,
                    ];
                    $b = \App\Order\Modules\Service\OrderGiveback::callbackPayment($params);
                    if($b){
                        \DB::commit();
                        echo $this->success;return;
                    }else{
                        //事物回滚 记录日志
                        \DB::rollBack();
                        \App\Lib\Common\LogApi::debug('小程序还机单关闭订单回调处理失败',$data);
                        echo $this->fail;return;
                    }
                }else{
                    //未扣款代扣全部执行
                    foreach ($instalmentList[$orderGivebackInfo['goods_no']] as $instalmentInfo) {
                        $b = \App\Order\Modules\Service\OrderWithhold::instalment_withhold($instalmentInfo['id']);
                        if(!$b){
                            echo $this->fail;return;
                        }
                    }
                    \App\Lib\Common\LogApi::debug('小程序还机单扣款未结清',$data);
                    echo $this->fail;return;
                }
            }else{
                //小程序清算订单
                $b = \App\Order\Modules\Service\OrderCleaning::miniUnfreezeAndPayClean($data);
                if($b){
                    \DB::commit();
                    echo $this->success;return;
                }else{
                    //事物回滚 记录日志
                    \DB::rollBack();
                    \App\Lib\Common\LogApi::debug('订单关闭处理失败',$data);
                    echo $this->fail;return;
                }
            }
        }
    }

    /**
     * 芝麻支付宝小程序 订单取消接口异步回调
     * Author: zhangjinhui
     * @param $this->data
     * @return string
     */
    private function orderCancelNotify(){
        $data = $this->data;
        //查询订单信息
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $data['out_order_no'] );
        if( $orderInfo == false ){
            echo '订单不存在';return;
        }
        //判断当前订单是否已经取消（或已退款）
        if($orderInfo['order_status'] == \App\Order\Modules\Inc\OrderStatus::OrderCancel || $orderInfo['order_status'] == \App\Order\Modules\Inc\OrderStatus::OrderClosedRefunded){
            //当前订单已经取消（或已退款）
            echo $this->success;return;
        }
        //开启事务
        \DB::beginTransaction();
        //小程序清算订单
        $b = \App\Order\Modules\Service\OrderCleaning::miniUnfreezeAndPayClean($data);
        if(!$b){
            //事物回滚 记录日志
            \DB::rollBack();
            \App\Lib\Common\LogApi::debug('订单关闭处理失败',$data);
            echo $this->fail;return;
        }else{
            \DB::commit();
            echo $this->success;return;
        }
    }

    /**
     * 芝麻支付宝小程序 订单扣款接口异步回调
     * Author: zhangjinhui
     * @param $this->data
     * @return string
     */
    private function  withholdingNotify(){
        $data = $this->data;
        //查询订单信息
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $data['out_order_no'] );
        if( $orderInfo == false ){
            echo '订单不存在';return;
        }
        $business_no = $data['out_trans_no'];
        // 扣款成功 修改分期状态
        if($data['pay_status'] == "PAY_SUCCESS"){
            //开启事务
            \DB::beginTransaction();
            $params = [
                'status'=>$this->success,
                'out_trade_no'=>$business_no,
            ];
            $instalment = OrderGoodsInstalment::queryInfo(
                [
                    'business_no'=>$business_no,
                ]
            );
            //判断当前订单是否已经修改分期状态
            if($instalment['status'] != \App\Order\Modules\Inc\OrderInstalmentStatus::SUCCESS){
                //修改分期状态
                $Instalment = new \App\Order\Modules\Repository\Order\Instalment();
                $b = $Instalment->paySuccess($params);
                if($b){
                    //提交事物预防下面查询数据出现脏数据
                    \DB::commit();
                    $order_goods = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow([
                        'order_no'=>$data['out_order_no']
                    ]);
                    //判断订单是否为还机状态
                    if($order_goods['goods_status'] == \App\Order\Modules\Inc\OrderGoodStatus::CLOSED_THE_MACHINE || $order_goods['goods_status'] ==\App\Order\Modules\Inc\OrderGoodStatus::BACK_IN_THE_MACHINE){
                        $orderGivebackService = new OrderGiveback();
                        //获取还机单基本信息
                        $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($order_goods['goods_no']);
                        if(empty($orderGivebackInfo)){
                            echo '还机单不存在';return;
                        }
                        //查询判断分期是否已经结清
                        $instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$orderGivebackInfo['goods_no'],'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
                        if( empty($instalmentList[$orderGivebackInfo['goods_no']]) ){//分期结清请求关闭接口
                            //支付状态为支付中则请求关闭订单接口
                            if( $orderGivebackInfo['withhold_status'] == OrderGivebackStatus::WITHHOLD_STATUS_IN_WITHHOLD || $orderGivebackInfo['payment_status'] == OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY || $orderGivebackInfo['payment_status'] == OrderGivebackStatus::PAYMENT_STATUS_NOT_PAY){
                                //请求关闭订单接口
                                $arr = [
                                    'zm_order_no'=>$data['zm_order_no'],
                                    'out_order_no'=>$orderGivebackInfo['order_no'],
                                    'pay_amount'=>$orderGivebackInfo['compensate_amount'],
                                    'remark'=>$orderGivebackInfo['giveback_no'],
                                    'app_id'=>$data['notify_app_id'],
                                ];
                                //判断是否有请求过（芝麻支付接口）
                                $where = [
                                    'out_order_no'=>$data['out_order_no'],
                                    'order_operate_type'=>'FINISH',
                                    'remark'=>$orderGivebackInfo['giveback_no'],
                                ];
                                $orderMiniCreditPayInfo = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::getMiniCreditPayInfo($where);
                                if( $orderMiniCreditPayInfo ) {
                                    $arr['out_trans_no'] = $orderMiniCreditPayInfo['out_trans_no'];
                                }else{
                                    $arr['out_trans_no'] = $orderGivebackInfo['giveback_no'];
                                }
                                $orderCloseResult = \App\Lib\Payment\mini\MiniApi::OrderClose($arr);
                                //提交事务
                                if( $orderCloseResult['code'] == 10000 ){
                                    \DB::commit();
                                    //记录日志
                                    \App\Lib\Common\LogApi::debug('扣款完成进行关闭订单请求返回成功',$orderCloseResult);
                                    echo $this->success;return;
                                }else{
                                    \DB::commit();
                                    //记录日志
                                    \App\Lib\Common\LogApi::debug('扣款完成进行关闭订单请求返回失败',$orderCloseResult);
                                    echo $this->success;return;
                                }
                            }
                        }else{
                            echo $this->success;return;
                        }
                    }else{
                        echo $this->success;return;
                    }
                }else{
                    //事物回滚 记录日志
                    \DB::rollBack();
                    \App\Lib\Common\LogApi::debug('小程序订单扣款回调处理失败',$data);
                    echo $this->fail;return;
                }
            }else{
                echo $this->success;return;
            }
        }else{
            \DB::rollBack();
            \App\Lib\Common\LogApi::debug('小程序订单扣款回调状态不等于PAY_SUCCESS',$data);
            echo $this->fail;return;
        }
    }

    /**
     * 确认订单异步通知接口 订单创建成功异步通知
     * Author: zhangjinhui
     * @param $this->data
     * @return string
     */
    public function rentTransition(){
        $data = $this->data;
        $params = [
            'business_type'=>1,
            'business_no'=>$data['out_order_no'],
            'status'=>$this->success,
        ];
        //开启事务
        \DB::beginTransaction();
        $b = \App\Order\Modules\Service\OrderPayNotify::callback($params);
        if (!$b) {
            //事物回滚 记录日志
            \DB::rollBack();
            \App\Lib\Common\LogApi::debug('支付回调处理失败',$params);
            echo "小程序订单支付失败";return;
        }else{
            \DB::commit();
            echo $this->success;return;
        }
    }

    /**
     * 开发环境转发接口
     */
    private function curl_dev(  $post = [], $timeout = 5 ){
        if(env('MINI_ZUJI_URL')){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, env('MINI_ZUJI_URL'));
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            $result = curl_exec($ch);
            \App\Lib\Common\LogApi::notify('芝麻小程序回调转发处理结果'.env('MINI_ZUJI_URL').$result,$_POST);
            curl_close($ch);
        }
    }

    /**
     * 测试发起请求小程序订单后续操作
     * @author zhangjinhui
     */
    public function withholdingCloseCancel(){
//        $b = \App\Lib\Payment\mini\MiniApi::OrderCancel([
//            'out_order_no'=>'20180805000429',//商户端订单号
//            'zm_order_no'=>'2018080500001001096338011605',//芝麻订单号
//            'remark'=>'取消订单测试操作',//订单操作说明
//            'app_id'=>'2018032002411058',//小程序appid
//        ]);

        $b = \App\Lib\Payment\mini\MiniApi::OrderClose([
            'out_order_no'=>'A730139501212361',//商户端订单号
            'zm_order_no'=>'2018073000001001093909089036',//芝麻订单号
            'out_trans_no'=>'A823119008551174',//商户端交易号
            'remark'=>'关闭订单操作',//订单操作说明
            'pay_amount'=>'0.00',//关闭金额
            'app_id'=>'2018032002411058',//小程序appid
        ]);

//        $b = \App\Lib\Payment\mini\MiniApi::withhold([
//            'out_order_no'=>'20180713000802',//商户端订单号
//            'zm_order_no'=>'2018071300001001092136454718',//芝麻订单号
//            'out_trans_no'=>'20180713000802',//商户端交易号
//            'remark'=>'20180713000802-1-期扣款',//订单操作说明
//            'pay_amount'=>'554.00',//关闭金额
//            'app_id'=>'2018032002411058',//小程序appid
//        ]);
        if($b == false){
            echo \App\Lib\Payment\mini\MiniApi::getError();die;
        }
        echo '成功';die;
    }
}