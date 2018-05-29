<?php
/**
 *
 * @author: wansq
 * @since: 1.0
 * Date: 2018/5/28
 * Time: 19:53
 */

namespace App\Warehouse\Modules\Func;

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