<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 20:36
 */

namespace App\Warehouse\Models;


class DeliveryGoods extends Warehouse
{

    protected $table = 'zuji_delivery_goods';

    public $timestamps = false;

    protected $fillable = ['delivery_no', 'order_no', 'sku_no','quantity',
        'quantity_delivered', 'status', 'status_time'];

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