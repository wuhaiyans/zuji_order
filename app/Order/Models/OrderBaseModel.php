<?php
/**
 * 数据模型 基类
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 数据模型 基类
 * 定义 基本的一些属性（为了统一）
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class OrderBaseModel  extends Model
{
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
	
    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = true;
	
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
