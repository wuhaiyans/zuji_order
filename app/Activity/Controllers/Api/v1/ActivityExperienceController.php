<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author qinliping <qinliping@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;
use App\Activity\Modules\Inc\DestineStatus;
use App\Activity\Modules\Repository\Activity\ExperienceDestine;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\OrderReturnRepository;
use Illuminate\Http\Request;
use App\Lib\ApiStatus;
use App\Activity\Modules\Service\ActivityExperience;

class ActivityExperienceController extends Controller
{

    protected $ActivityExperience;
    public function __construct(ActivityExperience $ActivityExperience)
    {
        $this->ActivityExperience = $ActivityExperience;
    }
    /***
     * 1元体验列表
     * @return array
     */
    public function experienceList(){
        $experienceList = ActivityExperience::experienceList();

        if( !$experienceList ){
            return apiResponse([],ApiStatus::CODE_95005);
        }
        if(time()<$experienceList[1][0]['begin_time']){
            return apiResponse($experienceList,ApiStatus::CODE_95006);//未开始
        }
        if(time()>$experienceList[1][0]['end_time']){
            return apiResponse($experienceList,ApiStatus::CODE_95007); //已结束
        }
        return apiResponse($experienceList,ApiStatus::CODE_0);
    }
    /**
     * 预定退款----15个自然日内
     * @param Request $request
     * [
     *     'id'           =>  '' ,//预订id   int     【必传】
     *     'refund_remark' => '', //退款备注  string   【必传】
     * ]
     * @return string
     */
   public function experienceRefund(Request $request){
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
       $res= $this->ActivityExperience->experienceRefund($params['params']);
       if(!$res){
           return apiResponse([],ApiStatus::CODE_95004);//创建清单失败
       }
       return apiResponse([],ApiStatus::CODE_0);


   }
    /**
     * 预定退款----15个自然日后
     * @param Request $request
     * [
     *    'id'            => ''   //预定id  int  【必传】
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
        $res=$this->ActivityExperience->refund($params['params']);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_95003);//预定金退款失败
        }
        return apiResponse([],ApiStatus::CODE_0);


    }


}