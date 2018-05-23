<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 11:38
 */

namespace App\Warehouse\Controllers\Api\v1;

use App\Warehouse\Modules\Service\ImeiService;
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\Request;

class ImeiController extends Controller
{

    protected $imei;

    /**
     * ImeiController constructor.
     *
     */
    public function __construct(ImeiService $imei)
    {
        $this->imei = $imei;
    }

    /**
     * imei 列表
     */
    public function list()
    {
        $params = $this->_dealParams([]);
        $list = $this->imei->list($params);
        return \apiResponse($list);
    }


    /**
     * 导入imei
     */
    public function import()
    {

        $rules = ['imeis' => 'required'];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->imei->import($params['imeis']);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }


    /**
     * 上传文件
     */
    public function upload(Request $request)
    {
        $filepath = ImeiService::upload($request);

        dd($filepath);

    }


}