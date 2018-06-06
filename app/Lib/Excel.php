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
     * 数字转字母 （类似于Excel列标）
     * @param Int $index 索引值
     * @param Int $start 字母起始值
     * @return String 返回字母
     */
    function intToChr($index, $start = 65) {
        $str = '';
        if (floor($index / 26) > 0) {
            $str .= $this->IntToChr(floor($index / 26)-1);
        }
        return $str . chr($index % 26 + $start);
    }


    /**
     * @param string $title
     * @param $headers
     * @param $body
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     *
     * 写文件
     */
    public function write($title='数据导出', $headers, $body)
    {
        if (!$headers || !$body) {
            return false;
        }

        $data = [];
        $rows = 1;
        foreach ($headers as $k => $v) {
            $data[$this->intToChr($k) . $rows] = $v;
        }

        foreach ($body as $k => $items) {
            $rows++;
            foreach ($items as $key => $item) {
                $data[$this->intToChr($key) . $rows] = $item;
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
        header("Content-Disposition: attachment; filename=" . $title . ".xlsx");
        $writer->save('php://output');
    }
}