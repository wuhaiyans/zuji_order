<?php

/**
 *
 *  分期逾期扣款记录列表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderOverdueRecord extends Model
{

    protected $table = 'order_overdue_record';

    protected $primaryKey='id';

    const CREATED_AT = 'create_time';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['overdue_id','deduction_amount','overdue_amount','remark','status','create_time'];

    
}