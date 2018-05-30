<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 11:38
 */

namespace App\Warehouse\Controllers\Api\v1;

use App\Warehouse\Modules\Func\Excel;
use App\Warehouse\Modules\Repository\ImeiRepository;
use App\Warehouse\Modules\Service\ImeiService;
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;


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
     * @param $imei
     * 前端imei模糊查询
     */
    public function search()
    {
        $rules = ['imei' => 'required'];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $limit = $limit > 50 ? 50 : $limit;//最大50

        try {
            $imeis = ImeiRepository::search($params['imei'], $limit);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse(['imeis' => $imeis]);
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
     * 下载 imei 导入模板文件
     */
    public function downTpl()
    {

        $filePath = storage_path('/app/download/imei_data_tpl.xlsx');

        $file = fopen ( $filePath, "r" );

        Header ( "Content-type: application/octet-stream" );
        Header ( "Accept-Ranges: bytes" );
        Header ( "Accept-Length: " . filesize ( $filePath ) );
        Header ( "Content-Disposition: attachment; filename=" . $filePath );
        //输出文件内容
        //读取文件内容并直接输出到浏览器
        echo fread ( $file, filesize ( $filePath ) );
        fclose ( $file );
        exit ();







        return response()->download($filePath,'imei数据导入模板.xls');
    }

    /**
     * 上传文件
     */
    public function importFromExcel(Request $request)
    {
        $inputFileName = ImeiService::upload($request);
        $data = Excel::read($inputFileName);

        unset($data[1]);//第一行文档名
        unset($data[2]);//第二行标题

        if (count($data) == 0) {
            return ;
        }

        try {
            DB::beginTransaction();

            $result = [];
            foreach ($data as $cel) {
                if (!isset($cel['A']) || !isset($cel['B'])) continue;
                $result[] = [
                    'imei' => $cel['A'],
                    'price' => $cel['B']
                ];
            }
            $this->imei->import($result);
            DB::commit();
        } catch (\Exception $e) {
            Log::error('imei数据导入出错');
            DB::rollBack();

            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse();
    }


}