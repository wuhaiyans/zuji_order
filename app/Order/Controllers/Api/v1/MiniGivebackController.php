<?php
namespace App\Order\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderGiveback;
use App\Order\Modules\Inc\OrderGivebackStatus;
use App\Order\Modules\Service\OrderGoods;
use App\Order\Modules\Service\OrderGoodsInstalment;
use App\Order\Modules\Service\OrderWithhold;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Repository\Order\Goods;

/**
 * 小程序还机处理接口
 * Class MiniGivebackController
 * @package App\Order\Controllers\Api\v1
 * @author zhangjinhui
 */

class MiniGivebackController extends Controller
{
    /**
     * 小程序还机信息详情接口
     * @param Request $request
     */
    public function GivebackInfo(Request $request){
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $paramsArr = isset($params['params'])? $params['params'] :[];
        if( empty($paramsArr['goods_no']) ) {
            return apiResponse([],ApiStatus::CODE_91001);
        }
        $goodsNo = $paramsArr['goods_no'];//提取商品编号
        //-+--------------------------------------------------------------------
        // | 通过商品编号获取需要展示的数据
        //-+--------------------------------------------------------------------

        //初始化最终返回数据数组
        $data = [];
        var_dump($paramsArr);
        $orderGoodsInfo = $this->__getOrderGoodsInfo($goodsNo);
        var_dump($orderGoodsInfo);die;
        if( !$orderGoodsInfo ) {
            return apiResponse([], get_code(), get_msg());
        }

        //创建服务层对象
        $orderGivebackService = new OrderGiveback();
        //获取还机单基本信息
        $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo( $goodsNo );
        //还机信息为空则返回还机申请页面信息
        if( !$orderGivebackInfo ){
            //组合最终返回商品基础数据
            $data['goods_info'] = $orderGoodsInfo;//商品信息
            $data['giveback_address'] = config('tripartite.Customer_Service_Address');
            $data['giveback_username'] = config('tripartite.Customer_Service_Name');;
            $data['giveback_tel'] = config('tripartite.Customer_Service_Phone');;
            $data['status'] = ''.OrderGivebackStatus::adminMapView(OrderGivebackStatus::STATUS_APPLYING);//状态
            $data['status_text'] = '还机申请中';//后台状态

            //物流信息
            $logistics_list = [];
            $logistics = \App\Warehouse\Config::$logistics;
            foreach ($logistics as $id => $name) {
                $logistics_list[] = [
                    'id' => $id,
                    'name' => $name,
                ];
            }
            $data['logistics_list'] = $logistics_list;//物流列表
            return apiResponse(GivebackController::givebackReturn($data),ApiStatus::CODE_0,'数据获取成功');
        }


        $orderGivebackInfo['status_name'] = OrderGivebackStatus::getStatusName($orderGivebackInfo['status']);
        $orderGivebackInfo['payment_status_name'] = OrderGivebackStatus::getPaymentStatusName($orderGivebackInfo['payment_status']);
        $orderGivebackInfo['evaluation_status_name'] = OrderGivebackStatus::getEvaluationStatusName($orderGivebackInfo['evaluation_status']);
        $orderGivebackInfo['yajin_status_name'] = OrderGivebackStatus::getEvaluationStatusName($orderGivebackInfo['yajin_status']);



        //组合最终返回商品基础数据
        $data['goods_info'] = $orderGoodsInfo;//商品信息
        $data['giveback_info'] =$orderGivebackInfo;//还机单信息
        //判断是否已经收货
        $isDelivery = false;
        if( $orderGivebackInfo['status'] != OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY ){
            $isDelivery = true;
        }
        //快递信息
        $data['logistics_info'] =[
            'logistics_name' => $orderGivebackInfo['logistics_name'],
            'logistics_no' => $orderGivebackInfo['logistics_no'],
            'is_delivery' => $isDelivery,//是否已收货
        ];
        //检测结果
        if( $orderGivebackInfo['evaluation_status'] != OrderGivebackStatus::EVALUATION_STATUS_INIT ){
            $data['evaluation_info'] = [
                'evaluation_status_name' => $orderGivebackInfo['evaluation_status_name'],
                'evaluation_status_remark' => $orderGivebackInfo['yajin_status'] == OrderGivebackStatus::YAJIN_STATUS_RETURN_COMOLETION? '押金已退还至支付账户，由于银行账务流水，请耐心等待1-3个工作日。':'',
                'reamrk' => '',
                'compensate_amount' => '',
            ];
        }
        if( $orderGivebackInfo['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED ){
            $data['evaluation_info']['remark'] = $orderGivebackInfo['evaluation_remark'];//检测备注
            $data['evaluation_info']['compensate_amount'] = $orderGivebackInfo['compensate_amount'];//赔偿金额
        }
        //退还押金
        if( $orderGivebackInfo['yajin_status'] == OrderGivebackStatus::YAJIN_STATUS_IN_RETURN || $orderGivebackInfo['yajin_status'] == OrderGivebackStatus::YAJIN_STATUS_RETURN_COMOLETION ){
            $data['yajin_info'] = [
                'yajin_status_name' => $orderGivebackInfo['yajin_status_name'],
            ];
        }
        //赔偿金额计算(检测不合格，没有未支付分期金额，押金》赔偿金，才能押金抵扣)
        if( $orderGivebackInfo['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED && !$orderGivebackInfo['instalment_amount'] && $orderGoodsInfo['yajin']>=$orderGivebackInfo['compensate_amount'] ){
            $data['compensate_info'] = [
                'compensate_all_amount' => $orderGivebackInfo['instalment_amount'] + $orderGivebackInfo['compensate_amount'],
                'compensate_deduction_amount' => $orderGivebackInfo['instalment_amount'] + $orderGivebackInfo['compensate_amount'],
                'compensate_release_amount' => $orderGoodsInfo['yajin'] - ($orderGivebackInfo['instalment_amount'] + $orderGivebackInfo['compensate_amount']),
            ];
        }else{
            $data['compensate_info'] = [
                'compensate_all_amount' => $orderGivebackInfo['instalment_amount'] + $orderGivebackInfo['compensate_amount'],
                'compensate_deduction_amount' => 0,
                'compensate_release_amount' => $orderGoodsInfo['yajin'],
            ];
        }

        $data['status'] = ''.OrderGivebackStatus::adminMapView($orderGivebackInfo['status']);//状态

        //物流信息
        return apiResponse(GivebackController::givebackReturn($data),ApiStatus::CODE_0,'数据获取成功');
    }

    /**
     * 小程序提交还机申请接口
     * @param $param
     * @return array
     */
    public function GivebackCreate(Request $request){

    }

    /**
     * 小程序还机支付赔偿金额接口
     * @param $param
     * @return array
     */
    public function GivebackPay(Request $request){

    }

    private function __getOrderGoodsInfo( $goodsNo ){

        //获取商品基础数据
        //创建商品服务层对象
        $orderGoodsService = new OrderGoods();
        $orderGoodsInfo = $orderGoodsService->getGoodsInfo($goodsNo);
        var_dump($orderGoodsInfo);
        if( !$orderGoodsInfo ) {
            return [];
        }
        //商品信息解析
        $orderGoodsInfo['goods_specs'] = filterSpecs($orderGoodsInfo['specs']);//商品规格信息
        $orderGoodsInfo['goods_img'] = $orderGoodsInfo['goods_thumb'];//商品缩略图
        return $orderGoodsInfo;
    }
}