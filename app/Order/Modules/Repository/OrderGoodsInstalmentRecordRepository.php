<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderGoodsInstalmentRecord;

class OrderGoodsInstalmentRepository
{


    public function __construct() {

    }

    public function instalment_init(){
        $this->goods_no         = !empty($this->componnet['sku']['goods_no']) ? $this->componnet['sku']['goods_no'] : "";

        $this->user_id          = $this->componnet['user']['user_id'];


        $this->zuqi             = $this->componnet['sku']['zuqi'];
        $this->zujin            = $this->componnet['sku']['zujin'];
        $this->yiwaixian        = $this->componnet['sku']['yiwaixian'];
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




}