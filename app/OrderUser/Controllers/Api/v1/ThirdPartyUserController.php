<?php
/**
 * User: jinlin wang
 * Date: 2018/8/1
 * Time: 16:38
 */
namespace App\OrderUser\Controllers\Api\v1;


use App\OrderUser\Modules\Service\ThirdPartyUserService;

class ThirdPartyUserController extends Controller
{

    /**
     * 第三方平台下单用户管理 列表
     */
    public function lists()
    {
        $params = $this->_dealParams([]);

        $list = ThirdPartyUserService::lists($params);
        return \apiResponse($list);
    }

    /**
     * 导入历史已下单用户execl表
     */
    public function excel(){

    }

    /**
     * 公共数据
     */
    public function publics()
    {
        $data = [
//            'status_list' => Imei::sta(),
            'kw_types'    => ImeiService::searchKws()
        ];
        return apiResponse($data);
    }

}