<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author qinliping <qinliping@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;
use Illuminate\Http\Request;
use App\Lib\ApiStatus;
use App\Activity\Modules\Service\ActivityExperience;

class ActivityExperienceController extends Controller
{


    /***
     * 1元体验列表
     * @return array
     */
    public function experienceList(){
        $experienceList=ActivityExperience::experienceList();
        return apiResponse($experienceList,ApiStatus::CODE_0);
    }


}