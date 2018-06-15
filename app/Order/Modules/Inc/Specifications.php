<?php
namespace App\Order\Modules\Inc;

/**
 * 订单中商品规格 对象
 *
 * @author 
 */
class Specifications {
    
    /**
     * 商品规格 输入过滤
     * @param array $specs_arr
     * [
     *	[
     *	    'name' => '',
     *	    'value' => '',
     *	]
     * ]
     * @param array $specs_arr
     */
    public static function input_filter( &$specs_arr ){
	foreach( $specs_arr as &$it ){
	    $it['name'] = str_replace([':',';'],'',$it['name']);
	    $it['value'] = str_replace([':',';'],'',$it['value']);
	}
    }
    
    /**
     * 订单商品的规格 输入格式化
     * 二维数组 转成 字符串
     * @param array $specs_arr
     * [
     *	[
     *	    'name' => '',
     *	    'value' => '',
     *	]
     * ]
     * @return string
     */
    public static function input_format( $specs_arr ){
	// 过滤 规格名称和规格值中的特殊字符
        Specifications::input_filter($specs_arr);
	$arr = [];
	foreach( $specs_arr as $it ){
	    $arr[] = $it['name'].':'.$it['value'];
	}
	return implode(';', $arr);
    }

    /**
     * 订单商品的规格 输出格式化
     * 字符串 转成 二维数组
     * @param string    $specs_str
     * @return array
     * [
     *	[
     *	    'name' => '',
     *	    'value' => '',
     *	]
     * ]
     */
    public static function output_format( $specs_str ){
	$specs_arr = explode(';', $specs_str);
	$arr = [];
	foreach( $specs_arr as $it ){
	    $_arr = [];
	    list($_arr['name'],$_arr['value']) = explode(':', $it);
	    $arr[] = $_arr;
	}
	return $arr;
    }
    
}
