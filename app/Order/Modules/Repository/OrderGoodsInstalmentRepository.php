<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderGoodsInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\CouponStatus;
use App\Order\Modules\Inc\PayInc;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Profiler;

class OrderGoodsInstalmentRepository
{
    private $OrderGoodsInstalment;

    private $componnet = null;

    //订单编号
    private $order_no = null;
    //租期
    private $zuqi = 0;
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
    //用户id
    private $user_id = 0;

    public function __construct($componnet) {
        $this->OrderGoodsInstalment = new OrderGoodsInstalment();
        $this->componnet = $componnet;
        $this->instalment_init();
    }

    public function instalment_init(){
        $this->goods_no         = !empty($this->componnet['sku']['goods_no']) ? $this->componnet['sku']['goods_no'] : "";

        $this->user_id          = $this->componnet['user']['user_id'];


        $this->zuqi             = $this->componnet['sku']['zuqi'];
        $this->zujin            = $this->componnet['sku']['zujin'];
        $this->yiwaixian        = $this->componnet['sku']['insurance'];
        $this->payment_type_id  = $this->componnet['sku']['pay_type'];
        $this->goods_discount_price     = !empty($this->componnet['sku']['discount_amount']) ? $this->componnet['sku']['discount_amount'] : 0;


        $this->all_amount       = $this->componnet['sku']['all_amount'];
        $this->amount           = $this->componnet['sku']['amount'];
        $this->fenqi_amount     = $this->componnet['sku']['zujin'];
        $this->first_amount     = $this->zujin + $this->yiwaixian;

        $this->componnet['coupon']  = !empty($this->componnet['coupon']) ? $this->componnet['coupon'] : [];
        // 优惠券信息
        if(!empty($this->componnet['coupon'])) {
            // 统计固定金额优惠券 总优惠
            foreach ($this->componnet['coupon'] as $v) {
                if ($v['coupon_type'] == CouponStatus::CouponTypeFixed) {
                    $this->discount_amount += $v['discount_amount'];
                }
            }
        }


        $fenqi_price = ($this->all_amount - $this->yiwaixian - $this->discount_amount) / $this->zuqi;
        $fenqi_price = $fenqi_price > 0 ? $fenqi_price : 0;

        $this->first_amount = $fenqi_price + $this->yiwaixian;;

        if(!empty($this->componnet['coupon'])) {
            foreach ($this->componnet['coupon'] as $item) {
                //首月零租金
                if ($item['coupon_type'] == CouponStatus::CouponTypeFirstMonthRentFree) {
                    $this->first_amount = $this->yiwaixian;
                }
            }
        }
        $this->fenqi_amount = $fenqi_price;


        // 下单立减
        if($this->goods_discount_price > 0){
            $this->coupon_type = 1;
            //优惠金额等于 商品优惠

            $first = $this->fenqi_amount - $this->goods_discount_price;
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

        if($this->coupon_type == 1){
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
        $result =  OrderGoodsInstalment::query()->where([
            ['id', '=', $id],
        ])->first();
        if (!$result) return false;
        return $result->toArray();
    }

    /**
     * 查询分期信息
     */
    public static function getInfo($params){
        if (empty($params)) return false;
        $result =  OrderGoodsInstalment::query()->where($params)->first();
        if (!$result) return false;
        return $result->toArray();
    }
    /**
     * 查询分期统计应付金额
     */
    public static function getSumAmount($params){
        if (empty($params)) return false;
        $result =  OrderGoodsInstalment::query()->where($params)->sum("amount");
        if (!$result) return false;
        return $result;
    }
    /**
     * 查询总数
     */
    public static function queryCount($param = []){
        $whereArray = [];
        //根据goods_no
        if (isset($param['goods_no']) && !empty($param['goods_no'])) {
            $whereArray[] = ['order_goods_instalment.goods_no', '=', $param['goods_no']];
        }

        //根据订单号
        if (isset($param['order_no']) && !empty($param['order_no'])) {
            $whereArray[] = ['order_goods_instalment.order_no', '=', $param['order_no']];
        }

        //根据分期状态
        if (isset($param['status']) && !empty($param['status'])) {
            $whereArray[] = ['order_goods_instalment.status', '=', $param['status']];
        }

        //根据分期日期
        if (isset($param['term']) && !empty($param['term'])) {
            $whereArray[] = ['order_goods_instalment.term', '=', $param['term']];
        }

        //根据用户mobile
        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['order_info.mobile', '=', $param['mobile']];
        }
        $result = OrderGoodsInstalment::query()->where($whereArray)
            ->leftJoin('order_info', 'order_goods_instalment.user_id', '=', 'order_info.user_id')
            ->select('order_info.user_id','order_goods_instalment.*')
            ->get();
        return count($result);
    }
    /**
     * 查询列表
     */
    public static function queryList($param = [], $additional = []){
        $page       = isset($additional['page']) ? $additional['page'] : 1;
        $pageSize   = isset($additional['limit']) ? $additional['limit'] : config("web.pre_page_size");
        $offset     = ($page - 1) * $pageSize;

        $whereArray = [];
        //根据goods_no
        if (isset($param['goods_no']) && !empty($param['goods_no'])) {
            $whereArray[] = ['order_goods_instalment.goods_no', '=', $param['goods_no']];
        }

        //根据订单号
        if (isset($param['order_no']) && !empty($param['order_no'])) {
            $whereArray[] = ['order_goods_instalment.order_no', '=', $param['order_no']];
        }

        //根据分期状态
        if (isset($param['status']) && !empty($param['status'])) {
            $whereArray[] = ['order_goods_instalment.status', '=', $param['status']];
        }

        //根据分期日期
        if (isset($param['term']) && !empty($param['term'])) {
            $whereArray[] = ['order_goods_instalment.term', '=', $param['term']];
        }

        //根据用户mobile
        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['order_info.mobile', '=', $param['mobile']];
        }

        $result =  OrderGoodsInstalment::query()
            ->leftJoin('order_info', 'order_goods_instalment.user_id', '=', 'order_info.user_id')
            ->where($whereArray)
            ->select('order_info.user_id','order_goods_instalment.*','order_info.mobile')
            ->offset($offset)
            ->limit($pageSize)
            ->get();
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
        if(isset($data['id'])){
            $where[] = ['id', '=', $data['id']];
        }

        if(isset($data['order_no'])){
            $where[] = ['order_no', '=', $data['order_no']];
        }
        if(isset($data['goods_no'])){
            $where[] = ['goods_no', '=', $data['goods_no']];
        }
        if(isset($data['user_id'])){
            $where[] = ['user_id', '=', $data['user_id']];
        }
        $status = ['status'=>OrderInstalmentStatus::CANCEL];
        $result =  OrderGoodsInstalment::where($where)->update($status);
        if (!$result) return false;

        return true;

    }

