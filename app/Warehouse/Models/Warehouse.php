<?php

namespace App\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

//-- desc zuji_delivery_goods
//        select COLUMN_NAME,COLUMN_TYPE,COLUMN_COMMENT  from information_schema.columns
//where table_schema = 'zuji_warehouse' #表所在数据库
//    and table_name = 'zuji_delivery_goods' ; #你要查的表


//        $a = Schema::getConnection()->getSchemaBuilder()->getColumnListing('admin');
//
//        $a = DB::select('show columns from user');
//
//        dd($a);die;

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