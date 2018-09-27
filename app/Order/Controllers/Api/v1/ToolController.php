<?php
namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderReturnCreater;
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
     * @params
     * order_no  => ''  //订单编号   string 【必选】
     *
     * @params array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @param Request $request
     */
    public function refundRefuse(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'=> 'required',    //订单编号
        ]);
        if(count($param)<1){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res= OrderReturnCreater::refundRefuse($param['order_no'] ,$orders['userinfo']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_33002,"退款审核失败");
        }
        return apiResponse([],ApiStatus::CODE_0);
    }

    /**
     * 订单状态是已发货，用户拒签
     * @params
     * order_no  => ''  //订单编号  string 【必选】
     * @param Request $request
     */
    public function refuseSign(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'=> 'required',    //订单编号
        ]);
        if(count($param)<1){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res= OrderReturnCreater::refuseSign($param['order_no'] ,$orders['userinfo']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_33009,"修改失败");
        }
        return apiResponse([],ApiStatus::CODE_0);
    }

    /**
     * 超过七天无理由退换货，没到租赁日期的退货订单
     * @params
     * order_no           => ''  //订单编号  string 【必选】
     * compensate_amount  => ''  //赔偿金额  string 【必选】
     * @param Request $request
     */
    public function advanceReturn(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $param = filter_array($params,[
            'order_no'=> 'required',    //订单编号
            'compensate_amount'=> 'required',    //赔偿金额
        ]);
        if(count($param)<2){
            return  apiResponse([],ApiStatus::CODE_20001);
        }
        $res= OrderReturnCreater::refuseSign($param ,$orders['userinfo']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_33009,"修改失败");
        }
        return apiResponse([],ApiStatus::CODE_0);
    }








}
?>
