<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 13:50
 */

namespace App\Warehouse\Modules\Service;

use App\Warehouse\Models\Imei;
use App\Warehouse\Modules\Repository\ImeiRepository;
use Dotenv\Exception\InvalidFileException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Support\Facades\Storage;

class ImeiService
{

    //查找类型
    const SEARCH_NAME = 'name';//手机
    const SEARCH_BRAND = 'brand';//订单号
    const SEARCH_IMEI = 'imei';//订单号
    const SEARCH_COLOR = 'color';
    const SEARCH_BUSINESS = 'business'; //运营商
    const SEARCH_STORAGE = 'storage'; //存储


    public static function searchKws()
    {
        $ks = [
            self::SEARCH_NAME => '手机型号',
            self::SEARCH_BRAND => '手机品牌',
            self::SEARCH_IMEI => '设备imei',
            self::SEARCH_COLOR => '颜色',
            self::SEARCH_BUSINESS => '运营商',
            self::SEARCH_STORAGE => '存储'
        ];

        return $ks;
    }











    /**
     * 导入数据
     */
    public function import($data)
    {
        if (!ImeiRepository::import($data)) {
            throw new \Exception('导入imei数据失败');
        }
    }


    public function list($params)
    {
        $limit = 20;

        if (isset($params['size']) && $params['size']) {
            $limit = $params['size'];
        }
        $whereParams = [];


        $search = $this->paramsSearch($params);

        if ($search) {
            $whereParams = array_merge($whereParams, $search);
        }

        $logic_params = [];
        if (isset($params['begin_time']) && $params['begin_time']) {
            array_push($logic_params, ['create_time', '>=', strtotime($params['begin_time'])]);
        }


        if (isset($params['end_time']) && $params['end_time']) {
            array_push($logic_params, ['create_time', '<=', strtotime($params['end_time'].' 23:59:59')]);
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        $collect = ImeiRepository::list($whereParams,$logic_params , $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return [
                'data'=>[], 'size'=>$limit, 'page'=>$collect->currentPage(), 'total'=>$collect->total()
            ];
        }

        return ['data'=>$items, 'size'=>$limit, 'page'=>$collect->currentPage(), 'total'=>$collect->total()];
    }


    /**
     * 查找类型
     */
    public function paramsSearch($params)
    {
        if (!isset($params['kw_type']) || !$params['kw_type']) {
            return false;
        }

        if (!isset($params['keywords']) || !$params['keywords']) {
            return false;
        }

        return [$params['kw_type'] => $params['keywords']];
    }


    /**
     * @param $params  对imei的查询条件
     * @return array
     */
    public function export($params)
    {
        $limit = 20;

        if (isset($params['size']) && $params['size']) {
            $limit = $params['size'];
        }
        $whereParams = [];

        $search = $this->paramsSearch($params);

        if ($search) {
            $whereParams = array_merge($whereParams, $search);
        }


        $logic_params = [];
        if (isset($params['begin_time']) && $params['begin_time']) {
            array_push($logic_params, ['create_time', '>=', strtotime($params['begin_time'])]);
        }


        if (isset($params['end_time']) && $params['end_time']) {
            array_push($logic_params, ['create_time', '<=', strtotime($params['end_time'])]);
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        $collect = ImeiRepository::list($whereParams,$logic_params , $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return false;
        }

        return $items;

//        $headers = ['imei','brand','name', 'price','apple_serial','quality','color','business','storage','status', 'create_time'];
    }

    /**
     * @param Request $request
     *
     * 上传imei文件
     */
    public static function upload()
    {
        $request = request();


        Log::error($request->input());
        Log::error($_FILES);


        if (!$request->isMethod('post')) {
            throw new MethodNotAllowedHttpException('请使用post方法上传文件');
        }


        $files = $request->file('params');
        if (!$files) {
            throw new InvalidFileException('上传文件失败');
        }

        $file = $files['imei_file'];
        if (!$file->isValid()) {
            throw new InvalidFileException('上传文件失败');
        }
        $ext = $file->getClientOriginalExtension();     // 扩展名
        $realPath = $file->getRealPath();   //临时文件的绝对路径

        // 上传文件
        $filename = date('Y-m-d-H-i-s') . '-' . uniqid() . '.' . $ext;

        $bool = Storage::disk('uploads')->put($filename, file_get_contents($realPath));


        if ($bool) {
            return storage_path('app/uploads') . '/' . $filename;
        }

        throw new InvalidFileException('上传文件失败');
    }
}