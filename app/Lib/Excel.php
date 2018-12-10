<?php
/**
 *
 * @author: wansq
 * @since: 1.0
 * Date: 2018/6/5
 * Time: 16:35
 */

namespace App\Lib;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class Excel
{

    /**
     * 改造成静态方法类
     * 数字转字母 （类似于Excel列标）
     * @param Int $index 索引值
     * @param Int $start 字母起始值
     * @return String 返回字母
     */
    private static function intToChr($index, $start = 65) {
        $str = '';
        if (floor($index / 26) > 0) {
            $str .= self::IntToChr(floor($index / 26)-1);
        }
        return $str . chr($index % 26 + $start);
    }


    /**
 * @param string $title
 * @param $headers 标题 【可选】
 * @param $body 主体内容 【必须】二维数组
 * @return bool
 *
 * 写文件
 */
    public static function write($body, $headers=[] , $title='数据导出')
    {
        if (!$headers || !$body) {
            return false;
        }

        $data = [];
        $rows = 1;

        if ($headers) {
            foreach ($headers as $k => $v) {
                $data[self::intToChr($k) . $rows] = $v;
            }
        }

        foreach ($body as $k => $items) {
            $rows++;
            foreach ($items as $key => $item) {
                $data[self::intToChr($key) . $rows] = $item;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();


        foreach ($data as $k => $v) {
            $sheet->setCellValue($k, $v);
        }
        if (ob_get_length()> 0) {
            ob_end_clean();
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        header("Content-Type:application/download");
        header("Content-Disposition: attachment; filename=" . $title . ".xlsx");
        $writer->save('php://output');
    }

    /**
     * 导出数据到本地服务器
     * @param $headers 标题 【可选】
     * @param $body 主体内容 【必须】二维数组
     * @param $title 标题 【必须】string
     * @param $path 文件目录 【必须】
     * @return bool
     *
     * 写文件
     */
    public static function localWrite($body,$headers, $title='数据导出',$path)
    {
        if (!$headers || !$body) {
            return false;
        }

        $data = [];
        $rows = 1;

        if ($headers) {
            foreach ($headers as $k => $v) {
                $data[self::intToChr($k) . $rows] = $v;
            }
        }

        foreach ($body as $k => $items) {
            $rows++;
            foreach ($items as $key => $item) {
                $data[self::intToChr($key) . $rows] = $item;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($data as $k => $v) {
            $sheet->setCellValue($k, $v);
        }
        if (ob_get_length()> 0) {
            ob_end_clean();
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save(dirname(dirname(dirname(__FILE__)))."/public/excel/".$path."/".$title.".xlsx");
    }
    public static function csvWrite($body, $headers=[] , $name='数据导出')
    {


        // csv文件内容不要以字母开始
        $title = '报表'."\n";
        // 准备字段
        $titles = [
            'id' => 'ID',
            'type' => '类型',
            'content' => '内容',
            'create_time' => '创建时间',
            'mark' => '备注'
        ];
        $fields = '';
        foreach ($titles as $k => $v) {
            $title .= $v.',';
            $fields .= $k.',';
        }
        $fields = rtrim($fields, ',');
    // 数据库查询
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=test', 'root', 'root');
        $res = $pdo->query('SELECT '.$fields.' from excel_test LIMIT 100000');
        $res = $res->fetchAll(PDO::FETCH_ASSOC);
    //  结果处理
        $csv = $title."\n";
        $fields = explode(',', $fields);
        foreach ($res as $value) {
            $row = '';
            foreach ($fields as $field) {
                // 按照 fputcsv() 函数的处理方式
                if (strpos($value[$field],',') !== FALSE || strpos($value[$field],'"') !== FALSE) {
                    $row .= '"'.str_replace('"','""',$value[$field]).'",';
                }else{
                    $row .= $value[$field].',';
                }
            }
            $csv .= $row."\n";
        }
        file_put_contents($name,mb_convert_encoding($csv, "GBK", "UTF-8"),FILE_APPEND);


    }



    public static function csvWrite1($export_data, $column_name=[] , $title='数据导出', $curPage=2)
    {
        if ($curPage==2) {

            header("Content-type:application/vnd.ms-excel");
            header("Content-Disposition:filename=" . iconv("UTF-8", "GB18030", ".$title") . ".csv");

        }
//       打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        if ($curPage==2) {
            // 将中文标题转换编码，否则乱码
            foreach ($column_name as $i => $v) {
                $column_name[$i] = iconv('utf-8', 'GB18030', $v);
            }
            // 将标题名称通过fputcsv写到文件句柄
            fputcsv($fp, $column_name);
        }


//        $pre_count = 5000;
//        for ($i=0;$i<intval($total_export_count/$pre_count)+1;$i++){
//            $export_data = $db->getAll($sql." limit ".strval($i*$pre_count).",{$pre_count}");
            foreach ( $export_data as $item ) {
                $rows = array();
                foreach ( $item as $export_obj){
                    $rows[] = iconv('utf-8', 'GB18030', $export_obj);
                }
                fputcsv($fp, $rows);
            }

            // 将已经写到csv中的数据存储变量销毁，释放内存占用
            unset($export_data);
            if(ob_get_level()>0){

                ob_flush();
                flush();
            }




    }

    public static function csvOrderListWrite($export_data, $fp , $title='数据导出')
    {

//        $pre_count = 5000;
//        for ($i=0;$i<intval($total_export_count/$pre_count)+1;$i++){
//            $export_data = $db->getAll($sql." limit ".strval($i*$pre_count).",{$pre_count}");
            foreach ( $export_data as $item ) {
                $rows = array();
                foreach ( $item as $export_obj){
                    $rows[] = iconv('utf-8', 'GB18030', $export_obj);
                }
                fputcsv($fp, $rows);
            }

            // 将已经写到csv中的数据存储变量销毁，释放内存占用
            unset($export_data);
            if(ob_get_level()>0){

                ob_flush();
                flush();
            }



    }
    public static function csvOppointmentListWrite($export_data, $fp , $title='预订单列表导出')
    {

//        $pre_count = 5000;
//        for ($i=0;$i<intval($total_export_count/$pre_count)+1;$i++){
//            $export_data = $db->getAll($sql." limit ".strval($i*$pre_count).",{$pre_count}");
        foreach ( $export_data as $item ) {
            $rows = array();
            foreach ( $item as $export_obj){
                $rows[] = iconv('utf-8', 'GB18030', $export_obj);
            }
            fputcsv($fp, $rows);
        }

        // 将已经写到csv中的数据存储变量销毁，释放内存占用
        unset($export_data);
        if(ob_get_level()>0){

            ob_flush();
            flush();
        }



    }

    /**
     * 导出多个工作表到本地服务器
     * @param $headers 标题 【可选】二维数组
     * @param $body 主体内容 【必须】二维数组
     * @param $fileName 文件名称 【必须】string
     * @param $title 工作表名称 【必须】二维数组
     * @param $path 文件目录 【必须】
     * @return bool
     *
     * 写文件
     */
    public static function xlsxExport($body,$headers,$title, $fileName='数据导出',$path)
    {
        if ( !$body) {
            return false;
        }



        $spreadsheet = new Spreadsheet();

        foreach($body as $keys=>$value){
            $data = [];
            $rows = 1;
            if ($headers) {
                foreach ($headers[$keys] as $k => $v) {
                    $data[self::intToChr($k) . $rows] = $v;
                }
            }

            foreach ($value as $k => $items) {
                $rows++;
                foreach ($items as $key => $item) {
                    $data[self::intToChr($key) . $rows] = $item;
                }
            }

            if($keys==0){
                $spreadsheet->getSheet($keys)->setTitle($title[$keys]);
            }
            else{
                $spreadsheet->createSheet();
                $spreadsheet->getSheet($keys)->setTitle($title[$keys]);
            }
            $sheet = $spreadsheet->setActiveSheetIndex($keys);
            foreach ($data as $k => $v) {
                $sheet->setCellValue($k, $v);
            }
        }
        $spreadsheet->setActiveSheetIndex(0);
        if (ob_get_length()> 0) {
            ob_end_clean();
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save(dirname(dirname(dirname(__FILE__)))."/public/excel/".$path."/".$fileName.".xlsx");
    }
}