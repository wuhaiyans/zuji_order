<?php
/**
 * User: jinlin
 * Date: 2018/7/18
 * Time: 16:20
 */

namespace App\Warehouse\Models;

/**
 * Class Imei
 * @package App\Warehouse\Models
 */
class ImeiLog extends Warehouse
{
    /**
     * @var bool
     *
     * 主键为字符串型，自增需要关闭
     */
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'zuji_imei_log';

    protected $primaryKey='imei';
    /**
     * imei状态
     */
    const STATUS_CNACEN = 0;//取消
    const STATUS_IN     = 1;//入库
    const STATUS_OUT    = 2;//出库

    /**
     *
     * 可填充字段
     */
    protected $fillable = [
        'imei', 'type', 'create_time'
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
            self::STATUS_CNACEN => '取消无效',
            self::STATUS_IN     => '入库',
            self::STATUS_OUT    => '出库',
        ];

        if ($status === null) return $st;

        return isset($st[$status]) ? $st[$status] : '';
    }

    /**
     * @param $imei
     * @return bool
     *
     * 入库
     */
    public static function in($imei)
    {
        $data = [
            'imei'=>$imei,
            'type'=>self::STATUS_IN,
            'create_time'=>time(),
        ];
        $model = self::create($data);
        if (!$model) {
            return false;
        }
        if (!$model) {
            return false;
        }

        return true;
    }

    /**
     * @param $imei
     * @return bool
     * 出库
     */
    public static function out($imei)
    {
        $data = [
            'imei'=>$imei,
            'type'=>self::STATUS_OUT,
            'create_time'=>time(),
        ];
        $model = self::create($data);
        if (!$model) {
            return false;
        }

        return true;
    }
}