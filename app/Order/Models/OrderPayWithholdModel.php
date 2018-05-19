<?php

/**
 *
 *  支付阶段--签约代扣环节明细表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayWithholdModel extends Model
{

    protected $table = 'order_pay_withhold';

    protected $primaryKey='withhold_no';

    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;

    // 可以被批量赋值的属性。
    protected $fillable = ['withhold_no','out_withhold_no','withhold_status','uid','sign_time','unsign_time','counter'];
}