<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;

use App\Order\Modules\Repository\OrderInstalmentRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Lib\ApiStatus;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class OrderInstalment
{

    /**
     * 创建订单分期
     * @return array
     *  $array = [
            'order'=>[
                'order_no'=>1,
            ],
            'sku'=>[
                'zuqi'=>1,
                'zuqi_type'=>1,
                'all_amount'=>1,
                'amount'=>1,
                'yiwaixian'=>1,
                'zujin'=>1,
                'yiwaixian'=>1,
                'payment_type_id'=>1,
            ],
            'coupon'=>[
                'discount_amount'=>1,
                'coupon_type'=>1,
            ],
            'user'=>[
                'withholding_no'=>1,
        ],
    ];
     */
    public static function create($params){
        $order    = $params['order'];
        $params['sku']      = $params['sku'][0];
        $sku      = $params['sku'];
        $coupon   = isset($params['coupon']) ? $params['coupon'] : "";
        $user     = $params['user'];

        $order = filter_array($order, [
            'order_no' => 'required',
        ]);
        if(!$order['order_no']){
            return false;
        }

        //获取sku
        $sku = filter_array($sku, [
            'goods_no'      => 'required',
            'zuqi'          => 'required',
            'zuqi_type'     => 'required',
            'all_amount'    => 'required',
            'amount'        => 'required',
            'yiwaixian'     => 'required',
            'zujin'         => 'required',
            'pay_type'      => 'required',
            'buyout_price'  => 'required',
        ]);

        if(count($sku) < 8){
            return false;
        }

        filter_array($coupon, [
            'discount_amount'   => 'required',
            'coupon_type'       => 'required',
        ]);


        $user = filter_array($user, [
            'user_id'        => 'required',
            'withholding_no' => 'required',
        ]);
        if(count($user) < 2){
            return false;
        }

        $res = new OrderInstalmentRepository($params);
        return $res->create();

    }


    /**
     * 创建订单分期
     * @return array
     *  $array = [
    'order'=>[
    'order_no'=>1,
    ],
    'sku'=>[
    'zuqi'=>1,
    'zuqi_type'=>1,
    'all_amount'=>1,
    'amount'=>1,
    'yiwaixian'=>1,
    'zujin'=>1,
    'yiwaixian'=>1,
    'payment_type_id'=>1,
    ],
    'coupon'=>[
    'discount_amount'=>1,
    'coupon_type'=>1,
    ],
    'user'=>[
    'withholding_no'=>1,
    ],
    ];
     */
    public static function get_data_schema($params){
        $params['sku']=$params['sku'][0];
        $sku      = $params['sku'];
        $coupon   = !empty($params['coupon']) ? $params['coupon'] : "";
        $user     = $params['user'];

        $sku = filter_array($sku, [
            'zuqi'=>'required',
            'zuqi_type'=>'required',
            'all_amount'=>'required',
            'amount'=>'required',
            'yiwaixian'=>'required',
            'zujin'=>'required',
            'pay_type'=>'required',
        ]);

        if(count($sku) < 7){
            return false;
        }

        filter_array($coupon, [
            'discount_amount'=>'required',
            'coupon_type'=>'required',
        ]);

        $user = filter_array($user, [
            'withholding_no' => 'required',
        ]);
        if(count($user) < 1){
            return false;
        }

        $res = new OrderInstalmentRepository($params);
        return $res->get_data_schema();


    }

    /**
     * 查询分期数据
     * @params array 查询条件
     * @return array
     */
    public static function queryInfo($params){
        if (empty($params)) {
            return ApiStatus::CODE_20001;
        }

        $result =  OrderInstalmentRepository::getInfo($params);
        if(!$result){
            return ApiStatus::CODE_71001;
        }
        return $result;
    }

    /**
     * 根据InstalmentId查询分期数据
     * @return array
     */
    public static function queryByInstalmentId($id){
        if (empty($id)) {
            return ApiStatus::CODE_20001;
        }

        $result =  OrderInstalmentRepository::getInfoById($id);
        if(!$result){
            return ApiStatus::CODE_71001;
        }
        return $result;
    }


    /**
     * 查询分期数据
     * @return array
     */
    public static function queryList($params = [],$additional = []){
        if (!is_array($params)) {
            return ApiStatus::CODE_20001;
        }

        $params = filter_array($params, [
            'goods_no'  =>'required',
            'order_no'  =>'required',
            'status'    => 'required',
            'mobile'    => 'required',
            'term'      => 'required',
        ]);

        $additional = filter_array($additional, [
            'page'  =>'required',
            'limit'  =>'required',
        ]);

        $total = OrderInstalmentRepository::queryCount($params);
        if($total == 0){
            return [];
        }

        $result =  OrderInstalmentRepository::queryList($params, $additional);
        $result = array_group_by($result,'goods_no');
        $result['total'] = $total;

        return $result;
    }


    /**
     * 根据用户id和订单号、商品编号，关闭用户的分期
     * @param data  array
     * [
     *      'id'       => '', 主键ID
     *      'order_no' => '', 订单编号
     *      'goods_no' => '', 商品编号
     *      'user_id'  => ''  用户id
     * ]
     */
    public static function close($data){
        if (!is_array($data) || $data == [] ) {
            return false;
        }
        $result =  OrderInstalmentRepository::closeInstalment($data);
        return $result;
    }

    /**
     * 是否允许扣款
     * @param  int  $instalment_id 订单分期付款id
     * @return bool true false
     */
    public static function allowWithhold($instalment_id){
        if(empty($instalment_id)){
            return false;
        }
        $alllow = false;
        $instalment_info = OrderInstalmentRepository::getInfoById($instalment_id);

        $status = $instalment_info['status'];

        $year   = date("Y");
        $month  = intval(date("m"));
        if($month < 10 ){
            $month = "0".$month;
        }
        $term 	= $year.$month;
        $day 	= intval(date("d"));

        //查询订单记录
        $order_info = OrderRepository::getInfoById($instalment_info['order_no']);

        if($status == OrderInstalmentStatus::UNPAID || $status == OrderInstalmentStatus::FAIL){
            // 本月15后以后 可扣当月 之前没有扣款的可扣款
            if(($term == $instalment_info['term'] && $day >= 15) || $term > $instalment_info['term']){
                //判断订单状态 必须是租用中 或者完成关闭的状态 才允许扣款
                if($order_info['order_status'] == OrderStatus::OrderInService && $order_info['freeze_type'] == OrderFreezeStatus::Non){
                    $alllow = true;
                }
            }
        }
        return $alllow;
    }


    /**
     * 更新分期扣款的租机交易码
     * @param int $id	主键ID
     * @param string $trade_no	交易码
     * @return mixed  false：更新失败；int：受影响记录数
     */
    public static function set_trade_no($id, $trade_no){
        if(!$id){
            return ApiStatus::CODE_20001;
        }

        if(!$trade_no){
            return ApiStatus::CODE_20001;
        }

        return OrderInstalmentRepository::setTradeNo($id, $trade_no);

    }

    /**
     * 更新分期扣款的租机交易码
     * @param int $id	主键ID
     * @param string $trade_no	交易码
     * @return mixed  false：更新失败；int：受影响记录数
     */
    public static function instalment_failed($fail_num,$instalment_id,$term,$data_sms){

        $data_sms = filter_array($data_sms, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
            'zuJin' =>'required',
        ]);
        if( count($data_sms) != 5 ){
            Log::error('短信参数错误');
            return false;
        }

        if ($fail_num == 0) {
            $model = 'hsb_sms_99a6f';
        } elseif ($fail_num > 0 && $term == date("Ym")) {
            $model = 'hsb_sms_16f75';
        } elseif ($fail_num > 0 && $term <= date("Ym") - 1) {
            $model = 'hsb_sms_7326b';
        }

        SmsApi::sendMessage($data_sms['mobile'], $model, [
            'realName'      => $data_sms['realName'],
            'orderNo'       => $data_sms['orderNo'],
            'goodsName'     => $data_sms['goodsName'],
            'zuJin'         => $data_sms['zuJin'],
            'serviceTel'    =>env("CUSTOMER_SERVICE_PHONE"),
        ]);

        $fail_num = intval($fail_num) + 1;

        //修改失败次数
        $b = OrderInstalmentRepository::save(['id'=>$instalment_id],['fail_num'=>$fail_num]);
        Log::error('更新失败次数失败');
        return $b;
    }


    /**
     * 修改方法
     * @param string $params 条件
     * @param string $data	 参数数组
     * @return mixed  false：更新失败；int：受影响记录数
     */
    public static function save($params, $data){
        if (!is_array($params) || $data == [] ) {
            return false;
        }
        $result =  OrderInstalmentRepository::save($params, $data);
        return $result;
    }

    /**
     * 冻结分期
     * @param string $goods_no 商品单号
     * @return bool
     */
    public static function instalment_unfreeze($goods_no){
        if ( !$goods_no ) {
            return false;
        }
        $where = [
            'goods_no' => $goods_no,
        ];
        $result =  OrderInstalmentRepository::save($where, ['unfreeze_status'=>0,'status'=>OrderInstalmentStatus::CANCEL]);
        return $result;
    }



}