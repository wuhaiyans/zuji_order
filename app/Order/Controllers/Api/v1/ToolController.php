<?php
namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderReturnCreater;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\Common\LogApi;



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
        try{
            $params     = $request->all();
            // 参数过滤
            $rules = [
                'begin_time'       => 'required',  // 开始时间
                'end_time'         => 'required',  // 结束时间
            ];
            $validateParams = $this->validateParams($rules,$params);
            if ($validateParams['code'] != 0) {
                return apiResponse([],$validateParams['code']);
            }

            $order_no   = $params['params']['order_no'];
            $begin_time = $params['params']['begin_time'];
            $end_time   = $params['params']['end_time'];


            $begin_time = strtotime($begin_time) > 0 ? strtotime($begin_time) : 0 ;
            $end_time   = strtotime($end_time) > 0 ? strtotime($end_time) : 0 ;

            if($begin_time >= $end_time){
                return apiResponse([], ApiStatus::CODE_50000, "时间错误");
            }


            $end_time = $end_time + (3600 * 24) - 1;
            $day = ceil( ($end_time - $begin_time) / 86400 );

            // 开启事务
            DB::beginTransaction();

            $data = [
                'begin_time'    => $begin_time,
                'end_time'      => $end_time,
                'zuqi'          => $day,
            ];

            // 修改ordergoods表
            $res = \App\Order\Models\OrderGoods::where([
                ['order_no', '=', $order_no]
            ])->update($data);
            if(!$res){
                LogApi::debug('[ToolDelay]修改order_goods失败');
                DB::rollBack();
                return apiResponse([],ApiStatus::CODE_50000, "修改order_goods失败");
            }

            $unitData = [
                'begin_time'    => $begin_time,
                'end_time'      => $end_time,
                'unit_value'    => $day,
            ];

            $result = \App\Order\Models\OrderGoodsUnit::where([
                ['order_no', '=', $order_no]
            ])->update($unitData);
            if(!$result){
                LogApi::debug('[ToolDelay]修改order_goods_unit失败');
                DB::rollBack();
                return apiResponse([],ApiStatus::CODE_50000,"修改order_goods_unit失败");
            }

            DB::commit();

            return apiResponse([],ApiStatus::CODE_0,"success");
        }catch(\Exception $exs){
            LogApi::error('订单延期处理异常',$exs);
            return apiResponse([],ApiStatus::CODE_50004,$exs->getMessage());
        }

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
     * [
     *      'order_no'   =>  '', //订单编号  string 【必选】
     *      'compensate_amount'=>'' //赔偿金额  string  【必选】
     * ]
     *
     * @params array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
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
        $res= OrderReturnCreater::advanceReturn($param ,$orders['userinfo']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_33009,"修改失败");
        }
        return apiResponse([],ApiStatus::CODE_0);
    }

    /**
     *
     * 用户逾期列表
     *
     */
    public function overDue(Request $request){
        try{

            $orders =$request->all();
            $params = $orders['params'];

            $orderData = OrderReturnCreater::overDue($params);

            if ($orderData['code']===ApiStatus::CODE_0) {

                return apiResponse($orderData['data'],ApiStatus::CODE_0);
            } else {

                return apiResponse([],ApiStatus::CODE_34007);
            }

        }catch (\Exception $e) {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }
    }







}
?>
