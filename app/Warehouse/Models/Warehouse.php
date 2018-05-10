<?php

namespace App\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    /**
     * 设置当前数据库
     */
    public function __construct() {
        $this->setConnection('warehouse');
    }

}