<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Activity\Modules\Repository\Activity;

use App\Activity\Models\ActivityDestine as ActivityDestineModel;
use App\Activity\Modules\Inc\DestineStatus;

/**
 * 
 *
 * @author wuhaiyan
 */
class ActivityDestine{
	
	
	/**
	 *
	 * @var \App\Activity\Models\ActivityDestine
	 */
	private $model = [];
	
	/**
	 * 构造函数
	 * @param array $data 活动预定原始数据
	 */
	public function __construct(ActivityDestineModel $data) {
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
	 * 支付
	 * @return bool 
	 */
	public function pay():bool{
	    if($this->model->destine_status != DestineStatus::DestineCreated){
	        return false;
        }
		$this->model->destine_status = DestineStatus::DestinePayed;
		$this->model->update_time = time();
		return $this->model->save();
	}
    /**
     * 退款
     * @return bool
     */
    public function refund():bool{
        $this->model->destine_status = DestineStatus::DestineRefunded;
        $this->model->update_time = time();
        return $this->model->save();
    }
	/**
	 * 下单
	 * @return bool 
	 */
	public function OrderCreate():bool{
        if($this->model->destine_status != DestineStatus::DestinePayed){
            return false;
        }
        $this->model->destine_status = DestineStatus::DestineOrderCreated;
        $this->model->update_time = time();
        return $this->model->save();
	}

	/**
	 * 通过预定编号获取活动预定表
	 * <p>当不存在时，返回false</p>
	 * @param string $destine_no		预定编号
	 * @param int		$lock			锁
	 * @return \App\Activity\Modules\Repository\Activity\ActivityDestine
	 * @return  bool
	 */
	public static function getByNo( string $destine_no, int $lock=0 ) {
	    $builder = ActivityDestineModel::where([
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
}
