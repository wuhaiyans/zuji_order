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
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\DB;
function apiResponse($data=[], $errno=0, $errmsg='')
{
    if (empty($errmsg)) {

        $errmsg =  ApiStatus::$errCodes[$errno];
    }
    return response()->json(['data'=>$data, 'code'=>$errno, 'msg'=>$errmsg]);
}

/**
 * @param array $data
 * @param int $errno
 * @param string $errmsg
 * @return array
 * 接口内部返回
 *
 */
function apiResponseArray($errno=0,$data=[], $errmsg='')
{
    if (empty($errmsg)) {

        $errmsg =  ApiStatus::$errCodes[$errno];
    }
    return ['data'=>$data, 'code'=>$errno, 'msg'=>$errmsg];
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
 * @param int $price    价格，单位：分
 * @return string   格式化价格，单位：元
 */
function priceFormat($price){
    $price = max(0,$price);
    return sprintf('%0.2f',$price);
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

/**
 * heaven
 * <p>规则：前缀（1位）+年（1位）+月（1位）+日（2位）+时间戳（5位）+毫秒（5位）+随机数（1位）</p>
 * @param $noType :1,分期交易号; 2,退货编号3.支付交易
 * 
 */
function createNo($noType=1){
    $npreNoType = array(
        //分期交易号
        1 => 'f',
        2 => 't',
        3 => 'P',
    );
    $year = array();
    if (!isset($npreNoType[$noType])) {
        return false;
    }
	// 年差值标记符，大写字母集[A-Z]
    for($i=65;$i<91;$i++){
        $year[]= strtoupper(chr($i));
    }
    $orderSn = $npreNoType[$noType].$year[(intval(date('Y')))-2018] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . rand(0, 9);
    return $orderSn;
}

/**
 * 根据key 二维数组分组
 * @param $arr 数组
 * @param $key 按照分组的key
 */
function array_group_by($arr, $key)
{
    $grouped = [];
    foreach ($arr as $value) {
        $grouped[$value[$key]][] = $value;
    }
    // Recursively build a nested grouping if more parameters are supplied
    // Each grouped array value is grouped according to the next sequential key
    if (func_num_args() > 2) {
        $args = func_get_args();
        foreach ($grouped as $key => $value) {
            $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
            $grouped[$key] = call_user_func_array('array_group_by', $parms);
        }
    }
    return $grouped;
}

/**
 * sql调试
 */
function sql_profiler()
{
    //sql调试
        DB::listen(function ($sql) {
            foreach ($sql->bindings as $i => $binding) {
                if ($binding instanceof \DateTime) {
                    $sql->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                } else {
                    if (is_string($binding)) {
                        $sql->bindings[$i] = "'$binding'";
                    }
                }
            }
            $query = str_replace(array('%', '?'), array('%%', '%s'), $sql->sql);
            $query = vsprintf($query, $sql->bindings);
            print_r($query);
            echo '<br />';
        });

}



function apiData()
{
    $data = request()->input();
    if (!$data || empty($data) || count($data) == 0) {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);
    }



    return $data;
}

/*
  *
  * 对象转换为数组
  * @param $d object
  * @author heavenwu
  * $@param type=1 多维对象 find  2一维对象 比如findfrist
  * return array
  */
function objectToArray($d, $type=1)
{
    //数据处理
    $result = array();
    if ($type == 1) {
        foreach ($d as $values) {
            if (!empty($values)) {
                $result[] = (array)($values['rows']);
            }
        }
    } else {

        foreach ($d as $keys=>$dValues) {
            $result[$keys] = $dValues;
        }
    }
    return $result;
}