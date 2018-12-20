<?php
/**
 * 收发货预警
 *
 * @author wangjinlin
 */
namespace App\Warehouse\Modules\Service;

use App\Lib\Common\LogApi;

class WarehouseWarning
{

    protected static $toEmail = array (
        'wangjinlin@huishoubao.com.cn'
    );

    /**
     * 预警
     * @param $msg  警报标题
     * @param array $data  警报内容
     */
    public static function warningWarehouse($msg='',  $data= array())
    {
        $msg = '[warningWarehouse]'.$msg;
        LogApi::alert($msg, $data,self::$toEmail);
    }



}