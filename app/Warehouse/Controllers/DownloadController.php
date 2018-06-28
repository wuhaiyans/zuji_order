<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 11:38
 */

namespace App\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use App\Warehouse\Models\Delivery;
use App\Warehouse\Modules\Func\Excel;
use App\Warehouse\Modules\Repository\ImeiRepository;
use App\Warehouse\Modules\Service\DeliveryService;
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

        if (!$items) {
            return false;
        }

        $headers = ['imei','品牌','产品型号', '价格','苹果序列号','成色','颜色','运营商','存储空间','状态', '入库时间'];
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
                date('Y-m-d H:i:s', $item['create_time'])
            ];
        }

        return \App\Lib\Excel::write($data, $headers,'imei数据导出');
    }


    /**
     * 待发货导出
     */
    public function deliverys()
    {
        $delivery = new DeliveryService();
        $params = request()->input();
        $items = $delivery->export($params);

        if (!$items) {
            return false;
        }

        $headers = ['订单号','客户名','手机号', '地址','物流单号','商品名','价格','状态'];

        $data = [];
        foreach ($items as $l) {
            if (!isset($l['goods']) || !$l['goods']) continue;
            foreach ($l['goods'] as $g) {
                $data[] = [
                    $l['order_no'],
                    $l['customer'],
                    $l['customer_mobile'],
                    $l['customer_address'],
                    $l['logistics_no'],
                    $g['goods_name'],
                    isset($g['price']) ? $g['price'] : 0.00,
                    Delivery::sta($l['status']),
                ];
            }
        }

        return \App\Lib\Excel::write($data, $headers,'待发货商品导出');
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
        if (ob_get_length()){
            ob_end_clean();
        }
//        $headers = [
//            'Content-Type:application/vnd.ms-excel',
//        ];
//        return response()->download($filePath, 'imei导入模板文件.xls', $headers);


//        $file_filesize = filesize($filePath);
//        $file = fopen($filePath, "r");
//        Header("Content-type: application/octet-stream");
//        Header("Accept-Ranges: bytes");
//        Header("Accept-Length: " . $file_filesize);
//        Header("Content-Disposition: attachment; filename=imei导入模板文件.xls");
//        echo fread($file, $file_filesize);
//        fclose($file);

        $filename=$filePath; //文件名
        Header( "Content-type:  application/vnd.ms-excel");
        Header( "Accept-Ranges:  bytes ");
        Header( "Accept-Length: " .filesize($filename));
        header( "Content-Disposition:  attachment;  filename=imei导入模板文件.xls");
//        echo file_get_contents($filename);
        readfile($filename);



    }

}