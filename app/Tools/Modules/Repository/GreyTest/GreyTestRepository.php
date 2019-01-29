<?php
namespace App\Tools\Modules\Repository\GreyTest;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Models\GreyTestModel;

class GreyTestRepository
{
    protected $greyTest = [];

    public function __construct(GreyTestModel $greyTestModel = null)
    {
        $this->greyTest = $greyTestModel;
    }
    
    /**
     * 获取couponModel元数据
     * @return array
     */
    public function toArray() : array
    {
        return $this->greyTest->toArray();
    }
    
    public static function getOne(array $where)
    {
        $greyTest = GreyTestModel::query()->where($where)->firstOrNew([]);
        return new self($greyTest);
    }
    
    public function setAttribute(string $key , $value = '')
    {
        return $this->greyTest->setAttribute($key , $value);
        return $this->greyTest;
    }
    
    public static function create(string $mobile , string $model_no) : bool
    {
        $greyTest = new GreyTestModel();
        $greyTest->setAttribute('mobile',$mobile);
        $greyTest->setAttribute('model_no',$model_no);
        $greyTest->setAttribute('create_time',time());
        return $greyTest->save();
    }
    
    public function stop()
    {
        $this->greyTest->setAttribute('status', 2);
        $this->greyTest->setAttribute('update_time', time());
        return $this->greyTest->save();
    }
    
    public function update(array $params) : bool
    {
        $this->setter($params);
        return $this->greyTest->save();
    }
    
    /**
     * 数据模型setter
     * @param array $setFields ['start_time'=>1111111111,'end_time'=>222222222]
     * @return boolean
     */
    private function setter(array $setFields)
    {
        if( is_array($setFields) && !empty($setFields) ){
            foreach($setFields as $fields => $value){
                if( in_array($fields , $this->greyTest->getColumnsNames()) ){
                    $this->greyTest->setAttribute($fields , $value);
                }
            }
        }
    }
}