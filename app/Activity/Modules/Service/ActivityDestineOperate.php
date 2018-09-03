<?php
/**
 *  活动预定操作类
 *  author: wuhaiyan
 */
namespace App\Activity\Modules\Service;

use App\Activity\Modules\Repository\ActivityDestineRepository;

class ActivityDestineOperate
{

    /**
     * 增加活动预定
     * @author wuhaiyan
     * @param $orderNo
     * @return array|bool
     */

    public static function create($data)
    {
        //判断用户是否 已经参与活动
        $b = ActivityDestineRepository::unActivityDestineByUser(18,1);
        var_dump($b);die;
        $destineNo = createNo("YD");

        //
        try{
            DB::beginTransaction();
            $order_no = OrderOperate::createOrderNo(1);
            //订单创建构造器
            $orderCreater = new OrderComponnet($orderNo,$data['user_id'],$data['appid'],$orderType);

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id'],$data['address_id']);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);

            //风控
            $orderCreater = new RiskComponnet($orderCreater);
            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$data['coupon'],$data['user_id']);

            //押金
            $orderCreater = new DepositComponnet($orderCreater);

            //收货地址
            $orderCreater = new AddressComponnet($orderCreater);

            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);



            //分期
            $orderCreater = new InstalmentComponnet($orderCreater);

            //支付
            $orderCreater = new OrderPayComponnet($orderCreater,$data['user_id'],$data['pay_channel_id']);


            //调用各个组件 过滤一些参数 和无法下单原因
            $b = $orderCreater->filter();
            if(!$b){
                DB::rollBack();
                //把无法下单的原因放入到用户表中
                User::setRemark($data['user_id'],$orderCreater->getOrderCreater()->getError());
                set_msg($orderCreater->getOrderCreater()->getError());
                return false;
            }
            $schemaData = $orderCreater->getDataSchema();
            //调用各个组件 创建方法
            $b = $orderCreater->create();
            //创建成功组装数据返回结果
            if(!$b){
                DB::rollBack();
                set_msg($orderCreater->getOrderCreater()->getError());
                return false;
            }

            DB::commit();
            //组合数据
            $result = [
                'url'			=> "123",
            ];
            return $result;

        } catch (\Exception $exc) {
            DB::rollBack();
            set_msg($exc->getMessage());
            return false;
        }
    }



}