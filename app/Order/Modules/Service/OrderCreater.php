<?php
namespace App\Order\Modules\Service;

use App\Lib\ApiStatus;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserInfo;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use App\Order\Modules\Repository\ThirdInterface;
use Illuminate\Support\Facades\DB;

class OrderCreater
{

    protected $third;
    protected $verify;
    protected $orderRepository;
    protected $orderUserInfoRepository;
    protected $orderGoodsRepository;

    public function __construct(ThirdInterface $third,OrderCreateVerify $orderCreateVerify,OrderRepository $orderRepository,OrderUserInfoRepository $orderUserInfoRepository,OrderGoodsRepository $orderGoodsRepository)
    {
        $this->third = $third;
        $this->verify =$orderCreateVerify;
        $this->orderRepository = $orderRepository;
        $this->orderUserInfoRepository = $orderUserInfoRepository;
        $this->orderGoodsRepository = $orderGoodsRepository;
    }

    /**
     * 创建订单
     * @return bool
     */
    public function create($data)
    {
        $order_no =rand(10000000000000,99999999999999999);

        DB::beginTransaction();
        try {
            //获取用户信息
            $user_info =$this->third->GetUser($data['user_id']);
            if(!is_array($user_info)){
                return $user_info;
            }
            //var_dump($user_info);die;

            //获取商品详情
            $goods_info =$this->third->GetSku($data['sku_id']);
            if(!is_array($goods_info)){
                return $goods_info;
            }
            //var_dump($goods_info);die;
            $data['channel_id'] =$goods_info['spu_info']['channel_id'];
            //下单验证
            $res =$this->verify->Verify($data,$user_info,$goods_info);
            if(!is_array($res)){
                return $res;
            }
            //获取风控信息
            $this->third->GetFengkong();
            $this->third->GetCredit();
            $this->orderRepository->create();
            $this->orderUserInfoRepository->create();
            $this->orderGoodsRepository->create();
            DB::commit();
            die;
        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();die;
        }

    }
}