<?php
/**
 * 用户创建构造器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;



use App\Lib\User\User;
use Mockery\Exception;

class UserComponnet implements OrderCreater
{

    //组件
    private $componnet;
    //用户ID
    private $userId;
    //手机号
    private $mobile;
    //代扣协议号
    private $withholdingNo;

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

    public function __construct(OrderCreater $componnet, int $userId,$addressId =0)
    {
        $this->componnet =$componnet;
        $this->userId =$userId;

        //获取用户信息
        $userInfo =User::getUser(config('tripartite.Interior_Goods_Request_data'), $this->userId,$addressId);
        if (!is_array($userInfo)) {
            throw new Exception("获取用户接口失败");
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
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderCreater
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
        var_dump("用户组件 -filter");
        return true;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        var_dump("用户组件 -get_data_schema");
        return [];
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        var_dump("用户组件 -create");
        return true;
    }

}