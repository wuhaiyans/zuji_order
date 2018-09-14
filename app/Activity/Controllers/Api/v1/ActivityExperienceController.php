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
    protected $ActivityExperience;
    public function __construct(ActivityExperience $ActivityExperience)
    {
        $this->ActivityExperience = $ActivityExperience;
    }


}