<?php
namespace App\Tools\Modules\Checker;
use App\Lib\ApiStatus;
use App\Tools\Modules\Inc\CouponStatus;
class Checker
{
    private $receive_start_time = null;
    private $receive_end_time = null;
    private $use_start_time = null;
    private $use_end_time = null;
    private $model_status = null;
    private $use_status = null;
    private $current_time = 0;
    
    private $code = ApiStatus::CODE_0;
    private $msg = '';
    
    public function __construct()
    {
        
    }
    
    public function setReceiveStartTime(int $time)
    {
        return $this->receive_start_time = $time;
    }
    
    public function setReceiveEndTime(int $time)
    {
        return $this->receive_end_time = $time;
    }
    
    public function setUseStartTime(int $time)
    {
        return $this->use_start_time = $time;
    }
    
    public function setUseEndTime(int $time)
    {
        return $this->use_end_time = $time;
    }
    
    public function setModelStatus(int $status)
    {
        return $this->model_status = $status;
    }
    
    public function setUseStatus(int $status)
    {
        return $this->use_status = $status;
    }
    
    public function setCurrentTime(int $time)
    {
        return $this->current_time = $time;
    }
    
    public function getCode()
    {
        return $this->code;
    }
    
    public function getMsg()
    {
        return $this->msg;
    }
    
    //可使用检测
    public function checkingUse()
    {
        $this->code = ApiStatus::CODE_0;
        if($this->use_status != CouponStatus::CouponStatusNotUsed){
            $this->code = ApiStatus::CODE_50000;
            $this->msg  = '工具状态错误';
        }
        if($this->current_time < $this->use_start_time){
            $this->code = ApiStatus::CODE_50000;
            $this->msg  = '未到开始时间';
        }
        if($this->current_time > $this->use_end_time){
            $this->code = ApiStatus::CODE_50000;
            $this->msg  = '已过结束时间';
        }
        if($this->model_status != CouponStatus::CouponTypeStatusIssue || $this->model_status != CouponStatus::CouponTypeStatusTest){
            $this->code = ApiStatus::CODE_50000;
            $this->msg  = '模型状态错误';
        }
    }
    
    //可领取检测
    public function checkingGet()
    {
        $this->code = ApiStatus::CODE_0;
        if($this->current_time < $this->receive_start_time){
            $this->code = ApiStatus::CODE_50000;
            $this->msg  = '未到开始时间';
        }
        if($this->current_time > $this->receive_end_time){
            $this->code = ApiStatus::CODE_50000;
            $this->msg  = '已过结束时间';
        }
        if($this->model_status != CouponStatus::CouponTypeStatusIssue || $this->model_status != CouponStatus::CouponTypeStatusTest){
            $this->code = ApiStatus::CODE_50000;
            $this->msg  = '模型状态错误';
        }
    }
    
    //可取消检测
    public function checkingCancel()
    {
        if($this->use_status == CouponStatus::CouponStatusAlreadyUsed){
            $this->code = ApiStatus::CODE_0;
        }else{
            $this->code = ApiStatus::CODE_50000;
            $this->msg = '优惠券使用状态错误';
        }
    }
    
}