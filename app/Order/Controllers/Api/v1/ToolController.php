<?php
namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderReturnCreater;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\Common\LogApi;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;



class ToolController extends Controller
{
    protected static $email = ['qinliping@huishoubao.com.cn'];
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
            $operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
            if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
                return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
            }
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


            //订单详情
            $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById($order_no);
            if (!$orderInfo) {
                LogApi::debug('[ToolDelay]订单不存在');
                return apiResponse([],ApiStatus::CODE_50000, "订单不存在");
            }

            if($orderInfo['zuqi_type'] == \App\Order\Modules\Inc\OrderStatus::ZUQI_TYPE2){
                return apiResponse([],ApiStatus::CODE_50000, "租期类型错误-目前只支持短租");
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

            //查询goods_no
            $goodsInfo  = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow(['order_no'=>$order_no]);
            $goodsNo    = $goodsInfo ? $goodsInfo['goods_no'] : "";

            //记录日志
            $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
                'order_no'      => $order_no,
                'action'        => '异常订单延期',
                'business_key'  => \App\Order\Modules\Inc\OrderStatus::BUSINESS_RELET,//此处用常量
                'business_no'   => createNo(9),
                'goods_no'      => $goodsNo,
                'operator_id'   => $operateUserInfo['uid'],
                'operator_name' => $operateUserInfo['username'],
                'operator_type' => $operateUserInfo['type']==1?\App\Lib\PublicInc::Type_Admin:\App\Lib\PublicInc::Type_User,
                'msg'           => '后台操作异常延期',
            ]);
            if( !$goodsLog ){
                //事务回滚
                DB::rollBack();
                return apiResponse([],ApiStatus::CODE_92700,'设备日志生成失败！');
            }

            DB::commit();

            return apiResponse([],ApiStatus::CODE_0,"success");
        }catch(\Exception $exs){
            \App\Lib\Common\LogApi::alert('tool-create:exception-error', [
                'pos'=>implode('|', [__FILE__,__METHOD__,__LINE__]),//位置
                'tip'=>'延期失败',//错误信息提示
                'data'=>['$ex'=>$exs],//错误返回数据
            ],self::$email);
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
            \App\Lib\Common\LogApi::alert('tool-create:exception-error', [
                'pos'=>implode('|', [__FILE__,__METHOD__,__LINE__]),//位置
                'tip'=>'异常订单-退款审核拒绝失败',//错误信息提示
                'data'=>['$ex'=>$res],//错误返回数据
            ],self::$email);
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
            \App\Lib\Common\LogApi::alert('tool-create:exception-error', [
                'pos'=>implode('|', [__FILE__,__METHOD__,__LINE__]),//位置
                'tip'=>'异常订单-拒签失败',//错误信息提示
                'data'=>['$ex'=>$res],//错误返回数据
            ],self::$email);
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
            \App\Lib\Common\LogApi::alert('tool-create:exception-error', [
                'pos'=>implode('|', [__FILE__,__METHOD__,__LINE__]),//位置
                'tip'=>'异常订单-中途退机失败',//错误信息提示
                'data'=>['$ex'=>$res],//错误返回数据
            ],self::$email);
            return apiResponse([],ApiStatus::CODE_33009,"修改失败");
        }
        return apiResponse([],ApiStatus::CODE_0);
    }

    /**
     *
     * 用户逾期列表
     *[
     * 'visit_id'    => '',  【可选】  回访id    int
     * 'keywords'    =>'',   【可选】  关键字    string
     * 'kw_type'     =>'',   【可选】  查询类型  string
     * 'zuqi_type'   =>'',   【可选】  租期类型  int
     *  'overDue_period'=>'', 【可选】 逾期时间段
     * 'page'        =>'',   【可选】  页数       int
     * 'size'        =>''    【可选】  条数       int
     * ]
     */
    public function overDue(Request $request){
        try{

            $orders =$request->all();
            $params = $orders['params'];
           // $params['channel_id'] = json_decode($orders['userinfo']['channel_id'], true);
          //  LogApi::debug("[overDue]接收用户信息",['params'=>$params,'channel_id'=>$params['channel_id']]);
            $orderData = OrderReturnCreater::overDue($params);

            if ($orderData['code']===ApiStatus::CODE_0) {

                return apiResponse($orderData['data'],ApiStatus::CODE_0);
            } else {

                return apiResponse([],ApiStatus::CODE_34007);
            }

        }catch (\Exception $e) {
            \App\Lib\Common\LogApi::alert('tool-create:exception-error', [
                'pos'=>implode('|', [__FILE__,__METHOD__,__LINE__]),//位置
                'tip'=>'获取逾期列表失败',//错误信息提示
                'data'=>['$ex'=>$e],//错误返回数据
            ],self::$email);
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }
    }

    /**
     *
     * 用户逾期列表导出
     *
     */
    public function overDueExport(Request $request){
        set_time_limit(0);
        $params = $request->all();
        $pageSize = 50000;
        if (isset($params['size']) && $params['size']>=50000) {
            $pageSize = 50000;
        } else {
            $pageSize = $params['size'];
        }
        $params['page'] = $params['page']?? 1;
        $outPages       = $params['page']?? 1;

        $total_export_count = $pageSize;
        $pre_count = $params['smallsize']?? 500;

        $smallPage = ceil($total_export_count/$pre_count);
        $abc = 1;

        $headers = ['订单编号', '下单时间','租期结束时间', '逾期天数','订单状态','冻结状态','订单来源','第三方平台下单','支付方式及通道','回访标识','回访备注', '用户名',
            '手机号','详细地址','设备名称','规格','租期','总租金'];

        $orderExcel = array();
        while(true) {
            if ($abc>$smallPage) {
                break;
            }
            $offset = ($outPages - 1) * $total_export_count;
            $params['page'] = intval(($offset / $pre_count)+ $abc) ;
            ++$abc;
            $orderData = array();
            LogApi::debug("[overDueExport]导出参数",['params'=>$params,'pre_count'=>$pre_count]);

            $orderData = OrderReturnCreater::overDueExport($params,$pre_count);
            LogApi::debug("[overDueExport]查询结果",$orderData);
            if ($orderData) {
                $data = array();
                foreach ($orderData['data']['data'] as $item) {
                    $data[] = [
                        $item['order_no'],
                        date('Y-m-d H:i:s', $item['create_time']),
                        date('Y-m-d H:i:s', $item['end_time']),
                        $item['overDue_time'],
                        $item['order_status_name'],
                        $item['freeze_type_name'],
                        $item['appid_name'],

                        $item['matching_name'],
                        $item['pay_type_name'],
                        $item['visit_name'],
                        $item['visit_text'],
                        $item['name'],
                        $item['mobile'],
                        $item['address_info'],
                        implode(",",array_column($item['goodsInfo'],"goods_name")),
                        implode(",",array_column($item['goodsInfo'],"specs")),
                        implode(",",array_column($item['goodsInfo'],"zuqi_name")),
                        $item['order_amount'],
                    ];

                }
                LogApi::debug("【overDueExport】导出数据列表",$data);
                $orderExcel =  \App\Lib\Excel::csvWrite1($data,  $headers, '逾期列表导出',$abc);

            } else {
                break;
            }
        }

        return $orderExcel;
        exit;
    }





}
?>
