<?php
namespace App\Tools\Modules\Repository;

class ToolLimit
{
    private $start_time = null;
    private $end_time = null;
    private $status = null;
    private $mobile = null;
    
    public function getStartTime()
    {
        return $this->start_time;
    }
    
    public function setStartTime(int $start_time)
    {
        return $this->start_time = $start_time;
    }
    
    public function getEndTime()
    {
        return $this->end_time;
    }
    
    public function setEndTime(int $end_time)
    {
        return $this->end_time = $end_time;
    }
    
    public function getStatus() : int
    {
        return $this->status;
    }
    
    public function setStatus(int $status)
    {
        return $this->status = $status;
    }
   
    
}