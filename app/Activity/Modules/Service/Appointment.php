<?php
/**
 * 预约活动
 * @access public (访问修饰符)
 * @author qinliping <qinliping@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Activity\Modules\Service;

use App\Activity\Modules\Repository\Activity\ActivityAppointment;
use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderStatus;
use App\Activity\Modules\Repository\ActivityAppointmentRepository;
use App\Activity\Modules\Repository\ActivityGoodsAppointmentRepository;
use Illuminate\Support\Facades\DB;

class Appointment
{
    /**
     * 添加预约活动
     * @param $params
     * [
     * 'title'             =>'',  标题           string    【必传】
     * 'appointment_price' =>'',  预定金额       string 【必传】
     * 'appointment_image' =>'',  活动图片       string 【必传】
     * 'desc'              =>'',  活动描述       string 【必传】
     * 'begin_time'        =>'',  活动开始时间   int    【必传】
     * 'end_time'          =>''   活动结束时间   int    【必传】
     * 'appointment_status' =>'', 活动状态       int    【必传】
     * 'spu_id'             =>['',''] 商品id     int    【必传】
     * ]
     * return bool
     */
    public function appointmentAdd(array $params)
    {
        DB::beginTransaction();
        try {
            $data['title'] = $params['title'];               //活动标题
            $data['appointment_price'] = $params['appointment_price']; //预定金额
            $data['appointment_image'] = $params['appointment_image']; //活动图片
            $data['desc'] = $params['desc'];                //活动描述
            $data['begin_time'] = $params['begin_time'];         //活动开始时间
            $data['end_time'] = $params['end_time'];            //活动结束时间
            $data['appointment_status'] = $params['appointment_status'];//活动状态
            $data['create_time'] = time();                          //创建时间
            $appointment_id = ActivityAppointmentRepository::add($data);//添加预约活动信息，获取添加活动的id
            if (!$appointment_id) {
                DB::rollBack();
                return false;
            }
            //循环添加活动和商品的关联关系
            foreach ($params['spu_id'] as $spu_id) {
                $goodsParams['appointment_id'] = $appointment_id;  //活动id
                $goodsParams['spu_id'] = $spu_id;                   //商品id
                $goodsParams['create_time'] = time();              //创建时间
                $res = ActivityGoodsAppointmentRepository::add($goodsParams);//执行添加
            }
            if(!$res){
                DB::rollBack();
                return false;
            }
            DB::commit();
            return true;
        }catch( \Exception $exc){
            DB::rollBack();
            LogApi::debug("程序异常",$exc);
            return false;
        }

    }


    /***
     * 执行修改活动
     * @param $data
     * [
     * 'id'                =>'',  活动id         int    【必传】
     * 'title'             =>'',  标题           string    【必传】
     * 'appointment_price' =>'',  预定金额       string 【必传】
     * 'appointment_image' =>'',  活动图片       string 【必传】
     * 'desc'              =>'',  活动描述       string 【必传】
     * 'begin_time'        =>'',  活动开始时间   int    【必传】
     * 'end_time'          =>''   活动结束时间   int    【必传】
     * 'appointment_status' =>'', 活动状态       int    【必传】
     * 'spu_id'             =>['',''] 商品id     int     【必传】
     * ]
     * @return bool
     */
    public function appointmentUpdate(array $params){
        DB::beginTransaction();
        try{
            //获取修改活动信息
            $activityInfo=ActivityAppointment::getByIdInfo($params['id']);
            if(!$activityInfo){
                return false;
            }
            //修改活动信息
            $activityUpdate= $activityInfo->activityUpdate($params);
            if(!$activityUpdate){
                LogApi::info("[appointmentUpdate]修改活动失败".$activityUpdate);
                DB::rollBack();
                return false;
            }
            //获取活动和商品的关联关系数据
            $activityGoodsInfo=ActivityGoodsAppointmentRepository::getByIdInfo($params['id']);
            if(!$activityGoodsInfo){
                return false;
            }
            foreach($activityGoodsInfo as $val){
                $activityGoods[]=$val['spu_id'];
            }
            //如果没有修改活动和商品的关联数据，则不做任何修改
             $b = array_diff($activityGoods,$params['spu_id']);
             if($b){

                 //删除活动和商品的关联数据，重新添加活动和商品的关联关系
                 $delActivityGoods=ActivityGoodsAppointmentRepository::delActivityGoods($params['id']);
                 if(!$delActivityGoods){
                     LogApi::info("[appointmentUpdate]删除活动和商品的关联数据失败".$delActivityGoods);
                     DB::rollBack();
                     return false;
                 }
                 //循环添加活动和商品的关联关系
                 foreach($params['spu_id'] as $spu_id){
                     $goodsParams['appointment_id'] = $params['id'];
                     $goodsParams['spu_id'] = $spu_id;
                     $goodsParams['create_time'] = time();
                     $addActivityGoods = ActivityGoodsAppointmentRepository::add($goodsParams);//执行添加
                 }
                 if(!$addActivityGoods){
                     LogApi::info("[appointmentUpdate]循环添加活动和商品的关联关系失败".$addActivityGoods);
                     DB::rollBack();
                     return false;
                 }
             }

             DB::commit();
             return true;
        }catch( \Exception $exc){
            DB::rollBack();
            LogApi::debug("程序异常",$exc);
            return false;
        }


    }

    /***
     * 获取活动信息
     * [
     *   'page'  =>'', //页数   int   【可选】
     *   'size'  =>'', //条数   int   【可选】
     * ]
     * @return array
     */
    public function appointmentList($params){
        $page = empty($params['page']) ? 1 : $params['page'];  //页数
        $size = !empty($params['size']) ? $params['size'] : config('web.pre_page_size'); //条数
        $activityInfo=ActivityAppointmentRepository::getActivityInfo($page,$size);
        if(!$activityInfo){
            return false;
        }

        return $activityInfo;

    }
}