<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Activity\Modules\Repository\Activity;

use App\Activity\Models\ActivityExperience as ActivityExperienceModel;

/**
 * 
 *
 * @author qinliping
 */
class ActivityExperience{
	
	
	/**
	 *
	 * @var \App\Activity\Models\ActivityExperience
	 */
	private $model = [];
	
	/**
	 * 构造函数
	 * @param array $data 活动预定原始数据
	 */
	public function __construct(ActivityExperienceModel $data) {
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
     * 通过体验id获取活动体验表信息
     * <p>当不存在时，返回false</p>
     * @param string   $id		体验id
     * @param int		$lock			锁
     * @return \App\Activity\Modules\Repository\Activity\Activityexperience
     * @return  bool
     */
    public static function getByIdNo( string $id, int $lock=0 ) {
        $builder = ActivityExperienceModel::where([
            ['id', '=', $id],
        ])->limit(1);
        if( $lock ){
            $builder->lockForUpdate();
        }
        $destine_info = $builder->first();
        if( !$destine_info ){
            return false;
        }
        return new self( $destine_info );
    }
    /**
     * 通过活动id获取活动体验表信息
     * <p>当不存在时，返回false</p>
     * @param string   $id		体验id
     * @param int		$lock			锁
     * @return \App\Activity\Modules\Repository\Activity\Activityexperience
     * @return  bool
     */
    public static function getByActivityId( string $id, int $lock=0 ) {
        $builder = ActivityExperienceModel::where([
            ['activity_id', '=', $id],
        ])->limit(1);
        if( $lock ){
            $builder->lockForUpdate();
        }
        $destine_info = $builder->first();
        if( !$destine_info ){
            return false;
        }
        return new self( $destine_info );
    }




}
