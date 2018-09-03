<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Activity\Modules\Repository\Activity;

use App\Activity\Models\ActivityAppointment as ActivityAppointmentModel;
use App\Activity\Modules\IncActivityAppointmentStatus;

/**
 * 
 *
 * @author qinliping
 */
class ActivityAppointment{
	
	
	/**
	 *
	 * @var \App\Activity\Models\ActivityAppointment
	 */
	private $model = [];
	
	/**
	 * 构造函数
	 * @param array $data 活动预定原始数据
	 */
	public function __construct(ActivityAppointmentModel $data) {
		$this->model = $data;
	}
	
	/**
	 * 读取原始数据
	 * @return array
	 */
	public function getData():array{
		return $this->model->toArray();
	}

    /***
     * 执行编辑活动
     * @param $data
     * [
     * 'id'                =>'',  活动id         int    【必传】
     * 'title'             =>'',  标题           string 【必传】
     * 'appointment_price' =>'',  活动价格       string 【必传】
     * 'appointment_image' =>'',  活动图片       string 【必传】
     * 'desc'              =>'',  活动描述       string 【必传】
     * 'begin_time'        =>'',  活动开始时间   int    【必传】
     * 'end_time'          =>''   活动结束时间   int    【必传】
     * 'appointment_status' =>'', 活动状态       int  【必传】
     * ]
     * @return bool
     */
    public  function activityUpdate(array $data){
        $this->model->title = $data['title'];
        $this->model->appointment_price = $data['appointment_price'];
        $this->model->appointment_image = $data['appointment_image'];
        $this->model->desc = $data['desc'];
        $this->model->begin_time = $data['begin_time'];
        $this->model->end_time = $data['end_time'];
        $this->model->appointment_status = $data['appointment_status'];
        $this->model->update_time = time();
        return $this->model->save();

    }
	/**
	 * 通过活动id获取活动信息
	 * <p>当不存在时，返回false</p>
	 * @param int   $id		活动id
	 * @param int		$lock	锁
	 * @return \App\Activity\Modules\Repository\Activity\ActivityAppointment
	 * @return  bool
	 */
	public static function getByIdInfo( int $id, int $lock=0 ) {
	    $builder = ActivityAppointmentModel::where([
            ['id', '=', $id],
        ])->limit(1);
		if( $lock ){
			$builder->lockForUpdate();
		}
		$activity_info = $builder->first();
		if( !$activity_info ){
			return false;
		}
		return new self( $activity_info );
	}
    /**
     * 获取活动信息
     * <p>当不存在时，返回false</p>
     * @return \App\Activity\Modules\Repository\Activity\ActivityAppointment
     * @return  bool
     */
    public static function getActivityInfo( ) {
        $activityInfo = ActivityAppointmentModel::query()->get();
        $list = [];
        foreach( $activityInfo as $it ) {
            $list[] = new self( $it );
        }
        return $list;
    }


}
