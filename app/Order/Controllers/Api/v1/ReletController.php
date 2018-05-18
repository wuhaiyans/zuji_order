<?php
/**
 * 续租
 * User: wangjinlin
 * Date: 2018/5/17
 * Time: 下午3:58
 */

namespace App\Order\Controllers\Api\v1;


class ReletController extends Controller
{

    /**
     * 创建续租
     */
    public function createRelet(){
        //接收参数

        //整理参数

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
    public function listRelet(){
        //接收参数

        //拼接 页数 搜索参数 每页显示数

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