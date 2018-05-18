<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use App\Lib\Channel\Channel;
use App\Lib\Deposit\Deposit;
use App\Lib\Fengkong\Fengkong;
use App\Order\Modules\Inc\PayInc;
use App\Lib\Payment\WithholdingApi;
use App\Order\Modules\Repository\OrderRepository;

/**
 * 下单验证类
 */
class OrderCreateVerify
{
    protected $third;
    protected $error;
    protected $schema=[];
    protected $Userschema=[];
    protected $flag =true;

    public function __construct()
    {
    }
    public function verify($data,$user_info,$goods_info){
        //验证用户信息
        $users = $this->UserVerify($user_info);
        if(!$users){
            $this->flag =false;
        }

        //判断是否需要签约代扣协议
        if($data['pay_type'] == PayInc::WithhodingPay ){
            $Withhold =$this->UserWithholding($data['appid'],$user_info);
            if(!$Withhold){
                $this->flag =false;
            }
        }
        //判断该渠道是否有效等
        $channel = $this->ChannelVerify($data['appid'],$data['channel_id']);
        if(!$channel){
            $this->flag =false;
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

        //分期单信息
       if($data['pay_type']!=PayInc::FlowerStagePay && $data['zuqi_type'] ==2){
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
        $this->SetUserSchema($arr);
        return $this->flag;

    }

    public function couponVerify($data,$goods){
        //商品总金额
        $total_amount =0;
        foreach ($goods as $k=>$v){

            $total_amount +=($v['sku_info']['zuqi']*$v['sku_info']['shop_price']-$v['sku_info']['buyout_price'])*$v['sku_info']['sku_num'];
        }
        $data['total_amount'] =$total_amount;
        //获取优惠券类型
        $xianjin_coupon =200;
        $first_coupon="Y";//首月0租金

        for($i =0;$i<count($data['coupon']);$i++) {
            $data['new_coupon'][$i]['coupon_no'] = $data['coupon'][$i];
            if($i ==0){
                $data['new_coupon'][$i]['coupon_type'] = 2;//1现金券 2 首月0租金
            }else{
                $data['new_coupon'][$i]['coupon_type'] = 1;//1现金券 2 首月0租金
            }

            $data['new_coupon'][$i]['discount_amount'] = 200;
            $data['new_coupon'][$i]['is_use'] = 0;//是否使用
        }
        $zongyouhui=0;
        foreach ($goods as $k => $v) {
            //var_dump($v['sku_info']);
            $youhui =0;
            for ($i = 0; $i < count($data['new_coupon']); $i++) {
                //var_dump($data['new_coupon']);
                if ($data['new_coupon'][$i]['coupon_type'] == 2 && $data['zuqi_type'] == 2) {//首月0租金
                    $zongzujin = ($v['sku_info']['zuqi'] -1 ) * $v['sku_info']['shop_price'];
                    $youhui+= $v['sku_info']['shop_price'];
                    $data['sku_youhui'][$v['sku_info']['sku_id']] =$youhui;
                    $data['new_coupon'][$i]['is_use'] = 1;
                }
                if ($data['new_coupon'][$i]['coupon_type'] == 1) {//现金券
                    $zongzujin = $v['sku_info']['zuqi'] * $v['sku_info']['shop_price'] - $v['sku_info']['buyout_price'];
                    $data['sku_youhui'][$v['sku_info']['sku_id']] = round($data['new_coupon'][$i]['discount_amount'] / $data['total_amount'] * $zongzujin, 2);

                    if($data['zuqi_type']==2){
                        $data['sku_youhui'][$v['sku_info']['sku_id']]=$data['sku_youhui'][$v['sku_info']['sku_id']]+$youhui;
                    }else{
                        $zongyouhui += $data['sku_youhui'][$v['sku_info']['sku_id']];
                        if ($k == count($goods) - 1) {
                            $data['sku_youhui'][$v['sku_info']['sku_id']] = $data['new_coupon'][$i]['discount_amount'] - $zongyouhui;
                        }
                    }

                    $data['new_coupon'][$i]['is_use'] = 1;
                }
            }
        }
        $this->SetUserSchema($data);
        return $data;

    }
    private function InstalmentVerify(){
        $data =array_merge($this->GetUserSchema(),$this->GetSchema());
        var_dump($data);die;
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
//        $score = Fengkong::getCredit(config('tripartite.Interior_Fengkong_Request_data'),['user_id'=>$this->user_id]);
//        if(!is_array($score)){
//            $this->set_error($score);
//            $this->flag =false;
//        }

        $yidun_data =[
            'yidun'=>[
                'decision' => "0",
                'score' => "0",
                'strategies' =>"111",
            ]
        ];
        //获取风控信息
        $yidun =Fengkong::getYidun(config('tripartite.Interior_Fengkong_Request_data'),[
            'user_id'=>$this->user_id,
            'user_name'=>$this->realname,
            'cert_no'=>$this->cert_no,
            'mobile'=>$this->mobile,
        ]);

//        if(is_array($yidun)){
//            $yidun_data =[
//                'yidun'=>[
//                    'decision' => $yidun['decision'],
//                    'score' => $yidun['score'],
//                    'strategies' =>$yidun['strategies'],
//                ]
//            ];
//        }
        $score['score']=99;
        if($score['score'] <env("ORDER_SCORE")){
            $this->set_error(ApiStatus::CODE_30006);
            $this->flag =false;
        }

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
        $arr =array_merge($user,$yidun_data,[
            'credit' => [
                // 已认证，通过人脸识别，通过风控，认证未过期
                'certified' => $this->certified,
                'certified_platform' => $this->certified_platform,
                'realname' => $this->realname,
                'cert_no' => $this->cert_no,
                'credit' => $score['score'],
                'age' =>$this->age,
                'face' => $this->face,
                'risk' => $this->risk,
                'credit_time' => $this->credit_time,
            ]
        ]);
        //var_dump($arr);die;
        $this->SetUserSchema($arr);
        return $this->flag;
    }

    /**
     * 押金计算验证
     * @param $users
     * @param $goods
     */

    private function DepositVerify($data){
        $arr =array_merge($this->GetUserSchema(),$this->GetSchema());
        $deposit =\App\Lib\Goods\Deposit::getDeposit(config('tripartite.Interior_Goods_Request_data'),[
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
        //var_dump($data);die;
        $this->sku_num =intval($sku_info['sku_num']);
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
        $this->sku_name = $sku_info['sku_name'];// sku_name 使用 spu 的 name 值
        $this->spu_name = $spu_info['name'];
        $this->brand_id = intval($spu_info['brand_id']);
        $this->category_id = intval($spu_info['catid']);
        $this->channel_id = intval($spu_info['channel_id']);
        $this->yiwaixian = $spu_info['yiwaixian']*100;
        $this->yiwaixian_cost = $spu_info['yiwaixian_cost']*100;
        $this->contract_id =$spu_info['contract_id'];
        $this->discount_amount =$sku_info['buyout_price'];
        $this->coupon_amount =$data['sku_youhui'][$this->sku_id];
        // 计算金额
        $this->amount = $this->all_amount = (($this->zujin * $this->zuqi) + $this->yiwaixian );
        if( $this->amount<0 ){
            $this->set_error(ApiStatus::CODE_40000);
            $this->flag =false;
        }
        // 库存量
        if( $this->stock<$this->sku_num ){
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
        // 押金必须
        if( $this->yajin < 1 && $this->payment_type_id != PayInc::MiniAlipay){
            $this->set_error(ApiStatus::CODE_40000);
            $this->flag =false;
        }
        if($this->zuqi_type==1){
            $zuqi_type_name = "day";
        }
        elseif($this->zuqi_type==2){
            $zuqi_type_name = "month";
        }

        $arr =[
            'sku' => [
                'sku_id' => $this->sku_id,
                'spu_id' => $this->spu_id,
                'sku_name' => $this->sku_name,
                'spu_name' => $this->spu_name,
                'sku_no'=>$sku_info['sn'],
                'spu_no'=>$spu_info['sn'],
                'weight'=>$sku_info['weight'],
                'edition'=>$sku_info['edition'],
                'sku_num'=>$this->sku_num,
                'brand_id' => $this->brand_id,
                'category_id' => $this->category_id,
                'specs' => $this->specs,
                'thumb' => $this->thumb,
                'yiwaixian' => priceFormat($this->yiwaixian/100),
                'yiwaixian_cost' => priceFormat($this->yiwaixian_cost/100),
                'zujin' => priceFormat($this->zujin/100),
                'yajin' => priceFormat($this->yajin/100),
                'zuqi' => $this->zuqi,
                'zuqi_type' => $this->zuqi_type,
                'zuqi_type_name'=>$zuqi_type_name,
                'buyout_price' => priceFormat($this->buyout_price/100),
                'market_price' => priceFormat($this->market_price/100),
                'chengse' => $this->chengse,
                'amount' => priceFormat($this->amount/100),
                'all_amount' => priceFormat($this->all_amount/100),
                'contract_id'=>$this->contract_id,
                'stock' => $this->stock,
                'pay_type'=>$data['pay_type'],
                'discount_amount' =>$this->discount_amount,
                'coupon_amount' =>$this->coupon_amount,
                'mianyajin' => 0.00,
            ]
        ];
        $this->SetSchema($arr);
        return $this->flag;
    }

    /**
     *  验证代扣
     */
    private function UserWithholding($appid,$user_info){
        if( $user_info['withholding_no']!="" ){
          //  调用支付系统的方法 如下：Y/N
            $res =WithholdingApi::withholdingstatus($appid,[
                'alipay_user_id' => $user_info['alipay_user_id'],
                'user_id' => $user_info['id'], //租机平台用户id
                'agreement_no' => $user_info['withholding_no'], //签约协议号

            ]);
            $status ="Y";
            if( $status!='Y' ){
                //用户已经解约代扣协议
                $this->set_error(ApiStatus::CODE_30001);
                $this->flag =false;
            }
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
    private function ChannelVerify($appid,$channel_id){
            $info = Channel::getChannel(config('tripartite.Interior_Goods_Request_data'),$appid);
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
    public function SetUserSchema($arr){
        $schema = $this->GetUserSchema();
        $this->Userschema =array_merge($schema,$arr);
        return $this;
    }
    public function GetUserSchema(){
        return $this->Userschema;
    }
    //获取数据之前 计算优惠金额
    public function filter(){
        $data =$this->schema;
        if(!empty($data['deposit'])){
            $data=$this->discrease_yajin();
        }
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


}