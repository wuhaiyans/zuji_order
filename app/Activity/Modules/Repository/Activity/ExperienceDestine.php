<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Activity\Modules\Repository\Activity;

use App\Activity\Models\ActivityDestine as ActivityDestineModel;
use App\Activity\Models\ActivityExperienceDestine;
use App\Activity\Modules\Inc\DestineStatus;

/**
 * 
 *
 * @author wuhaiyan
 */
class ExperienceDestine{
	
	
	/**
	 *
	 * @var \App\Activity\Models\ActivityDestine
	 */
	private $model = [];
	
	/**
	 * 构造函数
	 * @param array $data 活动预定原始数据
	 */
	public function __construct(ActivityExperienceDestine $data) {
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
     * 重新选择 更新所有信息
     *  $params = [
     *      'destine_no'    =>'',       //【必须】 string 活动编号
     *      'mobile'        =>'',       //【必须】 string 用户手机号
     *      'experience_id' =>'',       //【必须】 int    体验活动ID
     *      'zuqi'          =>'',       //【必须】 int    租期
     *      'destine_amount'=>'',       //【必须】 string 支付金额
     *      'pay_channel'   =>'',       //【必须】 int    支付渠道
     *      'app_id'        =>'',       //【必须】 int    appid
     *      'pay_type'      =>'',       //【必须】 int    支付方式
     *      'channel_id'    =>'',       //【必须】 int    渠道ID
     *   ];
     * @return bool
     */
    public function upDate($params):bool{
        $this->model->destine_no = $params['destine_no'];
        $this->model->mobile = $params['mobile'];
        $this->model->experience_id = $params['experience_id'];
        $this->model->zuqi = $params['zuqi'];
        $this->model->destine_amount = $params['destine_amount'];
        $this->model->pay_channel = $params['pay_channel'];
        $this->model->app_id = $params['app_id'];
        $this->model->pay_type = $params['pay_type'];
        $this->model->channel_id = $params['channel_id'];


        $this->model->create_time = time();
        $this->model->update_time = time();
        return $this->model->save();
    }

	/**
	 * 支付
	 * @return bool 
	 */
	public function pay():bool{
	    if($this->model->destine_status != DestineStatus::ExperienceDestineCreated){
	        return false;
        }
		$this->model->destine_status = DestineStatus::ExperienceDestinePayed;
		$this->model->pay_time = time();
		$this->model->update_time = time();
		return $this->model->save();
	}

    /**
     * 增加租期
     * @return bool
     */
    public function upZuqi():bool{
        if($this->model->destine_status != DestineStatus::ExperienceDestinePayed){
            return false;
        }
        $this->model->zuqi = $this->model->zuqi+1;
        $this->model->update_time = time();
        return $this->model->save();
    }


	/**
	 * 通过体验活动编号获取活动预定表
	 * <p>当不存在时，返回false</p>
	 * @param string $destine_no		预定编号
	 * @param int		$lock			锁
	 * @return \App\Activity\Modules\Repository\Activity\ExperienceDestine
	 * @return  bool
	 */
	public static function getByNo( string $destine_no, int $lock=0 ) {
	    $builder = ActivityExperienceDestine::where([
            ['destine_no', '=', $destine_no],
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
     * 通过体验活动ID获取活动预定表
     * <p>当不存在时，返回false</p>
     * @param string $id		预定id
     * @param int		$lock			锁
     * @return \App\Activity\Modules\Repository\Activity\ExperienceDestine
     * @return  bool
     */
    public static function getById( int $id, int $lock=0 ) {
        $builder = ActivityExperienceDestine::where([
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
     * 通过体验活动ID 与用户获取活动预定表
     * <p>当不存在时，返回false</p>
     * @param string $destine_no		预定编号
     * @param int		$lock			锁
     * @return \App\Activity\Modules\Repository\Activity\ExperienceDestine
     * @return  bool
     */
    public static function getByUser( int $userId,int $experienceId, int $lock=0 ) {
        $whereArray = array();
        $whereArray[] = ['user_id', '=', $userId];
        $whereArray[] = ['experience_id', '=', $experienceId];
        $builder = ActivityExperienceDestine::where($whereArray)->limit(1);
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
