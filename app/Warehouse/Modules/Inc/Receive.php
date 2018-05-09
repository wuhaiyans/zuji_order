<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:17
 */

namespace App\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Monolog\Handler\IFTTTHandler;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Illuminate\Support\Facades\DB;

class Receive extends Model
{

    public $incrementing = false;
    protected $table = 'zuji_receive';
    protected $primaryKey = 'receive_no';
    public $timestamps = false;

    const STATUS_NONE = 0;//已取消
    const STATUS_INIT = 1;//待收货
    const STATUS_RECEIVED = 2;//已收货
    const STATUS_FINISH = 3;//检测完成
}