<?php
namespace App\Tools\Models;

use Illuminate\Database\Eloquent\Model;

class GreyTestModel extends Tool
{
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    // Rest omitted for brevity
    protected $table = 'tool_grey_test';

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
    protected $fillable = ['id','type','mobile','model_no','status'];

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
    
    public function getColumnsNames() {
        return (array) $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }
}