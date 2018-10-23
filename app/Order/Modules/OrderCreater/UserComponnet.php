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



    //用户信息
    private $userInfo;

    public function __construct(OrderCreater $componnet, int $userId,$addressId =0,$addressInfo = [])
    {
        $this->componnet =$componnet;
        $this->userId =$userId;

        //获取用户信息
        $userInfo =User::getUser($this->userId,$addressId);
        if (!is_array($userInfo)) {
            throw new Exception("获取用户接口失败");
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
    }

    /**
     * 获取 用户ID
     * @return int
     */
    public function getUserId(){
        return $this->userId;
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
        $params= [
            'phone'=>$data['address']['mobile'],
            'identity'=>$data['user']['cert_no'],
            'consignee'=>$data['address']['name'],
            'province'=>$data['address']['province_name'],
            'city'=>$data['address']['city_name'],
            'county'=>$data['address']['country_name'],
            'shipping_address'=>$data['address']['address'],
        ];
        $matching = User::getUserMatching($params);

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
            LogApi::error(config('app.env')."[下单]保存用户认证信息失败",$RiskData);
            $this->getOrderCreater()->setError("保存用户认证信息失败");
            return false;
        }


        return true;
    }

}