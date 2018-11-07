<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActivityTheme;

class ActivityThemeRepository
{

    /*
     *  获取活动主题信息
     * @param $id int 活动主题id
     * @return $data
     */
   public static function getInfo($id){
       $data = ActivityTheme::query()->where(['id='.$id])->first();
       if (!$data) return false;
       return $data->toArray();
   }
}