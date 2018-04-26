<?php

namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // Rest omitted for brevity

    protected $table = 'order_info';

    protected $primaryKey='id';

}