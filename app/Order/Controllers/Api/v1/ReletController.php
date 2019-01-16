<?php
/**
 * 续租
 * Author: wangjinlin
 * Date: 2018/5/17
 * Time: 下午3:58
 */

namespace App\Order\Controllers\Api\v1;

use App\Lib\Common\LogApi;
use App\Lib\ApiStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Service\OrderRelet;
use Illuminate\Http\Request;
use App\Lib\Excel;

class ReletController extends Controller
{

    protected $relet;
    public function __construct(OrderRelet $relet)
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
     *      pay=>[]['pay_type'=>支付方式,'pay_name'=>支付方式名] 短租只有一次性结清,长租可选择代扣或分期或一次性支付
     * ]
     */
    public function pageRelet(Request $request){
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
     * 创建续租并支付
     *
     * 1.创建数据
     * 2.支付
     *
     * @params
     *  'user_id'       => 'required', //用户ID
     *  'zuqi'          => 'required', //租期
     *  'order_no'      => 'required', //订单编号
     *  'pay_type'      => 'required', //支付方式
     *  'user_name'     => 'required',//用户名(手机号)
     *  'goods_id'      => 'required', //设备ID
     *  'relet_amount'  => 'required',//续租金额
     *  'extended_params'=> 'required',//支付扩展参数(不是必须)
     *  'return_url'    => 'required'//前端回调地址
     *
     * @return \Illuminate\Http\JsonResponse
     * 成功时data:
     * 代扣返回空数组[];
     * 一次性分期支付返回array
     * [
     *		'url'		=> '',	// 跳转地址
     *		'params'	=> '',	// 跳转附件参数
     * ]
     */
    public function createRelet(Request $request){
        //接收参数
        $params = $request->input('params');

        //整理参数
        $params = filter_array($params, [
            'user_id'       => 'required', //用户ID
            'goods_id'      => 'required', //设备ID
            'zuqi'          => 'required', //租期
            'order_no'      => 'required', //订单编号
            'pay_type'      => 'required', //支付方式
            'relet_amount'  => 'required',//续租金额
            'user_name'     => 'required',//用户名(手机号)
            'extended_params'=> 'required',//支付扩展参数
            'return_url'    => 'required'//前端回调地址

        ]);
		
        //支付 扩展参数
        $extended_params = isset($params['extended_params'])?$params['extended_params']:[];
        // 支付宝支付扩展参数
        if( $params['pay_channel_id'] == \App\Order\Modules\Repository\Pay\Channel::Alipay ){
            if( isset($extended_params['alipay_params']['trade_type']) && $extended_params['alipay_params']['trade_type']=='MINI' ){
                
				$extended_params['alipay_params']['alipay_user_id'] = session()->get('alipay_user_id');
            }
        }
		
        if(count($params) < 7){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $res = $this->relet->createRelet($params);
        if($res){
            return apiResponse($res,ApiStatus::CODE_0);

        }else{
            return apiResponse([],ApiStatus::CODE_50000,get_msg());

        }

    }

    /**
     * 取消续租
     */
    public function cancelRelet(Request $request){
        //接收参数
        $params = $request->input('params');
        if(isset($params['id']) && !empty($params['id'])){
            if($this->relet->setStatus($params['id'])){
                return apiResponse([],ApiStatus::CODE_0);
            }else{
                return apiResponse([],ApiStatus::CODE_50000, get_msg());
            }
        }else{
            return apiResponse([],ApiStatus::CODE_50000, 'id不能为空');

        }

    }

    /**
     * 续租列表(后台)
     */
    public function listRelet(Request $request){
        try {
            //接收参数
            $params = $request->input('params');
            $req = $this->relet->getList($params);
            return apiResponse($req,ApiStatus::CODE_0);

        }catch(\Exception $e){
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }

    }

    /**
     * 获取未完成续租列表(用户)
     *
     * @param Request $request[
     *      user_id 用户ID
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function userListRelet(Request $request){
        try {
            //接收参数
            $params = $request->input('params');
            if(isset($params['user_id']) && !empty($params['user_id'])){
                $req = $this->relet->getUserList($params);
                if($req){
                    return apiResponse($req,ApiStatus::CODE_0);
                }else{
                    return apiResponse([],ApiStatus::CODE_50000, '该用户无未完成的续租');
                }


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
     * 续租列表导出
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function listReletExport(Request $request){
        set_time_limit(0);
        try {
            $params = $request->all();

            header ( "Content-type:application/vnd.ms-excel" );
            header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "后台续租列表数据导出" ) . ".csv" );

            // 打开PHP文件句柄，php://output 表示直接输出到浏览器
            $fp = fopen('php://output', 'a');

            // 租期，成色，颜色，容量，网络制式
            $headers = ['订单编号','下单时间','交易流水号', '支付方式及通道','用户名','手机号','设备名称','订单金额','租期','续租设备ID','应支付金额','续租时长','状态'];

            // 将中文标题转换编码，否则乱码
            foreach ($headers as $k => $v) {
                $column_name[$k] = iconv('utf-8', 'GB18030', $v);
            }

            // 将标题名称通过fputcsv写到文件句柄
            fputcsv($fp, $column_name);

            $params['pagesize'] = 10000;

            $list = $this->relet->getList($params);
            $list = $list['data'];
            $data = [];

            foreach($list as &$item){
                $reletType =  \App\Order\Modules\Inc\OrderStatus::getZuqiTypeName($item['zuqi_type']);
                $create_time = date("Y-m-d H:i:s",$item['create_time']);
                $goodsInfo = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow(['order_no'=>$item['order_no']]);
                $goodsName = $goodsInfo['goods_name'] ? $goodsInfo['goods_name'] : "";
                $data[] = [
                    $item['order_no'],          // 订单编号
                    $create_time,               // 下单时间
                    $item['trade_no'],          // 交易流水号
                    $item['pay_type'],          // 支付方式及通道
                    $item['user_name'],         // 用户名
                    $item['user_name'],         // 手机号
                    $goodsName,                 // 续租设备
                    $item['relet_amount'],      // 订单金额
                    $item['zuqi'],              // 租期
                    $item['goods_id'],          // 设备名称
                    $item['relet_amount'],      // 应支付金额
                    $item['zuqi'] . $reletType, // 续租时长
                    $item['status']             // 状态
                ];
            }

            $Excel =  Excel::csvOrderListWrite($data, $fp);


            return $Excel;

        } catch (\Exception $e) {

            return apiResponse([],ApiStatus::CODE_50000,$e);

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