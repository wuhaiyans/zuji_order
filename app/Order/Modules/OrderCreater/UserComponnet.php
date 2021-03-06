<?php
/**
 * 用户创建构造器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;



use App\Lib\Common\LogApi;
use App\Lib\User\User;
use App\Order\Models\OrderUserInfo;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use App\Order\Modules\Repository\OrderUserRiskRepository;
use Mockery\Exception;

class UserComponnet implements OrderCreater
{

    //组件
    private $componnet;
    private $flag = true;

    //用户ID
    private $userId;
    //手机号
    private $mobile;
    //地址
    private $address=[];

    private $islock;
    private $block;
    private $creditTime;
    private $certified;
    private $certifiedPlatform;
    private $realname;
    private $certNo;
    private $credit;
    private $age;
    private $risk;
    private $addressID;



    //用户信息
    private $userInfo;

    public function __construct(OrderCreater $componnet, int $userId,$addressId =0,$addressInfo = [])
    {
        $this->componnet =$componnet;
        $this->userId =$userId;

        //获取用户信息
        try{
            $userInfo =User::getUser($this->userId,$addressId);
        }catch (\Exception $e){
            LogApi::alert("OrderCreate:获取用户接口失败",['error'=>$e->getMessage()],[config('web.order_warning_user')]);
            LogApi::error("OrderCreate-GetUser-error:".$e->getMessage());
            throw new Exception("GetUser:".$e->getMessage());
        }

        if( empty($addressInfo) ){
            $this->address = $userInfo['address'];
        }else{
            $this->address = $addressInfo;
            $this->address['mobile'] = $userInfo['mobile'];
            $this->address['name'] = $userInfo['realname'];
        }


        $this->mobile = $userInfo['username'];
        $this->islock = intval($userInfo['islock'])?1:0;
        $this->block = intval($userInfo['block'])?1:0;
        $this->creditTime = intval( $userInfo['credit_time'] );
        $this->certified = $userInfo['certified']?1:0;
        $this->certifiedPlatform = intval($userInfo['certified_platform']);
        $this->realname = $userInfo['realname'];
        $this->certNo = $userInfo['cert_no'];
        $this->credit = intval($userInfo['credit']);
        $age =substr($this->certNo,6,8);
        $now = date("Ymd");
        $this->age = intval(($now-$age)/10000);
        $this->risk = $userInfo['risk']?1:0;
        $this->addressID =$addressId;
    }

    /**
     * 获取 用户ID
     * @return int
     */
    public function getUserId(){
        return $this->userId;
    }

    /**
     * 获取 用户地址ID
     * @return int
     */
    public function getAddressId(){
        return $this->addressID;
    }

    /**
     * 获取 用户手机号
     * @return int
     */
    public function getMobile(){
        return $this->mobile ;
    }
    /**
     * 获取 用户身份证号码
     * @return int
     */
    public function getCertNo(){
        return $this->certNo;
    }

    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderComponnet
    {
        return $this->componnet->getOrderCreater();
    }
    /**
     * 过滤
     * <p>注意：</p>
     * <p>在过滤过程中，可以修改下单需要的元数据</p>
     * <p>组件之间的过滤操作互不影响</p>
     * <p>先执行内部组件的filter()，然后再执行组件本身的过滤</p>
     * @return bool
     */
    public function filter(): bool
    {
        if( $this->islock ){
            $this->getOrderCreater()->setError('账号锁定');
            $this->flag = false;
        }
        return $this->flag;
    }
    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        return [
            'user' => [
                'user_id' => $this->userId,
                'user_mobile' => $this->mobile,
                'is_lock'=>$this->islock,
                'block'=>$this->block,
                'credit_time'=>$this->creditTime,
                'certified'=>$this->certified,
                'certified_platform'=>$this->certifiedPlatform,
                'realname'=>$this->realname,
                'cert_no'=>$this->certNo,
                'credit'=>$this->credit,
                'age'=>$this->age,
                'risk'=>$this->risk,
            ],
            'address'=>$this->address,
        ];

    }

    /**
     * 创建用户认证数据
     * @return bool
     */
    public function create(): bool
    {
        $orderNo=$this->componnet->getOrderCreater()->getOrderNo();
        $data =$this->getDataSchema();
        //var_dump($data);die;

        //调用第三方接口 获取用户是否在第三方平台下过单
        $matching =0;

        if($this->addressID){
            $params= [
                'phone'=>isset($data['address']['mobile'])?$data['address']['mobile']:"",
                'identity'=>isset($data['user']['cert_no'])?$data['user']['cert_no']:'',
                'consignee'=>isset($data['address']['name'])?$data['address']['name']:'',
                'province'=>isset($data['address']['province_name'])?$data['address']['province_name']:'',
                'city'=>isset($data['address']['city_name'])?$data['address']['city_name']:'',
                'county'=>isset($data['address']['country_name'])?$data['address']['country_name']:'',
                'shipping_address'=>isset($data['address']['address'])?$data['address']['address']:'',
            ];
            $matching = User::getUserMatching($params);
        }
        //保存用户认证信息
        $RiskData = [
            'order_no'=>$orderNo,
            'certified'=>$data['user']['certified'],
            'certified_platform'=>$data['user']['certified_platform'],
            'credit'=>$data['user']['credit'],
            'realname'=>$data['user']['realname'],
            'cret_no'=>$data['user']['cert_no'],
            'matching'=>$matching,
            'create_time'=>time(),
        ];
        $id = OrderUserCertifiedRepository::add($RiskData);
        if(!$id){
            LogApi::alert("OrderCreate:保存用户认证信息失败",$RiskData,[config('web.order_warning_user')]);
            LogApi::error(config('app.env')."OrderCreate-Add-RistData-error",$RiskData);
            $this->getOrderCreater()->setError("OrderCreate-Add-RistData-error");
            return false;
        }


        return true;
    }

}