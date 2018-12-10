<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 11:38
 */

namespace App\Warehouse\Controllers\Api\v1;

use App\Warehouse\Models\Imei;
use App\Warehouse\Modules\Func\Excel;
use App\Warehouse\Modules\Repository\ImeiRepository;
use App\Warehouse\Modules\Service\ImeiService;
use App\Lib\ApiStatus;
use App\Warehouse\Modules\Service\WarehouseWarning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Mockery\CountValidator\Exception;


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
        $request = request()->input();
        $params['channel_id'] = json_decode($request['userinfo']['channel_id'], true);
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

        $limit = isset($params['size']) ? $params['size'] : 10;
        $limit = $limit > 50 ? 50 : $limit;//最大50

        try {
            $imeis = ImeiRepository::search($params['imei'], $limit);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse(['imeis' => $imeis]);
    }

    /**
     * 根据IMEI查询返回一条记录
     *     前段修改用
     *
     * @param string $imei
     * @return array 一维数组
     */
    public function getRow(){
        $rules = ['imei' => 'required'];
        $params = $this->_dealParams($rules);

        try{
            $row = ImeiRepository::getRow($params['imei']);
        } catch (\Exception $e){
            return apiResponse([], ApiStatus::CODE_42002, $e->getMessage());
        }
        return \apiResponse([$row]);
    }

    /**
     * 修改IMEI
     *      1.出库不可修改
     *      2.每次修改完批量记录修改日志
     *
     * @param int id
     * @param array data
     * @return []
     */
    public function setRow(){
        $rules = [
            'id' => 'required',
            'data' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if(empty($params['data']) || empty($params['id'])){
            return apiResponse([], ApiStatus::CODE_20001, '参数错误');
        }
        DB::beginTransaction();
        try{
            ImeiRepository::setRow($params['id'],$params['data']);
            DB::commit();
        } catch (\Exception $e){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_42003, $e->getMessage());
        }
        return apiResponse([]);

    }

    /**
     * 查询IMEI日志
     *      1.出库入库日志
     *      2.修改IMEI字段日志
     */
    public function getImeiLog(){
        $rules = [
            'id' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if(empty($params['id'])){
            return apiResponse([], ApiStatus::CODE_20001, '参数错误');
        }
        try{
            $data = ImeiRepository::getImeiLog($params['id']);
        } catch (\Exception $e){
            return apiResponse([], ApiStatus::CODE_42003, $e->getMessage());
        }
        return apiResponse($data);

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
            WarehouseWarning::warningWarehouse('[导入imei]失败',[$params,$e]);
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 导出excel数据
     */
    public function export()
    {
        $params = $this->_dealParams([]);
        $list = $this->imei->export($params);

        return redirect()->action('UserController@profile', ['id'=>1]);
        //return \apiResponse($list);
    }


    /**
     * 下载 imei 导入模板文件
     */
//    public function downTpl()
//    {
//
//        $filePath = storage_path('/app/download/imei_data_tpl.xlsx');
//
//        $file = fopen ( $filePath, "r" );
//
//        Header ( "Content-type: application/octet-stream" );
//        Header ( "Accept-Ranges: bytes" );
//        Header ( "Accept-Length: " . filesize ( $filePath ) );
//        Header ( "Content-Disposition: attachment; filename=" . $filePath );
//        //输出文件内容
//        //读取文件内容并直接输出到浏览器
//        echo fread ( $file, filesize ( $filePath ) );
//        fclose ( $file );
//        exit ();
//
//
//        return response()->download($filePath,'imei数据导入模板.xls');
//    }

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
            $imeis = [];
            foreach ($data as $cel) {
                if (!isset($cel['A']) || !isset($cel['B'])) continue;
                array_push($imeis, $cel['A']);
                $result[] = [
                    'imei'          => isset($cel['A']) ? $cel['A'] :'',
                    'price'         => isset($cel['B']) ? (float)$cel['B'] : 0,
                    'apple_serial'  => isset($cel['C']) ? $cel['C'] : '',
                    'brand'         => isset($cel['D']) ? $cel['D'] : '',
                    'name'          => isset($cel['E']) ? $cel['E']:'',
                    'quality'       => isset($cel['F']) ? intval($cel['F']):100,
                    'color'         => isset($cel['G']) ? $cel['G']:'',
                    'business'      => isset($cel['H']) ? $cel['H']:'',
                    'storage'       => isset($cel['I']) ? intval($cel['I']):0
                ];
            }
            $oldImeis = Imei::whereIn('imei', $imeis)->get();

            if ($oldImeis) {
                $oImeis = [];
                foreach ($oldImeis as $oi) {
                    array_push($oImeis, $oi->imei);
                }
                foreach ($result as $k => $r) {
                    if (in_array($r['imei'], $oImeis)) {
                        unset($result[$k]);
                    }
                }
            }

            if (!$result)
                return \apiResponse([], ApiStatus::CODE_20001, '没有需要导入的数据,可能是数据已经导入过');

            $this->imei->import($result);
            DB::commit();
        } catch (\Exception $e) {
            Log::error('imei数据导入出错');
            DB::rollBack();

            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse();
    }

    /**
     * 共共数据
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