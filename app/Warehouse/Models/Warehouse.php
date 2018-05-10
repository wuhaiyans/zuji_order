<?php

namespace App\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    //设置数据库
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection('warehouse');
    }
}