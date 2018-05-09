<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 20:36
 */

namespace App\Warehouse\Modules\Inc;


use Illuminate\Database\Eloquent\Model;

class DeliveryGoods extends Model
{

    protected $table = 'zuji_delivery_goods';

    public $timestamps = false;

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }


    /**
     * @param $data
     * @return DeliveryGoods
     * 存储
     */
    public static function store($data)
    {
        $model = new self();

        $model->create($data);

        return $model;
    }

}