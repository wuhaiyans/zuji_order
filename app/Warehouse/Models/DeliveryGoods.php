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

    //状态：0：未配货；1：部分配货完成；2：全部配货完成
    const STATUS_INIT = 0; //未配
    const STATUS_PART = 1; //配货部分
    const STATUS_ALL = 2; //配货完成


    protected $fillable = [
        'delivery_no',
        'serial_no',
        'goods_no',
        'goods_name',
        'order_no',
        'quantity',
        'goods_name',
        'quantity_delivered',
        'status',
        'status_time'
    ];

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