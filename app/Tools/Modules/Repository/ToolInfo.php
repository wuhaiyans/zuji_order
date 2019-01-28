<?php
namespace App\Tools\Modules\Repository;

class ToolInfo
{
    private $id = null;
    private $name = null;
    private $desc = null;
    private $start_time = null;
    private $end_time = null;
    private $status = null;
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        return $this->id = $id;
    }
    
    public function getName() : string
    {
        return $this->name;
    }
    
    public function setName(string $name)
    {
        return $this->name = $name;
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