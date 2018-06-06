<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 11:38
 */

namespace App\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use App\Warehouse\Modules\Func\Excel;
use App\Warehouse\Modules\Repository\ImeiRepository;
use App\Warehouse\Modules\Service\ImeiService;
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;


class DownloadController extends Controller
{

    public function imei()
    {
        $imei = new ImeiService();

        $params = request()->input();

        $items = $imei->export($params);

        $headers = ['imei','品牌','产品型号', '价格','苹果序列号','成色','颜色','运营商','存储空间','状态', '入库时间'];
        $excel = new \App\Lib\Excel();

        foreach ($items as $item) {
            $data[] = [
                $item['imei'],
                $item['brand'],
                $item['name'],
                $item['price'],
                $item['apple_serial'],
                $item['quality'],
                $item['color'],
                $item['business'],
                $item['storage'],
                $item['status'],
                $item['create_time']
            ];
        }

        return $excel->write('imei数据导出', $headers, $data);
    }



    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     *
     * imei模板文件下载
     * http://laravel.order/download/imeitpl
     */
    public function imeitpl()
    {
        $filePath = storage_path('app/download/imei_data_tpl.xls');
        ob_end_clean();
        return response()->download($filePath, 'imei导入模板文件.xls');
    }

}