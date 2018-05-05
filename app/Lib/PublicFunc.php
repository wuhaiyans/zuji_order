<?php
/**
 * Created by PhpStorm.
 * User: FF
 * Date: 2018/4/28
 * Time: 15:12
 */
/**
 * api返回消息
 * @param $data
 * @param int $errno
 * @param string $errmsg
 * @return \Illuminate\Http\JsonResponse
 *
 */
function apiResponse($data=[], $errno=0, $errmsg='')
{
    return response()->json(['data'=>$data, 'code'=>$errno, 'msg'=>$errmsg]);
}

/**
 * 过滤数组
 * @param	array	$data	    【必须】关联数组
 * @param	array	$filters    【必须】校验器数组（支持管道符）
 * @param	array	$failed_list【可选】不满足过滤条件的元素组成的数组
 * @return  array	返回满足过滤条件的元素组成的数组
 */
function filter_array( $data, array $filters, &$failed_list=[] ){
    $result = [];
    if( !is_array($data) || count($data)==0 ){
        return $result;
    }
    // 循环数据，调用对应校验器，进行过滤
    foreach( $data as $k => $v ){
        // 判断必须
        if( isset($filters[$k]) && $filters[$k] ){
            if( filter_value($v, $filters[$k]) ){
                // 过滤成功，或没有指定过滤器
                $result[$k] = $v;
            }else{// 过滤失败的数据
                $failed_list[$k] = $v;
            }
        }
    }
    return $result;
}

/**
 * 过滤值
 * @param   mixed   $value  【必须】待过滤的值
 * @param   mixed   $filter 【必须】校验规则
 * @return boolean  true：满足过滤条件；false：不满足
 */
function filter_value( $value, $filter ){
    if( is_string($filter) ){
        $filter = explode('|', $filter);
    }
    foreach( $filter as $it ){
        if( !is_callable($it) ){
            throw new \Exception( '[filter] '.$it.' not found' );
        }
        $b = call_user_func( $it, $value );
        // 过滤失败
        if( $b===false || $b===null ){
            return false;
        }
    }
    return true;
}
//-+----------------------------------------------------------------------------
// | 变量值 校验函数
//-+----------------------------------------------------------------------------
// 值必须存在（非空，）
function required($v){
    return !is_null($v);
}
// 可以转换成true
function is_true($v){
    return !!$v;
}
/**
 * 判断价格，单位：元
 * 小数点后两位
 * @param string $price
 * @return boolean
 */
function is_price($price){
    if( !is_numeric($price) ){
        return false;
    }
    if( $price<1 ){
        return !!preg_match('/^0(\.\d{0,2})?$/', ''.$price);
    }
    return !!preg_match('/^[1-9]\d{0,8}(\.\d{0,2})?$/', ''.$price);
}

/**
 * 校验是否为 分页值
 * @param mixed $page
 * @return boolean
 */
function is_page($page){
    if(intval($page) != $page){
        return false;
    }
    if( $page<1 ){// 不可用小于1
        return false;
    }
    return true;
}

/**
 * 校验是否为 分页大小
 * @param mixed $page
 * @return boolean
 */
function is_size($size){
    if(intval($size) != $size){
        return false;
    }
    if( $size<0 ){// 非负数，可以为0
        return false;
    }
    return true;
}

/**
 * url格式判断
 * @param string $string
 * @return boolean
 */
function is_url($string){
    if (!empty($string)) {
        // 验证url格式
        $string = filter_var($string, FILTER_VALIDATE_URL);
        return $string!=false;
    }
    return FALSE;
}

/**
 * 判断值是否是主键自增ID格式
 */
function is_id( $id ){
    if(is_numeric($id) && $id>0 && $id==intval($id)){
        return true;
    }
    return false;
}

/**
 * 判断值是否是时间戳
 */
function is_time( $time ){
    return is_id($time);
}


//-+----------------------------------------------------------------------------
// | 数组 解析成 json 展示
//-+----------------------------------------------------------------------------
/**
 * 判断是否为关联数组
 * @param array $arr
 * @return boolean
 */
function is_assoc_arr( $arr ){
    if( is_array($arr) && count($arr) ){
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    return false;
}

/**
 * 错误提示
 * @global string $GLOBALS['__OEEOR__']
 * @name $__OEEOR__
 */

$GLOBALS['__OEEOR__'] = '';

/**
 *  设置错误提示
 * @param type $error
 */
function set_error( $error ){
    $GLOBALS['__OEEOR__'] = $error;
}
/**
 * 获取错误提示
 * @return type
 */
function get_error( ){
    return $GLOBALS['__OEEOR__'];
}
$GLOBALS['__DEBUGERROR__'] =[];
/**
 *  设置错误提示
 * @param type $error
 */
function set_debug_error( $error ){
    if($error==''){
        $GLOBALS['__DEBUGERROR__']=[];
    }else{
        $GLOBALS['__DEBUGERROR__'][] = $error;
    }
}
/**
 * 获取错误提示
 * @return type
 */
function get_debug_error( ){
    return $GLOBALS['__DEBUGERROR__'];
}



/**
 * 打印函数 print_r
 * @param $data 打印数组
 */
function p($data, $exit = '')
{
    echo "<pre>";
    print_r($data);
    if($exit == ""){
        exit;
    }
}
/**
 * 打印函数 var_dump
 * @param $data 打印数组
 */
function v($data, $exit = '')
{
    echo "<pre>";
    var_dump($data);
    if($exit == ""){
        exit;
    }
}
