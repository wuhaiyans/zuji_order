<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use App\Lib\OldInc;
use App\Lib\PayInc;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ThirdInterface;

/**
 * 下单验证类
 */
class OrderCreateVerify
{
    protected $third;
    protected $instalment;
    protected $error;
    protected $schema=[];
    protected $flag =true;

    public function __construct(ThirdInterface $third,OrderInstalment $instalment)
    {
        $this->third = $third;
        $this->instalment =$instalment;
    }
    public function Verify($data,$user_info,$goods_info){
        //验证用户信息
        $users = $this->UserVerify($user_info);
        if(!$users){
            $this->flag =false;
        }

        //判断是否需要签约代扣协议
        if($data['pay_type'] == PayInc::WithhodingPay){
            $Withhold =$this->UserWithholding($user_info['withholding_no'],$user_info['id']);
            if(!$Withhold){
                $this->flag =false;
            }
        }
        //判断商品是否允许下单
        $goods =$this->GoodsVerify($goods_info['sku_info'],$goods_info['spu_info'],$data);
        if(!$goods){
            $this->flag =false;
        }

        //押金验证计算
        $deposit =$this->DepositVerify($data,$user_info,$goods_info);
        if(!$deposit){
            $this->flag =false;
        }

        //判断该渠道是否有效等
        $channel = $this->Channel($data['appid'],$data['channel_id']);
        if(!$channel){
            $this->flag =false;
        }

        //验证优惠券信息
        if($data['coupon_no'] !=""){
            $coupon = $this->CouponVerify($data['coupon_no'],$data['user_id']);
            if(!$coupon){
                $this->flag =false;
            }
        }

        //分期单信息
        if($data['pay_type']!=PayInc::WithhodingPay){
            $instalment =$this->InstalmentVerify();
        }
        return $this->flag;

    }
    /**
     *下单验证收货地址
     * @param $user_info
     */
    public function AddressVerify($user_info){
        // 用户ID
        if( $user_info['id']!= $user_info['address']['mid'] ){
            $this->set_error(ApiStatus::CODE_41005);
            $this->flag =false;

        }
        if( $user_info['address']['status'] != 1 ){
            $this->set_error(ApiStatus::CODE_41005);
            $this->flag =false;
        }
        // 赋值
        $this->address_id = intval($user_info['address']['id']);
        $this->name = $user_info['address']['name'];
        $this->mobile = $user_info['address']['mobile'];
        $this->address = $user_info['address']['address'];
        $this->status = intval($user_info['address']['status'])?1:0;
        $this->country_id = intval($user_info['address']['district_id']);// 区县ID
        $this->country_name =$user_info['address']['country_name'];;
        $this->city_id = intval($user_info['address']['city_id']);
        $this->city_name = $user_info['address']['city_name'];;
        $this->province_id = intval($user_info['address']['province_id']);
        $this->province_name = $user_info['address']['province_name'];;
        $arr =[
            'address' => [
                'user_id' => $user_info['id'],
                'name' => $this->name,
                'mobile' => $this->mobile,
                'address' => $this->address,
                'province_id' => $this->province_id,
                'province_name' => $this->province_name,
                'city_id' => $this->city_id,
                'city_name' => $this->city_name,
                'country_id' => $this->country_id,
                'country_name' => $this->country_name,
            ]
        ];
        $this->SetSchema($arr);
        return $this->flag;

    }

    private function CouponVerify($coupon_no,$user_id){
        $arr = $this->GetSchema();
        $coupon = $this->third->GetCoupon($coupon_no,$user_id,$arr['sku']['all_amount'],$arr['sku']['spu_id'],$arr['sku']['sku_id']);
        //var_dump($coupon);die;
        if(is_array($coupon)){
            $this->discount_amount = $coupon['discount_amount'];
            $this->coupon_no = $coupon['coupon_no'];
            $this->coupon_id = $coupon['coupon_id'];
            $this->coupon_type = $coupon['coupon_type'];
            $this->coupon_name = $coupon['coupon_name'];

            $arr =[
                'coupon' => [
                    'coupon_no' => $this->coupon_no,
                    'coupon_name' => $this->coupon_name,
                    'coupon_type' => $this->coupon_type,
                    'discount_amount' => $this->discount_amount,
                ]
            ];
            $this->SetSchema($arr);
            return true;
        }
        $this->set_error($coupon);
        return false;
    }
    private function InstalmentVerify(){
        $data =$this->GetSchema();
        $instalment =$this->instalment->get_data_schema($data);
        $arr['instalment'] =$instalment['instalment'];
        $this->SetSchema($arr);
        return true;
    }

