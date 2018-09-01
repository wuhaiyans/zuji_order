<?php
/**
 * 预约活动
 * @access public (访问修饰符)
 * @author qinliping <qinliping@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Activity\Modules\Service;

use App\Common\LogApi;
use App\Order\Modules\Inc\OrderStatus;
use App\Activity\Modules\Repository\ActivityAppointmentRepository;
use App\Activity\Modules\Repository\ActivityGoodsAppointmentRepository;

class Appointment
{
    /**
     * 添加预约活动
     * @param $params
     * [
     * 'title'             =>'',  标题           int    【必传】
     * 'appointment_image' =>'',  活动图片       string 【必传】
     * 'desc'              =>'',  活动描述       string 【必传】
     * 'begin_time'        =>'',  活动开始时间   int    【必传】
     * 'end_time'          =>''   活动结束时间   int 【必传】
     * 'appointment_status' =>'', 活动状态      string 【必传】
     * 'spu_id'            =>['',''] 商品id     int      【必传】
     * ]
     * return bool
     */
    public function appointmentAdd(array $params)
    {
        $data['title']               = $params['title'];               //活动标题
        $data['appointment_image'] = $params['appointment_image']; //活动图片
        $data['desc']                = $params['desc'];                //活动描述
        $data['begin_time']         = $params['begin_time'];         //活动开始时间
        $data['end_time']           = $params['end_time'];            //活动结束时间
        $data['appointment_status']=$params['appointment_status'];//活动状态
        $data['create_time']        = time();                          //创建时间
        $appointment_id = ActivityAppointmentRepository::add($data);//添加预约活动信息，获取添加活动的id
        if(!$appointment_id){
            return false;
        }
        //循环添加活动和商品的关联关系
        foreach($params['spu_id'] as $spu_id){
            $goodsParams['appointment_id'] = $appointment_id;
            $goodsParams['spu_id'] = $spu_id;
            $goodsParams['create_time'] = time();
            $res = ActivityGoodsAppointmentRepository::add($goodsParams);//执行添加
        }
        return $res;
    }


    /***
     * 执行修改活动
     * @param $data
     * [
     * 'id'                =>'',  活动id         int    【必传】
     * 'title'             =>'',  标题           int    【必传】
     * 'appointment_image' =>'',  活动图片       string 【必传】
     * 'desc'              =>'',  活动描述       string 【必传】
     * 'begin_time'        =>'',  活动开始时间   int    【必传】
     * 'end_time'          =>''   活动结束时间   int    【必传】
     * 'appointment_status' =>'', 活动状态      string  【必传】
     * 'spu_id'             =>['',''] 商品id     int     【必传】
     * ]
     * @return bool
     */
    public function appointmentUpdate(array $params){
        $data['title']               = $params['title'];               //活动标题
        $data['appointment_image'] = $params['appointment_image']; //活动图片
        $data['desc']                = $params['desc'];                //活动描述
        $data['begin_time']         = $params['begin_time'];         //活动开始时间
        $data['end_time']           = $params['end_time'];            //活动结束时间
        $data['appointment_status']=$params['appointment_status'];//活动状态
        $data['update_time']        = time();                          //修改时间
        //修改条件
        $where[]=['id','=',$params['id']];
        $appointment_update = ActivityAppointmentRepository::activityUpdate($data,$where);     //执行修改预约活动
        if(!$appointment_update){
            return false;
        }
        //获取活动和商品的关联关系数据
        $activityInfo=ActivityGoodsAppointmentRepository::getByIdInfo($where);
        if(!$activityInfo){
            return false;
        }
        //如果没有修改活动和商品的关联数据，则不做任何修改
        //如果修改活动和商品的关联关系，则删除活动和商品的关联数据，重新添加活动和商品的关联关系
        //循环添加活动和商品的关联关系
        foreach($params['spu_id'] as $spu_id){
            $goodsParams['appointment_id'] = $params['id'];
            $goodsParams['spu_id'] = $spu_id;
            $goodsParams['create_time'] = time();
            $res = ActivityGoodsAppointmentRepository::add($goodsParams);//执行添加
        }
        return $res;

    }

    /***
     * 获取活动信息
     * @return array
     */
    public function appointmentList(){

    }
}