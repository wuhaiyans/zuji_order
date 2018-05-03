<?php
namespace App\Order\Modules\Service;

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
    protected $orderRepository;
    protected $orderUserInfoRepository;
    protected $orderGoodsRepository;

    public function __construct(ThirdInterface $third,OrderRepository $orderRepository,OrderUserInfoRepository $orderUserInfoRepository,OrderGoodsRepository $orderGoodsRepository)
    {
        $this->third = $third;
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

            $this->third->GetFengkong();
            //获取商品详情
            $sku_info =$this->third->GetSku($data['sku_id']);
            if(!is_array($sku_info)){
                return $sku_info;
            }

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