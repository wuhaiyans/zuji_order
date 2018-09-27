<?php
namespace App\Order\Controllers\Api\v1;

use Illuminate\Http\Request;



class ToolController extends Controller
{

    /**
     * 延期
     * @author maxiaoyu
     * @param Request $request
     * $params[
     *   'day'  => '', //【必须】int 延期天数
     * ]
     * @return bool true false
     */
    public function Delay(Request $request){


    }

    /**
     * 订单状态是备货中，用户取消订单，客服审核拒绝
     * @param Request $request
     */
    public function refundRefuse(Request $request){

    }

    /**
     * 订单状态是已发货，用户拒签
     * @param Request $request
     */
    public function refuseSign(Request $request){

    }

    /**
     * 超过七天无理由退换货，没到租赁日期的退货订单
     * @param Request $request
     */
    public function advanceReturn(Request $request){

    }








}
?>
