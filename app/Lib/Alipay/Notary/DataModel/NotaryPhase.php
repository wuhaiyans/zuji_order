<?php

/**
 * 蚂蚁金服 金融科技 可信存证 数据模型 存证 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Lib\Alipay\Notary\DataModel;

use Illuminate\Database\Eloquent\Model;
/**
 * 蚂蚁金服 金融科技 可信存证 数据模型 存证 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class NotaryPhase extends Model
{
    const CREATED_AT = 'create_time';

    // Rest omitted for brevity

    protected $table = 'order_notary_phase';

    protected $primaryKey='id';
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
    protected $fillable = [];

    /**
     * 获取当前时间
     *
     * @return int
     */
    public function freshTimestamp() {
        return time();
    }

    /**
     * 避免转换时间戳为时间字符串
     *
     * @param DateTime|int $value
     * @return DateTime|int
     */
    public function fromDateTime($value) {
        return $value;
    }
}