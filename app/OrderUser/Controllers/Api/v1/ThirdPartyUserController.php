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
use App\Lib\Excel;
use App\OrderUser\Models\ThirdPartyUser;
use App\OrderUser\Modules\Repository\ThirdPartyUserRepository;
use App\OrderUser\Modules\Service\ThirdPartyUserService;
use App\Warehouse\Modules\Service\ImeiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

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
        if(!$params['user_name']){
            return apiResponse([], ApiStatus::CODE_10104, '实名认证姓名必填');
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(){
        try{
            ThirdPartyUserRepository::start();
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_70007, $e->getMessage());
        }
        return apiResponse([]);
    }

    /**
     * 定时任务
     *  结束时间
     *
     * 过租用结束时间 已支付,已发货,已签收,租用中 改为已完成
     * @return \Illuminate\Http\JsonResponse
     */
    public function end(){
        try{
            ThirdPartyUserRepository::end();
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_70008, $e->getMessage());
        }
        return apiResponse([]);
    }

    /**
     * 导入已下单用户execl表
     */
    public function importExcel(Request $request){
        $inputFileName = ImeiService::upload($request);
        $data = \App\OrderUser\Modules\Func\Excel::read($inputFileName);

        unset($data[1]);//第一行文档名
        unset($data[2]);//第二行标题

        if (count($data) == 0) {
            return ;
        }


        try {
            DB::beginTransaction();

            foreach ($data as $cel) {
                $result = [
                    'user_name'         => isset($cel['A']) ? $cel['A'] :'',//真实认证姓名
                    'phone'             => isset($cel['B']) ? $cel['B'] :'',//收货人手机号
                    'consignee'         => isset($cel['C']) ? $cel['C'] :'',//收货人姓名
                    'province'          => isset($cel['D']) ? $cel['D'] :'',//省
                    'city'              => isset($cel['E']) ? $cel['E'] :'',//市
                    'county'            => isset($cel['F']) ? $cel['F'] :'',//区
                    'shipping_address'  => isset($cel['G']) ? $cel['G'] :'',//详细地址
                    'status'            => isset($cel['H']) ? $cel['H'] :'',//订单状态
                    'platform'          => isset($cel['I']) ? $cel['I'] :'',//下单平台
                    'types'             => isset($cel['J']) ? $cel['J'] :'',//订单类型

                    'start_time'        => isset($cel['K']) ? strtotime($cel['K']) :'',//开始时间
                    'end_time'          => isset($cel['L']) ? strtotime($cel['L']) :'',//结束时间
                    'identity'          => isset($cel['M']) ? $cel['M'] :'',//身份证号
                    'order_no'          => isset($cel['N']) ? $cel['N'] :'',//订单号
                    'imei'              => isset($cel['O']) ? $cel['O'] :'',//IMEI
                    'order_time'        => isset($cel['P']) ? strtotime($cel['P']) :'',//下单时间
                    'pinpai'            => isset($cel['Q']) ? $cel['Q'] :'',//品牌
                    'order_model'       => isset($cel['R']) ? $cel['R'] :'',//机型
                    'yanse'             => isset($cel['S']) ? $cel['S'] :'',//颜色
                    'rongliang'         => isset($cel['T']) ? $cel['T'] :'',//容量G
                    'colour'            => isset($cel['U']) ? $cel['U'] :'',//成色
                    'total_amount'      => isset($cel['V']) ? $cel['V'] :'',//总金额
                    'deposit'           => isset($cel['W']) ? $cel['W'] :'',//押金
                    'zujin'             => isset($cel['X']) ? $cel['X'] :'',//租金
                    'total_zujin'       => isset($cel['Y']) ? $cel['Y'] :'',//租金总额
                    'suipingbao_chengben'=> isset($cel['Z']) ? $cel['Z'] :'',//碎屏保成本价
                    'suipingbao'        => isset($cel['AA']) ? $cel['AA'] :'',//碎屏保价格
                    'zuqi'              => isset($cel['AB']) ? $cel['AB'] :'',//租期
                    'remarks'           => isset($cel['AC']) ? $cel['AC'] :''//备注
                ];
                ThirdPartyUser::insert($result);
            }

            DB::commit();
        } catch (\Exception $e) {
            //Log::error('第三方平台下单用户数据导入出错');
            DB::rollBack();

            return apiResponse([], ApiStatus::CODE_70009, $e->getMessage());
        }

        return apiResponse();
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