<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author qinliping <qinliping@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;
use Illuminate\Http\Request;
use App\Lib\ApiStatus;
use App\Activity\Modules\Service\Appointment;

class AppointmentController extends Controller
{
    protected $Appointment;
    public function __construct(Appointment $Appointment)
    {
        $this->Appointment = $Appointment;
    }
    /**
     * 添加预约活动
     * @param Request $request
     * [
     * 'title'              =>'',  标题           string    【必传】
     * 'appointment_price'  =>'',  预定金额       string    【必传】
     * 'appointment_image'  =>'',  活动图片       string    【必传】
     * 'desc'               =>'',  活动描述       string    【必传】
     * 'begin_time'         =>'',  活动开始时间   int       【必传】
     * 'end_time'           =>''   活动结束时间   int       【必传】
     * 'appointment_status' =>'', 活动状态       string     【必传】
     * 'spu_id'             =>['',''] 商品id      int       【必传】
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
   public function appointmentAdd(Request $request){
       //-+--------------------------------------------------------------------
       // | 获取参数并验证
       //-+--------------------------------------------------------------------
       $params = $request->input();
       $paramsArr = isset($params['params'])? $params['params'] :[];
       $rules = [
           'title'                   => 'required',//活动标题
           'appointment_price'     => 'required',//预定金额
           'appointment_image'     => 'required',//活动图片
           'desc'                    => 'required',//活动介绍
           'begin_time'             => 'required',//预约开始时间
           'end_time'                => 'required',//预约结束时间
           'appointment_status'    => 'required',//活动状态   开启 0,禁用1
       ];
       $validator = app('validator')->make($paramsArr, $rules);
       if ($validator->fails()){
           return apiResponse([],ApiStatus::CODE_20001);
       }
       //商品数组不能为空
       if(empty($params['params']['spu_id'])){
           return apiResponse([],ApiStatus::CODE_20001);
       }
       //开始时间必须小于结束时间
       if($params['params']['begin_time']>$params['params']['begin_time']){
           return apiResponse([],ApiStatus::CODE_20001);
       }
       if($params['params']['appointment_price'] <= 0){
           return apiResponse([],ApiStatus::CODE_20001);
       }
       $res=$this->Appointment->appointmentAdd($params['params']);
       if(!$res){
           return apiResponse([],ApiStatus::CODE_95000);//添加失败
       }
       return apiResponse([],ApiStatus::CODE_0);


   }
    /***
     * 执行修改预约活动
     * @param Request $request
     * [
     * 'id'                =>'',  活动id         int    【必传】
     * 'appointment_price' =>'',  预定金额       string 【必传】
     * 'title'             =>'',  标题           string    【必传】
     * 'appointment_image' =>'',  活动图片       string 【必传】
     * 'desc'              =>'',  活动描述       string 【必传】
     * 'begin_time'        =>'',  活动开始时间   int    【必传】
     * 'end_time'          =>''   活动结束时间   int    【必传】
     * 'appointment_status' =>'', 活动状态      string  【必传】
     * 'spu_id'             =>['',''] 商品id     int    【必传】
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function appointmentUpdate(Request $request){
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $paramsArr = isset($params['params'])? $params['params'] :[];
        $rules = [
            'id'                      => 'required',//活动id
            'title'                   => 'required',//活动标题
            'appointment_price'     => 'required',//预定金额
            'appointment_image'     => 'required',//活动图片
            'desc'                    => 'required',//活动介绍
            'begin_time'             => 'required',//预约开始时间
            'end_time'                => 'required',//预约结束时间
            'appointment_status'    => 'required',//活动状态   开启 0,禁用1
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        //商品数组不能为空
        if(empty($params['params']['spu_id'])){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        //开始时间必须小于结束时间
        if($params['params']['begin_time']>$params['params']['begin_time']){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        if($params['params']['appointment_price'] <= 0){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->Appointment->appointmentUpdate($params['params']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_95001);//修改失败
        }
        return apiResponse([],ApiStatus::CODE_0);

    }
    /***
     * 预约活动列表
     * @params
     * [
     * 'page' => ''   int  页数 【可选】
     * 'size' => ''   int  条数 【可选】
     *
     * ]
     *@return array
     * [
     * 'id'                =>'',  活动id         int
     * 'appointment_price' =>'',  预定金额       string
     * 'title'             =>'',  标题           string
     * 'appointment_image' =>'',  活动图片       string
     * 'desc'              =>'',  活动描述       string
     * 'begin_time'        =>'',  活动开始时间   int
     * 'end_time'          =>''   活动结束时间   int
     * 'appointment_status' =>'', 活动状态      string
     * ]
     *
     */
    public function appointmentList(Request $request){
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $res=$this->Appointment->appointmentList($params['params']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_95002);//获取数据失败
        }
        return apiResponse($res,ApiStatus::CODE_0);

    }

    /**
     * 预定退款----15个自然日内
     * @param Request $request
     * [
     *    'id'            =>  '' ,//预订id   int     【必传】
     *     'refund_remark' => '', //退款备注  string   【必传】
     * ]
     * @return string
     */
    public function appointmentRefund(Request $request){
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $paramsArr = isset($params['params'])? $params['params'] :[];
        $rules = [
            'id'                => 'required',//预定id
            'refund_remark'   => 'required',//退款原因
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->Appointment->appointmentRefund($params['params']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_95004);//创建清单失败
        }
        return apiResponse([],ApiStatus::CODE_0);


    }
    /**
     * 预定退款----15个自然日后
     * @param Request $request
     * [
     *    'id'            => ''   //预定id   int  【必传】
     *    'account_time'  =>''    //转账时间 int  【必传】
     *    'account_number'=>''   //支付宝账号string【必传】
     *    'refund_remark' =>''   //退款备注  string 【必传】
     * ]
     * @return string
     */
    public function refund(Request $request){
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $paramsArr = isset($params['params'])? $params['params'] :[];
        $rules = [
            'id'                 => 'required',//预定id
            'account_time'     => 'required',//转账时间
            'account_number'   => 'required',//支付宝账号
            'refund_remark'    => 'required',//账号备注

        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->Appointment->refund($params['params']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_95003);//预定金退款失败
        }
        return apiResponse([],ApiStatus::CODE_0);


    }
    public function test(Request $request){
        //-+--------------------------------------------------------------------
        // | 获取参数并验证
        //-+--------------------------------------------------------------------
        $params = $request->input();
        $paramsArr = isset($params['params'])? $params['params'] :[];
        $rules = [
            'business_no'      => 'required',//预定编号
            'business_type'   => 'required',//业务类型
            'status'            => 'required',//退款状态

        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()){
            return apiResponse([],ApiStatus::CODE_20001);
        }
        $res=$this->Appointment->callbackAppointment($params['params'],$params['userinfo']);
        print_r($res);
    }


    /***
     * 预约活动列表筛选接口
     * @return \Illuminate\Http\JsonResponse
     */
    public function oppointmentListFilter(){

        $res = \App\Activity\Modules\Inc\OppointmentListFiler::OppointmentInc();
        return apiResponse($res,ApiStatus::CODE_0,"success");
    }


}