<?php
/**
 * User: wansq
 * Date: 2018/5/14
 * Time: 10:27
 */

namespace App\Warehouse\Models;

/**
 * Class Imei
 * @package App\Warehouse\Models
 */
class Imei extends Warehouse
{
    /**
     * @var bool
     *
     * 主键为字符串型，自增需要关闭
     */
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'zuji_imei';

    protected $primaryKey='imei';
    /**
     * imei状态
     */
    const STATUS_CNACEN = 0;//取消
    const STATUS_IN     = 1;//仓库中
    const STATUS_OUT    = 2;//出库

    /**
     *
     * 可填充字段
     */
    protected $fillable = ['imei', 'price', 'status'];


    /**
     * @param $imei
     * @return bool
     *
     * 库存中
     */
    public static function in($imei)
    {
        $model = self::where(['imei'=>$imei])->first();
        if (!$model) {
            return ;
        }
        $model->status = self::STATUS_IN;

        return $model->update();
    }

    /**
     * @param $imei
     * @return bool
     * 出库
     */
    public static function out($imei)
    {
        $model = self::where(['imei'=>$imei])->first();
        $model->status = self::STATUS_OUT;

        return $model->update();
    }
}