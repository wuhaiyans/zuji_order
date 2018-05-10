<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\Payment\WithholdingApi;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Repository\ThirdInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Order\Modules\Repository\OrderInstalmentRepository;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Models\OrderInstalment;

class WithholdingController extends Controller
{


    // 代扣协议查询
    public function query(Request $request){
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];
        $user_id    = $params['user_id'];
        if(!$appid){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        if(!$user_id){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        // 查询用户协议
        $third = new ThirdInterface();
        $user_info = $third->GetUser($user_id);

        if( !$user_info ){
            Log::error("[代扣解约]lock查询用户信息失败");
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        if( !$user_info['withholding_no'] ){
            Log::error("用户未签约该协议");
            return apiResponse( [], ApiStatus::CODE_71004, '用户未签约该协议');
        }
        if( !$user_info['alipay_user_id'] ){
            Log::error("获取用户支付宝id失败");
            return apiResponse( [], ApiStatus::CODE_71004, '获取用户支付宝id失败');
        }

        $data = [
            'alipay_user_id'    => $user_info['alipay_user_id'], //支付宝用户id（2088开头）
     		'user_id'           => $user_id, //租机平台用户id
     		'agreement_no'      => $user_info['withholding_no'], //签约协议号
        ];
        //--网络查询支付宝接口，获取代扣协议状态----------------------------------
        try {
            $status = WithholdingApi::withholdingstatus($appid, $data);
            if( $status=='Y' ){
                $withholding_status = 'Y';
            }else{
                $withholding_status = 'N';
            }
        } catch (\Exception $exc) {
            Log::error('[代扣协议]查询用户代扣协议出现异常');
            $withholding_status = 'N';
        }

        return apiResponse(['withholding'=>$withholding_status],ApiStatus::CODE_0,"success");
    }

    //签约代扣
    public function sign(Request $request){
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];

        if(!$appid){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $params = filter_array($params, [
            'user_id' => 'required',
            'return_url' => 'required',
        ]);
        if(count($params) < 2){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $user_id    = $params['user_id'];
        // 查询用户协议
        $third = new ThirdInterface();
        $user_info = $third->GetUser($user_id);
        if( !$user_info ){
            Log::error("[代扣解约]lock查询用户信息失败");
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        if( !$user_info['withholding_no'] ){
            Log::error("用户未签约该协议");
            return apiResponse( [], ApiStatus::CODE_71004, '用户未签约该协议');
        }

        if( !$user_info['alipay_user_id'] ){
            Log::error("获取用户支付宝id失败");
            return apiResponse( [], ApiStatus::CODE_71004, '获取用户支付宝id失败');
        }

        try {
            $data = [
                'user_id'           => $params['user_id'], //租机平台用户ID
                'alipay_user_id'    => $user_info['alipay_user_id'], //用户支付宝id（2088开头）
                'front_url'         => $params['return_url'], //前端回跳地址
                'back_url'          => '', //后台通知地址
            ];
            $url = WithholdingApi::withholding( $appid, $data);

            return apiResponse(['url'=>$url],ApiStatus::CODE_0,"success");
        } catch (\Exception $exc) {
            return apiResponse([], ApiStatus::CODE_71008, "获取签约代扣URL地址失败");
        }

    }

    //解约代扣
    public function unsign(Request $request){
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];
        $user_id    = $params['user_id'];
        if(!$appid){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }
        //开启事务
        DB::beginTransaction();

        // 查询用户协议
        $third = new ThirdInterface();
        $user_info = $third->GetUser($user_id);

        if( !$user_info ){
            DB::rollBack();
            Log::error("[代扣解约]lock查询用户信息失败");
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        if( !$user_info['withholding_no'] ){
            DB::rollBack();
            Log::error("用户未签约该协议");
            return apiResponse( [], ApiStatus::CODE_71004, '用户未签约该协议');
        }
        if( !$user_info['alipay_user_id'] ){
            DB::rollBack();
            Log::error("获取用户支付宝id失败");
            return apiResponse( [], ApiStatus::CODE_71004, '获取用户支付宝id失败');
        }
        // 查看用户是否有未扣款的分期
        /* 如果有未扣款的分期信息，则不允许解约 */
        $n = OrderInstalment::query()->where([
            'agreement_no'=> $user_info['withholding_no']])
            ->whereIn('status', [OrderInstalmentStatus::UNPAID,OrderInstalmentStatus::FAIL])->count();

        if( $n > 0 ){
            Log::error("[代扣解约]订单分期查询错误");
            return apiResponse( [], ApiStatus::CODE_50000, '解约失败，有未完成分期');
        }
        try {
            $data = [
                'user_id'           => $user_id, //租机平台用户ID
                'alipay_user_id'    => $user_info['alipay_user_id'], //用户支付宝id（2088开头）
                'agreement_no'      => $user_info['agreement_no'], //签约协议号
            ];
            $b = WithholdingApi::rescind($appid, $data);
            if( !$b ){
                DB::rollBack();
                Log::error("[代扣解约]调用支付宝解约接口失败");
                return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
            }

            // 更新数据
            // 1) 用户表协议码 清除
//            $n = $member_table->where( $user_where )->limit(1)->save(['withholding_no'=>'']);
//            if( $n===false ){
//                $member_table->rollback();
//                //\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣解约]清除用户表协议码失败', $data);
//                api_resopnse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
//                return;
//            }
//            // 2) 用户代扣协议 状态改为 解约(status=2)
//            $withholding_table->where( ['id'=>$withholding_info['id']] )->limit(1)->save(['status'=>2]);
//            if( $n===false ){
//                $member_table->rollback();
//                //\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣解约]更新代扣协议状态失败', $data);
//                api_resopnse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
//                return;
//            }

            // 成功
            DB::commit();
            return apiResponse([],ApiStatus::CODE_0,"success");
        } catch (\Exception $exc) {
            return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');

        }
    }





}
