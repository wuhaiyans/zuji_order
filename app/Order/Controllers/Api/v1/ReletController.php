<?php
/**
 * 续租
 * Author: wangjinlin
 * Date: 2018/5/17
 * Time: 下午3:58
 */

namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Order\Modules\Service\Relet;
use Illuminate\Support\Facades\Request;

class ReletController extends Controller
{

    protected $relet;
    public function __construct(Relet $relet)
    {
        $this->relet = $relet;
    }

    /**
     * 去续租页数据
     *
     * @request [
     *      goods_id=>订单商品自增id,
     *      user_id=>用户编号,
     *      order_no=>订单编号,
     * ]
     * @return [
     *      订单商品数据,
     *      list=>['zuqi'=>租期单位(短租日长租月),'zujin'=>租金]
     * ]
     */
    public function toCreateRelet(Request $request){
        try{
            //接收参数
            $params = $request->input('params');

            //整理参数
            $params = filter_array($params, [
                'goods_id'      => 'required', //续租商品ID
                'user_id'       => 'required', //用户ID
                'order_no'     => 'required', //订单编号
            ]);
            //判断参数是否设置
            if(count($params) < 3){
                return apiResponse([], ApiStatus::CODE_20001, "参数错误");
            }
            $row = $this->relet->getGoodsZuqi($params);
            if($row){
                return apiResponse($row, ApiStatus::CODE_0);
            }else{
                return apiResponse([],ApiStatus::CODE_50000, get_msg());
            }

        }catch(\Exception $e){
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }
    }

    /**
     * 创建续租
     */
    public function createRelet(Request $request){
        try {
            //接收参数
            $params = $request->input('params');

            //整理参数
            $params = filter_array($params, [
                'user_id'       => 'required', //用户ID
                'zuqi_type'     => 'required', //类型 长租短租
                'zuqi'          => 'required', //租期
                'order_no'      => 'required', //订单编号
                'pay_type'      => 'required', //支付方式及渠道
                'user_name'     => 'required', //用户名
                'user_phone'    => 'required', //手机号
                'goods_id'      => 'required', //续租商品ID
                'relet_amount'  => 'required',//续租金额
            ]);
            if(count($params) < 9){
                return apiResponse([], ApiStatus::CODE_20001, "参数错误");
            }

            if($this->relet->createRelet($params)){
                return apiResponse([],ApiStatus::CODE_0);

            }else{
                return apiResponse([],ApiStatus::CODE_50000,'创建续租失败');

            }

        }catch(\Exception $e){
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }

    }

    /**
     * 取消续租
     */
    public function cancelRelet(Request $request){
        try {
            //接收参数
            $params = $request->input('params');
            if(isset($params['id']) && !empty($params['id'])){
                $par['id'] = $params['id'];
                $par['status'] = 3;
                if($this->relet->setStatus($par)){
                    return apiResponse([],ApiStatus::CODE_0);
                }else{
                    return apiResponse([],ApiStatus::CODE_50000, get_msg());
                }
            }else{
                return apiResponse([],ApiStatus::CODE_50000, 'id不能为空');

            }

        }catch(\Exception $e){
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }
    }

    /**
     * 支付续租费用
     *
     * 1.代扣
     * 2.分期一次性结清
     */
    public function paymentRelet(){
        //接收参数

        //拼接支付参数

        //调用支付接口

        //返回处理成功或失败
    }

    /**
     * 回调支付接口
     */
    public function backPaymentRelet(){
        //接收参数

        //判断成功或失败
        if(1){
            //修改订单状态

        }else{
            //返回错误信息

        }
    }

    /**
     * 续租列表
     */
    public function listRelet(Request $request){
        try {
            //接收参数
            $params = $request->input('params');
            if(isset($params['user_id']) && !empty($params['user_id'])){
                $req = $this->relet->getList($params);
                return apiResponse($req,ApiStatus::CODE_0);

            }else{
                return apiResponse([],ApiStatus::CODE_50000, '用户ID不能为空');

            }

        }catch(\Exception $e){
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }

    }

    /**
     * 续租详情
     */
    public function detailsRelet(Request $request){
        try {
            //接收参数
            $params = $request->input('params');
            if(isset($params['id']) && !empty($params['id'])){
                $req = $this->relet->getRowId($params);
                return apiResponse($req,ApiStatus::CODE_0);

            }else{
                return apiResponse([],ApiStatus::CODE_50000, 'id不能为空');

            }

        }catch(\Exception $e){
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }

    }

    /**
     * 推送到催收列表
     */

    /**
     * 取消催收
     */

    /**
     * 通知催收业务完成
     */



}