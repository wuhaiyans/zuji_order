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


}