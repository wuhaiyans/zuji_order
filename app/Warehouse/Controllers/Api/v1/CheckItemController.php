<?php
/**
 * User: wangjinlin
 * Date: 2018/5/8
 * Time: 11:38
 */

namespace App\Warehouse\Controllers\Api\v1;


use App\Lib\ApiStatus;
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
     * 线下门店待检测数量
     */
    public function receiveNum(){
        $request = request()->input();
        $channel_id = json_decode($request['userinfo']['channel_id'], true);
        $count = Receive::where(['status'=>Receive::STATUS_RECEIVED,'channel_id'=>$channel_id])->count();
        return \apiResponse(['count'=>$count]);
    }

}