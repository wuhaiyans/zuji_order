<?php
/**
 * User: wangjinlin
 * Date: 2018/5/8
 * Time: 11:38
 */

namespace App\Warehouse\Controllers\Api\v1;


use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Warehouse\Models\CheckItems;
use App\Warehouse\Models\Receive;
use App\Warehouse\Modules\Repository\CheckItemRepository;

class CheckItemController extends Controller
{
    /**
     * 查看检测详情
     *      包括收货信息商品信息和检测信息
     *
     * @param receive_no    收货单号
     * @param goods_no      商品唯一编号
     */
    public function getDetails()
    {
        $rules = [
            'receive_no' => 'required',
            'goods_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $data = CheckItemRepository::getDetails($params);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse($data);
    }

    /**
     * 线下门店查看检测详情
     *      包括收货信息商品信息和检测信息
     *
     * @param order_no  订单编号
     * @param goods_no  商品唯一编号
     */
    public function getXiannxiaDetails()
    {
        $rules = [
            'order_no' => 'required',
            'goods_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        LogApi::info("CheckItem_getDetails_查看检测详情",$params);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $data = CheckItemRepository::getXianxiaDetails($params);
            $data['EvaluationPayInfo'] = \App\Lib\Warehouse\Receive::getEvaluationPayInfo($params);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse($data);
    }

    /**
     * 线下门店待检测数量
     */
    public function receiveNum(){
        $request = request()->input();
        $channel_id = json_decode($request['userinfo']['channel_id'], true);
        $count = Receive::where(['status'=>Receive::STATUS_RECEIVED,'channel_id'=>$channel_id])->count();
        return \apiResponse(['count'=>$count]);
    }

    /**
     * 线下门店待检测列表
     */
    public function xianxiaCheck(){
        $request = request()->input();
        $channel_id = json_decode($request['userinfo']['channel_id'], true);
        $list_obj = Receive::where(['status'=>Receive::STATUS_RECEIVED,'channel_id'=>$channel_id])->get();
        if($list_obj){
            $list = [];
            foreach ($list_obj as $key=>$item){
                $list[$key] = $item->toArray();
                $list[$key]['goods_list'] = $item->goods->toArray();
            }
            return \apiResponse($list);
        }else{
            return \apiResponse([]);
        }
    }

    /**
     * 线下门店根据订单号查询是否显示检测,检测结果按钮
     *
     * @param order_no string 订单编号
     *
     * @return array [
     *      'jiance'=>是否显示检测按钮,
     *      'jiancejieguo'=>是否显示检测结果按钮,
     * ]
     */
    public function reviewButton(){
        $rules = [
            'order_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }
        $obj = Receive::where(['order_no'=>$params])->first();
        if(!$obj){
            return \apiResponse(['jiannce'=>false,'jiancejieguo'=>false]);
        }
        $row = $obj->toArray();
        if($row['status']==Receive::STATUS_RECEIVED){
            return \apiResponse(['jiannce'=>true,'jiancejieguo'=>false]);
        }elseif ($row['status']==Receive::STATUS_FINISH){
            return \apiResponse(['jiannce'=>false,'jiancejieguo'=>true]);
        }else{
            return \apiResponse(['jiannce'=>false,'jiancejieguo'=>false]);
        }

    }

    /**
     * 线下门店检测公共参数
     *
     * 1.定损类型
     */
    public function getPublic(){
        $data = [
            'dingsun_type'=>CheckItems::DINGSUN_TYPE
        ];
        return \apiResponse($data);
    }

}