<?php
/**
 *
 * @author: jinlin wang
 * @since: 1.0
 * Date: 2018/8/1
 * Time: 17:14
 */
namespace App\OrderUser\Modules\Func;

use PhpOffice\PhpSpreadsheet\IOFactory;

class Excel
{
    /**
     * 读取excel数据
     */
    public static function read($filepath)
    {
        $spreadsheet = IOFactory::load($filepath);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(
            null,
            true,
            true,
            true
        );
        return $sheetData;
    }
}