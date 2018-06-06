<?php
/**
 *
 * @author: wansq
 * @since: 1.0
 * Date: 2018/5/28
 * Time: 20:46
 */

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class DownloadController extends \App\Http\Controllers\Controller
{
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


    /**
     * 导出excel
     */
    public function excel($headers, $body)
    {

    }
}