<?php
/**
 * 用户创建构造器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;



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
    //风控系统分数
    private $score=0;
    //手机号
    private $mobile;
    //代扣协议号
    private $withholdingNo;
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
    private $face;
    private $age;
    private $risk;



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
        $this->withholdingNo = $userInfo['withholding_no'];
        $this->islock = intval($userInfo['islock'])?1:0;
        $this->block = intval($userInfo['block'])?1:0;
        $this->creditTime = intval( $userInfo['credit_time'] );
        $this->certified = $userInfo['certified']?1:0;
        $this->certifiedPlatform = intval($userInfo['certified_platform']);
        $this->realname = $userInfo['realname'];
        $this->certNo = $userInfo['cert_no'];
        $this->credit = intval($userInfo['credit']);
        $this->face = $userInfo['face']?1:0;
        $age =substr($this->certNo,6,8);
        $now = date("Ymd");
        $this->age = intval(($now-$age)/10000);
        $this->risk = $userInfo['risk']?1:0;
    }

    /**
     * 获取 用户ID
     * @return int
     */
    public function getUserId(){
        return $this->userId;
    }
    /**
     * 设置风控系统分
     *
     */
    public function setScore($score){
        $this->score =$score;
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
        if( $this->block ){
            $this->getOrderCreater()->setError('由于您的退款次数过多，账户暂时无法下单，请联系客服人员！');
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
                'withholding_no'=> $this->withholdingNo,
                'is_lock'=>$this->islock,
                'block'=>$this->block,
                'credit_time'=>$this->creditTime,
                'certified'=>$this->certified,
                'certified_platform'=>$this->certifiedPlatform,
                'realname'=>$this->realname,
                'cert_no'=>$this->certNo,
                'credit'=>$this->credit,
                'face'=>$this->face,
                'age'=>$this->age,
                'risk'=>$this->risk,
                'score'=>$this->score,
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
        $RiskData = [
            'order_no'=>$orderNo,
            'certified'=>$data['user']['certified'],
            'certified_platform'=>$data['user']['certified_platform'],
            'credit'=>$data['user']['credit'],
            'face'=>$data['user']['face'],
            'risk'=>$data['user']['risk'],
            'realname'=>$data['user']['realname'],
            'cret_no'=>$data['user']['cert_no'],
            'score'=>$this->score,
            'create_time'=>time(),
        ];
        $id = OrderUserCertifiedRepository::add($RiskData);
        if(!$id){
            $this->getOrderCreater()->setError("保存用户认证信息失败");
            return false;
        }
        return true;
    }

}