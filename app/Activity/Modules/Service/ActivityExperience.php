<?php
/**
 * 1元活动
 * @access public (访问修饰符)
 * @author qinliping <qinliping@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Activity\Modules\Service;



use App\Activity\Modules\Inc\DestineStatus;
use App\Activity\Modules\Repository\ActivityExperienceRepository;

class ActivityExperience
{
    /**
     * 获取体验活动列表
     */
    public static function experienceList(){
       $experienceList = ActivityExperienceRepository::getActivityExperienceInfo();//获取体验活动信息
       if(!$experienceList){
           return false;
       }
       foreach($experienceList as $key=>$item){
           $experienceList[$key]['group_type_name'] = DestineStatus::getActivityTypeName($item['group_type']);   //活动分组名称
           $experienceList[$key]['experience_status_name'] = DestineStatus::getExperienceStatusName($item['experience_status']);  //体验状态名称
           $experienceList[$key]['activity_name'] = DestineStatus::getExperienceActivityStatusName($item['activity_id']);   //活动类型
           $new_arr[$item['group_type']][] = $item;
       }
       return $new_arr;

    }


}