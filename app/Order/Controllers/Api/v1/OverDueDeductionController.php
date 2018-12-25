<?php
namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Excel;
use Illuminate\Support\Facades\DB;
use App\Order\Modules\Service\OrderOverdueDeduction;
use App\Order\Modules\Repository\OrderOverdueDeductionRepository;
use Illuminate\Http\Request;
use App\Order\Modules\Repository\Pay\WithholdQuery;

class OverDueDeductionController extends Controller
{
    /**
     * 逾期扣款列表
     * @author qinliping
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overdueDeductionList(Request $request){
        try{

            $allParams = $request->all();
            $params =   $allParams['params'];
            $overdueData = \App\Order\Modules\Service\OrderOverdueDeduction::getOverdueDeductionInfo($params);//获取逾期扣款信息

            if ($overdueData) {

                return apiResponse($overdueData,ApiStatus::CODE_0);
            } else {

                return apiResponse([],ApiStatus::CODE_34007);//获取逾期扣款信息失败
            }

        }catch (\Exception $e) {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }

    }

    /**
     * 逾期扣款列表导出
     * @param Request $request
     */
    public function overdueDeductionExport(Request $request){
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

        // excel头信息
        $headers = ['订单编号', '下单时间', '订单来源','商品名称','用户名','手机号','累计未缴纳租金','押金','扣款状态', '回访标识及记录',
            '扣款时间','扣款金额'];

        $overdueExcel = array();
        while(true) {
            if ($abc>$smallPage) {
                break;
            }
            $offset = ($outPages - 1) * $total_export_count;
            $params['page'] = intval(($offset / $pre_count)+ $abc) ;
            ++$abc;
            $overdueData = array();
            $overdueData = OrderOverdueDeduction::OverdueDeductionExport($params,$pre_count);
            if ($overdueData) {
                $data = array();
                foreach ($overdueData as $item) {
                    $data[] = [
                        $item['order_no'],
                        date('Y-m-d H:i:s', $item['order_time']),
                        $item['appid_name'],
                        $item['goods_name'],
                        $item['user_name'],
                        $item['mobile'],
                        $item['unpaid_amount'],
                        $item['overdue_amount'],
                        $item['deduction_name'],
                        $item['visit_name'],
                        date('Y-m-d H:i:s', $item['deduction_time']),
                        $item['deduction_amount'],

                    ];

                }

                $overdueExcel =  Excel::csvWrite1($data,  $headers, '逾期扣款列表',$abc);

            } else {
                break;
            }
        }

        return $overdueExcel;
        exit;


    }

    /**
     * 逾期扣款操作
     * @Request overdue_id 逾期扣款ID
     * @Request amount 扣款金额
     * @Request remark 备注
     * @return bool ture false
     */
    public function overdueDeposit(Request $request){
        $params             = $request->all();
        $rules = [
            'overdue_id'    => 'required|int',
            'amount'        => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }

        $params = $params['params'];

        $overdueId   = $params['overdue_id'];
        $amount      = $params['amount'];

        $overdueInfo = OrderOverdueDeductionRepository::info(['id' => $overdueId]);
        if(!$overdueInfo){
            // 提交事务
            return apiResponse([], ApiStatus::CODE_32002, "数据异常");
        }

        if( $params['amount'] > $overdueInfo['overdue_amount'] ){
            return apiResponse([], ApiStatus::CODE_32002, "剩余押金不足够扣款");
        }

        if( $amount < 0 ){
            return apiResponse([], ApiStatus::CODE_71003, '扣款金额不能小于1分');
        }

        // 开启事务
        DB::beginTransaction();


        // 生成交易码
        $business_no = createNo('YQ');
        $data = [
            'business_no'       => $business_no,
            'deduction_status'  => 5,// 修改状态支付中
        ];
        $b = OrderOverdueDeductionRepository::save(['id'=>$overdueId],$data);
        if( $b === false ){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_32002, "修改逾期交易号数据异常");
        }

        // 创建扣款记录表数据
        $recordData = [
            'overdue_id'        => $overdueId,              //逾期表ID
            'deduction_amount'  => $amount,                 //扣款金额
            'overdue_amount'    => $overdueInfo['overdue_amount'] - $amount,  //剩余金额
            'remark'            => $params['remark'],       //扣款备注
            'status'            => 5,                       //扣除押金状态0：无效；1：未支付；2：扣款成功；3：扣款失败；4：取消；5：扣款中''
            'create_time'       => time(),
        ];

        $recordb = \App\Order\Modules\Repository\OrderOverdueRecordRepository::create($recordData);
        if( $recordb === false ){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_32002, "修改逾期扣款记录数据异常");
        }

        // 代扣协议编号
        $channel = \App\Order\Modules\Repository\Pay\Channel::Alipay;   //暂时保留
        // 查询用户协议
        $withhold = WithholdQuery::getByUserChannel($overdueInfo['user_id'], $channel);

        $withholdInfo = $withhold->getData();

        $agreementNo = $withholdInfo['out_withhold_no'];
        if (!$agreementNo) {
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_81001, '用户代扣协议编号错误');
        }
        // 代扣接口
        $withholding = new \App\Lib\Payment\CommonWithholdingApi;

        $amount = bcmul($amount , 100 );

        $subject = $overdueInfo['order_no'] . "逾期扣除押金";

        $withholding_data = [
            'agreement_no'  => $agreementNo,            //支付平台代扣协议号
            'out_trade_no'  => $business_no,            //业务系统业务码
            'amount'        => $amount,                 //交易金额；单位：分
            'back_url'      => config('app.url') . "/order/pay/deduDepositNotify",//后台通知地址
            'name'          => $subject,                //交易备注
            'user_id'       => $overdueInfo['user_id'], //业务平台用户id
        ];

        try{
            // 请求代扣接口
            $withholdStatus = $withholding->deduct($withholding_data);

            if( !isset($withholdStatus['status']) || $withholdStatus['status'] != 'processing'){
                DB::rollBack();
                return apiResponse([], ApiStatus::CODE_81004, '预授权转支付失败');
            }
            LogApi::error('[overdueDeposit]逾期扣款押金失败-' . $overdueInfo['order_no'] , $withholdStatus);

        }catch(\App\Lib\ApiException $exc){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_81004, $exc->getMessage());
        }

        // 提交事务
        DB::commit();

        return apiResponse([],ApiStatus::CODE_0,"success");

    }
}