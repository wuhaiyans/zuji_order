<?php
namespace App\Order\Modules\Repository;

use App\Lib\PayInc;
use App\Order\Models\OrderInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\CouponStatus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Tests\Profiler\ProfilerTest;
use Symfony\Component\HttpKernel\Profiler;

class OrderInstalmentRepository
{
    private $OrderInstalment;

    private $componnet = null;

    //订单编号
    private $order_no = null;
    //租期
    private $zuqi = 0;
    //租期类型
    private $zuqi_type = 0;
    //代扣协议号
    private $withholding_no = null;
    //订单原始金额
    private $all_amount = 0;
    //订单实际金额
    private $goods_no = 0;
    //订单实际金额
    private $amount = 0;
    //租金
    private $zujin = 0;
    //优惠金额
    private $discount_amount = 0;
    //优惠方式
    private $coupon_type = 0;
    //意外险
    private $yiwaixian = 0;
    //首期金额
    private $first_amount = 0;
    //分期金额
    private $fenqi_amount = 0;
    //支付方式
    private $payment_type_id = 0;

    public function __construct($componnet) {
        $this->OrderInstalment = new OrderInstalment();
        $this->componnet = $componnet;
        $this->instalment_init();
    }

    public function instalment_init(){

        $this->goods_no         = !empty($this->componnet['sku']['goods_no']) ? $this->componnet['sku']['goods_no'] : "";
        $this->zuqi             = $this->componnet['sku']['zuqi'];
        $this->zuqi_type        = $this->componnet['sku']['zuqi_type'];
        $this->withholding_no   = $this->componnet['user']['withholding_no'];
        $this->all_amount       = $this->componnet['sku']['all_amount'];
        $this->amount           = $this->componnet['sku']['amount'];
        $this->zujin            = $this->componnet['sku']['zujin'];
        $this->discount_amount  = !empty($this->componnet['coupon']['discount_amount']) ? $this->componnet['coupon']['discount_amount'] : "";
        $this->coupon_type      = !empty($this->componnet['coupon']['coupon_type']) ? $this->componnet['coupon']['coupon_type'] : "";
        $this->yiwaixian        = $this->componnet['sku']['yiwaixian'];
        $this->fenqi_amount     = $this->componnet['sku']['zujin'];
        $this->first_amount     = $this->zujin + $this->yiwaixian;
        $this->payment_type_id  = $this->componnet['sku']['pay_type'];


        // 如果租期类型是：天，不论几天，统一按一个分期（只生成一个分期）
        // 将 $this->zuqi 设置为 1，后续程序处理不变
        if( $this->zuqi_type == 1 ){
            //先按照天租期计算租金
            $this->zujin        = $this->fenqi_amount = $this->zujin * $this->zuqi;
            $this->first_amount = $this->zujin + $this->yiwaixian;
            $this->fenqi_amount = round($this->amount / $this->zuqi, 2);
            //然后将租期重置为1期【按天租赁：只在首月扣款】
            $this->zuqi = 1;
        }
        //0首付
        if($this->coupon_type == CouponStatus::CouponTypeFirstMonthRentFree){
            $fenqi_price = ($this->all_amount - $this->yiwaixian) / $this->zuqi;
            $first = $fenqi_price - $this->discount_amount;
            $first = $first > 0 ? $first : 0;
            $first += $this->yiwaixian;
            $this->first_amount = $first;
            $this->fenqi_amount = $fenqi_price;
        }
        //固定金额
        elseif($this->coupon_type == CouponStatus::CouponTypeFixed){
            $price = $this->all_amount - $this->yiwaixian - $this->discount_amount;
            $price = $price > 0 ? $price : 0;
            $this->fenqi_amount = $price / $this->zuqi;
            $first = $this->fenqi_amount + $this->yiwaixian;
            $this->first_amount = $first;
        }
        //递减优惠券
        elseif($this->coupon_type == CouponStatus::CouponTypeDecline){
            $this->fenqi_amount = $this->zujin;
            $first = $this->fenqi_amount - $this->discount_amount;
            $this->first_amount = $first >= 0 ? $first + $this->yiwaixian : $this->yiwaixian;
        }
        //不同支付方式呈现不同分期金额
        if($this->payment_type_id == PayInc::FlowerStagePay || $this->payment_type_id == PayInc::UnionPay){
            $this->fenqi_amount = $this->amount / $this->zuqi;
        }
    }

    /**
     * 创建分期
     */
    public function create(){

        $this->order_no         = $this->componnet['order']['order_no'];
        //支持分期支付方式
        $pay_type = [
            PayInc::WithhodingPay,
            PayInc::MiniAlipay,
        ];
        if(!in_array($this->payment_type_id,$pay_type)){
            return true;
        }

        if($this->coupon_type == CouponStatus::CouponTypeDecline){
            return $this->diminishing_fenqi();
        }else{
            return $this->default_fenqi();
        }
    }

    /**
     * 根据id查询信息
     */
    public static function getInfoById($id){
        if (empty($id)) return false;
        $result =  OrderInstalment::query()->where([
            ['id', '=', $id],
        ])->first();
        if (!$result) return false;
        return $result->toArray();
    }

