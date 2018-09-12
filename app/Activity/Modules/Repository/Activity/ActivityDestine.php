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
     * 修改预定生成时间,金额支付方式等数据
     *  $destine = [
            'activity_id' => $data['activity_id'],    //【必须】 int   活动ID
            'user_id' => $data['user_id'],        //【必须】 int   用户ID
            'mobile' => $data['mobile'],         //【必须】 string 用户手机号
            'destine_amount' => $destineAmount,                     //【必须】 float  预定金额
            'pay_type' => $data['pay_type'],       //【必须】 int  支付类型
            'app_id' => $data['appid'],          //【必须】 int app_id
            'channel_id' => $channelId,                     //【必须】 int 渠道Id
            'activity_name' => $activityName,                     //【必须】 string 活动名称
    ];
     * @return bool
     */
    public function upDate($destine):bool{
        $this->model->activity_name = $destine['activity_name'];
        $this->model->user_id = $destine['user_id'];
        $this->model->mobile = $destine['mobile'];
        $this->model->destine_amount = $destine['destine_amount'];
        $this->model->pay_type = $destine['pay_type'];
        $this->model->app_id = $destine['app_id'];
        $this->model->channel_id = $destine['channel_id'];
        $this->model->activity_name = $destine['activity_name'];

        $this->model->create_time = time();
        $this->model->update_time = time();
        return $this->model->save();
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
		$this->model->pay_time = time();
		$this->model->update_time = time();
		return $this->model->save();
	}
    /**
     * 退款
     * @return bool
     */
    public function refund():bool{
        if($this->model->destine_status == DestineStatus::DestineRefunded){
            return false;
        }
        $this->model->destine_status = DestineStatus::DestineRefunded;
        $this->model->update_time = time();
        $this->model->account_time = time();
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
     * 15个自然日之后的更新订金退款状态
     * @param array $data
     * @return bool
     */
	public function updateActivityDestine(array $data):bool{
        $this->model->account_time = $data['account_time'];
        $this->model->account_number = $data['account_number'];
        $this->model->refund_remark = $data['refund_remark'];
        $this->model->destine_status = DestineStatus::DestineRefunded;
        return $this->model->save();
    }
    /**
     * 15个自然日内创建退款申请的订金状态
     * @param array $refund_remark
     *
     * @return bool
     */
    public function updateDestineRefund(string $refund_remark):bool{
       if($this->model->destine_status == DestineStatus::DestineRefund){
           return false;
       }
        $this->model->destine_status = DestineStatus::DestineRefund;
        $this->model->refund_remark  = $refund_remark;
        $this->model->update_time  = time();
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
    /**
     * 通过预定id获取活动预定表
     * <p>当不存在时，返回false</p>
     * @param string   $id		预定id
     * @param int		$lock			锁
     * @return \App\Activity\Modules\Repository\Activity\ActivityDestine
     * @return  bool
     */
    public static function getByIdNo( string $id, int $lock=0 ) {
        $builder = ActivityDestineModel::where([
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
}
