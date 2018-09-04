<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Activity\Modules\Repository\Activity;

use App\Activity\Models\ActivityGoodsAppointment as ActivityGoodsAppointmentModel;

/**
 * 
 *
 * @author qinliping
 */
class ActivityGoodsAppointment{
	
	
	/**
	 *
	 * @var \App\Activity\Models\ActivityGoodsAppointment
	 */
	private $model = [];
	
	/**
	 * 构造函数
	 * @param array $data 活动预定原始数据
	 */
	public function __construct(ActivityGoodsAppointmentModel $data) {
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
     * 禁用活动与商品的关联数据
     * @param $id  活动id         int    【必传】
     * @return bool
     */
    public  function closeActivity(int $id){
        if(empty($id)){
           return false;
        }
        $this->model->goods_status = 1;
        $this->model->update_time = time();
        return $this->model->save();

    }



}