    private function UserVerify($info){
       // var_dump($info);die;
        $this->user_id = intval($info['id']);
        $this->mobile = $info['username'];
        $this->withholding_no = $info['withholding_no'];
        $this->islock = intval($info['islock'])?1:0;
        $this->block = intval($info['block'])?1:0;
        $this->credit_time = intval( $info['credit_time'] );
        $this->certified = $info['certified']?1:0;
        $this->certified_platform = intval($info['certified_platform']);
        $this->realname = $info['realname'];
        $this->cert_no = $info['cert_no'];
        $this->credit = intval($info['credit']);
        $this->face = $info['face']?1:0;
        $age =substr($this->cert_no,6,8);
        $now = date("Ymd");
        $this->age = intval(($now-$age)/10000);
        $this->risk = $info['risk']?1:0;
        if( $this->islock ){
            $this->set_error(ApiStatus::CODE_41000);
            $this->flag =false;
        }
        if( $this->block ){
            $this->set_error(ApiStatus::CODE_41001);
            $this->flag =false;
        }
        // 信用认证结果有效期
        if( (time()-$this->credit_time) > 60*60 ){
            //$this->set_error(ApiStatus::CODE_41003);
            //$this->flag =false;
        }
        if( $this->certified == 0 ){
            $this->set_error(ApiStatus::CODE_41002);
            $this->flag =false;
        }
        /**
         * 增加信用分判断 是否允许下单
         */

         //判断是否有其他活跃 未完成订单
        $b =OrderRepository::unCompledOrder($this->user_id);
        if($b) {
            $this->set_error(ApiStatus::CODE_41004);
            $this->flag =false;
        }
        $user =[
            'user' => [
                'user_id' => $this->user_id,
                'mobile' => $this->mobile,
                'withholding_no'=> $this->withholding_no,
            ]
        ];
        $arr =array_merge($user,[
            'credit' => [
                // 已认证，通过人脸识别，通过风控，认证未过期
                'certified' => $this->certified,
                'certified_platform' => $this->certified_platform,
                'realname' => $this->realname,
                'cert_no' => $this->cert_no,
                'credit' => $this->credit,
                'age' =>$this->age,
                'face' => $this->face,
                'risk' => $this->risk,
                'credit_time' => $this->credit_time,
            ]
        ]);
        //var_dump($arr);die;
        $this->SetSchema($arr);
        return $this->flag;
    }

    /**
     * 押金计算验证
     * @param $users
     * @param $goods
     */

    private function DepositVerify($data){
        $arr =$this->GetSchema();
        $deposit =$this->third->GetDeposit([
                    'spu_id'=>$arr['sku']['spu_id'],
                    'pay_type'=>$data['pay_type'],
                    'credit'=>$arr['credit']['credit']?$arr['credit']['credit']:0,
                    'age'=>$arr['credit']['age']?$arr['credit']['age']:0,
                    'yajin'=>$arr['sku']['yajin'],

        ]);
        if(is_array($deposit)){
            $arr =[
                'deposit' => [
                    'jianmian' => $deposit['jianmian'],
                    'yajin' => $deposit['yajin'],
                ]];
            $this->SetSchema($arr);
            return true;
        }
        $this->set_error(ApiStatus::CODE_40002);
        return false;
}