    /**
     * 设置TradeNo
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
        $result =  OrderGoodsInstalment::where(
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
        if($this->goods_no == ""){
            return false;
        }
        // 租期数组
        $date  = $this->get_terms($this->zuqi);
        // 默认分期
        for($i = 1; $i <= $this->zuqi; $i++){
            //用户id
            $_data['user_id']         = $this->user_id;
            //商品编号
            $_data['goods_no']        = $this->goods_no;
            //订单ID
            $_data['order_no']        = $this->order_no;
            //还款日期(yyyymm)
            $_data['term']            = $date[$i];
            // 日期 天
            $_data['day']             = intval(date('d'));
            //第几期
            $_data['times']           = $i;
            //原始金额
            $_data['original_amount'] = $this->zujin;

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

            $ret = $this->OrderGoodsInstalment->insertGetId($_data);
            if(!$ret){
                return false;
            }
        }
        return true;
    }

    //递减式分期
    function diminishing_fenqi(){
        if($this->goods_no == ""){
            return false;
        }
        // 租期数组
        $date  = $this->get_terms($this->zuqi);
        //优惠金额
        $discount_amount = $this->goods_discount_price;
        // 默认分期
        for($i = 1; $i <= $this->zuqi; $i++){
            //业务编号
            $_data['trade_no']        = createNo();
            //用户id
            $_data['user_id']         = $this->user_id;
            //商品编号
            $_data['goods_no']        = $this->goods_no;
            //订单ID
            $_data['order_no']        = $this->order_no;
            //还款日期(yyyymm)
            $_data['term']            = $date[$i];
            // 日期 天
            $_data['day']             = intval(date('d'));
            //第几期
            $_data['times']           = $i;
            //原始金额
            $_data['original_amount'] = $this->zujin;

            if($discount_amount > $this->fenqi_amount){
                $discount_amount            = $discount_amount - $this->fenqi_amount;
                $_data['amount']            = 0;
                $_data['discount_amount']   = $this->zujin;
            }else{
                $_data['amount']            = $this->fenqi_amount - $discount_amount;
                $_data['discount_amount']   = $this->zujin - ($this->fenqi_amount - $discount_amount);
                $discount_amount            = 0;
            }
            //首期应付金额（分）
            if($i==1){
                $_data['amount']  += $this->yiwaixian;
            }

            $_data['unfreeze_status'] = 2;
            //支付状态 金额为0则为支付成功状态
            $_data['status']          = $_data['amount'] > 0 ? OrderInstalmentStatus::UNPAID : OrderInstalmentStatus::SUCCESS;
            $ret = $this->OrderGoodsInstalment->insertGetId($_data);

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

        $result =  OrderGoodsInstalment::where($where)->update($data);
        if (!$result) return false;

        return true;
    }



}