    /**
     * 根据goods_no查询分期信息
     */
    public static function queryList($params = [], $additional = []){
        if (empty($params)) return false;
        $page       = isset($additional['page']) ? $additional['page'] : 1;
        $pageSize   = isset($additional['limit']) ? $additional['limit'] : config("web.pre_page_size");

        $offset = ($page - 1) * $pageSize;

        $result =  OrderInstalment::query()->where($params)->offset($offset)->limit($pageSize)->get();

        if (!$result) return false;
        return $result->toArray();
    }

    /**
     * 关闭分期
     */
    public static function closeInstalment($data){

        if (!is_array($data) || $data == [] ) {
            return false;
        }
        $where = [];
        if(isset($data['order_no'])){
            $where .= ['order_no', '=', $data['order_no']];
        }
        if(isset($data['id'])){
            $where .= ['id', '=', $data['id']];
        }

        $status = ['status'=>OrderInstalmentStatus::CANCEL];
        $result =  OrderInstalment::where($where)->save($status);
        if (!$result) return false;

        return true;

    }

    /**
     * 关闭分期
     */
    public static function setTradeNo($id, $trade_no){

        if (!$id ) {
            return false;
        }

        if (!$trade_no ) {
            return false;
        }

        $data = [
            'trade_no'=>$trade_no
        ];
        $result =  OrderInstalment::where(
            ['id'=>$id]
        )->update($data);

        if (!$result) return false;

        return true;

    }


    /**
     * 获取分期数据
     */
    public function get_data_schema(){

        return array_merge($this->componnet,[
            'instalment' => [
                'first_amount' => floor($this->first_amount),
                'fenqi_amount' => floor($this->fenqi_amount),
                'coupon_type'  => !empty($this->componnet['coupon']['coupon_type']) ? $this->componnet['coupon']['coupon_type'] : "",
            ]
        ]);
    }

    //默认分期单生成
    public function default_fenqi(){

        // 租期数组
        $date  = $this->get_terms($this->zuqi);
        // 默认分期
        for($i = 1; $i <= $this->zuqi; $i++){
            //代扣协议号
            $_data['agreement_no']    = $this->withholding_no;
            $_data['goods_no']        = $this->goods_no;
            //订单ID
            $_data['order_no']        = $this->order_no;
            //还款日期(yyyymm)
            $_data['term']            = $date[$i];
            //第几期
            $_data['times']           = $i;
            if($i==1){
                //首期应付金额（分）
                $_data['amount']          = $this->first_amount;
                //优惠金额
                $_data['discount_amount'] = $this->zujin - ($this->first_amount - $this->yiwaixian);
            }else{
                //其余应付金额（分）
                $_data['amount']          = $this->fenqi_amount;
                //优惠金额
                $_data['discount_amount'] = $this->zujin - $this->fenqi_amount;
            }

            $_data['unfreeze_status'] = 2;
            //支付状态 金额为0则为支付成功状态
            $_data['status']          = $_data['amount'] > 0 ? OrderInstalmentStatus::UNPAID : OrderInstalmentStatus::SUCCESS;
            $ret = $this->OrderInstalment->insertGetId($_data);
            if(!$ret){
                return false;
            }
        }
        return true;
    }

    //递减式分期
    function diminishing_fenqi(){
        // 租期数组
        $date  = $this->get_terms($this->zuqi);
        //优惠金额
        $discount_amount = $this->discount_amount;
        // 默认分期
        for($i = 1; $i <= $this->zuqi; $i++){
            //代扣协议号
            $_data['agreement_no']    = $this->withholding_no;

            $_data['goods_no']        = $this->goods_no;
            //订单ID
            $_data['order_no']        = $this->order_no;
            //还款日期(yyyymm)
            $_data['term']            = $date[$i];
            //第几期
            $_data['times']           = $i;

            if($discount_amount > $this->zujin){
                $discount_amount = $discount_amount - $this->zujin;
                $_data['amount'] = 0;
                $_data['discount_amount'] = $this->zujin;
            }else{
                $_data['discount_amount'] = $discount_amount;
                $_data['amount'] = $this->zujin - $discount_amount;
                $discount_amount = 0;
            }
            //首期应付金额（分）
            if($i==1){
                $_data['amount']  += $this->yiwaixian;
            }

            $_data['unfreeze_status'] = 2;
            //支付状态 金额为0则为支付成功状态
            $_data['status']          = $_data['amount'] > 0 ? OrderInstalmentStatus::UNPAID : OrderInstalmentStatus::SUCCESS;
            $ret = $this->OrderInstalment->insertGetId($_data);

            if(!$ret){
                return false;
            }
        }
        return true;
    }


    /*
     * 根据代扣生效日期 生成月份日期
     * int    $times   期数
     * return string
     */
    public static function get_terms($times){
        $terms = [];
        if($times < 0){
            return $terms;
        }
        $year   = date("Y");
        $month  = intval(date("m"));
        $day    = intval(date("d"));
        $month  += 1;
        if($day > 15){
            $month += 1;
        }
        for($i = 1; $i <= intval($times); $i++){
            // 首月从下个月开始

            if($month > 12){
                $year += 1;
                $month = 1;
            }
            if($month < 10 ){
                $month = "0".$month;
            }
            $term = $year.$month;
            $terms[$i] = $term;
            $month += 1;
        }

        return $terms;
    }

    /*
     * 修改方法
     * array    $where
     * array    $data
     * return bool
     */
    public static function save($where, $data){

        if ( empty($where )) {
            return false;
        }

        if ( empty($data )) {
            return false;
        }


        $result =  OrderInstalment::where($where)->update($data);
        if (!$result) return false;

        return true;
    }



}