    /**
     *  下单商品信息过滤
     */
    private function GoodsVerify($sku_info,$spu_info,$data){
        $this->sku_id = intval($sku_info['sku_id']);
        $this->spu_id = intval($sku_info['spu_id']);
        $this->zujin = $sku_info['shop_price']*100;
        $this->yajin = $sku_info['yajin']*100;
        $this->zuqi = intval($sku_info['zuqi']);
        $this->zuqi_type = intval($sku_info['zuqi_type']);
        $this->chengse = intval($sku_info['chengse']);
        $this->stock = intval($sku_info['number']);
        $this->market_price = $sku_info['market_price']*100;
        $this->buyout_price = $this->market_price*1.2-$this->zujin*$this->zuqi;
        // 格式化 规格
        $_specs = [];
        foreach(json_decode($sku_info['spec'],true) as $it){
            $_specs[] = filter_array($it, [
                'id' => 'required',
                'name' => 'required',
                'value' => 'required',
            ]);
        }
        $this->specs = $_specs;
        $this->thumb = $spu_info['thumb'];
        $this->status = intval($sku_info['status'])?1:0;
        $this->sku_name = $spu_info['name'];// sku_name 使用 spu 的 name 值
        $this->spu_name = $spu_info['name'];
        $this->brand_id = intval($spu_info['brand_id']);
        $this->category_id = intval($spu_info['catid']);
        $this->channel_id = intval($spu_info['channel_id']);
        $this->yiwaixian = $spu_info['yiwaixian']*100;
        $this->yiwaixian_cost = $spu_info['yiwaixian_cost']*100;
        $this->contract_id =$spu_info['contract_id'];
        // 计算金额
        $this->amount = $this->all_amount = (($this->zujin * $this->zuqi) + $this->yiwaixian );
        if( $this->amount<0 ){
            $this->set_error(ApiStatus::CODE_40000);
            $this->flag =false;
        }
        // 库存量
        if( $this->stock<1 ){
            $this->set_error(ApiStatus::CODE_40000);
            $this->flag =false;
        }
        // 商品上下架状态
        if( $this->status!=1 ){
            $this->set_error(ApiStatus::CODE_40000);
            $this->flag =false;
        }
        // 成色 100,99,90,80,70,60
        if( $this->chengse<1 || $this->chengse>100 ){
            $this->set_error(ApiStatus::CODE_40000);
            $this->flag =false;
        }
        if( $this->zuqi_type == 1 ){ // 天
            // 租期[1,12]之间的正整数
            if( $this->zuqi<1 || $this->zuqi>31 ){
                $this->set_error(ApiStatus::CODE_40000);
                $this->flag =false;
            }
        }else{
            // 租期[1,12]之间的正整数
            if( $this->zuqi<1 || $this->zuqi>12 ){
                $this->set_error(ApiStatus::CODE_40000);
                $this->flag =false;
            }
        }
        // sku 必须有 月租金, 且不可低于系统设置的最低月租金
        $zujin_min_price = OldInc::ZUJIN_MIN_PRICE;// 最低月租金
        if( $this->zujin < ($zujin_min_price*100) ){
            $this->set_error(ApiStatus::CODE_40000);
            $this->flag =false;
        }
        // 押金必须
        if( $this->yajin < 1 && $this->payment_type_id != PayInc::MiniAlipay){
            $this->set_error(ApiStatus::CODE_40000);
            $this->flag =false;
        }
        // 规格
        $must_spec_id_list = OldInc::getMustSpecIdList();
        $spec_ids = array_column($this->specs, 'id');
        $spec_id_diff = array_diff($must_spec_id_list, $spec_ids);
        if( count($spec_id_diff)>0 ){
            $this->set_error(ApiStatus::CODE_40000);
            $this->flag =false;
        }
        $arr =[
            'sku' => [
                'sku_id' => $this->sku_id,
                'spu_id' => $this->spu_id,
                'sku_name' => $this->sku_name,
                'spu_name' => $this->spu_name,
                'brand_id' => $this->brand_id,
                'category_id' => $this->category_id,
                'specs' => $this->specs,
                'thumb' => $this->thumb,
                'yiwaixian' => $this->yiwaixian,
                'yiwaixian_cost' => $this->yiwaixian_cost,
                'zujin' => $this->zujin,
                'yajin' => $this->yajin,
                'zuqi' => $this->zuqi,
                'zuqi_type' => $this->zuqi_type,
                'buyout_price' => $this->buyout_price,
                'market_price' => $this->market_price,
                'chengse' => $this->chengse,
                'amount' => $this->amount,
                'all_amount' => $this->all_amount,
                'contract_id'=>$this->contract_id,
                'stock' => $this->stock,
                'pay_type'=>$data['pay_type'],
                'discount_amount' => 0,
                'mianyajin' => 0,
            ]
        ];
        $this->SetSchema($arr);
        return $this->flag;
    }

