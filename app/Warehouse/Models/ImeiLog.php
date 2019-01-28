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

    protected $primaryKey='id';
    /**
     * imei状态
     */
    const STATUS_CNACEN = 0;//取消
    const STATUS_IN     = 1;//入库
    const STATUS_OUT    = 2;//出库

    /**
     * 租期类型状态
     */
    const ZUQI_TYPE_MONTH   = 2;//月
    const ZUQI_TYPE_DAY     = 1;//日
    const ZUQI_TYPE_0       = 0;//无

    /**
     *
     * 可填充字段
     */
    protected $fillable = [
        'imei', 'type', 'create_time', 'order_no','id', 'imei_id', 'zuqi', 'zuqi_type'
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
     * @param null $status 不传值或传null时，返回状态列表，否则返回对应状态值
     * @return array|mixed|string
     *
     * 获取状态列表，或状态值
     */
    public static function zuqi_type($status=null)
    {
        $st = [
            self::ZUQI_TYPE_MONTH   => '月',
            self::ZUQI_TYPE_DAY     => '日',
            self::ZUQI_TYPE_0       => '无',
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
    public static function in($imei,$order_no=0,$zuqi=0,$zuqi_type=0)
    {
        $imei_row = Imei::where(['imei'=>$imei])->first();
        if($imei_row){
            $imei_row=$imei_row->toArray();
        }else{
            return false;
        }
        $data = [
            'imei'=>$imei,
            'type'=>self::STATUS_IN,
            'create_time'=>time(),
            'order_no'=>$order_no,
            'imei_id'=>$imei_row['id'],
            'zuqi'=>$zuqi,
            'zuqi_type'=>$zuqi_type,
        ];
        $model = self::create($data);
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
    public static function out($imei,$order_no=0,$zuqi=0,$zuqi_type=0)
    {
        $imei_row = Imei::where(['imei'=>$imei])->first();
        if($imei_row){
            $imei_row=$imei_row->toArray();
        }else{
            return false;
        }
        $data = [
            'imei'=>$imei,
            'type'=>self::STATUS_OUT,
            'create_time'=>time(),
            'order_no'=>$order_no,
            'imei_id'=>$imei_row['id'],
            'zuqi'=>$zuqi,
            'zuqi_type'=>$zuqi_type,
        ];
        $model = self::create($data);
        if (!$model) {
            return false;
        }

        return true;
    }
}