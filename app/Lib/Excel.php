<?php
/**
 *
 * @author: wansq
 * @since: 1.0
 * Date: 2018/6/5
 * Time: 16:35
 */

namespace App\Lib;
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
        ob_end_clean();

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        header("Content-Type:application/download");
        header("Content-Disposition: attachment; filename=" . $title . ".csv");
        $writer->save('php://output');
    }
}