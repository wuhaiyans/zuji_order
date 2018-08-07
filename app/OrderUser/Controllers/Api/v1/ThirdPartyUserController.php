<?php
/**
 * 第三方平台下单用户管理
 *
 * User: jinlin wang
 * Date: 2018/8/1
 * Time: 16:38
 */
namespace App\OrderUser\Controllers\Api\v1;


use App\Lib\ApiStatus;
use App\OrderUser\Models\ThirdPartyUser;
use App\OrderUser\Modules\Repository\ThirdPartyUserRepository;
use App\OrderUser\Modules\Service\ThirdPartyUserService;

class ThirdPartyUserController extends Controller
{

    /**
     * 列表
     */
    public function lists()
    {
        $params = $this->_dealParams([]);

        $list = ThirdPartyUserService::lists($params);
        return apiResponse($list);

    }

    /**
     * 修改下单用户信息
     */

    public function update(){
        $params = $this->_dealParams([]);

        if(!$params){
            return apiResponse([], ApiStatus::CODE_10104, '参数错误');
        }

        if(!$params['id']){
            return apiResponse([], ApiStatus::CODE_10104, '参数id必须');
        }

        try {
            ThirdPartyUserService::update($params);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_70006, $e->getMessage());
        }

        return apiResponse([]);
    }

    /**
     * 添加一条
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(){
        $params = $this->_dealParams([]);

        if(!$params['phone']){
            return apiResponse([], ApiStatus::CODE_10104, '收货人手机号必填');
        }
        if(!$params['consignee']){
            return apiResponse([], ApiStatus::CODE_10104, '收货人姓名必填');
        }
        if(!$params['province']){
            return apiResponse([], ApiStatus::CODE_10104, '省');
        }
        if(!$params['city']){
            return apiResponse([], ApiStatus::CODE_10104, '市');
        }
        if(!$params['county']){
            return apiResponse([], ApiStatus::CODE_10104, '区县');
        }
        if(!$params['shipping_address']){
            return apiResponse([], ApiStatus::CODE_10104, '收货地址详情必填');
        }
        if(!$params['status']){
            return apiResponse([], ApiStatus::CODE_10104, '订单状态必填');
        }
        if(!$params['platform']){
            return apiResponse([], ApiStatus::CODE_10104, '下单平台必填');
        }
        if(!$params['types']){
            return apiResponse([], ApiStatus::CODE_10104, '类型必填');
        }

        try {
            $data = ThirdPartyUserService::add($params);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_70001, $e->getMessage());
        }

        return apiResponse($data);

    }

    /**
     * 根据ID查询一条数据并返回
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRow(){
        $rules = [
            'id' => 'required',
        ];
        $params = $this->_dealParams($rules);
        if(!$params){
            return apiResponse([], ApiStatus::CODE_10104, '参数错误');
        }

        try {
            $row = ThirdPartyUserRepository::getRow($params['id']);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_70005, $e->getMessage());
        }

        return apiResponse($row);
    }

    /**
     * 根据新增数据查询相似订单
     *
     * @params array 二维数组
     * matching=>[
     *      ['phone'=>'手机号','identity'=>'身份证','consignee'=>'收货人','province'=>'省','city'=>'市','county'=>'区县','shipping_address'=>'详细地址'],
     *      ['phone'=>'手机号','identity'=>'身份证','consignee'=>'收货人','province'=>'省','city'=>'市','county'=>'区县','shipping_address'=>'详细地址'],
     * ]
     * @return array 三维数组
     * [
     *      ['phone'=>'手机号','identity'=>'身份证','consignee'=>'收货人','province'=>'省','city'=>'市','county'=>'区县','shipping_address'=>'详细地址','start_time'=>'开始日期','end_time'=>'结束日期','platform'=>'平台','status'=>'状态'],
     * ]
     */
    public function matching(){
        $rules = [
            'matching' => 'required',
        ];
        $params = $this->_dealParams($rules);
        if(!$params['matching']){
            return apiResponse([], ApiStatus::CODE_10104, '参数错误');
        }

        try{
            $data = ThirdPartyUserService::matching($params['matching']);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_70004, $e->getMessage());
        }

        return apiResponse($data);

    }

    /**
     * 删除
     * @return \Illuminate\Http\JsonResponse
     */
    public function del(){
        $rules = [
            'id' => 'required',
        ];
        $params = $this->_dealParams($rules);
        if(!$params['id']){
            return apiResponse([], ApiStatus::CODE_10104, '参数错误');
        }

        try{
            ThirdPartyUserRepository::del($params['id']);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_70002, $e->getMessage());
        }

        return apiResponse([]);

    }

    /**
     * 审核通过
     * @return \Illuminate\Http\JsonResponse
     */
    public function audit(){
        $rules = [
            'id' => 'required',
        ];
        $params = $this->_dealParams($rules);
        if(!$params['id']){
            return apiResponse([], ApiStatus::CODE_10104, '参数错误');
        }

        try{
            ThirdPartyUserRepository::audit($params['id']);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_70003, $e->getMessage());
        }

        return apiResponse([]);

    }

    /**
     * H5、小程序、App下单 匹配
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderMatching(){
        $rules = [
            'phone' => 'required',
            'identity' => 'required',
            'consignee' => 'required',
            'province' => 'required',
            'city' => 'required',
            'county' => 'required',
            'shipping_address' => 'required',
        ];
        $params = $this->_dealParams($rules);
        if(!$params){
            return apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }
        try{
            $data = ThirdPartyUserRepository::matching_row($params);
            if($data){
                $row = ['matching'=>1,'msg'=>'匹配到数据'];
            }else{
                $row = ['matching'=>0,'msg'=>'未匹配到数据'];
            }
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_70003, $e->getMessage());
        }

        return apiResponse($row);

    }

    /**
     * 定时任务
     *  开始时间
     *
     * 进入租用开始时间 已支付,已发货,已签收 改为租用中
     */
    public function start(){
        ThirdPartyUserRepository::start();
    }

    /**
     * 定时任务
     *  结束时间
     *
     * 过租用结束时间 已支付,已发货,已签收,租用中 改为已完成
     */
    public function end(){

    }

    /**
     * 导入历史已下单用户execl表
     */
    public function excel(){

    }

    /**
     * 公共数据
     */
    public function publics()
    {
        $data = [
            'status_list' => ThirdPartyUser::sta(),
            'platform_list'    => ThirdPartyUser::platform(),
            'pinpai_list'    => ThirdPartyUser::pinpai(),
            'chengse_list'    => ThirdPartyUser::chengse(),
            'types_list'    => ThirdPartyUser::types(),
            'spb_cb'    => ThirdPartyUser::spb_cb(),
            'select'    => ThirdPartyUser::SELECT,
        ];
        return apiResponse($data);
    }

}