<?php
namespace App\Tools\Modules\Repository;

interface ToolInterface
{
   public function getDetailByWhere(array $where);

   public function getToolInfo() : ToolInfo;
   
   public function getToolLimit() : ToolLimit;
   
}