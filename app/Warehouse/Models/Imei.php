<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 20:36
 */

namespace App\Warehouse\Models;


use Illuminate\Database\Eloquent\Model;

class Imei extends Model
{
    protected $table = 'zuji_imei';

    public $timestamps = false;

    /**
     * 导入imei数据
     */
    public static function import()
    {

    }


}