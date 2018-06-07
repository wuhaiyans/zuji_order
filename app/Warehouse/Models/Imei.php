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
    protected $fillable = [
        'imei', 'price', 'status', 'brand', 'name', 'price', 'apple_serial',
        'quality', 'color', 'business', 'storage', 'create_time', 'update_time'
    ];


    /**
     * @param null $status 不传值或传null时，返回状态列表，否则返回对应状态值
     * @return array|mixed|string
     *
     * 获取状态列表，或状态值
     */
    public static function sta($status=null)
    {
        $st = [
            self::STATUS_IN   => '仓库中',
            self::STATUS_OUT  => '出库',
        ];

        if ($status === null) return $st;

        return isset($st[$status]) ? $st[$status] : '';
    }

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