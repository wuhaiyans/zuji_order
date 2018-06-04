<?php

/**
 * 代扣 与 业务 关系表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayWithholdBusinessModel extends Model
{

    protected $table = 'order_pay_withhold_business';

	protected $primaryKey = 'id';

    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;

}