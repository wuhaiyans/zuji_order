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


	/**
	 * 通过活动id获取活动信息
	 * <p>当不存在时，返回false</p>
	 * @param string   $id		活动id
	 * @param int		$lock	锁
	 * @return \App\Activity\Modules\Repository\Activity\ActivityAppointment
	 * @return  bool
	 */
	public static function getByIdInfo( string $id, int $lock=0 ) {
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

}
