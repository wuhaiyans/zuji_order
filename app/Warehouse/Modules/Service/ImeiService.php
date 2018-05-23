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
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Support\Facades\Storage;

class ImeiService
{
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

        if (isset($params['limit']) && $params['limit']) {
            $limit = $params['limit'];
        }
        $whereParams = [];

        if (isset($params['imei']) && $params['imei']) {
            $whereParams['imei'] = $params['imei'];
        }

        $page = isset($params['page']) ? $params['page'] : null;

        $whereParams['status'] = [Imei::STATUS_OUT, Imei::STATUS_IN];

        $collect = ImeiRepository::list($whereParams, $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return [];
        }

        return ['data'=>$items, 'limit'=>$limit, 'page'=>$page];
    }

    /**
     * @param Request $request
     *
     * 上传imei文件
     */
    public static function upload()
    {
        $request = request();

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