    /**
     *  验证代扣
     */
    private function UserWithholding($withholding_no,$user_id){
        if( $withholding_no!="" ){
          //  调用支付系统的方法 如下：Y/N
            $status ="Y";
            if( $status!='Y' ){
                //用户已经解约代扣协议
                $this->set_error(ApiStatus::CODE_30001);
                $this->flag =false;
            }
//            // 更新用户签约协议状态
//            $withholding_table = \hd_load::getInstance()->table('payment/withholding_alipay');
//
//            // 一个合作者ID下同一个支付宝用户只允许签约一次
//            $where = [
//                'user_id' => $this->user_id,
//                'agreement_no' => $this->withholding_no,
//            ];
//            $withholding_info = $withholding_table->field(['id','user_id','partner_id','alipay_user_id','agreement_no','status'])->where( $where )->limit(1)->find();
//            if( !$withholding_info ){// 查询失败
//                \zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[创建订单]查询用户代扣协议失败', $where);
//                throw new ComponnetException('下单查询用户代扣协议信息失败');
//            }
//            // 支付宝用户号
//            $this->alipay_user_id = $withholding_info['alipay_user_id'];
            //--网络查询支付宝接口，获取代扣协议状态----------------------------------
//            try {
//                $withholding = new \alipay\Withholding();
//                $status = $withholding->query( $this->alipay_user_id );
//                if( $status=='Y' ){
//                    $this->flag = true;
//                }else{
//                    $this->get_order_creater()->set_error('[下单][代扣组件]用户已经解约代扣协议');
//                    $this->flag = false;
//                    $this->withholding_no = '';// 用户已解约，清空代扣协议号
//                }
//            } catch (\Exception $exc) {
//                \zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[下单][代扣组件]支付宝接口查询用户代扣协议出现异常', $exc->getMessage());
//                $this->get_order_creater()->set_error('[下单][代扣组件]支付宝接口查询用户代扣协议出现异常');
//                $this->flag = false;
//            }
        }else{
            //未签约代扣协议
            $this->set_error(ApiStatus::CODE_30000);
            $this->flag =false;
        }
        return $this->flag;

}
    /**
     *  验证渠道
     */
    private function Channel($appid,$channel_id){
            $info = $this->third->GetChannel($appid);
            if (!is_array($info)) {
                $this->set_error($info);
                return false;
            }
            $this->app_id = intval($info['appid']['id']);
            $this->app_name = $info['appid']['name'];
            $this->app_type = intval($info['appid']['type']);
            $this->app_status = intval($info['appid']['status']) ? 1 : 0;
            $this->channel_id = intval($info['_channel']['id']);
            $this->channel_name = $info['_channel']['name'];
            $this->channel_alone_goods = intval($info['_channel']['alone_goods']) ? 1 : 0;
            $this->channel_status = intval($info['_channel']['status']) ? 1 : 0;

            if ($this->app_status == 0) {
                $this->set_error(ApiStatus::CODE_30002);
                $this->flag =false;
            }
            if ($this->channel_status == 0) {
                $this->set_error(ApiStatus::CODE_30003);
                $this->flag =false;
            }
            if ($this->channel_alone_goods == 1) {
                if ($channel_id != $this->channel_id) {
                    $this->set_error(ApiStatus::CODE_30004);
                    $this->flag =false;
                }
            }
            $arr =[
                'channel' => [
                    'app_id' => $this->app_id,
                    'app_name' => $this->app_name,
                    'app_type' => $this->app_type,
                    'app_status' => $this->app_status,
                    'channel_id' => $this->channel_id,
                    'channel_name' => $this->channel_name,
                    'channel_status' => $this->channel_status,
                    'channel_alone_goods' => $this->channel_alone_goods,
                ]
            ];
        $this->SetSchema($arr);
        return $this->flag;
        }
    /**
     * 设置 错误提示
     * @param string $error  错误提示信息
     */
    public function set_error( string $error ) {
        $this->error = $error;
        return $this;
    }
    /**
     * 获取 错误提示
     * @return string
     */
    public function get_error(){
        return $this->error;
    }

    public function SetSchema($arr){
        $schema = $this->GetSchema();
        $this->schema =array_merge($schema,$arr);
        return $this;
    }
    public function GetSchema(){
        return $this->schema;
    }
    //获取数据之前 计算优惠金额
    public function filter(){
        $data =$this->schema;
       $data=$this->discrease_yajin();
       $data=$this->discount();
        return $data;
    }
    /**
     * 免押
     * @param int $amount
     */
    public function discrease_yajin(){
        $data =$this->schema;
        if( $data['deposit']['jianmian']<0 ){
            return $data;
        }
        // 优惠金额 大于 总金额 时，总金额设置为0.01
        if( $data['deposit']['jianmian'] >= $data['sku']['yajin'] ){
            $data['deposit']['jianmian'] = $data['sku']['yajin'];
        }
        $data['sku']['yajin'] -= $data['deposit']['jianmian'];// 更新押金
        $data['sku']['mianyajin'] += $data['deposit']['jianmian'];// 更新免押金额
        return $data;
    }
    /**
     * 优惠金额 -启用优惠券后金额
     * <p>如果优惠金额 大于 订单金额时，优惠金额值取总订单额进行优惠</p>
     * @param int $amount  金额值，单位：分；必须>=0
     */
    public function discount(){
        // 优惠
        $data =$this->schema;
        $amount =$data['coupon']['discount_amount'];
        if( $amount<0 ){
            return $data;
        }
        $price = $data['sku']['amount']-$data['sku']['yiwaixian'];
        // 优惠金额最多等于总金额
        if( $amount >= $price ){
            $amount = $price;
        }
        $data['sku']['amount'] -= $amount;// 更新总金额
        $data['sku']['discount_amount'] += $amount;// 更新优惠金额
        return $data;
    }

}