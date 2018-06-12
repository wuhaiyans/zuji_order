<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Lib;


class PublicInc
{
    //-+--------------------------------------------------------------------
    // | 角色
    //-+--------------------------------------------------------------------
    const Type_User = 2;    //用户
    const Type_Admin = 1; //管理员
    const Type_System = 3; // 系统自动化任务
    const Type_Store =4;//线下门店
    /**
     * 角色列表
     * @return array
     */
    public static function getRoleList(){
        return [
            self::Type_User => '买家',
            self::Type_Admin => '卖家',
            self::Type_System => '系统',
            self::Type_Store => '门店',
        ];
    }
    
    /**
     *  获取角色名称
     * @param int $status
     * @return string
     */
    public static function getRoleName($role){
        $list = self::getRoleList();
        if( isset($list[$role]) ){
            return $list[$role];
        }
        return '';
    }
}