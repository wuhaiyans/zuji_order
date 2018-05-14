<?php

namespace App\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Warehouse extends Model
{
    //设置数据库
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection('warehouse');
    }


    protected function finishSave(array $options)
    {
        parent::finishSave($options);

        DB::listen(function ($sql) {
            foreach ($sql->bindings as $i => $binding) {
                if ($binding instanceof \DateTime) {
                    $sql->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                } else {
                    if (is_string($binding)) {
                        $sql->bindings[$i] = "'$binding'";
                    }
                }
            }
            $query = str_replace(array('%', '?'), array('%%', '%s'), $sql->sql);
            $query = vsprintf($query, $sql->bindings);
            Log::info($query);
        });
    }


}