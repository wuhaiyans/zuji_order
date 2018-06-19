<?php

/**
 *
 *  支付阶段--签约代扣环节明细表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayFundauthModel extends Model
{

    protected $table = 'order_pay_fundauth';

    protected $primaryKey='fundauth_no';

    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = ['fundauth_no','out_fundauth_no','fundauth_status','user_id','freeze_time','unfreeze_time','total_amount','unfreeze_amount','pay_amount'];




}