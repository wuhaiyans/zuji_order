<?php
/**
 * 续租
 * User: wangjinlin
 * Date: 2018/5/17
 * Time: 下午3:58
 */

namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use Illuminate\Support\Facades\Request;

class ReletController extends Controller
{
    /**
     * 去创建续租页数据
     */
    public function toCreateRelet(Request $request){
        //接收参数
        $req = $request->all();

        //整理参数
        //判断参数是否设置
        if(empty($req['order_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"order_no不能为空");
        }
        if(empty($req['goods_id'])){
            return apiResponse([],ApiStatus::CODE_20001,"goods_id不能为空");
        }
    }

    /**
     * 创建续租
     */
    public function createRelet(Request $request){
        //接收参数
        $req = $request->all();

        //整理参数
        //判断参数是否设置
        if(empty($req['zuqi_type'])){
            return apiResponse([],ApiStatus::CODE_20001,"zuqi_type不能为空");
        }
        if(empty($req['zuqi'])){
            return apiResponse([],ApiStatus::CODE_20001,"zuqi不能为空");
        }
        if(empty($req['order_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"order_no不能为空");
        }

        //创建

        //返回成功或失败

    }

    /**
     * 取消续租
     */
    public function cancelRelet(){
        //接收参数

        //取消

        //返回成功或失败

    }

    /**
     * 支付续租费用
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
        //接收参数
        //接收参数
        $req = $request->all();

        //拼接 页数 搜索参数 每页显示数
        $pages = 1;
        $select = '';
        $num = 20;


        //查询

        //返回

    }

    /**
     * 续租详情
     */
    public function detailsRelet(){
        //接收参数

        //查询

        //返回
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